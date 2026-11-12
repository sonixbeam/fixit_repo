<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_once __DIR__ . '/../includes/repair-completion.php';

$user = requireRole('STUDENT');
$userId = (int) $user['user_id'];

// ตรวจสอบ ID
$requestId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$requestId) {
    http_response_code(400);
    exit('เลขคำขอไม่ถูกต้อง');
}

// CSRF Token สำหรับการยกเลิก
if (empty($_SESSION['csrf_cancel'])) {
    $_SESSION['csrf_cancel'] = bin2hex(random_bytes(32));
}

$error = '';
// ตรวจเจ้าของก่อนทำ action และอ่านข้อมูลประกอบ
$owner = $pdo->prepare('SELECT request_id FROM repair_request WHERE request_id = ? AND user_id = ?');
$owner->execute([$requestId, $userId]);
if (!$owner->fetchColumn()) {
    http_response_code(404);
    exit('ไม่พบคำขอแจ้งซ่อม');
}
if (empty($_SESSION['csrf_confirm'])) {
    $_SESSION['csrf_confirm'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($_POST['action'] ?? 'cancel', ['cancel', 'confirm'], true)) {
    http_response_code(400);
    exit('การทำรายการไม่ถูกต้อง');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_confirm'], $token)) {
        http_response_code(403);
        exit('CSRF Token ไม่ถูกต้อง');
    }
    try {
        confirmRepair($pdo, $requestId, $userId);
        header('Location: request-detail.php?id=' . $requestId . '&confirmed=1');
        exit;
    } catch (Throwable $ex) {
        http_response_code(409);
        $error = 'ไม่สามารถยืนยันงานได้ กรุณาตรวจสอบสถานะและหลักฐานอีกครั้ง';
    }
}


// ยกเลิกคำขอ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'cancel') === 'cancel') {

    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_cancel'], $token)) {

        http_response_code(403);
        exit('CSRF Token ไม่ถูกต้อง');
    }

    try {
        $pdo->beginTransaction();

        // เปลี่ยนสถานะได้เฉพาะคำขอของตัวเอง
        // และต้องยังเป็น PENDING
        $update = $pdo->prepare("
            UPDATE repair_request
            SET
                status = 'CANCELLED',
                cancelled_at = NOW()
            WHERE request_id = :request_id
              AND user_id = :user_id
              AND status = 'PENDING'
        ");

        $update->execute([
            ':request_id' => $requestId,
            ':user_id' => $userId
        ]);

        if ($update->rowCount() !== 1) {
            throw new RuntimeException(
                'ไม่สามารถยกเลิกคำขอนี้ได้'
            );
        }

        // เก็บประวัติการเปลี่ยนสถานะ
        $history = $pdo->prepare("
            INSERT INTO repair_history (
                request_id,
                changed_by_user_id,
                old_status,
                new_status,
                note
            )
            VALUES (
                :request_id,
                :changed_by,
                'PENDING',
                'CANCELLED',
                :note
            )
        ");

        $history->execute([
            ':request_id' => $requestId,
            ':changed_by' => $userId,
            ':note' => 'นักศึกษายกเลิกคำขอแจ้งซ่อม'
        ]);

        $pdo->commit();

        // Redirect ป้องกันการส่ง POST ซ้ำ
        header(
            'Location: request-detail.php?id=' .
            $requestId . '&cancelled=1'
        );
        exit;

    } catch (Throwable $ex) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = 'ไม่สามารถยกเลิกคำขอได้ '
               . 'กรุณาตรวจสอบสถานะอีกครั้ง';
    }
}

// ดึงข้อมูลคำขอเฉพาะของนักศึกษาคนนี้
$stmt = $pdo->prepare("
    SELECT
        r.*,
        c.category_name,
        rm.room_number,
        a.area_name
    FROM repair_request r
    JOIN repair_category c
        ON r.category_id = c.category_id
    LEFT JOIN room rm
        ON r.room_id = rm.room_id
    LEFT JOIN common_area a
        ON r.area_id = a.area_id
    WHERE r.request_id = :request_id
      AND r.user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':request_id' => $requestId,
    ':user_id' => $userId
]);

$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    http_response_code(404);
    exit('ไม่พบคำขอแจ้งซ่อม');
}

$historyStmt = $pdo->prepare('SELECT h.*, u.username AS actor_name FROM repair_history h
    JOIN `user` u ON u.user_id = h.changed_by_user_id
    WHERE h.request_id = ? ORDER BY h.changed_at ASC, h.history_id ASC');
$historyStmt->execute([$requestId]);
$histories = $historyStmt->fetchAll();
$assignmentStmt = $pdo->prepare('SELECT a.*, u.username AS technician_name FROM repair_assignment a
    JOIN `user` u ON u.user_id = a.technician_user_id
    WHERE a.request_id = ? AND a.is_active = 1 ORDER BY a.assigned_at DESC, a.assignment_id DESC');
$assignmentStmt->execute([$requestId]);
$assignments = $assignmentStmt->fetchAll();
// หากยังไม่ได้ติดตั้ง migration หน้ารายละเอียดและการยกเลิกยังใช้ได้
$completion = null;
$completionAvailable = true;
try {
    $completion = repairCompletion($pdo, $requestId);
} catch (PDOException $ex) {
    if (($ex->errorInfo[1] ?? null) !== 1146) {
        throw $ex;
    }
    $completionAvailable = false;
}
$completionUrl = $completion ? completionPhotoUrl($completion['completion_photo_path']) : null;
$canConfirm = canConfirmRepair($request, $completion);

$statusLabels = [
    'PENDING' => 'รอดำเนินการ',
    'IN_PROGRESS' => 'กำลังดำเนินการ',
    'COMPLETED' => 'เสร็จสิ้น',
    'CANCELLED' => 'ยกเลิกแล้ว'
];

$priorityLabels = [
    'LOW' => 'ต่ำ',
    'MEDIUM' => 'ปานกลาง',
    'HIGH' => 'สูง'
];

$status = $request['status'];

$location = $request['room_number'] !== null
    ? 'ห้อง ' . $request['room_number']
    : ($request['area_name'] ?? '-');

// อนุญาตให้แสดงภาพเฉพาะ path ในโฟลเดอร์ uploads/repairs
$imageUrl = null;
$imagePath = $request['image_path'] ?? '';

if (
    is_string($imagePath) &&
    preg_match(
        '~^uploads/repairs/[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$~i',
        $imagePath
    )
) {
    $imageUrl = '/fixit/' . $imagePath;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>รายละเอียดแจ้งซ่อม - FixIT</title>

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            color: #1f2937;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 28px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
        }

        .subtitle { color: #6b7280; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 25px 0;
        }

        .info-label {
            display: block;
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .description {
            background: #f8fafc;
            border-radius: 8px;
            padding: 18px;
            line-height: 1.7;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .image {
            max-width: 100%;
            max-height: 450px;
            border-radius: 10px;
            margin-top: 12px;
        }

        .badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .PENDING {
            background: #fef3c7;
            color: #92400e;
        }

        .IN_PROGRESS {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .COMPLETED {
            background: #dcfce7;
            color: #166534;
        }

        .CANCELLED {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .alert {
            padding: 14px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .cancel-section {
            border-top: 1px solid #e5e7eb;
            margin-top: 30px;
            padding-top: 25px;
        }

        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
        .timeline li { margin-bottom: 24px; padding-left: 8px; }
        .btn-confirm { background: #166534; color: white; padding: 12px 20px; border: 0; border-radius: 8px; cursor: pointer; }
    </style>
</head>

<body>
<div class="container">

    <a href="my-requests.php" class="back">
        &larr; กลับไปรายการแจ้งซ่อม
    </a>

    <div class="card">

        <h1>รายละเอียดคำขอ #<?= (int)$requestId ?></h1>

        <p class="subtitle">
            <?= e($request['title']) ?>
        </p>

        <?php if (isset($_GET['cancelled']) &&
                  $_GET['cancelled'] === '1' &&
                  $status === 'CANCELLED'): ?>

            <div class="alert success">
                คำขอนี้ถูกยกเลิกแล้ว
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="info-grid">

            <div>
                <span class="info-label">สถานะ</span>
                <span class="badge <?= e($status) ?>">
                    <?= e($statusLabels[$status] ?? $status) ?>
                </span>
            </div>

            <div>
                <span class="info-label">ความสำคัญ</span>
                <?= e(
                    $priorityLabels[$request['priority']]
                    ?? $request['priority']
                ) ?>
            </div>

            <div>
                <span class="info-label">หมวดหมู่</span>
                <?= e($request['category_name']) ?>
            </div>

            <div>
                <span class="info-label">สถานที่</span>
                <?= e($location) ?>
            </div>

            <div>
                <span class="info-label">วันที่แจ้ง</span>
                <?= e(date(
                    'd/m/Y H:i',
                    strtotime($request['created_at'])
                )) ?>
            </div>

            <?php if (!empty($request['cancelled_at'])): ?>
                <div>
                    <span class="info-label">วันที่ยกเลิก</span>
                    <?= e(date(
                        'd/m/Y H:i',
                        strtotime($request['cancelled_at'])
                    )) ?>
                </div>
            <?php endif; ?>

        </div>

        <h3>รายละเอียดปัญหา</h3>

        <div class="description"><?= e($request['description']) ?></div>

        <?php if ($imageUrl !== null): ?>

            <h3>รูปภาพที่แนบ</h3>

            <img
                src="<?= e($imageUrl) ?>"
                alt="ภาพประกอบการแจ้งซ่อม"
                class="image"
            >

        <?php endif; ?>

        <section class="cancel-section" aria-labelledby="progress-heading">
            <h2 id="progress-heading">ความคืบหน้าการซ่อม</h2>
            <p class="subtitle"><?= e($canConfirm ? 'รอนักศึกษายืนยัน' : ($statusLabels[$status] ?? $status)) ?></p>
            <h3>ช่างที่ได้รับมอบหมาย</h3>
            <?php if (!$assignments): ?><p class="subtitle">ยังไม่มีช่างที่ได้รับมอบหมาย</p><?php endif; ?>
            <?php foreach ($assignments as $assignment): ?>
                <p><?= e($assignment['technician_name']) ?> — <?= e($assignment['assigned_at']) ?></p>
                <?php if ($assignment['note']): ?><div class="description"><?= e($assignment['note']) ?></div><?php endif; ?>
            <?php endforeach; ?>
            <h3>ประวัติเหตุการณ์ (เก่าไปใหม่)</h3>
            <?php if (!$histories): ?><p class="subtitle">ยังไม่มีประวัติเหตุการณ์</p><?php endif; ?>
            <ol class="timeline">
                <?php foreach ($histories as $history): ?>
                    <li>
                        <time><?= e($history['changed_at']) ?></time> — <?= e($history['actor_name']) ?>
                        <p><?= e($history['old_status'] === null ? 'สร้างคำร้อง' : ($statusLabels[$history['old_status']] ?? $history['old_status'])) ?>
                            → <?= e($statusLabels[$history['new_status']] ?? $history['new_status']) ?></p>
                        <?php if ($history['note']): ?><div class="description"><?= e($history['note']) ?></div><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
        <section class="cancel-section" aria-labelledby="completion-heading">
            <h2 id="completion-heading">หลักฐานหลังซ่อม</h2>
            <?php if (!$completionAvailable): ?>
                <p class="subtitle">ระบบหลักฐานหลังซ่อมยังไม่พร้อมใช้งาน</p>
            <?php elseif (!$completion): ?>
                <p class="subtitle">ช่างยังไม่ได้ส่งหลักฐานหลังซ่อม จึงยังยืนยันงานไม่ได้</p>
            <?php else: ?>
                <p>ส่งโดย <?= e($completion['submitted_by_name']) ?> เมื่อ <?= e($completion['submitted_at']) ?></p>
                <?php if ($completion['note']): ?><div class="description"><?= e($completion['note']) ?></div><?php endif; ?>
                <?php if ($completionUrl): ?>
                    <a href="<?= e($completionUrl) ?>" target="_blank" rel="noopener"><img class="image" src="<?= e($completionUrl) ?>" alt="หลักฐานหลังซ่อมจากช่าง"></a>
                <?php else: ?><p class="subtitle">ไม่พบรูปหลักฐานที่ถูกต้อง จึงยังยืนยันงานไม่ได้</p><?php endif; ?>
                <?php if ($completion['confirmed_at']): ?>
                    <p class="alert success">ยืนยันงานซ่อมแล้ว เมื่อ <?= e($completion['confirmed_at']) ?></p>
                <?php elseif ($canConfirm): ?>
                    <p>รอนักศึกษายืนยัน กรุณาตรวจสอบรูปหลักฐานก่อนยืนยันงานซ่อม</p>
                    <form method="POST" onsubmit="return confirm('ยืนยันว่าได้รับการซ่อมเรียบร้อยแล้ว?');">
                        <input type="hidden" name="action" value="confirm">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_confirm']) ?>">
                        <button class="btn-confirm" type="submit">ยืนยันงานซ่อม</button>
                    </form>
                <?php else: ?><p class="subtitle">คำร้องนี้ยังไม่อยู่ในเงื่อนไขที่ยืนยันงานได้</p><?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- ยกเลิกได้เฉพาะ PENDING -->
        <?php if ($status === 'PENDING'): ?>

            <div class="cancel-section">

                <h3>ยกเลิกคำขอ</h3>

                <p class="subtitle">
                    สามารถยกเลิกได้ก่อนที่งานจะเริ่มดำเนินการ
                </p>

                <form
                    method="POST"
                    onsubmit="return confirm('ยืนยันการยกเลิกคำขอนี้?');"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['csrf_cancel']) ?>"
                    >

                    <button type="submit" class="btn-danger">
                        ยกเลิกคำขอแจ้งซ่อม
                    </button>

                </form>

            </div>

        <?php endif; ?>

    </div>

</div>
</body>
</html>

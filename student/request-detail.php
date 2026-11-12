<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

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

// ยกเลิกคำขอ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
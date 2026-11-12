<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// อนุญาตเฉพาะ STUDENT
$user = requireRole('STUDENT');
$userId = (int) $user['user_id'];

// รับค่าค้นหาและตัวกรอง
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'ALL';

$allowedStatuses = [
    'ALL', 'PENDING', 'IN_PROGRESS',
    'COMPLETED', 'CANCELLED'
];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'ALL';
}

// ค้นหาได้ทั้งเลขคำขอและหัวข้อ
// จำกัดความยาว input
$search = mb_substr($search, 0, 100);

// เตรียม SQL
$sql = "
    SELECT
        r.request_id,
        r.title,
        r.priority,
        r.status,
        r.created_at,
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
    WHERE r.user_id = :user_id
";

$params = [
    ':user_id' => $userId
];

if ($status !== 'ALL') {
    $sql .= " AND r.status = :status";
    $params[':status'] = $status;
}

if ($search !== '') {
    $sql .= "
        AND (
            CAST(r.request_id AS CHAR) LIKE :search_id
            OR r.title LIKE :search_title
        )
    ";

    $params[':search_id'] = "%{$search}%";
    $params[':search_title'] = "%{$search}%";
}

$sql .= " ORDER BY r.created_at DESC, r.request_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// นับจำนวนแต่ละสถานะของผู้ใช้
$countStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'PENDING') AS pending,
        SUM(status = 'IN_PROGRESS') AS in_progress,
        SUM(status = 'COMPLETED') AS completed,
        SUM(status = 'CANCELLED') AS cancelled
    FROM repair_request
    WHERE user_id = :user_id
");

$countStmt->execute([':user_id' => $userId]);
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>รายการแจ้งซ่อมของฉัน - FixIT</title>

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            color: #1f2937;
        }

        .container {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        h1 { margin-bottom: 6px; }

        .subtitle { color: #6b7280; }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn:hover { background: #1d4ed8; }

        .btn-secondary {
            background: #64748b;
        }

        .stats {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .stat-card strong {
            display: block;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .stat-card span { color: #6b7280; }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        input, select {
            padding: 11px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }

        input { flex: 1; min-width: 200px; }

        .table-wrapper { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 790px;
        }

        th, td {
            padding: 14px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8fafc;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
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

        .detail-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }

        .empty {
            padding: 45px 15px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 600px) {
            .header { align-items: flex-start; }
            .filters { flex-direction: column; }
            input { min-width: 0; }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <div>
            <h1>รายการแจ้งซ่อมของฉัน</h1>
            <p class="subtitle">
                ตรวจสอบและติดตามสถานะการแจ้งซ่อม
            </p>
        </div>

        <a class="btn" href="create-request.php">
            + แจ้งซ่อมใหม่
        </a>
    </div>

    <!-- สรุปจำนวน -->
    <div class="stats">

        <div class="stat-card">
            <strong><?= (int)$counts['total'] ?></strong>
            <span>ทั้งหมด</span>
        </div>

        <div class="stat-card">
            <strong><?= (int)$counts['pending'] ?></strong>
            <span>รอดำเนินการ</span>
        </div>

        <div class="stat-card">
            <strong><?= (int)$counts['in_progress'] ?></strong>
            <span>กำลังดำเนินการ</span>
        </div>

        <div class="stat-card">
            <strong><?= (int)$counts['completed'] ?></strong>
            <span>เสร็จสิ้น</span>
        </div>

        <div class="stat-card">
            <strong><?= (int)$counts['cancelled'] ?></strong>
            <span>ยกเลิกแล้ว</span>
        </div>

    </div>

    <div class="panel">

        <!-- ค้นหา / กรองสถานะ -->
        <form method="GET" class="filters">

            <input
                type="text"
                name="search"
                placeholder="ค้นหาเลขคำขอหรือหัวข้อ..."
                maxlength="100"
                value="<?= e($search) ?>"
            >

            <select name="status">
                <option value="ALL"
                    <?= $status === 'ALL' ? 'selected' : '' ?>>
                    ทุกสถานะ
                </option>

                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>"
                        <?= $status === $key ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn">
                ค้นหา
            </button>

            <a class="btn btn-secondary"
               href="my-requests.php">
                ล้างตัวกรอง
            </a>

        </form>

        <!-- ตารางรายการแจ้งซ่อม -->
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>เลขคำขอ</th>
                    <th>หัวข้อ</th>
                    <th>หมวดหมู่</th>
                    <th>สถานที่</th>
                    <th>ความสำคัญ</th>
                    <th>สถานะ</th>
                    <th>วันที่แจ้ง</th>
                    <th>จัดการ</th>
                </tr>
                </thead>

                <tbody>

                <?php if (empty($requests)): ?>

                    <tr>
                        <td colspan="8">
                            <div class="empty">
                                ไม่พบรายการแจ้งซ่อม
                            </div>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($requests as $request): ?>
                        <?php
                            $requestStatus = $request['status'];
                            $location = $request['room_number'] !== null
                                ? 'ห้อง ' . $request['room_number']
                                : ($request['area_name'] ?? '-');
                        ?>

                        <tr>
                            <td>
                                #<?= (int)$request['request_id'] ?>
                            </td>

                            <td><?= e($request['title']) ?></td>

                            <td>
                                <?= e($request['category_name']) ?>
                            </td>

                            <td><?= e($location) ?></td>

                            <td>
                                <?= e(
                                    $priorityLabels[$request['priority']]
                                    ?? $request['priority']
                                ) ?>
                            </td>

                            <td>
                                <span class="badge <?= e($requestStatus) ?>">
                                    <?= e(
                                        $statusLabels[$requestStatus]
                                        ?? $requestStatus
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?= e(date(
                                    'd/m/Y H:i',
                                    strtotime($request['created_at'])
                                )) ?>
                            </td>

                            <td>
                                <a class="detail-link"
                                   href="request-detail.php?id=<?= (int)$request['request_id'] ?>">
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </div>

</div>
</body>
</html>

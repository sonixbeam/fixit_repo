<?php
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole('STUDENT');

// ดึงหมวดหมู่การแจ้งซ่อมจากฐานข้อมูล
$stmt = $pdo->query(
    "SELECT category_name FROM repair_category"
);

$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - FixIT</title>
    <link rel="stylesheet"
          href="../assets/css/style.css">
</head>
<body>

<header class="navbar">
    <h2>FixIT | Student</h2>
    <div>
        <?= e($user['username']) ?>
        &nbsp;
        <a class="logout" href="../logout.php">
            ออกจากระบบ
        </a>
    </div>
</header>

<main class="container">
    <h1>หน้าหลักนักศึกษา</h1>
    <p class="subtitle">
        ยินดีต้อนรับ <?= e($user['username']) ?>
    </p>

        <a class="card" href="create-request.php">
            <h3>แจ้งซ่อม</h3>
            <p>สร้างรายการแจ้งซ่อมใหม่</p>
            <strong>เริ่มแจ้งซ่อม</strong>
        </a>

        <div class="card">
            <h3>รายการแจ้งซ่อมของฉัน</h3>
            <p>ดูสถานะและยกเลิกรายการ</p>
            <small>เตรียมพัฒนา</small>
        </div>
    </div>

    <section class="panel">
        <h3>หมวดหมู่การแจ้งซ่อม</h3>

        <ul>
            <?php foreach ($categories as $category): ?>
                <li>
                    <?= e($category['category_name']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</main>

</body>
</html>


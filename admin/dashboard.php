<?php
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole('ADMIN');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FixIT</title>
    <link rel="stylesheet"
          href="../assets/css/style.css">
</head>
<body>

<header class="navbar">
    <h2>FixIT | Admin</h2>
    <div>
        <?= e($user['username']) ?>
        &nbsp;
        <a class="logout" href="../logout.php">
            ออกจากระบบ
        </a>
    </div>
</header>

<main class="container">
    <h1>หน้าหลักผู้ดูแลระบบ</h1>
    <p class="subtitle">
        ยินดีต้อนรับ <?= e($user['username']) ?>
    </p>

    <div class="cards">
        <div class="card">
            <h3>จัดการรายการแจ้งซ่อม</h3>
            <p>ดูรายการและมอบหมายช่างซ่อม</p>
            <small>เตรียมพัฒนา</small>
        </div>

        <div class="card">
            <h3>จัดการห้องพัก</h3>
            <p>ดูและแก้ไขข้อมูลห้องพัก</p>
            <small>เตรียมพัฒนา</small>
        </div>

        <div class="card">
            <h3>รายงานสรุป</h3>
            <p>ดูสถิติการแจ้งซ่อม</p>
            <small>เตรียมพัฒนา</small>
        </div>
    </div>
</main>

</body>
</html>

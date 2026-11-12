<?php
// ป้องกันการเรียก partial โดยตรง; หน้าแม่ตรวจ session ผ่าน requireRole แล้ว
if (!isset($user, $pageTitle) || ($user['role'] ?? '') !== 'ADMIN') {
    http_response_code(403);
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - FixIT</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">
<header class="navbar">
    <h2>FixIT | Admin</h2>
    <nav aria-label="เมนูผู้ดูแล">
        <a href="dashboard.php">ภาพรวม</a>
        <a href="requests.php">คำร้องทั้งหมด</a>
        <a href="rooms.php">ห้องพัก</a>
        <a href="reports.php">รายงาน</a>
        <a class="logout" href="../logout.php">ออกจากระบบ</a>
    </nav>
</header>
<main class="container">
    <h1><?= e($pageTitle) ?></h1>

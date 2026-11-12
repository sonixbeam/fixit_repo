<?php
if (!isset($user,$pageTitle) || ($user['role'] ?? '')!=='TECHNICIAN') { http_response_code(403); exit; }
?>
<!DOCTYPE html><html lang="th"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> - FixIT</title><link rel="stylesheet" href="../assets/css/style.css">
</head><body class="admin-page technician-page">
<header class="navbar"><h2>FixIT | Technician</h2><nav aria-label="เมนูช่าง">
<a href="dashboard.php">ภาพรวม</a><a href="my-jobs.php">งานของฉัน</a><a class="logout" href="../logout.php">ออกจากระบบ</a>
</nav></header><main class="container"><h1><?= e($pageTitle) ?></h1>

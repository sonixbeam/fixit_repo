<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/repair-technician.php';
$user=requireRole('TECHNICIAN');$pageTitle='ภาพรวมงานของฉัน';$statusLabels=adminStatusLabels();
$stmt=$pdo->prepare('SELECT r.status,COUNT(*) FROM repair_request r WHERE EXISTS(SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.technician_user_id=? AND a.is_active=1) GROUP BY r.status');
$stmt->execute([(int)$user['user_id']]);$counts=$stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stmt=$pdo->prepare("SELECT COUNT(*) FROM repair_request r JOIN repair_completion c ON c.request_id=r.request_id WHERE r.status='IN_PROGRESS' AND c.confirmed_at IS NULL AND EXISTS(SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.technician_user_id=? AND a.is_active=1)");
$stmt->execute([(int)$user['user_id']]);$waiting=(int)$stmt->fetchColumn();
require __DIR__.'/../includes/technician-header.php';
?>
<p class="subtitle">สวัสดี <?= e($user['username']) ?> — เฉพาะงานที่คุณได้รับมอบหมายในปัจจุบัน</p>
<div class="cards"><?php foreach($statusLabels as $key=>$label): ?><a class="card" href="my-jobs.php?status=<?= e($key) ?>"><h2><?= e($label) ?></h2><strong><?= (int)($counts[$key] ?? 0) ?></strong> งาน</a><?php endforeach; ?>
<a class="card" href="my-jobs.php?status=WAITING"><h2>รอนักศึกษายืนยัน</h2><strong><?= $waiting ?></strong> งาน</a></div>
<section class="panel"><p>เริ่มซ่อมและบันทึกความคืบหน้าจากรายละเอียดงาน เมื่อซ่อมเสร็จต้องส่งรูปหลักฐานและรอให้นักศึกษายืนยัน</p><a href="my-jobs.php">ดูงานทั้งหมดของฉัน</a></section>
</main></body></html>

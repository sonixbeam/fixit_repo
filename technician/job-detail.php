<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/repair-technician.php';
$user=requireRole('TECHNICIAN'); $technicianId=(int)$user['user_id'];
$requestId=adminPositiveId($_GET['id'] ?? null);
if(!$requestId){http_response_code(400);exit('เลขงานไม่ถูกต้อง');}
if(!technicianOwnsRequest($pdo,$requestId,$technicianId)){http_response_code(404);exit('ไม่พบงานที่ได้รับมอบหมาย');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    $csrf=$_POST['csrf_token'] ?? null; $actionToken=$_POST['action_token'] ?? null;
    if(!is_string($csrf) || !hash_equals(technicianCsrfToken(),$csrf)) {http_response_code(403);exit('CSRF Token ไม่ถูกต้อง');}
    if(!is_string($actionToken) || !isset($_SESSION['technician_actions'][$requestId])
        || !hash_equals($_SESSION['technician_actions'][$requestId],$actionToken)) {
        http_response_code(409);$error='แบบฟอร์มนี้ถูกใช้แล้วหรือหมดอายุ กรุณาลองใหม่';
    } else {
        try {
            technicianChangeRequest($pdo,$requestId,$technicianId,adminText($_POST,'action'),$_POST['note'] ?? '',$_FILES['completion_photo'] ?? null);
            unset($_SESSION['technician_actions'][$requestId]);
            $_SESSION['technician_notice']='บันทึกเรียบร้อยแล้ว';
            header('Location: job-detail.php?id='.$requestId);exit;
        } catch(DomainException $ex) {http_response_code(409);$error=$ex->getMessage();}
        catch(Throwable $ex) {http_response_code(500);$error='บันทึกไม่สำเร็จ กรุณาลองใหม่';}
    }
}
// ตรวจ ownership ซ้ำในการอ่าน หากมีการเปลี่ยน assignment ระหว่างคำขอ
$stmt=$pdo->prepare('SELECT r.*,c.category_name,rm.room_number,ca.area_name FROM repair_request r JOIN repair_category c ON c.category_id=r.category_id
 LEFT JOIN room rm ON rm.room_id=r.room_id LEFT JOIN common_area ca ON ca.area_id=r.area_id
 WHERE r.request_id=? AND EXISTS(SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.technician_user_id=? AND a.is_active=1)');
$stmt->execute([$requestId,$technicianId]);$request=$stmt->fetch();
if(!$request){http_response_code(404);exit('ไม่พบงานที่ได้รับมอบหมาย');}
$stmt=$pdo->prepare('SELECT a.*,u.username AS assigned_by FROM repair_assignment a JOIN `user` u ON u.user_id=a.assigned_by_user_id WHERE a.request_id=? AND a.technician_user_id=? AND a.is_active=1 ORDER BY a.assignment_id DESC');
$stmt->execute([$requestId,$technicianId]);$assignments=$stmt->fetchAll();
$stmt=$pdo->prepare('SELECT h.*,u.username FROM repair_history h JOIN `user` u ON u.user_id=h.changed_by_user_id WHERE h.request_id=? ORDER BY h.changed_at,h.history_id');
$stmt->execute([$requestId]);$history=$stmt->fetchAll();
$completion=repairCompletion($pdo,$requestId);$completionUrl=$completion?completionPhotoUrl($completion['completion_photo_path']):null;
$editable=!$completion && in_array($request['status'],['PENDING','IN_PROGRESS'],true);
if(empty($_SESSION['technician_actions'][$requestId])) {$_SESSION['technician_actions'][$requestId]=bin2hex(random_bytes(32));}
$actionToken=$_SESSION['technician_actions'][$requestId];
$notice=$_SESSION['technician_notice'] ?? '';unset($_SESSION['technician_notice']);
$statusLabels=adminStatusLabels();$priorityLabels=adminPriorityLabels();
$imageUrl=null;
if(preg_match('~^uploads/repairs/[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$~i',$request['image_path'] ?? '')){$imageUrl='/fixit/'.$request['image_path'];}
$pageTitle='รายละเอียดงาน #'.$requestId;require __DIR__.'/../includes/technician-header.php';
?>
<a href="my-jobs.php">← กลับงานของฉัน</a>
<?php if($notice): ?><p class="admin-alert success" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if($error): ?><p class="admin-alert error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel"><h2><?= e($request['title']) ?></h2><dl class="admin-info">
<div><dt>สถานที่</dt><dd><?= e($request['room_number']!==null?'ห้อง '.$request['room_number']:$request['area_name']) ?></dd></div>
<div><dt>หมวดหมู่</dt><dd><?= e($request['category_name']) ?></dd></div>
<div><dt>สถานะ</dt><dd><?= e($request['status']==='IN_PROGRESS' && $completion && !$completion['confirmed_at']?'รอนักศึกษายืนยัน':$statusLabels[$request['status']]) ?></dd></div>
<div><dt>ความสำคัญ (กำหนดโดยผู้ดูแล)</dt><dd><?= e($priorityLabels[$request['priority']]) ?></dd></div>
<div><dt>วันที่แจ้ง</dt><dd><?= e($request['created_at']) ?></dd></div>
</dl><h3>รายละเอียดปัญหา</h3><div class="admin-description"><?= e($request['description']) ?></div>
<?php if($imageUrl): ?><h3>รูปปัญหาจากนักศึกษา</h3><a href="<?= e($imageUrl) ?>" target="_blank" rel="noopener"><img class="admin-image" src="<?= e($imageUrl) ?>" alt="รูปปัญหาที่แจ้ง"></a><?php endif; ?>
<h3>ข้อมูลการมอบหมาย</h3><?php foreach($assignments as $a): ?><p><?= e($a['assigned_at']) ?> โดย <?= e($a['assigned_by']) ?></p><?php if($a['note']): ?><div class="admin-description"><?= e($a['note']) ?></div><?php endif; ?><?php endforeach; ?>
</section>
<?php if($editable): ?><section class="panel"><h2>ดำเนินงานซ่อม</h2>
<?php if($request['status']==='PENDING'): ?>
<form method="POST"><input type="hidden" name="csrf_token" value="<?= e(technicianCsrfToken()) ?>"><input type="hidden" name="action_token" value="<?= e($actionToken) ?>"><input type="hidden" name="action" value="start"><button type="submit">เริ่มงานซ่อม</button></form>
<?php else: ?>
<form method="POST" class="admin-form"><input type="hidden" name="csrf_token" value="<?= e(technicianCsrfToken()) ?>"><input type="hidden" name="action_token" value="<?= e($actionToken) ?>"><input type="hidden" name="action" value="progress">
<label>บันทึกความคืบหน้า<textarea name="note" maxlength="2000" rows="4" required></textarea></label><button type="submit">บันทึกความคืบหน้า</button></form>
<h3>ส่งงานให้นักศึกษายืนยัน</h3><form method="POST" enctype="multipart/form-data" class="admin-form">
<input type="hidden" name="csrf_token" value="<?= e(technicianCsrfToken()) ?>"><input type="hidden" name="action_token" value="<?= e($actionToken) ?>"><input type="hidden" name="action" value="submit">
<label>รูปหลักฐานหลังซ่อม (บังคับ)<input type="file" name="completion_photo" accept="image/jpeg,image/png,image/webp" required></label>
<p class="subtitle">JPG, PNG หรือ WEBP ไม่เกิน 5 MB หลังส่งงานต้องรอให้นักศึกษาเจ้าของคำร้องยืนยัน</p>
<label>หมายเหตุหลังซ่อม (ไม่บังคับ)<textarea name="note" maxlength="2000" rows="3"></textarea></label><button type="submit">ส่งหลักฐานหลังซ่อม</button></form>
<?php endif; ?></section><?php endif; ?>
<section class="panel"><h2>หลักฐานหลังซ่อม</h2>
<?php if(!$completion): ?><p class="subtitle">ยังไม่ได้ส่งหลักฐานหลังซ่อม</p><?php else: ?>
<p>ส่งโดย <?= e($completion['submitted_by_name']) ?> เมื่อ <?= e($completion['submitted_at']) ?></p>
<?php if($completionUrl): ?><a href="<?= e($completionUrl) ?>" target="_blank" rel="noopener"><img class="admin-image" src="<?= e($completionUrl) ?>" alt="หลักฐานหลังซ่อม"></a><?php else: ?><p class="subtitle">ไม่พบไฟล์รูปหลักฐานที่ถูกต้อง</p><?php endif; ?>
<?php if($completion['note']): ?><div class="admin-description"><?= e($completion['note']) ?></div><?php endif; ?>
<p><?= $completion['confirmed_at']?'นักศึกษายืนยันแล้วเมื่อ '.e($completion['confirmed_at']):'รอนักศึกษายืนยัน (ข้อมูลอ่านอย่างเดียว)' ?></p>
<?php endif; ?></section>
<section class="panel"><h2>Timeline</h2><?php if(!$history): ?><p class="subtitle">ยังไม่มีประวัติ</p><?php endif; ?><ol class="admin-timeline">
<?php foreach($history as $h): ?><li><time><?= e($h['changed_at']) ?></time> — <?= e($h['username']) ?><p><?= e($h['old_status']===null?'สร้างคำร้อง':$statusLabels[$h['old_status']]) ?> → <?= e($statusLabels[$h['new_status']]) ?></p><?php if($h['note']): ?><div class="admin-description"><?= e($h['note']) ?></div><?php endif; ?></li><?php endforeach; ?>
</ol></section></main></body></html>

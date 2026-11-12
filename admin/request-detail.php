<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repair-admin.php';
$user = requireRole('ADMIN');
$requestId = adminPositiveId($_GET['id'] ?? null);
if (!$requestId) { http_response_code(400); exit('เลขคำร้องไม่ถูกต้อง'); }
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    adminVerifyCsrf();
    try {
        $changed = adminChangeRequest($pdo,$requestId,(int)$user['user_id'],adminText($_POST,'action'),$_POST);
        $_SESSION['admin_notice'] = $changed?'บันทึกคำร้องเรียบร้อยแล้ว':'ข้อมูลตรงกับค่าปัจจุบัน ไม่มีการบันทึกซ้ำ';
        header('Location: request-detail.php?id='.$requestId);
        exit;
    } catch (DomainException $ex) {
        http_response_code(409); $error = $ex->getMessage();
    } catch (Throwable $ex) {
        http_response_code(500); $error = 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่';
    }
}
$stmt = $pdo->prepare('SELECT r.*,u.username,u.first_name,u.last_name,c.category_name,rm.room_number,ca.area_name
    FROM repair_request r JOIN `user` u ON u.user_id=r.user_id JOIN repair_category c ON c.category_id=r.category_id
    LEFT JOIN room rm ON rm.room_id=r.room_id LEFT JOIN common_area ca ON ca.area_id=r.area_id WHERE r.request_id=?');
$stmt->execute([$requestId]); $request = $stmt->fetch();
if (!$request) { http_response_code(404); exit('ไม่พบคำร้อง'); }
$stmt = $pdo->prepare('SELECT h.*,u.username FROM repair_history h JOIN `user` u ON u.user_id=h.changed_by_user_id
    WHERE h.request_id=? ORDER BY h.changed_at,h.history_id');
$stmt->execute([$requestId]); $history = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT a.*,t.username AS technician_name,u.username AS admin_name FROM repair_assignment a
    JOIN `user` t ON t.user_id=a.technician_user_id JOIN `user` u ON u.user_id=a.assigned_by_user_id
    WHERE a.request_id=? ORDER BY a.assigned_at DESC,a.assignment_id DESC');
$stmt->execute([$requestId]); $assignments = $stmt->fetchAll();
$completion = repairCompletion($pdo,$requestId);
$completionUrl = $completion ? completionPhotoUrl($completion['completion_photo_path']) : null;
$technicians = $pdo->query("SELECT user_id,username FROM `user` WHERE role='TECHNICIAN' AND is_active=1 ORDER BY username")->fetchAll();
$statusLabels = adminStatusLabels(); $priorityLabels = adminPriorityLabels();
$editable = in_array($request['status'],['PENDING','IN_PROGRESS'],true) && !$completion;
$imageUrl = null;
if (preg_match('~^uploads/repairs/[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$~i',$request['image_path'] ?? '')) {
    $imageUrl = '/fixit/'.$request['image_path'];
}
$notice = $_SESSION['admin_notice'] ?? ''; unset($_SESSION['admin_notice']);
$pageTitle = 'รายละเอียดคำร้อง #'.$requestId;
require __DIR__ . '/../includes/admin-header.php';
?>
<a href="requests.php">← กลับคำร้องทั้งหมด</a>
<?php if ($notice): ?><p class="admin-alert success" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="admin-alert error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel">
    <h2><?= e($request['title']) ?></h2>
    <dl class="admin-info">
        <div><dt>ผู้แจ้ง</dt><dd><?= e($request['first_name'].' '.$request['last_name'].' ('.$request['username'].')') ?></dd></div>
        <div><dt>สถานที่</dt><dd><?= e($request['room_number']!==null?'ห้อง '.$request['room_number']:$request['area_name']) ?></dd></div>
        <div><dt>หมวดหมู่</dt><dd><?= e($request['category_name']) ?></dd></div>
        <div><dt>สถานะ</dt><dd><?= e($request['status']==='IN_PROGRESS' && $completion && !$completion['confirmed_at']?'รอนักศึกษายืนยัน':$statusLabels[$request['status']]) ?></dd></div>
        <div><dt>ความสำคัญ</dt><dd><?= e($priorityLabels[$request['priority']]) ?></dd></div>
        <div><dt>วันที่แจ้ง</dt><dd><?= e($request['created_at']) ?></dd></div>
        <?php if ($request['completed_at']): ?><div><dt>วันที่ปิดงาน</dt><dd><?= e($request['completed_at']) ?></dd></div><?php endif; ?>
        <?php if ($request['cancelled_at']): ?><div><dt>วันที่ยกเลิก</dt><dd><?= e($request['cancelled_at']) ?></dd></div><?php endif; ?>
    </dl>
    <h3>รายละเอียดปัญหา</h3><div class="admin-description"><?= e($request['description']) ?></div>
    <?php if ($imageUrl): ?><h3>รูปปัญหาจากนักศึกษา</h3><a href="<?= e($imageUrl) ?>" target="_blank" rel="noopener"><img class="admin-image" src="<?= e($imageUrl) ?>" alt="รูปปัญหาที่แจ้ง"></a><?php endif; ?>
</section>
<section class="panel">
    <h2>ความสำคัญและการมอบหมาย</h2>
    <?php if ($editable): ?>
    <form method="POST" class="admin-filters">
        <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>"><input type="hidden" name="action" value="priority">
        <label>ความสำคัญ<select name="priority" required><?php foreach ($priorityLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $request['priority']===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <button type="submit">บันทึกความสำคัญ</button>
    </form>
    <?php else: ?><p class="subtitle">คำร้องนี้เป็นข้อมูลอ่านอย่างเดียว เนื่องจากปิดงาน ยกเลิก หรือส่งหลักฐานแล้ว</p><?php endif; ?>
    <?php if ($editable && $request['status']==='PENDING'): ?>
        <?php if ($technicians): ?>
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>"><input type="hidden" name="action" value="assign">
            <label>เลือกช่าง<select name="technician_user_id" required><option value="">เลือกช่างที่เปิดใช้งาน</option><?php foreach ($technicians as $t): ?><option value="<?= (int)$t['user_id'] ?>"><?= e($t['username']) ?></option><?php endforeach; ?></select></label>
            <label>หมายเหตุการมอบหมาย<textarea name="note" rows="3" maxlength="2000"></textarea></label>
            <p class="subtitle">เปลี่ยนช่างได้ก่อนเริ่มงานเท่านั้น การมอบหมายยังคงสถานะรอดำเนินการ</p>
            <button type="submit">มอบหมาย / เปลี่ยนช่าง</button>
        </form>
        <?php else: ?><p class="subtitle">ยังไม่มีช่างที่เปิดใช้งาน กรุณาตรวจบัญชีผ่านผู้ดูแลฐานข้อมูล</p><?php endif; ?>
    <?php endif; ?>
    <h3>ประวัติการมอบหมาย</h3>
    <?php if (!$assignments): ?><p class="subtitle">ยังไม่มอบหมายช่าง</p><?php endif; ?>
    <?php foreach ($assignments as $a): ?>
        <div class="admin-event"><strong><?= e($a['technician_name']) ?> — <?= $a['is_active']?'ช่างปัจจุบัน':'การมอบหมายก่อนหน้า' ?></strong>
            <p><?= e($a['assigned_at']) ?> โดย <?= e($a['admin_name']) ?></p>
            <?php if ($a['note']): ?><div class="admin-description"><?= e($a['note']) ?></div><?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<section class="panel">
    <h2>หลักฐานหลังซ่อม</h2>
    <?php if (!$completion): ?><p class="subtitle">ช่างยังไม่ส่งหลักฐานหลังซ่อม</p><?php else: ?>
        <p>ส่งโดย <?= e($completion['submitted_by_name']) ?> เมื่อ <?= e($completion['submitted_at']) ?></p>
        <?php if ($completionUrl): ?><a href="<?= e($completionUrl) ?>" target="_blank" rel="noopener"><img class="admin-image" src="<?= e($completionUrl) ?>" alt="หลักฐานหลังซ่อมจากช่าง"></a><?php else: ?><p class="subtitle">ไม่พบไฟล์หลักฐานที่ถูกต้อง</p><?php endif; ?>
        <?php if ($completion['note']): ?><div class="admin-description"><?= e($completion['note']) ?></div><?php endif; ?>
        <p><?= $completion['confirmed_at']?'นักศึกษายืนยันแล้วเมื่อ '.e($completion['confirmed_at']):'รอนักศึกษาเจ้าของคำร้องยืนยัน' ?></p>
    <?php endif; ?>
</section>
<section class="panel">
    <h2>Timeline</h2>
    <?php if (!$history): ?><p class="subtitle">ยังไม่มีประวัติ</p><?php endif; ?>
    <ol class="admin-timeline"><?php foreach ($history as $h): ?><li>
        <time><?= e($h['changed_at']) ?></time> — <?= e($h['username']) ?>
        <p><?= e($h['old_status']===null?'สร้างคำร้อง':$statusLabels[$h['old_status']]) ?> → <?= e($statusLabels[$h['new_status']]) ?></p>
        <?php if ($h['note']): ?><div class="admin-description"><?= e($h['note']) ?></div><?php endif; ?>
    </li><?php endforeach; ?></ol>
</section>
</main></body></html>

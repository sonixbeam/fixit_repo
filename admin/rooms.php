<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repair-admin.php';
$user = requireRole('ADMIN');
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    adminVerifyCsrf();
    try {
        $roomId = adminPositiveId($_POST['room_id'] ?? null);
        if (!$roomId) { throw new DomainException('เลขห้องพักไม่ถูกต้อง'); }
        adminUpdateRoom($pdo,$roomId,(int)$user['user_id'],$_POST);
        $_SESSION['admin_room_notice'] = 'บันทึกห้องพักเรียบร้อยแล้ว';
        header('Location: rooms.php?edit='.$roomId); exit;
    } catch (DomainException $ex) {
        http_response_code(409); $error = $ex->getMessage();
    } catch (Throwable $ex) {
        http_response_code(500); $error = 'บันทึกห้องพักไม่สำเร็จ กรุณาลองใหม่';
    }
}
$roomLabels = ['AVAILABLE'=>'ว่าง','OCCUPIED'=>'มีผู้พัก','MAINTENANCE'=>'ซ่อมบำรุง'];
$editId = adminPositiveId($_GET['edit'] ?? null); $edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM room WHERE room_id=?'); $stmt->execute([$editId]); $edit = $stmt->fetch();
    if (!$edit) { http_response_code(404); exit('ไม่พบห้องพัก'); }
}
$search = adminText($_GET,'search',20);
$stmt = $pdo->prepare("SELECT r.*, (SELECT COUNT(*) FROM `user` u WHERE u.room_id=r.room_id AND u.role='STUDENT' AND u.is_active=1) AS residents
    FROM room r WHERE r.room_number LIKE ? ORDER BY r.floor,r.room_number,r.room_id");
$stmt->execute(['%'.$search.'%']); $rooms = $stmt->fetchAll();
$notice = $_SESSION['admin_room_notice'] ?? ''; unset($_SESSION['admin_room_notice']);
$pageTitle = 'จัดการห้องพัก'; require __DIR__ . '/../includes/admin-header.php';
?>
<?php if ($notice): ?><p class="admin-alert success" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="admin-alert error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($edit): ?>
<section class="panel">
    <h2>แก้ไขห้อง <?= e($edit['room_number']) ?></h2>
    <form method="POST" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>"><input type="hidden" name="room_id" value="<?= (int)$edit['room_id'] ?>">
        <label>เลขห้อง<input name="room_number" required maxlength="20" value="<?= e($edit['room_number']) ?>"></label>
        <label>ชั้น<input name="floor" type="number" min="0" max="255" required value="<?= (int)$edit['floor'] ?>"></label>
        <label>สถานะ<select name="room_status"><?php foreach ($roomLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $key===$edit['room_status']?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <p class="subtitle">ห้องที่มีผู้พักอยู่ไม่สามารถเปลี่ยนเป็นห้องว่างได้</p>
        <button type="submit">บันทึกห้องพัก</button><a href="rooms.php">กลับรายการ</a>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <form method="GET" class="admin-filters"><label>ค้นหาเลขห้อง<input name="search" maxlength="20" value="<?= e($search) ?>"></label><button type="submit">ค้นหา</button><a href="rooms.php">ล้าง</a></form>
    <?php if (!$rooms): ?><p class="subtitle">ไม่พบห้องพัก</p><?php else: ?>
    <div class="admin-table-wrap"><table class="admin-table admin-room-table"><thead><tr><th>เลขห้อง</th><th>ชั้น</th><th>สถานะ</th><th>จำนวนผู้พักที่เปิดใช้งาน</th><th>จัดการ</th></tr></thead>
    <tbody><?php foreach ($rooms as $r): ?><tr><td><?= e($r['room_number']) ?></td><td><?= (int)$r['floor'] ?></td><td><?= e($roomLabels[$r['room_status']]) ?></td><td><?= (int)$r['residents'] ?></td><td><a href="?edit=<?= (int)$r['room_id'] ?>">แก้ไข</a></td></tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
</section>
</main></body></html>

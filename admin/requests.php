<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repair-admin.php';
$user = requireRole('ADMIN');
$pageTitle = 'คำร้องแจ้งซ่อมทั้งหมด';
$statusLabels = adminStatusLabels();
$filterStatusLabels = $statusLabels + ['OPEN'=>'งานที่ยังไม่ปิด', 'WAITING'=>'รอนักศึกษายืนยัน'];
$priorityLabels = adminPriorityLabels();
$search = adminText($_GET,'search');
$status = adminText($_GET,'status');
$priority = adminText($_GET,'priority');
$categoryId = adminPositiveId($_GET['category_id'] ?? null);
$technician = adminText($_GET,'technician');
$params = [];
$where = [];
if (isset($statusLabels[$status])) { $where[]='r.status=?'; $params[]=$status; }
if ($status==='OPEN') { $where[]="r.status IN ('PENDING','IN_PROGRESS')"; }
if ($status==='WAITING') {
    $where[]="r.status='IN_PROGRESS' AND EXISTS (SELECT 1 FROM repair_completion rc WHERE rc.request_id=r.request_id AND rc.confirmed_at IS NULL)";
}
if (isset($priorityLabels[$priority])) { $where[]='r.priority=?'; $params[]=$priority; }
if ($categoryId) { $where[]='r.category_id=?'; $params[]=$categoryId; }
if ($search!=='') {
    $where[]='(CAST(r.request_id AS CHAR) LIKE ? OR r.title LIKE ?)';
    $params[]='%'.$search.'%'; $params[]='%'.$search.'%';
}
if ($technician==='unassigned') {
    $where[]='NOT EXISTS (SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.is_active=1)';
} elseif ($techId=adminPositiveId($technician)) {
    $where[]='EXISTS (SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.is_active=1 AND a.technician_user_id=?)';
    $params[]=$techId;
}
$page = adminPositiveId($_GET['page'] ?? null) ?? 1;
$from = ' FROM repair_request r JOIN `user` u ON u.user_id=r.user_id
    JOIN repair_category c ON c.category_id=r.category_id
    LEFT JOIN room rm ON rm.room_id=r.room_id LEFT JOIN common_area ca ON ca.area_id=r.area_id';
$condition = $where ? ' WHERE '.implode(' AND ',$where) : '';
$stmt = $pdo->prepare('SELECT COUNT(*)'.$from.$condition);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1,(int)ceil($total/25));
$page = min($page,$pages);
$stmt = $pdo->prepare('SELECT r.*,u.username,c.category_name,rm.room_number,ca.area_name,
    (SELECT GROUP_CONCAT(t.username ORDER BY a.assignment_id SEPARATOR ", ") FROM repair_assignment a
        JOIN `user` t ON t.user_id=a.technician_user_id WHERE a.request_id=r.request_id AND a.is_active=1) AS technicians,
    EXISTS(SELECT 1 FROM repair_completion rc WHERE rc.request_id=r.request_id AND rc.confirmed_at IS NULL) AS awaiting_confirmation'
    .$from.$condition.' ORDER BY r.created_at DESC,r.request_id DESC LIMIT 25 OFFSET '.(($page-1)*25));
$stmt->execute($params);
$requests = $stmt->fetchAll();
$categories = $pdo->query('SELECT category_id,category_name FROM repair_category ORDER BY category_name')->fetchAll();
$technicians = $pdo->query("SELECT user_id,username,is_active FROM `user` WHERE role='TECHNICIAN' ORDER BY username")->fetchAll();
require __DIR__ . '/../includes/admin-header.php';
?>
<section class="panel">
    <form method="GET" class="admin-filters">
        <label>ค้นหาเลขคำร้อง/หัวข้อ<input name="search" value="<?= e($search) ?>" maxlength="100"></label>
        <label>สถานะ<select name="status"><option value="">ทุกสถานะ</option>
            <?php foreach ($filterStatusLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $key===$status?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select></label>
        <label>ความสำคัญ<select name="priority"><option value="">ทุกระดับ</option>
            <?php foreach ($priorityLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $key===$priority?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select></label>
        <label>หมวดหมู่<select name="category_id"><option value="">ทุกหมวดหมู่</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= (int)$c['category_id']===$categoryId?'selected':'' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?>
        </select></label>
        <label>ช่าง<select name="technician"><option value="">ทุกคน</option><option value="unassigned" <?= $technician==='unassigned'?'selected':'' ?>>ยังไม่มอบหมาย</option>
            <?php foreach ($technicians as $t): ?><option value="<?= (int)$t['user_id'] ?>" <?= (string)$t['user_id']===$technician?'selected':'' ?>><?= e($t['username']) ?><?= $t['is_active']?'':' (ปิดใช้งาน)' ?></option><?php endforeach; ?>
        </select></label>
        <button type="submit">ค้นหา/กรอง</button><a href="requests.php">ล้างตัวกรอง</a>
    </form>
    <p>พบ <?= $total ?> คำร้อง</p>
    <?php if (!$requests): ?><p class="subtitle">ไม่พบคำร้องตามเงื่อนไข</p><?php else: ?>
    <div class="admin-table-wrap"><table class="admin-table admin-request-table">
        <thead><tr><th>เลขคำร้อง/หัวข้อ</th><th>ผู้แจ้ง/สถานที่</th><th>หมวดหมู่</th><th>สถานะ/ความสำคัญ</th><th>ช่าง</th><th>วันที่แจ้ง</th></tr></thead>
        <tbody><?php foreach ($requests as $r): ?><tr>
            <td><a href="request-detail.php?id=<?= (int)$r['request_id'] ?>">#<?= (int)$r['request_id'] ?> <?= e($r['title']) ?></a></td>
            <td><?= e($r['username']) ?><br><?= e($r['room_number']!==null?'ห้อง '.$r['room_number']:$r['area_name']) ?></td>
            <td><?= e($r['category_name']) ?></td>
            <td><?= e($r['status']==='IN_PROGRESS' && $r['awaiting_confirmation']?'รอนักศึกษายืนยัน':$statusLabels[$r['status']]) ?><br><?= e($priorityLabels[$r['priority']]) ?></td>
            <td><?= e($r['technicians'] ?? 'ยังไม่มอบหมาย') ?></td><td><?= e($r['created_at']) ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
    <nav class="admin-pagination" aria-label="แบ่งหน้า">
        <?php if ($page>1): ?><a href="?<?= e(http_build_query(['search'=>$search,'status'=>$status,'priority'=>$priority,'category_id'=>$categoryId,'technician'=>$technician,'page'=>$page-1])) ?>">หน้าก่อน</a><?php endif; ?>
        <span>หน้า <?= $page ?> / <?= $pages ?></span>
        <?php if ($page<$pages): ?><a href="?<?= e(http_build_query(['search'=>$search,'status'=>$status,'priority'=>$priority,'category_id'=>$categoryId,'technician'=>$technician,'page'=>$page+1])) ?>">หน้าถัดไป</a><?php endif; ?>
    </nav>
</section>
</main></body></html>

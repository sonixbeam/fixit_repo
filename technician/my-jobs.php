<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/repair-technician.php';
$user = requireRole('TECHNICIAN');
$pageTitle = 'งานที่ได้รับมอบหมาย';
$statusLabels = adminStatusLabels(); $priorityLabels = adminPriorityLabels();
$filterLabels = $statusLabels + ['WAITING'=>'รอนักศึกษายืนยัน'];
$search = adminText($_GET,'search'); $status = adminText($_GET,'status'); $priority = adminText($_GET,'priority');
$categoryId = adminPositiveId($_GET['category_id'] ?? null);
$params = [(int)$user['user_id']];
$where = ['EXISTS(SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.technician_user_id=? AND a.is_active=1)'];
if (isset($statusLabels[$status])) { $where[]='r.status=?'; $params[]=$status; }
if ($status==='WAITING') { $where[]="r.status='IN_PROGRESS' AND EXISTS(SELECT 1 FROM repair_completion rc WHERE rc.request_id=r.request_id AND rc.confirmed_at IS NULL)"; }
if (isset($priorityLabels[$priority])) { $where[]='r.priority=?'; $params[]=$priority; }
if ($categoryId) { $where[]='r.category_id=?'; $params[]=$categoryId; }
if ($search!=='') { $where[]='(CAST(r.request_id AS CHAR) LIKE ? OR r.title LIKE ?)'; $params[]='%'.$search.'%'; $params[]='%'.$search.'%'; }
$from = ' FROM repair_request r JOIN repair_category c ON c.category_id=r.category_id LEFT JOIN room rm ON rm.room_id=r.room_id LEFT JOIN common_area ca ON ca.area_id=r.area_id WHERE '.implode(' AND ',$where);
$stmt=$pdo->prepare('SELECT COUNT(*)'.$from); $stmt->execute($params); $total=(int)$stmt->fetchColumn();
$pages=max(1,(int)ceil($total/25)); $page=min(adminPositiveId($_GET['page'] ?? null) ?? 1,$pages);
$stmt=$pdo->prepare('SELECT r.*,c.category_name,rm.room_number,ca.area_name,
    EXISTS(SELECT 1 FROM repair_completion rc WHERE rc.request_id=r.request_id AND rc.confirmed_at IS NULL) AS waiting'.$from.' ORDER BY r.created_at DESC,r.request_id DESC LIMIT 25 OFFSET '.(($page-1)*25));
$stmt->execute($params); $jobs=$stmt->fetchAll();
$categories=$pdo->query('SELECT category_id,category_name FROM repair_category ORDER BY category_name')->fetchAll();
require __DIR__.'/../includes/technician-header.php';
?>
<section class="panel"><form method="GET" class="admin-filters">
<label>ค้นหาเลขงาน/หัวข้อ<input name="search" maxlength="100" value="<?= e($search) ?>"></label>
<label>สถานะ<select name="status"><option value="">ทุกสถานะ</option><?php foreach($filterLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $status===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>ความสำคัญ<select name="priority"><option value="">ทุกระดับ</option><?php foreach($priorityLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $priority===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>หมวดหมู่<select name="category_id"><option value="">ทุกหมวดหมู่</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $categoryId===(int)$c['category_id']?'selected':'' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></label>
<button type="submit">ค้นหา/กรอง</button><a href="my-jobs.php">ล้างตัวกรอง</a></form>
<p>พบ <?= $total ?> งาน</p>
<?php if(!$jobs): ?><p class="subtitle">ไม่พบงานที่ได้รับมอบหมายตามเงื่อนไข</p><?php else: ?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>เลขงาน/หัวข้อ</th><th>สถานที่</th><th>หมวดหมู่</th><th>สถานะ</th><th>ความสำคัญ</th><th>วันที่แจ้ง</th></tr></thead><tbody>
<?php foreach($jobs as $j): ?><tr><td><a href="job-detail.php?id=<?= (int)$j['request_id'] ?>">#<?= (int)$j['request_id'] ?> <?= e($j['title']) ?></a></td><td><?= e($j['room_number']!==null?'ห้อง '.$j['room_number']:$j['area_name']) ?></td><td><?= e($j['category_name']) ?></td><td><?= e($j['status']==='IN_PROGRESS' && $j['waiting']?'รอนักศึกษายืนยัน':$statusLabels[$j['status']]) ?></td><td><?= e($priorityLabels[$j['priority']]) ?></td><td><?= e($j['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<nav class="admin-pagination" aria-label="แบ่งหน้า">
<?php if($page>1): ?><a href="?<?= e(http_build_query(['search'=>$search,'status'=>$status,'priority'=>$priority,'category_id'=>$categoryId,'page'=>$page-1])) ?>">หน้าก่อน</a><?php endif; ?>
<span>หน้า <?= $page ?> / <?= $pages ?></span>
<?php if($page<$pages): ?><a href="?<?= e(http_build_query(['search'=>$search,'status'=>$status,'priority'=>$priority,'category_id'=>$categoryId,'page'=>$page+1])) ?>">หน้าถัดไป</a><?php endif; ?>
</nav></section></main></body></html>

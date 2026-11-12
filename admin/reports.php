<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/repair-reports.php';
$user=requireRole('ADMIN');
if($_SERVER['REQUEST_METHOD']!=='GET'){header('Allow: GET');http_response_code(405);exit('รายงานรองรับการอ่านข้อมูลเท่านั้น');}
$pageTitle='รายงานการแจ้งซ่อม';$statusLabels=adminStatusLabels();$priorityLabels=adminPriorityLabels();
$filterStatusLabels=$statusLabels+['WAITING'=>'รอนักศึกษายืนยัน'];$error='';$report=null;
try{$filters=reportFilters($_GET);$report=repairReport($pdo,$filters);}
catch(DomainException $ex){http_response_code(400);$error=$ex->getMessage();$filters=reportFilters([]);}
catch(Throwable $ex){http_response_code(500);$error='ไม่สามารถอ่านรายงานได้ กรุณาลองใหม่';$filters=reportFilters([]);}
$categories=$pdo->query('SELECT category_id,category_name FROM repair_category ORDER BY category_name')->fetchAll();
$technicians=$pdo->query("SELECT user_id,username,is_active FROM `user` WHERE role='TECHNICIAN' ORDER BY username")->fetchAll();
require __DIR__.'/../includes/admin-header.php';
?>
<p class="subtitle">รายงานตามวันที่แจ้ง (เวลาไทย) วันสิ้นสุดรวมทั้งวัน ทุกส่วนใช้ตัวกรองเดียวกัน</p>
<section class="panel"><form method="GET" class="admin-filters">
<label>วันที่แจ้งเริ่มต้น<input type="date" name="date_from" value="<?= e(adminText($_GET,'date_from',10)) ?>"></label>
<label>วันที่แจ้งสิ้นสุด<input type="date" name="date_to" value="<?= e(adminText($_GET,'date_to',10)) ?>"></label>
<label>สถานะ<select name="status"><option value="">ทุกสถานะ</option><?php foreach($filterStatusLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $filters['status']===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>ความสำคัญ<select name="priority"><option value="">ทุกระดับ</option><?php foreach($priorityLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $filters['priority']===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>หมวดหมู่<select name="category_id"><option value="">ทุกหมวดหมู่</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $filters['category_id']===(string)$c['category_id']?'selected':'' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></label>
<label>ช่างที่เกี่ยวข้อง<select name="technician"><option value="">ทุกคน</option><?php foreach($technicians as $t): ?><option value="<?= (int)$t['user_id'] ?>" <?= $filters['technician']===(string)$t['user_id']?'selected':'' ?>><?= e($t['username']) ?><?= $t['is_active']?'':' (ปิดใช้งาน)' ?></option><?php endforeach; ?></select></label>
<button type="submit">แสดงรายงาน</button><a href="reports.php">ล้างตัวกรอง</a>
</form>
<p class="subtitle">เว้นวันที่เพื่อดูทุกช่วงเวลา ช่างที่เกี่ยวข้องหมายถึงผู้รับมอบหมายปัจจุบันหรือผู้ส่งหลักฐานจริง</p>
<?php if($error): ?><p class="admin-alert error" role="alert"><?= e($error) ?></p><?php endif; ?>
</section>
<?php if($report): ?>
<div class="cards">
<div class="card"><h2>คำร้องทั้งหมดตามตัวกรอง</h2><strong id="report-total"><?= $report['total'] ?></strong> คำร้อง</div>
<div class="card"><h2>รอนักศึกษายืนยัน</h2><strong><?= $report['waiting'] ?></strong> คำร้อง<p>นับรวมในกำลังดำเนินการแล้ว</p></div>
<div class="card"><h2>รอมอบหมายช่าง</h2><strong><?= $report['unassigned'] ?></strong> คำร้อง<p>เฉพาะคำร้องรอดำเนินการ</p></div>
</div>
<?php if(array_sum($report['anomalies'])>0): ?>
<section class="panel" role="status"><h2>พบข้อมูลที่ควรตรวจสอบ</h2><p>ตัวเลขแต่ละกลุ่มอาจเป็นคำร้องเดียวกัน รายงานไม่แก้ข้อมูลเดิมอัตโนมัติ</p><ul>
<?php foreach(['duplicate_assignment'=>'คำร้องที่มีการมอบหมาย active มากกว่าหนึ่งรายการ','completed_without_confirmation'=>'คำร้องเสร็จสิ้นที่ข้อมูลยืนยันไม่ครบ','confirmation_mismatch'=>'ข้อมูลยืนยันหรือเวลาปิดไม่สอดคล้อง','submission_mismatch'=>'ข้อมูลผู้ส่งหลักฐานหรือการมอบหมายไม่สอดคล้อง'] as $key=>$label): ?>
<?php if($report['anomalies'][$key]>0): ?><li><?= e($label) ?>: <?= $report['anomalies'][$key] ?> คำร้อง</li><?php endif; ?><?php endforeach; ?>
</ul></section><?php endif; ?>
<section class="panel"><h2>สรุปตามสถานะ</h2><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>สถานะ</th><th>จำนวนคำร้อง</th></tr></thead><tbody>
<?php foreach($statusLabels as $key=>$label): ?><tr><td><?= e($label) ?></td><td><?= $report['statuses'][$key] ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="panel"><h2>สรุปตามความสำคัญ</h2><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ความสำคัญ</th><th>จำนวนคำร้อง</th></tr></thead><tbody>
<?php foreach($priorityLabels as $key=>$label): ?><tr><td><?= e($label) ?></td><td><?= $report['priorities'][$key] ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="panel"><h2>สรุปตามหมวดหมู่</h2>
<?php if(!$report['categories']): ?><p class="subtitle">ไม่มีข้อมูลตามตัวกรอง</p><?php else: ?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>หมวดหมู่</th><th>จำนวนคำร้อง</th></tr></thead><tbody>
<?php foreach($report['categories'] as $c): ?><tr><td><?= e($c['category_name']) ?></td><td><?= (int)$c['total'] ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>สรุปตามช่าง</h2>
<p class="subtitle">งานรับมอบหมายปัจจุบันรวมทุกสถานะ ส่วนงานส่งหลักฐานนับตามช่างผู้ส่งจริง ทั้งสองคอลัมน์อาจเป็นคำร้องเดียวกัน จึงไม่ควรบวกเข้าด้วยกัน<?= $filters['technician']!==''?' แสดงเฉพาะช่างที่เลือก':'' ?></p>
<?php if(!$report['technicians']): ?><p class="subtitle">ไม่มีข้อมูลช่างตามตัวกรอง</p><?php else: ?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ช่าง</th><th>รับมอบหมายปัจจุบัน</th><th>ส่งหลักฐานแล้ว</th><th>นักศึกษายืนยันแล้วจากงานที่ส่ง</th></tr></thead><tbody>
<?php foreach($report['technicians'] as $t): ?><tr><td><?= e($t['username']) ?><?= $t['is_active']?'':' (ปิดใช้งาน)' ?></td><td><?= (int)$t['assigned'] ?></td><td><?= (int)$t['submitted'] ?></td><td><?= (int)$t['confirmed'] ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>คำร้องประกอบรายงาน</h2><p>ทั้งหมด <?= $report['total'] ?> คำร้องตามตัวกรองข้างต้น</p>
<?php if(!$report['rows']): ?><p class="subtitle">ไม่พบคำร้องตามตัวกรอง</p><?php else: ?>
<div class="admin-table-wrap"><table class="admin-table admin-request-table"><thead><tr><th>คำร้อง/ผู้แจ้ง</th><th>สถานที่/หมวดหมู่</th><th>สถานะ/ความสำคัญ</th><th>ช่างปัจจุบัน</th><th>ผู้ส่งหลักฐานจริง</th><th>วันที่แจ้ง</th></tr></thead><tbody>
<?php foreach($report['rows'] as $r): ?><tr><td><a href="request-detail.php?id=<?= (int)$r['request_id'] ?>">#<?= (int)$r['request_id'] ?> <?= e($r['title']) ?></a><br><?= e($r['username']) ?></td>
<td><?= e($r['room_number']!==null?'ห้อง '.$r['room_number']:$r['area_name']) ?><br><?= e($r['category_name']) ?></td>
<td><?= e($r['status']==='IN_PROGRESS' && $r['submitted_at'] && !$r['confirmed_at']?'รอนักศึกษายืนยัน':$statusLabels[$r['status']]) ?><br><?= e($priorityLabels[$r['priority']]) ?></td>
<td><?= e($r['assigned_names'] ?? 'ยังไม่มอบหมาย') ?></td><td><?= e($r['submitter_name'] ?? 'ยังไม่ส่งหลักฐาน') ?></td><td><?= e($r['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<nav class="admin-pagination" aria-label="แบ่งหน้ารายงาน">
<?php if($report['page']>1): ?><a href="?<?= e(http_build_query(array_merge($filters,['page'=>$report['page']-1]))) ?>">หน้าก่อน</a><?php endif; ?>
<span>หน้า <?= $report['page'] ?> / <?= $report['pages'] ?></span>
<?php if($report['page']<$report['pages']): ?><a href="?<?= e(http_build_query(array_merge($filters,['page'=>$report['page']+1]))) ?>">หน้าถัดไป</a><?php endif; ?>
</nav></section>
<?php endif; ?></main></body></html>

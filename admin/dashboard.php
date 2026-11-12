<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/repair-admin.php';
$user = requireRole('ADMIN');
$pageTitle = 'ภาพรวมผู้ดูแลระบบ';
$statusLabels = adminStatusLabels(); $priorityLabels = adminPriorityLabels();
$statuses = $pdo->query('SELECT status,COUNT(*) AS total FROM repair_request GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$priorities = $pdo->query("SELECT priority,COUNT(*) AS total FROM repair_request WHERE status IN ('PENDING','IN_PROGRESS') GROUP BY priority")->fetchAll(PDO::FETCH_KEY_PAIR);
$unassigned = (int)$pdo->query("SELECT COUNT(*) FROM repair_request r WHERE r.status='PENDING'
    AND NOT EXISTS(SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.is_active=1)")->fetchColumn();
$waiting = (int)$pdo->query("SELECT COUNT(*) FROM repair_request r JOIN repair_completion c ON c.request_id=r.request_id
    WHERE r.status='IN_PROGRESS' AND c.confirmed_at IS NULL")->fetchColumn();
$categories = $pdo->query('SELECT c.category_name,COUNT(r.request_id) AS total FROM repair_category c
    LEFT JOIN repair_request r ON r.category_id=c.category_id GROUP BY c.category_id,c.category_name ORDER BY total DESC,c.category_name')->fetchAll();
require __DIR__ . '/../includes/admin-header.php';
?>
<p class="subtitle">สวัสดี <?= e($user['username']) ?> — สรุปจากคำร้องทั้งหมดในระบบ</p>
<p><a href="reports.php">ดูรายงานตามช่วงวันที่ หมวดหมู่ และช่าง</a></p>
<div class="cards">
    <?php foreach ($statusLabels as $key=>$label): ?><a class="card" href="requests.php?status=<?= e($key) ?>"><h2><?= e($label) ?></h2><strong><?= (int)($statuses[$key] ?? 0) ?></strong> คำร้อง</a><?php endforeach; ?>
    <a class="card" href="requests.php?status=PENDING&amp;technician=unassigned"><h2>รอมอบหมายช่าง</h2><strong><?= $unassigned ?></strong> คำร้อง</a>
    <a class="card" href="requests.php?status=WAITING"><h2>รอนักศึกษายืนยัน</h2><strong><?= $waiting ?></strong> คำร้อง</a>
</div>
<section class="panel">
    <h2>ความสำคัญของงานที่ยังไม่ปิด</h2><div class="cards">
    <?php foreach ($priorityLabels as $key=>$label): ?><a class="card" href="requests.php?status=OPEN&amp;priority=<?= e($key) ?>"><h3><?= e($label) ?></h3><strong><?= (int)($priorities[$key] ?? 0) ?></strong> คำร้อง</a><?php endforeach; ?>
    </div>
</section>
<section class="panel"><h2>สรุปตามหมวดหมู่</h2>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>หมวดหมู่</th><th>จำนวนคำร้องทั้งหมด</th></tr></thead><tbody>
    <?php foreach ($categories as $c): ?><tr><td><?= e($c['category_name']) ?></td><td><?= (int)$c['total'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
</main></body></html>

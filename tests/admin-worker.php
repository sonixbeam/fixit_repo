<?php
if (PHP_SAPI!=='cli' || !preg_match('/^fixit_test_[a-f0-9]{12}$/',$argv[1] ?? '')) { exit(2); }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/repair-admin.php';
$pdo->exec('USE `'.$argv[1].'`');
try {
    $id=(int)$argv[2];
    if (($argv[3] ?? '')==='cancel') {
        $pdo->beginTransaction();
        $stmt=$pdo->prepare("UPDATE repair_request SET status='CANCELLED',cancelled_at=NOW() WHERE request_id=? AND user_id=1 AND status='PENDING'");
        $stmt->execute([$id]);
        if ($stmt->rowCount()===1) {
            $pdo->prepare("INSERT INTO repair_history (request_id,changed_by_user_id,old_status,new_status,note) VALUES (?,1,'PENDING','CANCELLED','test cancellation')")->execute([$id]);
        }
        $pdo->commit();
        echo 'cancelled';
    } else {
        echo adminChangeRequest($pdo,$id,4,'assign',['technician_user_id'=>3]) ? 'changed' : 'unchanged';
    }
} catch (DomainException $ex) {
    echo 'rejected';
}

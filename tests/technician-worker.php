<?php
if(PHP_SAPI!=='cli' || !preg_match('/^fixit_test_[a-f0-9]{12}$/',$argv[1] ?? '')){exit(2);}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/repair-technician.php';
$pdo->exec('USE `'.$argv[1].'`');
$id=(int)$argv[2];$action=$argv[3];
try {
    if($action==='reassign'){adminChangeRequest($pdo,$id,4,'assign',['technician_user_id'=>5]);echo 'reassigned';}
    elseif($action==='cancel'){
        $pdo->beginTransaction();
        $stmt=$pdo->prepare("UPDATE repair_request SET status='CANCELLED',cancelled_at=NOW() WHERE request_id=? AND status='PENDING'");$stmt->execute([$id]);
        $changed=$stmt->rowCount();
        if($changed){$pdo->prepare("INSERT INTO repair_history (request_id,changed_by_user_id,old_status,new_status,note) VALUES (?,1,'PENDING','CANCELLED','test cancel')")->execute([$id]);}
        $pdo->commit();echo $changed?'cancelled':'rejected';
    } else {technicianChangeRequest($pdo,$id,3,'start');echo 'started';}
} catch(DomainException $ex){echo 'rejected';}

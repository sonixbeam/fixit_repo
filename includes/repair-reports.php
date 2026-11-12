<?php
require_once __DIR__.'/repair-admin.php';

function reportFilters(array $input): array
{
    $filters=[];
    foreach(['date_from','date_to','status','priority','category_id','technician'] as $key){
        $value=$input[$key] ?? '';
        if(!is_string($value) || strlen($value)>100){throw new DomainException('ค่าตัวกรองไม่ถูกต้อง');}
        $filters[$key]=trim($value);
    }
    $tz=new DateTimeZone('Asia/Bangkok');
    foreach(['date_from','date_to'] as $key){
        if($filters[$key]===''){continue;}
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$filters[$key],$tz);
        if(!$date || $date->format('Y-m-d')!==$filters[$key] || $date->format('Y')<'1000' || $date->format('Y')>'9998'){
            throw new DomainException('กรุณาระบุวันที่ที่ถูกต้อง');
        }
    }
    if($filters['date_from']!=='' && $filters['date_to']!=='' && $filters['date_from']>$filters['date_to']){
        throw new DomainException('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด');
    }
    if($filters['status']!=='' && !isset((adminStatusLabels()+['WAITING'=>'รอนักศึกษายืนยัน'])[$filters['status']])){
        throw new DomainException('สถานะไม่ถูกต้อง');
    }
    if($filters['priority']!=='' && !isset(adminPriorityLabels()[$filters['priority']])){throw new DomainException('ความสำคัญไม่ถูกต้อง');}
    foreach(['category_id','technician'] as $key){
        if($filters[$key]!=='' && !adminPositiveId($filters[$key])){throw new DomainException('เลขหมวดหมู่หรือช่างไม่ถูกต้อง');}
    }
    $page=$input['page'] ?? '1';
    if(!is_string($page) || !adminPositiveId($page)){throw new DomainException('เลขหน้าไม่ถูกต้อง');}
    $filters['page']=(int)$page;
    return $filters;
}

function reportScope(array $filters): array
{
    $where=['1=1'];$params=[];
    if($filters['date_from']!==''){$where[]='r.created_at >= ?';$params[]=$filters['date_from'].' 00:00:00';}
    if($filters['date_to']!==''){
        $where[]='r.created_at < ?';
        $params[]=(new DateTimeImmutable($filters['date_to'],new DateTimeZone('Asia/Bangkok')))->modify('+1 day')->format('Y-m-d').' 00:00:00';
    }
    if($filters['status']==='WAITING'){
        $where[]="r.status='IN_PROGRESS' AND EXISTS(SELECT 1 FROM repair_completion c WHERE c.request_id=r.request_id AND c.confirmed_at IS NULL)";
    }elseif($filters['status']!==''){$where[]='r.status=?';$params[]=$filters['status'];}
    if($filters['priority']!==''){$where[]='r.priority=?';$params[]=$filters['priority'];}
    if($filters['category_id']!==''){$where[]='r.category_id=?';$params[]=(int)$filters['category_id'];}
    if($filters['technician']!==''){
        $where[]='(EXISTS(SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.is_active=1 AND a.technician_user_id=?)
            OR EXISTS(SELECT 1 FROM repair_completion c WHERE c.request_id=r.request_id AND c.submitted_by_user_id=?))';
        $params[]=(int)$filters['technician'];$params[]=(int)$filters['technician'];
    }
    return [implode(' AND ',$where),$params];
}

// ทุก aggregate และรายการประกอบใช้ scope เดียวกันและ consistent snapshot
function repairReport(PDO $pdo,array $filters): array
{
    [$where,$params]=reportScope($filters);
    $query=function(string $sql,?array $values=null) use($pdo,$params): PDOStatement {
        $stmt=$pdo->prepare($sql);$stmt->execute($values ?? $params);return $stmt;
    };
    if($pdo->inTransaction()){throw new LogicException('Report requires its own read transaction');}
    $pdo->exec("SET SESSION time_zone='+07:00'");
    $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $pdo->exec('START TRANSACTION READ ONLY');
    try{
        $statuses=array_fill_keys(array_keys(adminStatusLabels()),0);
        foreach($query("SELECT r.status,COUNT(*) AS total FROM repair_request r WHERE $where GROUP BY r.status")->fetchAll() as $row){$statuses[$row['status']]=(int)$row['total'];}
        $total=array_sum($statuses);
        $priorities=array_fill_keys(array_keys(adminPriorityLabels()),0);
        foreach($query("SELECT r.priority,COUNT(*) AS total FROM repair_request r WHERE $where GROUP BY r.priority")->fetchAll() as $row){$priorities[$row['priority']]=(int)$row['total'];}
        $categories=$query("SELECT c.category_id,c.category_name,COUNT(*) AS total FROM repair_request r
            JOIN repair_category c ON c.category_id=r.category_id WHERE $where GROUP BY c.category_id,c.category_name ORDER BY total DESC,c.category_name")->fetchAll();
        $waiting=(int)$query("SELECT COUNT(*) FROM repair_request r WHERE $where AND r.status='IN_PROGRESS'
            AND EXISTS(SELECT 1 FROM repair_completion c WHERE c.request_id=r.request_id AND c.confirmed_at IS NULL)")->fetchColumn();
        $unassigned=(int)$query("SELECT COUNT(*) FROM repair_request r WHERE $where AND r.status='PENDING'
            AND NOT EXISTS(SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.is_active=1)")->fetchColumn();
        $technicians=[];
        $techWhere=$filters['technician']!==''?' AND u.user_id='.(int)$filters['technician']:'';
        foreach($query("SELECT u.user_id,u.username,u.is_active,COUNT(DISTINCT r.request_id) AS assigned FROM repair_request r
            JOIN repair_assignment a ON a.request_id=r.request_id AND a.is_active=1 JOIN `user` u ON u.user_id=a.technician_user_id
            WHERE $where $techWhere GROUP BY u.user_id,u.username,u.is_active")->fetchAll() as $row){
            $technicians[(int)$row['user_id']]=$row+['submitted'=>0,'confirmed'=>0];
        }
        foreach($query("SELECT u.user_id,u.username,u.is_active,COUNT(*) AS submitted,
            SUM(r.status='COMPLETED' AND c.confirmed_at IS NOT NULL AND c.confirmed_by_user_id=r.user_id AND r.completed_at IS NOT NULL) AS confirmed
            FROM repair_request r JOIN repair_completion c ON c.request_id=r.request_id JOIN `user` u ON u.user_id=c.submitted_by_user_id
            WHERE $where $techWhere GROUP BY u.user_id,u.username,u.is_active")->fetchAll() as $row){
            $key=(int)$row['user_id'];
            if(!isset($technicians[$key])){$technicians[$key]=$row+['assigned'=>0];}
            else{$technicians[$key]['submitted']=$row['submitted'];$technicians[$key]['confirmed']=$row['confirmed'];}
        }
        uasort($technicians,fn($a,$b)=>strcmp($a['username'],$b['username']));
        $anomalies=[];
        $anomalies['duplicate_assignment']=(int)$query("SELECT COUNT(*) FROM repair_request r WHERE $where
            AND (SELECT COUNT(*) FROM repair_assignment a WHERE a.request_id=r.request_id AND a.is_active=1)>1")->fetchColumn();
        $anomalies['completed_without_confirmation']=(int)$query("SELECT COUNT(*) FROM repair_request r LEFT JOIN repair_completion c ON c.request_id=r.request_id
            WHERE $where AND r.status='COMPLETED' AND (c.request_id IS NULL OR c.confirmed_at IS NULL OR c.confirmed_by_user_id IS NULL OR c.confirmed_by_user_id<>r.user_id OR r.completed_at IS NULL)")->fetchColumn();
        $anomalies['confirmation_mismatch']=(int)$query("SELECT COUNT(*) FROM repair_request r JOIN repair_completion c ON c.request_id=r.request_id WHERE $where
            AND ((c.confirmed_at IS NULL)<>(c.confirmed_by_user_id IS NULL) OR
                (c.confirmed_at IS NOT NULL AND (r.status<>'COMPLETED' OR c.confirmed_by_user_id<>r.user_id OR r.completed_at IS NULL))
                OR r.completed_at<r.created_at OR c.submitted_at<r.created_at OR c.confirmed_at<c.submitted_at OR r.completed_at<c.confirmed_at)")->fetchColumn();
        $anomalies['submission_mismatch']=(int)$query("SELECT COUNT(*) FROM repair_request r JOIN repair_completion c ON c.request_id=r.request_id JOIN `user` u ON u.user_id=c.submitted_by_user_id
            WHERE $where AND (r.status NOT IN ('IN_PROGRESS','COMPLETED') OR u.role<>'TECHNICIAN' OR NOT EXISTS(
                SELECT 1 FROM repair_assignment a WHERE a.request_id=r.request_id AND a.technician_user_id=c.submitted_by_user_id AND a.is_active=1 AND a.assigned_at<=c.submitted_at))")->fetchColumn();
        $pages=max(1,(int)ceil($total/25));$page=min($filters['page'],$pages);
        $rows=$query("SELECT r.*,u.username,c.category_name,rm.room_number,ca.area_name,s.username AS submitter_name,
            (SELECT GROUP_CONCAT(DISTINCT t.username ORDER BY t.username SEPARATOR ', ') FROM repair_assignment a
                JOIN `user` t ON t.user_id=a.technician_user_id WHERE a.request_id=r.request_id AND a.is_active=1) AS assigned_names,
            rc.submitted_at,rc.confirmed_at FROM repair_request r JOIN `user` u ON u.user_id=r.user_id
            JOIN repair_category c ON c.category_id=r.category_id LEFT JOIN room rm ON rm.room_id=r.room_id LEFT JOIN common_area ca ON ca.area_id=r.area_id
            LEFT JOIN repair_completion rc ON rc.request_id=r.request_id LEFT JOIN `user` s ON s.user_id=rc.submitted_by_user_id
            WHERE $where ORDER BY r.created_at DESC,r.request_id DESC LIMIT 25 OFFSET ".(($page-1)*25))->fetchAll();
        $pdo->commit();
        return compact('total','statuses','priorities','categories','waiting','unassigned','technicians','anomalies','rows','page','pages');
    }catch(Throwable $ex){if($pdo->inTransaction()){$pdo->rollBack();}throw $ex;}
}

<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
// เริ่มด้วย regression Phase 4.3 ทั้งชุด แล้วใช้ฐานทดสอบเดียวกันต่อ
require __DIR__.'/phase43.php';
require_once __DIR__.'/../includes/repair-admin.php';
$insert->execute([5,null,'test5',password_hash($password,PASSWORD_DEFAULT),'Test','Tech','TECHNICIAN']);
$insert->execute([6,null,'test6',password_hash($password,PASSWORD_DEFAULT),'Test','Inactive','TECHNICIAN']);
$pdo->exec('UPDATE `user` SET is_active=0 WHERE user_id=6');
$pdo->exec("INSERT INTO room (room_id,room_number,floor) VALUES (2,'EMPTY',2)");
function adminFixture(PDO $pdo, string $status='PENDING', int $owner=1): int {
    $pdo->prepare('INSERT INTO repair_request (user_id,room_id,category_id,title,description,status) VALUES (?,1,1,?,?,?)')->execute([$owner,'Admin fixture '.$owner,'<script>alert(1)</script>',$status]);
    return (int)$pdo->lastInsertId();
}
function adminSnapshot(PDO $pdo): array {
    $data=[];
    foreach(['repair_request','repair_assignment','repair_completion','repair_history','room'] as $table) {
        $data[$table]=$pdo->query("SELECT * FROM `$table` ORDER BY 1")->fetchAll();
    }
    return $data;
}
function adminRejected(PDO $pdo, callable $fn, string $label): void {
    $before=adminSnapshot($pdo);
    try { $fn(); } catch (DomainException $ex) {
        check($before===adminSnapshot($pdo),$label.' rejected without changing data'); return;
    }
    check(false,$label.' should reject');
}
$pending=adminFixture($pdo);
check(adminChangeRequest($pdo,$pending,4,'priority',['priority'=>'HIGH']),'admin changes priority');
$h=$pdo->query("SELECT * FROM repair_history WHERE request_id=$pending ORDER BY history_id DESC LIMIT 1")->fetch();
check($h['old_status']==='PENDING' && $h['new_status']==='PENDING' && str_contains($h['note'],'MEDIUM → HIGH') && (int)$h['changed_by_user_id']===4,'priority audit retains status and actor');
$before=adminSnapshot($pdo);
check(!adminChangeRequest($pdo,$pending,4,'priority',['priority'=>'HIGH']) && $before===adminSnapshot($pdo),'same priority is no-op');
adminRejected($pdo,fn()=>adminChangeRequest($pdo,$pending,1,'priority',['priority'=>'LOW']),'student actor');
adminRejected($pdo,fn()=>adminChangeRequest($pdo,$pending,4,'priority',['priority'=>'INVALID']),'invalid priority');
adminRejected($pdo,fn()=>adminChangeRequest($pdo,9999999,4,'assign',['technician_user_id'=>3]),'missing request');
adminRejected($pdo,fn()=>adminChangeRequest($pdo,$pending,4,'unknown',[]),'unknown action');
foreach([1,4,6,9999999] as $badTech) {
    adminRejected($pdo,fn()=>adminChangeRequest($pdo,$pending,4,'assign',['technician_user_id'=>$badTech]),'invalid/inactive technician '.$badTech);
}
check(adminChangeRequest($pdo,$pending,4,'assign',['technician_user_id'=>3,'note'=>'<note>']),'assign active technician');
$before=adminSnapshot($pdo);
check(!adminChangeRequest($pdo,$pending,4,'assign',['technician_user_id'=>3]) && $before===adminSnapshot($pdo),'double assignment no-op');
check(adminChangeRequest($pdo,$pending,4,'assign',['technician_user_id'=>5]),'reassign before work starts');
check((int)$pdo->query("SELECT COUNT(*) FROM repair_assignment WHERE request_id=$pending")->fetchColumn()===2
    && (int)$pdo->query("SELECT COUNT(*) FROM repair_assignment WHERE request_id=$pending AND is_active=1")->fetchColumn()===1
    && (int)$pdo->query("SELECT technician_user_id FROM repair_assignment WHERE request_id=$pending AND is_active=1")->fetchColumn()===5
    && $pdo->query("SELECT status FROM repair_request WHERE request_id=$pending")->fetchColumn()==='PENDING','reassignment retains old row and PENDING');
foreach(['IN_PROGRESS','COMPLETED','CANCELLED'] as $state) {
    $terminal=adminFixture($pdo,$state);
    adminRejected($pdo,fn()=>adminChangeRequest($pdo,$terminal,4,'assign',['technician_user_id'=>3]),'assignment '.$state);
    if($state!=='IN_PROGRESS') { adminRejected($pdo,fn()=>adminChangeRequest($pdo,$terminal,4,'priority',['priority'=>'HIGH']),'priority '.$state); }
    else { check(adminChangeRequest($pdo,$terminal,4,'priority',['priority'=>'HIGH']),'priority in progress before evidence'); }
}
$evidence=adminFixture($pdo,'IN_PROGRESS');
adminChangeRequest($pdo,$pending,4,'priority',['priority'=>'LOW']);
$pdo->prepare('INSERT INTO repair_assignment (request_id,technician_user_id,assigned_by_user_id) VALUES (?,3,4)')->execute([$evidence]);
$pdo->prepare("INSERT INTO repair_completion (request_id,submitted_by_user_id,completion_photo_path) VALUES (?,3,'uploads/completions/missing.png')")->execute([$evidence]);
adminRejected($pdo,fn()=>adminChangeRequest($pdo,$evidence,4,'assign',['technician_user_id'=>5]),'reassign after evidence');
adminRejected($pdo,fn()=>adminChangeRequest($pdo,$evidence,4,'priority',['priority'=>'HIGH']),'priority after evidence');
// บังคับ history ล้มเหลว ต้อง rollback ทั้ง Priority และ Assignment
$pdo->exec("CREATE TRIGGER admin_history_failure BEFORE INSERT ON repair_history FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='test rollback'");
foreach(['priority'=>['priority'=>'HIGH'],'assign'=>['technician_user_id'=>3]] as $action=>$values) {
    $before=adminSnapshot($pdo);
    try { adminChangeRequest($pdo,$pending,4,$action,$values); check(false,'must fail'); }
    catch(PDOException $ex) { check(($ex->errorInfo[0]??'')==='45000' && $before===adminSnapshot($pdo),$action.' rollback after history failure'); }
}
$pdo->exec('DROP TRIGGER admin_history_failure');
adminUpdateRoom($pdo,2,4,['room_number'=>'UPDATED','floor'=>3,'room_status'=>'MAINTENANCE']);
check($pdo->query('SELECT room_number FROM room WHERE room_id=2')->fetchColumn()==='UPDATED','edit room persists');
foreach([
    [2,['room_number'=>'TEST','floor'=>2,'room_status'=>'AVAILABLE']],
    [1,['room_number'=>'TEST','floor'=>1,'room_status'=>'AVAILABLE']],
    [2,['room_number'=>'','floor'=>2,'room_status'=>'AVAILABLE']],
    [2,['room_number'=>'X','floor'=>256,'room_status'=>'AVAILABLE']],
    [2,['room_number'=>'X','floor'=>1,'room_status'=>'BAD']],
] as [$roomId,$values]) { adminRejected($pdo,fn()=>adminUpdateRoom($pdo,$roomId,4,$values),'invalid/duplicate room'); }
adminRejected($pdo,fn()=>adminUpdateRoom($pdo,2,1,['room_number'=>'X','floor'=>1,'room_status'=>'AVAILABLE']),'student edits room');
check((int)$pdo->query('SELECT room_id FROM `user` WHERE user_id=1')->fetchColumn()===1
    && (int)$pdo->query("SELECT room_id FROM repair_request WHERE request_id=$pending")->fetchColumn()===1,'room edit preserves foreign keys');
// ทำให้ concurrent workers รอ parent row lock เดียวกัน
foreach([['assign','assign'],['assign','cancel']] as $actions) {
    $race=adminFixture($pdo); $pdo->beginTransaction();
    $pdo->query("SELECT request_id FROM repair_request WHERE request_id=$race FOR UPDATE")->fetch();
    $workers=[];
    foreach($actions as $action) {
        $process=proc_open([PHP_BINARY,__DIR__.'/admin-worker.php',$db,(string)$race,$action],[['pipe','r'],['pipe','w'],['pipe','w']],$wp);
        $workers[]=[$process,$wp];
    }
    usleep(300000); $pdo->commit(); $results=[];
    foreach($workers as [$process,$wp]) {
        fclose($wp[0]); $results[]=stream_get_contents($wp[1]); $err=stream_get_contents($wp[2]); fclose($wp[1]); fclose($wp[2]);
        check(proc_close($process)===0 && $err==='','admin parallel worker clean');
    }
    if($actions[1]==='assign') {
        sort($results);
        check($results===['changed','unchanged'] && (int)$pdo->query("SELECT COUNT(*) FROM repair_assignment WHERE request_id=$race AND is_active=1")->fetchColumn()===1
            && (int)$pdo->query("SELECT COUNT(*) FROM repair_history WHERE request_id=$race")->fetchColumn()===1,'concurrent assignment exactly once');
    } else {
        check($pdo->query("SELECT status FROM repair_request WHERE request_id=$race")->fetchColumn()==='CANCELLED'
            && (int)$pdo->query("SELECT COUNT(*) FROM repair_assignment WHERE request_id=$race AND is_active=1")->fetchColumn()<=1,'assign/cancel race preserves terminal status');
    }
}
// ใช้ HTTP client เดิมกับ PHP server ใหม่บนฐานทดสอบเดิม
$server=proc_open([PHP_BINARY,'-d','session.save_path='.__DIR__.'/sessions','-S','127.0.0.1:'.$port,__DIR__.'/router.php'],
    [['pipe','r'],['file',__DIR__.'/server.log','a'],['file',__DIR__.'/server.log','a']],$pipes,dirname(__DIR__));
try {
    for($n=0;$n<30;$n++) { $sock=@fsockopen('127.0.0.1',$port); if($sock){fclose($sock);break;} usleep(100000); }
    $routes=['admin/dashboard.php','admin/requests.php','admin/request-detail.php?id='.$pending,'admin/rooms.php'];
    foreach([null,1,3] as $actor) {
        $cookie=''; if($actor) {$http('login.php',['username'=>'test'.$actor,'password'=>$password]);}
        foreach($routes as $route) {
            check($http($route)[0]===302 && $http($route,['csrf_token'=>'x','action'=>'priority','priority'=>'HIGH'])[0]===302,'non-admin GET/POST blocked '.$route.' actor '.($actor??'anonymous'));
        }
        $http('logout.php');
    }
    $http('login.php',['username'=>'test4','password'=>$password]);
    foreach($routes as $route) { check($http($route)[0]===200,'admin GET '.$route); }
    $foreign=adminFixture($pdo,'PENDING',2);
    $body=$http('admin/requests.php?search=Admin+fixture+2&status=PENDING&priority=MEDIUM&category_id=1&technician=unassigned')[1];
    check(str_contains($body,'request-detail.php?id='.$foreign),'admin list filters across owners');
    check(str_contains($http('admin/requests.php?search=absent_unique_token')[1],'ไม่พบคำร้องตามเงื่อนไข'),'list empty state');
    $body=$http('admin/request-detail.php?id='.$foreign)[1]; $csrf=$token($body);
    check(str_contains($body,'&lt;script&gt;alert(1)&lt;/script&gt;'),'admin detail escapes HTML');
    check($http('admin/request-detail.php?id='.$foreign,['action'=>'priority','priority'=>'HIGH','csrf_token'=>'bad'])[0]===403,'admin CSRF rejection');
    check($http('admin/request-detail.php?id='.$foreign,['action'=>'priority','priority'=>'HIGH','csrf_token'=>$csrf])[0]===302,'HTTP priority POST');
    check($http('admin/request-detail.php?id='.$foreign,['action'=>'assign','technician_user_id'=>3,'note'=>'HTTP note','csrf_token'=>$csrf])[0]===302,'HTTP assign POST');
    check(str_contains($http('admin/requests.php?technician=3&priority=HIGH&status=PENDING')[1],'request-detail.php?id='.$foreign),'technician filter after assign');
    $dashboard=$http('admin/dashboard.php')[1];
    $expectedPending=(int)$pdo->query("SELECT COUNT(*) FROM repair_request WHERE status='PENDING'")->fetchColumn();
    check(preg_match('~<h2>รอดำเนินการ</h2><strong>'.$expectedPending.'</strong>~u',$dashboard)===1,'dashboard counts requests without assignment duplication');
    $openBody=$http('admin/requests.php?status=OPEN&search=Admin+fixture')[1];
    check(str_contains($openBody,'request-detail.php?id='.$foreign),'open filter includes pending request');
    $roomBody=$http('admin/rooms.php?edit=2')[1];
    check($http('admin/rooms.php?edit=2',['room_id'=>2,'room_number'=>'HTTPROOM','floor'=>4,'room_status'=>'AVAILABLE','csrf_token'=>$token($roomBody)])[0]===302,'HTTP room POST');
    check($http('admin/rooms.php?edit=2',['room_id'=>2,'room_number'=>'HTTPROOM','floor'=>4,'room_status'=>'AVAILABLE','csrf_token'=>'bad'])[0]===403,'room CSRF rejection');
    check($http('admin/request-detail.php?id=9999999')[0]===404 && $http('admin/request-detail.php?id[]=1')[0]===400,'invalid detail ids');
    check($http('admin/requests.php?search[]=x&status[]=x&category_id[]=1&page[]=1')[0]===200,'malformed filters do not crash');
    // จำลองข้อมูลจากช่างเฉพาะ test DB เพื่อเชื่อม Admin → Student confirmation
    $photo='uploads/completions/test_'.bin2hex(random_bytes(8)).'.png';
    file_put_contents(__DIR__.'/../'.$photo,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    $pdo->exec("UPDATE repair_request SET status='IN_PROGRESS' WHERE request_id=$foreign");
    $pdo->prepare('INSERT INTO repair_completion (request_id,submitted_by_user_id,completion_photo_path) VALUES (?,3,?)')->execute([$foreign,$photo]);
    check(str_contains($http('admin/requests.php?status=WAITING')[1],'request-detail.php?id='.$foreign),'waiting filter matches evidence');
    check(!str_contains($http('admin/request-detail.php?id='.$foreign)[1],'value="assign"'),'sent evidence is read-only for admin');
    $before=adminSnapshot($pdo);
    check($http('admin/request-detail.php?id='.$foreign,['action'=>'assign','technician_user_id'=>5,'csrf_token'=>$csrf])[0]===409
        && $before===adminSnapshot($pdo),'forged reassignment after evidence rejected atomically');
    check($http('admin/request-detail.php?id='.$foreign,['action'=>'confirm','csrf_token'=>$csrf])[0]===409
        && $before===adminSnapshot($pdo),'admin cannot bypass student confirmation');
    $http('logout.php'); $http('login.php',['username'=>'test2','password'=>$password]);
    $body=$http('student/request-detail.php?id='.$foreign)[1];
    check(str_contains($body,'value="confirm"') && str_contains($body,'HTTP note'),'student sees admin assignment and can confirm');
    check($http('student/request-detail.php?id='.$foreign,['action'=>'confirm','csrf_token'=>$token($body)])[0]===302
        && $pdo->query("SELECT status FROM repair_request WHERE request_id=$foreign")->fetchColumn()==='COMPLETED','admin assignment integrates with student completion');
    echo "Phase 5 executed checks passed. Admin browser visual QA and Technician UI integration NOT RUN.\n";
} finally {
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    if(isset($photo) && is_file(__DIR__.'/../'.$photo)){unlink(__DIR__.'/../'.$photo);}
}

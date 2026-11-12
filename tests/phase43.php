<?php
// CLI only. สร้าง DB ทดสอบใหม่จาก schema จริง ไม่แก้ข้อมูล fixit และไม่ DROP database
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/repair-completion.php';
$db = 'fixit_test_' . bin2hex(random_bytes(6));
$schema = [];
foreach (['room','common_area','repair_category','user','repair_request','repair_assignment','repair_history'] as $table) {
    $schema[] = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM)[1];
}
$pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$db`");
foreach ($schema as $sql) { $pdo->exec($sql); }
$migration = file_get_contents(__DIR__ . '/../migrations/20261113_repair_completion.sql');
$pdo->exec($migration);
$pdo->exec($migration);
echo "Test database: $db (retained; no production data changed)\n";
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $message); }
    echo 'PASS: ' . $message . PHP_EOL;
}
$pdo->exec("INSERT INTO room (room_id, room_number, floor) VALUES (1, 'TEST', 1)");
$pdo->exec("INSERT INTO repair_category (category_id, category_name) VALUES (1, 'TEST')");
$pdo->exec("INSERT INTO common_area (area_id, area_name) VALUES (1, 'TEST')");
$password = bin2hex(random_bytes(12));
$insert = $pdo->prepare('INSERT INTO `user` (user_id, room_id, username, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?, ?)');
foreach ([1=>'STUDENT',2=>'STUDENT',3=>'TECHNICIAN',4=>'ADMIN'] as $id=>$role) {
    $insert->execute([$id, $role === 'STUDENT' ? 1 : null, 'test'.$id, password_hash($password, PASSWORD_DEFAULT), 'Test', 'User', $role]);
}
$photo = 'uploads/completions/test_' . bin2hex(random_bytes(8)) . '.png';
file_put_contents(__DIR__ . '/../' . $photo, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
function fixture(PDO $pdo, string $photo, string $status = 'IN_PROGRESS'): int {
    $pdo->prepare('INSERT INTO repair_request (user_id, room_id, category_id, title, description, status) VALUES (1,1,1,?,?,?)')->execute(['Fixture', 'Test', $status]);
    $id = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO repair_assignment (request_id, technician_user_id, assigned_by_user_id) VALUES (?,3,4)')->execute([$id]);
    $pdo->prepare('INSERT INTO repair_completion (request_id, submitted_by_user_id, completion_photo_path) VALUES (?,3,?)')->execute([$id,$photo]);
    $pdo->prepare("INSERT INTO repair_history (request_id, changed_by_user_id, old_status, new_status, note) VALUES (?,3,'PENDING',?,'<test>')")->execute([$id,$status]);
    return $id;
}
function rejected(callable $fn, string $message) {
    global $pdo;
    $before=$pdo->query('SELECT request_id,status,completed_at FROM repair_request ORDER BY request_id')->fetchAll();
    $completionBefore=$pdo->query('SELECT * FROM repair_completion ORDER BY request_id')->fetchAll();
    $historyBefore=$pdo->query('SELECT * FROM repair_history ORDER BY history_id')->fetchAll();
    try { $fn(); } catch (Throwable $ex) {
        $expected=$message==='history failure injected'
            ? $ex instanceof PDOException && ($ex->errorInfo[0]??'')==='45000'
            : get_class($ex)===RuntimeException::class && $ex->getMessage()==='Repair is not ready for confirmation';
        check($expected && $before===$pdo->query('SELECT request_id,status,completed_at FROM repair_request ORDER BY request_id')->fetchAll()
            && $completionBefore===$pdo->query('SELECT * FROM repair_completion ORDER BY request_id')->fetchAll()
            && $historyBefore===$pdo->query('SELECT * FROM repair_history ORDER BY history_id')->fetchAll(), $message.' with expected error and unchanged data');
        return;
    }
    check(false, $message);
}
$server = null;
try {
    $id = fixture($pdo, $photo);
    rejected(fn()=>confirmRepair($pdo,$id,2), 'other owner rejected');
    foreach (['PENDING','CANCELLED','COMPLETED'] as $state) {
        $bad = fixture($pdo,$photo,$state);
        rejected(fn()=>confirmRepair($pdo,$bad,1), $state . ' rejected');
    }
    $bad = fixture($pdo,'uploads/completions/missing.png');
    rejected(fn()=>confirmRepair($pdo,$bad,1), 'missing photo rejected');
    $bad = fixture($pdo,$photo);
    $pdo->exec("UPDATE repair_assignment SET is_active=0 WHERE request_id=$bad");
    rejected(fn()=>confirmRepair($pdo,$bad,1), 'unassigned submitter rejected');
    $rollback = fixture($pdo,$photo);
    $pdo->exec("CREATE TRIGGER test_history_failure BEFORE INSERT ON repair_history FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='test rollback'");
    rejected(fn()=>confirmRepair($pdo,$rollback,1), 'history failure injected');
    check($pdo->query("SELECT status FROM repair_request WHERE request_id=$rollback")->fetchColumn()==='IN_PROGRESS'
        && repairCompletion($pdo,$rollback)['confirmed_at']===null, 'transaction fully rolled back');
    $pdo->exec('DROP TRIGGER test_history_failure');
    // ล็อกแถวไว้จน worker ทั้งคู่เริ่ม เพื่อให้คำยืนยันแข่งกันที่ lock เดียวกัน
    $race = fixture($pdo,$photo);
    $pdo->beginTransaction();
    $pdo->query("SELECT request_id FROM repair_request WHERE request_id=$race FOR UPDATE")->fetch();
    $workers=[];
    for($n=0;$n<2;$n++) {
        $process=proc_open([PHP_BINARY,__DIR__.'/confirm-worker.php',$db,(string)$race],
            [['pipe','r'],['pipe','w'],['pipe','w']],$workerPipes);
        $workers[]=[$process,$workerPipes];
    }
    usleep(300000);
    $pdo->commit();
    $results=[];
    foreach($workers as [$process,$workerPipes]) {
        fclose($workerPipes[0]);
        $results[]=stream_get_contents($workerPipes[1]);
        $errors=stream_get_contents($workerPipes[2]);
        fclose($workerPipes[1]); fclose($workerPipes[2]);
        check(proc_close($process)===0 && $errors==='', 'parallel worker exits cleanly');
    }
    sort($results);
    check($results===['confirmed','rejected'] && (int)$pdo->query("SELECT COUNT(*) FROM repair_history WHERE request_id=$race AND new_status='COMPLETED'")->fetchColumn()===1,
        'simultaneous confirmation creates exactly one history');
    // HTTP ใช้ endpoint จริงกับฐานข้อมูลทดสอบ ผ่าน router ที่จำกัดเส้นทาง
    putenv('FIXIT_TEST_DB='.$db);
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    $port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    if (!is_dir(__DIR__.'/sessions')) { mkdir(__DIR__.'/sessions'); }
    $server = proc_open([PHP_BINARY,'-d','session.save_path='.__DIR__.'/sessions','-S','127.0.0.1:'.$port,__DIR__.'/router.php'],
        [['pipe','r'],['file',__DIR__.'/server.log','a'],['file',__DIR__.'/server.log','a']], $pipes, dirname(__DIR__));
    for ($i=0;$i<30;$i++) { $sock=@fsockopen('127.0.0.1',$port); if($sock){fclose($sock);break;} usleep(100000); }
    $cookie='';
    $http = function(string $route, ?array $post=null) use ($port, &$cookie): array {
        $headers = "Cookie: $cookie\r\n";
        if ($post!==null) { $headers .= "Content-Type: application/x-www-form-urlencoded\r\n"; }
        $context=stream_context_create(['http'=>['method'=>$post===null?'GET':'POST','header'=>$headers,
            'content'=>$post===null?'':http_build_query($post),'ignore_errors'=>true,'follow_location'=>0]]);
        $body=file_get_contents('http://127.0.0.1:'.$port.'/fixit/'.$route,false,$context);
        foreach($http_response_header as $header) { if(preg_match('/^Set-Cookie: ([^;]+)/i',$header,$m)){$cookie=$m[1];} }
        preg_match('/\s(\d{3})\s/',$http_response_header[0],$m);
        return [(int)$m[1],$body];
    };
    $token = function(string $body, string $name='csrf_token'): string {
        preg_match('/name="'.$name.'"\s+value="([^"]+)"/s',$body,$m);
        return $m[1]??'';
    };
    check($http('student/my-requests.php')[0]===302,'anonymous redirected');
    check($http('login.php',['username'=>'test1','password'=>$password])[0]===302,'login password_verify');
    [$code,$body]=$http('student/request-detail.php?id='.$id);
    check($code===200 && str_contains($body,'&lt;test&gt;') && str_contains($body,'value="confirm"'), 'owner timeline escaped and confirmation displayed');
    $timeline=$pdo->prepare("INSERT INTO repair_history (request_id,changed_by_user_id,old_status,new_status,note,changed_at) VALUES (?,3,'IN_PROGRESS','IN_PROGRESS',?,?)");
    foreach ([['timeline-late','2026-01-02 12:00:00'],['timeline-first','2026-01-01 12:00:00'],['timeline-tie','2026-01-01 12:00:00']] as [$note,$at]) {
        $timeline->execute([$id,$note,$at]);
    }
    $timelineBody=$http('student/request-detail.php?id='.$id)[1];
    check(strpos($timelineBody,'timeline-first')<strpos($timelineBody,'timeline-tie')
        && strpos($timelineBody,'timeline-tie')<strpos($timelineBody,'timeline-late')
        && str_contains($timelineBody,'2026-01-01 12:00:00'),'timeline ordered by actual timestamp then history id');
    check($http('student/request-detail.php?id='.$id,['action'=>'confirm','csrf_token'=>'bad'])[0]===403,'CSRF rejected');
    $confirmToken=$token($body);
    check($http('student/request-detail.php?id='.$id,['action'=>'confirm','csrf_token'=>$confirmToken])[0]===302,'HTTP confirmation succeeds');
    $c=repairCompletion($pdo,$id);
    check($c['confirmed_at']!==null && (int)$c['confirmed_by_user_id']===1
        && $pdo->query("SELECT status FROM repair_request WHERE request_id=$id")->fetchColumn()==='COMPLETED'
        && (int)$pdo->query("SELECT COUNT(*) FROM repair_history WHERE request_id=$id AND new_status='COMPLETED'")->fetchColumn()===1,'completion audit and status atomic');
    check($http('student/request-detail.php?id='.$id,['action'=>'confirm','csrf_token'=>$confirmToken])[0]===409,'double submit rejected');
    check(!str_contains($http('student/request-detail.php?id='.$id)[1],'value="confirm"'),'confirmation hidden after completion');
    $http('logout.php');
    $http('login.php',['username'=>'test2','password'=>$password]);
    check($http('student/request-detail.php?id='.$id)[0]===404,'other student GET rejected');
    check($http('student/request-detail.php?id='.$id,['action'=>'confirm','csrf_token'=>'x'])[0]===404,'other student POST rejected');
    $http('logout.php');
    $http('login.php',['username'=>'test3','password'=>$password]);
    check($http('student/request-detail.php?id='.$id)[0]===302,'technician cannot confirm as student');
    $http('logout.php');
    $http('login.php',['username'=>'test1','password'=>$password]);
    foreach(['ROOM','AREA'] as $location) {
        $body=$http('student/create-request.php')[1];
        check($http('student/create-request.php',['csrf_token'=>$token($body),'location_type'=>$location,
            'area_id'=>1,'category_id'=>1,'title'=>'Regression '.$location,'description'=>'No image','priority'=>'HIGH'])[0]===302,'create '.$location.' without image');
        $created=(int)$pdo->query('SELECT MAX(request_id) FROM repair_request')->fetchColumn();
        $r=$pdo->query("SELECT * FROM repair_request WHERE request_id=$created")->fetch();
        check($r['status']==='PENDING' && $r['priority']==='MEDIUM' && $r['image_path']===null,'server defaults preserved');
        $body=$http('student/request-detail.php?id='.$created)[1];
        check(!str_contains($body,'value="confirm"'),'pending no confirmation button');
        check($http('student/request-detail.php?id='.$created,['csrf_token'=>$token($body)])[0]===302,'cancel pending');
        check($pdo->query("SELECT status FROM repair_request WHERE request_id=$created")->fetchColumn()==='CANCELLED','cancel persisted');
    }
    $body=$http('student/my-requests.php?search=Regression&status=CANCELLED')[1];
    check(str_contains($body,'Regression ROOM') && str_contains($body,'Regression AREA') && !str_contains($body,'Fixture'),'search and status filters');
    check($http('logout.php')[0]===302 && $http('student/my-requests.php')[0]===302,'logout regression');
    echo "All executed checks passed. Browser visual QA NOT RUN.\n";
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    if (is_file(__DIR__.'/../'.$photo)) { unlink(__DIR__.'/../'.$photo); }
}

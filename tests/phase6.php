<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/phase5.php';
require_once __DIR__.'/../includes/repair-technician.php';
function techFixture(PDO $pdo,string $status='PENDING',int $owner=1): int {
    $id=adminFixture($pdo,$status,$owner);
    $pdo->prepare('INSERT INTO repair_assignment (request_id,technician_user_id,assigned_by_user_id,note) VALUES (?,3,4,?)')->execute([$id,'Assigned technician test']);
    return $id;
}
function completionFiles(): array { $files=glob(__DIR__.'/../uploads/completions/*') ?: []; sort($files); return $files; }
$id=techFixture($pdo);
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,5,'start'),'unassigned technician start');
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,4,'start'),'admin cannot start as technician');
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,6,'start'),'inactive technician');
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,3,'progress','early'),'progress before start');
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,3,'submit'),'submission before start');
technicianChangeRequest($pdo,$id,3,'start');
check($pdo->query("SELECT status FROM repair_request WHERE request_id=$id")->fetchColumn()==='IN_PROGRESS','technician starts assigned job');
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,3,'start'),'start twice');
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,3,'progress','  '),'empty progress');
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,3,'progress',str_repeat('ก',2001)),'overlong progress');
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,3,'complete'),'technician cannot close directly');
technicianChangeRequest($pdo,$id,3,'progress','<progress>');
$last=$pdo->query("SELECT * FROM repair_history WHERE request_id=$id ORDER BY history_id DESC LIMIT 1")->fetch();
check($last['old_status']==='IN_PROGRESS' && $last['new_status']==='IN_PROGRESS' && (int)$last['changed_by_user_id']===3,'progress preserves state with actor');
foreach(['CANCELLED','COMPLETED'] as $state){$bad=techFixture($pdo,$state);adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$bad,3,'start'),$state.' job');}
$stale=techFixture($pdo);adminChangeRequest($pdo,$stale,4,'assign',['technician_user_id'=>5]);
adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$stale,3,'start'),'old assignment no longer authorized');
foreach([['start','start'],['start','cancel'],['start','reassign']] as $actions){
    $race=techFixture($pdo);$pdo->beginTransaction();$pdo->query("SELECT request_id FROM repair_request WHERE request_id=$race FOR UPDATE")->fetch();$workers=[];
    foreach($actions as $act){$p=proc_open([PHP_BINARY,__DIR__.'/technician-worker.php',$db,(string)$race,$act],[['pipe','r'],['pipe','w'],['pipe','w']],$wp);$workers[]=[$p,$wp];}
    usleep(200000);$pdo->commit();$results=[];
    foreach($workers as [$p,$wp]){fclose($wp[0]);$results[]=stream_get_contents($wp[1]);$err=stream_get_contents($wp[2]);fclose($wp[1]);fclose($wp[2]);check(proc_close($p)===0 && $err==='','technician race worker clean');}
    check(count(array_filter($results,fn($r)=>$r!=='rejected'))===1,'serialized '.implode('/',$actions).' permits one conflicting action');
    $r=$pdo->query("SELECT status FROM repair_request WHERE request_id=$race")->fetchColumn();
    if(in_array('started',$results,true)){check($r==='IN_PROGRESS' && (int)$pdo->query("SELECT technician_user_id FROM repair_assignment WHERE request_id=$race AND is_active=1")->fetchColumn()===3,'started job retains technician');}
}
$server=proc_open([PHP_BINARY,'-d','session.save_path='.__DIR__.'/sessions','-d','upload_max_filesize=6M','-d','post_max_size=8M','-S','127.0.0.1:'.$port,__DIR__.'/router.php'],
    [['pipe','r'],['file',__DIR__.'/server.log','a'],['file',__DIR__.'/server.log','a']],$pipes,dirname(__DIR__));
$server2=null;$createdFiles=[];$tempFiles=[];
$upload=function(int $job,array $fields,?string $bytes=null,string $filename='photo.png',?string $sessionCookie=null,?int $targetPort=null) use(&$cookie,$port,&$tempFiles): array {
    $curl=curl_init('http://127.0.0.1:'.($targetPort??$port).'/fixit/technician/job-detail.php?id='.$job);
    if($bytes!==null){$tmp=tempnam(sys_get_temp_dir(),'fixit_img_');$tempFiles[]=$tmp;file_put_contents($tmp,$bytes);$fields['completion_photo']=new CURLFile($tmp,'application/octet-stream',$filename);}
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$fields,CURLOPT_COOKIE=>$sessionCookie??$cookie,CURLOPT_TIMEOUT=>20]);
    $body=curl_exec($curl);$code=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);return [$code,$body];
};
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
try{
    for($i=0;$i<30;$i++){$sock=@fsockopen('127.0.0.1',$port);if($sock){fclose($sock);break;}usleep(100000);}
    $routes=['technician/dashboard.php','technician/my-jobs.php','technician/job-detail.php?id='.$id];
    foreach([null,1,4] as $actor){$cookie='';if($actor){$http('login.php',['username'=>'test'.$actor,'password'=>$password]);}
        foreach($routes as $route){check($http($route)[0]===302 && $http($route,['action'=>'start'])[0]===302,'non-technician GET/POST blocked '.$route);}$http('logout.php');}
    $http('login.php',['username'=>'test3','password'=>$password]);
    foreach($routes as $route){check($http($route)[0]===200,'assigned technician GET '.$route);}
    check($http('technician/job-detail.php?id='.$stale)[0]===404 && $http('technician/job-detail.php?id='.$stale,['action'=>'start'])[0]===404,'reassigned job GET/POST denied');
    check(!str_contains($http('technician/my-jobs.php')[1],'job-detail.php?id='.$stale),'reassigned job absent from list');
    $body=$http('technician/job-detail.php?id='.$id)[1];$csrf=$token($body);$nonce=$token($body,'action_token');
    check(str_contains($body,'&lt;progress&gt;'),'progress output escaped');
    check($http('technician/job-detail.php?id='.$id,['action'=>'progress','note'=>'x','csrf_token'=>'bad','action_token'=>$nonce])[0]===403,'technician CSRF rejected');
    $fields=['action'=>'progress','note'=>'HTTP progress','csrf_token'=>$csrf,'action_token'=>$nonce];
    check($http('technician/job-detail.php?id='.$id,$fields)[0]===302,'HTTP progress saves');
    $before=adminSnapshot($pdo);check($http('technician/job-detail.php?id='.$id,$fields)[0]===409 && $before===adminSnapshot($pdo),'progress form replay rejected');
    $body=$http('technician/job-detail.php?id='.$id)[1];$fields=['action'=>'submit','csrf_token'=>$token($body),'action_token'=>$token($body,'action_token'),'note'=>'Done'];
    foreach([[null,'photo.png'],['not an image','photo.png'],[$png,'photo.php'],[$png,'photo.jpg'],[$png.str_repeat('x',5*1024*1024),'photo.png']] as [$bytes,$filename]){
        $before=adminSnapshot($pdo);$beforeFiles=completionFiles();
        check($upload($id,$fields,$bytes,$filename)[0]===409 && $before===adminSnapshot($pdo) && $beforeFiles===completionFiles(),'invalid/missing/oversize upload rejected '.$filename);
    }
    $pdo->exec("CREATE TRIGGER tech_history_failure BEFORE INSERT ON repair_history FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='test rollback'");
    $before=adminSnapshot($pdo);$beforeFiles=completionFiles();
    check($upload($id,$fields,$png)[0]===500 && $before===adminSnapshot($pdo) && $beforeFiles===completionFiles(),'submission rollback removes only new photo');
    $pdo->exec('DROP TRIGGER tech_history_failure');
    check($upload($id,$fields,$png,'PHOTO.PNG')[0]===302,'valid PNG submission through HTTP');
    $c=repairCompletion($pdo,$id);$createdFiles[]=$c['completion_photo_path'];
    check(completionPhotoUrl($c['completion_photo_path'])!==null && (int)$c['submitted_by_user_id']===3 && $c['confirmed_at']===null && $c['confirmed_by_user_id']===null
        && $pdo->query("SELECT status FROM repair_request WHERE request_id=$id")->fetchColumn()==='IN_PROGRESS'
        && $pdo->query("SELECT completed_at FROM repair_request WHERE request_id=$id")->fetchColumn()===null,'submission stores real evidence without closing job');
    check(!str_contains($http('technician/job-detail.php?id='.$id)[1],'value="submit"'),'submission makes technician form read-only');
    $before=adminSnapshot($pdo);$beforeFiles=completionFiles();
    check($upload($id,$fields,$png)[0]===409 && $before===adminSnapshot($pdo) && $beforeFiles===completionFiles(),'duplicate upload leaves no new photo or history');
    adminRejected($pdo,fn()=>technicianChangeRequest($pdo,$id,3,'progress','late'),'progress after evidence');
    check(str_contains($http('technician/my-jobs.php?status=WAITING')[1],'job-detail.php?id='.$id),'waiting filter includes submitted job');
    // Workflow ทุก Role ใช้ endpoint จริงตั้งแต่สร้างคำร้อง
    $http('logout.php');$http('login.php',['username'=>'test1','password'=>$password]);
    $body=$http('student/request-detail.php?id='.$id)[1];
    check(str_contains($body,'หลักฐานหลังซ่อม') && str_contains($body,$c['completion_photo_path']),'student sees actual uploaded evidence');
    check($http('student/request-detail.php?id='.$id,['action'=>'confirm','csrf_token'=>$token($body)])[0]===302,'student confirms technician upload');
    $body=$http('student/create-request.php')[1];
    check($http('student/create-request.php',['location_type'=>'ROOM','category_id'=>1,'title'=>'Phase6 full workflow','description'=>'Integration','csrf_token'=>$token($body)])[0]===302,'workflow student creates request');
    $flow=(int)$pdo->query('SELECT MAX(request_id) FROM repair_request')->fetchColumn();
    $http('logout.php');$http('login.php',['username'=>'test4','password'=>$password]);$body=$http('admin/request-detail.php?id='.$flow)[1];
    check($http('admin/request-detail.php?id='.$flow,['action'=>'assign','technician_user_id'=>3,'csrf_token'=>$token($body)])[0]===302,'workflow admin assigns');
    $http('logout.php');$http('login.php',['username'=>'test3','password'=>$password]);$body=$http('technician/job-detail.php?id='.$flow)[1];
    $start=['action'=>'start','csrf_token'=>$token($body),'action_token'=>$token($body,'action_token')];
    check($http('technician/job-detail.php?id='.$flow,$start)[0]===302,'workflow technician starts');
    $body=$http('technician/job-detail.php?id='.$flow)[1];
    check($http('technician/job-detail.php?id='.$flow,['action'=>'progress','note'=>'Flow progress','csrf_token'=>$token($body),'action_token'=>$token($body,'action_token')])[0]===302,'workflow technician records progress');
    $body=$http('technician/job-detail.php?id='.$flow)[1];
    check($upload($flow,['action'=>'submit','note'=>'Flow finished','csrf_token'=>$token($body),'action_token'=>$token($body,'action_token')],$png)[0]===302,'workflow technician submits photo');
    $createdFiles[]=repairCompletion($pdo,$flow)['completion_photo_path'];
    $http('logout.php');$http('login.php',['username'=>'test1','password'=>$password]);$body=$http('student/request-detail.php?id='.$flow)[1];
    check($http('student/request-detail.php?id='.$flow,['action'=>'confirm','csrf_token'=>$token($body)])[0]===302,'workflow student confirms');
    foreach([['test1','student/request-detail.php'],['test3','technician/job-detail.php'],['test4','admin/request-detail.php']] as [$username,$route]){
        $http('logout.php');$http('login.php',['username'=>$username,'password'=>$password]);check(str_contains($http($route.'?id='.$flow)[1],'เสร็จสิ้น'),'all roles see completed '.$username);
    }
    check($pdo->query("SELECT status FROM repair_request WHERE request_id=$flow")->fetchColumn()==='COMPLETED'
        && (int)$pdo->query("SELECT COUNT(*) FROM repair_history WHERE request_id=$flow")->fetchColumn()===6,'full workflow history has create assign start progress submit confirm');

    // สอง HTTP servers + สอง sessions ของช่างเดียวกัน แข่งที่ parent row lock
    $sock=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);$port2=(int)substr(strrchr(stream_socket_get_name($sock,false),':'),1);fclose($sock);
    $server2=proc_open([PHP_BINARY,'-d','session.save_path='.__DIR__.'/sessions','-d','upload_max_filesize=6M','-d','post_max_size=8M','-S','127.0.0.1:'.$port2,__DIR__.'/router.php'],
        [['pipe','r'],['file',__DIR__.'/server.log','a'],['file',__DIR__.'/server.log','a']],$p2,dirname(__DIR__));
    for($i=0;$i<30;$i++){$sock=@fsockopen('127.0.0.1',$port2);if($sock){fclose($sock);break;}usleep(100000);}
    foreach([['submit','submit'],['submit','progress']] as $actions){
        $race=techFixture($pdo,'IN_PROGRESS');$handles=[];$multi=curl_multi_init();
        foreach($actions as $n=>$act){
            $cookie='';$http('login.php',['username'=>'test3','password'=>$password]);$body=$http('technician/job-detail.php?id='.$race)[1];
            $data=['action'=>$act,'note'=>'Concurrent '.$act,'csrf_token'=>$token($body),'action_token'=>$token($body,'action_token')];
            if($act==='submit'){$tmp=tempnam(sys_get_temp_dir(),'fixit_img_');$tempFiles[]=$tmp;file_put_contents($tmp,$png);$data['completion_photo']=new CURLFile($tmp,'image/png','race.png');}
            $curl=curl_init('http://127.0.0.1:'.($n===0?$port:$port2).'/fixit/technician/job-detail.php?id='.$race);
            curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$data,CURLOPT_COOKIE=>$cookie,CURLOPT_TIMEOUT=>20]);
            curl_multi_add_handle($multi,$curl);$handles[]=$curl;
        }
        $beforeFiles=completionFiles();$pdo->beginTransaction();$pdo->query("SELECT request_id FROM repair_request WHERE request_id=$race FOR UPDATE")->fetch();
        curl_multi_exec($multi,$running);usleep(300000);$pdo->commit();
        do{curl_multi_exec($multi,$running);if($running){curl_multi_select($multi,0.1);}}while($running);
        $codes=[];foreach($handles as $curl){$codes[]=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_multi_remove_handle($multi,$curl);curl_close($curl);}curl_multi_close($multi);
        $c=repairCompletion($pdo,$race);if($c){$createdFiles[]=$c['completion_photo_path'];}
        if($actions[1]==='submit'){sort($codes);check($codes===[302,409],'concurrent submissions accept exactly one');}
        else {check($codes[0]===302 && in_array($codes[1],[302,409],true),'submit/progress race respects read-only submission');}
        check($c && count(array_diff(completionFiles(),$beforeFiles))===1
            && (int)$pdo->query("SELECT COUNT(*) FROM repair_history WHERE request_id=$race AND note LIKE 'ช่างส่งรูปหลักฐาน%'")->fetchColumn()===1
            && $pdo->query("SELECT status FROM repair_request WHERE request_id=$race")->fetchColumn()==='IN_PROGRESS','concurrent upload stores one photo and one submission history');
    }
    foreach(require __DIR__.'/upload-fixtures.php' as $type=>$bytes){
        $job=techFixture($pdo,'IN_PROGRESS');$body=$http('technician/job-detail.php?id='.$job)[1];
        check($upload($job,['action'=>'submit','csrf_token'=>$token($body),'action_token'=>$token($body,'action_token')],$bytes,'proof.'.$type)[0]===302,'valid '.$type.' submission');
        $c=repairCompletion($pdo,$job);if($c){$createdFiles[]=$c['completion_photo_path'];}
    }
    // Pagination และตัวกรองทำงานบน ownership scope เดียวกัน
    for($i=1;$i<=30;$i++){
        $pageJob=techFixture($pdo);
        $pdo->prepare('UPDATE repair_request SET title=? WHERE request_id=?')->execute(['Tech pagination '.$i,$pageJob]);
    }
    $cookie='';$http('login.php',['username'=>'test3','password'=>$password]);
    $page1=$http('technician/my-jobs.php?search=Tech+pagination&priority=MEDIUM&category_id=1')[1];
    $page2=$http('technician/my-jobs.php?search=Tech+pagination&priority=MEDIUM&category_id=1&page=2')[1];
    check(preg_match_all('~href="job-detail.php\?id=~',$page1)===25 && preg_match_all('~href="job-detail.php\?id=~',$page2)===5,'pagination returns 25 plus 5 owned jobs');
    check(str_contains($page1,'page=2') && str_contains($page2,'search=Tech+pagination'),'pagination retains search and filters');
    check(str_contains($http('technician/my-jobs.php?search=not-present-unique')[1],'ไม่พบงานที่ได้รับมอบหมายตามเงื่อนไข'),'technician list empty state');
    check($http('technician/my-jobs.php?search[]=x&priority[]=x&page[]=x')[0]===200
        && $http('technician/job-detail.php?id[]=1')[0]===400,'malformed technician inputs handled');
    $rollbackStart=techFixture($pdo);$rollbackNote=techFixture($pdo,'IN_PROGRESS');
    $pdo->exec("CREATE TRIGGER tech_action_failure BEFORE INSERT ON repair_history FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='test rollback'");
    foreach([[$rollbackStart,'start'],[$rollbackNote,'progress']] as [$job,$act]){
        $before=adminSnapshot($pdo);
        try{technicianChangeRequest($pdo,$job,3,$act,'rollback');check(false,'must reject');}
        catch(PDOException $ex){check(($ex->errorInfo[0]??'')==='45000' && $before===adminSnapshot($pdo),$act.' rollback preserves request and history');}
    }
    $pdo->exec('DROP TRIGGER tech_action_failure');
    echo "Phase 6 HTTP/upload checks passed. Desktop Browser UI NOT RUN by this runner.\n";
} finally{
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    if(is_resource($server2)){proc_terminate($server2);proc_close($server2);}
    foreach($createdFiles as $path){if(is_file(__DIR__.'/../'.$path)){unlink(__DIR__.'/../'.$path);}}
    foreach($tempFiles as $path){if(is_file($path)){unlink($path);}}
}

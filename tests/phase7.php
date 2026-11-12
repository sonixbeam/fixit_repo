<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/phase6.php';
require_once __DIR__.'/../includes/repair-reports.php';
$pdo->prepare('INSERT INTO repair_category (category_id,category_name) VALUES (2,?)')->execute(['<Report category>']);
function reportFixture(PDO $pdo,string $at,string $status='PENDING',string $priority='MEDIUM',int $category=1): int {
    $pdo->prepare('INSERT INTO repair_request (user_id,room_id,category_id,title,description,status,priority,created_at) VALUES (1,1,?,?,?,?,?,?)')
        ->execute([$category,'<Report fixture>','Report test',$status,$priority,$at]);
    return (int)$pdo->lastInsertId();
}
function reportAssignment(PDO $pdo,int $job,int $tech,int $active=1): void {
    $pdo->prepare("INSERT INTO repair_assignment (request_id,technician_user_id,assigned_by_user_id,is_active,assigned_at) VALUES (?,?,4,?,'2024-02-28 00:00:00')")->execute([$job,$tech,$active]);
}
function reportCompletionFixture(PDO $pdo,int $job,bool $confirmed=false): void {
    $pdo->prepare("INSERT INTO repair_completion (request_id,submitted_by_user_id,completion_photo_path,submitted_at,confirmed_at,confirmed_by_user_id)
        VALUES (?,3,'uploads/completions/report_test.png','2024-03-01 10:00:00',?,?)")
        ->execute([$job,$confirmed?'2024-03-02 10:00:00':null,$confirmed?1:null]);
    if($confirmed){$pdo->prepare("UPDATE repair_request SET completed_at='2024-03-02 10:00:00' WHERE request_id=?")->execute([$job]);}
}
$a=reportFixture($pdo,'2024-02-28 00:00:00','PENDING','LOW');
$b=reportFixture($pdo,'2024-02-29 23:59:59','IN_PROGRESS','HIGH');reportAssignment($pdo,$b,3);reportCompletionFixture($pdo,$b);
$c=reportFixture($pdo,'2024-02-29 12:00:00','COMPLETED','MEDIUM',2);reportAssignment($pdo,$c,3,0);reportAssignment($pdo,$c,5);reportCompletionFixture($pdo,$c,true);
$d=reportFixture($pdo,'2024-02-28 12:00:00','CANCELLED','LOW',2);
$e=reportFixture($pdo,'2024-02-29 00:00:00','IN_PROGRESS','MEDIUM',2);reportAssignment($pdo,$e,3);reportAssignment($pdo,$e,3);reportAssignment($pdo,$e,5);
for($i=0;$i<10;$i++){$pdo->prepare("INSERT INTO repair_history (request_id,changed_by_user_id,old_status,new_status,note) VALUES (?,3,'IN_PROGRESS','IN_PROGRESS','Report test history')")->execute([$e]);}
reportFixture($pdo,'2024-02-27 23:59:59');reportFixture($pdo,'2024-03-01 00:00:00');
$window=['date_from'=>'2024-02-28','date_to'=>'2024-02-29'];
$before=adminSnapshot($pdo);$r=repairReport($pdo,reportFilters($window));
check($r['total']===5 && $r['statuses']===['PENDING'=>1,'IN_PROGRESS'=>2,'COMPLETED'=>1,'CANCELLED'=>1],'known report total and four statuses');
check($r['priorities']===['LOW'=>2,'MEDIUM'=>2,'HIGH'=>1],'known priority totals');
check(array_column($r['categories'],'total','category_id')===[2=>3,1=>2],'known category totals');
check($r['waiting']===1 && $r['unassigned']===1 && $r['waiting']<=$r['statuses']['IN_PROGRESS'],'waiting is subset and unassigned excludes terminal');
check(count($r['rows'])===5 && array_sum($r['statuses'])===$r['total'] && array_sum($r['priorities'])===$r['total']
    && array_sum(array_column($r['categories'],'total'))===$r['total'],'all summaries reconcile with details');
check((int)$r['technicians'][3]['assigned']===2 && (int)$r['technicians'][3]['submitted']===2 && (int)$r['technicians'][3]['confirmed']===1,'actual submitter attribution survives reassignment');
check((int)$r['technicians'][5]['assigned']===2 && (int)$r['technicians'][5]['submitted']===0,'new assignee not credited for old submissions');
check($r['anomalies']['duplicate_assignment']===1 && $r['anomalies']['submission_mismatch']===1,'data anomalies surfaced without inflating total');
check($before===adminSnapshot($pdo) && !$pdo->inTransaction(),'report leaves data unchanged and closes read transaction');
check($pdo->query('SELECT @@session.time_zone')->fetchColumn()==='+07:00','report explicitly uses Thai session timezone');
$pdo->exec("SET SESSION time_zone='+00:00'");
check(repairReport($pdo,reportFilters($window))['total']===5,'Thai date boundaries independent of prior DB timezone');
check((int)$r['rows'][0]['request_id']===$b && (int)$r['rows'][4]['request_id']===$a,'details ordered by actual date and id');
foreach([
    ['status'=>'WAITING','total'=>1],['status'=>'COMPLETED','total'=>1],['priority'=>'HIGH','total'=>1],
    ['category_id'=>'2','total'=>3],['technician'=>'3','total'=>3],['technician'=>'5','total'=>2],['technician'=>'999999','total'=>0]
] as $case){$expected=$case['total'];unset($case['total']);$result=repairReport($pdo,reportFilters($case+$window));check($result['total']===$expected,'report filter '.json_encode($case));}
$filtered=repairReport($pdo,reportFilters(['technician'=>'3']+$window));
check(array_keys($filtered['technicians'])===[3],'selected technician table includes selected actor only');
check(repairReport($pdo,reportFilters(['date_from'=>'2024-02-29','date_to'=>'2024-02-29']))['total']===3,'leap day includes midnight and last second');
check(repairReport($pdo,reportFilters(['date_from'=>'2024-03-01','date_to'=>'2024-03-01']))['total']===1,'next month boundary excludes previous day');
reportFixture($pdo,'2023-12-31 23:59:59');reportFixture($pdo,'2024-01-01 00:00:00');
check(repairReport($pdo,reportFilters(['date_from'=>'2023-12-31','date_to'=>'2023-12-31']))['total']===1
    && repairReport($pdo,reportFilters(['date_from'=>'2024-01-01','date_to'=>'2024-01-01']))['total']===1,'year boundary inclusive dates');
$empty=repairReport($pdo,reportFilters(['date_from'=>'2030-01-01','date_to'=>'2030-01-01']));
check($empty['total']===0 && $empty['waiting']===0 && !$empty['rows'] && !$empty['categories'] && !$empty['technicians'],'empty report has real zeros');
foreach([
    ['date_from'=>'2023-02-29'],['date_to'=>'2024-02-30'],['date_from'=>'2024-2-01'],['date_from'=>'2024-03-01','date_to'=>'2024-02-01'],
    ['date_to'=>'9999-12-31'],['date_from'=>['x']],['status'=>'SQL'],['priority'=>'CRITICAL'],['category_id'=>'1 OR 1=1'],['technician'=>'-1'],['page'=>'0'],['page'=>['x']]
] as $invalid){try{reportFilters($invalid);check(false,'invalid filters must reject');}catch(DomainException $ex){check(true,'invalid report filter rejected');}}
$legacy=reportFixture($pdo,'2025-01-01 00:00:00','COMPLETED');
$partial=reportFixture($pdo,'2025-01-01 01:00:00','IN_PROGRESS');reportAssignment($pdo,$partial,3);reportCompletionFixture($pdo,$partial);
$pdo->prepare('UPDATE repair_completion SET confirmed_by_user_id=1 WHERE request_id=?')->execute([$partial]);
$legacyReport=repairReport($pdo,reportFilters(['date_from'=>'2025-01-01','date_to'=>'2025-01-01']));
check($legacyReport['anomalies']['completed_without_confirmation']===1 && $legacyReport['anomalies']['confirmation_mismatch']===1,'legacy and partial confirmation detected without backfilling');
$badTime=reportFixture($pdo,'2025-01-03 00:00:00','COMPLETED');reportAssignment($pdo,$badTime,3);reportCompletionFixture($pdo,$badTime,true);
$pdo->prepare("UPDATE repair_completion SET submitted_at='2025-01-03 11:00:00',confirmed_at='2025-01-03 10:00:00' WHERE request_id=?")->execute([$badTime]);
$pdo->prepare("UPDATE repair_request SET completed_at='2025-01-03 12:00:00' WHERE request_id=?")->execute([$badTime]);
check(repairReport($pdo,reportFilters(['date_from'=>'2025-01-03','date_to'=>'2025-01-03']))['anomalies']['confirmation_mismatch']===1,'confirmation before submission detected');
for($i=1;$i<=30;$i++){reportFixture($pdo,'2022-01-01 12:00:00');}
$pagination=['date_from'=>'2022-01-01','date_to'=>'2022-01-01'];
$page1=repairReport($pdo,reportFilters($pagination));$page2=repairReport($pdo,reportFilters(['page'=>'2']+$pagination));
check($page1['total']===30 && count($page1['rows'])===25 && count($page2['rows'])===5 && $page1['statuses']===$page2['statuses'],'pagination never changes aggregate totals');
check(!array_intersect(array_column($page1['rows'],'request_id'),array_column($page2['rows'],'request_id')),'same timestamp pagination deterministic without duplicates');
check(repairReport($pdo,reportFilters(['page'=>'999']+$pagination))['page']===2,'out of range page clamped');
// ตรวจคำร้อง workflow ที่ผ่าน endpoints จริงจาก Phase 6 (ไม่ใช่ fixture ผิดปกติข้างบน)
$flowCompletion=repairCompletion($pdo,$flow);
check($flowCompletion && (int)$flowCompletion['confirmed_by_user_id']===1 && $flowCompletion['confirmed_at']!==null
    && $pdo->query("SELECT completed_at IS NOT NULL FROM repair_request WHERE request_id=$flow")->fetchColumn()==1
    && (int)$pdo->query("SELECT COUNT(*) FROM repair_history WHERE request_id=$flow AND new_status='COMPLETED'")->fetchColumn()===1,'real workflow confirmation and audit remain consistent');
check($flowCompletion['submitted_at']<=$flowCompletion['confirmed_at']
    && $pdo->query("SELECT completed_at FROM repair_request WHERE request_id=$flow")->fetchColumn()>=$flowCompletion['confirmed_at']
    && (int)$pdo->query("SELECT changed_by_user_id FROM repair_history WHERE request_id=$flow AND new_status='COMPLETED'")->fetchColumn()===1,'real workflow times and confirmation actor consistent');
// HTTP read-only role/filters/output validation ไม่มี Browser automation
$server=proc_open([PHP_BINARY,'-d','session.save_path='.__DIR__.'/sessions','-S','127.0.0.1:'.$port,__DIR__.'/router.php'],
    [['pipe','r'],['file',__DIR__.'/server.log','a'],['file',__DIR__.'/server.log','a']],$pipes,dirname(__DIR__));
try{
    for($i=0;$i<30;$i++){$sock=@fsockopen('127.0.0.1',$port);if($sock){fclose($sock);break;}usleep(100000);}
    $route='admin/reports.php?'.http_build_query($window);
    foreach([null,1,3] as $actor){$cookie='';if($actor){$http('login.php',['username'=>'test'.$actor,'password'=>$password]);}
        check($http($route)[0]===302 && $http($route,['status'=>'COMPLETED'])[0]===302,'report non-admin GET/POST blocked '.($actor??'anonymous'));$http('logout.php');}
    $http('login.php',['username'=>'test4','password'=>$password]);$before=adminSnapshot($pdo);
    [$code,$body]=$http($route);
    check($code===200 && str_contains($body,'id="report-total">5</strong>'),'HTTP report known total');
    check(str_contains($body,'&lt;Report fixture&gt;') && str_contains($body,'&lt;Report category&gt;') && !str_contains($body,'<Report fixture>'),'report escapes title and category');
    check(str_contains($body,'พบข้อมูลที่ควรตรวจสอบ'),'report warns about inconsistent data');
    check($http($route,['status'=>'COMPLETED'])[0]===405,'report POST explicitly read-only');
    foreach(['date_from=2024-02-30','date_from[]=x','status=INVALID','technician=1%20OR%201=1','page[]=x'] as $query){[$code,$html]=$http('admin/reports.php?'.$query);check($code===400 && !str_contains($html,'SQLSTATE'),'HTTP invalid report filter safely rejected');}
    $html=$http('admin/reports.php?date_from=2030-01-01&date_to=2030-01-01')[1];
    check(str_contains($html,'ไม่พบคำร้องตามตัวกรอง') && str_contains($html,'id="report-total">0</strong>'),'HTTP empty report');
    $html=$http('admin/reports.php?'.http_build_query(['page'=>'2']+$pagination))[1];
    check(preg_match_all('~href="request-detail.php\?id=~',$html)===5 && str_contains($html,'date_from=2022-01-01&amp;date_to=2022-01-01'),'HTTP report pagination retains dates');
    check(str_contains($http('admin/dashboard.php')[1],'href="reports.php"'),'dashboard and navigation link to reports');
    check($before===adminSnapshot($pdo),'all report HTTP reads and rejected POST leave data unchanged');
    echo "Phase 7 checks passed. Desktop UI pending user verification; no Browser automation.\n";
}finally{if(is_resource($server)){proc_terminate($server);proc_close($server);}}

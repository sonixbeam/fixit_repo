<?php
// ใช้เฉพาะ PHP development server ที่เริ่มจาก regression runner
if (PHP_SAPI !== 'cli-server' || !preg_match('/^fixit_test_[a-f0-9]{12}$/', getenv('FIXIT_TEST_DB') ?: '')) {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/database.php';
$pdo->exec('USE `' . getenv('FIXIT_TEST_DB') . '`');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routes = ['login.php', 'logout.php', 'student/dashboard.php', 'student/create-request.php',
    'student/my-requests.php', 'student/request-detail.php', 'admin/dashboard.php', 'admin/requests.php', 'admin/request-detail.php', 'admin/rooms.php', 'technician/dashboard.php', 'technician/my-jobs.php', 'technician/job-detail.php'];
$routes[]='admin/reports.php';
$route = preg_replace('~^/fixit/~', '', $path);
if ($route === 'assets/css/style.css' && $_SERVER['REQUEST_METHOD']==='GET') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__.'/../assets/css/style.css');
    exit;
}
if ($_SERVER['REQUEST_METHOD']==='GET' && preg_match('~^uploads/(repairs|completions)/[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$~i',$route)) {
    $file=__DIR__.'/../'.$route;
    $info=is_file($file) ? @getimagesize($file) : false;
    if (!$info) { http_response_code(404); exit; }
    header('Content-Type: '.$info['mime']);
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}
if (!in_array($route, $routes, true)) {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../' . $route;

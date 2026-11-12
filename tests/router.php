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
    'student/my-requests.php', 'student/request-detail.php', 'admin/dashboard.php', 'technician/dashboard.php'];
$route = preg_replace('~^/fixit/~', '', $path);
if (!in_array($route, $routes, true)) {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../' . $route;

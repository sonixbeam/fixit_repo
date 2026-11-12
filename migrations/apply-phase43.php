<?php
// CLI เท่านั้น ใช้การเชื่อมต่อเดิมโดยไม่คัดลอกรหัสผ่าน
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
foreach (['repair_request'=>'request_id','user'=>'user_id'] as $table=>$column) {
    $stmt=$pdo->prepare('SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $stmt->execute([$table,$column]);
    if (strtolower($stmt->fetchColumn() ?: '') !== 'int unsigned') {
        exit("STOP: incompatible key type; inspect SHOW CREATE TABLE first.\n");
    }
}
$existing=$pdo->query("SHOW TABLES LIKE 'repair_completion'")->fetchColumn();
if ($existing) {
    echo "repair_completion already exists. No changes made; inspect SHOW CREATE TABLE repair_completion before upgrading.\n";
    exit;
}
$pdo->exec(file_get_contents(__DIR__.'/20261113_repair_completion.sql'));
echo "Created repair_completion in " . $pdo->query('SELECT DATABASE()')->fetchColumn() . ". Existing data preserved.\n";

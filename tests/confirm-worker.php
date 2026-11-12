<?php
if (PHP_SAPI !== 'cli' || !preg_match('/^fixit_test_[a-f0-9]{12}$/', $argv[1] ?? '')) { exit(2); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/repair-completion.php';
$pdo->exec('USE `' . $argv[1] . '`');
try {
    confirmRepair($pdo, (int)$argv[2], 1);
    echo 'confirmed';
} catch (RuntimeException $ex) {
    echo 'rejected';
}

<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
function requireLogin(): array
{
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        header('Location: /fixit/login.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT user_id, username, role, is_active
         FROM `user`
         WHERE user_id = :user_id
         LIMIT 1"
    );

    $stmt->execute([
        'user_id' => $_SESSION['user_id']
    ]);

    $user = $stmt->fetch();

    // หากไม่พบบัญชี หรือบัญชีถูกปิดใช้งาน
    if (!$user || !$user['is_active']) {
        $_SESSION = [];
        session_destroy();

        header('Location: /fixit/login.php');
        exit;
    }

    return $user;
}

// ตรวจสอบสิทธิ์ตาม Role
function requireRole(string $requiredRole): array
{
    $user = requireLogin();

    if ($user['role'] !== $requiredRole) {
        header('Location: /fixit/login.php');
        exit;
    }

    return $user;
}

// ป้องกัน HTML Injection/XSS เมื่อแสดงข้อความ
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

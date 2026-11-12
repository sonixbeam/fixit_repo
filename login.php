
<?php
session_start();

require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอก Username และ Password';
    } else {
        $stmt = $pdo->prepare(
            "SELECT user_id, username, password, role, is_active
             FROM `user`
             WHERE username = :username
             LIMIT 1"
        );

        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_active']) {
                $error = 'บัญชีนี้ถูกระงับการใช้งาน';
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'STUDENT') {
                    header('Location: student/dashboard.php');
                } elseif ($user['role'] === 'TECHNICIAN') {
                    header('Location: technician/dashboard.php');
                } elseif ($user['role'] === 'ADMIN') {
                    header('Location: admin/dashboard.php');
                } else {
                    session_unset();
                    session_destroy();
                    $error = 'ไม่พบสิทธิ์การใช้งานของบัญชีนี้';
                }

                if ($error === '') {
                    exit;
                }
            }
        } else {
            $error = 'Username หรือ Password ไม่ถูกต้อง';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FixIT</title>
</head>
<body>

    <h1>FixIT</h1>
    <h2>ระบบแจ้งซ่อมหอพัก</h2>

    <form method="POST" action="">
        <div>
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                required
            >
        </div>

        <br>

        <div>
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <br>

        <button type="submit">เข้าสู่ระบบ</button>
    </form>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

</body>
</html>

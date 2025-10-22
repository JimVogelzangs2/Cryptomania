<?php
// Simple login system
ini_set('session.save_path', sys_get_temp_dir());
session_start();

$dbHost = 'localhost';
$dbName = 'cryptomania';
$dbUser = 'root';
$dbPass = '';

function db() {
    static $pdo = null;
    global $dbHost, $dbName, $dbUser, $dbPass;
    if ($pdo === null) {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        if ($action === 'register') {
            // Registration
            try {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
                $stmt->execute([$username, $passwordHash]);
                $_SESSION['user_id'] = db()->lastInsertId();
                $_SESSION['username'] = $username;
                header('Location: Cryptomania.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Username already exists';
            }
        } else {
            // Login
            $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                header('Location: Cryptomania.php');
                exit;
            }
            $error = 'Invalid username or password';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: Cryptomania.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Cryptomania</title>
    <link href="assets/cryptomania.css" rel="stylesheet">
    <style>
        .login-form { max-width: 300px; margin: 50px auto; padding: 20px; border: 1px solid #1f2937; border-radius: 8px; background: #0f172a; }
        .login-form input { width: 100%; padding: 8px; margin: 8px 0; border: 1px solid #1f2937; border-radius: 4px; background: #1f2937; color: #e2e8f0; }
        .login-form button { width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .login-form button:hover { background: #2563eb; }
        .error { color: #ef4444; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2 style="text-align: center; color: #e2e8f0;">Cryptomania Login</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <button type="submit" name="action" value="login" style="flex:1;">Login</button>
                    <button type="submit" name="action" value="register" style="flex:1;">Register</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
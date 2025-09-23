<?php
require_once 'config.php';
require_once 'api/Auth.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = sanitize_input($_POST['email'] ?? null);

    if (strlen($username) < 3 || strlen($password) < 6) {
        $message = 'Username must be at least 3 characters and password at least 6 characters.';
    } else {
        $res = Auth::register($username, $password, $email, 'admin'); // default to admin for first setup
        if ($res['success']) {
            header('Location: login.php');
            exit;
        } else {
            $message = 'Registration failed: ' . ($res['error'] ?? 'Unknown error');
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo APP_NAME; ?> - Register</title>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Register Admin</h2>
            <?php if ($message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" action="register.php">
                <label>Username</label>
                <input name="username" type="text" class="form-control" required>
                <label>Email (optional)</label>
                <input name="email" type="email" class="form-control">
                <label>Password</label>
                <input name="password" type="password" class="form-control" required>
                <div class="auth-actions">
                    <button class="btn btn-primary" type="submit">Register</button>
                </div>
            </form>
            <p class="text-muted small mt-2">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>
</html>

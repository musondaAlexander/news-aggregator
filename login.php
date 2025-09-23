<?php
require_once 'config.php';
require_once 'api/Auth.php';

// If already logged in, redirect to admin
if (Auth::check()) {
    header('Location: admin.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = sanitize_input($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    $res = Auth::login($user, $pass);
    if ($res['success']) {
        header('Location: admin.php');
        exit;
    } else {
        $message = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo APP_NAME; ?> - Admin Login</title>
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Admin Login</h2>
            <?php if ($message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <label>Username</label>
                <input name="username" type="text" class="form-control" required>
                <label>Password</label>
                <input name="password" type="password" class="form-control" required>
                <div class="auth-actions" style="display: flex; justify-content: center;">
                    <button class="btn btn-primary" type="submit" style="width: 100%;">Login</button>
                </div>
            </form>
            <span> Don't have an account?</span>
            <p class="mt-3"><a href="register.php" class="text-primary"> Register a new account</a></p>
        </div>
    </div>
</body>

</html>
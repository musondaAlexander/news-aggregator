<?php
require_once __DIR__ . '/../config.php';

class Auth
{
    public static function register($username, $password, $email = null, $role = 'user')
    {
        $db = Database::getConnection();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$username, $hash, $email, $role]);
            return ['success' => true, 'id' => $db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function login($username, $password)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session id for safety
            if (session_status() === PHP_SESSION_NONE) session_start();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return ['success' => true, 'user' => $user];
        }
        return ['success' => false, 'error' => 'Invalid credentials'];
    }

    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_unset();
        session_destroy();
        return true;
    }

    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return !empty($_SESSION['user_id']);
    }

    public static function user()
    {
        if (self::check()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'] ?? 'user'
            ];
        }
        return null;
    }
}

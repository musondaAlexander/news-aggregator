<?php

/**
 * News Aggregator Configuration File
 * Contains database connection and API settings
 */

// Start session for user preferences
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'news_aggregator');  // Name of the database

// News API Configuration
// 'apiKey' => "67f22648bef641a2bafffb36c62c20e3",
define('NEWS_API_KEY', '67f22648bef641a2bafffb36c62c20e3'); // Replace with your actual API key
define('NEWS_API_BASE_URL', 'https://newsapi.org/v2/');

// Application Configuration
define('APP_NAME', 'NewsHub Pro');
define('APP_VERSION', '1.0.0');
define('ARTICLES_PER_PAGE', 12);
define('CACHE_DURATION', 1800); // 30 minutes in seconds

// Admin credentials (change these in production)
define('ADMIN_USER', 'admin');
// Default password is 'admin1234' - update immediately. Stored as password_hash()
define('ADMIN_PASS_HASH', password_hash('changeme', PASSWORD_DEFAULT));

// Supported categories
$categories = [
    'general' => 'General',
    'business' => 'Business',
    'entertainment' => 'Entertainment',
    'health' => 'Health',
    'science' => 'Science',
    'sports' => 'Sports',
    'technology' => 'Technology'
];

// Supported countries
$countries = [
    'us' => 'United States',
    'gb' => 'United Kingdom',
    'ca' => 'Canada',
    'au' => 'Australia',
    'de' => 'Germany',
    'fr' => 'France',
    'jp' => 'Japan',
    'in' => 'India'
];

/**
 * Database Connection Class
 */
class Database
{
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }
}

/**
 * Utility Functions
 */
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_time_ago($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}

function truncate_text($text, $length = 150)
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// Error handling
function handleError($errno, $errstr, $errfile, $errline)
{
    error_log("Error: [$errno] $errstr in $errfile on line $errline");
    return true;
}

set_error_handler("handleError");

// Timezone setting
date_default_timezone_set('UTC');

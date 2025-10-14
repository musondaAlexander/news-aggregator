<?php
/**
 * Database Setup Script
 * Run this file ONCE to create all necessary tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
    h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
    .success { color: #4CAF50; padding: 10px; background: #e8f5e9; border-left: 4px solid #4CAF50; margin: 10px 0; }
    .error { color: #f44336; padding: 10px; background: #ffebee; border-left: 4px solid #f44336; margin: 10px 0; }
    .info { background: #e3f2fd; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

echo "<div class='container'>";
echo "<h1>🔧 Database Setup</h1>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

try {
    $db = Database::getConnection();
    echo "<div class='success'>✅ Database connection successful!</div>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Please check your database credentials in config.php and ensure MySQL is running.</div>";
    echo "</div>";
    exit;
}

echo "<hr>";
echo "<h2>Creating Tables...</h2>";

// Create users table
echo "<h3>1. Creating 'users' table...</h3>";
try {
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        role ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<div class='success'>✅ 'users' table created successfully!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error creating 'users' table: " . $e->getMessage() . "</div>";
}

// Create articles table
echo "<h3>2. Creating 'articles' table...</h3>";
try {
    $sql = "CREATE TABLE IF NOT EXISTS articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(500) NOT NULL,
        summary TEXT,
        content TEXT,
        url VARCHAR(1000) NOT NULL UNIQUE,
        image_url VARCHAR(1000),
        published_at DATETIME,
        scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        source_name VARCHAR(200),
        source_id VARCHAR(100),
        author VARCHAR(200),
        category VARCHAR(50) DEFAULT 'general',
        views INT DEFAULT 0,
        likes INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_published (published_at),
        INDEX idx_source (source_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<div class='success'>✅ 'articles' table created successfully!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error creating 'articles' table: " . $e->getMessage() . "</div>";
}

// Create article_views table
echo "<h3>3. Creating 'article_views' table...</h3>";
try {
    $sql = "CREATE TABLE IF NOT EXISTS article_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        article_id INT,
        view_count INT DEFAULT 1,
        last_viewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<div class='success'>✅ 'article_views' table created successfully!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error creating 'article_views' table: " . $e->getMessage() . "</div>";
}

// Create sources table
echo "<h3>4. Creating 'sources' table...</h3>";
try {
    $sql = "CREATE TABLE IF NOT EXISTS sources (
        id VARCHAR(100) PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        url VARCHAR(1000),
        category VARCHAR(50),
        language VARCHAR(10),
        country VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<div class='success'>✅ 'sources' table created successfully!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error creating 'sources' table: " . $e->getMessage() . "</div>";
}

// Create user_preferences table
echo "<h3>5. Creating 'user_preferences' table...</h3>";
try {
    $sql = "CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(255),
        preferred_categories JSON,
        preferred_sources JSON,
        preferred_country VARCHAR(10) DEFAULT 'us',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<div class='success'>✅ 'user_preferences' table created successfully!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error creating 'user_preferences' table: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h2>📊 Database Summary</h2>";

// Show all tables
try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='info'>";
    echo "<strong>Tables in database '" . DB_NAME . "':</strong><br>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>✓ $table</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Count records in each table
    echo "<h3>Table Statistics:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table</th><th>Record Count</th></tr>";
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        echo "<tr><td>$table</td><td>$count</td></tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Error getting table list: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h2>✅ Setup Complete!</h2>";
echo "<div class='success'>";
echo "<strong>Your database is ready to use!</strong><br><br>";
echo "You can now:<br>";
echo "• <a href='register.php'>Register a new user</a><br>";
echo "• <a href='login.php'>Login to your account</a><br>";
echo "• <a href='index.php'>Browse news articles</a><br>";
echo "• <a href='test_newsapi_class.php'>Test NewsAPI integration</a><br>";
echo "</div>";

echo "</div>";
?>

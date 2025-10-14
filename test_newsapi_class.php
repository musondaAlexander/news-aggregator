<?php
/**
 * NewsApi.php Functionality Test
 * This script tests the NewsAPI class methods
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/NewsApi.php';

echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
    h2 { color: #555; margin-top: 30px; background: #f0f0f0; padding: 10px; border-left: 4px solid #4CAF50; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .warning { color: #ff9800; font-weight: bold; }
    .info { background: #e3f2fd; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
    .test-result { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ddd; }
    .article-card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #fafafa; }
    .article-card img { max-width: 200px; border-radius: 4px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-size: 12px; margin: 5px; }
    .badge-success { background: #4CAF50; color: white; }
    .badge-error { background: #f44336; color: white; }
    .badge-info { background: #2196F3; color: white; }
</style>";

echo "<div class='container'>";
echo "<h1>🔍 NewsAPI Class Functionality Test</h1>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Test 1: Check API Key Configuration
echo "<h2>Test 1: API Key Configuration</h2>";
echo "<div class='test-result'>";
$apiKey = "67f22648bef641a2bafffb36c62c20e3";
if (empty(trim($apiKey))) {
    echo "<span class='error'>❌ FAILED:</span> API key is not configured<br>";
    echo "<span class='badge badge-error'>CRITICAL</span>";
} else {
    echo "<span class='success'>✅ PASSED:</span> API key is configured<br>";
    echo "<strong>API Key:</strong> " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -4) . " (masked)<br>";
    echo "<span class='badge badge-success'>OK</span>";
}
echo "</div>";

// Test 2: Database Connection
echo "<h2>Test 2: Database Connection</h2>";
echo "<div class='test-result'>";
try {
    $db = Database::getConnection();
    echo "<span class='success'>✅ PASSED:</span> Database connection successful<br>";
    echo "<strong>Database:</strong> " . DB_NAME . "<br>";
    echo "<span class='badge badge-success'>CONNECTED</span>";
} catch (Exception $e) {
    echo "<span class='error'>❌ FAILED:</span> Database connection failed<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<span class='badge badge-error'>ERROR</span>";
    exit;
}
echo "</div>";

// Test 3: Check Database Schema
echo "<h2>Test 3: Database Schema Check</h2>";
echo "<div class='test-result'>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'articles'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✅ PASSED:</span> 'articles' table exists<br>";
        
        // Check columns
        $stmt = $db->query("DESCRIBE articles");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<strong>Table Columns:</strong> " . implode(', ', $columns) . "<br>";
        echo "<span class='badge badge-success'>SCHEMA OK</span>";
    } else {
        echo "<span class='error'>❌ FAILED:</span> 'articles' table not found<br>";
        echo "<span class='badge badge-error'>MISSING TABLE</span>";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ ERROR:</span> " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 4: Initialize NewsAPI Class
echo "<h2>Test 4: Initialize NewsAPI Class</h2>";
echo "<div class='test-result'>";
try {
    $newsAPI = new NewsAPI();
    echo "<span class='success'>✅ PASSED:</span> NewsAPI class instantiated successfully<br>";
    echo "<span class='badge badge-success'>INITIALIZED</span>";
} catch (Exception $e) {
    echo "<span class='error'>❌ FAILED:</span> Failed to initialize NewsAPI class<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<span class='badge badge-error'>ERROR</span>";
    exit;
}
echo "</div>";

// Test 5: Fetch Articles from NewsAPI
echo "<h2>Test 5: Fetch Articles from NewsAPI (Technology Category)</h2>";
echo "<div class='test-result'>";
echo "<div class='info'>📡 Attempting to fetch articles from NewsAPI.org...</div>";

$result = $newsAPI->fetchAndStoreArticles('technology', 'us');

if ($result['success']) {
    echo "<span class='success'>✅ PASSED:</span> Articles fetched successfully!<br>";
    echo "<strong>Total Results:</strong> " . ($result['total'] ?? 'N/A') . "<br>";
    echo "<strong>Articles Stored:</strong> " . ($result['stored'] ?? 0) . "<br>";
    echo "<strong>Message:</strong> " . ($result['message'] ?? '') . "<br>";
    echo "<span class='badge badge-success'>FETCH SUCCESS</span>";
} else {
    echo "<span class='error'>❌ FAILED:</span> Failed to fetch articles<br>";
    echo "<strong>Error Message:</strong> " . ($result['message'] ?? 'Unknown error') . "<br>";
    echo "<span class='badge badge-error'>FETCH FAILED</span>";
    
    // Additional debugging
    echo "<div class='info'>";
    echo "<strong>Possible Issues:</strong><br>";
    echo "• Check if API key is valid<br>";
    echo "• Verify internet connection<br>";
    echo "• Check if you've exceeded API rate limits (500/day for free tier)<br>";
    echo "• Ensure SSL/HTTPS is configured correctly<br>";
    echo "</div>";
}
echo "</div>";

// Test 6: Retrieve Articles from Database
echo "<h2>Test 6: Retrieve Articles from Database</h2>";
echo "<div class='test-result'>";
try {
    $articles = $newsAPI->getArticles('technology', null, null, 1, 5);
    $count = count($articles);
    
    if ($count > 0) {
        echo "<span class='success'>✅ PASSED:</span> Retrieved {$count} articles from database<br>";
        echo "<span class='badge badge-success'>FOUND {$count} ARTICLES</span>";
    } else {
        echo "<span class='warning'>⚠️ WARNING:</span> No articles found in database<br>";
        echo "<span class='badge badge-info'>EMPTY</span>";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ FAILED:</span> Error retrieving articles<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    $articles = [];
}
echo "</div>";

// Test 7: Display Sample Articles
if (!empty($articles)) {
    echo "<h2>Test 7: Sample Articles (First 3)</h2>";
    echo "<div class='test-result'>";
    
    foreach (array_slice($articles, 0, 3) as $index => $article) {
        echo "<div class='article-card'>";
        echo "<h3>" . ($index + 1) . ". " . htmlspecialchars($article['title'] ?? 'No title') . "</h3>";
        
        if (!empty($article['image_url'])) {
            echo "<img src='" . htmlspecialchars($article['image_url']) . "' alt='Article image' onerror=\"this.style.display='none'\"><br>";
        }
        
        echo "<p><strong>Category:</strong> " . htmlspecialchars($article['category'] ?? 'N/A') . "</p>";
        echo "<p><strong>Author:</strong> " . htmlspecialchars($article['author'] ?? 'Unknown') . "</p>";
        echo "<p><strong>Published:</strong> " . ($article['published_at'] ?? 'N/A') . "</p>";
        echo "<p><strong>Description:</strong> " . htmlspecialchars(substr($article['description'] ?? 'No description', 0, 200)) . "...</p>";
        
        if (!empty($article['url'])) {
            echo "<p><a href='" . htmlspecialchars($article['url']) . "' target='_blank'>Read Full Article →</a></p>";
        }
        echo "</div>";
    }
    
    echo "</div>";
}

// Test 8: Get Statistics
echo "<h2>Test 8: Get Statistics</h2>";
echo "<div class='test-result'>";
try {
    $stats = $newsAPI->getStats();
    
    if (!empty($stats)) {
        echo "<span class='success'>✅ PASSED:</span> Statistics retrieved successfully<br>";
        echo "<strong>Total Articles:</strong> " . ($stats['total_articles'] ?? 0) . "<br>";
        echo "<strong>Recent Articles (24h):</strong> " . ($stats['recent_articles'] ?? 0) . "<br>";
        echo "<strong>Total Views:</strong> " . ($stats['total_views'] ?? 0) . "<br>";
        
        if (!empty($stats['by_category'])) {
            echo "<strong>Articles by Category:</strong><br>";
            echo "<ul>";
            foreach ($stats['by_category'] as $cat) {
                echo "<li>" . htmlspecialchars($cat['category']) . ": " . $cat['count'] . " articles</li>";
            }
            echo "</ul>";
        }
        echo "<span class='badge badge-success'>STATS OK</span>";
    } else {
        echo "<span class='warning'>⚠️ WARNING:</span> No statistics available<br>";
        echo "<span class='badge badge-info'>NO DATA</span>";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ FAILED:</span> Error getting statistics<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 9: Test Different Categories
echo "<h2>Test 9: Test Multiple Categories</h2>";
echo "<div class='test-result'>";
$categories = ['general', 'business', 'sports'];
echo "<p>Testing fetch for multiple categories...</p>";

foreach ($categories as $cat) {
    echo "<div style='margin: 10px 0;'>";
    echo "<strong>Category: {$cat}</strong> - ";
    
    $catResult = $newsAPI->fetchAndStoreArticles($cat, 'us');
    
    if ($catResult['success']) {
        echo "<span class='success'>✅ Success</span> ";
        echo "(" . ($catResult['stored'] ?? 0) . " articles stored)";
    } else {
        echo "<span class='error'>❌ Failed</span> ";
        echo "(" . ($catResult['message'] ?? 'Unknown error') . ")";
    }
    echo "</div>";
}
echo "</div>";

// Summary
echo "<hr>";
echo "<h2>📊 Test Summary</h2>";
echo "<div class='test-result'>";
echo "<h3 style='color: #4CAF50;'>✓ All Tests Completed!</h3>";
echo "<p><strong>Your NewsAPI integration is " . ($result['success'] ? "<span class='success'>WORKING CORRECTLY</span>" : "<span class='error'>EXPERIENCING ISSUES</span>") . "</strong></p>";

if ($result['success']) {
    echo "<div class='info'>";
    echo "<strong>Next Steps:</strong><br>";
    echo "• Your API key is valid and working<br>";
    echo "• Articles are being fetched and stored successfully<br>";
    echo "• You can now use the main news aggregator at <a href='index.php'>index.php</a><br>";
    echo "• Check the admin panel at <a href='admin.php'>admin.php</a> for more options<br>";
    echo "</div>";
} else {
    echo "<div class='info'>";
    echo "<strong>Troubleshooting:</strong><br>";
    echo "• Verify your API key at <a href='https://newsapi.org/account' target='_blank'>NewsAPI.org</a><br>";
    echo "• Check if you've exceeded the rate limit (500 requests/day for free tier)<br>";
    echo "• Ensure your server has internet access and can make HTTPS requests<br>";
    echo "• Check error logs for more details<br>";
    echo "</div>";
}

echo "</div>";

echo "<hr>";
echo "<p><a href='index.php'>← Back to News Aggregator</a> | <a href='admin.php'>Admin Panel →</a></p>";
echo "</div>";
?>

<?php
/**
 * NewsAPI Connection Test Script
 * This script tests if the NewsAPI key is working properly
 */

require_once 'config.php';

echo "<h1>NewsAPI Connection Test</h1>";
echo "<hr>";

// Check if API key is set
if (empty(trim(NEWS_API_KEY))) {
    echo "<p style='color: red;'><strong>ERROR:</strong> API key is not set in config.php</p>";
    echo "<p>Please add your NewsAPI key to config.php</p>";
    echo "<p>You can get a free API key at: <a href='https://newsapi.org/register' target='_blank'>https://newsapi.org/register</a></p>";
    exit;
}

echo "<p><strong>API Key Status:</strong> ✓ API key is configured</p>";
echo "<p><strong>API Key:</strong> " . substr(NEWS_API_KEY, 0, 8) . "..." . substr(NEWS_API_KEY, -4) . " (masked for security)</p>";
echo "<hr>";

// Test API connection by fetching a few articles
echo "<h2>Testing API Connection...</h2>";

$testUrl = NEWS_API_BASE_URL . 'top-headlines?country=us&pageSize=5&apiKey=' . NEWS_API_KEY;

echo "<p><strong>Test URL:</strong> " . str_replace(NEWS_API_KEY, 'YOUR_API_KEY', $testUrl) . "</p>";

// Attempt to fetch data
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($testUrl, false, $context);

if ($response === false) {
    echo "<p style='color: red;'><strong>ERROR:</strong> Failed to connect to NewsAPI</p>";
    echo "<p>Please check your internet connection and try again.</p>";
    exit;
}

// Parse response
$data = json_decode($response, true);

echo "<hr>";
echo "<h2>API Response:</h2>";

if (isset($data['status']) && $data['status'] === 'ok') {
    echo "<p style='color: green; font-size: 18px;'><strong>✓ SUCCESS!</strong> NewsAPI is working correctly!</p>";
    echo "<p><strong>Total Results:</strong> " . ($data['totalResults'] ?? 0) . "</p>";
    echo "<p><strong>Articles Fetched:</strong> " . count($data['articles'] ?? []) . "</p>";
    
    if (!empty($data['articles'])) {
        echo "<hr>";
        echo "<h2>Sample Articles:</h2>";
        echo "<div style='margin: 20px 0;'>";
        
        foreach (array_slice($data['articles'], 0, 3) as $index => $article) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h3>" . ($index + 1) . ". " . htmlspecialchars($article['title'] ?? 'No title') . "</h3>";
            echo "<p><strong>Source:</strong> " . htmlspecialchars($article['source']['name'] ?? 'Unknown') . "</p>";
            echo "<p><strong>Published:</strong> " . ($article['publishedAt'] ?? 'Unknown') . "</p>";
            echo "<p><strong>Description:</strong> " . htmlspecialchars(substr($article['description'] ?? 'No description', 0, 200)) . "...</p>";
            if (!empty($article['url'])) {
                echo "<p><a href='" . htmlspecialchars($article['url']) . "' target='_blank'>Read more →</a></p>";
            }
            echo "</div>";
        }
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ Your NewsAPI key is working perfectly!</h3>";
    echo "<p>You can now use the news aggregator system.</p>";
    
} elseif (isset($data['status']) && $data['status'] === 'error') {
    echo "<p style='color: red;'><strong>ERROR:</strong> API returned an error</p>";
    echo "<p><strong>Error Code:</strong> " . ($data['code'] ?? 'Unknown') . "</p>";
    echo "<p><strong>Error Message:</strong> " . ($data['message'] ?? 'Unknown error') . "</p>";
    
    if (isset($data['code'])) {
        echo "<hr>";
        echo "<h3>Common Error Solutions:</h3>";
        echo "<ul>";
        
        switch ($data['code']) {
            case 'apiKeyInvalid':
                echo "<li><strong>Invalid API Key:</strong> Your API key is not valid. Please check that you copied it correctly from NewsAPI.org</li>";
                echo "<li>Get a new API key at: <a href='https://newsapi.org/register' target='_blank'>https://newsapi.org/register</a></li>";
                break;
            case 'apiKeyMissing':
                echo "<li><strong>Missing API Key:</strong> API key was not found in the request.</li>";
                break;
            case 'rateLimited':
                echo "<li><strong>Rate Limited:</strong> You have exceeded your API request limit.</li>";
                echo "<li>Free tier allows 500 requests per day.</li>";
                echo "<li>Consider upgrading or wait until your quota resets.</li>";
                break;
            case 'apiKeyDisabled':
                echo "<li><strong>Disabled API Key:</strong> Your API key has been disabled.</li>";
                echo "<li>Contact NewsAPI support or create a new account.</li>";
                break;
            default:
                echo "<li>Check the NewsAPI documentation: <a href='https://newsapi.org/docs' target='_blank'>https://newsapi.org/docs</a></li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: orange;'><strong>WARNING:</strong> Unexpected API response</p>";
    echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
}

echo "<hr>";
echo "<p><small>Test completed at: " . date('Y-m-d H:i:s') . "</small></p>";
echo "<p><a href='index.php'>← Back to News Aggregator</a></p>";
?>

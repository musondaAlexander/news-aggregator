<?php
require_once 'config.php';

/**
 * News API Handler Class
 * Handles all interactions with the News API and database operations
 */
class NewsAPI
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Fetch articles from News API and store in database
     */
    public function fetchAndStoreArticles($category = 'general', $country = 'us', $sources = null, $query = null)
    {
        $url = NEWS_API_BASE_URL;
        $params = [
            'apiKey' => NEWS_API_KEY,
            'pageSize' => 100
        ];

        if ($query) {
            $url .= 'everything';
            $params['q'] = $query;
            $params['sortBy'] = 'publishedAt';
        } else {
            $url .= 'top-headlines';
            if ($category !== 'all') {
                $params['category'] = $category;
            }
            if ($country) {
                $params['country'] = $country;
            }
        }

        if ($sources) {
            $params['sources'] = $sources;
            unset($params['country']); // Can't use both sources and country
        }

        $url .= '?' . http_build_query($params);

        try {
            if (empty(NEWS_API_KEY)) {
                throw new Exception('News API key is not configured');
            }

            $resp = $this->httpRequest($url);
            if (!$resp['success']) {
                throw new Exception('HTTP request failed: ' . $resp['error'] . ' (HTTP ' . $resp['http_code'] . ')');
            }

            $responseBody = $resp['body'];
            $data = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse API response: ' . json_last_error_msg());
            }

            if (!isset($data['status']) || $data['status'] !== 'ok') {
                $apiMessage = isset($data['message']) ? $data['message'] : 'Unknown API error';
                throw new Exception('API Error: ' . $apiMessage);
            }

            $stored = 0;
            foreach ($data['articles'] as $article) {
                if ($this->storeArticle($article, $category, $country)) {
                    $stored++;
                }
            }

            return [
                'success' => true,
                'total' => $data['totalResults'],
                'stored' => $stored,
                'message' => "Successfully fetched and stored $stored articles"
            ];
        } catch (Exception $e) {
            error_log("NewsAPI Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to fetch articles: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform HTTP GET using cURL if available, otherwise fallback to file_get_contents.
     * Returns array: ['success'=>bool, 'http_code'=>int, 'body'=>string, 'error'=>string]
     */
    private function httpRequest($url)
    {
        // Prefer cURL for better diagnostics
        if (function_exists('curl_version')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_USERAGENT, 'NewsHub/1.0');
            // Ensure SSL verification is enabled in production
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $body = curl_exec($ch);
            $err = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || $err) {
                return ['success' => false, 'http_code' => $httpCode ?: 0, 'body' => '', 'error' => $err ?: 'Unknown curl error'];
            }

            return ['success' => true, 'http_code' => $httpCode, 'body' => $body, 'error' => ''];
        }

        // Fallback to file_get_contents
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'header' => "User-Agent: NewsHub/1.0\r\n"
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ]);

        set_error_handler(function ($severity, $message, $file, $line) use (&$phpErr) {
            $phpErr = $message;
        });
        $body = @file_get_contents($url, false, $context);
        restore_error_handler();

        if ($body === false) {
            $errMsg = isset($phpErr) ? $phpErr : 'file_get_contents failed';
            return ['success' => false, 'http_code' => 0, 'body' => '', 'error' => $errMsg];
        }

        // Attempt to get HTTP response code from $http_response_header
        $httpCode = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (stripos($hdr, 'HTTP/') === 0) {
                    $parts = explode(' ', $hdr);
                    if (isset($parts[1])) $httpCode = intval($parts[1]);
                    break;
                }
            }
        }

        return ['success' => true, 'http_code' => $httpCode, 'body' => $body, 'error' => ''];
    }

    /**
     * Store individual article in database
     */
    private function storeArticle($article, $category, $country)
    {
        if (empty($article['url']) || empty($article['title'])) {
            return false;
        }

        try {
            // Adapt to existing schema: articles table uses image_url and summary
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO articles 
                (title, summary, content, url, image_url, published_at, author, category)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $publishedAt = $article['publishedAt'] ?
                date('Y-m-d H:i:s', strtotime($article['publishedAt'])) : date('Y-m-d H:i:s');

            return $stmt->execute([
                $article['title'],
                $article['description'] ?? $article['content'] ?? null,
                $article['content'] ?? $article['description'] ?? null,
                $article['url'],
                $article['urlToImage'] ?? null,
                $publishedAt,
                $article['author'] ?? null,
                $category
            ]);
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get articles from database
     */
    public function getArticles($category = null, $country = null, $search = null, $page = 1, $limit = ARTICLES_PER_PAGE)
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        // Select columns that exist in current schema and map later to frontend keys
        $sql = "SELECT * FROM articles WHERE 1=1";

        if ($category && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

    // Note: some schemas do not have a 'country' column; skip filtering by country

        if ($search) {
            $sql .= " AND (title LIKE ? OR summary LIKE ? OR content LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY published_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            // Normalize rows to keys expected by templates/JS
            $mapped = [];
            foreach ($rows as $r) {
                $mapped[] = [
                    'id' => $r['id'] ?? null,
                    'title' => $r['title'] ?? '',
                    'description' => $r['summary'] ?? null,
                    'content' => $r['content'] ?? null,
                    'url' => $r['url'] ?? null,
                    'url_to_image' => $r['image_url'] ?? null,
                    'image_url' => $r['image_url'] ?? null,
                    'source_name' => $r['source_name'] ?? null,
                    'source_id' => $r['source_id'] ?? null,
                    'author' => $r['author'] ?? null,
                    'category' => $r['category'] ?? null,
                    'published_at' => $r['published_at'] ?? $r['scraped_at'] ?? null,
                    'views' => $r['views'] ?? 0,
                    'likes' => $r['likes'] ?? 0
                ];
            }

            return $mapped;
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count of articles
     */
    public function getTotalArticles($category = null, $country = null, $search = null)
    {
        $params = [];
        $sql = "SELECT COUNT(*) as total FROM articles WHERE 1=1";

        if ($category && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if ($search) {
            $sql .= " AND (title LIKE ? OR summary LIKE ? OR content LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetch()['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Track article views
     */
    public function trackView($articleId)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO article_views (article_id, view_count) 
                VALUES (?, 1)
                ON DUPLICATE KEY UPDATE 
                view_count = view_count + 1,
                last_viewed = CURRENT_TIMESTAMP
            ");
            return $stmt->execute([$articleId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get trending articles
     */
    public function getTrendingArticles($limit = 6)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, av.view_count as views
                FROM articles a
                LEFT JOIN article_views av ON a.id = av.article_id
                WHERE a.published_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY av.view_count DESC, a.published_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get latest articles by category
     */
    public function getLatestByCategory($limit = 4)
    {
        global $categories;
        $result = [];

        foreach (array_keys($categories) as $category) {
            $articles = $this->getArticles($category, null, null, 1, $limit);
            if (!empty($articles)) {
                $result[$category] = $articles;
            }
        }

        return $result;
    }

    /**
     * Get article statistics
     */
    public function getStats()
    {
        try {
            $stats = [];

            // Total articles
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM articles");
            $stats['total_articles'] = (int) $stmt->fetch()['total'];

            // Articles by category
            $stmt = $this->db->query("SELECT category, COUNT(*) as count FROM articles GROUP BY category ORDER BY count DESC");
            $stats['by_category'] = $stmt->fetchAll();

            // Recent articles (last 24 hours) by published_at
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM articles WHERE published_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stats['recent_articles'] = (int) $stmt->fetch()['count'];

            // Total views (from articles.views if available)
            try {
                $stmt = $this->db->query("SELECT SUM(views) as total FROM articles");
                $stats['total_views'] = (int) ($stmt->fetch()['total'] ?? 0);
            } catch (Exception $e) {
                $stats['total_views'] = 0;
            }

            return $stats;
        } catch (PDOException $e) {
            return [];
        }
    }
}

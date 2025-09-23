<?php
require_once 'config.php';
require_once 'api/Auth.php';

// Require admin authentication
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}
require_once 'NewsAPI.php';

$newsAPI = new NewsAPI();

// Handle admin actions
$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action'] ?? '');
    
    switch ($action) {
        case 'fetch_articles':
            $category = sanitize_input($_POST['category'] ?? 'general');
            $country = sanitize_input($_POST['country'] ?? 'us');
            $sources = sanitize_input($_POST['sources'] ?? '');
            $query = sanitize_input($_POST['query'] ?? '');
            
            $result = $newsAPI->fetchAndStoreArticles($category, $country, $sources ?: null, $query ?: null);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'clear_articles':
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("DELETE FROM articles WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $days = intval($_POST['days'] ?? 30);
                $stmt->execute([$days]);
                $affected = $stmt->rowCount();
                $message = "Deleted $affected articles older than $days days.";
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = "Error clearing articles: " . $e->getMessage();
                $message_type = 'danger';
            }
            break;
            
        case 'update_sources':
            // Fetch and store news sources
            try {
                $url = NEWS_API_BASE_URL . 'sources?apiKey=' . NEWS_API_KEY;
                $response = file_get_contents($url);
                $data = json_decode($response, true);
                
                if ($data['status'] === 'ok') {
                    $db = Database::getConnection();
                    $stmt = $db->prepare("INSERT INTO sources (id, name, description, url, category, language, country) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), url = VALUES(url), category = VALUES(category)");
                    
                    $updated = 0;
                    foreach ($data['sources'] as $source) {
                        if ($stmt->execute([
                            $source['id'],
                            $source['name'],
                            $source['description'],
                            $source['url'],
                            $source['category'],
                            $source['language'],
                            $source['country']
                        ])) {
                            $updated++;
                        }
                    }
                    
                    $message = "Updated $updated news sources.";
                    $message_type = 'success';
                } else {
                    $message = "Failed to fetch sources: " . $data['message'];
                    $message_type = 'danger';
                }
            } catch (Exception $e) {
                $message = "Error updating sources: " . $e->getMessage();
                $message_type = 'danger';
            }
            break;
    }
}

// Get statistics
$stats = $newsAPI->getStats();

// Get recent articles
$recent_articles = $newsAPI->getArticles(null, null, null, 1, 10);

// Get sources
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT * FROM sources ORDER BY name LIMIT 50");
    $sources = $stmt->fetchAll();
} catch (PDOException $e) {
    $sources = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 2rem 0;
        }
        
        .admin-nav {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-nav .nav-tabs {
            display: flex;
            gap: 1rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .admin-nav .nav-tab {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--text-secondary);
            transition: var(--transition);
            border: 1px solid transparent;
        }
        
        .admin-nav .nav-tab:hover,
        .admin-nav .nav-tab.active {
            background: var(--primary-color);
            color: white;
            text-decoration: none;
        }
        
        .admin-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.875rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table th {
            font-weight: 600;
            color: var(--text-primary);
            background: var(--bg-secondary);
        }
        
        .table td {
            color: var(--text-secondary);
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: white;
            background: var(--primary-color);
            border-radius: 0.25rem;
        }
        
        .progress {
            width: 100%;
            height: 8px;
            background: var(--bg-tertiary);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <nav class="navbar container">
            <a href="admin.php" class="navbar-brand">
                <i class="fas fa-cog"></i> Admin Panel
            </a>
            <ul class="navbar-nav">
                <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Back to Site</a></li>
                <li><span class="nav-link">Logged in as <strong><?php echo htmlspecialchars(Auth::user()['username'] ?? 'admin'); ?></strong></span></li>
                <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Admin Header -->
    <section class="admin-header">
        <div class="container">
            <h1><i class="fas fa-dashboard"></i> News Aggregator Dashboard</h1>
            <p>Manage articles, sources, and system settings</p>
        </div>
    </section>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <ul class="nav-tabs">
                <li><a href="#overview" class="nav-tab active" onclick="showTab('overview')">Overview</a></li>
                <li><a href="#fetch" class="nav-tab" onclick="showTab('fetch')">Fetch Articles</a></li>
                <li><a href="#manage" class="nav-tab" onclick="showTab('manage')">Manage Data</a></li>
                <li><a href="#sources" class="nav-tab" onclick="showTab('sources')">Sources</a></li>
                <li><a href="#settings" class="nav-tab" onclick="showTab('settings')">Settings</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Overview Tab -->
        <div id="overview-tab" class="tab-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($stats['total_articles'] ?? 0); ?></span>
                    <span class="stat-label">Total Articles</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($stats['recent_articles'] ?? 0); ?></span>
                    <span class="stat-label">Recent Articles (24h)</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($stats['total_views'] ?? 0); ?></span>
                    <span class="stat-label">Total Views</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($sources); ?></span>
                    <span class="stat-label">News Sources</span>
                </div>
            </div>

            <div class="admin-card">
                <h3><i class="fas fa-chart-pie"></i> Articles by Category</h3>
                <?php if (!empty($stats['by_category'])): ?>
                    <?php 
                    $total = array_sum(array_column($stats['by_category'], 'count'));
                    foreach ($stats['by_category'] as $category): 
                        $percentage = $total > 0 ? ($category['count'] / $total) * 100 : 0;
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-between align-center mb-1">
                                <span class="form-label"><?php echo ucfirst($category['category']); ?></span>
                                <span class="badge"><?php echo number_format($category['count']); ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted">No category data available</p>
                <?php endif; ?>
            </div>

            <div class="admin-card">
                <h3><i class="fas fa-clock"></i> Recent Articles</h3>
                <?php if (!empty($recent_articles)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Source</th>
                                    <th>Category</th>
                                    <th>Published</th>
                                    <th>Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_articles as $article): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($article['url']); ?>" target="_blank">
                                                <?php echo truncate_text(htmlspecialchars($article['title']), 50); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($article['source_name'] ?? 'Unknown'); ?></td>
                                        <td><span class="badge"><?php echo ucfirst($article['category']); ?></span></td>
                                        <td><?php echo format_time_ago($article['published_at']); ?></td>
                                        <td><?php echo number_format($article['views'] ?? 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No recent articles found</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fetch Articles Tab -->
        <div id="fetch-tab" class="tab-content" style="display: none;">
            <div class="admin-card">
                <h3><i class="fas fa-download"></i> Fetch New Articles</h3>
                <p class="text-secondary mb-4">Retrieve the latest articles from News API and store them in the database.</p>
                
                <form method="POST" onsubmit="showLoader(this)">
                    <input type="hidden" name="action" value="fetch_articles">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control">
                                <option value="general">General</option>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <select name="country" class="form-control">
                                <?php foreach ($countries as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Specific Sources (optional)</label>
                        <input type="text" name="sources" class="form-control" 
                               placeholder="e.g., bbc-news,cnn,techcrunch (comma-separated)">
                        <small class="text-muted">Leave empty to use category and country filters</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Search Query (optional)</label>
                        <input type="text" name="query" class="form-control" 
                               placeholder="Search for specific topics or keywords">
                        <small class="text-muted">Use this to fetch articles about specific topics</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Fetch Articles
                    </button>
                </form>
            </div>

            <div class="admin-card">
                <h3><i class="fas fa-info-circle"></i> API Usage Guidelines</h3>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Important:</strong> News API free tier allows 1,000 requests per day. Use wisely!
                </div>
                <ul>
                    <li>Each fetch operation counts as one API request</li>
                    <li>You can fetch up to 100 articles per request</li>
                    <li>Duplicate articles are automatically ignored</li>
                    <li>Articles older than 30 days may not be available</li>
                    <li>Some sources may require premium subscription</li>
                </ul>
            </div>
        </div>

        <!-- Manage Data Tab -->
        <div id="manage-tab" class="tab-content" style="display: none;">
            <div class="admin-card">
                <h3><i class="fas fa-trash-alt"></i> Clean Up Old Articles</h3>
                <p class="text-secondary mb-4">Remove old articles to keep your database clean and improve performance.</p>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete old articles? This action cannot be undone.')">
                    <input type="hidden" name="action" value="clear_articles">
                    
                    <div class="form-group">
                        <label class="form-label">Delete articles older than</label>
                        <select name="days" class="form-control">
                            <option value="7">7 days</option>
                            <option value="14">14 days</option>
                            <option value="30" selected>30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Delete Old Articles
                    </button>
                </form>
            </div>

            <div class="admin-card">
                <h3><i class="fas fa-database"></i> Database Statistics</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Records</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Articles</td>
                                <td><?php echo number_format($stats['total_articles'] ?? 0); ?></td>
                                <td>News articles from various sources</td>
                            </tr>
                            <tr>
                                <td>Sources</td>
                                <td><?php echo count($sources); ?></td>
                                <td>Available news sources</td>
                            </tr>
                            <tr>
                                <td>Article Views</td>
                                <td><?php echo number_format($stats['total_views'] ?? 0); ?></td>
                                <td>Total article view count</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sources Tab -->
        <div id="sources-tab" class="tab-content" style="display: none;">
            <div class="admin-card">
                <h3><i class="fas fa-sync-alt"></i> Update News Sources</h3>
                <p class="text-secondary mb-4">Fetch the latest list of available news sources from the API.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_sources">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Update Sources
                    </button>
                </form>
            </div>

            <div class="admin-card">
                <h3><i class="fas fa-list"></i> Available Sources</h3>
                <?php if (!empty($sources)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Country</th>
                                    <th>Language</th>
                                    <th>Website</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sources as $source): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($source['id']); ?></code></td>
                                        <td><?php echo htmlspecialchars($source['name']); ?></td>
                                        <td><span class="badge"><?php echo ucfirst($source['category']); ?></span></td>
                                        <td><?php echo strtoupper($source['country']); ?></td>
                                        <td><?php echo strtoupper($source['language']); ?></td>
                                        <td>
                                            <?php if ($source['url']): ?>
                                                <a href="<?php echo htmlspecialchars($source['url']); ?>" target="_blank">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No sources available. Click "Update Sources" to fetch them.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="settings-tab" class="tab-content" style="display: none;">
            <div class="admin-card">
                <h3><i class="fas fa-cog"></i> System Settings</h3>
                <p class="text-secondary">Configure basic application settings.</p>
                <form method="POST" onsubmit="return false;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Articles per page</label>
                            <input type="number" class="form-control" value="<?php echo ARTICLES_PER_PAGE; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cache duration (seconds)</label>
                            <input type="number" class="form-control" value="<?php echo CACHE_DURATION; ?>" disabled>
                        </div>
                    </div>
                    <p class="text-muted">To change these values, edit <code>config.php</code>.</p>
                </form>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container text-center">
            <small class="text-muted">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> — Admin Panel</small>
        </div>
    </footer>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.querySelector('.nav-tab[href="#' + tab + '"]').classList.add('active');
            document.getElementById(tab + '-tab').style.display = 'block';
        }

        // Attach active links properly
        document.querySelectorAll('.nav-tab').forEach(a => {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href').replace('#', '');
                showTab(href);
            });
        });

        function showLoader(form) {
            const btn = form.querySelector('button[type="submit"]');
            if (!btn) return true;
            btn.disabled = true;
            const original = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span> Working...';
            setTimeout(() => { btn.disabled = false; btn.innerHTML = original; }, 5000);
            return true;
        }
    </script>
</body>
</html>
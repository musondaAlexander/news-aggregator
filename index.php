<?php
require_once 'config.php';
require_once __DIR__ . '/api/NewsApi.php';

$newsAPI = new NewsAPI();

// Handle AJAX requests for fetching new articles
if (isset($_POST['fetch_articles'])) {
    header('Content-Type: application/json');
    $category = sanitize_input($_POST['category'] ?? 'general');
    $country = sanitize_input($_POST['country'] ?? 'us');
    $result = $newsAPI->fetchAndStoreArticles($category, $country);
    echo json_encode($result);
    exit;
}

// Get current filters
$current_category = sanitize_input($_GET['category'] ?? 'all');
$current_country = sanitize_input($_GET['country'] ?? 'us');
$current_search = sanitize_input($_GET['search'] ?? '');
$current_page = max(1, intval($_GET['page'] ?? 1));

// Get articles
$articles = $newsAPI->getArticles($current_category, $current_country, $current_search, $current_page);
$total_articles = $newsAPI->getTotalArticles($current_category, $current_country, $current_search);
$total_pages = ceil($total_articles / ARTICLES_PER_PAGE);

// Get additional data for homepage
$trending_articles = $newsAPI->getTrendingArticles(5);
$latest_by_category = $newsAPI->getLatestByCategory(3);
$stats = $newsAPI->getStats();

// Track page view (simple analytics)
if (!isset($_SESSION['viewed_pages'])) {
    $_SESSION['viewed_pages'] = [];
}
if (!in_array('homepage', $_SESSION['viewed_pages'])) {
    $_SESSION['viewed_pages'][] = 'homepage';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Stay Informed with Latest News</title>
    <meta name="description" content="Get the latest breaking news, headlines, and stories from around the world. Your trusted source for news in technology, business, health, sports and more.">
    <meta name="keywords" content="news, breaking news, headlines, world news, technology, business, sports, health">
    <meta name="author" content="<?php echo APP_NAME; ?>">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo APP_NAME; ?> - Latest News & Headlines">
    <meta property="og:description" content="Stay updated with breaking news and stories from trusted sources worldwide">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <nav class="navbar container">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-newspaper"></i> <?php echo APP_NAME; ?>
            </a>
            
            <ul class="navbar-nav" id="navbarNav">
                <li><a href="index.php" class="nav-link <?php echo empty($_GET) ? 'active' : ''; ?>">Home</a></li>
                <li><a href="?category=technology" class="nav-link <?php echo $current_category == 'technology' ? 'active' : ''; ?>">Tech</a></li>
                <li><a href="?category=business" class="nav-link <?php echo $current_category == 'business' ? 'active' : ''; ?>">Business</a></li>
                <li><a href="?category=sports" class="nav-link <?php echo $current_category == 'sports' ? 'active' : ''; ?>">Sports</a></li>
                <li><a href="?category=health" class="nav-link <?php echo $current_category == 'health' ? 'active' : ''; ?>">Health</a></li>
                <li><a href="admin.php" class="nav-link">Admin</a></li>
            </ul>
            
            <button class="navbar-toggler" onclick="toggleNavbar()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>

    <!-- Hero Section -->
    <?php if (empty($_GET)): ?>
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Stay Informed with Latest News</h1>
                <p>Get breaking news, headlines, and stories from trusted sources around the world</p>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['total_articles'] ?? 0); ?></span>
                        <span class="stat-label">Total Articles</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['recent_articles'] ?? 0); ?></span>
                        <span class="stat-label">Recent Articles</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['total_views'] ?? 0); ?></span>
                        <span class="stat-label">Total Views</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo count($categories); ?></span>
                        <span class="stat-label">Categories</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Search Section -->
    <section class="search-container">
        <div class="container">
            <form class="search-form" method="GET" action="index.php">
                <input type="text" name="search" class="search-input" 
                       placeholder="Search news articles..." 
                       value="<?php echo htmlspecialchars($current_search); ?>">
                
                <select name="category" class="search-select">
                    <option value="all" <?php echo $current_category == 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $current_category == $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="country" class="search-select">
                    <?php foreach ($countries as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $current_country == $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                
                <button type="button" class="btn btn-secondary" onclick="fetchLatestNews()">
                    <i class="fas fa-sync-alt"></i> <span id="fetch-text">Fetch Latest</span>
                </button>
            </form>
        </div>
    </section>

    <!-- Category Filters -->
    <section class="category-filters">
        <div class="container">
            <div class="filter-tabs">
                <a href="?" class="filter-tab <?php echo empty($_GET) ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> All News
                </a>
                <?php foreach ($categories as $key => $label): ?>
                    <a href="?category=<?php echo $key; ?><?php echo $current_country != 'us' ? '&country=' . $current_country : ''; ?>" 
                       class="filter-tab <?php echo $current_category == $key ? 'active' : ''; ?>">
                        <?php
                        $icons = [
                            'general' => 'fas fa-globe',
                            'business' => 'fas fa-chart-line',
                            'entertainment' => 'fas fa-film',
                            'health' => 'fas fa-heartbeat',
                            'science' => 'fas fa-flask',
                            'sports' => 'fas fa-futbol',
                            'technology' => 'fas fa-microchip'
                        ];
                        ?>
                        <i class="<?php echo $icons[$key] ?? 'fas fa-newspaper'; ?>"></i>
                        <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container">
        <div class="main-content">
            <!-- Articles Section -->
            <section class="content-area">
                <?php if (!empty($current_search)): ?>
                    <h2 class="mb-3">Search Results for "<?php echo htmlspecialchars($current_search); ?>"</h2>
                    <p class="text-secondary mb-4">Found <?php echo number_format($total_articles); ?> articles</p>
                <?php elseif ($current_category !== 'all'): ?>
                    <h2 class="mb-3"><?php echo $categories[$current_category]; ?> News</h2>
                <?php endif; ?>

                <!-- Articles Grid -->
                <div class="articles-grid" id="articlesGrid">
                    <?php if (empty($articles)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                            <h3>No Articles Found</h3>
                            <p>Try adjusting your search criteria or fetch the latest articles.</p>
                            <button onclick="fetchLatestNews()" class="btn btn-primary">
                                <i class="fas fa-sync-alt"></i> Fetch Latest Articles
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($articles as $article): ?>
                            <article class="article-card fade-in">
                                <?php if ($article['url_to_image']): ?>
                                    <img src="<?php echo htmlspecialchars($article['url_to_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                                         class="article-image"
                                        onerror="this.src='assets/images/placeholder.svg'">
                                <?php else: ?>
                                    <div class="article-image d-flex align-center justify-center" style="background: var(--bg-tertiary);">
                                        <i class="fas fa-newspaper fa-2x" style="color: var(--text-muted);"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="article-content">
                                    <div class="article-meta">
                                        <span class="article-source"><?php echo htmlspecialchars($article['source_name'] ?? 'Unknown'); ?></span>
                                        <span class="article-time"><?php echo format_time_ago($article['published_at']); ?></span>
                                    </div>
                                    
                                    <h3 class="article-title">
                                        <a href="<?php echo htmlspecialchars($article['url']); ?>" 
                                           target="_blank" 
                                           onclick="trackView(<?php echo $article['id']; ?>)">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if ($article['description']): ?>
                                        <p class="article-description">
                                            <?php echo truncate_text(htmlspecialchars($article['description']), 120); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="article-footer">
                                        <a href="<?php echo htmlspecialchars($article['url']); ?>" 
                                           target="_blank" 
                                           class="read-more"
                                           onclick="trackView(<?php echo $article['id']; ?>)">
                                            Read Full Article <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <?php if ($article['views']): ?>
                                            <span class="article-views">
                                                <i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?> views
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $current_page - 2);
                        $end = min($total_pages, $current_page + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <?php if ($i == $current_page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Trending Articles -->
                <?php if (!empty($trending_articles)): ?>
                    <div class="sidebar-section">
                        <h3 class="sidebar-title">
                            <i class="fas fa-fire"></i> Trending Now
                        </h3>
                        <?php foreach ($trending_articles as $article): ?>
                            <div class="trending-article">
                                <?php if ($article['url_to_image']): ?>
                                    <img src="<?php echo htmlspecialchars($article['url_to_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                                         class="trending-image"
                                         onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div class="trending-image d-flex align-center justify-center" style="background: var(--bg-tertiary);">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="trending-content">
                                    <h4 class="trending-title">
                                        <a href="<?php echo htmlspecialchars($article['url']); ?>" 
                                           target="_blank"
                                           onclick="trackView(<?php echo $article['id']; ?>)">
                                            <?php echo truncate_text(htmlspecialchars($article['title']), 60); ?>
                                        </a>
                                    </h4>
                                    <div class="trending-meta">
                                        <?php echo format_time_ago($article['published_at']); ?>
                                        <?php if ($article['views']): ?>
                                            • <?php echo number_format($article['views']); ?> views
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Category Stats -->
                <?php if (!empty($stats['by_category'])): ?>
                    <div class="sidebar-section">
                        <h3 class="sidebar-title">
                            <i class="fas fa-chart-bar"></i> Articles by Category
                        </h3>
                        <?php foreach ($stats['by_category'] as $stat): ?>
                            <div class="d-flex justify-between align-center mb-2">
                                <span><?php echo ucfirst($stat['category']); ?></span>
                                <span class="badge"><?php echo number_format($stat['count']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h3>
                    <div class="d-grid gap-2">
                        <button onclick="window.location.reload()" class="btn btn-secondary w-full">
                            <i class="fas fa-refresh"></i> Refresh Page
                        </button>
                        <button onclick="fetchLatestNews()" class="btn btn-primary w-full">
                            <i class="fas fa-download"></i> Fetch Latest
                        </button>
                        <a href="admin.php" class="btn btn-secondary w-full">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?php echo APP_NAME; ?></h4>
                    <p>Your trusted source for breaking news and stories from around the world. Stay informed, stay connected.</p>
                </div>
                <div class="footer-section">
                    <h4>Categories</h4>
                    <ul>
                        <?php foreach ($categories as $key => $label): ?>
                            <li><a href="?category=<?php echo $key; ?>"><?php echo $label; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>About</h4>
                    <ul>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="#privacy">Privacy Policy</a></li>
                        <li><a href="#terms">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Connect</h4>
                    <ul>
                        <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                        <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                        <li><a href="#"><i class="fab fa-linkedin"></i> LinkedIn</a></li>
                        <li><a href="#"><i class="fas fa-rss"></i> RSS Feed</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved. | Version <?php echo APP_VERSION; ?></p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Mobile Navigation Toggle
        function toggleNavbar() {
            const nav = document.getElementById('navbarNav');
            nav.classList.toggle('show');
        }

        // Track article views
        function trackView(articleId) {
            fetch('track_view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'article_id=' + articleId
            });
        }

        // Fetch latest news
        async function fetchLatestNews() {
            const button = document.querySelector('button[onclick="fetchLatestNews()"]');
            const fetchText = document.getElementById('fetch-text');
            const originalText = fetchText.textContent;
            
            // Show loading state
            fetchText.innerHTML = '<span class="loading-spinner"></span>Fetching...';
            button.disabled = true;

            try {
                const formData = new FormData();
                formData.append('fetch_articles', '1');
                formData.append('category', '<?php echo $current_category; ?>');
                formData.append('country', '<?php echo $current_country; ?>');

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('fetchLatestNews response:', result);

                if (result.success) {
                    showAlert('success', result.message);
                    // Reload page after 2 seconds to show new articles
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                showAlert('danger', 'Error fetching articles. Please try again.');
                console.error('Error:', error);
            } finally {
                // Reset button state
                fetchText.textContent = originalText;
                button.disabled = false;
            }
        }

        // Show alert messages
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            
            // Insert alert at top of main content
            const mainContent = document.querySelector('.main-content');
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
            
            // Auto-remove alert after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Close mobile navigation when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.getElementById('navbarNav');
            const toggler = document.querySelector('.navbar-toggler');
            
            if (nav.classList.contains('show') && !nav.contains(e.target) && !toggler.contains(e.target)) {
                nav.classList.remove('show');
            }
        });

        // Lazy loading for images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Search form enhancement
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            if (searchInput.value.trim() === '') {
                searchInput.removeAttribute('name');
            }
        });

        // Auto-refresh functionality (optional)
        let autoRefreshInterval;
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                const notification = document.createElement('div');
                notification.className = 'alert alert-info';
                notification.innerHTML = `
                    <i class="fas fa-info-circle"></i>
                    New articles may be available. <a href="#" onclick="window.location.reload()">Refresh page</a>
                `;
                document.body.appendChild(notification);
                
                setTimeout(() => notification.remove(), 10000);
            }, 300000); // 5 minutes
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        }

        // Initialize auto-refresh on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Only start auto-refresh on homepage
            if (window.location.pathname === '/index.php' || window.location.pathname === '/') {
                startAutoRefresh();
            }
        });

        // Stop auto-refresh when page is hidden
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                window.location.reload();
            }
            
            // Ctrl/Cmd + F for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.querySelector('.search-input').focus();
            }
        });

        // Service Worker for offline functionality (Progressive Web App)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('ServiceWorker registration failed: ', registrationError);
                    });
            });
        }

        // Performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log(`Page loaded in ${Math.round(loadTime)}ms`);
            
            // Track page load time (you could send this to analytics)
            if (loadTime > 3000) {
                console.warn('Page load time is slow');
            }
        });
    </script>

    <!-- Schema.org structured data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "NewsMediaOrganization",
        "name": "<?php echo APP_NAME; ?>",
        "description": "News aggregator providing latest headlines and stories from trusted sources",
        "url": "<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST']; ?>",
    "logo": "<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST']; ?>/assets/images/logo.svg",
        "sameAs": [
            "https://twitter.com/newshub",
            "https://facebook.com/newshub"
        ]
    }
    </script>
</body>
</html>
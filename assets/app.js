// Main front-end application moved from inline script in index.php
class NewsAggregatorApp {
  constructor() {
    this.currentCategory = 'all';
    this.currentOffset = 0;
    this.isLoading = false;
    this.articlesPerPage = 20;
  this.dailyPicks = [];
  this.dailyPickIndex = 0;
  }

  async init() {
    await this.loadCategories();
    await this.loadInitialData();
    this.setupEventListeners();
    this.loadUserStats();
  const savedTheme = localStorage.getItem('theme') || 'light';
  this.applyTheme(savedTheme);

  // Load today's daily picks (if UI exists)
  this.loadDailyPicks();
  }

  setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') this.performSearch();
      });
    }

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', (e) => {
        const href = anchor.getAttribute('href');
        if (!href || href === '#') return;
        const target = document.querySelector(href);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth' });
        }
      });
    });

    window.addEventListener('scroll', () => {
      if (this.isNearBottom() && !this.isLoading) {
        this.loadMoreNews();
      }
    });
  }

  async loadCategories() {
    try {
      const response = await fetch('api/news.php?action=categories');
      const data = await response.json();

      if (data.success) {
        this.renderCategoryFilters(data.data);
        this.renderCategoriesDropdown(data.data);
      }
    } catch (error) {
      console.error('Error loading categories:', error);
    }
  }

  renderCategoryFilters(categories) {
    const container = document.getElementById('categoryFilters');
    if (!container) return;
    let html = '<button class="btn btn-outline-primary active" onclick="app.loadCategory(\'all\')" data-category="all">All News</button>';

    categories.forEach(cat => {
      if (cat.category) {
        html += `\n                        <button class="btn btn-outline-primary" onclick="app.loadCategory('${cat.category}')" data-category="${cat.category}">\n                            ${cat.category} <span class="badge bg-secondary ms-1">${cat.article_count}</span>\n                        </button>`;
      }
    });

    container.innerHTML = html;
  }

  renderCategoriesDropdown(categories) {
    const dropdown = document.getElementById('categoriesDropdown');
    if (!dropdown) return;
    let html = '<li><a class="dropdown-item" href="#" onclick="app.loadCategory(\'all\')">All Categories</a></li>';

    categories.forEach(cat => {
      if (cat.category) {
        html += `\n                        <li><a class="dropdown-item" href="#" onclick="app.loadCategory('${cat.category}')">\n                            ${cat.category} (${cat.article_count})\n                        </a></li>`;
      }
    });

    dropdown.innerHTML = html;
  }

  async loadInitialData() {
    await Promise.all([
      this.loadNews(this.currentCategory),
      this.loadPopularArticles(),
      this.loadTrendingTopics(),
      this.loadNewsSources()
    ]);
  }

  async loadNews(category = 'all', offset = 0, append = false) {
    if (this.isLoading) return;

    this.isLoading = true;
    const container = document.getElementById('newsContainer');
    if (!container) { this.isLoading = false; return; }

    if (!append) container.innerHTML = this.getLoadingSpinner();

    try {
      const response = await fetch(`api/news.php?action=latest&category=${category}&limit=${this.articlesPerPage}&offset=${offset}`);
      const data = await response.json();

      if (data.success) {
        this.renderNews(data.data, append);
        if (!append) this.currentOffset = this.articlesPerPage; else this.currentOffset += this.articlesPerPage;
      }
    } catch (error) {
      console.error('Error loading news:', error);
      container.innerHTML = '<div class="alert alert-danger">Failed to load news articles.</div>';
    }

    this.isLoading = false;
  }

  renderNews(articles, append = false) {
    const container = document.getElementById('newsContainer');
    if (!container) return;

    if (!articles.length && !append) {
      container.innerHTML = '<div class="alert alert-info">No articles found for this category.</div>';
      return;
    }

    let html = append ? container.innerHTML : '';
    articles.forEach(article => html += this.createArticleCard(article));
    container.innerHTML = html;
  }

  createArticleCard(article) {
    const timeAgo = this.timeAgo(article.published_at);
    return `\n                <article class="news-card" onclick="app.showArticleModal(${article.id})">\n                    <div class="card h-100 news-item-card">\n                        ${article.image_url ? `\n                            <img src="${article.image_url}" class="card-img-top" alt="${this.escapeHtml(article.title)}" loading="lazy">\n                        ` : ''}\n                        <div class="card-body d-flex flex-column">\n                            <div class="card-meta mb-2">\n                                <div class="d-flex align-items-center justify-content-between">\n                                    <div class="d-flex align-items-center">\n                                        ${article.source_logo ? `\n                                            <img src="${article.source_logo}" alt="${this.escapeHtml(article.source_name)}" class="source-logo me-2">\n                                        ` : ''}\n                                        <small class="text-muted">${this.escapeHtml(article.source_name)}</small>\n                                    </div>\n                                    <small class="text-muted">${timeAgo}</small>\n                                </div>\n                            </div>\n                            \n                            <h5 class="card-title">${this.escapeHtml(article.title)}</h5>\n                            <p class="card-text flex-grow-1">${this.escapeHtml(article.summary || '')}</p>\n                            \n                            <div class="card-footer-content">\n                                <div class="d-flex justify-content-between align-items-center">\n                                    <div class="article-badges">\n                                        ${article.category ? `<span class="badge bg-primary">${this.escapeHtml(article.category)}</span>` : ''}\n                                        ${article.author ? `<span class="badge bg-secondary">${this.escapeHtml(article.author)}</span>` : ''}\n                                    </div>\n                                    <div class="article-stats">\n                                        <small class="text-muted">\n                                            <i class="bi bi-eye"></i> ${article.views || 0}\n                                            <i class="bi bi-heart ms-2"></i> ${article.likes || 0}\n                                        </small>\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                </article>\n            `;
  }

  async showArticleModal(articleId) {
    const modal = new bootstrap.Modal(document.getElementById('articleModal'));
    const modalTitle = document.getElementById('articleModalTitle');
    const modalContent = document.getElementById('articleModalContent');
    const likeButton = document.getElementById('likeButton');
    const readOriginalButton = document.getElementById('readOriginalButton');

    modalContent.innerHTML = this.getLoadingSpinner();
    modal.show();

    try {
      const response = await fetch(`api/news.php?action=article&id=${articleId}`);
      const data = await response.json();

      if (data.success) {
        const article = data.data;
        modalTitle.textContent = article.title;

        modalContent.innerHTML = `\n                        <div class="article-meta mb-3">\n                            <div class="d-flex align-items-center justify-content-between">\n                                <div class="d-flex align-items-center">\n                                    ${article.source_logo ? `\n                                        <img src="${article.source_logo}" alt="${this.escapeHtml(article.source_name)}" class="source-logo me-2">\n                                    ` : ''}\n                                    <div>\n                                        <strong>${this.escapeHtml(article.source_name)}</strong>\n                                        ${article.author ? `<br><small class="text-muted">By ${this.escapeHtml(article.author)}</small>` : ''}\n                                    </div>\n                                </div>\n                                <div class="text-end">\n                                    <div class="text-muted small">${new Date(article.published_at).toLocaleString()}</div>\n                                    <div class="article-stats mt-1">\n                                        <span class="badge bg-light text-dark me-1">\n                                            <i class="bi bi-eye"></i> ${article.views || 0}\n                                        </span>\n                                        <span class="badge bg-light text-dark">\n                                            <i class="bi bi-heart"></i> ${article.likes || 0}\n                                        </span>\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                        \n                        ${article.image_url ? `\n                            <img src="${article.image_url}" class="img-fluid rounded mb-3" alt="${this.escapeHtml(article.title)}">\n                        ` : ''}\n                        \n                        <div class="article-content">\n                            ${article.content || article.summary || 'Content not available.'}\n                        </div>\n                        \n                        ${article.category ? `\n                            <div class="mt-3">\n                                <span class="badge bg-primary">${this.escapeHtml(article.category)}</span>\n                            </div>\n                        ` : ''}\n                    `;

        likeButton.onclick = () => this.likeArticle(articleId);
        readOriginalButton.onclick = () => window.open(article.url, '_blank');

        setTimeout(() => this.loadUserStats(), 1000);
      }
    } catch (error) {
      console.error('Error loading article:', error);
      modalContent.innerHTML = '<div class="alert alert-danger">Failed to load article.</div>';
    }
  }

  async likeArticle(articleId) {
    try {
      const formData = new FormData();
      formData.append('id', articleId);

      const response = await fetch('api/news.php?action=like', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();
      if (data.success) {
        this.showToast('Article liked!', 'success');
        this.showArticleModal(articleId);
      }
    } catch (error) {
      console.error('Error liking article:', error);
      this.showToast('Failed to like article', 'error');
    }
  }

  async loadPopularArticles() {
    try {
      const response = await fetch('api/news.php?action=popular&limit=5');
      const data = await response.json();

      if (data.success) this.renderPopularArticles(data.data);
    } catch (error) {
      console.error('Error loading popular articles:', error);
    }
  }

  renderPopularArticles(articles) {
    const container = document.getElementById('popularArticles');
    if (!container) return;

    if (!articles.length) {
      container.innerHTML = '<p class="text-muted small">No popular articles found.</p>';
      return;
    }

    let html = '';
    articles.forEach((article, index) => {
      html += `\n                    <div class="popular-item" onclick="app.showArticleModal(${article.id})">\n                        <div class="popular-rank">${index + 1}</div>\n                        <div class="popular-content">\n                            <h6 class="popular-title">${this.escapeHtml(article.title)}</h6>\n                            <small class="text-muted">\n                                ${this.escapeHtml(article.source_name)} • ${this.timeAgo(article.published_at)}\n                            </small>\n                            <div class="popular-stats mt-1">\n                                <small class="text-muted">\n                                    <i class="bi bi-eye"></i> ${article.views || 0}\n                                    <i class="bi bi-heart ms-2"></i> ${article.likes || 0}\n                                </small>\n                            </div>\n                        </div>\n                    </div>\n                `;
    });

    container.innerHTML = html;
  }

  async loadTrendingTopics() {
    try {
      const response = await fetch('api/news.php?action=trending');
      const data = await response.json();

      if (data.success) this.renderTrendingTopics(data.data);
    } catch (error) {
      console.error('Error loading trending topics:', error);
    }
  }

  renderTrendingTopics(topics) {
    const container = document.getElementById('trendingTopics');
    if (!container) return;

    if (!topics.length) {
      container.innerHTML = '<p class="text-muted small">No trending topics found.</p>';
      return;
    }

    let html = '';
    topics.forEach(topic => {
      const intensity = Math.min(topic.trend_score / 10 * 100, 100);
      html += `\n                    <div class="trending-item" onclick="app.performSearch('${this.escapeHtml(topic.keyword)}')">\n                        <div class="trending-content">\n                            <span class="trending-keyword">#${this.escapeHtml(topic.keyword)}</span>\n                            <small class="text-muted d-block">${topic.mentions} mentions</small>\n                        </div>\n                        <div class="trending-intensity">\n                            <div class="intensity-bar">\n                                <div class="intensity-fill" style="width: ${intensity}%"></div>\n                            </div>\n                        </div>\n                    </div>\n                `;
    });

    container.innerHTML = html;
  }

  async loadNewsSources() {
    try {
      const response = await fetch('api/news.php?action=sources');
      const data = await response.json();

      if (data.success) this.renderNewsSources(data.data);
    } catch (error) {
      console.error('Error loading news sources:', error);
    }
  }

  renderNewsSources(sources) {
    const container = document.getElementById('newsSources');
    if (!container) return;

    if (!sources.length) {
      container.innerHTML = '<p class="text-muted small">No sources found.</p>';
      return;
    }

    let html = '';
    sources.forEach(source => {
      html += `\n                    <div class="source-item">\n                        ${source.logo_url ? `\n                            <img src="${source.logo_url}" alt="${this.escapeHtml(source.name)}" class="source-logo">\n                        ` : ''}\n                        <div class="source-info">\n                            <div class="source-name">${this.escapeHtml(source.name)}</div>\n                            <small class="text-muted">${this.escapeHtml(source.category)}</small>\n                        </div>\n                    </div>\n                `;
    });

    container.innerHTML = html;
  }

  async loadCategory(category) {
    this.currentCategory = category;
    this.currentOffset = 0;

    document.querySelectorAll('.category-filter button').forEach(btn => {
      btn.classList.remove('active');
      if (btn.dataset.category === category) btn.classList.add('active');
    });

    await this.loadNews(category);
    this.scrollToSection('latest');
  }

  async loadMoreNews() { await this.loadNews(this.currentCategory, this.currentOffset, true); }

  async performSearch(query = null) {
    const searchInput = document.getElementById('searchInput');
    const searchQuery = query || (searchInput ? searchInput.value.trim() : '');

    if (!searchQuery || searchQuery.length < 2) { this.showToast('Please enter at least 2 characters to search', 'warning'); return; }

    if (query && searchInput) searchInput.value = query;

    const searchSection = document.getElementById('searchResults');
    const searchContainer = document.getElementById('searchContainer');
    const latestSection = document.getElementById('latest');

    if (searchContainer) searchContainer.innerHTML = this.getLoadingSpinner();
    if (searchSection) searchSection.classList.remove('d-none');
    if (latestSection) latestSection.classList.add('d-none');

    try {
      const response = await fetch(`api/news.php?action=search&q=${encodeURIComponent(searchQuery)}`);
      const data = await response.json();

      if (data.success) this.renderSearchResults(data.data);
    } catch (error) {
      console.error('Error searching:', error);
      if (searchContainer) searchContainer.innerHTML = '<div class="alert alert-danger">Search failed. Please try again.</div>';
    }
  }

  renderSearchResults(articles) {
    const container = document.getElementById('searchContainer');
    if (!container) return;
    if (!articles.length) { container.innerHTML = '<div class="alert alert-info">No articles found for your search.</div>'; return; }
    let html = '';
    articles.forEach(article => html += this.createArticleCard(article));
    container.innerHTML = html;
  }

  clearSearch() { if (document.getElementById('searchInput')) document.getElementById('searchInput').value = ''; if (document.getElementById('searchResults')) document.getElementById('searchResults').classList.add('d-none'); if (document.getElementById('latest')) document.getElementById('latest').classList.remove('d-none'); }

  async loadUserStats() {
    try {
      const response = await fetch('api/news.php?action=stats');
      const data = await response.json();

      if (data.success && data.data) document.getElementById('articlesViewed').textContent = data.data.articles_viewed || 0;
    } catch (error) { console.error('Error loading user stats:', error); }
  }

  scrollToSection(sectionId) { const element = document.getElementById(sectionId); if (element) element.scrollIntoView({ behavior: 'smooth' }); }

  toggleTheme() { const currentTheme = document.body.getAttribute('data-theme') || 'light'; const newTheme = currentTheme === 'light' ? 'dark' : 'light'; this.applyTheme(newTheme); localStorage.setItem('theme', newTheme); }

  applyTheme(theme) { document.body.setAttribute('data-theme', theme); const themeIcon = document.getElementById('themeIcon'); if (themeIcon) themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars'; }

  isNearBottom() { return window.innerHeight + window.scrollY >= document.body.offsetHeight - 1000; }

  timeAgo(dateString) { const now = new Date(); const past = new Date(dateString); const diffInSeconds = Math.floor((now - past) / 1000); if (diffInSeconds < 60) return 'just now'; if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`; if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`; if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)}d ago`; if (diffInSeconds < 31104000) return `${Math.floor(diffInSeconds / 2592000)}mo ago`; return `${Math.floor(diffInSeconds / 31104000)}y ago`; }

  getLoadingSpinner() { return `\n                <div class="text-center py-4">\n                    <div class="spinner-border text-primary" role="status">\n                        <span class="visually-hidden">Loading...</span>\n                    </div>\n                </div>\n            `; }

  showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container') || this.createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0`;
    toast.setAttribute('role', 'alert');

    toast.innerHTML = `\n                <div class="d-flex">\n                    <div class="toast-body">${this.escapeHtml(message)}</div>\n                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>\n                </div>\n            `;

    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => { toast.remove(); });
  }

  createToastContainer() { const container = document.createElement('div'); container.className = 'toast-container position-fixed bottom-0 end-0 p-3'; container.style.zIndex = '11'; document.body.appendChild(container); return container; }

  escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  // Daily picks: fetch, render single pick, and navigation
  async loadDailyPicks(date = null, limit = 50) {
    const pickDate = date || new Date().toISOString().split('T')[0];
    try {
      const response = await fetch(`api/news.php?action=dailypicks&date=${encodeURIComponent(pickDate)}&limit=${limit}`);
      const data = await response.json();
      if (data.success) {
        this.dailyPicks = data.data || [];
        this.dailyPickIndex = 0;
        this.renderCurrentDailyPick();
      }
    } catch (err) {
      console.error('Error loading daily picks:', err);
    }
  }

  renderCurrentDailyPick() {
    const container = document.getElementById('dailyPicksContainer') || document.getElementById('dailyPicks');
    if (!container) return;

    if (!this.dailyPicks.length) {
      container.innerHTML = '<div class="alert alert-info">No daily picks available for today.</div>';
      return;
    }

    const pick = this.dailyPicks[this.dailyPickIndex];
    if (!pick) {
      container.innerHTML = '<div class="alert alert-info">No pick to display.</div>';
      return;
    }

    const article = pick; // API returns joined article fields
    const timeAgo = this.timeAgo(article.published_at);

    let html = `
      <div class="card daily-pick-card">
        ${article.image_url ? `<img src="${article.image_url}" class="card-img-top" alt="${this.escapeHtml(article.title)}">` : ''}
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="card-subtitle mb-1 text-muted">${this.escapeHtml(article.source_name)} • ${timeAgo}</h6>
              <h5 class="card-title">${this.escapeHtml(article.title)}</h5>
            </div>
            <div class="ms-2 text-end">
              <div class="btn-group" role="group" aria-label="Daily picks navigation">
                <button id="dailyPickPrev" type="button" class="btn btn-sm btn-outline-primary" ${this.dailyPickIndex === 0 ? 'disabled' : ''} onclick="window.prevDailyPick()">Prev</button>
                <button id="dailyPickNext" type="button" class="btn btn-sm btn-primary ms-1" ${this.dailyPickIndex >= this.dailyPicks.length - 1 ? 'disabled' : ''} onclick="window.nextDailyPick()">Next</button>
              </div>
            </div>
          </div>

          <p class="card-text mt-2">${this.escapeHtml(article.summary || article.content || '')}</p>

          <div class="mt-3 d-flex justify-content-between">
            <div>
              ${article.category ? `<span class="badge bg-primary">${this.escapeHtml(article.category)}</span>` : ''}
            </div>
            <div>
              <button class="btn btn-sm btn-outline-secondary me-2" onclick="app.showArticleModal(${article.id})">Open</button>
              <a class="btn btn-sm btn-outline-primary" href="${this.escapeHtml(article.url)}" target="_blank" rel="noopener">Read Original</a>
            </div>
          </div>
        </div>
      </div>
    `;

    container.innerHTML = html;
    // update button disabled states
    const prevBtn = document.getElementById('dailyPickPrev');
    const nextBtn = document.getElementById('dailyPickNext');
    if (prevBtn) prevBtn.disabled = this.dailyPickIndex === 0;
    if (nextBtn) nextBtn.disabled = this.dailyPickIndex >= this.dailyPicks.length - 1;
  }

  nextDailyPick() {
    if (this.dailyPickIndex < this.dailyPicks.length - 1) {
      this.dailyPickIndex += 1;
      this.renderCurrentDailyPick();
    } else {
      this.showToast('No more picks for today', 'info');
    }
  }

  prevDailyPick() {
    if (this.dailyPickIndex > 0) {
      this.dailyPickIndex -= 1;
      this.renderCurrentDailyPick();
    }
  }
}

// Initialize app on DOMContentLoaded and expose global helpers used in HTML
document.addEventListener('DOMContentLoaded', () => {
  window.app = new NewsAggregatorApp();
  window.app.init();

  window.loadCategory = (category) => window.app.loadCategory(category);
  window.performSearch = (query) => window.app.performSearch(query);
  window.clearSearch = () => window.app.clearSearch();
  window.loadMoreNews = () => window.app.loadMoreNews();
  window.scrollToSection = (section) => window.app.scrollToSection(section);
  window.toggleTheme = () => window.app.toggleTheme();
  window.nextDailyPick = () => window.app.nextDailyPick();
  window.prevDailyPick = () => window.app.prevDailyPick();
});

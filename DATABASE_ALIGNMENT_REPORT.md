# 🔍 Database & Code Alignment Report

**Generated:** October 14, 2025  
**Project:** News Aggregator Application

---

## ✅ ALIGNMENT STATUS: **FIXED**

All database schema and code mismatches have been identified and resolved.

---

## 📊 Summary of Issues Found & Fixed

### **Issue #1: Database Name Mismatch** ✅ FIXED
- **Problem:** `database.sql` was creating `news_aggregator_app` but `config.php` uses `news_aggregator`
- **Impact:** Database wouldn't be found, causing connection errors
- **Solution:** Updated `database.sql` to use `news_aggregator`

### **Issue #2: Missing Users Table** ✅ FIXED
- **Problem:** `database.sql` had NO users table, but `Auth.php` requires it
- **Impact:** Registration/Login would fail with "Table doesn't exist" error
- **Solution:** Added `users` table to `database.sql`

### **Issue #3: Articles Table Column Mismatch** ✅ FIXED
- **Problem:** 
  - Original `database.sql` used: `description`, `url_to_image`
  - `NewsApi.php` code uses: `summary`, `image_url`
  - Missing columns: `views`, `likes`, `scraped_at`
- **Impact:** Articles couldn't be stored, SELECT queries would fail
- **Solution:** Standardized on `summary` and `image_url` in database.sql

---

## 🗄️ Current Database Schema (ALIGNED)

### Database Name: `news_aggregator`

### Table: `users`
```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    role ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Used by:**
- `api/Auth.php` (register, login, logout)
- `register.php` (user registration)
- `login.php` (user authentication)

---

### Table: `articles`
```sql
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    summary TEXT,                    -- Used for description
    content TEXT,
    url VARCHAR(1000) NOT NULL UNIQUE,
    image_url VARCHAR(1000),         -- Used for article images
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
);
```

**Used by:**
- `api/NewsApi.php` (INSERT, SELECT, UPDATE)
- `index.php` (display articles)
- `admin.php` (manage articles)

**Column Mapping:**
- `summary` ← Maps to NewsAPI's `description`
- `image_url` ← Maps to NewsAPI's `urlToImage`

---

### Table: `article_views`
```sql
CREATE TABLE article_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    view_count INT DEFAULT 1,
    last_viewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
);
```

**Used by:**
- `api/NewsApi.php` (trackView method)
- `track_view.php` (AJAX view tracking)
- `index.php` (display view counts)

---

### Table: `sources`
```sql
CREATE TABLE sources (
    id VARCHAR(100) PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    url VARCHAR(1000),
    category VARCHAR(50),
    language VARCHAR(10),
    country VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Used by:**
- Future enhancement for managing news sources
- Sample data includes: BBC News, CNN, TechCrunch

---

### Table: `user_preferences`
```sql
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255),
    preferred_categories JSON,
    preferred_sources JSON,
    preferred_country VARCHAR(10) DEFAULT 'us',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Used by:**
- Future enhancement for user personalization
- Stores category and country preferences

---

## 🔄 Code-to-Database Mapping

### NewsApi.php → articles table

**INSERT Operation (storeArticle method):**
```php
INSERT INTO articles 
(title, summary, content, url, image_url, published_at, author, category)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
```

**Mapping:**
- `title` ← `$article['title']`
- `summary` ← `$article['description']` (from NewsAPI)
- `content` ← `$article['content']`
- `url` ← `$article['url']`
- `image_url` ← `$article['urlToImage']` (from NewsAPI)
- `published_at` ← `$article['publishedAt']`
- `author` ← `$article['author']`
- `category` ← Parameter

**SELECT Operation (getArticles method):**
```php
SELECT * FROM articles WHERE 1=1
```

**Output Mapping:**
```php
'description' => $r['summary']      // Frontend expects 'description'
'url_to_image' => $r['image_url']   // Frontend expects 'url_to_image'
'image_url' => $r['image_url']      // Both formats supported
```

---

### Auth.php → users table

**INSERT Operation (register method):**
```php
INSERT INTO users (username, password_hash, email, role) 
VALUES (?, ?, ?, ?)
```

**SELECT Operation (login method):**
```php
SELECT * FROM users WHERE username = ? LIMIT 1
```

---

## 🎯 Setup Instructions

### Option 1: Run setup_database.php (RECOMMENDED)
1. Open browser: `http://localhost/news-aggregator/setup_database.php`
2. Script will create all tables automatically
3. Shows detailed status report
4. Ready to use immediately

### Option 2: Manual SQL Import
1. Open phpMyAdmin
2. Import `database.sql` file
3. Verify all 5 tables are created:
   - ✅ users
   - ✅ articles
   - ✅ article_views
   - ✅ sources
   - ✅ user_preferences

---

## ✅ Verification Checklist

Run these checks to ensure everything is aligned:

### 1. Database Connection
```php
// In config.php, verify:
define('DB_NAME', 'news_aggregator');  // ✅ Matches database.sql
```

### 2. Tables Exist
```sql
SHOW TABLES FROM news_aggregator;
-- Should show: users, articles, article_views, sources, user_preferences
```

### 3. Users Table
```sql
DESCRIBE users;
-- Should have: id, username, password_hash, email, role, created_at
```

### 4. Articles Table
```sql
DESCRIBE articles;
-- Should have: summary (NOT description)
-- Should have: image_url (NOT url_to_image)
-- Should have: views, likes columns
```

### 5. Test Registration
```
http://localhost/news-aggregator/register.php
-- Should work without "Table doesn't exist" error
```

### 6. Test Article Fetching
```
http://localhost/news-aggregator/test_newsapi_class.php
-- Should fetch AND store articles successfully
```

---

## 🐛 Common Issues & Solutions

### Issue: "Table 'news_aggregator.users' doesn't exist"
**Solution:** Run `setup_database.php` or import `database.sql`

### Issue: Articles fetch but don't store
**Solution:** Already fixed! Database now uses `summary` and `image_url` columns

### Issue: "Access denied for user 'root'@'localhost'"
**Solution:** Check `config.php` database credentials

### Issue: "Unknown database 'news_aggregator'"
**Solution:** Create database first or run `database.sql`

---

## 📝 Key Takeaways

1. **Database Name:** `news_aggregator` (consistent everywhere)
2. **Column Names:** Use `summary` and `image_url` (not description/url_to_image)
3. **Users Table:** Now included in database.sql
4. **Setup Method:** Use `setup_database.php` for easy installation

---

## 🔍 Files Checked & Aligned

- ✅ `database.sql` - Fixed database name, added users table, aligned columns
- ✅ `config.php` - Database name matches
- ✅ `api/NewsApi.php` - Column names match database schema
- ✅ `api/Auth.php` - Works with users table
- ✅ `setup_database.php` - Creates all tables correctly
- ✅ `index.php` - Expects correct column names from getArticles()

---

## 🚀 Next Steps

1. **Run setup_database.php** to create all tables
2. **Register** your first admin account
3. **Test NewsAPI** by clicking "Fetch Latest" button
4. **Verify** articles are stored and displayed

---

**Status:** 🟢 ALL SYSTEMS ALIGNED  
**Last Updated:** October 14, 2025

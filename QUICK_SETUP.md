# 🚀 Quick Setup Guide

## Step-by-Step Setup (5 Minutes)

### 1️⃣ Create Database Tables
Open your browser and visit:
```
http://localhost/news-aggregator/setup_database.php
```

This will automatically create all 5 tables:
- ✅ users (for login/registration)
- ✅ articles (for news storage)
- ✅ article_views (for tracking views)
- ✅ sources (for news sources)
- ✅ user_preferences (for user settings)

### 2️⃣ Register Admin Account
```
http://localhost/news-aggregator/register.php
```

Create your admin account:
- Username: (your choice)
- Email: (optional)
- Password: (min 6 characters)

### 3️⃣ Test NewsAPI
```
http://localhost/news-aggregator/test_newsapi_class.php
```

This will:
- ✅ Verify API key is working
- ✅ Fetch sample articles
- ✅ Store them in database
- ✅ Show detailed results

### 4️⃣ Browse News
```
http://localhost/news-aggregator/index.php
```

- Click "Fetch Latest" to get fresh articles
- Browse by category (Tech, Business, Sports, etc.)
- Search for specific topics
- View trending articles

---

## 🔧 Configuration Files

### config.php
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'news_aggregator');  // ✅ Correct database name
define('NEWS_API_KEY', '67f22648bef641a2bafffb36c62c20e3');  // ✅ Your API key
```

---

## 📊 Database Schema Overview

### Articles Table (Main Storage)
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Primary key |
| title | VARCHAR(500) | Article headline |
| **summary** | TEXT | Article description ⚠️ |
| content | TEXT | Full article text |
| url | VARCHAR(1000) | Article URL |
| **image_url** | VARCHAR(1000) | Article image ⚠️ |
| published_at | DATETIME | Publish date |
| author | VARCHAR(200) | Author name |
| category | VARCHAR(50) | Category (tech, sports, etc.) |
| views | INT | View count |
| likes | INT | Like count |

⚠️ **Important:** Uses `summary` and `image_url` (not description/url_to_image)

### Users Table (Authentication)
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Primary key |
| username | VARCHAR(100) | Login username |
| password_hash | VARCHAR(255) | Hashed password |
| email | VARCHAR(255) | Email address |
| role | ENUM | admin/editor/user |

---

## ✅ Alignment Status

| Component | Status | Notes |
|-----------|--------|-------|
| Database Name | ✅ Fixed | Now uses `news_aggregator` |
| Users Table | ✅ Added | Registration/login will work |
| Articles Columns | ✅ Aligned | Uses `summary` & `image_url` |
| NewsAPI Integration | ✅ Working | API key verified |
| Setup Script | ✅ Ready | Run setup_database.php |

---

## 🐛 Troubleshooting

### Error: "Table doesn't exist"
**Solution:** Run `setup_database.php`

### Error: "Articles not storing"
**Solution:** Already fixed! Database uses correct column names now.

### Error: "Cannot connect to database"
**Solution:** 
1. Start XAMPP (Apache + MySQL)
2. Check config.php credentials

### Error: "API key invalid"
**Solution:** Your API key is already configured and working!

---

## 📁 Project Structure

```
news-aggregator/
├── api/
│   ├── Auth.php          ✅ Uses 'users' table
│   └── NewsApi.php       ✅ Uses 'articles' table with summary/image_url
├── config.php            ✅ Database: news_aggregator
├── database.sql          ✅ Creates all 5 tables correctly
├── setup_database.php    ✅ Automated setup script
├── register.php          ✅ User registration
├── login.php             ✅ User login
├── index.php             ✅ Main news page
└── test_newsapi_class.php ✅ API testing
```

---

## 🎯 What's Fixed

1. ✅ Database name changed from `news_aggregator_app` to `news_aggregator`
2. ✅ Added `users` table to database.sql
3. ✅ Aligned articles table columns (`summary`, `image_url`)
4. ✅ Added missing columns (`views`, `likes`, `scraped_at`)
5. ✅ All INSERT/SELECT queries match database schema

---

## 🚀 You're Ready!

Everything is now aligned. Just run:

1. `http://localhost/news-aggregator/setup_database.php`
2. `http://localhost/news-aggregator/register.php`
3. `http://localhost/news-aggregator/index.php`

Enjoy your news aggregator! 🎉

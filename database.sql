-- News Aggregator Database Setup
-- Run this in phpMyAdmin to create the database structure

CREATE DATABASE IF NOT EXISTS news_aggregator;
USE news_aggregator;

-- Table for users (authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    role ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing news articles
CREATE TABLE IF NOT EXISTS articles (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing news sources
CREATE TABLE IF NOT EXISTS sources (
    id VARCHAR(100) PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    url VARCHAR(1000),
    category VARCHAR(50),
    language VARCHAR(10),
    country VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for user preferences (for future enhancement)
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255),
    preferred_categories JSON,
    preferred_sources JSON,
    preferred_country VARCHAR(10) DEFAULT 'us',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for tracking popular articles
CREATE TABLE IF NOT EXISTS article_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    view_count INT DEFAULT 1,
    last_viewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some default categories for better organization
INSERT INTO user_preferences (session_id, preferred_categories, preferred_country) VALUES 
('default', '["general", "technology", "business", "health", "science"]', 'us');

-- Sample data insertion (you can remove this after testing)
INSERT INTO sources (id, name, description, category, language, country) VALUES 
('bbc-news', 'BBC News', 'Use BBC News for up-to-the-minute news, breaking news, video, audio and feature stories.', 'general', 'en', 'gb'),
('cnn', 'CNN', 'View the latest news and breaking news today for U.S., world, weather, entertainment, politics and health.', 'general', 'en', 'us'),
('techcrunch', 'TechCrunch', 'TechCrunch is a leading technology media property, dedicated to obsessively profiling startups.', 'technology', 'en', 'us');
# NewsHub Pro - News Aggregator

A modern PHP-based news aggregator that fetches and displays news articles from various sources using the NewsAPI. Features include real-time news fetching, category filtering, search functionality, and user management.

## Features

- 📰 Real-time news aggregation from multiple sources
- 🔍 Search functionality across all articles
- 📱 Responsive design for mobile and desktop
- 🎯 Category-based filtering (Technology, Business, Health, Sports, etc.)
- 🌍 Country-based news filtering
- 👤 User registration and authentication
- 📊 Admin panel with analytics
- 💾 Local article caching for improved performance
- 📈 Article view tracking and trending articles

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **API**: NewsAPI.org integration
- **Server**: Apache/Nginx (XAMPP recommended for development)

## Prerequisites

Before setting up this project, ensure you have the following installed:

1. **Web Server Environment**:
   - [XAMPP](https://www.apachefriends.org/download.html) (recommended) or
   - WAMP/LAMP/MAMP stack
   - PHP 7.4 or higher
   - MySQL 5.7 or higher

2. **NewsAPI Key**:
   - Get a free API key from [NewsAPI.org](https://newsapi.org/register)

## Installation & Setup

### Step 1: Clone the Repository

```bash
git clone https://github.com/musondaAlexander/news-aggregator.git
cd news-aggregator
```

### Step 2: Server Setup

#### Option A: Using XAMPP (Recommended)

1. **Download and Install XAMPP**:
   - Download from [Apache Friends](https://www.apachefriends.org/download.html)
   - Install XAMPP to `C:\xampp` (Windows) or `/Applications/XAMPP` (Mac)

2. **Place Project Files**:
   - Copy the project folder to `C:\xampp\htdocs\news-aggregator` (Windows)
   - Or `/Applications/XAMPP/htdocs/news-aggregator` (Mac/Linux)

3. **Start Services**:
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL** services

#### Option B: Using Other Web Servers

- Ensure your web server points to the project directory
- Make sure PHP and MySQL services are running

### Step 3: Database Setup

1. **Access phpMyAdmin**:
   - Open your browser and go to `http://localhost/phpmyadmin`
   - Login with default credentials (usually no password for XAMPP)

2. **Create Database**:
   - Click "New" to create a new database
   - Name it `news_aggregator` (or run the SQL commands below)

3. **Import Database Structure**:
   
   **Option A: Using phpMyAdmin**
   - Select your database
   - Click "Import" tab
   - Choose file: `database.sql`
   - Click "Go"

   **Option B: Using SQL Commands**
   - Copy and paste the contents of `database.sql` into phpMyAdmin SQL tab
   - Execute the commands

4. **Run Migrations** (Optional but recommended):
   - Execute the SQL in `database_migrations/001_create_users_table.sql`

### Step 4: Configuration

1. **Update Database Configuration**:
   - Open `config.php`
   - Verify database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');          // Your MySQL username
   define('DB_PASS', '');              // Your MySQL password (empty for XAMPP)
   define('DB_NAME', 'news_aggregator'); // Database name
   ```

2. **Configure NewsAPI**:
   - Open `config.php`
   - Replace the API key with your own:
   ```php
   define('NEWS_API_KEY', 'your_actual_api_key_here');
   ```

3. **Update Admin Credentials** (Important for Security):
   - In `config.php`, change the admin password:
   ```php
   define('ADMIN_PASS_HASH', password_hash('your_secure_password', PASSWORD_DEFAULT));
   ```

### Step 5: File Permissions (Linux/Mac only)

```bash
# Make sure web server can write to necessary directories
sudo chmod 755 /path/to/news-aggregator
sudo chown -R www-data:www-data /path/to/news-aggregator
```

## Running the Application

1. **Start Your Web Server**:
   - For XAMPP: Start Apache and MySQL from the control panel
   - For other setups: Ensure your web server and database are running

2. **Access the Application**:
   - Open your browser
   - Navigate to: `http://localhost/news-aggregator`

3. **Initial Setup**:
   - The application will automatically fetch some initial news articles
   - You can register a new user account or use admin credentials

## Usage

### User Features

1. **Browse News**:
   - Visit the homepage to view latest articles
   - Use category filters to browse by topic
   - Use country filters to get region-specific news

2. **Search Articles**:
   - Use the search bar to find specific articles
   - Search works across titles, descriptions, and content

3. **User Account**:
   - Register at `/register.php`
   - Login at `/login.php`
   - Manage preferences and view history

### Admin Features

1. **Access Admin Panel**:
   - Go to `/admin.php`
   - Login with admin credentials (default: admin/changeme)

2. **Admin Functions**:
   - View system statistics
   - Manage articles and sources
   - Monitor user activity
   - Force refresh of news articles

## API Endpoints

The application provides several API endpoints:

- `POST /api/Auth.php` - User authentication
- `GET /api/NewsApi.php` - Fetch news articles
- `POST /index.php` (with fetch_articles) - Refresh news data

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Verify MySQL is running
   - Check database credentials in `config.php`
   - Ensure database exists

2. **NewsAPI Not Working**:
   - Verify your API key is valid
   - Check if you've exceeded API limits (free tier: 500 requests/day)
   - Ensure internet connection is active

3. **Permission Denied Errors**:
   - Check file permissions (Linux/Mac)
   - Ensure web server has read/write access

4. **Page Not Found (404)**:
   - Verify Apache mod_rewrite is enabled
   - Check if project is in the correct directory

### Debug Mode

To enable debug mode:
1. In `config.php`, add:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Configuration Options

### News Categories
The application supports these categories:
- General, Business, Entertainment, Health
- Science, Sports, Technology

### Supported Countries
- US, UK, Canada, Australia, Germany
- France, Japan, India

Modify `$categories` and `$countries` arrays in `config.php` to customize.

## Development

### Project Structure
```
news-aggregator/
├── api/                    # API classes and endpoints
├── assets/                 # CSS, JS, and image files
├── database_migrations/    # Database migration files
├── config.php             # Main configuration file
├── index.php              # Homepage
├── admin.php              # Admin panel
├── login.php              # User login
├── register.php           # User registration
├── database.sql           # Database structure
└── README.md              # This file
```

### Adding New Features
1. Follow the existing code structure
2. Update database schema if needed
3. Add appropriate error handling
4. Test thoroughly before deployment

## Security Considerations

1. **Change Default Passwords**: Update admin credentials immediately
2. **API Key Security**: Keep your NewsAPI key private
3. **Database Security**: Use strong database passwords in production
4. **HTTPS**: Use SSL certificates in production
5. **Input Validation**: All user inputs are sanitized

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For issues and questions:
1. Check the troubleshooting section above
2. Create an issue on GitHub
3. Ensure you include error messages and system details

## Credits

- News data provided by [NewsAPI.org](https://newsapi.org)
- Built with PHP and modern web technologies
- Responsive design for optimal user experience

---

**Note**: Remember to keep your NewsAPI key secure and never commit it to public repositories. Consider using environment variables for production deployments.
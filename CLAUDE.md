# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Fishery Data Management System (渔业数据管理系统) - A lightweight table editing system with authentication for managing fishery landing records, purchase records, and sales data.

**Tech Stack:**
- Frontend: Native HTML5 + CSS3 + Vanilla JavaScript (no frameworks)
- Backend: Native PHP 8.2.28 (no frameworks)
- Database: MySQL (database: `table_editor`)
- Web Server: Nginx (宝塔面板/BT Panel managed)

**Project Location:** `/www/wwwroot/table-editor/`
**Public URL:** Typically accessed via `/public/` subdirectory

## Architecture

### Directory Structure

```
/www/wwwroot/table-editor/
├── api/                      # PHP API endpoints
│   ├── login.php            # User authentication
│   ├── logout.php           # Session cleanup
│   ├── table.php            # Generic table CRUD operations
│   ├── landing.php          # Landing records operations
│   ├── purchase.php         # Purchase records operations
│   ├── sales.php            # Sales records operations
│   ├── seafood.php          # Seafood data operations
│   └── landing-options.php  # Landing form dropdown options
├── config/
│   └── db.php               # Database connection configuration
├── database/
│   ├── schema.sql           # Original database schema (users + sample_data)
│   └── fishery_schema.sql   # Fishery system schema (landing/purchase/sales tables)
├── public/                  # Web-accessible files (document root)
│   ├── index.html           # Main application entry (redirects to public/)
│   ├── css/
│   │   └── style.css        # Main stylesheet
│   ├── js/
│   │   ├── app.js           # Core application logic
│   │   ├── landing.js       # Landing records page
│   │   ├── purchase.js      # Purchase records page
│   │   └── sales.js         # Sales records page
│   ├── monthly-report.html  # Monthly report page
│   └── purchase-landing.html # Purchase-landing bridge page
└── README.md                # Deployment documentation (Chinese)
```

### Database Tables

**Core Tables:**
- `users` - User authentication (password hashing with `password_hash`)
- `landing_records` - Fish landing records (date, fish_type, quantity, unit_price, supplier, boat, location)
- `purchase_records` - Purchase records (date, item_name, category, quantity, supplier, payment_status)
- `sales_records` - Sales records
- `tblSuppliers` - Supplier reference data
- `tblStock` - Stock inventory
- `tblBoat` - Boat/vessel reference data
- `tblPort` - Port/location reference data

## Development Commands

### Database Operations

```bash
# Access MySQL console
mysql -u root -p

# Import database schema
mysql -u root -p table_editor < /www/wwwroot/table-editor/database/fishery_schema.sql

# Check database status
mysql -u root -p -e "USE table_editor; SHOW TABLES;"
```

### PHP Service (宝塔/BT Panel)

```bash
# Check PHP-FPM status
/etc/init.d/php-fpm-82 status

# Restart PHP-FPM
/etc/init.d/php-fpm-82 restart

# Check PHP error log
tail -f /www/server/php/82/var/log/php-fpm.log
```

### Nginx/Web Server

```bash
# Test Nginx configuration
nginx -t

# Reload Nginx
systemctl reload nginx

# Check Nginx error log
tail -f /www/server/nginx/logs/error.log
```

### File Permissions

```bash
# Set correct ownership (宝塔/BT Panel standard)
chown -R www:www /www/wwwroot/table-editor

# Set correct permissions
find /www/wwwroot/table-editor -type d -exec chmod 755 {} \;
find /www/wwwroot/table-editor -type f -exec chmod 644 {} \;
```

### Testing API Endpoints

```bash
# Test login endpoint
curl -X POST http://your-domain/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"123456"}'

# Test table list endpoint
curl -X GET http://your-domain/api/table.php?action=list

# Check PHP syntax
php -l /www/wwwroot/table-editor/api/login.php
```

## Code Architecture

### Backend API Pattern

All PHP API files follow a consistent pattern:
1. Start session with `session_start()`
2. Include database config: `require_once '../config/db.php';`
3. Set JSON header: `header('Content-Type: application/json');`
4. Get action from `$_GET['action']` or `$_POST['action']`
5. Switch/case on action to route requests
6. Return JSON responses with `success`, `message`, and `data` fields

**Database Connection:**
- Singleton pattern via `Database::getInstance()`
- PDO-based with prepared statements
- Located in `config/db.php`

### Frontend Pattern

**Vanilla JavaScript Architecture:**
- Global state management with simple objects
- Direct DOM manipulation via `document.getElementById` and `querySelector`
- `fetch()` API for AJAX calls to `/api/*.php` endpoints
- LayUI framework loaded from CDN (https://unpkg.com/layui@2.8.0/)
- Session-based authentication via PHPSESSID cookie

**Common Frontend Operations:**
- `renderTable(data)` - Renders data tables
- `showModal(content)` - Shows LayUI modal dialogs
- `loadData()` - Fetches data from API
- `handleSubmit()` - Forms POST to API endpoints

### Authentication Flow

1. User submits credentials to `/api/login.php`
2. Backend verifies username/password_hash
3. On success, creates `$_SESSION['user_id']` and `$_SESSION['username']`
4. Frontend stores user info in `localStorage`
5. All subsequent API calls check `$_SESSION['user_id']`
6. Logout clears session via `/api/logout.php`

## Important Configuration Files

### Environment Variables (.env)
- **Location:** `/www/wwwroot/table-editor/.env`
- **Purpose:** Store sensitive configuration (NOT committed to git)
- **Template:** `deploy-package/config/.env.template`

**Environment Variables:**
| Variable | Description |
|----------|-------------|
| `DB_HOST` | MySQL host (default: localhost) |
| `DB_PORT` | MySQL port (default: 3306) |
| `DB_NAME` | Database name (default: seafood) |
| `DB_USER` | MySQL username |
| `DB_PASS` | MySQL password |
| `GMAIL_USERNAME` | Gmail SMTP username |
| `GMAIL_PASSWORD` | Gmail SMTP app password |

### Database Configuration
- **Location:** `config/db.php`
- **Contains:** Database connection configuration (reads from .env)
- **Class:** `Database` with static `getConnection()` method

### Nginx Configuration (宝塔)
- **Site Root:** Usually `/www/wwwroot/table-editor/public` or parent directory
- **PHP Version:** 8.2 (FPM)
- **Permission User:** www:www

## Testing Accounts

**Default Test Users:**
- `admin` / `123456`
- `testuser` / `123456`

**Security Note:** Change these passwords in production using PHP's `password_hash()`

## Common Tasks

### Adding a New API Endpoint

1. Create new PHP file in `/api/`
2. Include database config: `require_once '../config/db.php';`
3. Implement action routing with `switch($_GET['action'])`
4. Return JSON: `echo json_encode(['success' => true, 'data' => $result]);`

### Adding a New Page

1. Create HTML file in `/public/`
2. Copy structure from existing pages (use LayUI for styling)
3. Create corresponding JS file in `/public/js/`
4. Load LayUI and other dependencies via CDN in `<head>`

### Modifying Database Schema

1. Edit SQL in `/database/fishery_schema.sql`
2. Import changes: `mysql -u root -p table_editor < /www/wwwroot/table-editor/database/fishery_schema.sql`
3. Update corresponding API endpoints in `/api/*.php`
4. Update frontend table rendering in `/public/js/*.js`

## External Dependencies

**Frontend CDNs:**
- LayUI CSS/JS: `https://unpkg.com/layui@2.8.0/`
- XLSX (Excel export): `https://unpkg.com/xlsx@0.18.5/`
- html2canvas: `https://unpkg.com/html2canvas@1.4.1/`
- jsPDF: `https://unpkg.com/jspdf@2.5.1/`

**Note:** All loaded directly in HTML files without build tools

## Debugging

### Enable PHP Error Display
Edit affected PHP file temporarily:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Check Logs
```bash
# PHP errors
tail -f /www/server/php/82/var/log/php-fpm.log

# Nginx errors
tail -f /www/server/nginx/logs/error.log

# MySQL slow queries
tail -f /var/log/mysql/slow-query.log
```

### Browser Console
- Open DevTools (F12)
- Check Network tab for API responses
- Check Console for JavaScript errors
- Verify PHPSESSID cookie is set

## Deployment Notes

- Server runs on 宝塔 (BT Panel) - a popular Chinese server management panel
- PHP 8.2.28 via PHP-FPM
- MySQL managed via 宝塔 panel or CLI
- Document root typically points to `/public/` subdirectory
- Files owned by `www:www` user (Nginx standard)
- Session storage: `/tmp` or `/var/lib/php/sessions` (PHP default)

## Language & Localization

- UI Language: Chinese (Simplified)
- Database Charset: `utf8mb4_unicode_ci`
- All comments and documentation in Chinese
- Date format: YYYY-MM-DD (ISO 8601)

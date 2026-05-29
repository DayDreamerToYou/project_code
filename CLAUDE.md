# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Fishery Data Management System (渔业数据管理系统) - A lightweight table editing system with authentication for managing fishery landing records, purchase records, and sales data.

**Tech Stack:**
- Frontend: HTML5 + CSS3 + Vanilla JavaScript + Tailwind CSS 3
- Backend: Native PHP 8.2.28 (no frameworks)
- Database: MySQL (database: `seafood`)
- Web Server: Nginx (宝塔面板/BT Panel managed)

**Project Location:** `/www/wwwroot/table-editor/`
**Public URL:** Typically accessed via `/public/` subdirectory
**Local Development:** `php -S localhost:8080 -t public`

## Architecture

### System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         用户浏览器                               │
│  ┌─────────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐        │
│  │ Tailwind CSS│  │ XLSX.js │  │ ECharts │  │ jsPDF   │        │
│  │ (主版本UI)  │  │ (Excel) │  │ (图表)  │  │ (PDF)   │        │
│  └─────────────┘  └─────────┘  └─────────┘  └─────────┘        │
└─────────────────────────────────────────────────────────────────┘
                              │ HTTP/HTTPS
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Nginx Web Server                           │
│                    (宝塔面板管理)                                │
│         root: /www/wwwroot/table-editor/public                  │
└─────────────────────────────────────────────────────────────────┘
                              │ FastCGI
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      PHP-FPM 8.2.28                              │
│    ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐        │
│    │login.php │ │landing..│ │purchase..│ │ sales.php│  ...    │
│    └──────────┘ └──────────┘ └──────────┘ └──────────┘        │
└─────────────────────────────────────────────────────────────────┘
                              │ PDO
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      MySQL Database                              │
│     users │ tblLanding │ tblPurchase │ tblSales │ tblStock    │
└─────────────────────────────────────────────────────────────────┘
```

### Directory Structure

```
/www/wwwroot/table-editor/
├── api/                          # PHP API endpoints
│   ├── login.php                # User authentication
│   ├── logout.php               # Session cleanup
│   ├── landing.php              # Landing records CRUD
│   ├── purchase.php             # Purchase records CRUD
│   ├── sales.php                # Sales records CRUD
│   ├── data-management.php      # 基础数据管理
│   ├── fleet.php                # 船队管理 (含船只关联)
│   ├── boat-management.php      # 船舶管理 (含船队关联)
│   ├── supplier-stock-price.php # 供应商定价
│   ├── printer.php              # 打印机管理
│   ├── units.php                # 单位管理
│   ├── landing-to-purchase.php  # 到货转采购
│   ├── purchase-to-sales.php    # 采购转销售
│   ├── monthly-statistics.php   # 月度统计
│   ├── send-email.php           # 发送邮件
│   └── landing-options.php      # 到货下拉选项 (含供应商-船只映射)
├── config/
│   └── db.php                   # Database connection configuration
├── database/
│   ├── schema.sql               # Original database schema
│   ├── fishery_schema.sql       # Fishery system schema
│   └── seafood_backup_20260409.sql  # Database backup
├── lib/
│   └── PHPMailer/               # 邮件发送库
├── public/                      # Web-accessible files (document root)
│   ├── index.html               # Main application entry (Tailwind CSS 新版) ⭐
│   ├── index-layui.html         # Main application entry (Layui 旧版)
│   ├── data-management.html     # 基础数据管理 (Layui 旧版)
│   ├── print-landing.html       # Landing print page
│   ├── print-bill.html          # Bill print page
│   ├── css/
│   │   └── style.css            # Main stylesheet
│   └── js/
│       ├── app.js               # Layui 版核心逻辑
│       ├── app-fishery.js       # Tailwind 版渔业模块逻辑
│       ├── app-seafood.js       # Tailwind 版海鲜模块逻辑
│       ├── api.js               # API 调用封装
│       └── i18n.js              # 国际化支持
├── docs/
│   └── ARCHITECTURE.md          # Architecture documentation
├── MIGRATION_STATUS.md          # Migration progress report (Layui → Tailwind)
└── README.md                    # Deployment documentation (Chinese)
```

### Database Tables

**Business Tables (主从结构):**
| 表名 | 说明 | 主键 |
|------|------|------|
| `tblLanding` | 到货主表 | LandingID |
| `tblLandingDetail` | 到货明细表 | ID |
| `tblPurchase` | 采购主表 | PurchaseID |
| `tblPurchaseDetail` | 采购明细表 | ID |
| `tblSales` | 销售主表 | SalesID |
| `tblSalesDetail` | 销售明细表 | ID |

**Reference Tables (基础数据表):**
- `users` - User authentication (password hashing with `password_hash`)
- `tblSuppliers` - Supplier reference data
- `tblStock` - Stock inventory / 鱼种
- `tblBoat` - Boat/vessel reference data
- `tblPort` - Port/location reference data
- `tblFleet` - Fleet reference data
- `tblBin` - Bin/container reference data
- `tblCustomer` - Customer reference data

**Table Relationships:**
```
tblLanding (到货) 1:N → tblLandingDetail → tblStock (鱼种)
      │ 自动生成采购
      ▼
tblPurchase (采购) 1:N → tblPurchaseDetail
      │ 可选生成销售
      ▼
tblSales (销售) 1:N → tblSalesDetail

tblSuppliers (供应商) N:1 → tblFleet (船队) → tblBoat (船舶)
```

## Development Commands

### Database Operations

```bash
# Access MySQL console
mysql -u root -p

# Import database schema
mysql -u root -p seafood < /www/wwwroot/table-editor/database/fishery_schema.sql

# Check database status
mysql -u root -p -e "USE seafood; SHOW TABLES;"
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
```php
<?php
define('APP_ACCESS', true);
require_once '../config/db.php';
header('Content-Type: application/json; charset=utf-8');
session_start();

// 认证检查
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$conn = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// RESTful 路由
switch ($method) {
    case 'GET':    handleGet($conn);    break;
    case 'POST':   handlePost($conn);   break;
    case 'PUT':    handlePut($conn);    break;
    case 'DELETE': handleDelete($conn); break;
}
```

**Database Connection:**
- Singleton pattern via `Database::getConnection()`
- PDO-based with prepared statements
- Located in `config/db.php`

**API Response Format:**
```json
// 成功响应
{
    "success": true,
    "message": "操作成功",
    "data": { ... },
    "pagination": {
        "page": 1,
        "pageSize": 10,
        "total": 100,
        "totalPages": 10
    }
}

// 错误响应
{
    "success": false,
    "message": "错误描述"
}
```

**API Endpoints:**

| 端点 | 方法 | 功能 |
|------|------|------|
| `/api/login.php` | POST | 用户登录 |
| `/api/logout.php` | POST | 用户登出 |
| `/api/landing.php` | GET/POST/PUT/DELETE | 到货记录 CRUD |
| `/api/purchase.php` | GET/POST/PUT/DELETE | 采购记录 CRUD |
| `/api/sales.php` | GET/POST/PUT/DELETE | 销售记录 CRUD |
| `/api/fleet.php` | GET/POST/PUT/DELETE | 船队管理 |
| `/api/boat-management.php` | GET/POST/PUT/DELETE | 船舶管理 |
| `/api/data-management.php` | GET/POST/PUT/DELETE | 通用数据管理 |
| `/api/landing-to-purchase.php` | POST | 到货转采购 |
| `/api/purchase-to-sales.php` | POST | 采购转销售 |
| `/api/landing-options.php` | GET | 到货表单选项 |
| `/api/monthly-statistics.php` | GET | 月度统计 |
| `/api/send-email.php` | POST | 发送邮件 |

### Frontend Pattern

**Vanilla JavaScript Architecture:**
- Global state management with simple objects
- Direct DOM manipulation via `document.getElementById` and `querySelector`
- `fetch()` API for AJAX calls to `/api/*.php` endpoints
- Session-based authentication via PHPSESSID cookie

**UI Framework (Two Versions):**
- **Tailwind CSS 版本 (新版)**: `index.html` - 响应式设计，移动端适配
- **Layui 版本 (旧版)**: `index-layui.html` - 保留供参考，LayUI loaded from CDN (https://unpkg.com/layui@2.8.0/)

**Common Frontend Operations:**
- `renderTable(data)` - Renders data tables
- `showModal(content)` - Shows modal dialogs (Layui or Tailwind)
- `loadData()` - Fetches data from API
- `handleSubmit()` - Forms POST to API endpoints

### Authentication Flow

```
用户输入账号密码
        │
        ▼
POST /api/login.php {username, password}
        │
        ▼
查询 users 表 + password_verify()
        │
        ▼
$_SESSION['user_id'] = 用户ID
$_SESSION['username'] = 用户名
$_SESSION['login_token'] = 随机令牌
$_SESSION['login_time'] = 登录时间
        │
        ▼
设置 Session Cookie (7天有效期)
        │
        ▼
返回 {success: true, data: {user_id, username}}
        │
        ▼
前端 localStorage 存储用户信息
```

**Session Configuration:**
```php
session_set_cookie_params([
    'lifetime' => 7 * 24 * 60 * 60,  // 7天
    'path' => '/',
    'httponly' => true,
    'secure' => false,  // 开发环境 false
    'samesite' => 'Lax'
]);
```

**Auth Middleware:**
```php
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '未登录']);
        exit;
    }
    return $_SESSION['user_id'];
}
```

**Frontend Persistence (Tailwind 版本):**
```javascript
// 登录成功后存储
localStorage.setItem('isLoggedIn', 'true');
localStorage.setItem('userId', data.user_id);
localStorage.setItem('username', data.username);

// 页面加载时检查
window.addEventListener('DOMContentLoaded', () => {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    if (isLoggedIn === 'true') { /* 显示主界面 */ }
    else { /* 显示登录页面 */ }
});
```

## Important Configuration Files

### Environment Variables (.env)
- **Location:** `/www/wwwroot/table-editor/.env`
- **Purpose:** Store sensitive configuration (NOT committed to git)

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
2. Copy structure from existing pages (use Tailwind CSS for styling, or Layui for legacy pages)
3. Create corresponding JS file in `/public/js/`
4. Load Tailwind CSS via CDN: `<script src="https://cdn.tailwindcss.com"></script>`
5. For Layui pages, load LayUI and other dependencies via CDN in `<head>`

### Modifying Database Schema

1. Edit SQL in `/database/fishery_schema.sql`
2. Import changes: `mysql -u root -p seafood < /www/wwwroot/table-editor/database/fishery_schema.sql`
3. Update corresponding API endpoints in `/api/*.php`
4. Update frontend table rendering in `/public/js/*.js`

## External Dependencies

**Frontend CDNs:**
- Tailwind CSS: `https://cdn.tailwindcss.com` (新版主要框架)
- LayUI CSS/JS: `https://unpkg.com/layui@2.8.0/` (旧版保留)
- XLSX (Excel export): `https://unpkg.com/xlsx@0.18.5/`
- html2canvas: `https://unpkg.com/html2canvas@1.4.1/`
- jsPDF: `https://unpkg.com/jspdf@2.5.1/`
- ECharts: `https://unpkg.com/echarts@5.4.3/` (数据可视化图表)
- Material Symbols: Google 图标库

**Backend Libraries:**
- PHPMailer: `/lib/PHPMailer/` (Gmail SMTP 邮件发送)

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

- UI Language: Chinese (Simplified) / English (双语支持)
- Database Charset: `utf8mb4_unicode_ci`
- All comments and documentation in Chinese
- Date format: YYYY-MM-DD (ISO 8601)

## Business Flow

### Landing → Purchase → Sales Chain

```
Landing Records 页面
        │
        ▼ 填写表单 → 点击"保存"
tblLanding + tblLandingDetail 创建
        │
        ▼ 自动调用
/landing-to-purchase.php
        │
        ▼ 计算明细金额
tblPurchase + tblPurchaseDetail 自动创建
        │
        ▼ 可选操作
/purchase-to-sales.php
        │
        ▼ 生成销售记录
tblSales + tblSalesDetail 创建
```

### Data Linkage (数据联动)

**供应商-船只联动:**
- 选择供应商后自动筛选该供应商的船只
- 数据来源: `/api/landing-options.php` 返回 `supplierBoatMap`

**Management 数据关联:**
- Suppliers → FleetID (单选船队)
- Fleet → BoatIDs (多选船只)
- Boats → FleetIDs (多选船队) + PortID (港口)

**Purchase 金额计算:**
```
每行明细 Total = Landed KG × Unit Price
Subtotal = Σ (所有明细 Total)
GST = Subtotal × 10%
Total = Subtotal + GST
```

## Version Notes

项目已完成从 Layui 到 Tailwind CSS 的前端迁移：

| 版本 | 入口文件 | 说明 |
|------|----------|------|
| 新版 | `index.html` | Tailwind CSS，响应式设计，移动端适配 |
| 旧版 | `index-layui.html` | Layui 版本，保留供参考 |

详细迁移进度见 [MIGRATION_STATUS.md](MIGRATION_STATUS.md)

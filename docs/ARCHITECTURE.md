# 渔业数据管理系统 - 技术架构文档

> 生成时间: 2026-04-16
> 项目路径: `/www/wwwroot/table-editor/`
> 运行php -S localhost:8000

---

## 一、系统概述

Fishery Data Management System (渔业数据管理系统) - 一个轻量级的渔业数据管理平台，支持到货记录、采购记录、销售记录的管理，具备 Excel 导出、PDF 生成、邮件发送等功能。

---

## 二、技术栈总览

### 2.1 前端技术

| 技术 | 版本 | 用途 |
|------|------|------|
| HTML5 | - | 页面结构 |
| CSS3 | - | 样式与响应式布局 |
| LayUI | 2.8.0 | UI 组件库 (表格、表单、弹窗、Tab) |
| 原生 JavaScript | ES5 | 业务逻辑 |
| XLSX | 0.18.5 | Excel 导入/导出 |
| html2canvas | 1.4.1 | 网页截图 |
| jsPDF | 2.5.1 | PDF 生成 |
| ECharts | 5.4.3 | 数据可视化图表 |

### 2.2 后端技术

| 技术 | 版本 | 用途 |
|------|------|------|
| PHP | 8.2.28 | 后端语言 |
| 原生 PHP | - | 无框架，轻量级 |
| PDO | - | 数据库驱动 |
| PHPMailer | - | Gmail SMTP 邮件发送 |

### 2.3 数据库

| 技术 | 版本 | 用途 |
|------|------|------|
| MySQL | - | 关系型数据库 |
| PDO | - | 连接方式 |

### 2.4 服务器

| 技术 | 说明 |
|------|------|
| Nginx | Web 服务器 |
| 宝塔面板 | 服务器管理 |
| PHP-FPM | PHP 进程管理 |

---

## 三、系统架构图

```
┌─────────────────────────────────────────────────────────────────┐
│                         用户浏览器                               │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐           │
│  │  LayUI   │  │ XLSX.js │  │ECharts │  │ jsPDF   │           │
│  │ (UI框架) │  │(Excel)  │  │(图表)   │  │ (PDF)   │           │
│  └─────────┘  └─────────┘  └─────────┘  └─────────┘           │
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

---

## 四、目录结构

```
/www/wwwroot/table-editor/
├── api/                          # PHP API 端点 (25+ 文件)
│   ├── login.php                # 用户登录
│   ├── logout.php               # 用户登出
│   ├── landing.php              # 到货记录 CRUD
│   ├── purchase.php             # 采购记录 CRUD
│   ├── sales.php                # 销售记录 CRUD
│   ├── data-management.php      # 基础数据管理
│   ├── fleet.php                # 船队管理
│   ├── boat-management.php      # 船舶管理
│   ├── supplier-stock-price.php # 供应商定价
│   ├── printer.php              # 打印机管理
│   ├── units.php                # 单位管理
│   ├── landing-to-purchase.php  # 到货转采购
│   ├── purchase-to-sales.php    # 采购转销售
│   ├── monthly-statistics.php   # 月度统计
│   ├── send-email.php           # 发送邮件
│   ├── landing-options.php      # 到货下拉选项
│   └── ...
│
├── config/
│   └── db.php                   # 数据库配置 (单例模式)
│
├── lib/
│   └── PHPMailer/              # 邮件发送库
│       ├── src/PHPMailer.php
│       ├── src/SMTP.php
│       └── src/Exception.php
│
├── public/                      # Web 文档根目录
│   ├── index.html               # 主入口页面
│   ├── data-management.html     # 基础数据管理 (9个Tab)
│   ├── monthly-report.html      # 月度报表
│   ├── monthly-statistics.html  # 月度统计 (图表)
│   ├── purchase-landing.html    # 收货录入
│   ├── print-landing.html       # 到货打印
│   ├── print-bill.html          # 账单打印
│   ├── css/
│   │   ├── style.css           # 主样式表
│   │   ├── layui-custom.css    # LayUI 定制样式
│   │   └── purchase-landing.css
│   └── js/
│       ├── api.js              # API 请求封装
│       ├── app.js              # 核心应用逻辑
│       ├── i18n.js             # 国际化 (中英文)
│       ├── landing.js          # 到货记录页面
│       ├── purchase.js         # 采购记录页面
│       ├── sales.js            # 销售记录页面
│       ├── monthly-report.js   # 月度报表
│       ├── monthly-statistics.js # 月度统计
│       └── purchase-landing.js # 收货录入
│
├── database/
│   └── fishery_schema.sql       # 数据库建表脚本
│
├── .env                         # 环境变量 (不提交git)
├── .gitignore                   # Git 忽略配置
├── CLAUDE.md                    # Claude Code 指引
└── README.md                    # 部署文档 (中文)
```

---

## 五、API 架构

### 5.1 API 设计模式

每个 PHP API 文件遵循统一的模式：

```php
<?php
// 1. 安全常量
define('APP_ACCESS', true);

// 2. 引入数据库配置
require_once '../config/db.php';

// 3. 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 4. 启动 Session
session_start();

// 5. 认证检查
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

// 6. 获取数据库连接
$conn = Database::getConnection();

// 7. 路由分发
switch ($_GET['action'] ?? 'list') {
    case 'list':   listRecords($conn);   break;
    case 'get':    getRecord($conn);     break;
    case 'add':    addRecord($conn);     break;
    case 'update': updateRecord($conn);  break;
    case 'delete': deleteRecord($conn);  break;
}
```

### 5.2 API 端点列表

#### 核心业务 API

| API 文件 | 方法 | 功能 |
|----------|------|------|
| `/api/login.php` | POST | 用户登录 |
| `/api/logout.php` | POST | 用户登出 |
| `/api/landing.php` | GET/POST/PUT/DELETE | 到货记录 CRUD |
| `/api/purchase.php` | GET/POST/PUT/DELETE | 采购记录 CRUD |
| `/api/sales.php` | GET/POST/PUT/DELETE | 销售记录 CRUD |
| `/api/data-management.php` | GET/POST/PUT/DELETE | 基础数据管理 |
| `/api/fleet.php` | GET/POST/PUT/DELETE | 船队管理 |
| `/api/boat-management.php` | GET/POST/PUT/DELETE | 船舶管理 |
| `/api/supplier-stock-price.php` | GET/POST/PUT/DELETE | 供应商定价 |
| `/api/printer.php` | GET/POST/PUT/DELETE | 打印机管理 |
| `/api/units.php` | GET/POST/PUT/DELETE | 单位管理 |

#### 业务流转 API

| API 文件 | 功能 |
|----------|------|
| `/api/landing-to-purchase.php` | 从到货记录生成采购记录 |
| `/api/purchase-to-sales.php` | 从采购记录生成销售记录 |
| `/api/monthly-statistics.php` | 月度统计数据 |

#### 辅助 API

| API 文件 | 功能 |
|----------|------|
| `/api/send-email.php` | 发送带 PDF 附件的邮件 |
| `/api/landing-options.php` | 到货表单下拉选项 |
| `/api/customer-options.php` | 客户下拉选项 |
| `/api/common-options.php` | 通用下拉选项 |
| `/api/statistics-options.php` | 统计页下拉选项 |
| `/api/unit-options.php` | 单位下拉选项 |
| `/api/view-email-log.php` | 邮件发送日志 |

### 5.3 API 响应格式

**成功响应：**
```json
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
```

**错误响应：**
```json
{
    "success": false,
    "message": "错误描述"
}
```

---

## 六、数据库设计

### 6.1 数据库连接

**配置文件：** `config/db.php`

```php
class Database {
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn !== null) return self::$conn;

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        self::$conn = new PDO($dsn, $username, $password, $options);
        return self::$conn;
    }
}
```

### 6.2 数据表结构

#### 核心业务表

| 表名 | 说明 | 主键 |
|------|------|------|
| `tblLanding` | 到货主表 | LandingID |
| `tblLandingDetail` | 到货明细表 | ID |
| `tblPurchase` | 采购主表 | PurchaseID |
| `tblPurchaseDetail` | 采购明细表 | ID |
| `tblSales` | 销售主表 | SalesID |
| `tblSalesDetail` | 销售明细表 | ID |

#### 基础数据表

| 表名 | 说明 |
|------|------|
| `users` | 用户表 |
| `tblSuppliers` | 供应商表 |
| `tblBoat` | 船舶表 |
| `tblPort` | 港口表 |
| `tblStock` | 库存/鱼种表 |
| `tblBin` | 篮子/容器表 |
| `tblCustomer` | 客户表 |
| `tblFleet` | 船队表 |

### 6.3 表关系图

```
┌─────────────┐      ┌──────────────────┐      ┌─────────────────┐
│ tblLanding  │──────│ tblLandingDetail │◄─────│    tblStock     │
│   (到货)    │  1:N │    (到货明细)    │      │   (鱼种/库存)   │
└──────┬──────┘      └──────────────────┘      └─────────────────┘
       │ 自动生成采购
       ▼
┌─────────────┐      ┌──────────────────┐
│ tblPurchase │──────│ tblPurchaseDetail│
│   (采购)    │  1:N │    (采购明细)    │
└──────┬──────┘      └──────────────────┘
       │ 可选生成销售
       ▼
┌─────────────┐      ┌──────────────────┐
│  tblSales   │──────│  tblSalesDetail  │
│   (销售)    │  1:N │    (销售明细)    │
└─────────────┘      └──────────────────┘

┌─────────────┐      ┌──────────────────┐
│tblSuppliers │◄─────│    tblFleet      │
│  (供应商)   │  N:1 │    (船队)       │
└─────────────┘      └────────┬─────────┘
                               │
                               ▼
                        ┌──────────────┐
                        │   tblBoat    │
                        │   (船舶)     │
                        └──────────────┘
```

---

## 七、认证机制

### 7.1 登录流程

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
$_SESSION['login_token'] = 随机令牌 (32字节)
$_SESSION['login_time'] = 登录时间
        │
        ▼
返回 {success: true, data: {user_id, username}}
        │
        ▼
前端 localStorage 存储用户信息
```

### 7.2 认证中间件

所有 API 文件包含认证检查：

```php
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '未登录']);
        exit;
    }
    return $_SESSION['user_id'];
}
```

### 7.3 登出流程

```php
// /api/logout.php
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();
```

---

## 八、核心业务流程

### 8.1 到货-采购-销售链路

```
purchase-landing.html (收货录入页面)
        │
        ▼ 填写表单 → 点击"保存到货"
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

### 8.2 邮件发送流程

```
用户点击"发送邮件"
        │
        ▼
前端生成 HTML 打印页面
        │
        ▼ html2canvas 截图
Base64 编码的图片
        │
        ▼ jsPDF 生成 PDF
Blob 对象 (PDF 文件)
        │
        ▼ POST /api/send-email.php (FormData)
        │
        ▼ PHPMailer + Gmail SMTP
发送邮件给供应商
        │
        ▼
更新采购记录的 EmailSent = 1
```

---

## 九、前端架构

### 9.1 页面结构

| 页面 | 功能 | Tab/模块 |
|------|------|----------|
| `index.html` | 主入口 | 到货记录 / 采购记录 / 销售记录 |
| `data-management.html` | 基础数据 | 供应商 / 船队 / 船舶 / 港口 / 库存 / 篮子 / 定价 / 打印机 / 单位 |
| `monthly-report.html` | 月度报表 | Excel 导出 |
| `monthly-statistics.html` | 月度统计 | ECharts 图表 |
| `purchase-landing.html` | 收货录入 | 新增到货 → 自动生成采购 |

### 9.2 JavaScript 模块化

使用 LayUI 模块化规范 `layui.define`：

```javascript
// api.js
layui.define(['jquery'], function(exports){
    var API_BASE = '../api/';

    function request(url, options) {
        return fetch(url, options)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message);
                return data;
            });
    }

    exports('api', {
        login, logout, getList, getById, add, update, delete
    });
});
```

### 9.3 国际化 (i18n)

`js/i18n.js` 支持中英文切换：

```javascript
var translations = {
    zh: { add: '添加', edit: '编辑', delete: '删除', ... },
    en: { add: 'Add', edit: 'Edit', delete: 'Delete', ... }
};

function setLanguage(lang) {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        el.textContent = translations[lang][el.dataset.i18n];
    });
}
```

---

## 十、安全措施

| 防护类型 | 实现方式 |
|----------|----------|
| SQL 注入 | PDO 预处理 `prepare/execute` |
| XSS | LayUI 自动转义输出 |
| CSRF | Session 验证 |
| 密码安全 | `password_hash()` (bcrypt) |
| 敏感数据 | `.env` 文件分离配置 |
| 文件权限 | `www:www` 所有者 |
| 错误处理 | `try-catch` + `error_log()` |

---

## 十一、部署说明

### 11.1 环境要求

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Nginx 1.18+
- 宝塔面板 (可选)

### 11.2 Nginx 配置要点

```nginx
server {
    listen 80;
    server_name example.com;
    root /www/wwwroot/table-editor/public;
    index index.html index.php;

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 11.3 文件权限

```bash
# 所有者
chown -R www:www /www/wwwroot/table-editor

# 目录权限
find /www/wwwroot/table-editor -type d -exec chmod 755 {} \;

# 文件权限
find /www/wwwroot/table-editor -type f -exec chmod 644 {} \;
```

### 11.4 测试账户

| 用户名 | 密码 | 说明 |
|--------|------|------|
| admin | 123456 | 管理员账户 |
| testuser | 123456 | 测试账户 |

---

## 十二、环境变量

**配置文件：** `.env` (不提交 Git)

| 变量 | 说明 | 示例 |
|------|------|------|
| `DB_HOST` | MySQL 主机 | localhost |
| `DB_PORT` | MySQL 端口 | 3306 |
| `DB_NAME` | 数据库名 | seafood |
| `DB_USER` | 用户名 | root |
| `DB_PASS` | 密码 | - |
| `GMAIL_USERNAME` | Gmail 用户名 | - |
| `GMAIL_PASSWORD` | Gmail 应用密码 | - |

---

## 十三、常用命令

### 数据库

```bash
# 导入数据库
mysql -u root -p table_editor < /www/wwwroot/table-editor/database/fishery_schema.sql

# 检查数据库状态
mysql -u root -p -e "USE table_editor; SHOW TABLES;"
```

### PHP-FPM

```bash
# 检查状态
/etc/init.d/php-fpm-82 status

# 重启
/etc/init.d/php-fpm-82 restart

# 查看错误日志
tail -f /www/server/php/82/var/log/php-fpm.log
```

### Nginx

```bash
# 测试配置
nginx -t

# 重载配置
systemctl reload nginx

# 查看错误日志
tail -f /www/server/nginx/logs/error.log
```

---

## 十四、总结

这是一个**轻量级的 LEMP 架构企业管理系统**：

| 层级 | 技术 | 说明 |
|------|------|------|
| 前端 | HTML5 + CSS3 + 原生 JS + LayUI | 响应式布局，LayUI 组件 |
| 后端 | PHP 8.2 原生 | 无框架，轻量级 |
| 数据库 | MySQL + PDO | 单例模式连接 |
| Web服务器 | Nginx (宝塔管理) | PHP-FPM 模式 |
| 认证 | Session-based | 安全的密码哈希 |
| 特色功能 | Excel 导出、PDF 生成、邮件发送 | 完整业务闭环 |

项目规模适中，代码结构清晰，适合学习和二次开发。

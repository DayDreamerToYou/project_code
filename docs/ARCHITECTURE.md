# 渔业数据管理系统 - 技术架构文档

> 更新时间: 2026-04-30
> 项目路径: `/www/wwwroot/table-editor/`
> 本地开发: `php -S localhost:8080 -t public`

---

## 一、系统概述

Fishery Data Management System (渔业数据管理系统) - 一个轻量级的渔业数据管理平台，支持到货记录、采购记录、销售记录的管理，具备 Excel 导出、PDF 生成、邮件发送等功能。

**当前版本：** Tailwind CSS 版本（主版本），Layui 版本保留供参考

---

## 二、技术栈总览

### 2.1 前端技术

| 技术 | 版本 | 用途 |
|------|------|------|
| HTML5 | - | 页面结构 |
| CSS3 | - | 样式与响应式布局 |
| **Tailwind CSS** | 3.x | **主版本 UI 框架** |
| LayUI | 2.8.0 | 旧版 UI 框架（保留参考） |
| 原生 JavaScript | ES6+ | 业务逻辑 |
| XLSX | 0.18.5 | Excel 导入/导出 |
| html2canvas | 1.4.1 | 网页截图 |
| jsPDF | 2.5.1 | PDF 生成 |
| ECharts | 5.4.3 | 数据可视化图表 |
| Material Symbols | - | 图标库 |

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
| MySQL | 5.7+ | 关系型数据库 |
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
│  ┌─────────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐        │
│  │ Tailwind CSS│  │ XLSX.js │  │ ECharts │  │ jsPDF   │        │
│  │ (主版本UI)  │  │ (Excel) │  │ (图表)  │  │ (PDF)   │        │
│  └─────────────┘  └─────────┘  └─────────┘  └─────────┘        │
│  ┌─────────────┐                                                │
│  │   LayUI     │  (旧版本保留参考)                              │
│  └─────────────┘                                                │
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
├── api/                          # PHP API 端点
│   ├── login.php                # 用户登录
│   ├── logout.php               # 用户登出
│   ├── landing.php              # 到货记录 CRUD
│   ├── purchase.php             # 采购记录 CRUD
│   ├── sales.php                # 销售记录 CRUD
│   ├── data-management.php      # 基础数据管理
│   ├── fleet.php                # 船队管理（含船只关联）
│   ├── boat-management.php      # 船舶管理（含船队关联）
│   ├── supplier-stock-price.php # 供应商定价
│   ├── printer.php              # 打印机管理
│   ├── units.php                # 单位管理
│   ├── landing-to-purchase.php  # 到货转采购
│   ├── purchase-to-sales.php    # 采购转销售
│   ├── monthly-statistics.php   # 月度统计
│   ├── send-email.php           # 发送邮件
│   └── landing-options.php      # 到货下拉选项（含供应商-船只映射）
│
├── config/
│   └── db.php                   # 数据库配置 (单例模式)
│
├── lib/
│   └── PHPMailer/               # 邮件发送库
│
├── public/                      # Web 文档根目录
│   ├── index.html               # 主入口页面 (Tailwind 新版) ⭐
│   ├── index-layui.html         # 主入口页面 (Layui 旧版)
│   ├── data-management.html     # 基础数据管理 (Layui 旧版)
│   ├── monthly-report.html      # 月度报表
│   ├── monthly-statistics.html  # 月度统计 (图表)
│   ├── purchase-landing.html    # 收货录入
│   ├── print-landing.html       # 到货打印
│   ├── print-bill.html          # 账单打印
│   ├── css/
│   │   ├── style.css            # 主样式表
│   │   └── layui-custom.css     # LayUI 定制样式
│   └── js/
│       ├── api.js               # API 请求封装
│       ├── app.js               # Layui 版核心逻辑
│       ├── i18n.js              # 国际化 (中英文)
│       └── ...
│
├── database/
│   └── seafood_backup_20260409.sql  # 数据库备份
│
├── docs/
│   └── ARCHITECTURE.md          # 本文档
│
├── .trae/
│   └── rules/
│       └── project_rules.md     # 项目规则配置
│
├── MIGRATION_STATUS.md          # 迁移进度报告
├── CLAUDE.md                    # Claude Code 指引
└── README.md                    # 项目说明文档
```

---

## 五、版本对比

### 5.1 入口文件

| 版本 | 文件 | 框架 | 状态 |
|------|------|------|------|
| **新版** | `public/index.html` | Tailwind CSS | **主版本** |
| 旧版 | `public/index-layui.html` | LayUI | 保留参考 |

### 5.2 功能对比

| 功能 | Tailwind 版 | Layui 版 |
|------|-------------|----------|
| 响应式设计 | ✅ 移动端适配 | ❌ 仅桌面 |
| 底部导航栏 | ✅ | ❌ |
| 双语切换 | ✅ | ✅ |
| 数据联动 | ✅ 供应商-船只筛选 | ✅ |
| Management 关联 | ✅ 船队-船只多选 | ✅ |
| 登录状态持久化 | ✅ localStorage + Session | ✅ Session |

---

## 六、API 架构

### 6.1 API 设计模式

每个 PHP API 文件遵循统一的模式：

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

### 6.2 API 端点列表

#### 认证 API

| 端点 | 方法 | 功能 |
|------|------|------|
| `/api/login.php` | POST | 用户登录，返回 Session |
| `/api/logout.php` | POST | 用户登出，销毁 Session |
| `/api/landing.php` | GET | 检查登录状态 |

#### 核心业务 API

| 端点 | 方法 | 功能 |
|------|------|------|
| `/api/landing.php` | GET/POST/PUT/DELETE | 到货记录 CRUD |
| `/api/purchase.php` | GET/POST/PUT/DELETE | 采购记录 CRUD |
| `/api/sales.php` | GET/POST/PUT/DELETE | 销售记录 CRUD |

#### 基础数据管理 API

| 端点 | 功能 | 关联支持 |
|------|------|----------|
| `/api/fleet.php` | 船队管理 | 支持船只多选关联 |
| `/api/boat-management.php` | 船舶管理 | 支持船队多选关联 |
| `/api/data-management.php` | 通用数据管理 | 供应商关联船队 |
| `/api/supplier-stock-price.php` | 供应商定价 | - |
| `/api/printer.php` | 打印机管理 | 支持测试连接 |
| `/api/units.php` | 单位管理 | - |

#### 业务流转 API

| 端点 | 功能 |
|------|------|
| `/api/landing-to-purchase.php` | 从到货记录生成采购记录 |
| `/api/purchase-to-sales.php` | 从采购记录生成销售记录 |

#### 辅助 API

| 端点 | 功能 |
|------|------|
| `/api/landing-options.php` | 到货表单选项（含 supplierBoatMap） |
| `/api/monthly-statistics.php` | 月度统计数据 |
| `/api/send-email.php` | 发送带 PDF 附件的邮件 |

### 6.3 API 响应格式

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

## 七、数据库设计

### 7.1 数据库连接

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

### 7.2 核心数据表

#### 业务表

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

### 7.3 表关系图

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
│  (供应商)   │  N:1 │    (船队)        │
└─────────────┘      └────────┬─────────┘
        │                     │
        │                     ▼
        │              ┌──────────────┐
        │              │   tblBoat    │
        │              │   (船舶)     │
        │              └──────────────┘
        │                     ▲
        └─────────────────────┘
           (供应商-船只映射)
```

---

## 八、认证机制

### 8.1 登录流程

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

### 8.2 登录状态持久化

**Tailwind 版本实现：**

```javascript
// 登录成功后存储
localStorage.setItem('isLoggedIn', 'true');
localStorage.setItem('userId', data.user_id);
localStorage.setItem('username', data.username);

// 页面加载时检查
window.addEventListener('DOMContentLoaded', () => {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    if (isLoggedIn === 'true') {
        // 显示主界面
    } else {
        // 显示登录页面
    }
});
```

**PHP Session 配置：**

```php
session_set_cookie_params([
    'lifetime' => 7 * 24 * 60 * 60,  // 7天
    'path' => '/',
    'httponly' => true,
    'secure' => false,  // 开发环境 false
    'samesite' => 'Lax'
]);
```

### 8.3 认证中间件

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

---

## 九、核心业务流程

### 9.1 到货-采购-销售链路

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

### 9.2 数据联动功能详解

#### 9.2.1 供应商-船只联动（Landing Records）

**场景：** 在到货记录编辑/新增页面，选择供应商后自动筛选该供应商的船只

**数据来源：** `/api/landing-options.php` 返回 `supplierBoatMap`

```javascript
// supplierBoatMap 结构
{
    "S001": ["B001", "B002", "B003"],  // 供应商S001 的船只
    "S002": ["B004", "B005"]           // 供应商S002 的船只
}

// 前端实现
function filterBoatsBySupplier(supplierId) {
    const boatSelect = document.getElementById('edit-boat');
    const boats = landingOptions.supplierBoatMap[supplierId] || [];
    // 更新船只下拉选项
    boatSelect.innerHTML = boats.map(b => 
        `<option value="${b.BoatID}">${b.BoatName}</option>`
    ).join('');
}
```

**触发时机：** 供应商下拉框 `onchange` 事件

#### 9.2.2 Management 数据关联

**Suppliers (供应商) 关联：**
- FleetID: 下拉选择关联的船队（单选）
- 新增/编辑时显示 Fleet 下拉框

**Fleet (船队) 关联：**
- BoatIDs: 多选关联船只（checkbox 列表）
- 编辑时预选已关联的船只
- API: `/api/fleet.php` 返回 `associatedBoats`

**Boats (船只) 关联：**
- FleetIDs: 多选关联船队（checkbox 列表）
- PortID: 下拉选择港口
- 编辑时预选已关联的船队
- API: `/api/boat-management.php` 返回 `FleetNames` 列

**关联数据加载：**
```javascript
// 编辑时加载关联数据
async function editMgmtRecord(id) {
    // 1. 获取主记录
    const record = await fetch(`${API}?action=get&id=${id}`);
    
    // 2. 获取关联数据
    if (tab === 'fleet') {
        const boats = await fetch(`/api/fleet.php?action=getBoats&id=${id}`);
        mgmtSelectedBoats = boats;  // 预选船只
    }
    if (tab === 'boat-management') {
        const fleets = await fetch(`/api/boat-management.php?action=getFleets&id=${id}`);
        mgmtSelectedFleets = fleets;  // 预选船队
    }
}
```

#### 9.2.3 数据更新后选项刷新

**场景：** 在 Management 中新增/编辑/删除数据后，Landing Records 的下拉选项自动更新

**实现：**
```javascript
async function submitMgmtForm() {
    // 保存数据
    await fetch(API, { method: 'POST', body: formData });
    
    // 刷新选项（根据当前 tab）
    if (['suppliers', 'fleet', 'boat-management', 'port'].includes(mgmtState.tab)) {
        await loadLandingOptions();  // 重新加载 Landing 选项
    }
}
```

**涉及的表：**
- suppliers → 更新供应商下拉
- fleet → 更新船队下拉
- boat-management → 更新船只下拉
- port → 更新港口下拉

#### 9.2.4 Purchase Record 金额计算联动

**场景：** 编辑采购明细时，自动计算 Subtotal、GST、Total

**计算逻辑：**
```
每行明细 Total = Landed KG × Unit Price
Subtotal = Σ (所有明细 Total)
GST = Subtotal × 10%
Total = Subtotal + GST
```

**实现：**
```javascript
function calculatePurchaseDetailTotal(row) {
    const landed = parseFloat(row.querySelector('.purchase-detail-landed').value) || 0;
    const price = parseFloat(row.querySelector('.purchase-detail-price').value) || 0;
    row.querySelector('.purchase-detail-total').value = (landed * price).toFixed(2);
    
    updatePurchaseTotals();  // 更新汇总
}

function updatePurchaseTotals() {
    let subtotal = 0;
    document.querySelectorAll('#edit-details-body tr').forEach(row => {
        subtotal += parseFloat(row.querySelector('.purchase-detail-total').value) || 0;
    });
    
    document.getElementById('edit-purchase-subtotal').value = subtotal.toFixed(2);
    document.getElementById('edit-purchase-gst').value = (subtotal * 0.1).toFixed(2);
    document.getElementById('edit-purchase-total').value = (subtotal * 1.1).toFixed(2);
}
```

**触发时机：**
- Landed KG 输入框 `input` 事件
- Unit Price 输入框 `input` 事件
- 删除明细行后

#### 9.2.5 Landing Record Detail 联动

**Stock + State → Description/Price/Area：**
```javascript
async function updateDetailRowState(row) {
    const stock = row.querySelector('.detail-stock-select').value;
    const state = row.querySelector('.detail-state-select').value;
    
    if (!stock || !state) {
        // 清空 Description 和 Area
        row.querySelector('.detail-description').value = '';
        row.querySelector('.detail-area').value = '';
        return;
    }
    
    // 从 stocks 数据获取 Description
    const stockData = landingOptions.stocks.find(s => s.Stock === stock && s.State === state);
    if (stockData) {
        row.querySelector('.detail-description').value = stockData.Description || '';
    }
    
    // 从 price API 获取价格
    const price = await fetchPrice(stock, state, supplierId);
    row.querySelector('.detail-price').value = price || '';
}
```

**联动关系图：**
```
Stock 选择 ──┬──→ Description 更新（从 stocks 数据）
             │
             └──→ 触发 State 检查
                    │
State 选择 ─────────┼──→ Description 显示（需 Stock + State 都选择）
                    │
                    └──→ Price 查询（从 price API）
                           │
                           └──→ Area 更新（从 price 数据）
```

#### 9.2.6 Landing 编辑同步更新 Purchase

**场景：** 编辑 Landing Record 并保存后，自动同步更新对应的 Purchase Record

**关联关系：**
```
LandingID ←→ PurchaseID
PurchaseID = LandingID + 50000
```

**同步逻辑：**
```php
// 在 landing.php 的 handlePut 中调用
function updatePurchaseFromLanding($conn, $landingId) {
    $purchaseId = $landingId + 50000;
    
    // 1. 检查 Purchase 是否存在
    if (!exists || is_deleted) {
        // 不存在则创建新的
        return generatePurchaseFromLanding($conn, $landingId);
    }
    
    // 2. 更新 Purchase 主表
    UPDATE tblPurchase SET
        PurchaseDate = ?,
        SupplierID = ?,
        Subtotal = ?,
        GST = ?,
        Total = ?
    WHERE PurchaseID = ?;
    
    // 3. 删除旧明细，插入新明细
    DELETE FROM tblPurchaseDetail WHERE PurchaseID = ?;
    INSERT INTO tblPurchaseDetail ...;
}
```

**同步的字段：**

| Landing 字段 | → | Purchase 字段 | 计算方式 |
|-------------|---|--------------|---------|
| LandingDate | → | PurchaseDate | 直接同步 |
| SupplierID | → | SupplierID | 直接同步 |
| L-Weight - (BinWeight × BinQty) | → | LandedKG | 净重计算 |
| LandedKG × Conversion | → | GreenKG | 转换重量 |
| LandedKG × Price | → | Total | 行金额 |
| Σ(Total) | → | Subtotal | 小计 |
| Subtotal × 15% | → | GST | 税额 |
| Subtotal + GST | → | Total | 总金额 |

**触发时机：**
- Landing Record 编辑保存成功后
- 在同一个数据库事务中完成

**API 响应：**
```json
{
    "success": true,
    "message": "更新成功",
    "LandingID": 123,
    "purchaseUpdated": true,
    "PurchaseID": 50123,
    "PurchaseData": {
        "PurchaseID": 50123,
        "LandingID": 123,
        "Subtotal": 1000.00,
        "GST": 150.00,
        "Total": 1150.00,
        "details_count": 5
    }
}
```

---

## 十、前端架构

### 10.1 Tailwind 版本 (index.html)

**技术特点：**
- 单文件应用 (SPA) 架构
- Tailwind CSS CDN 引入
- 原生 JavaScript ES6+
- Material Symbols 图标

**核心模块：**

```javascript
// 状态管理
let currentTab = 'landing';
let currentRecordData = {};
let landingOptions = {};
let mgmtState = { tab: 'suppliers', page: 1 };

// 核心函数
async function loadLandingOptions() { ... }
async function refreshTable() { ... }
function renderLandingTable(data) { ... }
function renderPurchaseTable(data) { ... }
function renderSalesTable(data) { ... }

// Management 模块
function switchMgmtTab(tab) { ... }
function renderMgmtTable(data) { ... }
function buildMgmtFormFields(config, data) { ... }
```

### 10.2 页面结构

| 页面 | 功能 | Tab/模块 |
|------|------|----------|
| `index.html` | 主入口 | Landing / Purchase / Sales / Management |
| `data-management.html` | 基础数据 (Layui) | 供应商 / 船队 / 船舶 / 港口 / 库存 / 篮子 / 定价 / 打印机 / 单位 |
| `monthly-report.html` | 月度报表 | Excel 导出 |
| `monthly-statistics.html` | 月度统计 | ECharts 图表 |

### 10.3 响应式设计

```html
<!-- 移动端底部导航 -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t">
    <!-- 底部导航栏 -->
</nav>

<!-- 桌面端侧边导航 -->
<aside class="hidden md:flex">
    <!-- 侧边导航栏 -->
</aside>
```

---

## 十一、安全措施

| 防护类型 | 实现方式 |
|----------|----------|
| SQL 注入 | PDO 预处理 `prepare/execute` |
| XSS | 输出转义 |
| CSRF | Session 验证 |
| 密码安全 | `password_hash()` (bcrypt) |
| 敏感数据 | `.env` 文件分离配置 |
| 文件权限 | `www:www` 所有者 |
| 错误处理 | `try-catch` + `error_log()` |

---

## 十二、部署说明

### 12.1 环境要求

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Nginx 1.18+

### 12.2 Nginx 配置

```nginx
server {
    listen 80;
    server_name example.com;
    root /www/wwwroot/table-editor/public;
    index index.html index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 12.3 文件权限

```bash
chown -R www:www /www/wwwroot/table-editor
find /www/wwwroot/table-editor -type d -exec chmod 755 {} \;
find /www/wwwroot/table-editor -type f -exec chmod 644 {} \;
```

### 12.4 测试账户

| 用户名 | 密码 | 说明 |
|--------|------|------|
| admin | 123456 | 管理员账户 |
| testuser | 123456 | 测试账户 |

---

## 十三、常用命令

### 本地开发

```bash
# 启动开发服务器
cd /Users/liyiliang/code/table-editor
php -S localhost:8080 -t public
```

### 数据库

```bash
# 导入数据库
mysql -u root -p seafood < database/seafood_backup_20260409.sql

# 检查数据库状态
mysql -u root -p -e "USE seafood; SHOW TABLES;"
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

## 十四、更新日志

### 2026-04-30
- 完成 Purchase Record 编辑表单优化（移除 Invoice No/Status，添加 Subtotal/GST/Total）
- 实现明细金额自动计算和汇总
- **新增 Landing 编辑同步更新 Purchase 功能**
- 更新 README.md 和 ARCHITECTURE.md 文档
- 优化 .gitignore 配置，添加数据库备份文件忽略

### 2026-04-29
- 修复 Landing Record 详情页 Unit 保存问题
- 修复 Description 显示逻辑（需选择 State 后才显示）
- 移除重复的 KG 单位选项

### 2026-04-28
- 实现供应商-船只联动筛选
- 实现 Management 数据关联（供应商-船队-船只）
- 修复登录状态持久化问题
- 修复 Management 数据更新后选项刷新问题

### 2026-04-16
- 完成 Tailwind CSS 版本迁移
- 实现响应式设计和移动端适配

---

## 十五、总结

这是一个**轻量级的 LEMP 架构企业管理系统**：

| 层级 | 技术 | 说明 |
|------|------|------|
| 前端 | HTML5 + CSS3 + 原生 JS + Tailwind CSS | 响应式布局，移动端适配 |
| 后端 | PHP 8.2 原生 | 无框架，轻量级 |
| 数据库 | MySQL + PDO | 单例模式连接 |
| Web服务器 | Nginx (宝塔管理) | PHP-FPM 模式 |
| 认证 | Session + localStorage | 安全的密码哈希 |
| 特色功能 | Excel 导出、PDF 生成、邮件发送、数据联动 | 完整业务闭环 |

项目规模适中，代码结构清晰，适合学习和二次开发。

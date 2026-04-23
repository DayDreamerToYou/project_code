# 渔业数据管理系统 (Fishery Data Management System)

轻量级渔业业务数据管理系统，用于管理渔船到货记录、采购订单、销售订单及基础数据。

## 核心功能

### 业务记录管理
- **到货记录**：渔船捕获数据录入与管理，支持导出、邮件发送、打印、网络打印
- **采购记录**：基于到货记录自动生成采购订单，支持邮件发送和打印
- **销售记录**：基于采购记录创建销售订单，支持导出和打印

### 基础数据管理
- **供应商管理**：管理供应商信息，关联船队
- **船队管理**：管理船队，支持船只多选关联
- **船舶管理**：管理船舶信息，支持船队多选关联
- **港口管理**：管理港口/产地信息
- **库存管理**：管理鱼种、规格、产地等库存数据
- **篮子管理**：管理篮子类型及重量
- **供应商定价**：管理供应商-鱼种-价格对应关系
- **打印机管理**：管理网络打印机，支持测试连接、设为默认
- **单位管理**：管理系统计量单位

### 报表与统计
- **月度汇总报表**：按月汇总数据，支持按供应商筛选，导出 Excel
- **月度统计**：多维度数据统计分析

### 系统特性
- **双语言支持**：中文/English 实时切换
- **移动端适配**：响应式设计，支持手机/平板操作，底部导航栏
- **数据导出**：支持 Excel、PDF 导出
- **邮件通知**：支持发送邮件给供应商
- **网络打印**：支持 ESC/POS 网络打印机

## 业务流程

```
渔船到货 → 生成采购单 → 生成销售单 → 统计分析
     ↓
  导出/邮件/打印
```

## 技术栈

**前端：** HTML5 + CSS3 + Vanilla JavaScript + Tailwind CSS 3
**后端：** PHP 8.2.28 (原生，无框架)
**数据库：** MySQL (utf8mb4_unicode_ci)
**服务器：** Nginx + PHP-FPM

## 快速部署（宝塔面板）

### 1. 上传项目

```bash
/www/wwwroot/your-domain.com/table-editor/
```

### 2. 创建数据库

- 数据库名：`seafood`
- 导入 SQL：`database/seafood_backup_20260409.sql`

### 3. 配置数据库连接

编辑 `config/db.php`：

```php
private static $host = 'localhost';
private static $db_name = 'seafood';
private static $username = 'root';
private static $password = 'your_password';
```

### 4. 设置文件权限

```bash
chown -R www:www /www/wwwroot/table-editor
find /www/wwwroot/table-editor -type d -exec chmod 755 {} \;
find /www/wwwroot/table-editor -type f -exec chmod 644 {} \;
```

### 5. 访问测试

- **新版入口**：`http://your-domain.com/table-editor/public/`
- **旧版入口**：`http://your-domain.com/table-editor/public/index-layui.html`
- 测试账号：`admin` / `123456`

**生产环境请务必修改默认密码！**

## 目录结构

```
table-editor/
├── api/                          # PHP API 接口
│   ├── login.php                 # 登录认证
│   ├── logout.php                # 用户登出
│   ├── table.php                 # 通用 CRUD
│   ├── landing.php               # 到货记录
│   ├── purchase.php              # 采购记录
│   ├── sales.php                 # 销售记录
│   ├── fleet.php                 # 船队管理
│   ├── boat-management.php      # 船舶管理
│   ├── printer.php               # 打印机管理
│   ├── units.php                 # 单位管理
│   ├── supplier-stock-price.php  # 供应商定价
│   ├── monthly-statistics.php    # 月度统计
│   ├── data-management.php       # 基础数据管理
│   ├── send-email.php            # 邮件发送
│   └── landing-options.php       # 到货表单选项
├── config/
│   └── db.php                    # 数据库配置
├── database/
│   └── seafood_backup_20260409.sql  # 数据库备份（完整19表）
├── public/                       # Web 入口
│   ├── index.html                # 主页面（新版 Tailwind）
│   ├── index-layui.html          # 主页面（旧版 Layui）
│   ├── data-management.html      # 基础数据管理（旧版）
│   ├── monthly-report.html       # 月度汇总报表（旧版）
│   ├── monthly-statistics.html   # 月度统计（旧版）
│   ├── purchase-landing.html     # 采购关联到货（旧版）
│   ├── print-landing.html        # 到货打印页面（旧版）
│   ├── print-bill.html           # 单据打印页面（旧版）
│   ├── css/
│   │   └── style.css            # 样式文件
│   └── js/
│       ├── api.js               # API 调用封装
│       ├── app.js               # 旧版 Layui 逻辑
│       └── i18n.js              # 旧版国际化支持
├── docs/
│   └── ARCHITECTURE.md          # 架构文档
├── MIGRATION_STATUS.md           # 迁移进度报告
└── README.md
```

## 核心接口

### 认证
- `POST /api/login.php` - 用户登录
- `POST /api/logout.php` - 用户登出

### 到货记录
- `GET /api/landing.php?action=list` - 获取列表
- `POST /api/landing.php?action=add` - 新增
- `POST /api/landing.php?action=edit` - 编辑
- `POST /api/landing.php?action=delete` - 删除

### 采购记录
- `GET /api/purchase.php?action=list` - 获取列表
- `POST /api/purchase.php?action=generate` - 从到货生成
- `POST /api/purchase.php?action=sendEmail` - 发送邮件

### 销售记录
- `GET /api/sales.php?action=list` - 获取列表
- `POST /api/sales.php?action=generate` - 从采购生成

### 基础数据管理
- `GET /api/fleet.php?action=list` - 船队列表
- `GET /api/boat-management.php?action=list` - 船舶列表
- `GET /api/printer.php?action=list` - 打印机列表
- `POST /api/printer.php?action=test` - 测试打印机
- `POST /api/printer.php?action=setDefault` - 设置默认打印机
- `GET /api/units.php?action=list` - 单位列表
- `GET /api/supplier-stock-price.php?action=list` - 供应商定价列表

### 报表统计
- `GET /api/monthly-statistics.php` - 月度统计数据

## 邮件配置

编辑 `api/send-email.php`：

```php
$mail->Host     = 'smtp.gmail.com';
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password'; // Gmail 应用专用密码
$mail->Port     = 587;
```

## 打印机配置

系统支持 ESC/POS 协议的网络打印机（如佳博、芯烨等）：

1. 在「打印机管理」中添加打印机
2. 填写 IP 地址和端口（默认 9100）
3. 点击「测试」验证连接
4. 可设置默认打印机

## 测试账号

- 用户名：`admin`，密码：`123456`
- 用户名：`testuser`，密码：`123456`

## 常见问题

**Q: 登录后自动退出？**
→ 检查 PHP Session 存储路径权限

**Q: 表格数据不显示？**
→ 检查数据库连接和浏览器 Network 面板 API 响应

**Q: 生成采购单提示"已生成"？**
→ 每个到货记录只能生成一次，在采购记录页面查找

## 迁移说明

项目已完成从 Layui 到 Tailwind CSS 的前端迁移，详见 [MIGRATION_STATUS.md](MIGRATION_STATUS.md)。

- **新版**：使用 Tailwind CSS，支持移动端响应式和双语切换
- **旧版**：保留 Layui 版本供对比参考

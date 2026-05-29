# 渔业数据管理系统 (Fishery Data Management System)

基于 Laravel 13 框架重构的渔业业务数据管理系统，用于管理渔船到货记录、采购订单、销售订单及基础数据。

## 核心功能

### 业务记录管理
- **到货记录 (Landing)**：渔船捕获数据录入与管理，自动生成采购单
- **采购记录 (Purchase)**：采购订单管理，支持邮件发送和打印
- **销售记录 (Sales)**：销售订单管理，支持导出和打印

### 基础数据管理
- **供应商管理**：管理供应商信息，关联船队
- **船队管理**：管理船队，支持船只多选关联
- **船舶管理**：管理船舶信息，支持船队多选关联
- **港口管理**：管理港口/产地信息
- **库存管理**：管理鱼种、规格、产地等库存数据
- **篮子管理**：管理篮子类型及重量
- **客户管理**：管理客户信息

### 系统特性
- **数据联动**：Landing → Purchase → Sales 自动关联
- **重量计算**：LandedKG = L-Weight - (B-Weight × BinQty)
- **邮件通知**：支持发送带 PDF 附件的邮件
- **双语言支持**：中文/English 实时切换
- **移动端适配**：响应式设计

## 业务流程

```
渔船到货 (Landing)
    │
    ├─ 自动生成采购单 (PurchaseID = LandingID + 50000)
    │   └─ LandedKG = L-Weight - (B-Weight × BinQty)
    │   └─ GreenKG = LandedKG × Conversion
    │
    └─ 采购单 → 生成销售单 (SalesID = PurchaseID + 20000)
        └─ N-Weight = LandedKG
        └─ G-Weight = N-Weight + (BinQty × B-Weight)
        └─ Amount = N-Weight × Price
```

## 技术栈

| 层级 | 技术 | 版本 |
|------|------|------|
| **后端框架** | Laravel | 13.x |
| **PHP** | PHP | 8.4+ |
| **数据库** | MySQL | 5.7+ |
| **前端** | Tailwind CSS + Vanilla JS | 3.x |
| **邮件** | Symfony Mailer (SMTP) | - |
| **PDF** | jsPDF | 2.5.1 |
| **Excel** | XLSX.js | 0.18.5 |
| **容器化** | Docker + Docker Compose | - |

---

## 一、快速开始（本地开发）

### 1.1 环境要求

- Docker Desktop >= 4.0
- Docker Compose >= 2.0
- Git
- MySQL 5.7+ (本地)

### 1.2 启动步骤

```bash
# 1. 克隆项目
git clone <repository-url>
cd table-editor

# 2. 配置后端环境变量
cd backend
cp .env.example .env
# 编辑 .env 配置数据库连接（连接本地 MySQL）

# 3. 返回项目根目录，启动 Docker 服务
cd ..
docker-compose up -d

# 4. 查看服务状态
docker-compose ps

# 5. 查看日志
docker-compose logs -f
```

### 1.3 访问地址

- **前端**：http://localhost:8080
- **API**：http://localhost:8080/api
- **测试账号**：`admin` / `123456`

### 1.4 容器说明

| 容器 | 基础镜像 | 端口 | 功能 |
|-----|---------|------|------|
| fishery-frontend | nginx:1.25-alpine | 8080:80 | 前端静态文件服务 |
| fishery-backend | php:8.4-fpm-alpine | 8001:8001 | Laravel API 服务 |

---

## 二、常用命令

### Docker 操作

```bash
# 启动服务
docker-compose up -d

# 停止服务
docker-compose down

# 重启服务
docker-compose restart

# 查看日志
docker-compose logs -f [service-name]

# 进入容器
docker exec -it fishery-frontend sh
docker exec -it fishery-backend sh

# 查看容器状态
docker-compose ps

# 清理所有容器和数据
docker-compose down -v
```

### 后端开发

```bash
# 进入后端容器
docker exec -it fishery-backend sh

# 运行测试
php artisan test

# 清除缓存
php artisan config:clear
php artisan route:clear

# 查看路由
php artisan route:list --path=api

# 安装依赖
composer install
```

### 前端开发

```bash
# 修改前端文件（开发环境自动生效）
vim frontend/public/index.html

# 刷新浏览器即可看到变化

# 生产环境需要重新构建
docker-compose build frontend
docker-compose up -d frontend
```

---

## 三、项目架构

### 3.1 目录结构

```
table-editor/
├── frontend/              # 前端项目（Nginx 容器）
│   └── public/           # 静态文件
│       └── index.html   # 主入口
│
├── backend/               # 后端项目（Laravel 容器）
│   ├── app/              # 应用代码
│   │   ├── Http/Controllers/Api/  # API 控制器
│   │   ├── Models/               # Eloquent 模型
│   │   └── Services/             # 业务逻辑层
│   ├── routes/           # 路由配置
│   ├── config/           # 配置文件
│   └── .env              # 环境配置
│
├── docker/                # Docker 配置
│   ├── frontend/         # 前端容器配置
│   │   ├── Dockerfile
│   │   └── nginx.conf
│   └── backend/          # 后端容器配置
│       ├── Dockerfile
│       └── php.ini
│
├── database/             # 数据库相关
│   └── sql_change/       # SQL 变更管理
│
├── docs/                 # 文档
│   ├── ARCHITECTURE.md   # 技术架构详解
│   └── STATISTICS_MODULE_README.md  # 统计模块说明
│
├── docker-compose.yml    # Docker Compose 配置
└── README.md             # 本文档
```

### 3.2 架构图

```
┌─────────────────────────────────────────────────────────────────┐
│                         用户浏览器                               │
│  ┌─────────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐        │
│  │ Tailwind CSS│  │ XLSX.js │  │ ECharts │  │ jsPDF   │        │
│  └─────────────┘  └─────────┘  └─────────┘  └─────────┘        │
└─────────────────────────────────────────────────────────────────┘
                              │ HTTP
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              Frontend Container (Nginx)                          │
│         Port: 8080 → 80                                          │
│         - 静态文件服务                                            │
│         - API 代理到后端容器                                       │
└─────────────────────────────────────────────────────────────────┘
                              │ /api → proxy_pass
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              Backend Container (PHP-FPM)                         │
│         Port: 8001                                               │
│         - Laravel API 服务                                        │
│         - 业务逻辑处理                                             │
└─────────────────────────────────────────────────────────────────┘
                              │ PDO
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              MySQL Database (本地)                               │
│         Port: 3306                                               │
│         - 数据持久化                                               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 四、API 端点

### 认证
```
POST /api/login          # 登录
POST /api/logout         # 登出
```

### 到货记录 (Landing)
```
GET    /api/landings              # 列表
GET    /api/landings/{id}         # 详情
POST   /api/landings              # 新增 (自动生成 Purchase)
PUT    /api/landings/{id}         # 更新 (同步 Purchase)
DELETE /api/landings/{id}         # 删除 (级联删除 Purchase + Sales)
```

### 采购记录 (Purchase)
```
GET    /api/purchases             # 列表
GET    /api/purchases/{id}        # 详情
POST   /api/purchases             # 新增
PUT    /api/purchases/{id}        # 更新
DELETE /api/purchases/{id}        # 删除
POST   /api/purchases/generate-sales/{id}  # 生成销售单
```

### 销售记录 (Sales)
```
GET    /api/sales                 # 列表
GET    /api/sales/{id}            # 详情
POST   /api/sales                 # 新增
PUT    /api/sales/{id}            # 更新
DELETE /api/sales/{id}            # 删除
```

### 基础数据
```
GET    /api/data/{table}          # 获取基础数据
POST   /api/data/{table}          # 新增
PUT    /api/data/{table}/{id}     # 更新
DELETE /api/data/{table}/{id}     # 删除
```

### 邮件
```
POST   /api/email/send            # 发送邮件 (带 PDF 附件)
```

---

## 五、数据联动规则

### Landing → Purchase
- **触发**：Landing 创建/更新时
- **PurchaseID** = LandingID + 50000
- **LandedKG** = L-Weight - (B-Weight × BinQty)
- **GreenKG** = LandedKG × Conversion
- **Total** = LandedKG × Price

### Purchase → Sales
- **触发**：调用 generate-sales API
- **SalesID** = PurchaseID + 20000
- **N-Weight** = LandedKG
- **G-Weight** = N-Weight + (BinQty × B-Weight)
- **Amount** = N-Weight × Price

### 级联删除
- Landing 删除 → Purchase 删除 → Sales 删除
- Purchase 删除 → Sales 删除

---

## 六、生产环境部署

### 6.1 服务器要求

- **操作系统**: Ubuntu 20.04/22.04 或 CentOS 7/8
- **CPU**: 2 核心以上
- **内存**: 4GB 以上
- **硬盘**: 20GB 以上
- **网络**: 公网 IP，开放 80 和 443 端口

### 6.2 安装 Docker

```bash
# Ubuntu 安装 Docker
apt update && apt upgrade -y
apt install -y apt-transport-https ca-certificates curl gnupg lsb-release

curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

apt update
apt install -y docker-ce docker-ce-cli containerd.io

systemctl start docker
systemctl enable docker

# 安装 Docker Compose
curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
```

### 6.3 部署项目

```bash
# 1. 创建项目目录
mkdir -p /opt/fishery
cd /opt/fishery

# 2. 克隆代码
git clone https://github.com/your-username/table-editor.git .

# 3. 配置环境变量
cd backend
cp .env.example .env
nano .env  # 修改数据库、邮件等配置

# 4. 设置权限
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 5. 生成应用密钥
php artisan key:generate

# 6. 返回项目根目录，构建并启动
cd ..
docker-compose up -d --build

# 7. 查看状态
docker-compose ps
```

### 6.4 配置 Nginx 反向代理

```bash
# 安装 Nginx
apt install -y nginx

# 创建站点配置
nano /etc/nginx/sites-available/fishery
```

**Nginx 配置**：

```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    location /api {
        proxy_pass http://127.0.0.1:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

```bash
# 启用站点
ln -s /etc/nginx/sites-available/fishery /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 6.5 配置 SSL 证书

```bash
# 安装 Certbot
apt install -y certbot python3-certbot-nginx

# 获取证书
certbot --nginx -d your-domain.com
```

### 6.6 日常维护

```bash
# 查看日志
docker-compose logs -f

# 更新代码
git pull
docker-compose build
docker-compose up -d

# 备份数据库
mysqldump -u root -p seafood > backup_$(date +%Y%m%d).sql
```

---

## 七、故障排查

### 容器无法启动

```bash
# 查看日志
docker-compose logs backend

# 进入容器调试
docker exec -it fishery-backend sh
```

### 数据库连接失败

```bash
# 检查数据库连接
docker exec -it fishery-backend php artisan tinker
DB::connection()->getPdo();

# 检查环境变量
docker exec -it fishery-backend cat .env | grep DB_
```

### 前端无法访问 API

```bash
# 检查 Nginx 配置
docker exec -it fishery-frontend cat /etc/nginx/conf.d/default.conf

# 测试后端 API
curl http://localhost:8001/api/health
```

### 常见问题

**Q: 登录后提示未授权？**
→ 检查 Session 配置，确保 `SESSION_DRIVER=file` 或 `database`

**Q: API 返回 404？**
→ 检查路由：`docker exec -it fishery-backend php artisan route:list --path=api`

**Q: 邮件发送失败？**
→ 检查 `.env` 中的 SMTP 配置，Gmail 需使用应用专用密码

---

## 八、开发指南

### 添加新模块

1. 创建 Model：`php artisan make:model ModelName`
2. 创建 Service：在 `app/Services/` 创建业务逻辑
3. 创建 Controller：在 `app/Http/Controllers/Api/`
4. 添加路由：在 `routes/api.php`

### 代码结构

- **Controller**：处理 HTTP 请求，调用 Service
- **Service**：业务逻辑层，处理数据联动
- **Model**：Eloquent 模型，定义关联关系

---

## 九、相关文档

- [技术架构详解](docs/ARCHITECTURE.md) - 详细的技术架构说明
- [统计模块说明](docs/STATISTICS_MODULE_README.md) - 月度统计功能文档

---

## License

MIT

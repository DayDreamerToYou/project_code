# 渔业数据管理系统 - 部署包说明
# Fishery Data Management System - Deployment Package

## 目录结构

```
deploy-package/
├── README.md              # 本说明文件
├── bin/
│   └── deploy.sh          # 一键部署脚本
├── config/
│   ├── db.php.template    # 数据库配置模板
│   └── .env.template      # 环境变量模板
├── database/              # 数据库文件
├── nginx/
│   └── table-editor.conf  # Nginx 配置模板
├── scripts/
│   ├── init-db.sh         # 数据库初始化脚本
│   ├── backup.sh          # 数据库备份脚本
│   └── update.sh          # 系统更新脚本
└── install.sh             # 主安装脚本
```

## 快速开始

### 方法一: 一键部署 (推荐)

1. 上传整个部署包到服务器
2. 解压:
   ```bash
   unzip deploy-package.zip -d /tmp/
   cd /tmp/deploy-package
   ```

3. 运行部署脚本:
   ```bash
   chmod +x bin/deploy.sh
   sudo bash bin/deploy.sh
   ```

4. 按照提示输入配置信息:
   - 数据库主机、端口、名称、用户名、密码
   - 网站域名
   - 网站目录
   - PHP 版本

### 方法二: 手动部署

1. 上传项目文件到网站目录:
   ```bash
   unzip deploy-package.zip -d /www/wwwroot/table-editor
   ```

2. 配置数据库:
   ```bash
   cd /www/wwwroot/table-editor
   chmod +x scripts/init-db.sh
   sudo bash scripts/init-db.sh
   ```

3. 修改数据库配置:
   编辑 `config/db.php`，填入正确的数据库信息

4. 配置 Nginx:
   ```bash
   sudo cp nginx/table-editor.conf /etc/nginx/conf.d/
   # 编辑配置文件，替换 DOMAIN 和 PHP_VERSION
   sudo nginx -t
   sudo systemctl reload nginx
   ```

5. 设置文件权限:
   ```bash
   sudo chown -R www:www /www/wwwroot/table-editor
   sudo chmod -R 755 /www/wwwroot/table-editor
   ```

## 系统要求

- CentOS 7+ / Ubuntu 18+ / Debian 10+
- PHP 7.4+ (推荐 PHP 8.2)
- MySQL 5.7+ / MariaDB 10.3+
- Nginx 1.18+
- 至少 1GB 内存
- 至少 5GB 可用磁盘空间

## 安装依赖

### CentOS/RHEL

```bash
yum install -y php php-fpm php-mysql php-json php-mbstring php-gd php-xml php-curl nginx mysql-server
```

### Ubuntu/Debian

```bash
apt update
apt install -y php php-fpm php-mysql php-json php-mbstring php-gd php-xml php-curl nginx mysql-server
```

### 启动服务

```bash
# CentOS
systemctl enable php-fpm nginx mysqld
systemctl start php-fpm nginx mysqld

# Ubuntu/Debian
systemctl enable php*-fpm nginx mysql
systemctl start php*-fpm nginx mysql
```

## 数据库配置

如果选择手动配置数据库，创建 MySQL 用户和数据库:

```sql
CREATE DATABASE table_editor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'table_editor'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON table_editor.* TO 'table_editor'@'localhost';
FLUSH PRIVILEGES;
```

导入数据库结构:
```bash
mysql -u root -p table_editor < database/seafood_backup_20260409.sql
```

## 默认账号

- 用户名: `admin`
- 密码: `123456`

**重要**: 首次登录后请立即修改密码!

## 常用命令

### 重启服务

```bash
# Nginx
sudo systemctl reload nginx

# PHP-FPM (CentOS)
sudo systemctl restart php-fpm

# PHP-FPM (Ubuntu/Debian)
sudo systemctl restart php*-fpm

# MySQL
sudo systemctl restart mysqld  # CentOS
sudo systemctl restart mysql   # Ubuntu/Debian
```

### 查看日志

```bash
# Nginx 访问日志
sudo tail -f /var/log/nginx/table-editor_access.log

# Nginx 错误日志
sudo tail -f /var/log/nginx/table-editor_error.log

# PHP-FPM 日志
sudo tail -f /var/log/php-fpm/error.log
```

### 数据库备份

```bash
# 手动备份
mysqldump -u root -p table_editor > backup_$(date +%Y%m%d).sql

# 使用备份脚本
chmod +x scripts/backup.sh
sudo bash scripts/backup.sh
```

## 故障排除

### 1. 502 Bad Gateway

- 检查 PHP-FPM 是否运行: `systemctl status php*-fpm`
- 检查 Nginx 配置中的 PHP-FPM socket 路径
- 查看错误日志: `/var/log/nginx/table-editor_error.log`

### 2. 数据库连接失败

- 检查 `config/db.php` 中的数据库配置
- 测试数据库连接: `mysql -h localhost -u username -p database_name`
- 检查 MySQL 服务状态: `systemctl status mysqld`

### 3. 权限问题

```bash
sudo chown -R www:www /www/wwwroot/table-editor
sudo chmod -R 755 /www/wwwroot/table-editor
```

### 4. Session 无法写入

```bash
sudo chown -R www:www /var/lib/php/session
```

## SSL/HTTPS 配置

1. 获取 SSL 证书 (推荐 Let's Encrypt):

```bash
# 安装 Certbot
yum install -y certbot python3-certbot-nginx  # CentOS
apt install -y certbot python3-certbot-nginx   # Ubuntu

# 获取证书
certbot --nginx -d your-domain.com
```

2. 或者手动配置 SSL，参考 `nginx/table-editor.conf` 中的 HTTPS 配置

## 目录说明

- `/www/wwwroot/table-editor/` - 项目根目录
- `/www/wwwroot/table-editor/api/` - API 接口
- `/www/wwwroot/table-editor/public/` - 网站根目录 (document root)
- `/www/wwwroot/table-editor/config/` - 配置文件
- `/www/wwwroot/table-editor/database/` - 数据库文件
- `/www/wwwroot/table-editor/backup/` - 备份目录

## 安全建议

1. 修改默认管理员密码
2. 配置 HTTPS (使用有效证书)
3. 定期备份数据库
4. 限制 phpMyAdmin 访问 (如果使用)
5. 禁用不必要的 PHP 函数
6. 启用防火墙，只开放必要端口 (80, 443)

## 技术支持

如有问题，请检查:
- 系统日志: `/var/log/messages` (CentOS) 或 `/var/log/syslog` (Ubuntu)
- Nginx 错误日志: `/var/log/nginx/error.log`
- PHP 错误日志: PHP-FPM 配置中的 error_log

---

**版本**: 2.0
**更新日期**: 2026-04-10

#!/bin/bash
# ============================================
# 渔业数据管理系统 - 一键部署脚本 v2.0
# Fishery Data Management System - Deploy Script
# ============================================

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 脚本目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PARENT_DIR="$(dirname "$SCRIPT_DIR")"
PROJECT_NAME="table-editor"
PACKAGE_NAME="fishery-data-management"

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}  渔业数据管理系统 - 一键部署脚本 v2.0${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "${BLUE}项目目录: ${PARENT_DIR}${NC}"
echo ""

# 检查是否为 root 用户
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}错误: 请使用 root 用户运行此脚本${NC}"
    echo "或者使用: sudo bash deploy.sh"
    exit 1
fi

# 检查必要的命令
check_command() {
    if ! command -v $1 &> /dev/null; then
        echo -e "${RED}错误: 缺少必要的命令: $1${NC}"
        echo "请先安装: yum install $1 或 apt install $1"
        exit 1
    fi
}

echo -e "${YELLOW}检查必要的命令...${NC}"
check_command php
check_command mysql
check_command tar
check_command nginx
check_command unzip
echo -e "${GREEN}✓ 所有必要命令已安装${NC}"
echo ""

# 获取数据库配置
echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  数据库配置${NC}"
echo -e "${YELLOW}========================================${NC}"

read -p "数据库主机 [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "数据库端口 [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

read -p "数据库名称 [table_editor]: " DB_NAME
DB_NAME=${DB_NAME:-table_editor}

read -p "数据库用户名: " DB_USER
while [ -z "$DB_USER" ]; do
    echo -e "${RED}用户名不能为空${NC}"
    read -p "数据库用户名: " DB_USER
done

read -s -p "数据库密码: " DB_PASS
echo ""
while [ -z "$DB_PASS" ]; do
    echo -e "${RED}密码不能为空${NC}"
    read -s -p "数据库密码: " DB_PASS
    echo ""
done

# 获取网站配置
echo ""
echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  网站配置${NC}"
echo -e "${YELLOW}========================================${NC}"

read -p "网站域名 (例如: seafood.example.com) [localhost]: " DOMAIN
DOMAIN=${DOMAIN:-localhost}

read -p "网站目录 [/www/wwwroot/${PROJECT_NAME}]: " WEB_ROOT
WEB_ROOT=${WEB_ROOT:-/www/wwwroot/${PROJECT_NAME}}

read -p "PHP 版本 (例如: 82, 74) [82]: " PHP_VERSION
PHP_VERSION=${PHP_VERSION:-82}

# SSL 配置
echo ""
echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  SSL 配置 (可选)${NC}"
echo -e "${YELLOW}========================================${NC}"
read -p "是否配置 SSL? (y/n) [n]: " ENABLE_SSL
ENABLE_SSL=${ENABLE_SSL:-n}

SSL_CERT=""
SSL_KEY=""
if [ "$ENABLE_SSL" = "y" ] || [ "$ENABLE_SSL" = "Y" ]; then
    read -p "SSL 证书路径 [/etc/ssl/certs/server.crt]: " SSL_CERT
    SSL_CERT=${SSL_CERT:-/etc/ssl/certs/server.crt}
    read -p "SSL 密钥路径 [/etc/ssl/private/server.key]: " SSL_KEY
    SSL_KEY=${SSL_KEY:-/etc/ssl/private/server.key}
fi

echo ""
echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  开始部署...${NC}"
echo -e "${YELLOW}========================================${NC}"

# 0. 备份现有网站（如果存在）
if [ -d "$WEB_ROOT" ]; then
    BACKUP_DIR="${WEB_ROOT}_backup_$(date +%Y%m%d_%H%M%S)"
    echo -e "${YELLOW}[0/7] 备份现有网站到 ${BACKUP_DIR}...${NC}"
    mv "$WEB_ROOT" "$BACKUP_DIR"
    echo -e "${GREEN}✓ 备份完成${NC}"
fi

# 1. 创建网站目录
echo -e "${YELLOW}[1/7] 创建网站目录...${NC}"
mkdir -p "$WEB_ROOT"
echo -e "${GREEN}✓ 网站目录创建成功${NC}"

# 2. 复制文件
echo -e "${YELLOW}[2/7] 复制项目文件...${NC}"
cp -r "$PARENT_DIR/"* "$WEB_ROOT/" 2>/dev/null || true
echo -e "${GREEN}✓ 文件复制完成${NC}"

# 3. 创建数据库
echo -e "${YELLOW}[3/7] 创建数据库...${NC}"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || {
    echo -e "${RED}错误: 无法连接数据库，请检查数据库配置${NC}"
    exit 1
}
echo -e "${GREEN}✓ 数据库创建成功${NC}"

# 4. 导入数据库
echo -e "${YELLOW}[4/7] 导入数据库结构...${NC}"
DB_SCHEMA="${WEB_ROOT}/database/seafood_backup_20260409.sql"
if [ -f "$DB_SCHEMA" ]; then
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$DB_SCHEMA"
    echo -e "${GREEN}✓ 数据库导入成功${NC}"
else
    # 尝试使用主数据库文件
    DB_SCHEMA_MAIN="${WEB_ROOT}/database/fishery_schema.sql"
    if [ -f "$DB_SCHEMA_MAIN" ]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$DB_SCHEMA_MAIN"
        echo -e "${GREEN}✓ 数据库导入成功${NC}"
    else
        echo -e "${YELLOW}警告: 未找到数据库文件，跳过数据库导入${NC}"
    fi
fi

# 5. 更新数据库配置
echo -e "${YELLOW}[5/7] 更新数据库配置文件...${NC}"
cat > "${WEB_ROOT}/config/db.php" << 'EOF'
<?php
/**
 * ============================================
 * 数据库配置文件
 * 自动生成于部署脚本
 * ============================================
 */

if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}

/**
 * 数据库配置类
 */
class Database {
    private static $host = 'DB_HOST_PLACEHOLDER';
    private static $port = 'DB_PORT_PLACEHOLDER';
    private static $db_name = 'DB_NAME_PLACEHOLDER';
    private static $username = 'DB_USER_PLACEHOLDER';
    private static $password = 'DB_PASS_PLACEHOLDER';
    private static $charset = 'utf8mb4';

    public static function getConnection() {
        static $conn = null;

        if ($conn !== null) {
            return $conn;
        }

        try {
            $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$db_name . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $conn = new PDO($dsn, self::$username, self::$password, $options);
            return $conn;

        } catch(PDOException $e) {
            error_log("数据库连接失败: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '数据库连接失败',
                'error' => '系统错误，请联系管理员'
            ]);
            exit;
        }
    }

    public static function getInstance() {
        return self::getConnection();
    }

    public static function testConnection() {
        try {
            $conn = self::getConnection();
            return $conn !== null;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
EOF

# 替换占位符
sed -i "s/DB_HOST_PLACEHOLDER/${DB_HOST}/g" "${WEB_ROOT}/config/db.php"
sed -i "s/DB_PORT_PLACEHOLDER/${DB_PORT}/g" "${WEB_ROOT}/config/db.php"
sed -i "s/DB_NAME_PLACEHOLDER/${DB_NAME}/g" "${WEB_ROOT}/config/db.php"
sed -i "s/DB_USER_PLACEHOLDER/${DB_USER}/g" "${WEB_ROOT}/config/db.php"
sed -i "s/DB_PASS_PLACEHOLDER/${DB_PASS}/g" "${WEB_ROOT}/config/db.php"

echo -e "${GREEN}✓ 数据库配置已更新${NC}"

# 6. 设置文件权限
echo -e "${YELLOW}[6/7] 设置文件权限...${NC}"
chown -R www:www "$WEB_ROOT"
find "$WEB_ROOT" -type d -exec chmod 755 {} \;
find "$WEB_ROOT" -type f -exec chmod 644 {} \;
# 确保特殊文件有执行权限
chmod +x "$WEB_ROOT"/deploy.sh 2>/dev/null || true
chmod +x "$WEB_ROOT"/api/*.php 2>/dev/null || true
echo -e "${GREEN}✓ 文件权限已设置${NC}"

# 7. 配置 Nginx
echo -e "${YELLOW}[7/7] 配置 Nginx...${NC}"

# 确定 public 目录
PUBLIC_DIR="${WEB_ROOT}/public"

# 创建 HTTP 配置
NGINX_CONF_HTTP="/etc/nginx/conf.d/${PROJECT_NAME}.conf"
cat > "${NGINX_CONF_HTTP}" << EOF
server {
    listen 80;
    server_name ${DOMAIN};

    root ${PUBLIC_DIR};
    index index.html index.php;

    # 字符集
    charset utf-8;

    # 访问日志
    access_log /var/log/nginx/${PROJECT_NAME}_access.log;
    error_log /var/log/nginx/${PROJECT_NAME}_error.log;

    # 最大上传大小 (100MB)
    client_max_body_size 100M;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # PHP 配置
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
    }

    # 禁止访问隐藏文件
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # URL 重写
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # 禁止访问敏感文件
    location ~* \.(sql|bak|sh|md|txt|log)$ {
        deny all;
    }
}
EOF

# 如果启用 SSL，创建 HTTPS 配置
if [ "$ENABLE_SSL" = "y" ] || [ "$ENABLE_SSL" = "Y" ]; then
    NGINX_CONF_HTTPS="/etc/nginx/conf.d/${PROJECT_NAME}-ssl.conf"
    cat > "${NGINX_CONF_HTTPS}" << EOF
server {
    listen 443 ssl http2;
    server_name ${DOMAIN};

    ssl_certificate ${SSL_CERT};
    ssl_certificate_key ${SSL_KEY};
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;

    # 现代 SSL 配置
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;

    root ${PUBLIC_DIR};
    index index.html index.php;

    charset utf-8;

    access_log /var/log/nginx/${PROJECT_NAME}_ssl_access.log;
    error_log /var/log/nginx/${PROJECT_NAME}_ssl_error.log;

    client_max_body_size 100M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location ~* \.(sql|bak|sh|md|txt|log)$ {
        deny all;
    }
}

# HTTP 重定向到 HTTPS
server {
    listen 80;
    server_name ${DOMAIN};
    return 301 https://\$server_name\$request_uri;
}
EOF
fi

# 测试 Nginx 配置
nginx -t 2>/dev/null
if [ $? -eq 0 ]; then
    systemctl reload nginx
    echo -e "${GREEN}✓ Nginx 配置已更新${NC}"
else
    echo -e "${YELLOW}警告: Nginx 配置可能有问题，请手动检查${NC}"
fi

# 8. 创建环境配置文件 (可选，用于后续修改)
cat > "${WEB_ROOT}/.env" << EOF
# 渔业数据管理系统 - 环境配置
# 此文件由部署脚本自动生成

# 数据库配置
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}

# 网站配置
DOMAIN=${DOMAIN}
WEB_ROOT=${WEB_ROOT}
PHP_VERSION=${PHP_VERSION}

# SSL 配置
ENABLE_SSL=${ENABLE_SSL}
EOF

echo -e "${GREEN}✓ 环境配置文件已创建${NC}"

# 完成
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  部署成功!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "访问地址: ${YELLOW}http://${DOMAIN}${NC}"
if [ "$ENABLE_SSL" = "y" ] || [ "$ENABLE_SSL" = "Y" ]; then
    echo -e "安全访问: ${YELLOW}https://${DOMAIN}${NC}"
fi
echo ""
echo -e "测试账号:"
echo -e "  用户名: ${YELLOW}admin${NC}"
echo -e "  密码:   ${YELLOW}123456${NC}"
echo ""
echo -e "${RED}重要: 请尽快修改默认密码!${NC}"
echo ""
echo -e "后续操作:"
echo -e "  1. ${YELLOW}配置 HTTPS${NC} (如果未启用)"
echo -e "  2. ${YELLOW}修改默认管理员密码${NC}"
echo -e "  3. ${YELLOW}检查防火墙设置${NC}"
echo "  4. ${YELLOW}查看日志: tail -f /var/log/nginx/${PROJECT_NAME}_access.log${NC}"
echo ""
echo -e "常用命令:"
echo -e "  重启 PHP-FPM:  systemctl restart php${PHP_VERSION}-fpm"
echo -e "  重载 Nginx:    systemctl reload nginx"
echo -e "  查看错误日志:  tail -f /var/log/nginx/${PROJECT_NAME}_error.log"
echo ""

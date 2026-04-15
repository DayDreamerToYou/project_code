#!/bin/bash
# ============================================
# 渔业数据管理系统 - 主安装脚本
# Fishery Data Management System - Main Installer
# ============================================

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# 脚本目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# 显示欢迎信息
show_banner() {
    echo -e "${CYAN}╔═══════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║     渔业数据管理系统 - 安装向导 v2.0        ║${NC}"
    echo -e "${CYAN}║     Fishery Data Management System        ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════╝${NC}"
    echo ""
}

# 检查系统环境
check_environment() {
    echo -e "${YELLOW}[1/5] 检查系统环境...${NC}"

    if [ "$EUID" -ne 0 ]; then
        echo -e "${RED}错误: 请使用 root 用户运行此脚本${NC}"
        echo "使用: sudo bash install.sh"
        exit 1
    fi

    # 检查必要命令
    local missing_cmds=()

    for cmd in php mysql nginx tar gzip; do
        if ! command -v $cmd &> /dev/null; then
            missing_cmds+=($cmd)
        fi
    done

    if [ ${#missing_cmds[@]} -ne 0 ]; then
        echo -e "${RED}错误: 缺少必要的命令: ${missing_cmds[*]}${NC}"
        echo ""
        echo "请先安装所需软件:"
        echo "  CentOS: yum install -y php php-fpm php-mysql nginx mysql-server"
        echo "  Ubuntu: apt install -y php php-fpm php-mysql nginx mysql-server"
        exit 1
    fi

    echo -e "${GREEN}✓ 系统环境检查通过${NC}"
    echo ""
}

# 获取配置信息
get_config() {
    echo -e "${YELLOW}[2/5] 配置信息${NC}"
    echo ""

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

    read -p "网站域名 [localhost]: " DOMAIN
    DOMAIN=${DOMAIN:-localhost}

    read -p "安装目录 [/www/wwwroot/table-editor]: " INSTALL_DIR
    INSTALL_DIR=${INSTALL_DIR:-/www/wwwroot/table-editor}

    read -p "PHP 版本 (74/82) [82]: " PHP_VERSION
    PHP_VERSION=${PHP_VERSION:-82}

    echo ""
}

# 创建目录和复制文件
setup_files() {
    echo -e "${YELLOW}[3/5] 设置文件...${NC}"

    # 复制文件到安装目录
    echo "  - 复制项目文件..."
    mkdir -p "$INSTALL_DIR"
    cp -r "$SCRIPT_DIR"/* "$INSTALL_DIR/" 2>/dev/null || true

    # 复制 deploy-package 内容到根目录
    if [ -d "$INSTALL_DIR/deploy-package" ]; then
        cp -r "$INSTALL_DIR/deploy-package/"* "$INSTALL_DIR/" 2>/dev/null || true
        rm -rf "$INSTALL_DIR/deploy-package"
    fi

    echo -e "${GREEN}✓ 文件设置完成${NC}"
    echo ""
}

# 配置数据库
setup_database() {
    echo -e "${YELLOW}[4/5] 配置数据库...${NC}"

    # 创建数据库
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e \
        "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || {
        echo -e "${RED}错误: 无法连接数据库${NC}"
        exit 1
    }

    # 导入数据库
    DB_FILE="$INSTALL_DIR/database/seafood_backup_20260409.sql"
    if [ -f "$DB_FILE" ]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$DB_FILE" 2>/dev/null
        echo -e "${GREEN}✓ 数据库导入成功${NC}"
    fi

    # 更新配置文件
    cat > "$INSTALL_DIR/config/db.php" << EOF
<?php
/**
 * 数据库配置文件
 * 自动生成于安装脚本
 */

if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}

class Database {
    private static \$host = '${DB_HOST}';
    private static \$port = '${DB_PORT}';
    private static \$db_name = '${DB_NAME}';
    private static \$username = '${DB_USER}';
    private static \$password = '${DB_PASS}';
    private static \$charset = 'utf8mb4';

    public static function getConnection() {
        static \$conn = null;
        if (\$conn !== null) return \$conn;

        try {
            \$dsn = "mysql:host=" . self::\$host . ";port=" . self::\$port . ";dbname=" . self::\$db_name . ";charset=" . self::\$charset;
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            \$conn = new PDO(\$dsn, self::\$username, self::\$password, \$options);
            return \$conn;
        } catch(PDOException \$e) {
            error_log("数据库连接失败: " . \$e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '数据库连接失败']);
            exit;
        }
    }

    public static function getInstance() {
        return self::getConnection();
    }
}
?>
EOF

    # 创建环境配置文件
    cat > "$INSTALL_DIR/.env" << EOF
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DOMAIN=${DOMAIN}
INSTALL_DIR=${INSTALL_DIR}
PHP_VERSION=${PHP_VERSION}
EOF

    # 设置权限
    chown -R www:www "$INSTALL_DIR"
    find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
    find "$INSTALL_DIR" -type f -exec chmod 644 {} \;

    echo -e "${GREEN}✓ 数据库配置完成${NC}"
    echo ""
}

# 配置 Nginx
setup_nginx() {
    echo -e "${YELLOW}[5/5] 配置 Nginx...${NC}"

    NGINX_CONF="/etc/nginx/conf.d/table-editor.conf"

    cat > "$NGINX_CONF" << EOF
server {
    listen 80;
    server_name ${DOMAIN};

    root ${INSTALL_DIR}/public;
    index index.html index.php;

    charset utf-8;

    access_log /var/log/nginx/table-editor_access.log;
    error_log /var/log/nginx/table-editor_error.log;

    client_max_body_size 100M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\. {
        deny all;
    }

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location ~* \.(sql|bak|sh|md)$ {
        deny all;
    }
}
EOF

    # 测试并重载 Nginx
    if nginx -t 2>/dev/null; then
        systemctl reload nginx
        echo -e "${GREEN}✓ Nginx 配置完成${NC}"
    else
        echo -e "${YELLOW}警告: Nginx 配置有问题，请手动检查${NC}"
    fi

    echo ""
}

# 完成
show_complete() {
    echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║           安装完成!                         ║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "访问地址: ${YELLOW}http://${DOMAIN}${NC}"
    echo ""
    echo -e "测试账号:"
    echo -e "  用户名: ${YELLOW}admin${NC}"
    echo -e "  密码:   ${YELLOW}123456${NC}"
    echo ""
    echo -e "${RED}重要: 请立即修改默认密码!${NC}"
    echo ""
    echo -e "后续操作:"
    echo -e "  1. 配置 HTTPS (推荐使用 Let's Encrypt)"
    echo -e "  2. 修改管理员密码"
    echo -e "  3. 设置防火墙: firewall-cmd --add-service=http --permanent"
    echo ""
}

# 主函数
main() {
    show_banner
    check_environment
    get_config
    setup_files
    setup_database
    setup_nginx
    show_complete
}

main "$@"

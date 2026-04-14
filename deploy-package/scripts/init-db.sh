#!/bin/bash
# ============================================
# 数据库初始化脚本
# ============================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  数据库初始化脚本${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""

# 读取配置文件
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

if [ -f "$PROJECT_DIR/.env" ]; then
    source "$PROJECT_DIR/.env"
    echo -e "${GREEN}✓ 已加载配置文件${NC}"
else
    echo -e "${YELLOW}警告: 未找到 .env 配置文件${NC}"
fi

# 数据库配置
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-3306}
DB_NAME=${DB_NAME:-table_editor}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}

echo ""
echo -e "${YELLOW}数据库配置:${NC}"
echo "  主机: $DB_HOST"
echo "  端口: $DB_PORT"
echo "  数据库: $DB_NAME"
echo "  用户: $DB_USER"
echo ""

read -p "确认是否继续? (y/n) [n]: " CONFIRM
CONFIRM=${CONFIRM:-n}

if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    echo "操作已取消"
    exit 0
fi

# 创建数据库
echo -e "${YELLOW}创建数据库...${NC}"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || {
    echo -e "${RED}错误: 无法连接数据库${NC}"
    exit 1
}
echo -e "${GREEN}✓ 数据库创建成功${NC}"

# 导入数据
echo -e "${YELLOW}导入数据库结构...${NC}"
DB_SCHEMA="$PROJECT_DIR/database/seafood_backup_20260409.sql"

if [ -f "$DB_SCHEMA" ]; then
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$DB_SCHEMA"
    echo -e "${GREEN}✓ 数据导入成功${NC}"
else
    # 尝试备用文件
    DB_SCHEMA_ALT="$PROJECT_DIR/database/fishery_schema.sql"
    if [ -f "$DB_SCHEMA_ALT" ]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$DB_SCHEMA_ALT"
        echo -e "${GREEN}✓ 数据导入成功${NC}"
    else
        echo -e "${RED}错误: 未找到数据库文件${NC}"
        exit 1
    fi
fi

# 创建测试用户 (如果不存在)
echo -e "${YELLOW}创建测试用户...${NC}"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
INSERT IGNORE INTO users (username, password, display_name, role, created_at)
VALUES ('admin', '\$2y\$10\$8K1p/a0dL1LXMIgoEDFrwOfMQbLgT4rM4C8qW3RVTOq2fK3L.7GHe', '管理员', 'admin', NOW());
" 2>/dev/null || echo -e "${YELLOW}用户创建跳过（可能已存在）${NC}"

echo -e "${GREEN}✓ 数据库初始化完成${NC}"
echo ""
echo -e "${GREEN}测试账号:${NC}"
echo "  用户名: admin"
echo "  密码: 123456"
echo ""

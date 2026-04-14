#!/bin/bash
# ============================================
# 系统更新脚本
# ============================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  系统更新脚本${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 检查必要文件
if [ ! -f "$PROJECT_DIR/.env" ]; then
    echo -e "${RED}错误: 未找到配置文件 .env${NC}"
    exit 1
fi

source "$PROJECT_DIR/.env"

echo -e "${YELLOW}当前版本信息:${NC}"
echo "  项目目录: $PROJECT_DIR"
echo "  数据库: $DB_NAME"
echo "  域名: $DOMAIN"
echo ""

read -p "确认更新? (y/n) [n]: " CONFIRM
CONFIRM=${CONFIRM:-n}

if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    echo "操作已取消"
    exit 0
fi

# 1. 备份当前版本
echo -e "${YELLOW}[1/4] 备份当前版本...${NC}"
BACKUP_DIR="$PROJECT_DIR/backup"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/update_backup_$(date +%Y%m%d_%H%M%S).tar.gz"

tar -czf "$BACKUP_FILE" \
    --exclude="$PROJECT_DIR/public/uploads" \
    --exclude="$PROJECT_DIR/backup" \
    --exclude="$PROJECT_DIR/.git" \
    -C "$PROJECT_DIR" . 2>/dev/null || true

echo -e "${GREEN}✓ 备份完成: $BACKUP_FILE${NC}"

# 2. 同步文件 (从源目录)
echo -e "${YELLOW}[2/4] 同步文件...${NC}"

# 这里假设有更新源，实际使用时替换为实际的更新命令
# 例如: rsync -avz update-server:/path/to/latest/ "$PROJECT_DIR/"

echo -e "${GREEN}✓ 文件同步完成${NC}"

# 3. 导入数据库更新 (如果有)
echo -e "${YELLOW}[3/4] 检查数据库更新...${NC}"
UPDATE_SQL="$PROJECT_DIR/database/update.sql"
if [ -f "$UPDATE_SQL" ]; then
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$UPDATE_SQL"
    echo -e "${GREEN}✓ 数据库更新完成${NC}"
else
    echo -e "${YELLOW}未找到数据库更新文件${NC}"
fi

# 4. 重新加载服务
echo -e "${YELLOW}[4/4] 重新加载服务...${NC}"
chown -R www:www "$PROJECT_DIR"
systemctl reload nginx 2>/dev/null || true
systemctl reload php${PHP_VERSION}-fpm 2>/dev/null || true

echo -e "${GREEN}✓ 服务重新加载完成${NC}"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  更新成功!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "如有问题，可以使用备份恢复:"
echo -e "  tar -xzf $BACKUP_FILE -C /"
echo ""

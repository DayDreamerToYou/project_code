#!/bin/bash
# ============================================
# 数据库备份脚本
# ============================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 配置
BACKUP_DIR="/www/wwwroot/table-editor/backup"
DATE=$(date +%Y%m%d_%H%M%S)
PROJECT_NAME="table-editor"

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  数据库备份脚本${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""

# 读取配置文件
PROJECT_DIR="/www/wwwroot/table-editor"

if [ -f "$PROJECT_DIR/.env" ]; then
    source "$PROJECT_DIR/.env"
fi

DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-3306}
DB_NAME=${DB_NAME:-table_editor}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}

# 创建备份目录
mkdir -p "$BACKUP_DIR"

# 执行备份
BACKUP_FILE="${BACKUP_DIR}/${PROJECT_NAME}_backup_${DATE}.sql"

echo -e "${YELLOW}正在备份数据库: $DB_NAME${NC}"
echo -e "备份文件: $BACKUP_FILE"

mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --events \
    "$DB_NAME" > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    # 压缩备份文件
    gzip "$BACKUP_FILE"
    BACKUP_FILE="${BACKUP_FILE}.gz"

    echo -e "${GREEN}✓ 备份成功!${NC}"
    echo "备份文件: $BACKUP_FILE"
    echo "文件大小: $(du -h "$BACKUP_FILE" | cut -f1)"
else
    echo -e "${RED}✗ 备份失败!${NC}"
    rm -f "$BACKUP_FILE"
    exit 1
fi

# 清理旧备份 (保留最近 30 天)
echo -e "${YELLOW}清理旧备份文件 (保留 30 天)...${NC}"
find "$BACKUP_DIR" -name "${PROJECT_NAME}_backup_*.sql.gz" -mtime +30 -delete
echo -e "${GREEN}✓ 清理完成${NC}"

echo ""
echo -e "${GREEN}备份完成!${NC}"
echo ""

#!/bin/bash
# 打包脚本 - 将整个项目打包成部署包

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PARENT_DIR="$(dirname "$SCRIPT_DIR")"
OUTPUT_DIR="/tmp"

echo "正在打包项目..."
echo "源目录: $PARENT_DIR"
echo "输出目录: $OUTPUT_DIR"

# 创建打包目录
PKG_DIR="$OUTPUT_DIR/fishery-deploy"
rm -rf "$PKG_DIR"
mkdir -p "$PKG_DIR"

# 复制项目文件
echo "复制项目文件..."
cp -r "$PARENT_DIR/"* "$PKG_DIR/"

# 复制数据库文件 (如果存在)
if [ -f "$PARENT_DIR/database/seafood_backup_20260409.sql" ]; then
    cp "$PARENT_DIR/database/seafood_backup_20260409.sql" "$PKG_DIR/database/"
fi

# 打包
echo "创建压缩包..."
cd "$OUTPUT_DIR"
tar -czf "fishery-data-management-$(date +%Y%m%d).tar.gz" fishery-deploy

echo ""
echo "打包完成!"
echo "打包文件: $OUTPUT_DIR/fishery-data-management-$(date +%Y%m%d).tar.gz"
echo "文件大小: $(du -h "$OUTPUT_DIR/fishery-data-management-$(date +%Y%m%d).tar.gz" | cut -f1)"

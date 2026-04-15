-- ============================================
-- 修改 tblSupplierStockPrice 表的删除字段
-- 从 IsActive 改为 is_del，与其他表保持一致
-- ============================================

USE seafood;

-- 1. 添加 is_del 字段
ALTER TABLE tblSupplierStockPrice
ADD COLUMN is_del INT(2) NOT NULL DEFAULT 0 COMMENT '是否删除：0=未删除，1=已删除'
AFTER UpdateTime;

-- 2. 将现有的 IsActive 数据迁移到 is_del
-- IsActive = 1（启用）-> is_del = 0（未删除）
-- IsActive = 0（禁用）-> is_del = 1（已删除）
UPDATE tblSupplierStockPrice
SET is_del = CASE
    WHEN IsActive = 1 THEN 0
    WHEN IsActive = 0 THEN 1
    ELSE 0
END;

-- 3. 如果确定迁移成功，可以删除 IsActive 字段（可选）
-- ALTER TABLE tblSupplierStockPrice DROP COLUMN IsActive;

-- ============================================
-- 验证修改
-- ============================================

-- 查看表结构
DESCRIBE tblSupplierStockPrice;

-- 查看数据迁移情况
SELECT
    PriceID,
    SupplierID,
    StockID,
    UnitPrice,
    IsActive,
    is_del,
    CASE
        WHEN IsActive = 1 AND is_del = 0 THEN '✓ 迁移正确（启用）'
        WHEN IsActive = 0 AND is_del = 1 THEN '✓ 迁移正确（禁用）'
        ELSE '✗ 迁移异常'
    END AS '迁移状态'
FROM tblSupplierStockPrice
ORDER BY PriceID
LIMIT 10;

-- ============================================
-- 常用查询（使用新的 is_del 字段）
-- ============================================

-- 查询所有未删除的定价
-- SELECT * FROM tblSupplierStockPrice WHERE is_del = 0;

-- 查询所有已删除的定价
-- SELECT * FROM tblSupplierStockPrice WHERE is_del = 1;

-- 统计未删除的定价数量
-- SELECT COUNT(*) as '未删除定价数' FROM tblSupplierStockPrice WHERE is_del = 1;

-- ============================================
-- 完成
-- ============================================

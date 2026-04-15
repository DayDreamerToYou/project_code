-- ============================================
-- 删除 tblSupplierStockPrice 表的 EffectiveDate 字段
-- 创建时间: 2026-01-26
-- ============================================

USE your_database_name;

-- 删除 EffectiveDate 字段
ALTER TABLE tblSupplierStockPrice
DROP COLUMN EffectiveDate;

-- 验证字段是否已删除
-- DESC tblSupplierStockPrice;
-- SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tblSupplierStockPrice';

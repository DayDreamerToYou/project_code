-- ============================================
-- 统计模块性能优化索引
-- 说明：为 tblPurchase 和 tblPurchaseDetail 表创建联合索引以提升统计查询性能
-- ============================================

USE seafood;

-- 1. 为 tblPurchase 创建索引
ALTER TABLE tblPurchase
ADD INDEX idx_purchase_date (PurchaseDate);

ALTER TABLE tblPurchase
ADD INDEX idx_purchase_supplier (SupplierID);

ALTER TABLE tblPurchase
ADD INDEX idx_purchase_is_del (is_del);

-- 2. 为 tblPurchaseDetail 创建索引（用于统计 GreenKG）
ALTER TABLE tblPurchaseDetail
ADD INDEX idx_purchase_detail_stats (PurchaseID, StockID, is_del);

ALTER TABLE tblPurchaseDetail
ADD INDEX idx_purchase_detail_stock (StockID);

-- 查看索引创建结果
SHOW INDEX FROM tblPurchase;
SHOW INDEX FROM tblPurchaseDetail;

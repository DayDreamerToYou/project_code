-- ============================================
-- 插入供应商-鱼种定价测试数据
-- ============================================

USE seafood;

-- 清空现有测试数据（可选）
-- DELETE FROM tblSupplierStockPrice WHERE PriceID > 0;

-- 插入测试定价数据
INSERT INTO tblSupplierStockPrice (SupplierID, StockID, UnitPrice, EffectiveDate, Remark) VALUES
-- 测试供应商的定价
(1, 'BCO/GRE', 15.50, '2026-01-01', '2026年1月协商价'),
(1, 'BUT/GRE', 18.00, '2026-01-01', '标准定价'),
(1, 'BPF/GUT', 16.50, '2026-01-15', '优惠价格'),

-- Capricorn II Fishing 的定价
(2, 'BCO/GRE', 14.50, '2026-01-01', '长期合作价'),
(2, 'BUT/GRE', 17.00, '2026-01-01', ''),
(2, 'BPF/GRE', 15.00, '2026-01-10', '冬季定价'),

-- Sven Penwarden 的定价
(3, 'BCO/GUT', 16.00, '2026-01-01', ''),
(3, 'BUT/HGU', 19.50, '2026-01-05', '高质量鱼种'),
(3, 'BFL3/GRE', 14.00, '2026-01-12', '批发价'),

-- White Acres Fishing Ltd 的定价
(4, 'BPF/GRE', 15.50, '2026-01-01', ''),
(4, 'BUT/GUT', 18.50, '2026-01-08', ''),
(4, 'BCO/HGU', 17.00, '2026-01-15', '新鲜渔获'),

-- Robert White 的定价
(5, 'BCO/GRE', 13.50, '2026-01-01', '促销价'),
(5, 'BUT/GRE', 16.00, '2026-01-01', ''),
(5, 'BPF/HGU', 15.00, '2026-01-20', ''),

-- Galeforce Fishing Limited 的定价
(6, 'BUT/GUT', 17.50, '2026-01-01', ''),
(6, 'BCO/GRE', 14.00, '2026-01-10', ''),
(6, 'BFL3/HGU', 13.50, '2026-01-18', '批量优惠'),

-- Shangri La Fishing Ltd 的定价
(7, 'BPF/GRE', 16.00, '2026-01-01', ''),
(7, 'BUT/HGU', 19.00, '2026-01-12', ''),
(7, 'BCO/GUT', 15.50, '2026-01-25', ''),

-- Seafood Direct 的定价
(8, 'BCO/GRE', 14.50, '2026-01-01', '内部定价'),
(8, 'BUT/GRE', 17.00, '2026-01-01', ''),
(8, 'BPF/GUT', 16.00, '2026-01-08', ''),

-- Golden Harvest Contractors Ltd 的定价
(9, 'BUT/GUT', 18.00, '2026-01-01', ''),
(9, 'BCO/HGU', 16.50, '2026-01-15', ''),
(9, 'BFL3/GRE', 13.00, '2026-01-22', '特惠价'),

-- Waikawa Fishing Company 的定价
(12, 'BPF/GRE', 15.00, '2026-01-01', ''),
(12, 'BUT/HGU', 18.50, '2026-01-10', ''),
(12, 'BCO/GUT', 14.50, '2026-01-20', '');

-- ============================================
-- 验证插入的数据
-- ============================================

-- 查看插入的定价数量
SELECT COUNT(*) as '总定价记录数' FROM tblSupplierStockPrice WHERE IsActive = 1;

-- 查看每个供应商的定价数量
SELECT
    s.SupplierName,
    COUNT(p.PriceID) as '定价数量'
FROM tblSuppliers s
LEFT JOIN tblSupplierStockPrice p ON s.SupplierID = p.SupplierID AND p.IsActive = 1
WHERE s.is_del = 0
GROUP BY s.SupplierID, s.SupplierName
ORDER BY COUNT(p.PriceID) DESC;

-- 查看每个鱼种的供应商数量
SELECT
    st.Stock,
    st.State,
    COUNT(DISTINCT p.SupplierID) as '供应商数量',
    MIN(p.UnitPrice) as '最低价',
    MAX(p.UnitPrice) as '最高价',
    AVG(p.UnitPrice) as '平均价'
FROM tblStock st
LEFT JOIN tblSupplierStockPrice p ON st.StockID = p.StockID AND p.IsActive = 1
WHERE st.is_del = 0
GROUP BY st.StockID, st.Stock, st.State
HAVING COUNT(DISTINCT p.SupplierID) > 0
ORDER BY COUNT(DISTINCT p.SupplierID) DESC;

-- ============================================
-- 完成
-- ============================================

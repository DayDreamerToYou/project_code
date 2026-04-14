-- ============================================
-- 供应商-鱼种定价表创建脚本
-- 说明：解决一个供应商对应多种鱼、一种鱼对应多个供应商的定价问题
-- ============================================

USE seafood;

-- 创建供应商-鱼种定价表（核心关联表）
CREATE TABLE IF NOT EXISTS tblSupplierStockPrice (
    PriceID INT AUTO_INCREMENT COMMENT '定价记录唯一主键（自增）',
    SupplierID INT NOT NULL COMMENT '供应商ID，关联tblSuppliers表的主键',
    StockID VARCHAR(510) NOT NULL COMMENT '鱼种ID，关联tblStock表的主键',
    UnitPrice DECIMAL(10,2) NOT NULL COMMENT '该供应商对应此鱼种的专属单价（保留2位小数，适配金额/重量计价）',
    EffectiveDate DATE NOT NULL COMMENT '定价生效日期',
    IsActive TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用：1=启用，0=禁用（保留历史定价，不直接删除）',
    Remark VARCHAR(255) DEFAULT '' COMMENT '定价备注（如"2026年1月协商价""临时优惠价"）',
    CreateTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录创建时间',
    UpdateTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '记录更新时间',

    -- 主键约束
    PRIMARY KEY (PriceID),

    -- 索引：提升查询性能
    INDEX idx_supplier (SupplierID) USING BTREE COMMENT '供应商ID索引（快速查询某供应商的所有定价）',
    INDEX idx_stock (StockID) USING BTREE COMMENT '鱼种ID索引（快速查询某鱼种的所有供应商定价）',
    INDEX idx_effective (EffectiveDate) USING BTREE COMMENT '生效日期索引（按时间筛选定价）',
    INDEX idx_is_active (IsActive) USING BTREE COMMENT '启用状态索引（快速筛选生效定价）',

    -- 联合索引：查询生效的定价（最常用查询）
    INDEX idx_supplier_active (SupplierID, IsActive) USING BTREE COMMENT '供应商+启用状态联合索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='供应商-鱼种专属定价表（解决不同供应商同鱼种不同价问题）';

-- ============================================
-- 完成
-- ============================================

-- ============================================
-- 为tblPurchase表添加PortID和BoatID字段
-- 日期：2026-05-16
-- 说明：使Purchase记录直接关联Port和Boat信息
-- ============================================

USE `seafood`;

-- 1. 添加PortID字段
ALTER TABLE `tblPurchase` 
ADD COLUMN `PortID` INT(11) NULL COMMENT '港口ID' AFTER `SupplierID`,
ADD KEY `idx_portid` (`PortID`);

-- 2. 添加BoatID字段
ALTER TABLE `tblPurchase` 
ADD COLUMN `BoatID` INT(11) NULL COMMENT '渔船ID' AFTER `PortID`,
ADD KEY `idx_boatid` (`BoatID`);

-- 3. 添加外键约束（可选，根据实际需求决定是否启用）
-- ALTER TABLE `tblPurchase` 
-- ADD CONSTRAINT `fk_purchase_port` FOREIGN KEY (`PortID`) REFERENCES `tblPort` (`PortID`) ON DELETE SET NULL ON UPDATE CASCADE,
-- ADD CONSTRAINT `fk_purchase_boat` FOREIGN KEY (`BoatID`) REFERENCES `tblBoat` (`BoatID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 4. 数据迁移：从现有的LandingID关联更新PortID和BoatID
UPDATE `tblPurchase` p
LEFT JOIN `tblLanding` l ON p.LandingID = l.LandingID
SET p.PortID = l.PortID, p.BoatID = l.BoatID
WHERE p.LandingID IS NOT NULL;

-- ============================================
-- 说明：
-- 1. 添加了PortID和BoatID字段到tblPurchase表
-- 2. 为新字段创建了索引以提升查询性能
-- 3. 从现有的Landing关联中迁移了PortID和BoatID数据
-- 4. 外键约束已注释，可根据实际需求启用
-- ============================================

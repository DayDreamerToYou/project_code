-- ============================================
-- 渔业数据管理系统数据库建表脚本
-- 创建时间：2025-01-11
-- 说明：上岸记录、采购记录、销售记录表
-- ============================================

USE `table_editor`;

-- ============================================
-- 上岸记录表
-- ============================================
DROP TABLE IF EXISTS `landing_records`;
CREATE TABLE `landing_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `landing_date` date NOT NULL COMMENT '上岸日期',
  `fish_type` varchar(100) NOT NULL COMMENT '鱼种类',
  `quantity` decimal(10,2) NOT NULL COMMENT '数量(斤)',
  `unit_price` decimal(10,2) NOT NULL COMMENT '单价(元/斤)',
  `total_price` decimal(10,2) NOT NULL COMMENT '总价(元)',
  `supplier` varchar(100) DEFAULT NULL COMMENT '供应商',
  `boat_name` varchar(100) DEFAULT NULL COMMENT '船名',
  `location` varchar(200) DEFAULT NULL COMMENT '上岸地点',
  `quality` varchar(50) DEFAULT NULL COMMENT '品质等级',
  `notes` text DEFAULT NULL COMMENT '备注',
  `created_by` int(11) DEFAULT NULL COMMENT '创建人ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_landing_date` (`landing_date`),
  KEY `idx_fish_type` (`fish_type`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='上岸记录表';

-- ============================================
-- 采购记录表
-- ============================================
DROP TABLE IF EXISTS `purchase_records`;
CREATE TABLE `purchase_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `purchase_date` date NOT NULL COMMENT '采购日期',
  `item_name` varchar(200) NOT NULL COMMENT '物品名称',
  `category` varchar(100) DEFAULT NULL COMMENT '物品分类',
  `quantity` decimal(10,2) NOT NULL COMMENT '数量',
  `unit` varchar(50) NOT NULL COMMENT '单位',
  `unit_price` decimal(10,2) NOT NULL COMMENT '单价(元)',
  `total_price` decimal(10,2) NOT NULL COMMENT '总价(元)',
  `supplier` varchar(200) DEFAULT NULL COMMENT '供应商',
  `payment_method` varchar(50) DEFAULT NULL COMMENT '支付方式',
  `payment_status` varchar(50) DEFAULT '未付款' COMMENT '付款状态',
  `notes` text DEFAULT NULL COMMENT '备注',
  `created_by` int(11) DEFAULT NULL COMMENT '创建人ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_purchase_date` (`purchase_date`),
  KEY `idx_category` (`category`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购记录表';

-- ============================================
-- 销售记录表
-- ============================================
DROP TABLE IF EXISTS `sales_records`;
CREATE TABLE `sales_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `sale_date` date NOT NULL COMMENT '销售日期',
  `customer_name` varchar(200) NOT NULL COMMENT '客户名称',
  `customer_phone` varchar(50) DEFAULT NULL COMMENT '客户电话',
  `item_name` varchar(200) NOT NULL COMMENT '商品名称',
  `quantity` decimal(10,2) NOT NULL COMMENT '数量',
  `unit` varchar(50) NOT NULL COMMENT '单位',
  `unit_price` decimal(10,2) NOT NULL COMMENT '单价(元)',
  `total_price` decimal(10,2) NOT NULL COMMENT '总价(元)',
  `payment_method` varchar(50) DEFAULT NULL COMMENT '支付方式',
  `payment_status` varchar(50) DEFAULT '未收款' COMMENT '收款状态',
  `delivery_status` varchar(50) DEFAULT '未发货' COMMENT '发货状态',
  `notes` text DEFAULT NULL COMMENT '备注',
  `created_by` int(11) DEFAULT NULL COMMENT '创建人ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_sale_date` (`sale_date`),
  KEY `idx_customer` (`customer_name`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='销售记录表';

-- ============================================
-- 插入示例数据
-- ============================================

-- 上岸记录示例数据
INSERT INTO `landing_records` (`landing_date`, `fish_type`, `quantity`, `unit_price`, `total_price`, `supplier`, `boat_name`, `location`, `quality`, `created_by`) VALUES
('2025-01-10', '鲳鱼', 150.00, 25.00, 3750.00, '张三渔业', '浙渔12345', '舟山渔港', 'A级', 1),
('2025-01-10', '带鱼', 200.00, 18.00, 3600.00, '李四渔业', '浙渔23456', '宁波港', 'A级', 1),
('2025-01-09', '大黄鱼', 80.00, 45.00, 3600.00, '王五渔业', '浙渔34567', '温州港', '特级', 1);

-- 采购记录示例数据
INSERT INTO `purchase_records` (`purchase_date`, `item_name`, `category`, `quantity`, `unit`, `unit_price`, `total_price`, `supplier`, `payment_method`, `payment_status`, `created_by`) VALUES
('2025-01-10', '冰块', '冷链用品', 500.00, '块', 15.00, 7500.00, '冰鲜制冰厂', '现金', '已付款', 1),
('2025-01-09', '包装箱', '包装材料', 200.00, '个', 8.00, 1600.00, '包装材料厂', '转账', '已付款', 1),
('2025-01-08', '渔网', '渔具', 10.00, '张', 350.00, 3500.00, '渔具店', '赊账', '未付款', 1);

-- 销售记录示例数据
INSERT INTO `sales_records` (`sale_date`, `customer_name`, `customer_phone`, `item_name`, `quantity`, `unit`, `unit_price`, `total_price`, `payment_method`, `payment_status`, `delivery_status`, `created_by`) VALUES
('2025-01-10', '海鲜市场A商户', '13800138001', '鲳鱼', 50.00, '斤', 35.00, 1750.00, '现金', '已收款', '已发货', 1),
('2025-01-10', '海鲜饭店', '13800138002', '大黄鱼', 30.00, '斤', 58.00, 1740.00, '转账', '已收款', '已发货', 1),
('2025-01-09', '个人客户', '13800138003', '带鱼', 20.00, '斤', 25.00, 500.00, '微信', '已收款', '已发货', 1);

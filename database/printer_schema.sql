-- 打印机配置表
CREATE TABLE IF NOT EXISTS `tblPrinters` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `printer_name` VARCHAR(100) NOT NULL COMMENT '打印机名称',
  `printer_ip` VARCHAR(50) NOT NULL COMMENT '打印机IP地址',
  `printer_port` INT(11) NOT NULL DEFAULT 9100 COMMENT '打印机端口',
  `printer_type` VARCHAR(50) NOT NULL DEFAULT 'ESC/POS' COMMENT '打印机类型',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否默认打印机',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态：1=启用，0=禁用',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '描述说明',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_printer_name` (`printer_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='打印机配置表';

-- 插入示例打印机配置
INSERT INTO `tblPrinters` (`printer_name`, `printer_ip`, `printer_port`, `printer_type`, `is_default`, `status`, `description`) VALUES
('前台打印机', '192.168.1.100', 9100, 'ESC/POS', 1, 1, '前台默认热敏打印机'),
('仓库打印机', '192.168.1.101', 9100, 'ESC/POS', 0, 1, '仓库热敏打印机');

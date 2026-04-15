-- ============================================
-- 表格编辑系统数据库建表脚本
-- 创建时间：2025-01-05
-- 说明：包含用户表和示例数据表
-- ============================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `table_editor` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `table_editor`;

-- ============================================
-- 用户表
-- 用于存储登录用户信息
-- ============================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码（使用password_hash加密）',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ============================================
-- 示例数据表
-- 用于演示表格编辑功能
-- 可根据需要修改表结构
-- ============================================
DROP TABLE IF EXISTS `sample_data`;
CREATE TABLE `sample_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` int(11) NOT NULL COMMENT '所属用户ID',
  `name` varchar(100) NOT NULL COMMENT '姓名',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `phone` varchar(20) DEFAULT NULL COMMENT '电话',
  `department` varchar(50) DEFAULT NULL COMMENT '部门',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态（1=启用，0=禁用）',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_sample_data_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='示例数据表';

-- ============================================
-- 插入测试数据
-- ============================================

-- 插入测试用户（密码为：123456）
-- password_hash('123456', PASSWORD_DEFAULT) 的哈希值
INSERT INTO `users` (`username`, `password`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 插入示例数据（关联到admin用户，ID=1）
INSERT INTO `sample_data` (`user_id`, `name`, `email`, `phone`, `department`, `status`) VALUES
(1, '张三', 'zhangsan@example.com', '13800138001', '技术部', 1),
(1, '李四', 'lisi@example.com', '13800138002', '市场部', 1),
(1, '王五', 'wangwu@example.com', '13800138003', '财务部', 0),
(1, '赵六', 'zhaoliu@example.com', '13800138004', '人事部', 1),
(1, '钱七', 'qianqi@example.com', '13800138005', '技术部', 1);

-- ============================================
-- 查询验证（可选）
-- ============================================
-- SELECT * FROM users;
-- SELECT * FROM sample_data;

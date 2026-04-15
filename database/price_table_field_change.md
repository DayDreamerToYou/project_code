# tblSupplierStockPrice 字段修改说明

## 修改内容

将 `tblSupplierStockPrice` 表的删除字段从 `IsActive` 改为 `is_del`，与系统中其他表保持一致。

## 字段对照

| 原字段 | 新字段 | 说明 |
|--------|--------|------|
| `IsActive` | `is_del` | 删除标记字段 |
| IsActive = 1（启用） | is_del = 0（未删除） | 逻辑反转 |
| IsActive = 0（禁用） | is_del = 1（已删除） | 逻辑反转 |

## 已修改的文件

### 1. 数据库
- **表结构**：`tblSupplierStockPrice` 表已有 `is_del` 字段
- **数据状态**：30 条测试记录，所有记录的 `is_del = 0`（未删除）

### 2. 后端 API
- **文件**：`/api/supplier-stock-price.php`
- **修改内容**：
  - 所有查询条件从 `WHERE IsActive = 1` 改为 `WHERE is_del = 0`
  - 所有 INSERT 语句中的 `IsActive` 改为 `is_del`，默认值从 `1` 改为 `0`
  - 所有 UPDATE 语句中的 `IsActive` 改为 `is_del`
  - DELETE 操作将 `is_del` 设置为 `1`（已删除）

### 3. 前端 JavaScript
- **文件**：`/public/js/price-management.js`
- **修改内容**：
  - 表格列字段从 `IsActive` 改为 `is_del`
  - 状态显示逻辑：`is_del == 0` 显示"启用"，`is_del == 1` 显示"禁用"

## 验证结果

### 数据库验证
```sql
SELECT COUNT(*) as total FROM tblSupplierStockPrice WHERE is_del = 0;
-- 结果：30 条记录
```

### PHP 语法验证
```bash
php -l /www/wwwroot/table-editor/api/supplier-stock-price.php
-- 结果：No syntax errors detected
```

## 功能说明

### 删除逻辑（软删除）
- 新增记录时，`is_del` 默认为 `0`（未删除）
- 删除操作（DELETE）将 `is_del` 设置为 `1`（已删除）
- 查询时只显示 `is_del = 0` 的记录（未删除）
- 数据不会物理删除，保留历史记录

### 状态对照表

| is_del 值 | 含义 | 前端显示 | 备注 |
|-----------|------|----------|------|
| 0 | 未删除 | 启用（绿色） | 正常使用状态 |
| 1 | 已删除 | 禁用（红色） | 已禁用/删除状态 |

## 使用示例

### 查询所有未删除的定价
```sql
SELECT * FROM tblSupplierStockPrice WHERE is_del = 0;
```

### 查询所有已删除的定价
```sql
SELECT * FROM tblSupplierStockPrice WHERE is_del = 1;
```

### 恢复已删除的定价
```sql
UPDATE tblSupplierStockPrice SET is_del = 0 WHERE PriceID = ?;
```

## 测试数据

当前系统中有 **30 条测试定价记录**：
- 所有记录的 `is_del = 0`（未删除状态）
- 覆盖 10 个供应商
- 包含多种鱼种（BCO/GRE, BUT/GRE, BPF/GUT 等）
- 价格范围：$13.00 - $19.50

## 完成日期

2026-01-26

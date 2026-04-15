# 月度采购统计模块使用说明

## 功能概述

月度采购统计模块用于汇总和分析每个月每个供应商对应每种鱼类的采购数据，提供直观的数据展示和可视化分析。

## 访问方式

- **直接访问**: `http://your-domain/public/monthly-statistics.html`
- **导航入口**: 点击首页顶部导航的"统计报告"按钮

## 核心功能

### 1. 数据筛选

| 筛选项 | 说明 | 默认值 |
|--------|------|--------|
| **统计月份** | 选择要统计的月份，支持历史月份查询 | 当前月 |
| **供应商** | 多选下拉框，可选择一个或多个供应商 | 全部 |
| **鱼种** | 多选下拉框，可选择一个或多个鱼种 | 全部 |

### 2. 数据统计指标

- **总净重 (Green Weight)**: 当月该供应商该鱼种的净重总和（kg）
- **交易次数**: 当月该供应商该鱼种的交易笔数
- **平均单价**: 当月该供应商该鱼种的平均单价（$/kg）
- **交易总额**: 当月该供应商该鱼种的总交易金额（$）

### 3. 视图展示

#### 表格视图
- 支持按列排序
- 支持分页显示（10/20/50/100条/页）
- 数据高亮显示（净重绿色、交易次数蓝色、金额红色）

#### 图表视图
- **柱状图**: 供应商-鱼种净重对比
- **饼图**: 各供应商净重占比
- 支持交互式数据提示
- 响应式布局，自适应窗口大小

### 4. 汇总卡片

页面顶部显示三个汇总指标：
- 总净重（绿色卡片）
- 总交易次数（蓝色卡片）
- 总交易金额（橙色卡片）

## 数据源

### 核心数据表

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| **tblPurchase** | 采购主表 | PurchaseID, PurchaseDate, SupplierID |
| **tblPurchaseDetail** | 采购明细表 | PurchaseID, StockID, GreenKG, Price, Total |
| **tblSuppliers** | 供应商表 | SupplierID, SupplierName |
| **tblStock** | 鱼种表 | StockID, Stock |

### 关联关系

```
tblPurchase
    ├─ INNER JOIN tblSuppliers (SupplierID)
    ├─ INNER JOIN tblPurchaseDetail (PurchaseID)
    └─ INNER JOIN tblStock (StockID)
```

## 性能优化

### 数据库索引

为提升统计查询性能，已创建以下索引：

**tblPurchase 表**:
- `idx_purchase_date` - PurchaseDate 字段索引
- `idx_purchase_supplier` - SupplierID 字段索引
- `idx_purchase_is_del` - is_del 字段索引

**tblPurchaseDetail 表**:
- `idx_purchase_detail_stats` - 联合索引 (PurchaseID, StockID, is_del)
- `idx_purchase_detail_stock` - StockID 字段索引

### 查询优化

- 使用 `DATE_FORMAT(PurchaseDate, '%Y-%m')` 进行月份分组
- 使用 `COALESCE()` 函数确保无数据时显示 0
- 使用 `INNER JOIN` 确保只统计有效数据
- 软删除过滤：`is_del = 0`

## API 接口

### 1. 统计数据接口

**URL**: `/api/monthly-statistics.php`

**方法**: GET

**参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| month | string | 否 | 统计月份 (YYYY-MM)，默认当前月 |
| supplierIds | string | 否 | 供应商ID列表，逗号分隔 |
| stockIds | string | 否 | 鱼种ID列表，逗号分隔 |
| view | string | 否 | 视图类型 (table/chart) |

**返回示例**:
```json
{
    "success": true,
    "data": {
        "statistics": [
            {
                "SupplierID": "1",
                "SupplierName": "供应商名称",
                "StockID": "BCO",
                "StockName": "鱼种名称",
                "month": "2026-01",
                "total_green_weight": 37.72,
                "transaction_count": 2,
                "avg_price": 5.50,
                "total_amount": 207.46
            }
        ],
        "summary": {
            "total_green_weight": 1000.50,
            "total_transactions": 50,
            "total_amount": 5000.00
        }
    }
}
```

### 2. 筛选选项接口

**URL**: `/api/statistics-options.php`

**方法**: GET

**返回示例**:
```json
{
    "success": true,
    "data": {
        "suppliers": [...],
        "stocks": [...],
        "months": [...]
    }
}
```

## 技术栈

- **前端**: 原生 HTML5 + LayUI 2.8.0
- **图表**: ECharts 5.4.3
- **后端**: PHP 8.2 + PDO
- **数据库**: MySQL 5.7+

## 使用示例

### 场景 1: 查看当前月所有统计数据

1. 打开统计页面
2. 确认"统计月份"为当前月
3. 确认"供应商"和"鱼种"为"全部"
4. 点击"查询"按钮
5. 查看汇总卡片和详细数据

### 场景 2: 查看特定供应商的某月数据

1. 选择"统计月份"
2. 在"供应商"下拉框中选择目标供应商
3. 点击"查询"按钮
4. 切换到"图表视图"查看可视化分析

### 场景 3: 对比多个供应商的鱼种采购量

1. 选择"统计月份"
2. 在"供应商"下拉框中多选多个供应商
3. 点击"查询"按钮
4. 切换到"图表视图"
5. 查看柱状图对比

## 常见问题

### Q1: 为什么有些数据显示为 0？
**A**: 使用 `COALESCE()` 函数确保无交易数据时显示 0 而非 NULL，便于数据统计和展示。

### Q2: 可以查询多个月份的数据吗？
**A**: 当前版本一次只能查询单个月份的数据。如需查询多个月份，请分别查询或导出数据后合并分析。

### Q3: 导出功能在哪里？
**A**: 当前版本暂不支持导出功能，可后续添加 Excel 导出功能。

## 后续优化建议

1. **导出功能**: 添加 Excel/PDF 导出功能
2. **趋势分析**: 支持多月份数据对比和趋势图表
3. **权限控制**: 根据用户角色限制数据访问范围
4. **实时刷新**: 自动定时刷新统计数据
5. **数据钻取**: 点击数据查看明细记录
6. **自定义报表**: 支持用户自定义统计维度和指标

## 更新日志

### v1.0.0 (2026-01-29)
- ✅ 初始版本发布
- ✅ 实现基本的月度统计功能
- ✅ 表格和图表双视图展示
- ✅ 多维度筛选功能
- ✅ 数据库性能优化

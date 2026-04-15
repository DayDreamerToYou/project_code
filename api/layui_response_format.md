# LayUI 标准响应格式修改说明

## 修改内容

将供应商定价模块的 API 响应格式修改为 LayUI 标准格式。

## LayUI Table 标准响应格式

### 成功响应
```json
{
  "code": 0,
  "msg": "",
  "count": 100,
  "data": [...]
}
```

### 错误响应
```json
{
  "code": 500,
  "msg": "错误信息"
}
```

## 修改对照表

| 含义 | 原格式 | 新格式 |
|------|--------|--------|
| 成功标识 | `success: true` | `code: 0` |
| 失败标识 | `success: false` | `code: 500` |
| 消息内容 | `message: "..."` | `msg: "..."` |
| 总记录数 | `total: 100` | `count: 100` |
| 数据数组 | `data: [...]` | `data: [...]` (保持不变) |

## 已修改的文件

### 1. 后端 API
**文件**：`/api/supplier-stock-price.php`

**修改内容**：
- ✅ 所有成功响应改为 `code: 0`
- ✅ 所有失败响应改为 `code: 500`
- ✅ 所有 `message` 字段改为 `msg`
- ✅ 列表查询的 `total` 字段改为 `count`

### 2. 前端 JavaScript
**文件**：`/public/js/price-management.js`

**修改内容**：
- ✅ 添加 `parseData` 函数解析响应
- ✅ 所有 `res.success` 改为 `res.code === 0`
- ✅ 所有 `res.message` 改为 `res.msg`

## 验证方法

### 1. API 测试
```bash
curl "http://your-domain/api/supplier-stock-price.php?action=list&page=1&limit=10"
```

预期响应：
```json
{
  "code": 0,
  "msg": "",
  "count": 30,
  "data": [...]
}
```

### 2. 前端测试
访问数据管理页面：
1. http://159.75.152.100/data-management.html
2. 登录系统
3. 切换到"供应商定价"标签页
4. 应该正常显示数据，无错误提示

## 兼容性说明

修改后的格式符合 LayUI 2.x 的官方标准，可以正常使用以下功能：
- ✅ 数据表格渲染
- ✅ 分页功能
- ✅ 搜索功能
- ✅ 排序功能
- ✅ 工具栏操作

## 注意事项

1. **其他 API 接口**：当前只修改了供应商定价模块的 API，其他模块仍使用旧的 `success/message` 格式
2. **前端适配**：前端 JS 已添加 `parseData` 函数，可以正确解析新格式
3. **向后兼容**：如果需要统一所有 API 格式，建议逐步修改其他模块

## 完成日期

2026-01-26

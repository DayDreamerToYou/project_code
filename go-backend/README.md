# Go 后端 (table-editor-backend)

基于 Go 1.24 + Gin + GORM 重构的渔业数据管理系统后端。

## 目录结构

```
go-backend/
├── cmd/server/             # 入口
│   └── main.go
├── internal/
│   ├── config/             # 配置加载
│   ├── database/           # 数据库连接
│   ├── models/             # ORM 模型
│   ├── repository/         # 仓储层（直接操作数据库）
│   ├── services/           # 业务服务层（保留所有原计算逻辑）
│   ├── controllers/        # HTTP 控制器
│   ├── middleware/         # 中间件
│   ├── routes/             # 路由注册
│   └── utils/              # 工具（响应包装、辅助函数）
├── Dockerfile              # 生产构建
├── Dockerfile.dev          # 开发镜像（air 热重载）
├── .air.toml               # air 配置
├── Makefile
├── .env.example
└── go.mod
```

## 架构

**简单分层架构 (Controller → Service → Repository)**

- **Controller**: 接收 HTTP 请求，参数验证，调用 Service
- **Service**: 业务逻辑（重量/金额计算、数据联动、级联删除）
- **Repository**: 数据库操作（GORM）

## 保留的核心计算逻辑

### Landing → Purchase
- `LandedKG = L-Weight − (B-Weight × BinQty)`
- `GreenKG = LandedKG × Stock.Conversion`
- `Total = LandedKG × Price`
- `Subtotal = Σ Total`
- `GST = Subtotal × (Supplier.GST / 100)`
- `PurchaseID = LandingID + 50000`

### Purchase → Sales
- `G-Weight = N-Weight + (BinQty × B-Weight)`（其中 N-Weight 即 LandedKG）
- `Amount = N-Weight × Price`
- `SalesID = PurchaseID + 20000`

### 级联删除
- 删除 Landing → 关联 Purchase → 关联 Sales（按物理删除执行）
- 删除 Purchase → 关联 Sales（按物理删除执行）
- 删除后重置 AUTO_INCREMENT

## 快速开始

### 1. 本地运行
```bash
cp .env.example .env
# 编辑 .env 填入数据库配置
go mod tidy
go run ./cmd/server
```

### 2. 热重载开发
```bash
make dev
```

### 3. Docker
```bash
# 生产
docker compose up -d backend

# 开发（air 热重载）
docker compose --profile dev up backend-dev
```

## API 端点

所有 API 路径前缀为 `/api/v1`。

### 公开
- `POST /auth/login` - 登录
- `POST /auth/register` - 注册

### 需认证（Bearer Token）
- `GET    /auth/me` - 当前用户
- `POST   /auth/logout` - 退出

#### 到货 Landing
- `GET    /landings` - 列表
- `POST   /landings` - 创建
- `GET    /landings/:id` - 详情
- `PUT    /landings/:id` - 更新
- `DELETE /landings/:id` - 删除（级联）
- `GET    /landings/options` - 下拉选项
- `POST   /landings/details` - 取明细
- `POST   /landings/update-lweight` - 更新 L-Weight
- `POST   /landings/generate-purchase` - 生成采购

#### 采购 Purchase
- `GET    /purchases` - 列表
- `GET    /purchases/:id` - 详情
- `DELETE /purchases/:id` - 删除（级联）
- `POST   /purchases/generate-sales` - 生成销售

#### 销售 Sales
- `GET    /sales` - 列表
- `GET    /sales/:id` - 详情
- `DELETE /sales/:id` - 删除

#### 基础数据
- `GET/POST/PUT/DELETE /suppliers` - 供应商
- `GET/POST/PUT/DELETE /ports` - 港口
- `GET/POST/PUT/DELETE /boats` - 船
- `GET/POST/PUT/DELETE /bins` - 箱子
- `GET/POST/PUT/DELETE /stocks` - 库存品
- `GET/POST/PUT/DELETE /units` - 单位
- `GET/POST/PUT/DELETE /customers` - 客户
- `GET/POST/PUT/DELETE /fleets` - 船队
- `POST /fleets/:id/boats` - 船加入船队
- `DELETE /fleets/:id/boats/:boatId` - 船从船队移除
- `GET/POST/PUT/DELETE /supplier-stock-prices` - 供应商价格

## 响应格式

```json
{
  "success": true,
  "message": "ok",
  "data": { ... },
  "pagination": { "page": 1, "pageSize": 20, "total": 100, "totalPages": 5 }
}
```

## 数据库

完全沿用原有 Laravel 数据库结构（表名、字段名一致），通过 GORM 的 `TableName` 方法显式指定：
- `tblLanding`, `tblLandingDetail`
- `tblPurchase`, `tblPurchaseDetail`
- `tblSales`, `tblSalesDetail`
- `tblSuppliers`, `tblPort`, `tblBoat`, `tblBin`, `tblStock`, `tblUnit`, `tblCustomer`, `tblFleet`, `tblFleetDetail`
- `users` (id, username, password)

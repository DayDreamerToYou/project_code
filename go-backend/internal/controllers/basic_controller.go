package controllers

import (
	"strconv"

	"github.com/gin-gonic/gin"

	"table-editor-backend/internal/models"
	"table-editor-backend/internal/services"
	"table-editor-backend/internal/utils"
)

// BasicController 基础数据控制器
type BasicController struct {
	basicSvc *services.BasicService
}

// NewBasicController 构造
func NewBasicController(svc *services.BasicService) *BasicController {
	return &BasicController{basicSvc: svc}
}

// ============ Suppliers ============

// ListSuppliers GET /suppliers
func (ctl *BasicController) ListSuppliers(c *gin.Context) {
	items, err := ctl.basicSvc.ListSuppliers(c.Request.Context())
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

// GetSupplier GET /suppliers/:id
func (ctl *BasicController) GetSupplier(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	item, err := ctl.basicSvc.GetSupplier(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, item)
}

// CreateSupplier POST /suppliers
func (ctl *BasicController) CreateSupplier(c *gin.Context) {
	var item models.Supplier
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}
	if item.SupplierName == "" {
		utils.BadRequest(c, "供应商名称不能为空")
		return
	}
	if err := ctl.basicSvc.CreateSupplier(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Created(c, item, "供应商创建成功")
}

// UpdateSupplier PUT /suppliers/:id
func (ctl *BasicController) UpdateSupplier(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var item models.Supplier
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}
	item.SupplierID = id
	if err := ctl.basicSvc.UpdateSupplier(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, item, "供应商更新成功")
}

// DeleteSupplier DELETE /suppliers/:id
func (ctl *BasicController) DeleteSupplier(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.basicSvc.DeleteSupplier(c.Request.Context(), id); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "供应商已删除")
}

// ============ Ports ============

func (ctl *BasicController) ListPorts(c *gin.Context) {
	items, err := ctl.basicSvc.ListPorts(c.Request.Context())
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

func (ctl *BasicController) GetPort(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	item, err := ctl.basicSvc.GetPort(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, item)
}

func (ctl *BasicController) CreatePort(c *gin.Context) {
	var item models.Port
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	if err := ctl.basicSvc.CreatePort(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Created(c, item, "港口创建成功")
}

func (ctl *BasicController) UpdatePort(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var item models.Port
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	item.PortID = id
	if err := ctl.basicSvc.UpdatePort(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, item, "港口更新成功")
}

func (ctl *BasicController) DeletePort(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.basicSvc.DeletePort(c.Request.Context(), id); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "港口已删除")
}

// ============ Boats ============

func (ctl *BasicController) ListBoats(c *gin.Context) {
	items, err := ctl.basicSvc.ListBoats(c.Request.Context())
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

func (ctl *BasicController) GetBoat(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	item, err := ctl.basicSvc.GetBoat(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, item)
}

func (ctl *BasicController) CreateBoat(c *gin.Context) {
	var item models.Boat
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	if err := ctl.basicSvc.CreateBoat(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Created(c, item, "船创建成功")
}

func (ctl *BasicController) UpdateBoat(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var item models.Boat
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	item.BoatID = id
	if err := ctl.basicSvc.UpdateBoat(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, item, "船更新成功")
}

func (ctl *BasicController) DeleteBoat(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.basicSvc.DeleteBoat(c.Request.Context(), id); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "船已删除")
}

// ============ Bins ============

func (ctl *BasicController) ListBins(c *gin.Context) {
	items, err := ctl.basicSvc.ListBins(c.Request.Context())
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

func (ctl *BasicController) GetBin(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	item, err := ctl.basicSvc.GetBin(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, item)
}

func (ctl *BasicController) CreateBin(c *gin.Context) {
	var item models.Bin
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	if err := ctl.basicSvc.CreateBin(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Created(c, item, "箱子创建成功")
}

func (ctl *BasicController) UpdateBin(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var item models.Bin
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	item.BinID = id
	if err := ctl.basicSvc.UpdateBin(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, item, "箱子更新成功")
}

func (ctl *BasicController) DeleteBin(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.basicSvc.DeleteBin(c.Request.Context(), id); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "箱子已删除")
}

// ============ Stocks ============

func (ctl *BasicController) ListStocks(c *gin.Context) {
	items, err := ctl.basicSvc.ListStocks(c.Request.Context())
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

func (ctl *BasicController) GetStock(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	item, err := ctl.basicSvc.GetStock(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, item)
}

func (ctl *BasicController) CreateStock(c *gin.Context) {
	var item models.Stock
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	if err := ctl.basicSvc.CreateStock(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Created(c, item, "库存品创建成功")
}

func (ctl *BasicController) UpdateStock(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var item models.Stock
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	item.StockID = id
	if err := ctl.basicSvc.UpdateStock(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, item, "库存品更新成功")
}

func (ctl *BasicController) DeleteStock(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.basicSvc.DeleteStock(c.Request.Context(), id); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "库存品已删除")
}

// ============ Units ============

func (ctl *BasicController) ListUnits(c *gin.Context) {
	items, err := ctl.basicSvc.ListUnits(c.Request.Context())
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

func (ctl *BasicController) GetUnit(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	item, err := ctl.basicSvc.GetUnit(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, item)
}

func (ctl *BasicController) CreateUnit(c *gin.Context) {
	var item models.Unit
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	if err := ctl.basicSvc.CreateUnit(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Created(c, item, "单位创建成功")
}

func (ctl *BasicController) UpdateUnit(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var item models.Unit
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	item.UnitID = id
	if err := ctl.basicSvc.UpdateUnit(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, item, "单位更新成功")
}

func (ctl *BasicController) DeleteUnit(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.basicSvc.DeleteUnit(c.Request.Context(), id); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "单位已删除")
}

// ============ Customers ============

func (ctl *BasicController) ListCustomers(c *gin.Context) {
	items, err := ctl.basicSvc.ListCustomers(c.Request.Context())
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

func (ctl *BasicController) GetCustomer(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	item, err := ctl.basicSvc.GetCustomer(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, item)
}

func (ctl *BasicController) CreateCustomer(c *gin.Context) {
	var item models.Customer
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	if err := ctl.basicSvc.CreateCustomer(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Created(c, item, "客户创建成功")
}

func (ctl *BasicController) UpdateCustomer(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var item models.Customer
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	item.CustID = id
	if err := ctl.basicSvc.UpdateCustomer(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, item, "客户更新成功")
}

func (ctl *BasicController) DeleteCustomer(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.basicSvc.DeleteCustomer(c.Request.Context(), id); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "客户已删除")
}

// ============ Fleets ============

func (ctl *BasicController) ListFleets(c *gin.Context) {
	items, err := ctl.basicSvc.ListFleets(c.Request.Context())
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

func (ctl *BasicController) GetFleet(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	item, err := ctl.basicSvc.GetFleet(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, item)
}

func (ctl *BasicController) CreateFleet(c *gin.Context) {
	var item models.Fleet
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	if err := ctl.basicSvc.CreateFleet(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Created(c, item, "船队创建成功")
}

func (ctl *BasicController) UpdateFleet(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var item models.Fleet
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	item.FleetID = id
	if err := ctl.basicSvc.UpdateFleet(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, item, "船队更新成功")
}

func (ctl *BasicController) DeleteFleet(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.basicSvc.DeleteFleet(c.Request.Context(), id); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "船队已删除")
}

// FleetBoats GET /fleets/:id/boats
func (ctl *BasicController) FleetBoats(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	items, err := ctl.basicSvc.FleetBoats(c.Request.Context(), id)
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

// BoatFleets GET /boats/:id/fleets
func (ctl *BasicController) BoatFleets(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	items, err := ctl.basicSvc.BoatFleets(c.Request.Context(), id)
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

// AddFleetBoat POST /fleets/:id/boats
func (ctl *BasicController) AddFleetBoat(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var body struct {
		BoatID int `json:"BoatID"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	if err := ctl.basicSvc.AddFleetBoat(c.Request.Context(), id, body.BoatID); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "船已加入船队")
}

// RemoveFleetBoat DELETE /fleets/:id/boats/:boatId
func (ctl *BasicController) RemoveFleetBoat(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	boatID, _ := strconv.Atoi(c.Param("boatId"))
	if err := ctl.basicSvc.RemoveFleetBoat(c.Request.Context(), id, boatID); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "已从船队移除")
}

// ============ SupplierStockPrice ============

func (ctl *BasicController) GetSupplierStockPrices(c *gin.Context) {
	supplierID, _ := strconv.Atoi(c.Query("supplierID"))
	items, err := ctl.basicSvc.GetSupplierStockPrices(c.Request.Context(), supplierID)
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, items)
}

func (ctl *BasicController) CreateSupplierStockPrice(c *gin.Context) {
	var item models.SupplierStockPrice
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	if err := ctl.basicSvc.CreateSupplierStockPrice(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Created(c, item, "价格创建成功")
}

func (ctl *BasicController) UpdateSupplierStockPrice(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var item models.SupplierStockPrice
	if err := c.ShouldBindJSON(&item); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	item.ID = id
	if err := ctl.basicSvc.UpdateSupplierStockPrice(c.Request.Context(), &item); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, item, "价格更新成功")
}

func (ctl *BasicController) DeleteSupplierStockPrice(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.basicSvc.DeleteSupplierStockPrice(c.Request.Context(), id); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "价格已删除")
}

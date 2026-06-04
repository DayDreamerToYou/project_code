package controllers

import (
	"strconv"

	"github.com/gin-gonic/gin"

	"table-editor-backend/internal/services"
	"table-editor-backend/internal/utils"
)

// PurchaseController 采购控制器
type PurchaseController struct {
	purchaseSvc *services.PurchaseService
}

// NewPurchaseController 构造
func NewPurchaseController(svc *services.PurchaseService) *PurchaseController {
	return &PurchaseController{purchaseSvc: svc}
}

// List GET /purchases
func (ctl *PurchaseController) List(c *gin.Context) {
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	pageSize, _ := strconv.Atoi(c.DefaultQuery("pageSize", "20"))
	search := c.Query("search")
	sortField := c.Query("sortField")
	sortOrder := c.Query("sortOrder")

	items, total, err := ctl.purchaseSvc.ListPurchase(c.Request.Context(), page, pageSize, search, sortField, sortOrder)
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}

	totalPages := int(total) / pageSize
	if int(total)%pageSize != 0 {
		totalPages++
	}
	utils.Paginated(c, items, utils.Pagination{
		Page:       page,
		PageSize:   pageSize,
		Total:      total,
		TotalPages: totalPages,
	})
}

// Show GET /purchases/:id
func (ctl *PurchaseController) Show(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	data, err := ctl.purchaseSvc.GetPurchase(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, data)
}

// Destroy DELETE /purchases/:id
func (ctl *PurchaseController) Destroy(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.purchaseSvc.DeletePurchase(c.Request.Context(), id); err != nil {
		utils.InternalError(c, "删除失败: "+err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "采购记录已删除")
}

// GenerateSales POST /purchases/generate-sales
func (ctl *PurchaseController) GenerateSales(c *gin.Context) {
	var body struct {
		PurchaseID int `json:"PurchaseID"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}
	result, err := ctl.purchaseSvc.GenerateSales(c.Request.Context(), body.PurchaseID)
	if err != nil {
		utils.BadRequest(c, "生成销售单失败: "+err.Error())
		return
	}
	utils.SuccessWithMessage(c, result, "销售单生成成功")
}

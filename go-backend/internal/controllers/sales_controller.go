package controllers

import (
	"strconv"

	"github.com/gin-gonic/gin"

	"table-editor-backend/internal/services"
	"table-editor-backend/internal/utils"
)

// SalesController 销售控制器
type SalesController struct {
	salesSvc *services.SalesService
}

// NewSalesController 构造
func NewSalesController(svc *services.SalesService) *SalesController {
	return &SalesController{salesSvc: svc}
}

// List GET /sales
func (ctl *SalesController) List(c *gin.Context) {
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	pageSize, _ := strconv.Atoi(c.DefaultQuery("pageSize", "20"))
	search := c.Query("search")
	sortField := c.Query("sortField")
	sortOrder := c.Query("sortOrder")

	items, total, err := ctl.salesSvc.ListSales(c.Request.Context(), page, pageSize, search, sortField, sortOrder)
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

// Show GET /sales/:id
func (ctl *SalesController) Show(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	data, err := ctl.salesSvc.GetSales(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, data)
}

// Destroy DELETE /sales/:id
func (ctl *SalesController) Destroy(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.salesSvc.DeleteSales(c.Request.Context(), id); err != nil {
		utils.InternalError(c, "删除失败: "+err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "销售记录已删除")
}

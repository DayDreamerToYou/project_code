package controllers

import (
	"strconv"
	"time"

	"github.com/gin-gonic/gin"

	"table-editor-backend/internal/models"
	"table-editor-backend/internal/services"
	"table-editor-backend/internal/utils"
)

// LandingController 到货控制器
type LandingController struct {
	landingSvc *services.LandingService
}

// NewLandingController 构造
func NewLandingController(svc *services.LandingService) *LandingController {
	return &LandingController{landingSvc: svc}
}

// List GET /landings
func (ctl *LandingController) List(c *gin.Context) {
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	pageSize, _ := strconv.Atoi(c.DefaultQuery("pageSize", "20"))
	search := c.Query("search")
	sortField := c.Query("sortField")
	sortOrder := c.Query("sortOrder")

	items, total, err := ctl.landingSvc.ListLanding(c.Request.Context(), page, pageSize, search, sortField, sortOrder)
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

// Show GET /landings/:id
func (ctl *LandingController) Show(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	data, err := ctl.landingSvc.GetLanding(c.Request.Context(), id)
	if err != nil {
		utils.NotFound(c, err.Error())
		return
	}
	utils.Success(c, data)
}

// Store POST /landings
func (ctl *LandingController) Store(c *gin.Context) {
	var body struct {
		LandingDate string                 `json:"LandingDate"`
		SupplierID  int                    `json:"SupplierID"`
		PortID      int                    `json:"PortID"`
		BoatID      int                    `json:"BoatID"`
		Details     []models.LandingDetail `json:"details"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}

	if body.SupplierID == 0 || body.PortID == 0 || body.BoatID == 0 {
		utils.BadRequest(c, "供应商、港口、船为必填项")
		return
	}

	landing := &models.Landing{
		LandingDate: parseDate(body.LandingDate),
		SupplierID:  body.SupplierID,
		PortID:      body.PortID,
		BoatID:      body.BoatID,
	}

	if err := ctl.landingSvc.CreateLanding(c.Request.Context(), landing, body.Details); err != nil {
		utils.InternalError(c, "创建失败: "+err.Error())
		return
	}
	utils.Created(c, map[string]interface{}{"LandingID": landing.LandingID}, "到货记录创建成功")
}

// Update PUT /landings/:id
func (ctl *LandingController) Update(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	var body struct {
		LandingDate string                 `json:"LandingDate"`
		SupplierID  int                    `json:"SupplierID"`
		PortID      int                    `json:"PortID"`
		BoatID      int                    `json:"BoatID"`
		Details     []models.LandingDetail `json:"details"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}

	landing := &models.Landing{
		LandingID:   id,
		LandingDate: parseDate(body.LandingDate),
		SupplierID:  body.SupplierID,
		PortID:      body.PortID,
		BoatID:      body.BoatID,
	}

	if err := ctl.landingSvc.UpdateLanding(c.Request.Context(), landing, body.Details); err != nil {
		utils.InternalError(c, "更新失败: "+err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "到货记录更新成功")
}

// Destroy DELETE /landings/:id
func (ctl *LandingController) Destroy(c *gin.Context) {
	id, _ := strconv.Atoi(c.Param("id"))
	if err := ctl.landingSvc.DeleteLanding(c.Request.Context(), id); err != nil {
		utils.InternalError(c, "删除失败: "+err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "到货记录已删除")
}

// GetDetails POST /landings/details
func (ctl *LandingController) GetDetails(c *gin.Context) {
	var body struct {
		LandingID int `json:"LandingID"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}
	details, err := ctl.landingSvc.GetLandingDetails(c.Request.Context(), body.LandingID)
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, details)
}

// UpdateLWeight POST /landings/update-lweight
func (ctl *LandingController) UpdateLWeight(c *gin.Context) {
	var body struct {
		ID      int     `json:"ID"`
		LWeight float64 `json:"L-Weight"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}
	if err := ctl.landingSvc.UpdateLWeight(c.Request.Context(), body.ID, body.LWeight); err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "L-Weight 已更新")
}

// GeneratePurchase POST /landings/generate-purchase
func (ctl *LandingController) GeneratePurchase(c *gin.Context) {
	var body struct {
		LandingID int `json:"LandingID"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}
	result, err := ctl.landingSvc.GeneratePurchase(c.Request.Context(), body.LandingID)
	if err != nil {
		utils.BadRequest(c, "生成采购单失败: "+err.Error())
		return
	}
	utils.SuccessWithMessage(c, result, "采购单生成成功")
}

// Options GET /landings/options
func (ctl *LandingController) Options(c *gin.Context) {
	opts, err := ctl.landingSvc.GetOptions(c.Request.Context())
	if err != nil {
		utils.InternalError(c, err.Error())
		return
	}
	utils.Success(c, opts)
}

// parseDate 解析日期
func parseDate(s string) time.Time {
	if s == "" {
		return time.Now()
	}
	for _, layout := range []string{time.RFC3339, "2006-01-02", "2006-01-02 15:04:05"} {
		if t, err := time.Parse(layout, s); err == nil {
			return t
		}
	}
	return time.Now()
}

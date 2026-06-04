package services

import (
	"context"
	"errors"
	"fmt"
	"strings"
	"time"

	"gorm.io/gorm"

	"table-editor-backend/internal/models"
	"table-editor-backend/internal/repository"
	"table-editor-backend/internal/utils"
)

// LandingService 到货服务
type LandingService struct {
	landingRepo  *repository.LandingRepository
	purchaseRepo *repository.PurchaseRepository
	salesRepo    *repository.SalesRepository
	basicRepo    *repository.BasicRepository
	db           *gorm.DB
}

// NewLandingService 构造
func NewLandingService(
	landingRepo *repository.LandingRepository,
	purchaseRepo *repository.PurchaseRepository,
	salesRepo *repository.SalesRepository,
	basicRepo *repository.BasicRepository,
	db *gorm.DB,
) *LandingService {
	return &LandingService{
		landingRepo:  landingRepo,
		purchaseRepo: purchaseRepo,
		salesRepo:    salesRepo,
		basicRepo:    basicRepo,
		db:           db,
	}
}

// ListLanding 到货列表
func (s *LandingService) ListLanding(ctx context.Context, page, pageSize int, search, sortField, sortOrder string) ([]models.Landing, int64, error) {
	if page < 1 {
		page = 1
	}
	if pageSize < 1 {
		pageSize = 20
	}
	return s.landingRepo.GetList(ctx, page, pageSize, search, sortField, sortOrder)
}

// GetLanding 到货详情
func (s *LandingService) GetLanding(ctx context.Context, id int) (map[string]interface{}, error) {
	landing, err := s.landingRepo.GetByID(ctx, id, true)
	if err != nil {
		if errors.Is(err, repository.ErrNotFound) {
			return nil, fmt.Errorf("到货记录不存在")
		}
		return nil, err
	}
	details, err := s.landingRepo.GetDetailsByLandingID(ctx, id)
	if err != nil {
		return nil, err
	}
	// 关联 stock/bin/unit
	for i := range details {
		s.fillLandingDetail(ctx, &details[i])
	}
	return map[string]interface{}{
		"landing": landing,
		"details": details,
	}, nil
}

// GetLandingDetails 仅明细
func (s *LandingService) GetLandingDetails(ctx context.Context, id int) ([]models.LandingDetail, error) {
	details, err := s.landingRepo.GetDetailsByLandingID(ctx, id)
	if err != nil {
		return nil, err
	}
	for i := range details {
		s.fillLandingDetail(ctx, &details[i])
	}
	return details, nil
}

// CreateLanding 创建到货
func (s *LandingService) CreateLanding(ctx context.Context, l *models.Landing, details []models.LandingDetail) error {
	if l.LandingDate.IsZero() {
		l.LandingDate = time.Now()
	}
	return s.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		if err := tx.Table("tblLanding").Create(l).Error; err != nil {
			return err
		}
		for i := range details {
			details[i].LandingID = l.LandingID
			if err := tx.Table("tblLandingDetail").Create(&details[i]).Error; err != nil {
				return err
			}
		}
		return nil
	})
}

// UpdateLanding 更新到货
func (s *LandingService) UpdateLanding(ctx context.Context, l *models.Landing, details []models.LandingDetail) error {
	return s.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		if err := tx.Table("tblLanding").
			Where("LandingID = ? AND is_del = 0", l.LandingID).
			Updates(map[string]interface{}{
				"LandingDate": l.LandingDate,
				"SupplierID":  l.SupplierID,
				"PortID":      l.PortID,
				"BoatID":      l.BoatID,
			}).Error; err != nil {
			return err
		}
		// 物理删除旧明细，重新插入（保留原 PHP 行为）
		if err := tx.Table("tblLandingDetail").
			Where("LandingID = ?", l.LandingID).
			Delete(&models.LandingDetail{}).Error; err != nil {
			return err
		}
		for i := range details {
			details[i].LandingID = l.LandingID
			details[i].ID = 0
			if err := tx.Table("tblLandingDetail").Create(&details[i]).Error; err != nil {
				return err
			}
		}
		return nil
	})
}

// DeleteLanding 级联删除 Landing + 关联 Purchase + 关联 Sales
func (s *LandingService) DeleteLanding(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		// 1) 查找关联的 Purchase
		var purchaseIDs []int
		if err := tx.Table("tblPurchase").
			Where("LandingID = ?", id).
			Pluck("PurchaseID", &purchaseIDs).Error; err != nil {
			return err
		}
		// 2) 查找关联的 Sales
		var salesIDs []int
		if len(purchaseIDs) > 0 {
			if err := tx.Table("tblSales").
				Where("PurchaseID IN ?", purchaseIDs).
				Pluck("SalesID", &salesIDs).Error; err != nil {
				return err
			}
		}
		// 3) 物理删除 Sales 明细 + 主表
		if len(salesIDs) > 0 {
			if err := tx.Table("tblSalesDetail").
				Where("SalesID IN ?", salesIDs).
				Delete(&models.SalesDetail{}).Error; err != nil {
				return err
			}
			if err := tx.Table("tblSales").
				Where("SalesID IN ?", salesIDs).
				Delete(&models.Sales{}).Error; err != nil {
				return err
			}
		}
		// 4) 物理删除 Purchase 明细 + 主表
		if len(purchaseIDs) > 0 {
			if err := tx.Table("tblPurchaseDetail").
				Where("PurchaseID IN ?", purchaseIDs).
				Delete(&models.PurchaseDetail{}).Error; err != nil {
				return err
			}
			if err := tx.Table("tblPurchase").
				Where("PurchaseID IN ?", purchaseIDs).
				Delete(&models.Purchase{}).Error; err != nil {
				return err
			}
		}
		// 5) 物理删除 Landing 明细 + 主表
		if err := tx.Table("tblLandingDetail").
			Where("LandingID = ?", id).
			Delete(&models.LandingDetail{}).Error; err != nil {
			return err
		}
		if err := tx.Table("tblLanding").
			Where("LandingID = ?", id).
			Delete(&models.Landing{}).Error; err != nil {
			return err
		}
		// 6) 重置自增
		s.landingRepo.ResetAutoIncrement(ctx)
		s.purchaseRepo.ResetAutoIncrement(ctx)
		s.salesRepo.ResetAutoIncrement(ctx)
		return nil
	})
}

// UpdateLWeight 更新单条明细 L-Weight
func (s *LandingService) UpdateLWeight(ctx context.Context, detailID int, weight float64) error {
	// 同时联动更新 Purchase 关联明细的 LandedKG（沿用原逻辑）
	detail, err := s.landingRepo.GetDetailByID(ctx, detailID)
	if err != nil {
		return fmt.Errorf("明细不存在")
	}
	if err := s.landingRepo.UpdateDetailLWeight(ctx, detailID, weight); err != nil {
		return err
	}

	// 如果该 Landing 已有 Purchase 记录，则同步更新
	purchase, _ := s.purchaseRepo.FindByLandingID(ctx, detail.LandingID)
	if purchase == nil {
		return nil
	}

	// 重新计算
	newLandedKG, newGreenKG, newTotal, err := s.computeLandingDetailWeight(ctx, detail.LandingID, detail.StockID, weight)
	if err != nil {
		return err
	}
	// 更新 PurchaseDetail 中对应记录
	return s.db.WithContext(ctx).Table("tblPurchaseDetail").
		Where("PurchaseID = ? AND StockID = ?", purchase.PurchaseID, detail.StockID).
		Updates(map[string]interface{}{
			"LandedKG": newLandedKG,
			"GreenKG":  newGreenKG,
			"Total":    newTotal,
		}).Error
}

// generatePurchaseFromLanding 内部：生成采购
func (s *LandingService) generatePurchaseFromLanding(ctx context.Context, landingID int) (map[string]interface{}, error) {
	landing, err := s.landingRepo.GetByID(ctx, landingID, true)
	if err != nil {
		if errors.Is(err, repository.ErrNotFound) {
			return nil, fmt.Errorf("到货记录不存在")
		}
		return nil, err
	}

	details, err := s.landingRepo.GetDetailsByLandingID(ctx, landingID)
	if err != nil {
		return nil, err
	}
	if len(details) == 0 {
		return nil, fmt.Errorf("到货记录没有明细，无法生成采购单")
	}

	// 检查是否已生成
	existing, _ := s.purchaseRepo.FindByLandingID(ctx, landingID)
	if existing != nil {
		return nil, fmt.Errorf("该到货记录已生成采购单（采购号 %d），请勿重复生成", existing.PurchaseID)
	}

	subtotal := 0.0
	purchaseDetails := make([]map[string]interface{}, 0, len(details))

	for _, d := range details {
		var lWeight, price, landedKG, greenKG, total float64
		var binQty int
		if d.BinQty == 0 {
			binQty = 1
		} else {
			binQty = d.BinQty
		}
		price = d.Price
		if d.LWeight != nil {
			lWeight = *d.LWeight
		}

		if d.LWeight == nil || *d.LWeight == 0 {
			landedKG = 0
			greenKG = 0
			total = 0
		} else {
			binWeight := 0.0
			if d.BinID > 0 {
				var bin models.Bin
				if err := s.db.WithContext(ctx).Table("tblBin").
					Where("BinID = ? AND is_del = 0", d.BinID).First(&bin).Error; err == nil {
					binWeight = bin.BWeight
				}
			}
			totalBinWeight := binWeight * float64(binQty)
			landedKG = lWeight - totalBinWeight

			conversion := 1.0
			var stock models.Stock
			if err := s.db.WithContext(ctx).Table("tblStock").
				Where("StockID = ? AND is_del = 0", d.StockID).First(&stock).Error; err == nil {
				conversion = stock.Conversion
				if conversion == 0 {
					conversion = 1
				}
			}
			greenKG = landedKG * conversion
			total = landedKG * price
		}
		subtotal += total

		purchaseDetails = append(purchaseDetails, map[string]interface{}{
			"StockID":            d.StockID,
			"BinQty":             binQty,
			"ICE":                d.ICE,
			"GreenKG":            utils.Round(greenKG, 3),
			"LandedKG":           utils.Round(landedKG, 3),
			"LandedWeightUnitID": d.WeightUnitID,
			"GreenWeightUnitID":  d.WeightUnitID,
			"Price":              utils.Round(price, 2),
			"Total":              utils.Round(total, 2),
		})
	}

	// 取 GST
	var gstRate float64
	if landing.SupplierID > 0 {
		var sup models.Supplier
		if err := s.db.WithContext(ctx).Table("tblSuppliers").
			Where("SupplierID = ? AND is_del = 0", landing.SupplierID).First(&sup).Error; err == nil {
			gstRate = sup.GST / 100
		}
	}
	gst := subtotal * gstRate
	total := subtotal + gst

	// 生成采购号
	purchaseID := landingID + 50000
	purchase := &models.Purchase{
		PurchaseID:   purchaseID,
		PurchaseDate: landing.LandingDate,
		SupplierID:   landing.SupplierID,
		LandingID:    landing.LandingID,
		PortID:       landing.PortID,
		BoatID:       landing.BoatID,
		Subtotal:     utils.Round(subtotal, 2),
		GST:          utils.Round(gst, 2),
		Total:        utils.Round(total, 2),
	}

	// 写库
	if err := s.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		if err := tx.Table("tblPurchase").Create(purchase).Error; err != nil {
			return err
		}
		for _, pd := range purchaseDetails {
			row := models.PurchaseDetail{
				PurchaseID:         purchase.PurchaseID,
				StockID:            pd["StockID"].(int),
				BinQty:             utils.Ptr(pd["BinQty"].(int)),
				ICE:                pd["ICE"].(int),
				GreenKG:            pd["GreenKG"].(float64),
				LandedKG:           pd["LandedKG"].(float64),
				LandedWeightUnitID: pd["LandedWeightUnitID"].(int),
				GreenWeightUnitID:  pd["GreenWeightUnitID"].(int),
				Price:              pd["Price"].(float64),
				Total:              pd["Total"].(float64),
			}
			if err := tx.Table("tblPurchaseDetail").Create(&row).Error; err != nil {
				return err
			}
		}
		return nil
	}); err != nil {
		return nil, err
	}

	// 重置自增
	_ = s.purchaseRepo.ResetAutoIncrement(ctx)

	return map[string]interface{}{
		"PurchaseID": purchase.PurchaseID,
		"LandingID":  purchase.LandingID,
		"Subtotal":   purchase.Subtotal,
		"GST":        purchase.GST,
		"Total":      purchase.Total,
	}, nil
}

// GeneratePurchase 公开包装：生成采购（保留原 generatePurchaseFromLanding 行为）
func (s *LandingService) GeneratePurchase(ctx context.Context, landingID int) (map[string]interface{}, error) {
	return s.generatePurchaseFromLanding(ctx, landingID)
}

// computeLandingDetailWeight 内部：按当前 L-Weight 计算 LandedKG/GreenKG/Total
func (s *LandingService) computeLandingDetailWeight(ctx context.Context, landingID, stockID int, lWeight float64) (landedKG, greenKG, total float64, err error) {
	landingDetail, err := s.landingRepo.FindDetailByLandingAndStock(ctx, landingID, stockID)
	if err != nil {
		return 0, 0, 0, err
	}
	binQty := 1
	if landingDetail.BinQty > 0 {
		binQty = landingDetail.BinQty
	}
	binWeight := 0.0
	if landingDetail.BinID > 0 {
		var bin models.Bin
		if e := s.db.WithContext(ctx).Table("tblBin").
			Where("BinID = ? AND is_del = 0", landingDetail.BinID).First(&bin).Error; e == nil {
			binWeight = bin.BWeight
		}
	}
	totalBinWeight := binWeight * float64(binQty)
	landedKG = lWeight - totalBinWeight

	conversion := 1.0
	var stock models.Stock
	if e := s.db.WithContext(ctx).Table("tblStock").
		Where("StockID = ? AND is_del = 0", stockID).First(&stock).Error; e == nil {
		conversion = stock.Conversion
		if conversion == 0 {
			conversion = 1
		}
	}
	greenKG = landedKG * conversion
	total = landedKG * landingDetail.Price
	return landedKG, greenKG, total, nil
}

// fillLandingDetail 填充关联字段
func (s *LandingService) fillLandingDetail(ctx context.Context, d *models.LandingDetail) {
	if d.StockID > 0 {
		var st models.Stock
		if err := s.db.WithContext(ctx).Table("tblStock").
			Where("StockID = ? AND is_del = 0", d.StockID).First(&st).Error; err == nil {
			d.Stock = st.Stock
			d.Description = st.Description
			d.State = st.State
			d.Area = st.Area
		}
	}
	if d.BinID > 0 {
		var bn models.Bin
		if err := s.db.WithContext(ctx).Table("tblBin").
			Where("BinID = ? AND is_del = 0", d.BinID).First(&bn).Error; err == nil {
			d.BinName = bn.BinName
		}
	}
	if d.WeightUnitID > 0 {
		var u models.Unit
		if err := s.db.WithContext(ctx).Table("tblUnit").
			Where("UnitID = ?", d.WeightUnitID).First(&u).Error; err == nil {
			d.WeightUnit = u.UnitName
			d.WeightSymbol = u.UnitSymbol
		}
	}
}

// GetOptions 返回前端需要的下拉选项
func (s *LandingService) GetOptions(ctx context.Context) (map[string]interface{}, error) {
	return s.basicRepo.LandingOptions(ctx)
}

// ErrInvalidInput 参数错误
var ErrInvalidInput = errors.New("invalid input")

// normalizeTrim 字符串处理
func normalizeTrim(s string) string { return strings.TrimSpace(s) }

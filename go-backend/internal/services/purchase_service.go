package services

import (
	"context"
	"errors"
	"fmt"
	"time"

	"gorm.io/gorm"

	"table-editor-backend/internal/models"
	"table-editor-backend/internal/repository"
	"table-editor-backend/internal/utils"
)

// PurchaseService 采购服务
type PurchaseService struct {
	purchaseRepo *repository.PurchaseRepository
	landingRepo  *repository.LandingRepository
	salesRepo    *repository.SalesRepository
	basicRepo    *repository.BasicRepository
	db           *gorm.DB
}

// NewPurchaseService 构造
func NewPurchaseService(
	purchaseRepo *repository.PurchaseRepository,
	landingRepo *repository.LandingRepository,
	salesRepo *repository.SalesRepository,
	basicRepo *repository.BasicRepository,
	db *gorm.DB,
) *PurchaseService {
	return &PurchaseService{
		purchaseRepo: purchaseRepo,
		landingRepo:  landingRepo,
		salesRepo:    salesRepo,
		basicRepo:    basicRepo,
		db:           db,
	}
}

// ListPurchase 采购列表
func (s *PurchaseService) ListPurchase(ctx context.Context, page, pageSize int, search, sortField, sortOrder string) ([]models.Purchase, int64, error) {
	if page < 1 {
		page = 1
	}
	if pageSize < 1 {
		pageSize = 20
	}
	return s.purchaseRepo.GetList(ctx, page, pageSize, search, sortField, sortOrder)
}

// GetPurchase 采购详情
func (s *PurchaseService) GetPurchase(ctx context.Context, id int) (map[string]interface{}, error) {
	purchase, err := s.purchaseRepo.GetByID(ctx, id)
	if err != nil {
		if errors.Is(err, repository.ErrNotFound) {
			return nil, fmt.Errorf("采购记录不存在")
		}
		return nil, err
	}
	details, err := s.purchaseRepo.GetDetailsByPurchaseID(ctx, id)
	if err != nil {
		return nil, err
	}
	// 关联 stock
	for i := range details {
		var st models.Stock
		if err := s.db.WithContext(ctx).Table("tblStock").
			Where("StockID = ? AND is_del = 0", details[i].StockID).First(&st).Error; err == nil {
			details[i].Stock = st.Stock
			details[i].Description = st.Description
			details[i].State = st.State
			details[i].Area = st.Area
		}
	}
	return map[string]interface{}{
		"purchase": purchase,
		"details":  details,
	}, nil
}

// DeletePurchase 级联删除 Purchase + Sales
func (s *PurchaseService) DeletePurchase(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		// 1) 关联的 Sales
		var salesIDs []int
		if err := tx.Table("tblSales").
			Where("PurchaseID = ?", id).
			Pluck("SalesID", &salesIDs).Error; err != nil {
			return err
		}
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
		// 2) Purchase
		if err := tx.Table("tblPurchaseDetail").
			Where("PurchaseID = ?", id).
			Delete(&models.PurchaseDetail{}).Error; err != nil {
			return err
		}
		if err := tx.Table("tblPurchase").
			Where("PurchaseID = ?", id).
			Delete(&models.Purchase{}).Error; err != nil {
			return err
		}
		_ = s.purchaseRepo.ResetAutoIncrement(ctx)
		_ = s.salesRepo.ResetAutoIncrement(ctx)
		return nil
	})
}

// GenerateSales 公开包装：生成销售
func (s *PurchaseService) GenerateSales(ctx context.Context, purchaseID int) (map[string]interface{}, error) {
	return s.generateSalesFromPurchase(ctx, purchaseID)
}

// generateSalesFromPurchase 内部：生成销售（保留原 PHP 行为）
func (s *PurchaseService) generateSalesFromPurchase(ctx context.Context, purchaseID int) (map[string]interface{}, error) {
	purchase, err := s.purchaseRepo.GetByID(ctx, purchaseID)
	if err != nil {
		if errors.Is(err, repository.ErrNotFound) {
			return nil, fmt.Errorf("采购记录不存在")
		}
		return nil, err
	}

	// 检查是否已生成
	if existing, _ := s.salesRepo.FindByPurchaseID(ctx, purchaseID); existing != nil {
		return nil, fmt.Errorf("该采购记录已生成销售单（销售号 %d），请勿重复生成", existing.SalesID)
	}

	details, err := s.purchaseRepo.GetDetailsByPurchaseID(ctx, purchaseID)
	if err != nil {
		return nil, err
	}
	if len(details) == 0 {
		return nil, fmt.Errorf("采购记录没有明细，无法生成销售单")
	}

	subtotal := 0.0
	salesDetails := make([]map[string]interface{}, 0, len(details))

	for _, d := range details {
		nWeight := d.LandedKG
		weightUnitID := 1
		if d.LandedWeightUnitID > 0 {
			weightUnitID = d.LandedWeightUnitID
		}
		price := d.Price
		binQty := 1
		if d.BinQty != nil {
			binQty = *d.BinQty
		}

		binID := 0
		bWeight := 0.0
		if purchase.LandingID > 0 {
			landingDetail, err := s.landingRepo.FindDetailByLandingAndStock(ctx, purchase.LandingID, d.StockID)
			if err == nil && landingDetail != nil && landingDetail.BinID > 0 {
				binID = landingDetail.BinID
				var bin models.Bin
				if err := s.db.WithContext(ctx).Table("tblBin").
					Where("BinID = ? AND is_del = 0", binID).First(&bin).Error; err == nil {
					bWeight = bin.BWeight
				}
			}
		}
		gWeight := nWeight + (float64(binQty) * bWeight)
		amount := nWeight * price

		salesDetails = append(salesDetails, map[string]interface{}{
			"StockID":      d.StockID,
			"BinID":        binID,
			"BinQty":       binQty,
			"G-Weight":     utils.Round(gWeight, 3),
			"N-Weight":     utils.Round(nWeight, 3),
			"WeightUnitID": weightUnitID,
			"Price":        utils.Round(price, 2),
			"Amount":       utils.Round(amount, 2),
		})
		subtotal += amount
	}

	// 销售 GST 这里取 0（沿用原 PHP 行为）
	gst := 0.0
	total := subtotal + gst

	// 生成销售号
	salesID := purchaseID + 20000
	sale := &models.Sales{
		SalesID:    salesID,
		SaleDate:   time.Now(),
		CustomerID: 0, // 沿用原 PHP 默认
		PurchaseID: purchase.PurchaseID,
		Subtotal:   utils.Round(subtotal, 2),
		GST:        utils.Round(gst, 2),
		Total:      utils.Round(total, 2),
	}

	if err := s.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		if err := tx.Table("tblSales").Create(sale).Error; err != nil {
			return err
		}
		for _, sd := range salesDetails {
			row := models.SalesDetail{
				SalesID:      sale.SalesID,
				StockID:      sd["StockID"].(int),
				BinID:        utils.Ptr(sd["BinID"].(int)),
				BinQty:       sd["BinQty"].(int),
				GWeight:      sd["G-Weight"].(float64),
				NWeight:      sd["N-Weight"].(float64),
				WeightUnitID: sd["WeightUnitID"].(int),
				Price:        sd["Price"].(float64),
				Amount:       sd["Amount"].(float64),
			}
			if err := tx.Table("tblSalesDetail").Create(&row).Error; err != nil {
				return err
			}
		}
		return nil
	}); err != nil {
		return nil, err
	}
	_ = s.salesRepo.ResetAutoIncrement(ctx)

	return map[string]interface{}{
		"SalesID":    sale.SalesID,
		"PurchaseID": sale.PurchaseID,
		"Subtotal":   sale.Subtotal,
		"GST":        sale.GST,
		"Total":      sale.Total,
	}, nil
}

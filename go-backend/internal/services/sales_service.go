package services

import (
	"context"
	"errors"
	"fmt"

	"gorm.io/gorm"

	"table-editor-backend/internal/models"
	"table-editor-backend/internal/repository"
)

// SalesService 销售服务
type SalesService struct {
	salesRepo    *repository.SalesRepository
	purchaseRepo *repository.PurchaseRepository
	basicRepo    *repository.BasicRepository
	db           *gorm.DB
}

// NewSalesService 构造
func NewSalesService(
	salesRepo *repository.SalesRepository,
	purchaseRepo *repository.PurchaseRepository,
	basicRepo *repository.BasicRepository,
	db *gorm.DB,
) *SalesService {
	return &SalesService{
		salesRepo:    salesRepo,
		purchaseRepo: purchaseRepo,
		basicRepo:    basicRepo,
		db:           db,
	}
}

// ListSales 销售列表
func (s *SalesService) ListSales(ctx context.Context, page, pageSize int, search, sortField, sortOrder string) ([]models.Sales, int64, error) {
	if page < 1 {
		page = 1
	}
	if pageSize < 1 {
		pageSize = 20
	}
	return s.salesRepo.GetList(ctx, page, pageSize, search, sortField, sortOrder)
}

// GetSales 销售详情
func (s *SalesService) GetSales(ctx context.Context, id int) (map[string]interface{}, error) {
	sale, err := s.salesRepo.GetByID(ctx, id)
	if err != nil {
		if errors.Is(err, repository.ErrNotFound) {
			return nil, fmt.Errorf("销售记录不存在")
		}
		return nil, err
	}
	details, err := s.salesRepo.GetDetailsBySalesIDs(ctx, []int{id})
	if err != nil {
		return nil, err
	}
	return map[string]interface{}{
		"sales":   sale,
		"details": details,
	}, nil
}

// DeleteSales 物理删除
func (s *SalesService) DeleteSales(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		if err := tx.Table("tblSalesDetail").
			Where("SalesID = ?", id).
			Delete(&models.SalesDetail{}).Error; err != nil {
			return err
		}
		if err := tx.Table("tblSales").
			Where("SalesID = ?", id).
			Delete(&models.Sales{}).Error; err != nil {
			return err
		}
		_ = s.salesRepo.ResetAutoIncrement(ctx)
		return nil
	})
}

package repository

import (
	"context"
	"errors"
	"fmt"
	"strings"

	"gorm.io/gorm"

	"table-editor-backend/internal/models"
)

// SalesRepository 销售仓储
type SalesRepository struct {
	BaseRepository
}

// NewSalesRepository 构造
func NewSalesRepository(db *gorm.DB) *SalesRepository {
	return &SalesRepository{BaseRepository: BaseRepository{DB: db}}
}

// GetList 分页列表
func (r *SalesRepository) GetList(
	ctx context.Context,
	page, pageSize int,
	search string,
	sortField string,
	sortOrder string,
) ([]models.Sales, int64, error) {
	allowedSort := map[string]string{
		"SalesID":      "tblSales.SalesID",
		"SaleDate":     "tblSales.SaleDate",
		"CustomerName": "customers.CustomerName",
		"Subtotal":     "tblSales.Subtotal",
		"GST":          "tblSales.GST",
		"Total":        "tblSales.Total",
	}
	sortCol, ok := allowedSort[sortField]
	if !ok {
		sortCol = "tblSales.SalesID"
	}
	sortDir := "desc"
	if strings.ToLower(sortOrder) == "asc" {
		sortDir = "asc"
	}

	baseQ := r.DB.WithContext(ctx).Table("tblSales").
		Where("tblSales.is_del = ?", 0)
	if search != "" {
		like := "%" + search + "%"
		baseQ = baseQ.Where(
			r.DB.Where("customers.CustomerName LIKE ?", like).
				Or("tblSales.SaleDate LIKE ?", like),
		)
	}

	var total int64
	if err := baseQ.
		Joins("LEFT JOIN tblCustomer as customers ON tblSales.CustomerID = customers.CustID AND customers.is_del = 0").
		Count(&total).Error; err != nil {
		return nil, 0, err
	}

	type Row struct {
		models.Sales
		CustomerName string
	}
	var rows []Row

	offset := (page - 1) * pageSize
	if err := baseQ.
		Select("tblSales.*, customers.CustomerName").
		Order(sortCol + " " + sortDir).
		Offset(offset).Limit(pageSize).
		Scan(&rows).Error; err != nil {
		return nil, 0, err
	}

	out := make([]models.Sales, 0, len(rows))
	for _, row := range rows {
		var detailCount int64
		var totalG, totalN *float64
		r.DB.WithContext(ctx).Table("tblSalesDetail").
			Where("SalesID = ? AND is_del = 0", row.SalesID).
			Count(&detailCount)
		r.DB.WithContext(ctx).Table("tblSalesDetail").
			Select("SUM(`G-Weight`)").
			Where("SalesID = ? AND is_del = 0", row.SalesID).
			Scan(&totalG)
		r.DB.WithContext(ctx).Table("tblSalesDetail").
			Select("SUM(`N-Weight`)").
			Where("SalesID = ? AND is_del = 0", row.SalesID).
			Scan(&totalN)

		s := row.Sales
		s.CustomerName = row.CustomerName
		s.DetailCount = detailCount
		if totalG != nil {
			s.TotalGWeight = *totalG
		}
		if totalN != nil {
			s.TotalNWeight = *totalN
		}
		out = append(out, s)
	}
	return out, total, nil
}

// GetByID 取单条
func (r *SalesRepository) GetByID(ctx context.Context, id int) (*models.Sales, error) {
	var s models.Sales
	err := r.DB.WithContext(ctx).Table("tblSales").
		Where("SalesID = ? AND is_del = 0", id).First(&s).Error
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	if s.CustomerID > 0 {
		var c models.Customer
		if err := r.DB.WithContext(ctx).Table("tblCustomer").
			Where("CustID = ? AND is_del = 0", s.CustomerID).First(&c).Error; err == nil {
			s.CustomerName = c.CustomerName
		}
	}
	return &s, nil
}

// FindByPurchaseID 根据 PurchaseID 查找
func (r *SalesRepository) FindByPurchaseID(ctx context.Context, purchaseID int) (*models.Sales, error) {
	var s models.Sales
	err := r.DB.WithContext(ctx).Table("tblSales").
		Where("PurchaseID = ? AND is_del = 0", purchaseID).First(&s).Error
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	return &s, nil
}

// Create 创建
func (r *SalesRepository) Create(ctx context.Context, s *models.Sales) error {
	return r.DB.WithContext(ctx).Table("tblSales").Create(s).Error
}

// Update 更新
func (r *SalesRepository) Update(ctx context.Context, s *models.Sales) error {
	return r.DB.WithContext(ctx).Table("tblSales").
		Where("SalesID = ?", s.SalesID).
		Updates(map[string]interface{}{
			"SaleDate":   s.SaleDate,
			"CustomerID": s.CustomerID,
			"Subtotal":   s.Subtotal,
			"GST":        s.GST,
			"Total":      s.Total,
		}).Error
}

// HardDelete 物理删除
func (r *SalesRepository) HardDelete(ctx context.Context, id int) error {
	return r.DB.WithContext(ctx).Table("tblSales").
		Where("SalesID = ?", id).
		Delete(&models.Sales{}).Error
}

// HardDeleteByIDs 物理删除多个
func (r *SalesRepository) HardDeleteByIDs(ctx context.Context, ids []int) error {
	if len(ids) == 0 {
		return nil
	}
	return r.DB.WithContext(ctx).Table("tblSales").
		Where("SalesID IN ?", ids).
		Delete(&models.Sales{}).Error
}

// HardDeleteDetailsBySalesIDs 物理删除明细
func (r *SalesRepository) HardDeleteDetailsBySalesIDs(ctx context.Context, ids []int) error {
	if len(ids) == 0 {
		return nil
	}
	return r.DB.WithContext(ctx).Table("tblSalesDetail").
		Where("SalesID IN ?", ids).
		Delete(&models.SalesDetail{}).Error
}

// GetDetailsBySalesIDs 批量取明细
func (r *SalesRepository) GetDetailsBySalesIDs(ctx context.Context, ids []int) ([]models.SalesDetail, error) {
	if len(ids) == 0 {
		return nil, nil
	}
	var details []models.SalesDetail
	if err := r.DB.WithContext(ctx).Table("tblSalesDetail").
		Where("SalesID IN ? AND is_del = 0", ids).
		Order("SalesID ASC, ID ASC").
		Find(&details).Error; err != nil {
		return nil, err
	}
	for i := range details {
		d := &details[i]
		var st models.Stock
		if err := r.DB.WithContext(ctx).Table("tblStock").
			Where("StockID = ? AND is_del = 0", d.StockID).First(&st).Error; err == nil {
			d.Stock = st.Stock
			d.Description = st.Description
			d.State = st.State
			d.Area = st.Area
		}
		if d.WeightUnitID > 0 {
			var u models.Unit
			if err := r.DB.WithContext(ctx).Table("tblUnit").
				Where("UnitID = ?", d.WeightUnitID).First(&u).Error; err == nil {
				d.WeightUnit = u.UnitName
				d.WeightSymbol = u.UnitSymbol
			}
		}
	}
	return details, nil
}

// SoftDeleteDetailsBySalesID 软删除明细
func (r *SalesRepository) SoftDeleteDetailsBySalesID(ctx context.Context, id int) error {
	return r.DB.WithContext(ctx).Table("tblSalesDetail").
		Where("SalesID = ?", id).
		Update("is_del", 1).Error
}

// CreateDetail 创建明细
func (r *SalesRepository) CreateDetail(ctx context.Context, d *models.SalesDetail) error {
	return r.DB.WithContext(ctx).Table("tblSalesDetail").Create(d).Error
}

// ResetAutoIncrement 重置自增
func (r *SalesRepository) ResetAutoIncrement(ctx context.Context) error {
	var maxID int
	if err := r.DB.WithContext(ctx).Table("tblSales").
		Select("COALESCE(MAX(SalesID), 0)").Scan(&maxID).Error; err != nil {
		return err
	}
	next := maxID + 1
	return r.DB.WithContext(ctx).Exec(
		fmt.Sprintf("ALTER TABLE tblSales AUTO_INCREMENT = %d", next)).Error
}

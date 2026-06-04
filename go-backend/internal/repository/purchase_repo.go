package repository

import (
	"context"
	"errors"
	"fmt"
	"strings"

	"gorm.io/gorm"

	"table-editor-backend/internal/models"
)

// PurchaseRepository 采购仓储
type PurchaseRepository struct {
	BaseRepository
}

// NewPurchaseRepository 构造
func NewPurchaseRepository(db *gorm.DB) *PurchaseRepository {
	return &PurchaseRepository{BaseRepository: BaseRepository{DB: db}}
}

// GetList 分页列表
func (r *PurchaseRepository) GetList(
	ctx context.Context,
	page, pageSize int,
	search string,
	sortField string,
	sortOrder string,
) ([]models.Purchase, int64, error) {
	allowedSort := map[string]string{
		"PurchaseID":   "tblPurchase.PurchaseID",
		"PurchaseDate": "tblPurchase.PurchaseDate",
		"SupplierName": "suppliers.SupplierName",
		"Subtotal":     "tblPurchase.Subtotal",
		"GST":          "tblPurchase.GST",
		"Total":        "tblPurchase.Total",
	}
	sortCol, ok := allowedSort[sortField]
	if !ok {
		sortCol = "tblPurchase.PurchaseID"
	}
	sortDir := "desc"
	if strings.ToLower(sortOrder) == "asc" {
		sortDir = "asc"
	}

	baseQ := r.DB.WithContext(ctx).Table("tblPurchase").
		Where("tblPurchase.is_del = ?", 0)
	if search != "" {
		like := "%" + search + "%"
		baseQ = baseQ.Where(
			r.DB.Where("suppliers.SupplierName LIKE ?", like).
				Or("tblPurchase.PurchaseDate LIKE ?", like),
		)
	}

	var total int64
	if err := baseQ.
		Joins("LEFT JOIN tblSuppliers as suppliers ON tblPurchase.SupplierID = suppliers.SupplierID AND suppliers.is_del = 0").
		Count(&total).Error; err != nil {
		return nil, 0, err
	}

	type Row struct {
		models.Purchase
		SupplierName string
		BoatName     string
		BoatNo       string
		PortName     string
	}
	var rows []Row

	offset := (page - 1) * pageSize
	if err := baseQ.
		Select(`tblPurchase.*, suppliers.SupplierName,
			boats.BoatName, boats.BoatNo, ports.Port as PortName`).
		Joins("LEFT JOIN tblBoat as boats ON tblPurchase.BoatID = boats.BoatID AND boats.is_del = 0").
		Joins("LEFT JOIN tblPort as ports ON tblPurchase.PortID = ports.PortID AND ports.is_del = 0").
		Order(sortCol + " " + sortDir).
		Offset(offset).Limit(pageSize).
		Scan(&rows).Error; err != nil {
		return nil, 0, err
	}

	out := make([]models.Purchase, 0, len(rows))
	for _, row := range rows {
		var detailCount int64
		var totalGreen, totalLanded *float64
		r.DB.WithContext(ctx).Table("tblPurchaseDetail").
			Where("PurchaseID = ? AND is_del = 0", row.PurchaseID).
			Count(&detailCount)
		r.DB.WithContext(ctx).Table("tblPurchaseDetail").
			Select("SUM(GreenKG)").
			Where("PurchaseID = ? AND is_del = 0", row.PurchaseID).
			Scan(&totalGreen)
		r.DB.WithContext(ctx).Table("tblPurchaseDetail").
			Select("SUM(LandedKG)").
			Where("PurchaseID = ? AND is_del = 0", row.PurchaseID).
			Scan(&totalLanded)

		p := row.Purchase
		p.SupplierName = row.SupplierName
		p.BoatName = row.BoatName
		p.BoatNo = row.BoatNo
		p.PortName = row.PortName
		p.DetailCount = detailCount
		if totalGreen != nil {
			p.TotalGreenKG = *totalGreen
		}
		if totalLanded != nil {
			p.TotalLandedKG = *totalLanded
		}
		out = append(out, p)
	}
	return out, total, nil
}

// GetByID 取单条
func (r *PurchaseRepository) GetByID(ctx context.Context, id int) (*models.Purchase, error) {
	var p models.Purchase
	err := r.DB.WithContext(ctx).Table("tblPurchase").
		Where("PurchaseID = ? AND is_del = 0", id).First(&p).Error
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	if p.SupplierID > 0 {
		var s models.Supplier
		if err := r.DB.WithContext(ctx).Table("tblSuppliers").
			Where("SupplierID = ? AND is_del = 0", p.SupplierID).First(&s).Error; err == nil {
			p.SupplierName = s.SupplierName
		}
	}
	if p.BoatID > 0 {
		var b models.Boat
		if err := r.DB.WithContext(ctx).Table("tblBoat").
			Where("BoatID = ? AND is_del = 0", p.BoatID).First(&b).Error; err == nil {
			p.BoatName = b.BoatName
			p.BoatNo = b.BoatNo
		}
	}
	if p.PortID > 0 {
		var pt models.Port
		if err := r.DB.WithContext(ctx).Table("tblPort").
			Where("PortID = ? AND is_del = 0", p.PortID).First(&pt).Error; err == nil {
			p.PortName = pt.Port
		}
	}
	return &p, nil
}

// FindByIDIncludingDeleted 含已删除
func (r *PurchaseRepository) FindByIDIncludingDeleted(ctx context.Context, id int) (*models.Purchase, error) {
	var p models.Purchase
	err := r.DB.WithContext(ctx).Table("tblPurchase").
		Where("PurchaseID = ?", id).First(&p).Error
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	return &p, nil
}

// FindByLandingID 根据 LandingID 查找（未删除）
func (r *PurchaseRepository) FindByLandingID(ctx context.Context, landingID int) (*models.Purchase, error) {
	var p models.Purchase
	err := r.DB.WithContext(ctx).Table("tblPurchase").
		Where("LandingID = ? AND is_del = 0", landingID).First(&p).Error
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	return &p, nil
}

// Create 创建
func (r *PurchaseRepository) Create(ctx context.Context, p *models.Purchase) error {
	return r.DB.WithContext(ctx).Table("tblPurchase").Create(p).Error
}

// Update 全部字段更新
func (r *PurchaseRepository) Update(ctx context.Context, p *models.Purchase) error {
	return r.DB.WithContext(ctx).Table("tblPurchase").
		Where("PurchaseID = ?", p.PurchaseID).
		Updates(map[string]interface{}{
			"PurchaseDate": p.PurchaseDate,
			"SupplierID":   p.SupplierID,
			"PortID":       p.PortID,
			"BoatID":       p.BoatID,
			"Subtotal":     p.Subtotal,
			"GST":          p.GST,
			"Total":        p.Total,
		}).Error
}

// Restore 恢复软删除
func (r *PurchaseRepository) Restore(ctx context.Context, p *models.Purchase) error {
	return r.DB.WithContext(ctx).Table("tblPurchase").
		Where("PurchaseID = ?", p.PurchaseID).
		Updates(map[string]interface{}{
			"PurchaseDate": p.PurchaseDate,
			"SupplierID":   p.SupplierID,
			"LandingID":    p.LandingID,
			"PortID":       p.PortID,
			"BoatID":       p.BoatID,
			"Subtotal":     p.Subtotal,
			"GST":          p.GST,
			"Total":        p.Total,
			"is_del":       0,
		}).Error
}

// HardDelete 物理删除主表
func (r *PurchaseRepository) HardDelete(ctx context.Context, id int) error {
	return r.DB.WithContext(ctx).Table("tblPurchase").
		Where("PurchaseID = ?", id).
		Delete(&models.Purchase{}).Error
}

// HardDeleteDetailsByPurchaseIDs 物理删除明细
func (r *PurchaseRepository) HardDeleteDetailsByPurchaseIDs(ctx context.Context, ids []int) error {
	if len(ids) == 0 {
		return nil
	}
	return r.DB.WithContext(ctx).Table("tblPurchaseDetail").
		Where("PurchaseID IN ?", ids).
		Delete(&models.PurchaseDetail{}).Error
}

// HardDeleteByIDs 物理删除多个
func (r *PurchaseRepository) HardDeleteByIDs(ctx context.Context, ids []int) error {
	if len(ids) == 0 {
		return nil
	}
	return r.DB.WithContext(ctx).Table("tblPurchase").
		Where("PurchaseID IN ?", ids).
		Delete(&models.Purchase{}).Error
}

// GetDetailsByPurchaseIDs 批量取明细
func (r *PurchaseRepository) GetDetailsByPurchaseIDs(ctx context.Context, ids []int) ([]models.PurchaseDetail, error) {
	if len(ids) == 0 {
		return nil, nil
	}
	var details []models.PurchaseDetail
	if err := r.DB.WithContext(ctx).Table("tblPurchaseDetail").
		Where("PurchaseID IN ? AND is_del = 0", ids).
		Order("PurchaseID ASC, ID ASC").
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
	}
	return details, nil
}

// GetDetailsByPurchaseID 取所有未删除明细
func (r *PurchaseRepository) GetDetailsByPurchaseID(ctx context.Context, id int) ([]models.PurchaseDetail, error) {
	var ds []models.PurchaseDetail
	err := r.DB.WithContext(ctx).Table("tblPurchaseDetail").
		Where("PurchaseID = ? AND is_del = 0", id).
		Find(&ds).Error
	return ds, err
}

// SoftDeleteDetailsByPurchaseID 软删除明细
func (r *PurchaseRepository) SoftDeleteDetailsByPurchaseID(ctx context.Context, id int) error {
	return r.DB.WithContext(ctx).Table("tblPurchaseDetail").
		Where("PurchaseID = ?", id).
		Update("is_del", 1).Error
}

// CreateDetail 创建明细
func (r *PurchaseRepository) CreateDetail(ctx context.Context, d *models.PurchaseDetail) error {
	return r.DB.WithContext(ctx).Table("tblPurchaseDetail").Create(d).Error
}

// UpdateEmailSent 更新邮件发送标记
func (r *PurchaseRepository) UpdateEmailSent(ctx context.Context, id int, sent int) error {
	return r.DB.WithContext(ctx).Table("tblPurchase").
		Where("PurchaseID = ?", id).
		Update("EmailSent", sent).Error
}

// ResetAutoIncrement 重置自增
func (r *PurchaseRepository) ResetAutoIncrement(ctx context.Context) error {
	var maxID int
	if err := r.DB.WithContext(ctx).Table("tblPurchase").
		Select("COALESCE(MAX(PurchaseID), 0)").Scan(&maxID).Error; err != nil {
		return err
	}
	next := maxID + 1
	return r.DB.WithContext(ctx).Exec(
		fmt.Sprintf("ALTER TABLE tblPurchase AUTO_INCREMENT = %d", next)).Error
}

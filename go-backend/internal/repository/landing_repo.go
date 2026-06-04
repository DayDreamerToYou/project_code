package repository

import (
	"context"
	"errors"
	"fmt"
	"strings"

	"gorm.io/gorm"

	"table-editor-backend/internal/models"
)

// LandingRepository 到货仓储
type LandingRepository struct {
	BaseRepository
}

// NewLandingRepository 构造
func NewLandingRepository(db *gorm.DB) *LandingRepository {
	return &LandingRepository{BaseRepository: BaseRepository{DB: db}}
}

// GetList 分页列表（与原 PHP 行为完全一致）
func (r *LandingRepository) GetList(
	ctx context.Context,
	page, pageSize int,
	search string,
	sortField string,
	sortOrder string,
) ([]models.Landing, int64, error) {
	allowedSort := map[string]string{
		"LandingID":    "tblLanding.LandingID",
		"LandingDate":  "tblLanding.LandingDate",
		"SupplierName": "suppliers.SupplierName",
		"Port":         "ports.Port",
		"BoatNo":       "boats.BoatNo",
		"BoatName":     "boats.BoatName",
	}
	sortCol, ok := allowedSort[sortField]
	if !ok {
		sortCol = "tblLanding.LandingID"
	}
	sortDir := "desc"
	if strings.ToLower(sortOrder) == "asc" {
		sortDir = "asc"
	}

	// 基础查询
	baseQ := r.DB.WithContext(ctx).Table("tblLanding").
		Where("tblLanding.is_del = ?", 0)
	if search != "" {
		like := "%" + search + "%"
		baseQ = baseQ.Where(
			r.DB.Where("suppliers.SupplierName LIKE ?", like).
				Or("ports.Port LIKE ?", like).
				Or("tblLanding.LandingDate LIKE ?", like).
				Or("boats.BoatName LIKE ?", like),
		)
	}

	// 总数
	var total int64
	if err := baseQ.
		Joins("LEFT JOIN tblSuppliers as suppliers ON tblLanding.SupplierID = suppliers.SupplierID AND suppliers.is_del = 0").
		Joins("LEFT JOIN tblPort as ports ON tblLanding.PortID = ports.PortID AND ports.is_del = 0").
		Joins("LEFT JOIN tblBoat as boats ON tblLanding.BoatID = boats.BoatID AND boats.is_del = 0").
		Count(&total).Error; err != nil {
		return nil, 0, err
	}

	type Row struct {
		models.Landing
		SupplierName string
		PortName     string
		BoatName     string
		BoatNo       string
	}
	var rows []Row

	offset := (page - 1) * pageSize
	if err := baseQ.
		Select(`tblLanding.*, suppliers.SupplierName as SupplierName,
			ports.Port as PortName, boats.BoatName, boats.BoatNo`).
		Order(sortCol + " " + sortDir).
		Offset(offset).Limit(pageSize).
		Scan(&rows).Error; err != nil {
		return nil, 0, err
	}

	// 计算 detail_count 和 total_weight
	out := make([]models.Landing, 0, len(rows))
	for _, row := range rows {
		var detailCount int64
		var totalWeight *float64
		r.DB.WithContext(ctx).Table("tblLandingDetail").
			Where("LandingID = ? AND is_del = 0", row.LandingID).
			Count(&detailCount)
		r.DB.WithContext(ctx).Table("tblLandingDetail").
			Select("SUM(`L-Weight`)").
			Where("LandingID = ? AND is_del = 0", row.LandingID).
			Scan(&totalWeight)

		l := row.Landing
		l.SupplierName = row.SupplierName
		l.PortName = row.PortName
		l.BoatName = row.BoatName
		l.BoatNo = row.BoatNo
		l.DetailCount = detailCount
		if totalWeight != nil {
			l.TotalWeight = *totalWeight
		}
		out = append(out, l)
	}
	return out, total, nil
}

// GetByID 根据 ID 查询（带关联）
func (r *LandingRepository) GetByID(ctx context.Context, id int, includeDetails bool) (*models.Landing, error) {
	var l models.Landing
	err := r.DB.WithContext(ctx).Table("tblLanding").
		Where("LandingID = ? AND is_del = 0", id).First(&l).Error
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}

	// 关联 supplier
	if l.SupplierID > 0 {
		var s models.Supplier
		if err := r.DB.WithContext(ctx).Table("tblSuppliers").
			Where("SupplierID = ? AND is_del = 0", l.SupplierID).First(&s).Error; err == nil {
			l.SupplierName = s.SupplierName
		}
	}
	// 关联 port
	if l.PortID > 0 {
		var p models.Port
		if err := r.DB.WithContext(ctx).Table("tblPort").
			Where("PortID = ? AND is_del = 0", l.PortID).First(&p).Error; err == nil {
			l.PortName = p.Port
		}
	}
	// 关联 boat
	if l.BoatID > 0 {
		var b models.Boat
		if err := r.DB.WithContext(ctx).Table("tblBoat").
			Where("BoatID = ? AND is_del = 0", l.BoatID).First(&b).Error; err == nil {
			l.BoatName = b.BoatName
			l.BoatNo = b.BoatNo
		}
	}
	return &l, nil
}

// Create 创建到货主表
func (r *LandingRepository) Create(ctx context.Context, l *models.Landing) error {
	return r.DB.WithContext(ctx).Table("tblLanding").Create(l).Error
}

// Update 更新到货主表
func (r *LandingRepository) Update(ctx context.Context, l *models.Landing) error {
	return r.DB.WithContext(ctx).Table("tblLanding").
		Where("LandingID = ?", l.LandingID).
		Updates(map[string]interface{}{
			"LandingDate": l.LandingDate,
			"SupplierID":  l.SupplierID,
			"PortID":      l.PortID,
			"BoatID":      l.BoatID,
		}).Error
}

// HardDelete 物理删除
func (r *LandingRepository) HardDelete(ctx context.Context, id int) error {
	return r.DB.WithContext(ctx).Table("tblLanding").
		Where("LandingID = ?", id).
		Delete(&models.Landing{}).Error
}

// GetDetailsByLandingIDs 批量取明细
func (r *LandingRepository) GetDetailsByLandingIDs(ctx context.Context, ids []int) ([]models.LandingDetail, error) {
	if len(ids) == 0 {
		return nil, nil
	}
	var details []models.LandingDetail
	if err := r.DB.WithContext(ctx).Table("tblLandingDetail").
		Where("LandingID IN ? AND is_del = 0", ids).
		Order("ID ASC").
		Find(&details).Error; err != nil {
		return nil, err
	}

	// 关联 stock/bin/unit
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
		if d.BinID > 0 {
			var bn models.Bin
			if err := r.DB.WithContext(ctx).Table("tblBin").
				Where("BinID = ? AND is_del = 0", d.BinID).First(&bn).Error; err == nil {
				d.BinName = bn.BinName
			}
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

// SoftDeleteDetailsByLandingID 软删除明细
func (r *LandingRepository) SoftDeleteDetailsByLandingID(ctx context.Context, id int) error {
	return r.DB.WithContext(ctx).Table("tblLandingDetail").
		Where("LandingID = ?", id).
		Update("is_del", 1).Error
}

// HardDeleteDetailsByLandingID 物理删除明细
func (r *LandingRepository) HardDeleteDetailsByLandingID(ctx context.Context, id int) error {
	return r.DB.WithContext(ctx).Table("tblLandingDetail").
		Where("LandingID = ?", id).
		Delete(&models.LandingDetail{}).Error
}

// CreateDetail 创建明细
func (r *LandingRepository) CreateDetail(ctx context.Context, d *models.LandingDetail) error {
	return r.DB.WithContext(ctx).Table("tblLandingDetail").Create(d).Error
}

// GetDetailByID 按主键取明细
func (r *LandingRepository) GetDetailByID(ctx context.Context, id int) (*models.LandingDetail, error) {
	var d models.LandingDetail
	if err := r.DB.WithContext(ctx).Table("tblLandingDetail").
		Where("ID = ?", id).First(&d).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	return &d, nil
}

// FindDetailByLandingAndStock 查找指定 landing + stock 的明细
func (r *LandingRepository) FindDetailByLandingAndStock(ctx context.Context, landingID, stockID int) (*models.LandingDetail, error) {
	var d models.LandingDetail
	err := r.DB.WithContext(ctx).Table("tblLandingDetail").
		Where("LandingID = ? AND StockID = ? AND is_del = 0", landingID, stockID).
		First(&d).Error
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	return &d, nil
}

// UpdateDetailLWeight 更新 L-Weight
func (r *LandingRepository) UpdateDetailLWeight(ctx context.Context, id int, weight float64) error {
	return r.DB.WithContext(ctx).Table("tblLandingDetail").
		Where("ID = ?", id).
		Update("L-Weight", weight).Error
}

// GetDetailsByLandingID 取所有未删除明细
func (r *LandingRepository) GetDetailsByLandingID(ctx context.Context, id int) ([]models.LandingDetail, error) {
	var ds []models.LandingDetail
	err := r.DB.WithContext(ctx).Table("tblLandingDetail").
		Where("LandingID = ? AND is_del = 0", id).
		Find(&ds).Error
	return ds, err
}

// ResetAutoIncrement 重置自增
func (r *LandingRepository) ResetAutoIncrement(ctx context.Context) error {
	var maxID int
	if err := r.DB.WithContext(ctx).Table("tblLanding").
		Select("COALESCE(MAX(LandingID), 0)").Scan(&maxID).Error; err != nil {
		return err
	}
	next := maxID + 1
	return r.DB.WithContext(ctx).Exec(
		fmt.Sprintf("ALTER TABLE tblLanding AUTO_INCREMENT = %d", next)).Error
}

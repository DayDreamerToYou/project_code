package services

import (
	"context"
	"errors"
	"fmt"

	"gorm.io/gorm"

	"table-editor-backend/internal/models"
	"table-editor-backend/internal/repository"
)

// BasicService 基础数据服务（Supplier/Port/Boat/Bin/Stock/Unit/Customer/...）
type BasicService struct {
	basicRepo *repository.BasicRepository
	db        *gorm.DB
}

// NewBasicService 构造
func NewBasicService(basicRepo *repository.BasicRepository, db *gorm.DB) *BasicService {
	return &BasicService{basicRepo: basicRepo, db: db}
}

// GetSuppliers / GetPorts / GetBoats / GetStocks / GetBins / GetUnits / GetCustomers / GetFleets
// 全部使用通用 List 方法

// ListSuppliers 供应商列表
func (s *BasicService) ListSuppliers(ctx context.Context) ([]models.Supplier, error) {
	var list []models.Supplier
	if err := s.db.WithContext(ctx).Table("tblSuppliers").
		Where("is_del = ?", 0).Find(&list).Error; err != nil {
		return nil, err
	}
	return list, nil
}

// GetSupplier 单条
func (s *BasicService) GetSupplier(ctx context.Context, id int) (*models.Supplier, error) {
	var item models.Supplier
	if err := s.db.WithContext(ctx).Table("tblSuppliers").
		Where("SupplierID = ? AND is_del = 0", id).First(&item).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, fmt.Errorf("供应商不存在")
		}
		return nil, err
	}
	return &item, nil
}

// CreateSupplier 创建
func (s *BasicService) CreateSupplier(ctx context.Context, item *models.Supplier) error {
	return s.db.WithContext(ctx).Table("tblSuppliers").Create(item).Error
}

// UpdateSupplier 更新
func (s *BasicService) UpdateSupplier(ctx context.Context, item *models.Supplier) error {
	return s.db.WithContext(ctx).Table("tblSuppliers").
		Where("SupplierID = ?", item.SupplierID).
		Updates(map[string]interface{}{
			"SupplierName": item.SupplierName,
			"GST":          item.GST,
		}).Error
}

// DeleteSupplier 软删除
func (s *BasicService) DeleteSupplier(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Table("tblSuppliers").
		Where("SupplierID = ?", id).Update("is_del", 1).Error
}

// === Ports ===

func (s *BasicService) ListPorts(ctx context.Context) ([]models.Port, error) {
	var list []models.Port
	if err := s.db.WithContext(ctx).Table("tblPort").
		Where("is_del = ?", 0).Find(&list).Error; err != nil {
		return nil, err
	}
	return list, nil
}

func (s *BasicService) GetPort(ctx context.Context, id int) (*models.Port, error) {
	var item models.Port
	if err := s.db.WithContext(ctx).Table("tblPort").
		Where("PortID = ? AND is_del = 0", id).First(&item).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, fmt.Errorf("港口不存在")
		}
		return nil, err
	}
	return &item, nil
}

func (s *BasicService) CreatePort(ctx context.Context, item *models.Port) error {
	return s.db.WithContext(ctx).Table("tblPort").Create(item).Error
}

func (s *BasicService) UpdatePort(ctx context.Context, item *models.Port) error {
	return s.db.WithContext(ctx).Table("tblPort").
		Where("PortID = ?", item.PortID).
		Update("Port", item.Port).Error
}

func (s *BasicService) DeletePort(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Table("tblPort").
		Where("PortID = ?", id).Update("is_del", 1).Error
}

// === Boats ===

func (s *BasicService) ListBoats(ctx context.Context) ([]models.Boat, error) {
	var list []models.Boat
	if err := s.db.WithContext(ctx).Table("tblBoat").
		Where("is_del = ?", 0).Find(&list).Error; err != nil {
		return nil, err
	}
	return list, nil
}

func (s *BasicService) GetBoat(ctx context.Context, id int) (*models.Boat, error) {
	var item models.Boat
	if err := s.db.WithContext(ctx).Table("tblBoat").
		Where("BoatID = ? AND is_del = 0", id).First(&item).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, fmt.Errorf("船不存在")
		}
		return nil, err
	}
	return &item, nil
}

func (s *BasicService) CreateBoat(ctx context.Context, item *models.Boat) error {
	return s.db.WithContext(ctx).Table("tblBoat").Create(item).Error
}

func (s *BasicService) UpdateBoat(ctx context.Context, item *models.Boat) error {
	return s.db.WithContext(ctx).Table("tblBoat").
		Where("BoatID = ?", item.BoatID).
		Updates(map[string]interface{}{
			"BoatName": item.BoatName,
			"BoatNo":   item.BoatNo,
		}).Error
}

func (s *BasicService) DeleteBoat(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Table("tblBoat").
		Where("BoatID = ?", id).Update("is_del", 1).Error
}

// === Bins ===

func (s *BasicService) ListBins(ctx context.Context) ([]models.Bin, error) {
	var list []models.Bin
	if err := s.db.WithContext(ctx).Table("tblBin").
		Where("is_del = ?", 0).Find(&list).Error; err != nil {
		return nil, err
	}
	return list, nil
}

func (s *BasicService) GetBin(ctx context.Context, id int) (*models.Bin, error) {
	var item models.Bin
	if err := s.db.WithContext(ctx).Table("tblBin").
		Where("BinID = ? AND is_del = 0", id).First(&item).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, fmt.Errorf("箱子不存在")
		}
		return nil, err
	}
	return &item, nil
}

func (s *BasicService) CreateBin(ctx context.Context, item *models.Bin) error {
	return s.db.WithContext(ctx).Table("tblBin").Create(item).Error
}

func (s *BasicService) UpdateBin(ctx context.Context, item *models.Bin) error {
	return s.db.WithContext(ctx).Table("tblBin").
		Where("BinID = ?", item.BinID).
		Updates(map[string]interface{}{
			"BinName": item.BinName,
			"B-Weight": item.BWeight,
		}).Error
}

func (s *BasicService) DeleteBin(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Table("tblBin").
		Where("BinID = ?", id).Update("is_del", 1).Error
}

// === Stocks ===

func (s *BasicService) ListStocks(ctx context.Context) ([]models.Stock, error) {
	var list []models.Stock
	if err := s.db.WithContext(ctx).Table("tblStock").
		Where("is_del = ?", 0).Find(&list).Error; err != nil {
		return nil, err
	}
	return list, nil
}

func (s *BasicService) GetStock(ctx context.Context, id int) (*models.Stock, error) {
	var item models.Stock
	if err := s.db.WithContext(ctx).Table("tblStock").
		Where("StockID = ? AND is_del = 0", id).First(&item).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, fmt.Errorf("库存品不存在")
		}
		return nil, err
	}
	return &item, nil
}

func (s *BasicService) CreateStock(ctx context.Context, item *models.Stock) error {
	return s.db.WithContext(ctx).Table("tblStock").Create(item).Error
}

func (s *BasicService) UpdateStock(ctx context.Context, item *models.Stock) error {
	return s.db.WithContext(ctx).Table("tblStock").
		Where("StockID = ?", item.StockID).
		Updates(map[string]interface{}{
			"Stock":       item.Stock,
			"Description": item.Description,
			"State":       item.State,
			"Area":        item.Area,
			"Conversion":  item.Conversion,
		}).Error
}

func (s *BasicService) DeleteStock(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Table("tblStock").
		Where("StockID = ?", id).Update("is_del", 1).Error
}

// === Units ===

func (s *BasicService) ListUnits(ctx context.Context) ([]models.Unit, error) {
	var list []models.Unit
	if err := s.db.WithContext(ctx).Table("tblUnit").Find(&list).Error; err != nil {
		return nil, err
	}
	return list, nil
}

func (s *BasicService) GetUnit(ctx context.Context, id int) (*models.Unit, error) {
	var item models.Unit
	if err := s.db.WithContext(ctx).Table("tblUnit").
		Where("UnitID = ?", id).First(&item).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, fmt.Errorf("单位不存在")
		}
		return nil, err
	}
	return &item, nil
}

func (s *BasicService) CreateUnit(ctx context.Context, item *models.Unit) error {
	return s.db.WithContext(ctx).Table("tblUnit").Create(item).Error
}

func (s *BasicService) UpdateUnit(ctx context.Context, item *models.Unit) error {
	return s.db.WithContext(ctx).Table("tblUnit").
		Where("UnitID = ?", item.UnitID).
		Updates(map[string]interface{}{
			"UnitName":   item.UnitName,
			"UnitSymbol": item.UnitSymbol,
		}).Error
}

func (s *BasicService) DeleteUnit(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Table("tblUnit").
		Where("UnitID = ?", id).Update("is_del", 1).Error
}

// === Customers ===

func (s *BasicService) ListCustomers(ctx context.Context) ([]models.Customer, error) {
	var list []models.Customer
	if err := s.db.WithContext(ctx).Table("tblCustomer").
		Where("is_del = ?", 0).Find(&list).Error; err != nil {
		return nil, err
	}
	return list, nil
}

func (s *BasicService) GetCustomer(ctx context.Context, id int) (*models.Customer, error) {
	var item models.Customer
	if err := s.db.WithContext(ctx).Table("tblCustomer").
		Where("CustID = ? AND is_del = 0", id).First(&item).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, fmt.Errorf("客户不存在")
		}
		return nil, err
	}
	return &item, nil
}

func (s *BasicService) CreateCustomer(ctx context.Context, item *models.Customer) error {
	return s.db.WithContext(ctx).Table("tblCustomer").Create(item).Error
}

func (s *BasicService) UpdateCustomer(ctx context.Context, item *models.Customer) error {
	return s.db.WithContext(ctx).Table("tblCustomer").
		Where("CustID = ?", item.CustID).
		Update("CustomerName", item.CustomerName).Error
}

func (s *BasicService) DeleteCustomer(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Table("tblCustomer").
		Where("CustID = ?", id).Update("is_del", 1).Error
}

// === Fleets ===

func (s *BasicService) ListFleets(ctx context.Context) ([]models.Fleet, error) {
	var list []models.Fleet
	if err := s.db.WithContext(ctx).Table("tblFleet").
		Where("is_del = ?", 0).Find(&list).Error; err != nil {
		return nil, err
	}
	return list, nil
}

func (s *BasicService) GetFleet(ctx context.Context, id int) (*models.Fleet, error) {
	var item models.Fleet
	if err := s.db.WithContext(ctx).Table("tblFleet").
		Where("FleetID = ? AND is_del = 0", id).First(&item).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, fmt.Errorf("船队不存在")
		}
		return nil, err
	}
	return &item, nil
}

func (s *BasicService) CreateFleet(ctx context.Context, item *models.Fleet) error {
	return s.db.WithContext(ctx).Table("tblFleet").Create(item).Error
}

func (s *BasicService) UpdateFleet(ctx context.Context, item *models.Fleet) error {
	return s.db.WithContext(ctx).Table("tblFleet").
		Where("FleetID = ?", item.FleetID).
		Update("FleetName", item.FleetName).Error
}

func (s *BasicService) DeleteFleet(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Table("tblFleet").
		Where("FleetID = ?", id).Update("is_del", 1).Error
}

// FleetBoats 取某船队下的船
func (s *BasicService) FleetBoats(ctx context.Context, fleetID int) ([]map[string]interface{}, error) {
	return s.basicRepo.FleetBoats(ctx, fleetID)
}

// BoatFleets 取某船所属的船队
func (s *BasicService) BoatFleets(ctx context.Context, boatID int) ([]map[string]interface{}, error) {
	return s.basicRepo.BoatFleets(ctx, boatID)
}

// AddFleetBoat 添加船到船队
func (s *BasicService) AddFleetBoat(ctx context.Context, fleetID, boatID int) error {
	return s.db.WithContext(ctx).Table("tblFleetDetail").Create(&models.FleetDetail{
		FleetID: fleetID,
		BoatID:  boatID,
	}).Error
}

// RemoveFleetBoat 从船队移除船
func (s *BasicService) RemoveFleetBoat(ctx context.Context, fleetID, boatID int) error {
	return s.db.WithContext(ctx).Table("tblFleetDetail").
		Where("FleetID = ? AND BoatID = ?", fleetID, boatID).
		Update("is_del", 1).Error
}

// === SupplierStockPrice 供应商库存品价格 ===

func (s *BasicService) GetSupplierStockPrices(ctx context.Context, supplierID int) ([]models.SupplierStockPrice, error) {
	var list []models.SupplierStockPrice
	if err := s.db.WithContext(ctx).Table("tblSupplierStockPrice").
		Where("SupplierID = ? AND is_del = 0", supplierID).Find(&list).Error; err != nil {
		return nil, err
	}
	return list, nil
}

func (s *BasicService) CreateSupplierStockPrice(ctx context.Context, item *models.SupplierStockPrice) error {
	return s.db.WithContext(ctx).Table("tblSupplierStockPrice").Create(item).Error
}

func (s *BasicService) UpdateSupplierStockPrice(ctx context.Context, item *models.SupplierStockPrice) error {
	return s.db.WithContext(ctx).Table("tblSupplierStockPrice").
		Where("ID = ?", item.ID).
		Updates(map[string]interface{}{
			"SupplierID": item.SupplierID,
			"StockID":    item.StockID,
			"Price":      item.Price,
		}).Error
}

func (s *BasicService) DeleteSupplierStockPrice(ctx context.Context, id int) error {
	return s.db.WithContext(ctx).Table("tblSupplierStockPrice").
		Where("ID = ?", id).Update("is_del", 1).Error
}

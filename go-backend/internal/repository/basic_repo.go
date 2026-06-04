package repository

import (
	"context"
	"errors"
	"fmt"
	"reflect"
	"strings"

	"gorm.io/gorm"
)

// BasicRepository 基础数据表的通用仓储（Supplier/Port/Boat/Bin/Stock/Unit/Customer/...）
type BasicRepository struct {
	BaseRepository
}

// NewBasicRepository 构造
func NewBasicRepository(db *gorm.DB) *BasicRepository {
	return &BasicRepository{BaseRepository: BaseRepository{DB: db}}
}

// GetByID 取单条
func (r *BasicRepository) GetByID(ctx context.Context, table string, id int, dest interface{}) error {
	if err := r.DB.WithContext(ctx).Table(table).
		Where("is_del = ?", 0).Where(getPKCondition(table), id).First(dest).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return ErrNotFound
		}
		return err
	}
	return nil
}

// Create 新增
func (r *BasicRepository) Create(ctx context.Context, table string, data interface{}) error {
	return r.DB.WithContext(ctx).Table(table).Create(data).Error
}

// Update 更新（按主键）
func (r *BasicRepository) Update(ctx context.Context, table string, data map[string]interface{}, id int, pk string) error {
	if pk == "" {
		pk = getPrimaryKey(table)
	}
	return r.DB.WithContext(ctx).Table(table).
		Where(pk+" = ?", id).
		Updates(data).Error
}

// HardDelete 物理删除
func (r *BasicRepository) HardDelete(ctx context.Context, table string, id int, pk string) error {
	if pk == "" {
		pk = getPrimaryKey(table)
	}
	return r.DB.WithContext(ctx).Table(table).
		Where(pk+" = ?", id).
		Delete(map[string]interface{}{}).Error
}

// GetList 通用列表
func (r *BasicRepository) GetList(ctx context.Context, table string, dest interface{}) error {
	return r.DB.WithContext(ctx).Table(table).
		Where("is_del = ?", 0).
		Find(dest).Error
}

// FleetBoats 取某船队下的船
func (r *BasicRepository) FleetBoats(ctx context.Context, fleetID int) ([]map[string]interface{}, error) {
	type Row struct {
		BoatID   int
		BoatName string
		BoatNo   string
	}
	var rows []Row
	err := r.DB.WithContext(ctx).Table("tblFleetDetail").
		Select("tblBoat.BoatID, tblBoat.BoatName, tblBoat.BoatNo").
		Joins("LEFT JOIN tblBoat ON tblFleetDetail.BoatID = tblBoat.BoatID AND tblBoat.is_del = 0").
		Where("tblFleetDetail.FleetID = ? AND tblFleetDetail.is_del = 0", fleetID).
		Scan(&rows).Error

	if err != nil {
		return nil, err
	}
	out := make([]map[string]interface{}, 0, len(rows))
	for _, row := range rows {
		out = append(out, map[string]interface{}{
			"BoatID":   row.BoatID,
			"BoatName": row.BoatName,
			"BoatNo":   row.BoatNo,
		})
	}
	return out, nil
}

// BoatFleets 取某船所属的船队
func (r *BasicRepository) BoatFleets(ctx context.Context, boatID int) ([]map[string]interface{}, error) {
	type Row struct {
		FleetID   int
		FleetName string
	}
	var rows []Row
	err := r.DB.WithContext(ctx).Table("tblFleetDetail").
		Select("tblFleet.FleetID, tblFleet.FleetName").
		Joins("LEFT JOIN tblFleet ON tblFleetDetail.FleetID = tblFleet.FleetID AND tblFleet.is_del = 0").
		Where("tblFleetDetail.BoatID = ? AND tblFleetDetail.is_del = 0", boatID).
		Scan(&rows).Error

	if err != nil {
		return nil, err
	}
	out := make([]map[string]interface{}, 0, len(rows))
	for _, row := range rows {
		out = append(out, map[string]interface{}{
			"FleetID":   row.FleetID,
			"FleetName": row.FleetName,
		})
	}
	return out, nil
}

// LandingOptions 取 Landing 下拉选项（Supplier + Port + Boat）
func (r *BasicRepository) LandingOptions(ctx context.Context) (map[string]interface{}, error) {
	result := map[string]interface{}{}

	type Item struct {
		ID   int
		Name string
	}

	// Suppliers
	var suppliers []Item
	if err := r.DB.WithContext(ctx).Table("tblSuppliers").
		Select("SupplierID as ID, SupplierName as Name").
		Where("is_del = ?", 0).Scan(&suppliers).Error; err != nil {
		return nil, err
	}
	result["suppliers"] = suppliers

	// Ports
	var ports []Item
	if err := r.DB.WithContext(ctx).Table("tblPort").
		Select("PortID as ID, Port as Name").
		Where("is_del = ?", 0).Scan(&ports).Error; err != nil {
		return nil, err
	}
	result["ports"] = ports

	// Boats
	var boats []Item
	if err := r.DB.WithContext(ctx).Table("tblBoat").
		Select("BoatID as ID, BoatName as Name").
		Where("is_del = ?", 0).Scan(&boats).Error; err != nil {
		return nil, err
	}
	result["boats"] = boats

	// Stocks
	var stocks []Item
	if err := r.DB.WithContext(ctx).Table("tblStock").
		Select("StockID as ID, Stock as Name").
		Where("is_del = ?", 0).Scan(&stocks).Error; err != nil {
		return nil, err
	}
	result["stocks"] = stocks

	// Bins
	var bins []Item
	if err := r.DB.WithContext(ctx).Table("tblBin").
		Select("BinID as ID, BinName as Name").
		Where("is_del = ?", 0).Scan(&bins).Error; err != nil {
		return nil, err
	}
	result["bins"] = bins

	// Units
	var units []Item
	if err := r.DB.WithContext(ctx).Table("tblUnit").
		Select("UnitID as ID, UnitName as Name").
		Scan(&units).Error; err != nil {
		return nil, err
	}
	result["units"] = units

	return result, nil
}

// CustomerOptions 取客户下拉
func (r *BasicRepository) CustomerOptions(ctx context.Context) ([]map[string]interface{}, error) {
	type Row struct {
		CustID       int
		CustomerName string
	}
	var rows []Row
	err := r.DB.WithContext(ctx).Table("tblCustomer").
		Select("CustID, CustomerName").
		Where("is_del = ?", 0).Scan(&rows).Error

	if err != nil {
		return nil, err
	}
	out := make([]map[string]interface{}, 0, len(rows))
	for _, row := range rows {
		out = append(out, map[string]interface{}{
			"CustID":       row.CustID,
			"CustomerName": row.CustomerName,
		})
	}
	return out, nil
}

// getPKCondition 取主键 where 条件
func getPKCondition(table string) string {
	return getPrimaryKey(table) + " = ?"
}

// getPrimaryKey 根据表名推断主键
func getPrimaryKey(table string) string {
	switch table {
	case "tblLanding":
		return "LandingID"
	case "tblLandingDetail":
		return "ID"
	case "tblPurchase":
		return "PurchaseID"
	case "tblPurchaseDetail":
		return "ID"
	case "tblSales":
		return "SalesID"
	case "tblSalesDetail":
		return "ID"
	case "tblSuppliers":
		return "SupplierID"
	case "tblPort":
		return "PortID"
	case "tblBoat":
		return "BoatID"
	case "tblBin":
		return "BinID"
	case "tblStock":
		return "StockID"
	case "tblUnit":
		return "UnitID"
	case "tblCustomer":
		return "CustID"
	case "tblFleet":
		return "FleetID"
	case "tblFleetDetail":
		return "ID"
	case "tblSupplierStockPrice":
		return "ID"
	case "tblPrinter":
		return "ID"
	case "users":
		return "id"
	}
	return "id"
}

// GetPrimaryKey 公开主键获取
func GetPrimaryKey(table string) string {
	return getPrimaryKey(table)
}

// TrimLower 用于判断 table 名是否包含前缀
func TrimLower(s string) string {
	return strings.ToLower(s)
}

// 用于规避未使用 import 报错
var _ = reflect.TypeOf
var _ = fmt.Sprintf

package models

import "time"

// Base 通用基础字段
// 注意: 原 Laravel 表没有 created_at / updated_at，使用 is_del 做软删除
type Base struct {
	IsDel int `gorm:"column:is_del;default:0" json:"is_del"`
}

// Landing 到货主表 tblLanding
type Landing struct {
	LandingID   int       `gorm:"column:LandingID;primaryKey;autoIncrement" json:"LandingID"`
	LandingDate time.Time `gorm:"column:LandingDate" json:"LandingDate"`
	SupplierID  int       `gorm:"column:SupplierID" json:"SupplierID"`
	PortID      int       `gorm:"column:PortID" json:"PortID"`
	BoatID      int       `gorm:"column:BoatID" json:"BoatID"`
	Base

	// 关联字段（不存数据库）
	SupplierName string  `gorm:"-" json:"SupplierName,omitempty"`
	PortName     string  `gorm:"-" json:"PortName,omitempty"`
	BoatName     string  `gorm:"-" json:"BoatName,omitempty"`
	BoatNo       string  `gorm:"-" json:"BoatNo,omitempty"`
	DetailCount  int64   `gorm:"-" json:"detail_count,omitempty"`
	TotalWeight  float64 `gorm:"-" json:"total_weight,omitempty"`
}

// TableName 指定表名
func (Landing) TableName() string {
	return "tblLanding"
}

// LandingDetail 到货明细表 tblLandingDetail
type LandingDetail struct {
	ID           int     `gorm:"column:ID;primaryKey;autoIncrement" json:"ID"`
	LandingID    int     `gorm:"column:LandingID" json:"LandingID"`
	StockID      int     `gorm:"column:StockID" json:"StockID"`
	BinID        int     `gorm:"column:BinID" json:"BinID"`
	BinQty       int     `gorm:"column:BinQty" json:"BinQty"`
	LWeight      *float64 `gorm:"column:L-Weight" json:"L-Weight"`
	ICE          int     `gorm:"column:ICE" json:"ICE"`
	Price        float64 `gorm:"column:Price" json:"Price"`
	WeightUnitID int     `gorm:"column:WeightUnitID" json:"WeightUnitID"`
	Base

	// 关联字段
	Stock        string  `gorm:"-" json:"Stock,omitempty"`
	Description  string  `gorm:"-" json:"Description,omitempty"`
	State        string  `gorm:"-" json:"State,omitempty"`
	Area         string  `gorm:"-" json:"Area,omitempty"`
	BinName      string  `gorm:"-" json:"BinName,omitempty"`
	WeightUnit   string  `gorm:"-" json:"WeightUnit,omitempty"`
	WeightSymbol string  `gorm:"-" json:"WeightSymbol,omitempty"`
}

// TableName 指定表名
func (LandingDetail) TableName() string {
	return "tblLandingDetail"
}

// Purchase 采购主表 tblPurchase
type Purchase struct {
	PurchaseID   int       `gorm:"column:PurchaseID;primaryKey" json:"PurchaseID"`
	PurchaseDate time.Time `gorm:"column:PurchaseDate" json:"PurchaseDate"`
	SupplierID   int       `gorm:"column:SupplierID" json:"SupplierID"`
	LandingID    int       `gorm:"column:LandingID" json:"LandingID"`
	PortID       int       `gorm:"column:PortID" json:"PortID"`
	BoatID       int       `gorm:"column:BoatID" json:"BoatID"`
	Subtotal     float64   `gorm:"column:Subtotal" json:"Subtotal"`
	GST          float64   `gorm:"column:GST" json:"GST"`
	Total        float64   `gorm:"column:Total" json:"Total"`
	EmailSent    int       `gorm:"column:EmailSent;default:0" json:"EmailSent"`
	Base

	// 关联字段
	SupplierName string  `gorm:"-" json:"SupplierName,omitempty"`
	PortName     string  `gorm:"-" json:"PortName,omitempty"`
	BoatName     string  `gorm:"-" json:"BoatName,omitempty"`
	BoatNo       string  `gorm:"-" json:"BoatNo,omitempty"`
	DetailCount  int64   `gorm:"-" json:"detail_count,omitempty"`
	TotalGreenKG float64 `gorm:"-" json:"total_green_kg,omitempty"`
	TotalLandedKG float64 `gorm:"-" json:"total_landed_kg,omitempty"`
}

// TableName 指定表名
func (Purchase) TableName() string {
	return "tblPurchase"
}

// PurchaseDetail 采购明细表 tblPurchaseDetail
type PurchaseDetail struct {
	ID                 int     `gorm:"column:ID;primaryKey;autoIncrement" json:"ID"`
	PurchaseID         int     `gorm:"column:PurchaseID" json:"PurchaseID"`
	StockID            int     `gorm:"column:StockID" json:"StockID"`
	BinQty             *int    `gorm:"column:BinQty" json:"BinQty"`
	UnloadingDocket    *string `gorm:"column:UnloadingDocket" json:"UnloadingDocket"`
	ICE                int     `gorm:"column:ICE;default:0" json:"ICE"`
	GreenKG            float64 `gorm:"column:GreenKG" json:"GreenKG"`
	LandedKG           float64 `gorm:"column:LandedKG" json:"LandedKG"`
	LandedWeightUnitID int     `gorm:"column:LandedWeightUnitID" json:"LandedWeightUnitID"`
	GreenWeightUnitID  int     `gorm:"column:GreenWeightUnitID" json:"GreenWeightUnitID"`
	Price              float64 `gorm:"column:Price" json:"Price"`
	Total              float64 `gorm:"column:Total" json:"Total"`
	Base

	// 关联字段
	Stock       string `gorm:"-" json:"Stock,omitempty"`
	Description string `gorm:"-" json:"Description,omitempty"`
	State       string `gorm:"-" json:"State,omitempty"`
	Area        string `gorm:"-" json:"Area,omitempty"`
}

// TableName 指定表名
func (PurchaseDetail) TableName() string {
	return "tblPurchaseDetail"
}

// Sales 销售主表 tblSales
type Sales struct {
	SalesID    int       `gorm:"column:SalesID;primaryKey" json:"SalesID"`
	SaleDate   time.Time `gorm:"column:SaleDate" json:"SaleDate"`
	CustomerID int       `gorm:"column:CustomerID" json:"CustomerID"`
	PurchaseID int       `gorm:"column:PurchaseID" json:"PurchaseID"`
	Subtotal   float64   `gorm:"column:Subtotal" json:"Subtotal"`
	GST        float64   `gorm:"column:GST" json:"GST"`
	Total      float64   `gorm:"column:Total" json:"Total"`
	Base

	// 关联字段
	CustomerName string  `gorm:"-" json:"CustomerName,omitempty"`
	DetailCount  int64   `gorm:"-" json:"detail_count,omitempty"`
	TotalGWeight float64 `gorm:"-" json:"total_g_weight,omitempty"`
	TotalNWeight float64 `gorm:"-" json:"total_n_weight,omitempty"`
}

// TableName 指定表名
func (Sales) TableName() string {
	return "tblSales"
}

// SalesDetail 销售明细表 tblSalesDetail
type SalesDetail struct {
	ID          int     `gorm:"column:ID;primaryKey;autoIncrement" json:"ID"`
	SalesID     int     `gorm:"column:SalesID" json:"SalesID"`
	StockID     int     `gorm:"column:StockID" json:"StockID"`
	BinID       *int    `gorm:"column:BinID" json:"BinID"`
	BinQty      int     `gorm:"column:BinQty" json:"BinQty"`
	GWeight     float64 `gorm:"column:G-Weight" json:"G-Weight"`
	NWeight     float64 `gorm:"column:N-Weight" json:"N-Weight"`
	WeightUnitID int    `gorm:"column:WeightUnitID" json:"WeightUnitID"`
	Price       float64 `gorm:"column:Price" json:"Price"`
	Amount      float64 `gorm:"column:Amount" json:"Amount"`
	Base

	// 关联字段
	Stock        string `gorm:"-" json:"Stock,omitempty"`
	Description  string `gorm:"-" json:"Description,omitempty"`
	State        string `gorm:"-" json:"State,omitempty"`
	Area         string `gorm:"-" json:"Area,omitempty"`
	WeightUnit   string `gorm:"-" json:"WeightUnit,omitempty"`
	WeightSymbol string `gorm:"-" json:"WeightSymbol,omitempty"`
}

// TableName 指定表名
func (SalesDetail) TableName() string {
	return "tblSalesDetail"
}

// ============== 基础数据表 ==============

// Supplier 供应商表
type Supplier struct {
	SupplierID   int     `gorm:"column:SupplierID;primaryKey;autoIncrement" json:"SupplierID"`
	SupplierName string  `gorm:"column:SupplierName" json:"SupplierName"`
	GST          float64 `gorm:"column:GST" json:"GST"`
	Base
}

// TableName 指定表名
func (Supplier) TableName() string {
	return "tblSuppliers"
}

// Port 港口表
type Port struct {
	PortID int    `gorm:"column:PortID;primaryKey;autoIncrement" json:"PortID"`
	Port   string `gorm:"column:Port" json:"Port"`
	Base
}

// TableName 指定表名
func (Port) TableName() string {
	return "tblPort"
}

// Boat 船表
type Boat struct {
	BoatID   int    `gorm:"column:BoatID;primaryKey;autoIncrement" json:"BoatID"`
	BoatName string `gorm:"column:BoatName" json:"BoatName"`
	BoatNo   string `gorm:"column:BoatNo" json:"BoatNo"`
	Base
}

// TableName 指定表名
func (Boat) TableName() string {
	return "tblBoat"
}

// Bin 箱子表
type Bin struct {
	BinID    int     `gorm:"column:BinID;primaryKey;autoIncrement" json:"BinID"`
	BinName  string  `gorm:"column:BinName" json:"BinName"`
	BWeight  float64 `gorm:"column:B-Weight" json:"B-Weight"`
	Base
}

// TableName 指定表名
func (Bin) TableName() string {
	return "tblBin"
}

// Stock 库存品表
type Stock struct {
	StockID    int     `gorm:"column:StockID;primaryKey;autoIncrement" json:"StockID"`
	Stock      string  `gorm:"column:Stock" json:"Stock"`
	Description string `gorm:"column:Description" json:"Description"`
	State      string  `gorm:"column:State" json:"State"`
	Area       string  `gorm:"column:Area" json:"Area"`
	Conversion float64 `gorm:"column:Conversion;default:1" json:"Conversion"`
	Base
}

// TableName 指定表名
func (Stock) TableName() string {
	return "tblStock"
}

// Unit 计量单位表
type Unit struct {
	UnitID     int    `gorm:"column:UnitID;primaryKey;autoIncrement" json:"UnitID"`
	UnitName   string `gorm:"column:UnitName" json:"UnitName"`
	UnitSymbol string `gorm:"column:UnitSymbol" json:"UnitSymbol"`
	Base
}

// TableName 指定表名
func (Unit) TableName() string {
	return "tblUnit"
}

// Customer 客户表
type Customer struct {
	CustID       int    `gorm:"column:CustID;primaryKey;autoIncrement" json:"CustID"`
	CustomerName string `gorm:"column:CustomerName" json:"CustomerName"`
	Base
}

// TableName 指定表名
func (Customer) TableName() string {
	return "tblCustomer"
}

// Fleet 船队表
type Fleet struct {
	FleetID   int    `gorm:"column:FleetID;primaryKey;autoIncrement" json:"FleetID"`
	FleetName string `gorm:"column:FleetName" json:"FleetName"`
	Base
}

// TableName 指定表名
func (Fleet) TableName() string {
	return "tblFleet"
}

// FleetDetail 船队-船关系表
type FleetDetail struct {
	ID      int `gorm:"column:ID;primaryKey;autoIncrement" json:"ID"`
	FleetID int `gorm:"column:FleetID" json:"FleetID"`
	BoatID  int `gorm:"column:BoatID" json:"BoatID"`
	Base
}

// TableName 指定表名
func (FleetDetail) TableName() string {
	return "tblFleetDetail"
}

// SupplierStockPrice 供应商-库存品价格表
type SupplierStockPrice struct {
	ID         int     `gorm:"column:ID;primaryKey;autoIncrement" json:"ID"`
	SupplierID int     `gorm:"column:SupplierID" json:"SupplierID"`
	StockID    int     `gorm:"column:StockID" json:"StockID"`
	Price      float64 `gorm:"column:Price" json:"Price"`
	Base
}

// TableName 指定表名
func (SupplierStockPrice) TableName() string {
	return "tblSupplierStockPrice"
}

// Printer 打印机表
type Printer struct {
	ID        int    `gorm:"column:ID;primaryKey;autoIncrement" json:"ID"`
	Name      string `gorm:"column:Name" json:"Name"`
	IPAddress string `gorm:"column:IPAddress" json:"IPAddress"`
	Port      int    `gorm:"column:Port" json:"Port"`
	IsDefault int    `gorm:"column:IsDefault" json:"IsDefault"`
	Base
}

// TableName 指定表名
func (Printer) TableName() string {
	return "tblPrinter"
}

// User 用户表
type User struct {
	ID       int    `gorm:"column:id;primaryKey;autoIncrement" json:"id"`
	Username string `gorm:"column:username" json:"username"`
	Password string `gorm:"column:password" json:"-"`
	Base
}

// TableName 指定表名
func (User) TableName() string {
	return "users"
}

package repository

import (
	"context"
	"errors"

	"gorm.io/gorm"
)

// ErrNotFound 通用未找到错误
var ErrNotFound = errors.New("record not found")

// BaseRepository 仓储基类
type BaseRepository struct {
	DB *gorm.DB
}

// NotDeleted 未软删除条件
func NotDeleted() func(db *gorm.DB) *gorm.DB {
	return func(db *gorm.DB) *gorm.DB {
		return db.Where("is_del = ?", 0)
	}
}

// NotDeletedTable 指定表的未软删除
func NotDeletedTable(table string) func(db *gorm.DB) *gorm.DB {
	return func(db *gorm.DB) *gorm.DB {
		return db.Where(table+".is_del = ?", 0)
	}
}

// Ctx 注入 context 的链式
func Ctx(ctx context.Context) func(db *gorm.DB) *gorm.DB {
	return func(db *gorm.DB) *gorm.DB {
		return db.WithContext(ctx)
	}
}

package repository

import (
	"context"
	"errors"
	"fmt"

	"gorm.io/gorm"

	"table-editor-backend/internal/models"
)

// UserRepository 用户仓储
type UserRepository struct {
	BaseRepository
}

// NewUserRepository 构造
func NewUserRepository(db *gorm.DB) *UserRepository {
	return &UserRepository{BaseRepository: BaseRepository{DB: db}}
}

// FindByUsername 按用户名查找
func (r *UserRepository) FindByUsername(ctx context.Context, username string) (*models.User, error) {
	var u models.User
	err := r.DB.WithContext(ctx).Table("users").
		Where("username = ?", username).First(&u).Error
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	return &u, nil
}

// GetByID 按 ID 查找
func (r *UserRepository) GetByID(ctx context.Context, id int) (*models.User, error) {
	var u models.User
	err := r.DB.WithContext(ctx).Table("users").
		Where("id = ?", id).First(&u).Error
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	return &u, nil
}

// Create 创建
func (r *UserRepository) Create(ctx context.Context, u *models.User) error {
	return r.DB.WithContext(ctx).Table("users").Create(u).Error
}

// 用于避免未使用 import
var _ = fmt.Sprintf

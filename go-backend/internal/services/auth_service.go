package services

import (
	"context"
	"errors"
	"fmt"
	"time"

	"gorm.io/gorm"
	"golang.org/x/crypto/bcrypt"

	"table-editor-backend/internal/config"
	"table-editor-backend/internal/models"
	"table-editor-backend/internal/repository"
	"table-editor-backend/internal/utils"

	"github.com/golang-jwt/jwt/v5"
)

// AuthService 认证服务
type AuthService struct {
	userRepo *repository.UserRepository
	db       *gorm.DB
	cfg      *config.Config
}

// NewAuthService 构造
func NewAuthService(userRepo *repository.UserRepository, db *gorm.DB, cfg *config.Config) *AuthService {
	return &AuthService{userRepo: userRepo, db: db, cfg: cfg}
}

// Login 登录
func (s *AuthService) Login(ctx context.Context, username, password string) (map[string]interface{}, error) {
	user, err := s.userRepo.FindByUsername(ctx, username)
	if err != nil {
		if errors.Is(err, repository.ErrNotFound) {
			return nil, fmt.Errorf("用户名或密码错误")
		}
		return nil, err
	}

	// 验证密码（bcrypt）
	if err := bcrypt.CompareHashAndPassword([]byte(user.Password), []byte(password)); err != nil {
		return nil, fmt.Errorf("用户名或密码错误")
	}

	token, err := s.GenerateToken(user)
	if err != nil {
		return nil, err
	}
	return map[string]interface{}{
		"token":  token,
		"user":   user,
		"logged_in": true,
	}, nil
}

// Register 注册
func (s *AuthService) Register(ctx context.Context, username, password string) error {
	if username == "" || password == "" {
		return fmt.Errorf("用户名和密码不能为空")
	}
	// 查重
	if existing, _ := s.userRepo.FindByUsername(ctx, username); existing != nil {
		return fmt.Errorf("用户名已存在")
	}
	hashed, err := bcrypt.GenerateFromPassword([]byte(password), bcrypt.DefaultCost)
	if err != nil {
		return err
	}
	u := &models.User{
		Username: username,
		Password: string(hashed),
	}
	return s.userRepo.Create(ctx, u)
}

// Claims JWT 声明
type Claims struct {
	UserID   int    `json:"user_id"`
	Username string `json:"username"`
	jwt.RegisteredClaims
}

// GenerateToken 生成 token
func (s *AuthService) GenerateToken(user *models.User) (string, error) {
	claims := Claims{
		UserID:   user.ID,
		Username: user.Username,
		RegisteredClaims: jwt.RegisteredClaims{
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(s.cfg.JWTExpireDuration())),
			IssuedAt:  jwt.NewNumericDate(time.Now()),
			NotBefore: jwt.NewNumericDate(time.Now()),
			Issuer:    "table-editor-backend",
		},
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	return token.SignedString([]byte(s.cfg.JWTSecret))
}

// ParseToken 解析 token
func (s *AuthService) ParseToken(tokenString string) (*Claims, error) {
	token, err := jwt.ParseWithClaims(tokenString, &Claims{}, func(t *jwt.Token) (interface{}, error) {
		if _, ok := t.Method.(*jwt.SigningMethodHMAC); !ok {
			return nil, fmt.Errorf("unexpected signing method")
		}
		return []byte(s.cfg.JWTSecret), nil
	})
	if err != nil {
		return nil, err
	}
	claims, ok := token.Claims.(*Claims)
	if !ok || !token.Valid {
		return nil, fmt.Errorf("invalid token")
	}
	return claims, nil
}

// 用于规避未使用 import 报错
var _ = utils.Round

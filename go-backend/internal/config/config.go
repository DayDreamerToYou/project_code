package config

import (
	"fmt"
	"os"
	"strconv"
	"time"

	"github.com/joho/godotenv"
)

// Config 应用全局配置
type Config struct {
	AppEnv    string
	AppPort   string
	AppDebug  bool

	DBHost     string
	DBPort     string
	DBDatabase string
	DBUsername string
	DBPassword string

	JWTSecret      string
	JWTExpireHours int

	CORSAllowOrigins string
}

// Load 加载配置（优先从 .env 读取）
func Load() *Config {
	// 加载 .env 文件（如果存在）
	_ = godotenv.Load()

	cfg := &Config{
		AppEnv:           getEnv("APP_ENV", "local"),
		AppPort:          getEnv("APP_PORT", "8080"),
		AppDebug:         getEnvBool("APP_DEBUG", true),
		DBHost:           getEnv("DB_HOST", "127.0.0.1"),
		DBPort:           getEnv("DB_PORT", "3306"),
		DBDatabase:       getEnv("DB_DATABASE", "fishery"),
		DBUsername:       getEnv("DB_USERNAME", "root"),
		DBPassword:       getEnv("DB_PASSWORD", ""),
		JWTSecret:        getEnv("JWT_SECRET", "default-secret"),
		JWTExpireHours:   getEnvInt("JWT_EXPIRE_HOURS", 24),
		CORSAllowOrigins: getEnv("CORS_ALLOW_ORIGINS", "*"),
	}

	return cfg
}

// DSN 生成 GORM 使用的 DSN 字符串
func (c *Config) DSN() string {
	return fmt.Sprintf(
		"%s:%s@tcp(%s:%s)/%s?charset=utf8mb4&parseTime=True&loc=Local",
		c.DBUsername, c.DBPassword, c.DBHost, c.DBPort, c.DBDatabase,
	)
}

// JWTExpireDuration 返回 JWT 过期时间
func (c *Config) JWTExpireDuration() time.Duration {
	return time.Duration(c.JWTExpireHours) * time.Hour
}

func getEnv(key, defaultValue string) string {
	if v, ok := os.LookupEnv(key); ok && v != "" {
		return v
	}
	return defaultValue
}

func getEnvInt(key string, defaultValue int) int {
	if v, ok := os.LookupEnv(key); ok && v != "" {
		if n, err := strconv.Atoi(v); err == nil {
			return n
		}
	}
	return defaultValue
}

func getEnvBool(key string, defaultValue bool) bool {
	if v, ok := os.LookupEnv(key); ok && v != "" {
		if b, err := strconv.ParseBool(v); err == nil {
			return b
		}
	}
	return defaultValue
}

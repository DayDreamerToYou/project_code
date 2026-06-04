package utils

import "math"

// Round 保留 n 位小数
func Round(val float64, n int) float64 {
	mult := math.Pow10(n)
	return math.Round(val*mult) / mult
}

// Ptr 返回值的指针
func Ptr[T any](v T) *T {
	return &v
}

// Deref 安全解引用指针
func Deref[T any](p *T) T {
	if p == nil {
		var zero T
		return zero
	}
	return *p
}

// DerefFloat64 解引用 float64 指针
func DerefFloat64(p *float64) float64 {
	if p == nil {
		return 0
	}
	return *p
}

// DerefInt 解引用 int 指针
func DerefInt(p *int) int {
	if p == nil {
		return 0
	}
	return *p
}

// DerefString 解引用 string 指针
func DerefString(p *string) string {
	if p == nil {
		return ""
	}
	return *p
}

// F64 将任意数值类型转为 float64
func F64(v interface{}) float64 {
	switch x := v.(type) {
	case float64:
		return x
	case float32:
		return float64(x)
	case int:
		return float64(x)
	case int32:
		return float64(x)
	case int64:
		return float64(x)
	default:
		return 0
	}
}

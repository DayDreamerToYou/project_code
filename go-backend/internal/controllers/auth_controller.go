package controllers

import (
	"github.com/gin-gonic/gin"

	"table-editor-backend/internal/services"
	"table-editor-backend/internal/utils"
)

// AuthController 认证控制器
type AuthController struct {
	authSvc *services.AuthService
}

// NewAuthController 构造
func NewAuthController(svc *services.AuthService) *AuthController {
	return &AuthController{authSvc: svc}
}

// Login POST /auth/login
func (ctl *AuthController) Login(c *gin.Context) {
	var body struct {
		Username string `json:"username"`
		Password string `json:"password"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}
	if body.Username == "" || body.Password == "" {
		utils.BadRequest(c, "用户名和密码不能为空")
		return
	}
	result, err := ctl.authSvc.Login(c.Request.Context(), body.Username, body.Password)
	if err != nil {
		utils.Unauthorized(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, result, "登录成功")
}

// Register POST /auth/register
func (ctl *AuthController) Register(c *gin.Context) {
	var body struct {
		Username string `json:"username"`
		Password string `json:"password"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		utils.BadRequest(c, "参数错误: "+err.Error())
		return
	}
	if err := ctl.authSvc.Register(c.Request.Context(), body.Username, body.Password); err != nil {
		utils.BadRequest(c, err.Error())
		return
	}
	utils.SuccessWithMessage(c, nil, "注册成功")
}

// Me GET /auth/me
func (ctl *AuthController) Me(c *gin.Context) {
	username, _ := c.Get("username")
	userID, _ := c.Get("user_id")
	utils.Success(c, map[string]interface{}{
		"id":       userID,
		"username": username,
		"logged_in": true,
	})
}

// Logout POST /auth/logout
func (ctl *AuthController) Logout(c *gin.Context) {
	// JWT 无状态：客户端删除 token 即可
	utils.SuccessWithMessage(c, nil, "已退出登录")
}

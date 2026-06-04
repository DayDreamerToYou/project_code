package main

import (
	"log"
	"os"

	"github.com/gin-gonic/gin"

	"table-editor-backend/internal/config"
	"table-editor-backend/internal/controllers"
	"table-editor-backend/internal/database"
	"table-editor-backend/internal/repository"
	"table-editor-backend/internal/routes"
	"table-editor-backend/internal/services"
)

func main() {
	cfg := config.Load()
	if cfg.AppDebug {
		log.SetFlags(log.LstdFlags | log.Lshortfile)
	} else {
		gin.SetMode(gin.ReleaseMode)
	}

	// 初始化 DB
	db, err := database.Init(cfg)
	if err != nil {
		log.Fatalf("数据库初始化失败: %v", err)
	}

	// 依赖注入
	landingRepo := repository.NewLandingRepository(db)
	purchaseRepo := repository.NewPurchaseRepository(db)
	salesRepo := repository.NewSalesRepository(db)
	basicRepo := repository.NewBasicRepository(db)
	userRepo := repository.NewUserRepository(db)

	landingSvc := services.NewLandingService(landingRepo, purchaseRepo, salesRepo, basicRepo, db)
	purchaseSvc := services.NewPurchaseService(purchaseRepo, landingRepo, salesRepo, basicRepo, db)
	salesSvc := services.NewSalesService(salesRepo, purchaseRepo, basicRepo, db)
	basicSvc := services.NewBasicService(basicRepo, db)
	authSvc := services.NewAuthService(userRepo, db, cfg)

	authCtl := controllers.NewAuthController(authSvc)
	landingCtl := controllers.NewLandingController(landingSvc)
	purchaseCtl := controllers.NewPurchaseController(purchaseSvc)
	salesCtl := controllers.NewSalesController(salesSvc)
	basicCtl := controllers.NewBasicController(basicSvc)

	// 注册路由
	r := routes.Setup(authCtl, landingCtl, purchaseCtl, salesCtl, basicCtl, authSvc)

	addr := ":" + cfg.AppPort
	log.Printf("[Server] 监听 %s，模式 %s", addr, cfg.AppEnv)
	if err := r.Run(addr); err != nil {
		log.Fatalf("服务启动失败: %v", err)
		os.Exit(1)
	}
}

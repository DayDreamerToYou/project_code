package routes

import (
	"time"

	"github.com/gin-contrib/cors"
	"github.com/gin-gonic/gin"

	"table-editor-backend/internal/controllers"
	"table-editor-backend/internal/middleware"
	"table-editor-backend/internal/services"
)

// Setup 注册所有路由
func Setup(
	authCtl *controllers.AuthController,
	landingCtl *controllers.LandingController,
	purchaseCtl *controllers.PurchaseController,
	salesCtl *controllers.SalesController,
	basicCtl *controllers.BasicController,
	authSvc *services.AuthService,
) *gin.Engine {
	r := gin.New()
	r.Use(middleware.Recovery(), middleware.Logger(), middleware.CORS())

	// CORS 配置
	r.Use(cors.New(cors.Config{
		AllowOrigins:     []string{"*"},
		AllowMethods:     []string{"GET", "POST", "PUT", "DELETE", "OPTIONS", "PATCH"},
		AllowHeaders:     []string{"Origin", "Content-Type", "Authorization", "X-Requested-With", "Accept"},
		ExposeHeaders:    []string{"Content-Length", "Content-Type"},
		AllowCredentials: false,
		MaxAge:           12 * time.Hour,
	}))

	// 健康检查
	r.GET("/health", func(c *gin.Context) {
		c.JSON(200, gin.H{"status": "ok"})
	})

	// 公开 API（无需认证）
	public := r.Group("/api/v1")
	{
		pub := public.Group("/auth")
		{
			pub.POST("/login", authCtl.Login)
			pub.POST("/register", authCtl.Register)
		}
	}

	// 需认证 API
	auth := r.Group("/api/v1")
	auth.Use(middleware.JWTAuth(authSvc))
	{
		// 认证
		auth.GET("/auth/me", authCtl.Me)
		auth.POST("/auth/logout", authCtl.Logout)

		// 到货 Landing
		landings := auth.Group("/landings")
		{
			landings.GET("", landingCtl.List)
			landings.GET("/options", landingCtl.Options)
			landings.GET("/:id", landingCtl.Show)
			landings.POST("", landingCtl.Store)
			landings.PUT("/:id", landingCtl.Update)
			landings.DELETE("/:id", landingCtl.Destroy)
			landings.POST("/details", landingCtl.GetDetails)
			landings.POST("/update-lweight", landingCtl.UpdateLWeight)
			landings.POST("/generate-purchase", landingCtl.GeneratePurchase)
		}

		// 采购 Purchase
		purchases := auth.Group("/purchases")
		{
			purchases.GET("", purchaseCtl.List)
			purchases.GET("/:id", purchaseCtl.Show)
			purchases.DELETE("/:id", purchaseCtl.Destroy)
			purchases.POST("/generate-sales", purchaseCtl.GenerateSales)
		}

		// 销售 Sales
		sales := auth.Group("/sales")
		{
			sales.GET("", salesCtl.List)
			sales.GET("/:id", salesCtl.Show)
			sales.DELETE("/:id", salesCtl.Destroy)
		}

		// 供应商 Suppliers
		suppliers := auth.Group("/suppliers")
		{
			suppliers.GET("", basicCtl.ListSuppliers)
			suppliers.POST("", basicCtl.CreateSupplier)
			suppliers.GET("/:id", basicCtl.GetSupplier)
			suppliers.PUT("/:id", basicCtl.UpdateSupplier)
			suppliers.DELETE("/:id", basicCtl.DeleteSupplier)
		}

		// 供应商库存品价格
		ssp := auth.Group("/supplier-stock-prices")
		{
			ssp.GET("", basicCtl.GetSupplierStockPrices)
			ssp.POST("", basicCtl.CreateSupplierStockPrice)
			ssp.PUT("/:id", basicCtl.UpdateSupplierStockPrice)
			ssp.DELETE("/:id", basicCtl.DeleteSupplierStockPrice)
		}

		// 港口 Ports
		ports := auth.Group("/ports")
		{
			ports.GET("", basicCtl.ListPorts)
			ports.POST("", basicCtl.CreatePort)
			ports.GET("/:id", basicCtl.GetPort)
			ports.PUT("/:id", basicCtl.UpdatePort)
			ports.DELETE("/:id", basicCtl.DeletePort)
		}

		// 船 Boats
		boats := auth.Group("/boats")
		{
			boats.GET("", basicCtl.ListBoats)
			boats.POST("", basicCtl.CreateBoat)
			boats.GET("/:id", basicCtl.GetBoat)
			boats.PUT("/:id", basicCtl.UpdateBoat)
			boats.DELETE("/:id", basicCtl.DeleteBoat)
			boats.GET("/:id/fleets", basicCtl.BoatFleets)
		}

		// 箱子 Bins
		bins := auth.Group("/bins")
		{
			bins.GET("", basicCtl.ListBins)
			bins.POST("", basicCtl.CreateBin)
			bins.GET("/:id", basicCtl.GetBin)
			bins.PUT("/:id", basicCtl.UpdateBin)
			bins.DELETE("/:id", basicCtl.DeleteBin)
		}

		// 库存品 Stocks
		stocks := auth.Group("/stocks")
		{
			stocks.GET("", basicCtl.ListStocks)
			stocks.POST("", basicCtl.CreateStock)
			stocks.GET("/:id", basicCtl.GetStock)
			stocks.PUT("/:id", basicCtl.UpdateStock)
			stocks.DELETE("/:id", basicCtl.DeleteStock)
		}

		// 单位 Units
		units := auth.Group("/units")
		{
			units.GET("", basicCtl.ListUnits)
			units.POST("", basicCtl.CreateUnit)
			units.GET("/:id", basicCtl.GetUnit)
			units.PUT("/:id", basicCtl.UpdateUnit)
			units.DELETE("/:id", basicCtl.DeleteUnit)
		}

		// 客户 Customers
		customers := auth.Group("/customers")
		{
			customers.GET("", basicCtl.ListCustomers)
			customers.POST("", basicCtl.CreateCustomer)
			customers.GET("/:id", basicCtl.GetCustomer)
			customers.PUT("/:id", basicCtl.UpdateCustomer)
			customers.DELETE("/:id", basicCtl.DeleteCustomer)
		}

		// 船队 Fleets
		fleets := auth.Group("/fleets")
		{
			fleets.GET("", basicCtl.ListFleets)
			fleets.POST("", basicCtl.CreateFleet)
			fleets.GET("/:id", basicCtl.GetFleet)
			fleets.PUT("/:id", basicCtl.UpdateFleet)
			fleets.DELETE("/:id", basicCtl.DeleteFleet)
			fleets.GET("/:id/boats", basicCtl.FleetBoats)
			fleets.POST("/:id/boats", basicCtl.AddFleetBoat)
			fleets.DELETE("/:id/boats/:boatId", basicCtl.RemoveFleetBoat)
		}
	}

	return r
}

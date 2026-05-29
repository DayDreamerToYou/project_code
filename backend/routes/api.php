<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LandingController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\DataManagementController;
use App\Http\Controllers\Api\OptionsController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\PrinterController;
use App\Http\Controllers\Api\StatisticsController;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
});

Route::post('/login', [AuthController::class, 'login']);

// 需要登录的路由
Route::middleware('session.auth')->group(function () {

    // 认证
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // 到货记录
    Route::get('/landings', [LandingController::class, 'index']);
    Route::get('/landings/{id}', [LandingController::class, 'show']);
    Route::post('/landings', [LandingController::class, 'store']);
    Route::put('/landings/{id}', [LandingController::class, 'update']);
    Route::delete('/landings/{id}', [LandingController::class, 'destroy']);
    Route::post('/landings/details', [LandingController::class, 'getDetails']);
    Route::post('/landings/update-lweight', [LandingController::class, 'updateLWeight']);
    Route::post('/landings/generate-purchase', [LandingController::class, 'generatePurchase']);

    // 采购记录
    Route::get('/purchases', [PurchaseController::class, 'index']);
    Route::get('/purchases/{id}', [PurchaseController::class, 'show']);
    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::put('/purchases/{id}', [PurchaseController::class, 'update']);
    Route::delete('/purchases/{id}', [PurchaseController::class, 'destroy']);
    Route::post('/purchases/details', [PurchaseController::class, 'getDetails']);
    Route::post('/purchases/generate-sales', [PurchaseController::class, 'generateSales']);

    // 销售记录
    Route::get('/sales', [SalesController::class, 'index']);
    Route::get('/sales/{id}', [SalesController::class, 'show']);
    Route::post('/sales', [SalesController::class, 'store']);
    Route::put('/sales/{id}', [SalesController::class, 'update']);
    Route::delete('/sales/{id}', [SalesController::class, 'destroy']);
    Route::post('/sales/details', [SalesController::class, 'getDetails']);

    // 基础数据管理
    Route::get('/data/{table}', [DataManagementController::class, 'list']);
    Route::get('/data/{table}/{id}', [DataManagementController::class, 'get']);
    Route::post('/data/{table}', [DataManagementController::class, 'add']);
    Route::put('/data/{table}/{id}', [DataManagementController::class, 'update']);
    Route::delete('/data/{table}/{id}', [DataManagementController::class, 'delete']);
    Route::get('/data/fleet/{id}/boats', [DataManagementController::class, 'fleetBoats']);
    Route::get('/data/boat-management/{id}/fleets', [DataManagementController::class, 'boatFleets']);

    // 下拉选项
    Route::get('/options/landing', [OptionsController::class, 'landingOptions']);
    Route::get('/options/customers', [OptionsController::class, 'customerOptions']);

    // 统计
    Route::get('/statistics/monthly', [StatisticsController::class, 'monthly']);

    // 邮件发送
    Route::post('/email/send', [EmailController::class, 'send']);
    Route::post('/purchases/update-email-sent', [EmailController::class, 'updatePurchaseEmailSent']);

    // 打印机管理
    Route::get('/printers', [PrinterController::class, 'index']);
    Route::get('/printers/{id}', [PrinterController::class, 'show']);
    Route::post('/printers', [PrinterController::class, 'store']);
    Route::put('/printers/{id}', [PrinterController::class, 'update']);
    Route::delete('/printers/{id}', [PrinterController::class, 'destroy']);
    Route::post('/printers/set-default', [PrinterController::class, 'setDefault']);
    Route::post('/printers/test', [PrinterController::class, 'test']);
    Route::post('/printers/print-landing', [PrinterController::class, 'printLanding']);
    Route::post('/printers/print-purchase', [PrinterController::class, 'printPurchase']);
    Route::post('/printers/print-sales', [PrinterController::class, 'printSales']);
});

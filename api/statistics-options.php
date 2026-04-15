<?php
/**
 * ============================================
 * 统计筛选选项API接口
 * 说明：获取供应商列表和鱼种列表用于筛选
 * ============================================
 */

// 定义访问常量
define('APP_ACCESS', true);

// 引入数据库配置
require_once '../config/db.php';

// 禁用错误显示，只记录日志
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 启动Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 输出JSON响应并退出
 */
function jsonResponse($success, $message = '', $data = null) {
    $response = ['success' => $success];
    if ($message) {
        $response['message'] = $message;
    }
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

try {
    // 验证登录状态
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, '未登录或登录已过期');
    }

    // 获取数据库连接
    $conn = Database::getConnection();

    // 获取供应商列表（只获取未删除的）
    $supplierSql = "SELECT SupplierID, SupplierName
                    FROM tblSuppliers
                    WHERE is_del = 0
                    ORDER BY SupplierName";
    $supplierStmt = $conn->prepare($supplierSql);
    $supplierStmt->execute();
    $suppliers = $supplierStmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取鱼种列表（只获取未删除的，按Stock名称去重）
    $stockSql = "SELECT DISTINCT Stock as StockName
                 FROM tblStock
                 WHERE is_del = 0
                 ORDER BY Stock";
    $stockStmt = $conn->prepare($stockSql);
    $stockStmt->execute();
    $stocks = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取可用的月份列表（有采购数据的月份）
    $monthSql = "SELECT DISTINCT DATE_FORMAT(PurchaseDate, '%Y-%m') as month
                 FROM tblPurchase
                 WHERE is_del = 0
                 ORDER BY month DESC";
    $monthStmt = $conn->prepare($monthSql);
    $monthStmt->execute();
    $months = $monthStmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, '', [
        'suppliers' => $suppliers,
        'stocks' => $stocks,
        'months' => $months
    ]);

} catch (PDOException $e) {
    error_log("数据库错误 - 获取筛选选项失败: " . $e->getMessage());
    jsonResponse(false, '数据库查询失败');
} catch (Exception $e) {
    error_log("获取筛选选项失败: " . $e->getMessage());
    jsonResponse(false, '获取数据失败');
}
?>

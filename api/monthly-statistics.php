<?php
/**
 * ============================================
 * 月度统计API接口
 * 说明：按月统计每个供应商每种鱼类的采购数据
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

    // 获取请求参数
    $month = $_GET['month'] ?? date('Y-m');  // 默认当前月
    $supplierIds = $_GET['supplierIds'] ?? '';  // 供应商ID列表，逗号分隔
    $stockNames = $_GET['stockIds'] ?? '';  // 鱼种名称列表，逗号分隔（使用stockIds参数名保持兼容）
    $view = $_GET['view'] ?? 'table';  // 视图类型：table、chart

    // 验证月份格式 (YYYY-MM)
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        jsonResponse(false, '月份格式不正确，应为 YYYY-MM');
    }

    // 构建查询条件
    $whereConditions = [];
    $params = [];

    // 月份筛选
    $whereConditions[] = "DATE_FORMAT(p.PurchaseDate, '%Y-%m') = ?";
    $params[] = $month;

    // 软删除筛选
    $whereConditions[] = "p.is_del = 0";
    $whereConditions[] = "pd.is_del = 0";

    // 供应商筛选
    if (!empty($supplierIds)) {
        $supplierIdArray = explode(',', $supplierIds);
        $placeholders = str_repeat('?,', count($supplierIdArray) - 1) . '?';
        $whereConditions[] = "p.SupplierID IN ($placeholders)";
        $params = array_merge($params, $supplierIdArray);
    }

    // 鱼种筛选 - 使用 Stock 名称而不是 StockID
    if (!empty($stockNames)) {
        $stockNameArray = explode(',', $stockNames);
        $placeholders = str_repeat('?,', count($stockNameArray) - 1) . '?';
        $whereConditions[] = "st.Stock IN ($placeholders)";
        $params = array_merge($params, $stockNameArray);
    }

    $whereClause = implode(' AND ', $whereConditions);

    // 构建查询SQL - 每条记录独立显示（不分组，不合并）
    $sql = "SELECT
                pd.ID,
                s.SupplierID,
                s.SupplierName,
                s.QRN,
                st.Stock as StockName,
                st.Description,
                p.PurchaseDate,
                DATE_FORMAT(p.PurchaseDate, '%Y-%m') as month,
                pd.GreenKG,
                pd.Price,
                pd.Total,
                p.PurchaseID
            FROM tblPurchase p
            INNER JOIN tblSuppliers s ON p.SupplierID = s.SupplierID AND s.is_del = 0
            INNER JOIN tblPurchaseDetail pd ON p.PurchaseID = pd.PurchaseID AND pd.is_del = 0
            INNER JOIN tblStock st ON pd.StockID = st.StockID AND st.is_del = 0
            WHERE $whereClause
            ORDER BY s.SupplierName, pd.ID";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 计算总计数据
    $totalGreenWeight = 0;
    $totalAmount = 0;
    foreach ($statistics as $row) {
        $totalGreenWeight += floatval($row['GreenKG']);
        $totalAmount += floatval($row['Total']);
    }

    // 获取筛选选项数据
    $optionsSql = "SELECT
                    (SELECT COUNT(DISTINCT SupplierID) FROM tblSuppliers WHERE is_del = 0) as supplier_count,
                    (SELECT COUNT(DISTINCT StockID) FROM tblStock WHERE is_del = 0) as stock_count";
    $optionsStmt = $conn->query($optionsSql);
    $optionsInfo = $optionsStmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse(true, '', [
        'statistics' => $statistics,
        'summary' => [
            'total_green_weight' => round($totalGreenWeight, 2),
            'total_amount' => round($totalAmount, 2)
        ],
        'filters' => [
            'month' => $month,
            'supplierIds' => $supplierIds,
            'stockIds' => $stockIds,
            'view' => $view
        ],
        'options' => $optionsInfo
    ]);

} catch (PDOException $e) {
    error_log("统计查询失败: " . $e->getMessage());
    jsonResponse(false, '统计查询失败');
} catch (Exception $e) {
    error_log("统计错误: " . $e->getMessage());
    jsonResponse(false, '系统错误');
}
?>

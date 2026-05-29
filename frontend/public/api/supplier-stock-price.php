<?php
/**
 * ============================================
 * 供应商-鱼种定价管理 API 接口
 * 说明：处理供应商和鱼种之间的定价关系
 * ============================================
 */

// 定义访问常量
define('APP_ACCESS', true);

// 引入数据库配置
require_once '../config/db.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

// 启动Session
session_start();

try {
    // 获取数据库连接
    $conn = Database::getConnection();

    // 根据请求方法处理不同的操作
    switch ($method) {
        case 'GET':
            handleGet($conn);
            break;
        case 'POST':
            handlePost($conn);
            break;
        case 'PUT':
            handlePut($conn);
            break;
        case 'DELETE':
            handleDelete($conn);
            break;
        default:
            echo json_encode([
                'code' => 500,
                'msg' => 'Unsupported request method'
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'code' => 500,
        'msg' => 'Server error, please try again later'
    ]);
}

/**
 * 处理GET请求 - 查询数据
 */
function handleGet($conn) {
    $action = isset($_GET['action']) ? $_GET['action'] : 'list';

    switch ($action) {
        case 'list':
            handleList($conn);
            break;
        case 'detail':
            handleDetail($conn);
            break;
        case 'suppliers':
            getSuppliers($conn);
            break;
        case 'stock':
            getStock($conn);
            break;
        default:
            echo json_encode([
                'code' => 500,
                'msg' => 'Invalid action'
            ]);
            break;
    }
}

/**
 * 获取定价列表（带分页和搜索）
 */
function handleList($conn) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $offset = ($page - 1) * $limit;

    // 构建查询条件
    $where = ["p.is_del = 0"];
    $params = [];

    if (!empty($search)) {
        $where[] = "(s.SupplierName LIKE ? OR st.Stock LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = implode(' AND ', $where);

    // 查询总数
    $countSql = "SELECT COUNT(*) as total
                 FROM tblSupplierStockPrice p
                 INNER JOIN tblSuppliers s ON p.SupplierID COLLATE utf8mb4_unicode_ci = s.SupplierID
                 INNER JOIN tblStock st ON p.StockID COLLATE utf8mb4_unicode_ci = st.StockID
                 WHERE $whereClause";

    $stmt = $conn->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 查询数据
    $sql = "SELECT
                p.PriceID,
                p.SupplierID,
                p.StockID,
                p.UnitPrice,
                p.is_del,
                p.Remark,
                s.SupplierName,
                st.Stock,
                st.State,
                st.Area,
                p.CreateTime,
                p.UpdateTime
            FROM tblSupplierStockPrice p
            INNER JOIN tblSuppliers s ON p.SupplierID COLLATE utf8mb4_unicode_ci = s.SupplierID
            INNER JOIN tblStock st ON p.StockID COLLATE utf8mb4_unicode_ci = st.StockID
            WHERE $whereClause
            ORDER BY p.SupplierID, p.StockID
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'code' => 0,
        'msg' => '',
        'count' => $total,
        'data' => $data
    ]);
}

/**
 * 获取单条定价详情
 */
function handleDetail($conn) {
    $priceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($priceId <= 0) {
        echo json_encode([
            'code' => 500,
            'msg' => 'Invalid Price ID'
        ]);
        return;
    }

    $sql = "SELECT
                p.PriceID,
                p.SupplierID,
                p.StockID,
                p.UnitPrice,
                p.is_del,
                p.Remark,
                s.SupplierName,
                st.Stock,
                st.State,
                st.Area,
                p.CreateTime,
                p.UpdateTime
            FROM tblSupplierStockPrice p
            INNER JOIN tblSuppliers s ON p.SupplierID COLLATE utf8mb4_unicode_ci = s.SupplierID
            INNER JOIN tblStock st ON p.StockID COLLATE utf8mb4_unicode_ci = st.StockID
            WHERE p.PriceID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$priceId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode([
            'code' => 500,
            'msg' => 'Pricing record not found'
        ]);
        return;
    }

    echo json_encode([
        'code' => 0,
        'data' => $data
    ]);
}

/**
 * 获取供应商列表（用于下拉选择）
 */
function getSuppliers($conn) {
    $sql = "SELECT SupplierID, SupplierName
            FROM tblSuppliers
            WHERE is_del = 0
            ORDER BY SupplierName";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'code' => 0,
        'data' => $data
    ]);
}

/**
 * 获取鱼种列表（用于下拉选择）
 */
function getStock($conn) {
    $sql = "SELECT StockID, Stock, State, Area
            FROM tblStock
            WHERE is_del = 0
            ORDER BY Stock";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'code' => 0,
        'data' => $data
    ]);
}

/**
 * 处理POST请求 - 新增数据
 */
function handlePost($conn) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode([
            'code' => 500,
            'msg' => 'Invalid request data'
        ]);
        return;
    }

    // 验证必填字段
    if (empty($input['SupplierID']) || empty($input['StockID']) || !isset($input['UnitPrice'])) {
        echo json_encode([
            'code' => 500,
            'msg' => 'Supplier, Stock and Unit Price are required'
        ]);
        return;
    }

    // 检查是否已存在相同的生效定价
    $checkSql = "SELECT PriceID FROM tblSupplierStockPrice
                 WHERE SupplierID = ? AND StockID = ? AND is_del = 0";

    $stmt = $conn->prepare($checkSql);
    $stmt->execute([$input['SupplierID'], $input['StockID']]);

    if ($stmt->fetch()) {
        echo json_encode([
            'code' => 500,
            'msg' => 'Active pricing already exists for this supplier and stock. Please disable the existing pricing or edit it.'
        ]);
        return;
    }

    // 插入新记录
    $sql = "INSERT INTO tblSupplierStockPrice (
                SupplierID,
                StockID,
                UnitPrice,
                is_del,
                Remark
            ) VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $input['SupplierID'],
        $input['StockID'],
        $input['UnitPrice'],
        $input['is_del'] ?? 0,
        $input['Remark'] ?? ''
    ]);

    if ($result) {
        echo json_encode([
            'code' => 0,
            'msg' => 'Pricing added successfully',
            'PriceID' => $conn->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'code' => 500,
            'msg' => 'Failed to add pricing'
        ]);
    }
}

/**
 * 处理PUT请求 - 更新数据
 */
function handlePut($conn) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['PriceID'])) {
        echo json_encode([
            'code' => 500,
            'msg' => 'Invalid request data'
        ]);
        return;
    }

    // 检查是否与其他生效定价冲突
    $checkSql = "SELECT PriceID FROM tblSupplierStockPrice
                 WHERE SupplierID = ? AND StockID = ? AND is_del = 0 AND PriceID != ?";

    $stmt = $conn->prepare($checkSql);
    $stmt->execute([
        $input['SupplierID'],
        $input['StockID'],
        $input['PriceID']
    ]);

    if ($stmt->fetch()) {
        echo json_encode([
            'code' => 500,
            'msg' => 'Another active pricing already exists for this supplier and stock'
        ]);
        return;
    }

    // 更新记录
    $sql = "UPDATE tblSupplierStockPrice SET
                SupplierID = ?,
                StockID = ?,
                UnitPrice = ?,
                is_del = ?,
                Remark = ?
            WHERE PriceID = ?";

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $input['SupplierID'],
        $input['StockID'],
        $input['UnitPrice'],
        $input['is_del'] ?? 0,
        $input['Remark'] ?? '',
        $input['PriceID']
    ]);

    if ($result) {
        echo json_encode([
            'code' => 0,
            'msg' => 'Pricing updated successfully'
        ]);
    } else {
        echo json_encode([
            'code' => 500,
            'msg' => 'Failed to update pricing'
        ]);
    }
}

/**
 * 处理DELETE请求 - 删除数据（软删除：禁用定价）
 */
function handleDelete($conn) {
    $priceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($priceId <= 0) {
        echo json_encode([
            'code' => 500,
            'msg' => 'Invalid Price ID'
        ]);
        return;
    }

    // 软删除：设置is_del=1
    $sql = "UPDATE tblSupplierStockPrice SET is_del = 1 WHERE PriceID = ?";

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$priceId]);

    if ($result) {
        echo json_encode([
            'code' => 0,
            'msg' => 'Pricing deleted successfully'
        ]);
    } else {
        echo json_encode([
            'code' => 500,
            'msg' => 'Operation failed'
        ]);
    }
}
?>

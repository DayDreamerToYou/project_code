<?php
/**
 * ============================================
 * 销售记录API接口
 * 说明：处理销售记录(tblSales和tblSalesDetail)的增删改查操作
 * ============================================
 */

// 定义访问常量
define('APP_ACCESS', true);

// 引入数据库配置
require_once '../config/db.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 获取请求方法和路径
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
                'success' => false,
                'message' => '不支持的请求方法'
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("API错误: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '服务器错误，请稍后重试'
    ]);
}

/**
 * 处理GET请求 - 查询数据
 */
function handleGet($conn) {
    // 验证登录状态
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => '未登录或登录已过期',
            'logged_in' => false
        ]);
        return;
    }

    // 处理批量获取明细的请求
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'getDetails') {
        handleGetDetails($conn);
        return;
    }

    $salesId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $includeDetails = isset($_GET['includeDetails']) ? $_GET['includeDetails'] === 'true' : false;

    // 如果有ID，返回单条记录及其明细
    if ($salesId > 0) {
        // 查询主表
        $sql = "SELECT s.SalesID, s.SaleDate, s.CustomerID, s.Subtotal, s.GST, s.Total,
                       c.CustomerName, c.Address
                FROM tblSales s
                LEFT JOIN tblCustomer c ON s.CustomerID = c.CustID
                WHERE s.SalesID = ? AND s.is_del = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$salesId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // 查询明细表
            if ($includeDetails) {
                $detailSql = "SELECT sd.ID, sd.SalesID, sd.StockID, sd.BinQty,
                                     sd.`G-Weight`, sd.`N-Weight`, sd.Price, sd.Amount, sd.BinID,
                                     st.Stock, st.Description, st.State
                              FROM tblSalesDetail sd
                              LEFT JOIN tblStock st ON sd.StockID = st.StockID
                              WHERE sd.SalesID = ? AND sd.is_del = 0
                              ORDER BY sd.ID";
                $detailStmt = $conn->prepare($detailSql);
                $detailStmt->execute([$salesId]);
                $data['details'] = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $data['details'] = [];
            }

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '记录不存在'
            ]);
        }
        return;
    }

    // 分页查询列表
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? max(1, intval($_GET['pageSize'])) : 10;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $offset = ($page - 1) * $pageSize;
    
    // 获取排序参数
    $sortField = isset($_GET['field']) ? trim($_GET['field']) : 'SalesID';
    $sortOrder = isset($_GET['order']) ? trim($_GET['order']) : 'desc';

    // 构建查询条件
    $where = "s.is_del = 0";
    $params = [];

    if ($search) {
        $where .= " AND (c.CustomerName LIKE ? OR s.SaleDate LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam];
    }

    // 查询总数
    $countSql = "SELECT COUNT(*) as total
                 FROM tblSales s
                 LEFT JOIN tblCustomer c ON s.CustomerID = c.CustID
                 WHERE $where";
    $stmt = $conn->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 构建排序子句（防止 SQL 注入）
    $allowedSortFields = [
        'SalesID' => 's.SalesID',
        'SaleDate' => 's.SaleDate',
        'CustomerName' => 'c.CustomerName',
        'detail_count' => 'detail_count',
        'Subtotal' => 's.Subtotal',
        'GST' => 's.GST',
        'Total' => 's.Total'
    ];
    
    $orderByField = isset($allowedSortFields[$sortField]) ? $allowedSortFields[$sortField] : 's.SalesID';
    $orderByOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';

    // 查询数据
    $sql = "SELECT s.SalesID, s.SaleDate, s.CustomerID, s.Subtotal, s.GST, s.Total,
                   c.CustomerName, c.Address,
                   (SELECT COUNT(*) FROM tblSalesDetail sd WHERE sd.SalesID = s.SalesID AND sd.is_del = 0) as detail_count,
                   (SELECT SUM(`G-Weight`) FROM tblSalesDetail sd WHERE sd.SalesID = s.SalesID AND sd.is_del = 0) as total_g_weight,
                   (SELECT SUM(`N-Weight`) FROM tblSalesDetail sd WHERE sd.SalesID = s.SalesID AND sd.is_del = 0) as total_n_weight
            FROM tblSales s
            LEFT JOIN tblCustomer c ON s.CustomerID = c.CustID
            WHERE $where
            ORDER BY $orderByField $orderByOrder
            LIMIT ? OFFSET ?";
    $params[] = $pageSize;
    $params[] = $offset;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = ceil($total / $pageSize);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPages' => $totalPages
        ]
    ]);
}

/**
 * 处理POST请求 - 新增记录
 */
function handlePost($conn) {
    // 验证登录状态
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => '未登录或登录已过期'
        ]);
        return;
    }

    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);

    // 处理批量获取明细的请求
    if (isset($input['action']) && $input['action'] === 'getDetails') {
        handleGetDetails($conn);
        return;
    }

    // 验证必填字段
    if (empty($input['SaleDate']) || empty($input['CustomerID'])) {
        echo json_encode([
            'success' => false,
            'message' => '请填写所有必填字段(销售日期、客户)'
        ]);
        return;
    }

    // 验证明细数据
    if (empty($input['details']) || !is_array($input['details'])) {
        echo json_encode([
            'success' => false,
            'message' => '请至少添加一条明细记录'
        ]);
        return;
    }

    try {
        $conn->beginTransaction();

        // 插入主表
        $sql = "INSERT INTO tblSales (SaleDate, CustomerID, Subtotal, GST, Total)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $input['SaleDate'],
            $input['CustomerID'],
            $input['Subtotal'] ?? 0,
            $input['GST'] ?? 0,
            $input['Total'] ?? 0
        ]);

        // 获取插入的ID
        $salesId = $conn->lastInsertId();

        // 验证是否成功获取到ID
        if (empty($salesId)) {
            throw new Exception('无法获取新插入记录的ID');
        }

        // 插入明细表
        $detailSql = "INSERT INTO tblSalesDetail (SalesID, StockID, BinQty, `G-Weight`, `N-Weight`, WeightUnitID, Price, Amount, BinID)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $detailStmt = $conn->prepare($detailSql);

        $detailCount = 0;
        foreach ($input['details'] as $detail) {
            if (empty($detail['StockID'])) {
                continue;
            }
            $detailStmt->execute([
                $salesId,
                $detail['StockID'],
                $detail['BinQty'] ?? 0,
                $detail['G-Weight'] ?? 0,
                $detail['N-Weight'] ?? 0,
                $detail['WeightUnitID'] ?? 1,
                $detail['Price'] ?? 0,
                $detail['Amount'] ?? 0,
                $detail['BinID'] ?? null
            ]);
            $detailCount++;
        }

        if ($detailCount === 0) {
            throw new Exception('请至少添加一条明细记录');
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => '添加成功',
            'SalesID' => $salesId
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("新增销售记录失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '添加失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 处理PUT请求 - 更新记录
 */
function handlePut($conn) {
    // 验证登录状态
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => '未登录或登录已过期'
        ]);
        return;
    }

    // 获取PUT数据
    $input = json_decode(file_get_contents('php://input'), true);

    // 验证ID
    if (empty($input['SalesID'])) {
        echo json_encode([
            'success' => false,
            'message' => '记录ID不能为空'
        ]);
        return;
    }

    // 验证必填字段
    if (empty($input['SaleDate']) || empty($input['CustomerID'])) {
        echo json_encode([
            'success' => false,
            'message' => '请填写所有必填字段(销售日期、客户)'
        ]);
        return;
    }

    // 验证明细数据
    if (empty($input['details']) || !is_array($input['details'])) {
        echo json_encode([
            'success' => false,
            'message' => '请至少添加一条明细记录'
        ]);
        return;
    }

    try {
        $conn->beginTransaction();

        // 更新主表
        $sql = "UPDATE tblSales SET SaleDate = ?, CustomerID = ?, Subtotal = ?, GST = ?, Total = ?
                WHERE SalesID = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $input['SaleDate'],
            $input['CustomerID'],
            $input['Subtotal'] ?? 0,
            $input['GST'] ?? 0,
            $input['Total'] ?? 0,
            $input['SalesID']
        ]);

        // 软删除原有明细
        $deleteDetailSql = "UPDATE tblSalesDetail SET is_del = 1 WHERE SalesID = ?";
        $deleteDetailStmt = $conn->prepare($deleteDetailSql);
        $deleteDetailStmt->execute([$input['SalesID']]);

        // 插入新的明细
        $detailSql = "INSERT INTO tblSalesDetail (SalesID, StockID, BinQty, `G-Weight`, `N-Weight`, WeightUnitID, Price, Amount, BinID)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $detailStmt = $conn->prepare($detailSql);

        foreach ($input['details'] as $detail) {
            if (empty($detail['StockID'])) {
                continue;
            }
            $detailStmt->execute([
                $input['SalesID'],
                $detail['StockID'],
                $detail['BinQty'] ?? 0,
                $detail['G-Weight'] ?? 0,
                $detail['N-Weight'] ?? 0,
                $detail['WeightUnitID'] ?? 1,
                $detail['Price'] ?? 0,
                $detail['Amount'] ?? 0,
                $detail['BinID'] ?? null
            ]);
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => '更新成功'
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("更新销售记录失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '更新失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 处理DELETE请求 - 删除记录（物理删除）
 */
function handleDelete($conn) {
    // 验证登录状态
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => '未登录或登录已过期'
        ]);
        return;
    }

    // 获取DELETE数据
    $input = json_decode(file_get_contents('php://input'), true);

    // 验证ID
    if (empty($input['SalesID'])) {
        echo json_encode([
            'success' => false,
            'message' => '记录ID不能为空'
        ]);
        return;
    }

    try {
        $conn->beginTransaction();

        // 物理删除明细表
        $detailSql = "DELETE FROM tblSalesDetail WHERE SalesID = ?";
        $detailStmt = $conn->prepare($detailSql);
        $detailStmt->execute([$input['SalesID']]);

        // 物理删除主表
        $sql = "DELETE FROM tblSales WHERE SalesID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$input['SalesID']]);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => '删除成功'
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("删除销售记录失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '删除失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 批量获取销售明细
 */
function handleGetDetails($conn) {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['salesIds']) || !is_array($input['salesIds'])) {
        echo json_encode([
            'success' => false,
            'message' => '参数错误：缺少salesIds'
        ]);
        return;
    }

    $salesIds = $input['salesIds'];

    // 过滤并验证ID
    $salesIds = array_filter($salesIds, function($id) {
        return is_numeric($id) && intval($id) > 0;
    });

    if (empty($salesIds)) {
        echo json_encode([
            'success' => false,
            'message' => '没有有效的销售ID'
        ]);
        return;
    }

    // 转换为整数
    $salesIds = array_map('intval', $salesIds);

    // 构建IN子句的占位符
    $placeholders = str_repeat('?,', count($salesIds) - 1) . '?';

    // 查询销售明细
    $sql = "SELECT sd.SalesID, sd.ID, sd.StockID, sd.BinQty, sd.`G-Weight`, sd.`N-Weight`, sd.WeightUnitID,
                   sd.Price, sd.Amount, sd.BinID,
                   st.Stock, st.Description, st.State,
                   u.UnitSymbol as WeightUnitSymbol
            FROM tblSalesDetail sd
            LEFT JOIN tblStock st ON sd.StockID = st.StockID
            LEFT JOIN tblUnits u ON sd.WeightUnitID = u.UnitID
            WHERE sd.SalesID IN ($placeholders)
            AND sd.is_del = 0
            ORDER BY sd.SalesID, sd.ID";

    $stmt = $conn->prepare($sql);
    $stmt->execute($salesIds);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ]);
}
?>

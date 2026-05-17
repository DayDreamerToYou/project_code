<?php
/**
 * ============================================
 * 采购记录API接口
 * 说明：处理采购记录(tblPurchase和tblPurchaseDetail)的增删改查操作
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

    $purchaseId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $includeDetails = isset($_GET['includeDetails']) ? $_GET['includeDetails'] === 'true' : false;

    // 如果有ID，返回单条记录及其明细
    if ($purchaseId > 0) {
        // 查询主表
        $sql = "SELECT p.PurchaseID, p.PurchaseDate, p.SupplierID, p.Subtotal, p.GST, p.Total, p.EmailSent,
                       s.SupplierName, s.Email
                FROM tblPurchase p
                LEFT JOIN tblSuppliers s ON p.SupplierID = s.SupplierID
                WHERE p.PurchaseID = ? AND p.is_del = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$purchaseId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // 查询明细表
            if ($includeDetails) {
                $detailSql = "SELECT pd.ID, pd.PurchaseID, pd.StockID, p.LandingID, pd.BinQty, pd.UnloadingDocket,
                                     pd.ICE, pd.GreenKG, pd.LandedKG, pd.LandedWeightUnitID, pd.GreenWeightUnitID, pd.Price, pd.Total,
                                     st.Stock, st.Description, st.State, st.Area
                              FROM tblPurchaseDetail pd
                              LEFT JOIN tblPurchase p ON pd.PurchaseID = p.PurchaseID
                              LEFT JOIN tblStock st ON pd.StockID = st.StockID
                              WHERE pd.PurchaseID = ? AND pd.is_del = 0
                              ORDER BY pd.ID";
                $detailStmt = $conn->prepare($detailSql);
                $detailStmt->execute([$purchaseId]);
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
    $sortField = isset($_GET['field']) ? trim($_GET['field']) : 'PurchaseID';
    $sortOrder = isset($_GET['order']) ? trim($_GET['order']) : 'desc';

    // 构建查询条件
    $where = "p.is_del = 0";
    $params = [];

    if ($search) {
        $where .= " AND (s.SupplierName LIKE ? OR p.PurchaseDate LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam];
    }

    // 查询总数
    $countSql = "SELECT COUNT(*) as total
                 FROM tblPurchase p
                 LEFT JOIN tblSuppliers s ON p.SupplierID = s.SupplierID
                 WHERE $where";
    $stmt = $conn->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 构建排序子句（防止 SQL 注入）
    $allowedSortFields = [
        'PurchaseID' => 'p.PurchaseID',
        'PurchaseDate' => 'p.PurchaseDate',
        'SupplierName' => 's.SupplierName',
        'BoatName' => 'b.BoatName',
        'BoatNo' => 'b.BoatNo',
        'detail_count' => 'detail_count',
        'Subtotal' => 'p.Subtotal',
        'GST' => 'p.GST',
        'Total' => 'p.Total'
    ];
    
    $orderByField = isset($allowedSortFields[$sortField]) ? $allowedSortFields[$sortField] : 'p.PurchaseID';
    $orderByOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';

    // 查询数据
    $sql = "SELECT p.PurchaseID, p.PurchaseDate, p.SupplierID, p.PortID, p.BoatID, p.Subtotal, p.GST, p.Total, p.EmailSent,
                   s.SupplierName, s.Email, s.GST as SupplierGST,
                   b.BoatName, b.BoatNo,
                   pt.Port,
                   (SELECT COUNT(*) FROM tblPurchaseDetail pd WHERE pd.PurchaseID = p.PurchaseID AND pd.is_del = 0) as detail_count,
                   (SELECT SUM(GreenKG) FROM tblPurchaseDetail pd WHERE pd.PurchaseID = p.PurchaseID AND pd.is_del = 0) as total_green_kg,
                   (SELECT SUM(LandedKG) FROM tblPurchaseDetail pd WHERE pd.PurchaseID = p.PurchaseID AND pd.is_del = 0) as total_landed_kg
            FROM tblPurchase p
            LEFT JOIN tblSuppliers s ON p.SupplierID = s.SupplierID
            LEFT JOIN tblBoat b ON p.BoatID = b.BoatID
            LEFT JOIN tblPort pt ON p.PortID = pt.PortID
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

    // 处理更新邮件发送状态的请求
    if (isset($input['action']) && $input['action'] === 'updateEmailSent') {
        handleUpdateEmailSent($conn);
        return;
    }

    // 验证必填字段
    if (empty($input['PurchaseDate']) || empty($input['SupplierID'])) {
        echo json_encode([
            'success' => false,
            'message' => '请填写所有必填字段(采购日期、供应商)'
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
        $sql = "INSERT INTO tblPurchase (PurchaseDate, SupplierID, PortID, BoatID, Subtotal, GST, Total)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $input['PurchaseDate'],
            $input['SupplierID'],
            $input['PortID'] ?? null,
            $input['BoatID'] ?? null,
            $input['Subtotal'] ?? 0,
            $input['GST'] ?? 0,
            $input['Total'] ?? 0
        ]);

        // 获取插入的ID
        $purchaseId = $conn->lastInsertId();

        // 验证是否成功获取到ID
        if (empty($purchaseId)) {
            throw new Exception('无法获取新插入记录的ID');
        }

        // 插入明细表
        $detailSql = "INSERT INTO tblPurchaseDetail (PurchaseID, StockID, BinQty, UnloadingDocket, ICE, GreenKG, LandedKG, LandedWeightUnitID, GreenWeightUnitID, Price, Total)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $detailStmt = $conn->prepare($detailSql);

        $detailCount = 0;
        foreach ($input['details'] as $detail) {
            if (empty($detail['StockID'])) {
                continue;
            }
            $binQty = isset($detail['BinQty']) ? intval($detail['BinQty']) : null;
            $unloadingDocket = isset($detail['UnloadingDocket']) ? intval($detail['UnloadingDocket']) : null;
            // 单位ID，默认为1 (KG)
            $landedWeightUnitId = isset($detail['LandedWeightUnitID']) ? intval($detail['LandedWeightUnitID']) : 1;
            $greenWeightUnitId = isset($detail['GreenWeightUnitID']) ? intval($detail['GreenWeightUnitID']) : 1;
            $detailStmt->execute([
                $purchaseId,
                $detail['StockID'],
                $binQty,
                $unloadingDocket,
                $detail['ICE'] ?? 0,
                $detail['GreenKG'] ?? 0,
                $detail['LandedKG'] ?? 0,
                $landedWeightUnitId,
                $greenWeightUnitId,
                $detail['Price'] ?? 0,
                $detail['Total'] ?? 0
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
            'PurchaseID' => $purchaseId
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("新增采购记录失败: " . $e->getMessage());
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
    if (empty($input['PurchaseID'])) {
        echo json_encode([
            'success' => false,
            'message' => '记录ID不能为空'
        ]);
        return;
    }

    // 验证必填字段
    if (empty($input['PurchaseDate']) || empty($input['SupplierID'])) {
        echo json_encode([
            'success' => false,
            'message' => '请填写所有必填字段(采购日期、供应商)'
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
        $sql = "UPDATE tblPurchase SET PurchaseDate = ?, SupplierID = ?, PortID = ?, BoatID = ?, Subtotal = ?, GST = ?, Total = ?
                WHERE PurchaseID = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $input['PurchaseDate'],
            $input['SupplierID'],
            $input['PortID'] ?? null,
            $input['BoatID'] ?? null,
            $input['Subtotal'] ?? 0,
            $input['GST'] ?? 0,
            $input['Total'] ?? 0,
            $input['PurchaseID']
        ]);

        // 软删除原有明细
        $deleteDetailSql = "UPDATE tblPurchaseDetail SET is_del = 1 WHERE PurchaseID = ?";
        $deleteDetailStmt = $conn->prepare($deleteDetailSql);
        $deleteDetailStmt->execute([$input['PurchaseID']]);

        // 插入新的明细
        $detailSql = "INSERT INTO tblPurchaseDetail (PurchaseID, StockID, BinQty, UnloadingDocket, ICE, GreenKG, LandedKG, LandedWeightUnitID, GreenWeightUnitID, Price, Total)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $detailStmt = $conn->prepare($detailSql);

        foreach ($input['details'] as $detail) {
            if (empty($detail['StockID'])) {
                continue;
            }
            $binQty = isset($detail['BinQty']) ? intval($detail['BinQty']) : null;
            $unloadingDocket = isset($detail['UnloadingDocket']) ? intval($detail['UnloadingDocket']) : null;
            // 单位ID，默认为1 (KG)
            $landedWeightUnitId = isset($detail['LandedWeightUnitID']) ? intval($detail['LandedWeightUnitID']) : 1;
            $greenWeightUnitId = isset($detail['GreenWeightUnitID']) ? intval($detail['GreenWeightUnitID']) : 1;
            $detailStmt->execute([
                $input['PurchaseID'],
                $detail['StockID'],
                $binQty,
                $unloadingDocket,
                $detail['ICE'] ?? 0,
                $detail['GreenKG'] ?? 0,
                $detail['LandedKG'] ?? 0,
                $landedWeightUnitId,
                $greenWeightUnitId,
                $detail['Price'] ?? 0,
                $detail['Total'] ?? 0
            ]);
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => '更新成功'
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("更新采购记录失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '更新失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 处理DELETE请求 - 删除记录（软删除，级联删除关联的销售记录）
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
    if (empty($input['PurchaseID'])) {
        echo json_encode([
            'success' => false,
            'message' => '记录ID不能为空'
        ]);
        return;
    }

    try {
        $conn->beginTransaction();

        $purchaseId = $input['PurchaseID'];

        // 1. 查找关联的销售记录
        $salesQuery = "SELECT SalesID FROM tblSales WHERE PurchaseID = ? AND is_del = 0";
        $salesStmt = $conn->prepare($salesQuery);
        $salesStmt->execute([$purchaseId]);
        $salesIds = $salesStmt->fetchAll(PDO::FETCH_COLUMN);

        // 2. 级联软删除销售记录及其明细
        if (!empty($salesIds)) {
            $salesIdList = implode(',', array_fill(0, count($salesIds), '?'));

            // 软删除销售明细
            $salesDetailSql = "UPDATE tblSalesDetail SET is_del = 1 WHERE SalesID IN ($salesIdList)";
            $salesDetailStmt = $conn->prepare($salesDetailSql);
            $salesDetailStmt->execute($salesIds);

            // 软删除销售记录
            $salesSql = "UPDATE tblSales SET is_del = 1 WHERE SalesID IN ($salesIdList)";
            $salesStmt = $conn->prepare($salesSql);
            $salesStmt->execute($salesIds);
        }

        // 3. 软删除采购明细
        $purchaseDetailSql = "UPDATE tblPurchaseDetail SET is_del = 1 WHERE PurchaseID = ?";
        $purchaseDetailStmt = $conn->prepare($purchaseDetailSql);
        $purchaseDetailStmt->execute([$purchaseId]);

        // 4. 软删除采购主表
        $purchaseSql = "UPDATE tblPurchase SET is_del = 1 WHERE PurchaseID = ?";
        $purchaseStmt = $conn->prepare($purchaseSql);
        $purchaseStmt->execute([$purchaseId]);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => '删除成功（已级联删除关联的销售记录）'
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("删除采购记录失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '删除失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 批量获取采购明细
 */
function handleGetDetails($conn) {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['purchaseIds']) || !is_array($input['purchaseIds'])) {
        echo json_encode([
            'success' => false,
            'message' => '参数错误：缺少purchaseIds'
        ]);
        return;
    }

    $purchaseIds = $input['purchaseIds'];

    // 过滤并验证ID
    $purchaseIds = array_filter($purchaseIds, function($id) {
        return is_numeric($id) && intval($id) > 0;
    });

    if (empty($purchaseIds)) {
        echo json_encode([
            'success' => false,
            'message' => '没有有效的采购ID'
        ]);
        return;
    }

    // 转换为整数
    $purchaseIds = array_map('intval', $purchaseIds);

    // 构建IN子句的占位符
    $placeholders = str_repeat('?,', count($purchaseIds) - 1) . '?';

    // 查询采购明细
    $sql = "SELECT pd.PurchaseID, pd.ID, pd.StockID, p.LandingID, pd.BinQty, pd.UnloadingDocket,
                   pd.ICE, pd.GreenKG, pd.LandedKG, pd.LandedWeightUnitID, pd.GreenWeightUnitID, pd.Price, pd.Total,
                   st.Stock, st.Description, st.State, st.Area
            FROM tblPurchaseDetail pd
            LEFT JOIN tblPurchase p ON pd.PurchaseID = p.PurchaseID
            LEFT JOIN tblStock st ON pd.StockID = st.StockID
            WHERE pd.PurchaseID IN ($placeholders)
            AND pd.is_del = 0
            ORDER BY pd.PurchaseID, pd.ID";

    $stmt = $conn->prepare($sql);
    $stmt->execute($purchaseIds);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ]);
}

/**
 * 处理更新邮件发送状态的请求
 */
function handleUpdateEmailSent($conn) {
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

    // 验证必填字段
    if (empty($input['PurchaseID'])) {
        echo json_encode([
            'success' => false,
            'message' => '缺少采购ID'
        ]);
        return;
    }

    $purchaseId = intval($input['PurchaseID']);
    $emailSent = isset($input['EmailSent']) ? intval($input['EmailSent']) : 1;

    try {
        // 更新邮件发送状态
        $sql = "UPDATE tblPurchase SET EmailSent = ? WHERE PurchaseID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$emailSent, $purchaseId]);

        echo json_encode([
            'success' => true,
            'message' => '邮件发送状态更新成功'
        ]);
    } catch (PDOException $e) {
        error_log("更新邮件发送状态失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '更新失败: ' . $e->getMessage()
        ]);
    }
}
?>

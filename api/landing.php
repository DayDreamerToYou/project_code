<?php
/**
 * ============================================
 * 到货记录API接口
 * 说明：处理到货记录(tblLanding和tblLandingDetail)的增删改查操作
 * ============================================
 */

// 定义访问常量（防止重复定义）
if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}

// 引入数据库配置（使用绝对路径）
require_once dirname(__DIR__) . '/config/db.php';

// 引入生成 Purchase 的函数
require_once __DIR__ . '/landing-to-purchase.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 获取请求方法和路径
$method = $_SERVER['REQUEST_METHOD'];

// 启动Session并配置cookie持久化
session_set_cookie_params([
    'lifetime' => 86400 * 7,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
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
        'message' => '服务器错误，请稍后重试',
        'error' => $e->getMessage()
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

    $landingId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $includeDetails = isset($_GET['includeDetails']) ? $_GET['includeDetails'] === 'true' : false;

    // 如果有ID，返回单条记录及其明细
    if ($landingId > 0) {
        // 查询主表
        $sql = "SELECT l.LandingID, l.LandingDate, l.SupplierID, l.PortID, l.BoatID,
                       s.SupplierName, s.QRN, s.Email, p.Port, b.BoatName, b.BoatNo
                FROM tblLanding l
                LEFT JOIN tblSuppliers s ON l.SupplierID = s.SupplierID
                LEFT JOIN tblPort p ON l.PortID = p.PortID
                LEFT JOIN tblBoat b ON l.BoatID = b.BoatID
                WHERE l.LandingID = ? AND l.is_del = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$landingId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // 查询明细表
            if ($includeDetails) {
                $detailSql = "SELECT ld.ID, ld.LandingID, ld.StockID, ld.BinID, ld.BinQty, ld.`L-Weight`, ld.ICE, ld.Price, ld.WeightUnitID,
                                     u.UnitSymbol, u.UnitCode,
                                     st.Stock, st.Description, st.State, st.Area, b.BinName
                              FROM tblLandingDetail ld
                              LEFT JOIN tblUnits u ON ld.WeightUnitID = u.UnitID
                              LEFT JOIN tblStock st ON ld.StockID = st.StockID
                              LEFT JOIN tblBin b ON ld.BinID = b.BinID
                              WHERE ld.LandingID = ? AND ld.is_del = 0
                              ORDER BY ld.ID";
                $detailStmt = $conn->prepare($detailSql);
                $detailStmt->execute([$landingId]);
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
    $sortField = isset($_GET['field']) ? trim($_GET['field']) : 'LandingID';
    $sortOrder = isset($_GET['order']) ? trim($_GET['order']) : 'desc';

    // 构建查询条件
    $where = "l.is_del = 0";
    $params = [];

    if ($search) {
        $where .= " AND (s.SupplierName LIKE ? OR p.Port LIKE ? OR l.LandingDate LIKE ? OR b.BoatName LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }

    // 查询总数
    $countSql = "SELECT COUNT(*) as total
                 FROM tblLanding l
                 LEFT JOIN tblSuppliers s ON l.SupplierID = s.SupplierID
                 LEFT JOIN tblPort p ON l.PortID = p.PortID
                 LEFT JOIN tblBoat b ON l.BoatID = b.BoatID
                 WHERE $where";
    $stmt = $conn->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 构建排序子句（防止 SQL 注入）
    $allowedSortFields = [
        'LandingID' => 'l.LandingID',
        'LandingDate' => 'l.LandingDate',
        'SupplierName' => 's.SupplierName',
        'Port' => 'p.Port',
        'BoatNo' => 'b.BoatNo',
        'BoatName' => 'b.BoatName',
        'detail_count' => 'detail_count'
    ];
    
    $orderByField = isset($allowedSortFields[$sortField]) ? $allowedSortFields[$sortField] : 'l.LandingID';
    $orderByOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';

    // 查询数据
    $sql = "SELECT l.LandingID, l.LandingDate, l.SupplierID, l.PortID, l.BoatID,
                   s.SupplierName, s.QRN, s.Email, p.Port, b.BoatName, b.BoatNo,
                   (SELECT COUNT(*) FROM tblLandingDetail ld WHERE ld.LandingID = l.LandingID AND ld.is_del = 0) as detail_count,
                   (SELECT SUM(`L-Weight`) FROM tblLandingDetail ld WHERE ld.LandingID = l.LandingID AND ld.is_del = 0) as total_weight,
                   (SELECT GROUP_CONCAT(DISTINCT u.UnitSymbol ORDER BY u.UnitSymbol SEPARATOR ', ')
                    FROM tblLandingDetail ld
                    LEFT JOIN tblUnits u ON ld.WeightUnitID = u.UnitID
                    WHERE ld.LandingID = l.LandingID AND ld.is_del = 0) as units
            FROM tblLanding l
            LEFT JOIN tblSuppliers s ON l.SupplierID = s.SupplierID
            LEFT JOIN tblPort p ON l.PortID = p.PortID
            LEFT JOIN tblBoat b ON l.BoatID = b.BoatID
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
 * 处理POST请求 - 新增记录或获取明细
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

    // 处理获取明细的请求
    if (isset($input['action']) && $input['action'] === 'getDetails' && isset($input['landingIds'])) {
        getLandingDetails($conn, $input['landingIds']);
        return;
    }

    // 处理更新Landing Detail的L-Weight的请求
    if (isset($input['action']) && $input['action'] === 'updateLWeight') {
        updateLandingDetailLWeight($conn, $input);
        return;
    }

    // 处理查找Landing Detail的请求
    if (isset($input['action']) && $input['action'] === 'findLandingDetailByStock') {
        findLandingDetailByStock($conn, $input);
        return;
    }

    // 验证必填字段
    if (empty($input['LandingDate']) || empty($input['SupplierID']) || empty($input['PortID']) || empty($input['BoatID'])) {
        echo json_encode([
            'success' => false,
            'message' => '请填写所有必填字段(到货日期、供应商、港口、船)'
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
        $sql = "INSERT INTO tblLanding (LandingDate, SupplierID, PortID, BoatID)
                VALUES (?, ?, ?, ?)";

        // 处理日期：如果只包含日期部分，补充时间部分
        $landingDate = $input['LandingDate'];
        if (strlen($landingDate) <= 10) {
            $landingDate = $landingDate . ' 00:00:00';
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $landingDate,
            $input['SupplierID'],
            $input['PortID'],
            $input['BoatID']
        ]);

        // 获取插入的ID
        $landingId = $conn->lastInsertId();

        // 验证是否成功获取到ID
        if (empty($landingId)) {
            throw new Exception('无法获取新插入记录的ID');
        }

        // 插入明细表
        $detailSql = "INSERT INTO tblLandingDetail (LandingID, StockID, BinID, BinQty, `L-Weight`, ICE, Price, WeightUnitID)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $detailStmt = $conn->prepare($detailSql);

        $detailCount = 0;
        foreach ($input['details'] as $detail) {
            // 验证必填字段（L-Weight 可以为空）
            if (empty($detail['StockID']) || empty($detail['BinID'])) {
                continue;
            }
            $binQty = isset($detail['BinQty']) ? intval($detail['BinQty']) : 1;
            $ice = isset($detail['ICE']) ? intval($detail['ICE']) : 0;
            $price = isset($detail['Price']) ? floatval($detail['Price']) : 0;
            // L-Weight 可以为空，传 null 或空字符串
            $lWeight = isset($detail['L-Weight']) && $detail['L-Weight'] !== '' ? $detail['L-Weight'] : null;
            // 单位 ID，默认为 1 (KG)
            $weightUnitId = isset($detail['WeightUnitID']) ? intval($detail['WeightUnitID']) : 1;
            $detailStmt->execute([
                $landingId,
                $detail['StockID'],
                $detail['BinID'],
                $binQty,
                $lWeight,
                $ice,
                $price,
                $weightUnitId
            ]);
            $detailCount++;
        }

        if ($detailCount === 0) {
            throw new Exception('请至少添加一条明细记录');
        }

        // 自动生成对应的 Purchase 记录
        try {
            $purchaseData = generatePurchaseFromLanding($conn, $landingId);
            $autoGeneratedPurchase = true;
        } catch (Exception $purchaseEx) {
            // 如果生成 Purchase 失败，记录日志但不影响 Landing 的创建
            error_log("自动生成 Purchase 失败: " . $purchaseEx->getMessage());
            $autoGeneratedPurchase = false;
            $purchaseError = $purchaseEx->getMessage();
        }

        $conn->commit();

        $response = [
            'success' => true,
            'message' => '添加成功',
            'LandingID' => $landingId,
            'autoGeneratedPurchase' => $autoGeneratedPurchase
        ];

        // 如果成功生成 Purchase，返回 Purchase 数据
        if ($autoGeneratedPurchase && isset($purchaseData)) {
            $response['PurchaseID'] = $purchaseData['PurchaseID'];
            $response['PurchaseData'] = $purchaseData;
        }

        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("新增到货记录失败: " . $e->getMessage());
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
    if (empty($input['LandingID'])) {
        echo json_encode([
            'success' => false,
            'message' => '记录ID不能为空'
        ]);
        return;
    }

    // 验证必填字段
    if (empty($input['LandingDate']) || empty($input['SupplierID']) || empty($input['PortID']) || empty($input['BoatID'])) {
        echo json_encode([
            'success' => false,
            'message' => '请填写所有必填字段(到货日期、供应商、港口、船)'
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
        $sql = "UPDATE tblLanding SET LandingDate = ?, SupplierID = ?, PortID = ?, BoatID = ?
                WHERE LandingID = ?";

        // 处理日期：如果只包含日期部分，补充时间部分
        $landingDate = $input['LandingDate'];
        if (strlen($landingDate) <= 10) {
            $landingDate = $landingDate . ' 00:00:00';
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $landingDate,
            $input['SupplierID'],
            $input['PortID'],
            $input['BoatID'],
            $input['LandingID']
        ]);

        // 软删除原有明细
        $deleteDetailSql = "UPDATE tblLandingDetail SET is_del = 1 WHERE LandingID = ?";
        $deleteDetailStmt = $conn->prepare($deleteDetailSql);
        $deleteDetailStmt->execute([$input['LandingID']]);

        // 插入新的明细
        $detailSql = "INSERT INTO tblLandingDetail (LandingID, StockID, BinID, BinQty, `L-Weight`, ICE, Price, WeightUnitID)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $detailStmt = $conn->prepare($detailSql);

        foreach ($input['details'] as $detail) {
            // 验证必填字段（L-Weight 可以为空）
            if (empty($detail['StockID']) || empty($detail['BinID'])) {
                continue;
            }
            $binQty = isset($detail['BinQty']) ? intval($detail['BinQty']) : 1;
            $ice = isset($detail['ICE']) ? intval($detail['ICE']) : 0;
            $price = isset($detail['Price']) ? floatval($detail['Price']) : 0;
            // L-Weight 可以为空，传 null 或空字符串
            $lWeight = isset($detail['L-Weight']) && $detail['L-Weight'] !== '' ? $detail['L-Weight'] : null;
            // 单位 ID，默认为 1 (KG)
            $weightUnitId = isset($detail['WeightUnitID']) ? intval($detail['WeightUnitID']) : 1;
            $detailStmt->execute([
                $input['LandingID'],
                $detail['StockID'],
                $detail['BinID'],
                $binQty,
                $lWeight,
                $ice,
                $price,
                $weightUnitId
            ]);
        }

        // 同步更新对应的 Purchase 记录
        $purchaseUpdated = false;
        $purchaseData = null;
        try {
            $purchaseData = updatePurchaseFromLanding($conn, $input['LandingID']);
            $purchaseUpdated = true;
        } catch (Exception $purchaseEx) {
            // 如果更新 Purchase 失败，记录日志但不影响 Landing 的更新
            error_log("同步更新 Purchase 失败: " . $purchaseEx->getMessage());
            $purchaseUpdated = false;
            $purchaseError = $purchaseEx->getMessage();
        }

        $conn->commit();

        $response = [
            'success' => true,
            'message' => '更新成功',
            'LandingID' => $input['LandingID'],
            'purchaseUpdated' => $purchaseUpdated
        ];

        // 如果成功更新 Purchase，返回 Purchase 数据
        if ($purchaseUpdated && $purchaseData) {
            $response['PurchaseID'] = $purchaseData['PurchaseID'];
            $response['PurchaseData'] = $purchaseData;
        }

        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("更新到货记录失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '更新失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 重置 AUTO_INCREMENT
 */
function resetAutoIncrement($conn, $table, $idField) {
    $sql = "SELECT COALESCE(MAX($idField), 0) + 1 as next_id FROM $table";
    $result = $conn->query($sql)->fetch();
    $nextId = $result['next_id'];
    
    $conn->exec("ALTER TABLE $table AUTO_INCREMENT = $nextId");
}

/**
 * 处理DELETE请求 - 物理删除记录（级联删除关联的采购和销售记录）
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
    if (empty($input['LandingID'])) {
        echo json_encode([
            'success' => false,
            'message' => '记录ID不能为空'
        ]);
        return;
    }

    try {
        $conn->beginTransaction();

        $landingId = $input['LandingID'];

        // 1. 物理删除到货明细
        $landingDetailSql = "DELETE FROM tblLandingDetail WHERE LandingID = ?";
        $landingDetailStmt = $conn->prepare($landingDetailSql);
        $landingDetailStmt->execute([$landingId]);

        // 2. 查找关联的采购记录
        $purchaseQuery = "SELECT PurchaseID FROM tblPurchase WHERE LandingID = ?";
        $purchaseStmt = $conn->prepare($purchaseQuery);
        $purchaseStmt->execute([$landingId]);
        $purchaseIds = $purchaseStmt->fetchAll(PDO::FETCH_COLUMN);

        // 3. 级联物理删除采购记录及其明细
        if (!empty($purchaseIds)) {
            $purchaseIdList = implode(',', array_fill(0, count($purchaseIds), '?'));

            // 查找并物理删除关联的销售记录及其明细
            $salesQuery = "SELECT SalesID FROM tblSales WHERE PurchaseID IN ($purchaseIdList)";
            $salesStmt = $conn->prepare($salesQuery);
            $salesStmt->execute($purchaseIds);
            $salesIds = $salesStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($salesIds)) {
                $salesIdList = implode(',', array_fill(0, count($salesIds), '?'));

                // 物理删除销售明细
                $salesDetailSql = "DELETE FROM tblSalesDetail WHERE SalesID IN ($salesIdList)";
                $salesDetailStmt = $conn->prepare($salesDetailSql);
                $salesDetailStmt->execute($salesIds);

                // 物理删除销售记录
                $salesSql = "DELETE FROM tblSales WHERE SalesID IN ($salesIdList)";
                $salesStmt = $conn->prepare($salesSql);
                $salesStmt->execute($salesIds);
            }

            // 物理删除采购明细
            $purchaseDetailSql = "DELETE FROM tblPurchaseDetail WHERE PurchaseID IN ($purchaseIdList)";
            $purchaseDetailStmt = $conn->prepare($purchaseDetailSql);
            $purchaseDetailStmt->execute($purchaseIds);

            // 物理删除采购记录
            $purchaseSql = "DELETE FROM tblPurchase WHERE PurchaseID IN ($purchaseIdList)";
            $purchaseStmt = $conn->prepare($purchaseSql);
            $purchaseStmt->execute($purchaseIds);
        }

        // 4. 物理删除到货主表
        $landingSql = "DELETE FROM tblLanding WHERE LandingID = ?";
        $landingStmt = $conn->prepare($landingSql);
        $landingStmt->execute([$landingId]);

        $conn->commit();
        
        // 5. 重置 AUTO_INCREMENT（在事务提交后执行）
        resetAutoIncrement($conn, 'tblLanding', 'LandingID');

        echo json_encode([
            'success' => true,
            'message' => '删除成功（已级联删除关联的采购和销售记录）'
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("删除到货记录失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '删除失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 获取下拉选项数据（供应商、港口、库存、箱）
 */
function getOptions($conn) {
    // 验证登录状态
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => '未登录或登录已过期'
        ]);
        return;
    }

    try {
        // 获取供应商列表
        $supplierSql = "SELECT SupplierID, SupplierName FROM tblSuppliers WHERE Disc = 0 ORDER BY SupplierName";
        $supplierStmt = $conn->prepare($supplierSql);
        $supplierStmt->execute();
        $suppliers = $supplierStmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取港口列表
        $portSql = "SELECT PortID, Port FROM tblPort WHERE Disc = 0 ORDER BY Port";
        $portStmt = $conn->prepare($portSql);
        $portStmt->execute();
        $ports = $portStmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取库存列表
        $stockSql = "SELECT StockID, Stock, Description FROM tblStock ORDER BY Stock";
        $stockStmt = $conn->prepare($stockSql);
        $stockStmt->execute();
        $stocks = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取箱列表（只获取未删除的：is_del = 0）
        $binSql = "SELECT BinID, BinName, `B-Weight` FROM tblBin WHERE is_del = 0 ORDER BY BinName";
        $binStmt = $conn->prepare($binSql);
        $binStmt->execute();
        $bins = $binStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'suppliers' => $suppliers,
                'ports' => $ports,
                'stocks' => $stocks,
                'bins' => $bins
            ]
        ]);

    } catch (Exception $e) {
        error_log("获取选项数据失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '获取数据失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 获取到货明细（批量）
 * @param PDO $conn 数据库连接
 * @param array $landingIds 到货ID数组
 */
function getLandingDetails($conn, $landingIds) {
    if (empty($landingIds) || !is_array($landingIds)) {
        echo json_encode([
            'success' => false,
            'message' => '无效的到货ID'
        ]);
        return;
    }

    try {
        // 构建IN子句的占位符
        $placeholders = str_repeat('?,', count($landingIds) - 1) . '?';

        // 查询明细数据
        $sql = "SELECT ld.ID, ld.LandingID, ld.StockID, ld.BinID, ld.BinQty, ld.`L-Weight`, ld.ICE, ld.Price,
                       st.Stock, st.Description, st.State, st.Area, b.BinName
                FROM tblLandingDetail ld
                LEFT JOIN tblStock st ON ld.StockID = st.StockID
                LEFT JOIN tblBin b ON ld.BinID = b.BinID
                WHERE ld.LandingID IN ($placeholders) AND ld.is_del = 0
                ORDER BY ld.LandingID, ld.ID";

        $stmt = $conn->prepare($sql);
        $stmt->execute($landingIds);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);

    } catch (PDOException $e) {
        error_log("获取到货明细失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '获取明细失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 更新Landing Detail的L-Weight
 * 根据Purchase的LandedWeight倒计算L-Weight
 * 公式: L-Weight = LandedKG + (B-Weight × BinQty)
 */
function updateLandingDetailLWeight($conn, $input) {
    try {
        // 验证必填字段
        if (empty($input['landingDetailId']) || !isset($input['landedKG'])) {
            echo json_encode([
                'success' => false,
                'message' => '缺少必要参数：landingDetailId 或 landedKG'
            ]);
            return;
        }

        $landingDetailId = intval($input['landingDetailId']);
        $landedKG = floatval($input['landedKG']);

        // 查询Landing Detail的信息（BinQty, BinID）
        $sql = "SELECT BinID, BinQty FROM tblLandingDetail WHERE ID = ? AND is_del = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$landingDetailId]);
        $detail = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$detail) {
            echo json_encode([
                'success' => false,
                'message' => 'Landing Detail不存在'
            ]);
            return;
        }

        // 计算篮子总重量
        $binID = $detail['BinID'];
        $binQty = intval($detail['BinQty'] ?? 1);
        $totalBinWeight = 0;

        if ($binID) {
            $binSql = "SELECT `B-Weight` FROM tblBin WHERE BinID = ?";
            $binStmt = $conn->prepare($binSql);
            $binStmt->execute([$binID]);
            $binData = $binStmt->fetch(PDO::FETCH_ASSOC);
            $singleBinWeight = floatval($binData['B-Weight'] ?? 0);
            $totalBinWeight = $singleBinWeight * $binQty;
        }

        // 倒计算 L-Weight = LandedKG + TotalBinWeight
        $lWeight = $landedKG + $totalBinWeight;

        // 更新Landing Detail的L-Weight
        $updateSql = "UPDATE tblLandingDetail SET `L-Weight` = ? WHERE ID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([$lWeight, $landingDetailId]);

        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'lWeight' => $lWeight
        ]);

    } catch (PDOException $e) {
        error_log("更新Landing Detail L-Weight失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '更新失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 根据LandingID和StockID查找Landing Detail
 */
function findLandingDetailByStock($conn, $input) {
    try {
        // 验证必填字段
        if (empty($input['landingID']) || empty($input['stockID'])) {
            echo json_encode([
                'success' => false,
                'message' => '缺少必要参数：landingID 或 stockID'
            ]);
            return;
        }

        $landingID = intval($input['landingID']);
        $stockID = $input['stockID'];

        // 查询Landing Detail
        $sql = "SELECT ID, LandingID, StockID, BinID, BinQty, `L-Weight`, WeightUnitID
                FROM tblLandingDetail
                WHERE LandingID = ? AND StockID = ? AND is_del = 0
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$landingID, $stockID]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '未找到对应的Landing Detail记录'
            ]);
        }

    } catch (PDOException $e) {
        error_log("查找Landing Detail失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '查找失败: ' . $e->getMessage()
        ]);
    }
}
?>

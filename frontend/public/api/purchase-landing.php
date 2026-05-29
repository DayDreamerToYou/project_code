<?php
/**
 * ============================================
 * 渔业收购管理系统 - 业务处理API
 * 说明：处理收货录入、ID生成、账单生成、月底汇总等功能
 * ============================================
 */

// 定义访问常量
define('APP_ACCESS', true);

// 引入数据库配置
require_once '../config/db.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * 验证用户登录状态
 */
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    return $_SESSION['user_id'];
}

/**
 * 处理请求
 */
try {
    // 验证用户登录
    $userId = checkAuth();
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '未登录或登录已过期，请重新登录'
        ]);
        exit;
    }

    // 获取数据库连接
    $db = Database::getConnection();
    $db->beginTransaction();

    // GET请求：查询数据
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        switch ($action) {
            case 'getRecentLandings':
                $result = getRecentLandings($db);
                break;

            case 'getMonthlyReport':
                $result = getMonthlyReport($db, $_GET);
                break;

            default:
                $result = [
                    'success' => false,
                    'message' => '无效的操作'
                ];
        }
    }
    // POST请求：处理数据
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $requestData = json_decode($input, true);

        if (!$requestData || !isset($requestData['action'])) {
            throw new Exception('无效的请求数据');
        }

        $action = $requestData['action'];

        switch ($action) {
            case 'saveLanding':
                $result = saveLanding($db, $requestData['data']);
                break;

            case 'generateBills':
                $result = generateBills($db, $requestData['docketId']);
                break;

            case 'getBills':
                $result = getBills($db, $requestData['docketId']);
                break;

            default:
                $result = [
                    'success' => false,
                    'message' => '无效的操作'
                ];
        }
    }

    $db->commit();

    // 返回结果
    echo json_encode($result);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log("业务处理错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ==================== 核心业务函数 ====================

/**
 * 保存收货记录
 */
function saveLanding($db, $data) {
    try {
        $landingDate = $data['landingDate'];
        $supplierId = $data['supplierId'];
        $portId = $data['portId'];
        $boatId = isset($data['boatId']) ? $data['boatId'] : null;
        $details = $data['details'];

        if (empty($details)) {
            throw new Exception('没有明细数据');
        }

        // 1. 生成Docket ID（使用当前最大ID + 1）
        $docketId = generateDocketId($db);

        // 2. 插入上岸记录
        $sql = "INSERT INTO tblLanding (LandingID, LandingDate, SupplierID, PortID)
                VALUES (:docketId, :landingDate, :supplierId, :portId)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':docketId' => $docketId,
            ':landingDate' => $landingDate,
            ':supplierId' => $supplierId,
            ':portId' => $portId
        ]);

        // 3. 插入上岸明细
        foreach ($details as $detail) {
            $binQty = isset($detail['binQty']) ? intval($detail['binQty']) : 1;
            $sql = "INSERT INTO tblLandingDetail (LandingID, StockID, BinID, BinQty, L-Weight)
                    VALUES (:landingId, :stockId, :binId, :binQty, :landedWeight)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':landingId' => $docketId,
                ':stockId' => $detail['stockId'],
                ':binId' => $detail['binId'],
                ':binQty' => $binQty,
                ':landedWeight' => $detail['landedWeight']
            ]);
        }

        // 4. 生成采购和销售账单
        $bills = generatePurchaseAndSalesBills($db, $docketId, $supplierId, $landingDate, $details);

        $db->commit();

        // 获取供应商和港口信息
        $supplier = $db->query("SELECT SupplierName FROM tblSuppliers WHERE SupplierID = $supplierId")->fetch();
        $port = $db->query("SELECT Port FROM tblPort WHERE PortID = $portId")->fetch();

        return [
            'success' => true,
            'message' => '保存成功',
            'landing' => [
                'DocketID' => $docketId,
                'PurchaseID' => $bills['purchaseId'],
                'SalesID' => $bills['salesId'],
                'LandingDate' => $landingDate,
                'SupplierName' => $supplier['SupplierName'],
                'Port' => $port['Port']
            ],
            'bills' => $bills
        ];

    } catch (PDOException $e) {
        error_log("保存收货记录错误: " . $e->getMessage());
        throw new Exception('保存收货记录失败: ' . $e->getMessage());
    }
}

/**
 * 生成Docket ID
 */
function generateDocketId($db) {
    // 获取当前最大的LandingID
    $stmt = $db->query("SELECT MAX(LandingID) as maxId FROM tblLanding");
    $result = $stmt->fetch();
    $maxId = $result['maxId'] ?? 0;

    // 返回最大ID + 1
    return $maxId + 1;
}

/**
 * 生成采购和销售账单
 */
function generatePurchaseAndSalesBills($db, $docketId, $supplierId, $landingDate, $details) {
    // 计算ID
    $purchaseId = $docketId + 50000;
    $salesId = $docketId + 80000;

    // 1. 创建采购账单
    $subtotal = 0;
    $purchaseDetails = [];

    foreach ($details as $detail) {
        $greenWeight = $detail['greenWeight'];
        $price = $detail['price'];
        $total = $detail['total'];
        $binQty = isset($detail['binQty']) ? intval($detail['binQty']) : 1;
        $binId = isset($detail['binId']) ? $detail['binId'] : null;

        $subtotal += $total;

        // 插入采购明细
        $sql = "INSERT INTO tblPurchaseDetail (PurchaseID, StockID, BinQty, GreenKG, LandedKG, Price, Total)
                VALUES (:purchaseId, :stockId, :binQty, :greenKG, :landedKG, :price, :total)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':purchaseId' => $purchaseId,
            ':stockId' => $detail['stockId'],
            ':binQty' => $binQty,
            ':greenKG' => $greenWeight,
            ':landedKG' => $detail['landedWeight'],
            ':price' => $price,
            ':total' => $total
        ]);

        // 获取鱼种类信息
        $stockStmt = $db->prepare("SELECT Stock FROM tblStock WHERE StockID = ?");
        $stockStmt->execute([$detail['stockId']]);
        $stock = $stockStmt->fetch();

        $purchaseDetails[] = [
            'StockID' => $detail['stockId'],
            'Stock' => $stock['Stock'],
            'BinQty' => $binQty,
            'GreenKG' => $greenWeight,
            'LandedKG' => $detail['landedWeight'],
            'Price' => $price,
            'Total' => $total
        ];
    }

    // 计算GST和总计（GST 15%）
    $gst = $subtotal * 0.15;
    $total = $subtotal + $gst;

    // 插入采购表
    $sql = "INSERT INTO tblPurchase (PurchaseID, PurchaseDate, SupplierID, Subtotal, GST, Total, LandingID)
            VALUES (:purchaseId, :purchaseDate, :supplierId, :subtotal, :gst, :total, :landingId)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':purchaseId' => $purchaseId,
        ':purchaseDate' => $landingDate,
        ':supplierId' => $supplierId,
        ':subtotal' => $subtotal,
        ':gst' => $gst,
        ':total' => $total,
        ':landingId' => $docketId
    ]);

    // 2. 创建销售账单（简化版本，使用相同数据）
    $salesSubtotal = $subtotal;
    $salesGst = $gst;
    $salesTotal = $total;

    $sql = "INSERT INTO tblSales (SalesID, SaleDate, CustomerID, Subtotal, GST, Total, PurchaseID)
            VALUES (:salesId, :saleDate, :customerId, :subtotal, :gst, :total, :purchaseId)";
    $stmt = $db->prepare($sql);
    // 默认客户ID为1（内部销售）
    $stmt->execute([
        ':salesId' => $salesId,
        ':saleDate' => $landingDate,
        ':customerId' => 1,
        ':subtotal' => $salesSubtotal,
        ':gst' => $salesGst,
        ':total' => $salesTotal,
        ':purchaseId' => $purchaseId
    ]);

    // 插入销售明细
    $salesDetails = [];
    foreach ($details as $detail) {
        $binQty = isset($detail['binQty']) ? intval($detail['binQty']) : 1;
        $gWeight = $detail['landedWeight'];
        $nWeight = $detail['greenWeight'];
        $amount = $detail['total'];

        $sql = "INSERT INTO tblSalesDetail (SalesID, StockID, BinQty, G-Weight, N-Weight, Price, Amount, BinID)
                VALUES (:salesId, :stockId, :binQty, :gWeight, :nWeight, :price, :amount, :binId)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':salesId' => $salesId,
            ':stockId' => $detail['stockId'],
            ':binQty' => $binQty,
            ':gWeight' => $gWeight,
            ':nWeight' => $nWeight,
            ':price' => $detail['price'],
            ':amount' => $amount,
            ':binId' => $detail['binId']
        ]);

        $stockStmt = $db->prepare("SELECT Stock FROM tblStock WHERE StockID = ?");
        $stockStmt->execute([$detail['stockId']]);
        $stock = $stockStmt->fetch();

        $salesDetails[] = [
            'StockID' => $detail['stockId'],
            'Stock' => $stock['Stock'],
            'BinQty' => $binQty,
            'G_Weight' => $gWeight,
            'N_Weight' => $nWeight,
            'Price' => $detail['price'],
            'Amount' => $amount
        ];
    }

    return [
        'purchaseId' => $purchaseId,
        'salesId' => $salesId,
        'purchase' => [
            'PurchaseID' => $purchaseId,
            'DocketID' => $docketId,
            'PurchaseDate' => $landingDate,
            'Subtotal' => $subtotal,
            'GST' => $gst,
            'Total' => $total,
            'details' => $purchaseDetails
        ],
        'sales' => [
            'SalesID' => $salesId,
            'PurchaseID' => $purchaseId,
            'SaleDate' => $landingDate,
            'Subtotal' => $salesSubtotal,
            'GST' => $salesGst,
            'Total' => $salesTotal,
            'details' => $salesDetails
        ]
    ];
}

/**
 * 生成账单
 */
function generateBills($db, $docketId) {
    try {
        // 检查是否已存在账单
        $stmt = $db->prepare("SELECT PurchaseID FROM tblPurchase WHERE LandingID = ?");
        $stmt->execute([$docketId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            throw new Exception('账单不存在，请先保存收货记录');
        }

        $bills = getBills($db, $docketId);
        return $bills;

    } catch (PDOException $e) {
        error_log("生成账单错误: " . $e->getMessage());
        throw new Exception('生成账单失败: ' . $e->getMessage());
    }
}

/**
 * 获取账单数据
 */
function getBills($db, $docketId) {
    try {
        // 获取上岸记录
        $stmt = $db->prepare("
            SELECT l.*, s.SupplierName, p.Port
            FROM tblLanding l
            LEFT JOIN tblSuppliers s ON l.SupplierID = s.SupplierID
            LEFT JOIN tblPort p ON l.PortID = p.PortID
            WHERE l.LandingID = ?
        ");
        $stmt->execute([$docketId]);
        $landing = $stmt->fetch();

        if (!$landing) {
            throw new Exception('收货记录不存在');
        }

        // 获取采购账单
        $stmt = $db->prepare("
            SELECT pu.*, s.SupplierName
            FROM tblPurchase pu
            LEFT JOIN tblSuppliers s ON pu.SupplierID = s.SupplierID
            WHERE pu.LandingID = ?
        ");
        $stmt->execute([$docketId]);
        $purchase = $stmt->fetch();

        // 获取采购明细
        $stmt = $db->prepare("
            SELECT pd.*, st.Stock,
                   uLanded.UnitSymbol as LandedUnitSymbol,
                   uGreen.UnitSymbol as GreenUnitSymbol
            FROM tblPurchaseDetail pd
            LEFT JOIN tblStock st ON pd.StockID = st.StockID
            LEFT JOIN tblUnits uLanded ON pd.LandedWeightUnitID = uLanded.UnitID
            LEFT JOIN tblUnits uGreen ON pd.GreenWeightUnitID = uGreen.UnitID
            WHERE pd.PurchaseID = ?
        ");
        $stmt->execute([$purchase['PurchaseID']]);
        $purchaseDetails = $stmt->fetchAll();

        // 获取销售账单
        $stmt = $db->prepare("SELECT * FROM tblSales WHERE PurchaseID = ?");
        $stmt->execute([$purchase['PurchaseID']]);
        $sales = $stmt->fetch();

        // 获取销售明细
        $stmt = $db->prepare("
            SELECT sd.*, st.Stock,
                   u.UnitSymbol as WeightUnitSymbol
            FROM tblSalesDetail sd
            LEFT JOIN tblStock st ON sd.StockID = st.StockID
            LEFT JOIN tblUnits u ON sd.WeightUnitID = u.UnitID
            WHERE sd.SalesID = ?
        ");
        $stmt->execute([$sales['SalesID']]);
        $salesDetails = $stmt->fetchAll();

        return [
            'success' => true,
            'bills' => [
                'purchase' => [
                    'PurchaseID' => $purchase['PurchaseID'],
                    'DocketID' => $docketId,
                    'PurchaseDate' => $purchase['PurchaseDate'],
                    'SupplierName' => $landing['SupplierName'],
                    'Subtotal' => (float)$purchase['Subtotal'],
                    'GST' => (float)$purchase['GST'],
                    'Total' => (float)$purchase['Total'],
                    'details' => $purchaseDetails
                ],
                'sales' => [
                    'SalesID' => $sales['SalesID'],
                    'PurchaseID' => $purchase['PurchaseID'],
                    'SaleDate' => $sales['SaleDate'],
                    'Subtotal' => (float)$sales['Subtotal'],
                    'GST' => (float)$sales['GST'],
                    'Total' => (float)$sales['Total'],
                    'details' => $salesDetails
                ]
            ]
        ];

    } catch (PDOException $e) {
        error_log("获取账单错误: " . $e->getMessage());
        throw new Exception('获取账单失败: ' . $e->getMessage());
    }
}

/**
 * 获取最近收货记录
 */
function getRecentLandings($db) {
    try {
        // 获取分页参数
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        // 查询总数
        $countSql = "
            SELECT COUNT(DISTINCT l.LandingID) as total
            FROM tblLanding l
            WHERE l.is_del = 0
        ";
        $countStmt = $db->query($countSql);
        $total = $countStmt->fetch()['total'];

        // 查询数据
        $sql = "
            SELECT
                l.LandingID as DocketID,
                l.LandingDate,
                l.SupplierID,
                s.SupplierName,
                p.Port,
                pu.PurchaseID,
                sa.SalesID,
                COALESCE(SUM(pd.GreenKG), 0) as TotalGreenWeight,
                COALESCE(pu.Total, 0) as TotalAmount
            FROM tblLanding l
            LEFT JOIN tblSuppliers s ON l.SupplierID = s.SupplierID
            LEFT JOIN tblPort p ON l.PortID = p.PortID
            LEFT JOIN tblPurchase pu ON l.LandingID = pu.LandingID
            LEFT JOIN tblSales sa ON pu.PurchaseID = sa.PurchaseID
            LEFT JOIN tblPurchaseDetail pd ON pu.PurchaseID = pd.PurchaseID
            WHERE l.is_del = 0
            GROUP BY l.LandingID
            ORDER BY l.LandingID ASC
            LIMIT ? OFFSET ?
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $landings = $stmt->fetchAll();

        return [
            'success' => true,
            'data' => $landings,
            'count' => $total,
            'page' => $page,
            'limit' => $limit
        ];

    } catch (PDOException $e) {
        error_log("获取收货记录错误: " . $e->getMessage());
        throw new Exception('获取收货记录失败: ' . $e->getMessage());
    }
}

/**
 * 生成月底汇总报表
 */
function getMonthlyReport($db, $params) {
    try {
        $month = isset($params['month']) ? $params['month'] : date('Y-m');
        $supplierId = isset($params['supplierId']) ? $params['supplierId'] : null;

        // 解析月份
        $year = substr($month, 0, 4);
        $monthNum = substr($month, 5, 2);

        // 查询每条收鱼记录的详细信息（按 PurchaseDetail ID 区分每条记录）
        $sql = "
            SELECT
                pd.ID,
                s.SupplierID,
                s.SupplierName,
                s.QRN,
                st.StockID,
                st.Stock,
                st.Description,
                l.LandingID,
                l.LandingDate,
                pd.GreenKG,
                pd.GreenWeightUnitID,
                pd.Price,
                pd.Total,
                b.BoatName,
                p.Port,
                uGreen.UnitSymbol as GreenUnitSymbol
            FROM tblLanding l
            LEFT JOIN tblSuppliers s ON l.SupplierID = s.SupplierID
            LEFT JOIN tblPurchase pu ON l.LandingID = pu.LandingID
            LEFT JOIN tblPurchaseDetail pd ON pu.PurchaseID = pd.PurchaseID
            LEFT JOIN tblStock st ON pd.StockID = st.StockID
            LEFT JOIN tblBoat b ON l.BoatID = b.BoatID
            LEFT JOIN tblPort p ON l.PortID = p.PortID
            LEFT JOIN tblUnits uGreen ON pd.GreenWeightUnitID = uGreen.UnitID
            WHERE YEAR(l.LandingDate) = :year
            AND MONTH(l.LandingDate) = :month
            AND l.is_del = 0
            AND pd.ID IS NOT NULL
        ";

        $bindParams = [
            ':year' => $year,
            ':month' => $monthNum
        ];

        // 如果指定供应商
        if ($supplierId) {
            $sql .= " AND s.SupplierID = :supplierId";
            $bindParams[':supplierId'] = $supplierId;
        }

        $sql .= " ORDER BY s.SupplierName, pd.ID";

        $stmt = $db->prepare($sql);
        $stmt->execute($bindParams);
        $detailRecords = $stmt->fetchAll();

        // 按供应商分组数据（不按 StockID 二级分组，每条记录独立）
        $supplierGroups = [];
        $summary = [
            'totalLandings' => 0,
            'totalGreenWeight' => 0,
            'totalAmount' => 0,
            'totalRecords' => 0
        ];

        $landingIds = [];

        foreach ($detailRecords as $record) {
            $supplierId = $record['SupplierID'];
            $supplierName = $record['SupplierName'];
            $qrn = $record['QRN'];

            // 统计汇总数据
            $summary['totalGreenWeight'] += $record['GreenKG'];
            $summary['totalAmount'] += $record['Total'];
            $summary['totalRecords']++;

            if (!in_array($record['LandingID'], $landingIds)) {
                $landingIds[] = $record['LandingID'];
            }

            // 初始化供应商分组
            if (!isset($supplierGroups[$supplierId])) {
                $supplierGroups[$supplierId] = [
                    'SupplierID' => $supplierId,
                    'SupplierName' => $supplierName,
                    'QRN' => $qrn,
                    'records' => []
                ];
            }

            // 直接添加每条记录（不按 StockID 分组）
            $supplierGroups[$supplierId]['records'][] = [
                'ID' => $record['ID'],
                'StockID' => $record['StockID'],
                'Stock' => $record['Stock'],
                'Description' => $record['Description'],
                'LandingID' => $record['LandingID'],
                'LandingDate' => $record['LandingDate'],
                'GreenKG' => $record['GreenKG'],
                'GreenWeightUnitID' => $record['GreenWeightUnitID'],
                'GreenUnitSymbol' => $record['GreenUnitSymbol'] ?: 'kg',
                'Price' => $record['Price'],
                'Total' => $record['Total'],
                'BoatName' => $record['BoatName'],
                'Port' => $record['Port']
            ];
        }

        $summary['totalLandings'] = count($landingIds);

        // 转换为索引数组
        $data = array_values($supplierGroups);

        return [
            'success' => true,
            'month' => $month,
            'summary' => $summary,
            'data' => $data
        ];

    } catch (PDOException $e) {
        error_log("生成月报错误: " . $e->getMessage());
        throw new Exception('生成月报失败: ' . $e->getMessage());
    }
}
?>

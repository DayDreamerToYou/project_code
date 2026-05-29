<?php
/**
 * ============================================
 * 到货记录选项数据API接口
 * 说明：获取供应商、港口、库存、箱等下拉选项数据
 * ============================================
 */

// 定义访问常量
if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}

// 引入数据库配置
require_once dirname(__DIR__) . '/config/db.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 启动Session
session_start();

try {
    // 获取数据库连接
    $conn = Database::getConnection();

    // 注释掉登录验证，选项数据不需要权限
    // if (!isset($_SESSION['user_id'])) {
    //     echo json_encode([
    //         'success' => false,
    //         'message' => '未登录或登录已过期'
    //     ]);
    //     exit;
    // }

    // 检查是否是根据供应商获取船只的请求
    $supplierId = isset($_GET['supplierId']) ? intval($_GET['supplierId']) : 0;

    if ($supplierId > 0) {
        // 根据供应商获取对应船队的船只
        $boatSql = "SELECT b.BoatID, b.BoatNo, b.BoatName
                    FROM tblBoat b
                    INNER JOIN tblFleetDetail fd ON b.BoatID = fd.BoatID
                    INNER JOIN tblSuppliers s ON fd.FleetID = s.FleetID
                    WHERE s.SupplierID = :supplierId
                    AND b.is_del = 0
                    AND fd.is_del = 0
                    AND s.is_del = 0
                    ORDER BY b.BoatName";
        $boatStmt = $conn->prepare($boatSql);
        $boatStmt->bindParam(':supplierId', $supplierId, PDO::PARAM_INT);
        $boatStmt->execute();
        $boats = $boatStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $boats
        ]);
        exit;
    }

    // 获取供应商列表（只获取未删除的：is_del = 0）
    $supplierSql = "SELECT SupplierID, SupplierName, FleetID FROM tblSuppliers WHERE is_del = 0 ORDER BY SupplierName";
    $supplierStmt = $conn->prepare($supplierSql);
    $supplierStmt->execute();
    $suppliers = $supplierStmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取港口列表（只获取未删除的：is_del = 0）
    $portSql = "SELECT PortID, Port FROM tblPort WHERE is_del = 0 ORDER BY Port";
    $portStmt = $conn->prepare($portSql);
    $portStmt->execute();
    $ports = $portStmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取库存列表（只获取未删除的：is_del = 0）
    $stockSql = "SELECT StockID, Stock, Description, State, Area, Price, Conversion FROM tblStock WHERE is_del = 0 ORDER BY Stock";
    $stockStmt = $conn->prepare($stockSql);
    $stockStmt->execute();
    $stocks = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取箱列表（只获取未删除的：is_del = 0）
    $binSql = "SELECT BinID, BinName, `B-Weight` FROM tblBin WHERE is_del = 0 ORDER BY BinName";
    $binStmt = $conn->prepare($binSql);
    $binStmt->execute();
    $bins = $binStmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取船只列表（只获取未删除的：is_del = 0）
    $boatSql = "SELECT BoatID, BoatNo, BoatName FROM tblBoat WHERE is_del = 0 ORDER BY BoatName";
    $boatStmt = $conn->prepare($boatSql);
    $boatStmt->execute();
    $boats = $boatStmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取供应商-船只映射关系（用于前端缓存，避免重复请求）
    $supplierBoatSql = "SELECT s.SupplierID, b.BoatID, b.BoatNo, b.BoatName
                        FROM tblSuppliers s
                        LEFT JOIN tblFleetDetail fd ON s.FleetID = fd.FleetID AND fd.is_del = 0
                        LEFT JOIN tblBoat b ON fd.BoatID = b.BoatID AND b.is_del = 0
                        WHERE s.is_del = 0
                        ORDER BY s.SupplierID, b.BoatName";
    $supplierBoatStmt = $conn->prepare($supplierBoatSql);
    $supplierBoatStmt->execute();
    $supplierBoats = $supplierBoatStmt->fetchAll(PDO::FETCH_ASSOC);

    // 构建供应商-船只映射数组：SupplierID => [Boat1, Boat2, ...]
    $supplierBoatMap = [];
    foreach ($supplierBoats as $row) {
        $supplierId = $row['SupplierID'];
        if ($row['BoatID']) {  // 只有存在船只时才添加
            if (!isset($supplierBoatMap[$supplierId])) {
                $supplierBoatMap[$supplierId] = [];
            }
            $supplierBoatMap[$supplierId][] = [
                'BoatID' => $row['BoatID'],
                'BoatNo' => $row['BoatNo'],
                'BoatName' => $row['BoatName']
            ];
        }
    }

    // 获取供应商特定价格列表（构建映射：SupplierID_StockID => UnitPrice）
    $priceSql = "SELECT SupplierID, StockID, UnitPrice FROM tblSupplierStockPrice WHERE is_del = 0";
    $priceStmt = $conn->prepare($priceSql);
    $priceStmt->execute();
    $prices = $priceStmt->fetchAll(PDO::FETCH_ASSOC);

    // 构建价格映射数组
    $priceMap = [];
    foreach ($prices as $price) {
        $key = $price['SupplierID'] . '_' . $price['StockID'];
        $priceMap[$key] = floatval($price['UnitPrice']);
    }

    // 获取单位列表
    $unitSql = "SELECT UnitID, UnitCode, UnitName, UnitSymbol, SortOrder
                FROM tblUnits
                WHERE is_del = 0
                ORDER BY SortOrder ASC, UnitID ASC";
    $unitStmt = $conn->prepare($unitSql);
    $unitStmt->execute();
    $units = $unitStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'suppliers' => $suppliers,
            'ports' => $ports,
            'stocks' => $stocks,
            'bins' => $bins,
            'boats' => $boats,
            'priceMap' => $priceMap,
            'supplierBoatMap' => $supplierBoatMap,
            'units' => $units
        ]
    ]);

} catch (Exception $e) {
    error_log("获取选项数据失败: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '获取数据失败: ' . $e->getMessage()
    ]);
}
?>

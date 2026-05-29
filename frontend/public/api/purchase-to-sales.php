<?php
/**
 * ============================================
 * 从采购记录生成销售单API接口
 * 说明：根据采购记录自动生成销售单和销售明细
 * ============================================
 */

// 定义访问常量
define('APP_ACCESS', true);

// 引入数据库配置
require_once '../config/db.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 启动Session
session_start();

try {
    // 获取数据库连接
    $conn = Database::getConnection();

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
            'message' => '采购记录ID不能为空'
        ]);
        return;
    }

    $purchaseId = intval($input['PurchaseID']);

    // 开启事务
    $conn->beginTransaction();

    // 1. 获取采购记录
    $sql = "SELECT PurchaseID, PurchaseDate, LandingID, SupplierID, Subtotal, GST, Total
            FROM tblPurchase
            WHERE PurchaseID = ? AND is_del = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$purchaseId]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception('采购记录不存在');
    }

    // 2. 检查是否已经生成过销售单
    $checkSql = "SELECT COUNT(*) as count FROM tblSales WHERE PurchaseID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$purchaseId]);
    $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($checkResult['count'] > 0) {
        throw new Exception('该采购记录已生成销售单，请勿重复生成');
    }

    // 3. 获取采购明细
    $detailSql = "SELECT pd.ID, pd.StockID, pd.BinQty, pd.LandedKG, pd.LandedWeightUnitID, pd.Price, pd.Total,
                  st.Stock
                  FROM tblPurchaseDetail pd
                  LEFT JOIN tblStock st ON pd.StockID = st.StockID
                  WHERE pd.PurchaseID = ? AND pd.is_del = 0
                  ORDER BY pd.ID";
    $detailStmt = $conn->prepare($detailSql);
    $detailStmt->execute([$purchaseId]);
    $purchaseDetails = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($purchaseDetails)) {
        throw new Exception('采购明细不存在');
    }

    // 4. 计算销售明细数据
    $salesDetailsData = [];
    $subtotal = 0;

    foreach ($purchaseDetails as $purchaseDetail) {
        $stockID = $purchaseDetail['StockID'];
        $nWeight = floatval($purchaseDetail['LandedKG']); // N-Weight = LandedKG
        $weightUnitId = intval($purchaseDetail['LandedWeightUnitID'] ?? 1); // 获取单位ID，默认为1 (KG)
        $price = floatval($purchaseDetail['Price']);
        $binQty = intval($purchaseDetail['BinQty'] ?? 1); // 从采购明细获取 BinQty，默认1

        // 查找对应的到货明细，获取BinID
        $binId = null;
        $bWeight = 0;

        if (!empty($purchase['LandingID'])) {
            $landingDetailSql = "SELECT BinID FROM tblLandingDetail
                                WHERE LandingID = ? AND StockID = ? AND is_del = 0
                                LIMIT 1";
            $landingDetailStmt = $conn->prepare($landingDetailSql);
            $landingDetailStmt->execute([$purchase['LandingID'], $stockID]);
            $landingDetail = $landingDetailStmt->fetch(PDO::FETCH_ASSOC);

            if ($landingDetail && !empty($landingDetail['BinID'])) {
                $binId = intval($landingDetail['BinID']);

                // 获取Bin的B-Weight（只获取未删除的：is_del = 0）
                $binSql = "SELECT `B-Weight` FROM tblBin WHERE BinID = ? AND is_del = 0";
                $binStmt = $conn->prepare($binSql);
                $binStmt->execute([$binId]);
                $bin = $binStmt->fetch(PDO::FETCH_ASSOC);

                if ($bin) {
                    $bWeight = floatval($bin['B-Weight']);
                }
            }
        }

        $gWeight = $nWeight + ($binQty * $bWeight); // G-Weight = N-Weight + BinQty * B-Weight
        $amount = $nWeight * $price; // Amount = N-Weight * Price

        $salesDetailsData[] = [
            'StockID' => $stockID,
            'BinID' => $binId,
            'BinQty' => $binQty,
            'G-Weight' => $gWeight,
            'N-Weight' => $nWeight,
            'WeightUnitID' => $weightUnitId,
            'Price' => $price,
            'Amount' => $amount
        ];

        $subtotal += $amount;
    }

    // 5. 计算销售记录数据
    $salesId = $purchaseId + 20000; // SalesID = PurchaseID + 20000
    $gst = floatval($purchase['GST']); // GST 对应采购记录的GST
    $total = $subtotal + $gst; // Total = Subtotal + GST

    // 6. 插入销售记录
    $insertSalesSql = "INSERT INTO tblSales (SalesID, SaleDate, CustomerID, Subtotal, GST, Total, PurchaseID)
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertSalesStmt = $conn->prepare($insertSalesSql);
    $insertSalesStmt->execute([
        $salesId,
        $purchase['PurchaseDate'],
        1, // CustomerID 默认为 1
        $subtotal,
        $gst,
        $total,
        $purchaseId
    ]);

    // 7. 插入销售明细
    $insertDetailSql = "INSERT INTO tblSalesDetail (SalesID, StockID, BinQty, `G-Weight`, `N-Weight`, WeightUnitID, Price, Amount, BinID)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertDetailStmt = $conn->prepare($insertDetailSql);

    foreach ($salesDetailsData as $detail) {
        $insertDetailStmt->execute([
            $salesId,
            $detail['StockID'],
            $detail['BinQty'],
            $detail['G-Weight'],
            $detail['N-Weight'],
            $detail['WeightUnitID'],
            $detail['Price'],
            $detail['Amount'],
            $detail['BinID']
        ]);
    }

    // 提交事务
    $conn->commit();

    // 返回成功结果
    echo json_encode([
        'success' => true,
        'message' => '销售单生成成功',
        'data' => [
            'SalesID' => $salesId,
            'PurchaseID' => $purchaseId,
            'SaleDate' => $purchase['PurchaseDate'],
            'Subtotal' => number_format($subtotal, 2),
            'GST' => number_format($gst, 2),
            'Total' => number_format($total, 2),
            'details_count' => count($salesDetailsData)
        ]
    ]);

} catch (Exception $e) {
    // 回滚事务
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("生成销售单失败: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => '生成销售单失败: ' . $e->getMessage()
    ]);
}
?>

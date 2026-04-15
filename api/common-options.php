<?php
/**
 * ============================================
 * 通用选项API接口
 * 说明：返回基础数据列表选项
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

    $data = [];

    // 获取供应商列表
    $sql = "SELECT SupplierID, SupplierName, Phone
            FROM tblSuppliers
            WHERE is_del = 0 AND (Disc = 0 OR Disc IS NULL)
            ORDER BY SupplierName";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data['suppliers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取港口列表
    $sql = "SELECT PortID, Port
            FROM tblPort
            WHERE Disc = 0 OR Disc IS NULL
            ORDER BY Port";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data['ports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取船只列表
    $sql = "SELECT BoatID, BoatNo, BoatName
            FROM tblBoat
            WHERE Disc = 0 OR Disc IS NULL
            ORDER BY BoatNo";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data['boats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取库存列表
    $sql = "SELECT StockID, Stock, Price
            FROM tblStock
            ORDER BY Stock";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data['stocks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log("获取选项数据失败: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '获取数据失败'
    ]);
}
?>

<?php
/**
 * ============================================
 * 客户选项API接口
 * 说明：返回客户列表选项
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

    // 注释掉登录验证，选项数据不需要权限
    // if (!isset($_SESSION['user_id'])) {
    //     echo json_encode([
    //         'success' => false,
    //         'message' => '未登录或登录已过期'
    //     ]);
    //     return;
    // }

    // 查询客户列表
    $sql = "SELECT CustID, CustomerName, Phone, Address
            FROM tblCustomer
            WHERE Disc = 0
            ORDER BY CustomerName";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'customers' => $customers
        ]
    ]);

} catch (Exception $e) {
    error_log("获取客户选项失败: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '获取数据失败'
    ]);
}
?>

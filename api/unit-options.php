<?php
/**
 * ============================================
 * API: 单位选项接口
 * 说明：获取单位列表供前端下拉选择使用
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
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // 验证登录状态
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => '未登录或登录已过期'
        ]);
        exit;
    }

    // 获取数据库连接
    $conn = Database::getConnection();

    // 查询单位列表
    $sql = "SELECT UnitID, UnitCode, UnitName, UnitSymbol, SortOrder
            FROM tblUnits
            WHERE is_del = 0
            ORDER BY SortOrder ASC, UnitID ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'units' => $units
        ]
    ]);

} catch (Exception $e) {
    error_log("获取单位选项失败: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '获取单位失败: ' . $e->getMessage()
    ]);
}
?>

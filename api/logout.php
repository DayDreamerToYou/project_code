<?php
/**
 * ============================================
 * 登出接口
 * 说明：处理用户登出请求，销毁会话
 * ============================================
 */

// 定义访问常量
define('APP_ACCESS', true);

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * 处理登出请求
 */
try {
    // 开启Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 清空Session数据
    $_SESSION = array();

    // 删除Session Cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }

    // 销毁Session
    session_destroy();

    // 返回成功响应
    echo json_encode([
        'success' => true,
        'message' => '登出成功'
    ]);

} catch (Exception $e) {
    // 错误处理
    error_log("登出错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系统错误，请稍后重试'
    ]);
}
?>

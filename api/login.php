<?php
/**
 * ============================================
 * 登录接口
 * 说明：处理用户登录请求，验证用户名和密码
 * ============================================
 */

// 定义访问常量
define('APP_ACCESS', true);

// 引入数据库配置
require_once '../config/db.php';

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

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
    exit;
}

/**
 * 处理登录请求
 */
try {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);

    // 如果JSON解析失败，尝试从$_POST获取
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
    }

    // 验证必填字段
    if (empty($input['username']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '用户名和密码不能为空'
        ]);
        exit;
    }

    $username = trim($input['username']);
    $password = trim($input['password']);

    // 获取数据库连接
    $db = Database::getConnection();

    // 查询用户信息
    $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch();

    // 验证用户是否存在以及密码是否正确
    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '用户名或密码错误'
        ]);
        exit;
    }

    // 开启Session并配置cookie持久化（7天）
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }

    // 生成会话令牌（额外的安全措施）
    $token = bin2hex(random_bytes(32));

    // 设置Session数据
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['login_token'] = $token;
    $_SESSION['login_time'] = time();

    // 更新最后登录时间（可选）
    $updateStmt = $db->prepare("UPDATE users SET updated_at = NOW() WHERE id = :id");
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();

    // 返回成功响应
    echo json_encode([
        'success' => true,
        'message' => '登录成功',
        'data' => [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'token' => $token
        ]
    ]);

} catch (PDOException $e) {
    // 数据库错误
    error_log("登录错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系统错误，请稍后重试'
    ]);
} catch (Exception $e) {
    // 其他错误
    error_log("登录错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系统错误，请稍后重试'
    ]);
}
?>

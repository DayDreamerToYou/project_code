<?php
/**
 * ============================================
 * 表格操作接口
 * 说明：处理表格的增删改查操作
 * 支持：GET（查询）、POST（新增）、PUT（修改）、DELETE（删除）
 * ============================================
 */

// 定义访问常量
define('APP_ACCESS', true);

// 引入数据库配置
require_once '../config/db.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * 验证用户登录状态
 * @return int 用户ID，未登录返回false
 */
function checkAuth() {
    // 开启Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 检查Session是否存在用户ID
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

    // 获取请求方法
    $method = $_SERVER['REQUEST_METHOD'];

    // 获取数据库连接
    $db = Database::getConnection();

    /**
     * GET请求：查询数据列表
     */
    if ($method === 'GET') {
        // 获取查询参数
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $pageSize = isset($_GET['pageSize']) ? max(1, min(100, intval($_GET['pageSize']))) : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $offset = ($page - 1) * $pageSize;

        // 构建查询条件
        $where = "WHERE user_id = :user_id";
        $params = ['user_id' => $userId];

        // 如果有搜索关键词，添加搜索条件
        if ($search) {
            $where .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search OR department LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        // 查询总记录数
        $countStmt = $db->prepare("SELECT COUNT(*) as total FROM sample_data {$where}");
        foreach ($params as $key => $value) {
            $countStmt->bindValue(":{$key}", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        // 查询数据列表
        $sql = "SELECT id, name, email, phone, department, status, created_at, updated_at
                FROM sample_data
                {$where}
                ORDER BY id DESC
                LIMIT :offset, :pageSize";

        $stmt = $db->prepare($sql);

        // 绑定参数
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll();

        // 返回结果
        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => ceil($total / $pageSize)
            ]
        ]);
    }

    /**
     * POST请求：新增数据
     */
    elseif ($method === 'POST') {
        // 获取POST数据
        $input = json_decode(file_get_contents('php://input'), true);

        // 如果JSON解析失败，尝试从$_POST获取
        if (json_last_error() !== JSON_ERROR_NONE) {
            $input = $_POST;
        }

        // 验证必填字段
        if (empty($input['name'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '姓名不能为空'
            ]);
            exit;
        }

        // 准备SQL语句
        $sql = "INSERT INTO sample_data (user_id, name, email, phone, department, status)
                VALUES (:user_id, :name, :email, :phone, :department, :status)";

        $stmt = $db->prepare($sql);

        // 绑定参数
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':name', $input['name']);
        $stmt->bindParam(':email', $input['email']);
        $stmt->bindParam(':phone', $input['phone']);
        $stmt->bindParam(':department', $input['department']);
        $status = isset($input['status']) ? intval($input['status']) : 1;
        $stmt->bindParam(':status', $status);

        // 执行插入
        if ($stmt->execute()) {
            $newId = $db->lastInsertId();

            // 查询新插入的数据
            $selectStmt = $db->prepare("SELECT * FROM sample_data WHERE id = :id");
            $selectStmt->bindParam(':id', $newId);
            $selectStmt->execute();
            $newData = $selectStmt->fetch();

            echo json_encode([
                'success' => true,
                'message' => '添加成功',
                'data' => $newData
            ]);
        } else {
            throw new Exception('添加失败');
        }
    }

    /**
     * PUT请求：修改数据
     */
    elseif ($method === 'PUT') {
        // 获取PUT数据
        $input = json_decode(file_get_contents('php://input'), true);

        // 验证必填字段
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '记录ID不能为空'
            ]);
            exit;
        }

        if (empty($input['name'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '姓名不能为空'
            ]);
            exit;
        }

        // 准备SQL语句
        $sql = "UPDATE sample_data
                SET name = :name, email = :email, phone = :phone,
                    department = :department, status = :status
                WHERE id = :id AND user_id = :user_id";

        $stmt = $db->prepare($sql);

        // 绑定参数
        $stmt->bindParam(':id', $input['id']);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':name', $input['name']);
        $stmt->bindParam(':email', $input['email']);
        $stmt->bindParam(':phone', $input['phone']);
        $stmt->bindParam(':department', $input['department']);
        $status = isset($input['status']) ? intval($input['status']) : 1;
        $stmt->bindParam(':status', $status);

        // 执行更新
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                // 查询更新后的数据
                $selectStmt = $db->prepare("SELECT * FROM sample_data WHERE id = :id");
                $selectStmt->bindParam(':id', $input['id']);
                $selectStmt->execute();
                $updatedData = $selectStmt->fetch();

                echo json_encode([
                    'success' => true,
                    'message' => '更新成功',
                    'data' => $updatedData
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => '记录不存在或无权修改'
                ]);
            }
        } else {
            throw new Exception('更新失败');
        }
    }

    /**
     * DELETE请求：删除数据
     */
    elseif ($method === 'DELETE') {
        // 获取DELETE数据
        $input = json_decode(file_get_contents('php://input'), true);

        // 如果JSON解析失败，尝试从查询参数获取
        if (json_last_error() !== JSON_ERROR_NONE || empty($input['id'])) {
            $input = ['id' => $_GET['id'] ?? null];
        }

        // 验证必填字段
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '记录ID不能为空'
            ]);
            exit;
        }

        // 准备SQL语句
        $sql = "DELETE FROM sample_data WHERE id = :id AND user_id = :user_id";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $input['id']);
        $stmt->bindParam(':user_id', $userId);

        // 执行删除
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => '删除成功'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => '记录不存在或无权删除'
                ]);
            }
        } else {
            throw new Exception('删除失败');
        }
    }

    /**
     * 不支持的请求方法
     */
    else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => '不支持的请求方法'
        ]);
    }

} catch (PDOException $e) {
    // 数据库错误
    error_log("表格操作错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '数据库错误，请稍后重试'
    ]);
} catch (Exception $e) {
    // 其他错误
    error_log("表格操作错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系统错误，请稍后重试'
    ]);
}
?>

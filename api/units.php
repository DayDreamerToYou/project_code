<?php
/**
 * ============================================
 * API: 单位管理接口
 * 说明：管理单位配置的增删改查
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

    // 获取请求方法和动作
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // 根据请求方法和动作分发
    if ($method === 'GET') {
        if ($action === 'list') {
            handleGetList($conn);
        } elseif ($action === 'get') {
            handleGet($conn);
        } else {
            handleGetList($conn);  // 默认返回列表
        }
    } elseif ($method === 'POST') {
        handlePost($conn);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '不支持的请求方法'
        ]);
    }

} catch (Exception $e) {
    error_log("单位管理API错误: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '服务器错误，请稍后重试'
    ]);
}

/**
 * 获取单位列表
 */
function handleGetList($conn) {
    $sql = "SELECT UnitID, UnitCode, UnitName, UnitSymbol, SortOrder, IsActive
            FROM tblUnits
            WHERE is_del = 0
            ORDER BY SortOrder ASC, UnitID ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * 获取单个单位详情
 */
function handleGet($conn) {
    $unitId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($unitId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => '单位ID不能为空'
        ]);
        return;
    }

    $sql = "SELECT * FROM tblUnits WHERE UnitID = ? AND is_del = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$unitId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '单位不存在'
        ]);
    }
}

/**
 * 新增单位
 */
function handlePost($conn) {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $input = json_decode(file_get_contents('php://input'), true);

    // 根据action分发
    if ($action === 'delete') {
        handleDeleteInternal($conn, $input);
    } elseif ($action === 'edit') {
        handleEditInternal($conn, $input);
    } elseif ($action === 'add') {
        handleAddInternal($conn, $input);
    } else {
        handleAddInternal($conn, $input);
    }
}

/**
 * 内部函数：添加单位
 */
function handleAddInternal($conn, $input) {
    // 验证必填字段
    if (empty($input['UnitCode']) || empty($input['UnitName']) || empty($input['UnitSymbol'])) {
        echo json_encode([
            'success' => false,
            'message' => '请填写所有必填字段(单位代码、单位名称、单位符号)'
        ]);
        return;
    }

    // 检查单位代码是否已存在
    $checkSql = "SELECT UnitID FROM tblUnits WHERE UnitCode = ? AND is_del = 0";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$input['UnitCode']]);
    if ($checkStmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => '单位代码已存在，请使用其他代码'
        ]);
        return;
    }

    try {
        $sql = "INSERT INTO tblUnits (UnitCode, UnitName, UnitSymbol, SortOrder, IsActive)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $input['UnitCode'],
            $input['UnitName'],
            $input['UnitSymbol'],
            isset($input['SortOrder']) ? intval($input['SortOrder']) : 0,
            isset($input['IsActive']) ? intval($input['IsActive']) : 1
        ]);

        $unitId = $conn->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => '添加成功',
            'UnitID' => $unitId
        ]);

    } catch (Exception $e) {
        error_log("新增单位失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '添加失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 内部函数：编辑单位
 */
function handleEditInternal($conn, $input) {
    // 验证ID
    if (empty($input['UnitID'])) {
        echo json_encode([
            'success' => false,
            'message' => '单位ID不能为空'
        ]);
        return;
    }

    // 验证必填字段
    if (empty($input['UnitCode']) || empty($input['UnitName']) || empty($input['UnitSymbol'])) {
        echo json_encode([
            'success' => false,
            'message' => '请填写所有必填字段(单位代码、单位名称、单位符号)'
        ]);
        return;
    }

    try {
        $sql = "UPDATE tblUnits SET UnitCode = ?, UnitName = ?, UnitSymbol = ?, SortOrder = ?, IsActive = ?
                WHERE UnitID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $input['UnitCode'],
            $input['UnitName'],
            $input['UnitSymbol'],
            isset($input['SortOrder']) ? intval($input['SortOrder']) : 0,
            isset($input['IsActive']) ? intval($input['IsActive']) : 1,
            $input['UnitID']
        ]);

        echo json_encode([
            'success' => true,
            'message' => '更新成功'
        ]);

    } catch (Exception $e) {
        error_log("更新单位失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '更新失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 内部函数：删除单位
 */
function handleDeleteInternal($conn, $input) {
    // 支持id或UnitID字段
    $unitId = isset($input['id']) ? intval($input['id']) : (isset($input['UnitID']) ? intval($input['UnitID']) : 0);

    // 验证ID
    if ($unitId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => '单位ID不能为空'
        ]);
        return;
    }

    // 检查是否有数据在使用该单位
    $checkSql = "SELECT COUNT(*) as count FROM tblLandingDetail WHERE WeightUnitID = ? AND is_del = 0";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$unitId]);
    $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'message' => '该单位正在使用中，无法删除（已有 ' . $count . ' 条记录使用该单位）'
        ]);
        return;
    }

    try {
        $sql = "UPDATE tblUnits SET is_del = 1 WHERE UnitID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$unitId]);

        echo json_encode([
            'success' => true,
            'message' => '删除成功'
        ]);

    } catch (Exception $e) {
        error_log("删除单位失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '删除失败: ' . $e->getMessage()
        ]);
    }
}

?>

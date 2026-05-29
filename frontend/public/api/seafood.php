<?php
/**
 * ============================================
 * Seafood 数据库操作接口
 * 说明：处理 seafood 数据库的多表查询操作
 * 支持：GET（查询多表数据）、POST（新增/编辑/删除记录）
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
 * 获取表列表
 */
function getTableList() {
    $tables = [
        ['name' => 'tblStock', 'label' => '海鲜库存', 'icon' => '📦'],
        ['name' => 'tblSuppliers', 'label' => '供应商', 'icon' => '🏢'],
        ['name' => 'tblCustomer', 'label' => '客户', 'icon' => '👤'],
        ['name' => 'tblBoat', 'label' => '渔船', 'icon' => '🚤'],
        ['name' => 'tblBin', 'label' => '鱼箱', 'icon' => '📦'],
        ['name' => 'tblPort', 'label' => '港口', 'icon' => '⚓'],
        ['name' => 'tblFleet', 'label' => '舰队', 'icon' => '🛥️'],
        ['name' => 'tblFleetDetail', 'label' => '舰队详情', 'icon' => '📋'],
        ['name' => 'tblLanding', 'label' => '上岸记录', 'icon' => '📥'],
        ['name' => 'tblLandingDetail', 'label' => '上岸详情', 'icon' => '📝'],
        ['name' => 'tblPurchase', 'label' => '采购记录', 'icon' => '💰'],
        ['name' => 'tblPurchaseDetail', 'label' => '采购详情', 'icon' => '📋'],
        ['name' => 'tblSales', 'label' => '销售记录', 'icon' => '💵'],
        ['name' => 'tblSalesDetail', 'label' => '销售详情', 'icon' => '📝'],
    ];
    return $tables;
}

/**
 * 获取表数据
 */
function getTableData($db, $tableName, $page, $pageSize, $search) {
    $offset = ($page - 1) * $pageSize;

    // 构建查询条件
    $where = "";
    $params = [];

    if ($search) {
        // 获取表的字段信息
        $columns = $db->query("DESCRIBE {$tableName}")->fetchAll(PDO::FETCH_COLUMN);
        $searchConditions = [];
        foreach ($columns as $column) {
            // 排除不适合搜索的字段类型
            if ($column !== 'Disc' && strpos($column, 'ID') === false || $column === 'StockID') {
                $searchConditions[] = "{$column} LIKE :search";
            }
        }
        if (!empty($searchConditions)) {
            $where = "WHERE " . implode(" OR ", $searchConditions);
            $params['search'] = "%{$search}%";
        }
    }

    // 查询总记录数
    $countSql = "SELECT COUNT(*) as total FROM {$tableName} {$where}";
    $countStmt = $db->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue(":{$key}", $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];

    // 查询数据列表
    $dataSql = "SELECT * FROM {$tableName} {$where} LIMIT :offset, :pageSize";
    $stmt = $db->prepare($dataSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();

    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'pageSize' => $pageSize,
        'totalPages' => ceil($total / $pageSize)
    ];
}

/**
 * 新增记录
 */
function addRecord($db, $tableName, $data) {
    try {
        // 移除特殊字段
        unset($data['_original']);

        if (empty($data)) {
            return [
                'success' => false,
                'message' => '没有数据需要保存'
            ];
        }

        // 构建INSERT语句
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO {$tableName} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);

        // 执行插入
        $result = $stmt->execute(array_values($data));

        if ($result) {
            return [
                'success' => true,
                'message' => '添加成功',
                'insertId' => $db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => '添加失败'
            ];
        }
    } catch (PDOException $e) {
        error_log("添加记录错误: " . $e->getMessage());
        return [
            'success' => false,
            'message' => '数据库错误：' . $e->getMessage()
        ];
    }
}

/**
 * 编辑记录
 */
function editRecord($db, $tableName, $data) {
    try {
        // 获取原始记录数据用于定位
        $original = isset($data['_original']) ? $data['_original'] : null;
        unset($data['_original']);

        if (!$original || empty($data)) {
            return [
                'success' => false,
                'message' => '没有数据需要更新'
            ];
        }

        // 构建WHERE条件（使用原始记录的所有字段值）
        $whereConditions = [];
        $whereValues = [];
        foreach ($original as $key => $value) {
            $whereConditions[] = "{$key} = ?";
            $whereValues[] = $value;
        }

        // 构建SET语句（排除 create_time 和 update_time，这些字段由数据库自动管理）
        $setConditions = [];
        $setValues = [];
        $excludeFields = ['create_time', 'update_time', 'ID', 'LandingID', 'PurchaseID', 'SalesID'];
        foreach ($data as $key => $value) {
            // 跳过系统自动管理的字段和主键字段
            if (in_array($key, $excludeFields)) {
                continue;
            }
            $setConditions[] = "{$key} = ?";
            $setValues[] = $value;
        }

        $sql = "UPDATE {$tableName} SET " . implode(', ', $setConditions) . " WHERE " . implode(' AND ', $whereConditions);
        $stmt = $db->prepare($sql);

        // 合并参数值：先SET的值，再WHERE的值
        $params = array_merge($setValues, $whereValues);
        $result = $stmt->execute($params);

        if ($result) {
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                return [
                    'success' => true,
                    'message' => '更新成功',
                    'affectedRows' => $rowCount
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '未找到匹配的记录进行更新'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => '更新失败'
            ];
        }
    } catch (PDOException $e) {
        error_log("编辑记录错误: " . $e->getMessage());
        return [
            'success' => false,
            'message' => '数据库错误：' . $e->getMessage()
        ];
    }
}

/**
 * 删除记录
 */
function deleteRecord($db, $tableName, $data) {
    try {
        if (empty($data)) {
            return [
                'success' => false,
                'message' => '没有指定要删除的记录'
            ];
        }

        // 构建WHERE条件（使用记录的所有字段值）
        $whereConditions = [];
        $whereValues = [];
        foreach ($data as $key => $value) {
            $whereConditions[] = "{$key} = ?";
            $whereValues[] = $value;
        }

        $sql = "DELETE FROM {$tableName} WHERE " . implode(' AND ', $whereConditions);
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($whereValues);

        if ($result) {
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                return [
                    'success' => true,
                    'message' => '删除成功',
                    'affectedRows' => $rowCount
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '未找到匹配的记录进行删除'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => '删除失败'
            ];
        }
    } catch (PDOException $e) {
        error_log("删除记录错误: " . $e->getMessage());
        return [
            'success' => false,
            'message' => '数据库错误：' . $e->getMessage()
        ];
    }
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

    // 获取请求方法
    $method = $_SERVER['REQUEST_METHOD'];

    // 获取表名参数
    $table = isset($_GET['table']) ? $_GET['table'] : '';

    /**
     * GET请求：查询数据
     */
    if ($method === 'GET') {
        // 如果没有指定表，返回表列表
        if (empty($table)) {
            echo json_encode([
                'success' => true,
                'tables' => getTableList()
            ]);
            exit;
        }

        // 验证表名
        $validTables = array_column(getTableList(), 'name');
        if (!in_array($table, $validTables)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '无效的表名'
            ]);
            exit;
        }

        // 获取查询参数
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $pageSize = isset($_GET['pageSize']) ? max(1, min(100, intval($_GET['pageSize']))) : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        // 获取表数据
        $result = getTableData($db, $table, $page, $pageSize, $search);

        // 返回结果
        echo json_encode([
            'success' => true,
            'table' => $table,
            'data' => $result['data'],
            'pagination' => [
                'page' => $result['page'],
                'pageSize' => $result['pageSize'],
                'total' => $result['total'],
                'totalPages' => $result['totalPages']
            ]
        ]);
    }

    /**
     * POST请求：增删改操作
     */
    elseif ($method === 'POST') {
        // 获取请求数据
        $input = file_get_contents('php://input');
        $requestData = json_decode($input, true);

        if (!$requestData || !isset($requestData['action'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '无效的请求数据'
            ]);
            exit;
        }

        $action = $requestData['action'];
        $table = isset($requestData['table']) ? $requestData['table'] : '';
        $data = isset($requestData['data']) ? $requestData['data'] : [];

        // 验证表名
        $validTables = array_column(getTableList(), 'name');
        if (!in_array($table, $validTables)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '无效的表名'
            ]);
            exit;
        }

        // 根据操作类型执行相应操作
        switch ($action) {
            case 'add':
                $result = addRecord($db, $table, $data);
                break;

            case 'edit':
                $result = editRecord($db, $table, $data);
                break;

            case 'delete':
                $result = deleteRecord($db, $table, $data);
                break;

            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => '无效的操作类型'
                ]);
                exit;
        }

        // 返回结果
        echo json_encode($result);
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
    error_log("Seafood操作错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '数据库错误：' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // 其他错误
    error_log("Seafood操作错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系统错误，请稍后重试'
    ]);
}
?>

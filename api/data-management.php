<?php
/**
 * ============================================
 * 数据管理API接口
 * 说明：处理基础数据的增删改查
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

// 获取请求方法和操作类型
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$table = $_GET['table'] ?? '';

// 允许的表名
$allowedTables = ['suppliers', 'port', 'boat', 'stock', 'bin'];
$tableMapping = [
    'suppliers' => [
        'table' => 'tblSuppliers',
        'idField' => 'SupplierID',
        'nameField' => 'SupplierName',
        'fields' => ['SupplierName', 'Address', 'Person', 'Phone', 'Email', 'GST', 'QRN', 'FleetID']
    ],
    'port' => [
        'table' => 'tblPort',
        'idField' => 'PortID',
        'nameField' => 'Port',
        'fields' => ['Port']
    ],
    'boat' => [
        'table' => 'tblBoat',
        'idField' => 'BoatID',
        'nameField' => 'BoatName',
        'fields' => ['BoatNo', 'BoatName', 'Person']
    ],
    'stock' => [
        'table' => 'tblStock',
        'idField' => 'StockID',
        'nameField' => 'Stock',
        'fields' => ['Stock', 'Description', 'State', 'Area', 'Price', 'Conversion', 'ScientificName']
    ],
    'bin' => [
        'table' => 'tblBin',
        'idField' => 'BinID',
        'nameField' => 'BinName',
        'fields' => ['BinName', 'B-Weight']
    ]
];

try {
    // 验证表名
    if (!in_array($table, $allowedTables)) {
        throw new Exception('无效的表名');
    }

    $tableConfig = $tableMapping[$table];
    $tableName = $tableConfig['table'];
    $idField = $tableConfig['idField'];

    // 获取数据库连接
    $conn = Database::getConnection();

    // 根据操作类型处理
    switch ($action) {
        case 'list':
            // 获取列表
            getList($conn, $tableName, $tableConfig);
            break;

        case 'get':
            // 获取单条记录
            getOne($conn, $tableName, $idField);
            break;

        case 'add':
            // 添加记录
            addRecord($conn, $tableName, $tableConfig);
            break;

        case 'update':
            // 更新记录
            updateRecord($conn, $tableName, $idField, $tableConfig);
            break;

        case 'delete':
            // 删除记录
            deleteRecord($conn, $tableName, $idField);
            break;

        default:
            throw new Exception('无效的操作');
    }

} catch (Exception $e) {
    error_log("数据管理错误: " . $e->getMessage());
    echo json_encode([
        'success' => false
    ]);
}

/**
 * 获取列表
 */
function getList($conn, $tableName, $tableConfig) {
    $idField = $tableConfig['idField'];
    $fields = $tableConfig['fields'];

    // 特殊处理供应商表 - 需要关联船队表
    if ($tableName === 'tblSuppliers') {
        $selectFields = 's.' . $idField . ', s.' . implode(', s.', $fields) . ', f.FleetName';

        // 对于不同的表添加Disc字段
        $selectFields .= ', s.Disc';

        // 构建WHERE条件 - 所有表都支持软删除
        $whereClause = ' WHERE s.is_del = 0';

        // 获取数据
        $sql = "SELECT $selectFields
                FROM $tableName s
                LEFT JOIN tblFleet f ON s.FleetID = f.FleetID AND f.is_del = 0
                $whereClause
                ORDER BY s.$idField DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM $tableName s WHERE s.is_del = 0";
        $stmt = $conn->prepare($countSql);
        $stmt->execute();
        $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $countResult['total'];
    } else {
        // 构建查询字段 - 对包含连字符的字段名使用反引号
        $selectFields = $idField;
        foreach ($fields as $field) {
            // 检查字段名是否包含特殊字符（如连字符）
            if (preg_match('/[^a-zA-Z0-9_]/', $field)) {
                $selectFields .= ', `' . $field . '`';
            } else {
                $selectFields .= ', ' . $field;
            }
        }

        // 对于不同的表添加Disc字段
        if (in_array($tableName, ['tblSuppliers', 'tblPort', 'tblBoat', 'tblBin'])) {
            $selectFields .= ', Disc';
        }

        // 构建WHERE条件 - 所有表都支持软删除
        $whereClause = ' WHERE is_del = 0';

        // 先获取总数
        $countSql = "SELECT COUNT(*) as total FROM $tableName$whereClause";
        $stmt = $conn->prepare($countSql);
        $stmt->execute();
        $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $countResult['total'];

        // 获取数据
        $sql = "SELECT $selectFields FROM $tableName$whereClause ORDER BY $idField DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => $total
    ]);
}

/**
 * 获取单条记录
 */
function getOne($conn, $tableName, $idField) {
    $id = $_GET['id'] ?? 0;

    $sql = "SELECT * FROM $tableName WHERE $idField = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '记录不存在'
        ]);
    }
}

/**
 * 添加记录
 */
function addRecord($conn, $tableName, $tableConfig) {
    $fields = $tableConfig['fields'];
    $idField = $tableConfig['idField'];

    // 获取POST数据
    $postData = json_decode(file_get_contents('php://input'), true);

    // 构建字段和值
    $fieldList = [];
    $valueList = [];
    $paramList = [];

    // 处理主键 - 只有tblStock的主键需要手动输入
    if ($tableName === 'tblStock') {
        if (empty($postData[$idField])) {
            throw new Exception('StockID不能为空');
        }
        $fieldList[] = $idField;
        $valueList[] = ":$idField";
        $paramList[$idField] = $postData[$idField];
    }
    // 其他表的主键都是AUTO_INCREMENT，不需要处理

    foreach ($fields as $field) {
        $value = $postData[$field] ?? null;
        // 对包含特殊字符的字段名使用反引号
        if (preg_match('/[^a-zA-Z0-9_]/', $field)) {
            $fieldList[] = "`$field`";
            // 为特殊字段创建安全的参数名（替换连字符为下划线）
            $safeParam = str_replace(['-', ' ', '.'], '_', $field);
            $valueList[] = ":$safeParam";
            $paramList[$safeParam] = $value;
        } else {
            $fieldList[] = $field;
            $valueList[] = ":$field";
            $paramList[$field] = $value;
        }
    }

    // 对于有Disc字段的表，添加默认值
    if (in_array($tableName, ['tblSuppliers', 'tblPort', 'tblBoat', 'tblBin'])) {
        $fieldList[] = 'Disc';
        $valueList[] = ':Disc';
        $paramList['Disc'] = 0;
    }

    $sql = "INSERT INTO $tableName (" . implode(', ', $fieldList) . ")
            VALUES (" . implode(', ', $valueList) . ")";

    $stmt = $conn->prepare($sql);

    foreach ($paramList as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $stmt->execute();

    echo json_encode([
        'success' => true,
        'id' => $paramList[$idField] ?? $conn->lastInsertId()
    ]);
}

/**
 * 更新记录
 */
function updateRecord($conn, $tableName, $idField, $tableConfig) {
    $fields = $tableConfig['fields'];
    $postData = json_decode(file_get_contents('php://input'), true);
    $id = $postData[$idField] ?? 0;

    if (!$id) {
        error_log("更新失败: 缺少记录ID, postData=" . json_encode($postData));
        throw new Exception('缺少记录ID');
    }

    // 构建更新语句 - 跳过主键字段和系统自动管理的字段
    $updateList = [];
    $paramList = [];
    $excludeFields = [$idField, 'create_time', 'update_time', 'is_del'];
    foreach ($fields as $field) {
        // 跳过系统字段，不放在SET子句中
        if (in_array($field, $excludeFields)) {
            continue;
        }
        // 对包含特殊字符的字段名使用反引号和安全的参数名
        if (preg_match('/[^a-zA-Z0-9_]/', $field)) {
            $safeParam = str_replace(['-', ' ', '.'], '_', $field);
            $updateList[] = "`$field` = :$safeParam";
            $paramList[$safeParam] = $postData[$field] ?? null;
        } else {
            $updateList[] = "$field = :$field";
            $paramList[$field] = $postData[$field] ?? null;
        }
    }

    $sql = "UPDATE $tableName SET " . implode(', ', $updateList) . " WHERE $idField = :id";

    error_log("更新SQL: $sql, ID: $id, postData=" . json_encode($postData));

    $stmt = $conn->prepare($sql);

    // 绑定参数
    foreach ($paramList as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':id', $id, PDO::PARAM_STR);

    try {
        $stmt->execute();
        $affected = $stmt->rowCount();
        error_log("更新成功: 影响行数=$affected");

        echo json_encode([
            'success' => true,
            'affected' => $affected
        ]);
    } catch (PDOException $e) {
        error_log("更新失败: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * 删除记录
 */
function deleteRecord($conn, $tableName, $idField) {
    $postData = json_decode(file_get_contents('php://input'), true);
    $id = $postData['id'] ?? 0;

    if (!$id) {
        throw new Exception('缺少记录ID');
    }

    // 所有表都使用软删除
    $sql = "UPDATE $tableName SET is_del = 1 WHERE $idField = :id";

    $stmt = $conn->prepare($sql);
    // 根据表名决定ID类型
    if ($tableName === 'tblStock') {
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
    } else {
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    }
    $stmt->execute();

    echo json_encode([
        'success' => true
    ]);
}
?>

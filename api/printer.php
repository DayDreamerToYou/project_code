<?php
/**
 * ============================================
 * 打印机管理接口
 * 说明：处理打印机配置的增删改查和网络打印
 * 支持：ESC/POS 热敏打印机网络打印
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
 * 发送数据到网络打印机
 * @param string $ip 打印机IP地址
 * @param int $port 打印机端口
 * @param string $data 打印数据（ESC/POS指令）
 * @return array ['success' => bool, 'message' => string]
 */
function sendToPrinter($ip, $port, $data) {
    try {
        // 创建socket连接
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($socket === false) {
            return [
                'success' => false,
                'message' => '无法创建socket: ' . socket_strerror(socket_last_error())
            ];
        }

        // 设置超时时间
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

        // 连接打印机
        $result = @socket_connect($socket, $ip, $port);

        if ($result === false) {
            socket_close($socket);
            return [
                'success' => false,
                'message' => "无法连接到打印机 {$ip}:{$port} - " . socket_strerror(socket_last_error())
            ];
        }

        // 发送打印数据
        @socket_write($socket, $data, strlen($data));

        // 关闭连接
        socket_close($socket);

        return [
            'success' => true,
            'message' => '打印任务已发送'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => '打印异常: ' . $e->getMessage()
        ];
    }
}

/**
 * 生成ESC/POS打印指令（到货记录）
 * @param array $landing 到货记录数据
 * @return string ESC/POS指令数据
 */
function generateLandingPrintData($landing) {
    // ESC/POS 初始化和格式化指令
    $data = "\x1B\x40";                      // 初始化打印机
    $data .= "\x1B\x61\x01";                 // 居中对齐
    $data .= "\x1D\x21\x11";                 // 双倍宽高
    $data .= "FISHERY LANDING RECORD\n";     // 标题
    $data .= "\x1D\x21\x00";                 // 正常字体
    $data .= "\x1B\x61\x00";                 // 左对齐
    $data .= "================================\n";

    // 日期
    $data .= "\x1B\x45\x01";                 // 加粗开启
    $data .= "Date: ";
    $data .= "\x1B\x45\x00";                 // 加粗关闭
    $data .= $landing['date'] . "\n\n";

    // 供应商
    $data .= "\x1B\x45\x01";
    $data .= "Supplier: ";
    $data .= "\x1B\x45\x00";
    $data .= $landing['supplier'] . "\n";

    // 船只
    $data .= "\x1B\x45\x01";
    $data .= "Boat: ";
    $data .= "\x1B\x45\x00";
    $data .= $landing['boat'] . "\n";

    // 地点
    $data .= "\x1B\x45\x01";
    $data .= "Location: ";
    $data .= "\x1B\x45\x00";
    $data .= $landing['location'] . "\n\n";

    $data .= "--------------------------------\n";
    $data .= "\x1B\x45\x01";
    $data .= "FISH TYPE      QTY    PRICE    TOTAL\n";
    $data .= "\x1B\x45\x00";
    $data .= "--------------------------------\n";

    // 鱼种明细
    if (!empty($landing['details']) && is_array($landing['details'])) {
        foreach ($landing['details'] as $detail) {
            $fishType = $detail['fish_type'] ?? '';
            $quantity = $detail['quantity'] ?? 0;
            $unitPrice = $detail['unit_price'] ?? 0;
            $total = $quantity * $unitPrice;

            // 格式化输出
            $data .= sprintf("%-14s %5s %8.2f %8.2f\n",
                mb_substr($fishType, 0, 14, 'UTF-8'),
                number_format($quantity),
                $unitPrice,
                $total
            );
        }
    }

    $data .= "--------------------------------\n";

    // 总计
    $totalAmount = 0;
    if (!empty($landing['details']) && is_array($landing['details'])) {
        foreach ($landing['details'] as $detail) {
            $totalAmount += ($detail['quantity'] ?? 0) * ($detail['unit_price'] ?? 0);
        }
    }

    $data .= "\x1D\x21\x11";                 // 双倍宽高
    $data .= sprintf("TOTAL: \$%.2f\n", $totalAmount);
    $data .= "\x1D\x21\x00";                 // 正常字体

    $data .= "\n\n";

    // 切纸指令
    $data .= "\x1D\x56\x00";                 // 切纸并退纸

    return $data;
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

    // 获取请求方法和action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $input = json_decode(file_get_contents('php://input'), true);

    // 如果JSON解析失败，尝试从$_POST获取
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
    }

    // 获取数据库连接
    $db = Database::getConnection();

    /**
     * 获取打印机列表
     */
    if ($method === 'GET' && $action === 'list') {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $pageSize = isset($_GET['pageSize']) ? max(1, min(100, intval($_GET['pageSize']))) : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $offset = ($page - 1) * $pageSize;

        // 构建查询条件
        $where = "WHERE 1=1";
        $params = [];

        if ($search) {
            $where .= " AND (printer_name LIKE :search OR printer_ip LIKE :search OR description LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        // 查询总记录数
        $countSql = "SELECT COUNT(*) as total FROM tblPrinters {$where}";
        $countStmt = $db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(":{$key}", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        // 查询数据列表
        $sql = "SELECT * FROM tblPrinters {$where} ORDER BY is_default DESC, id DESC LIMIT :offset, :pageSize";
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();

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
     * 获取单个打印机详情
     */
    elseif ($method === 'GET' && $action === 'get') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机ID不能为空']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM tblPrinters WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $printer = $stmt->fetch();

        if ($printer) {
            echo json_encode(['success' => true, 'data' => $printer]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '打印机不存在']);
        }
    }

    /**
     * 添加打印机
     */
    elseif ($method === 'POST' && $action === 'add') {
        // 验证必填字段
        if (empty($input['printer_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机名称不能为空']);
            exit;
        }
        if (empty($input['printer_ip'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机IP地址不能为空']);
            exit;
        }
        if (empty($input['printer_port'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机端口不能为空']);
            exit;
        }

        // 验证IP格式
        if (!filter_var($input['printer_ip'], FILTER_VALIDATE_IP)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'IP地址格式不正确']);
            exit;
        }

        // 验证端口范围
        $port = intval($input['printer_port']);
        if ($port < 1 || $port > 65535) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '端口范围必须在1-65535之间']);
            exit;
        }

        // 检查是否设置为默认打印机
        $isDefault = isset($input['is_default']) ? intval($input['is_default']) : 0;

        // 如果设置为默认打印机，先取消其他默认打印机
        if ($isDefault) {
            $db->exec("UPDATE tblPrinters SET is_default = 0 WHERE is_default = 1");
        }

        // 插入打印机
        $sql = "INSERT INTO tblPrinters (printer_name, printer_ip, printer_port, printer_type, is_default, status, description)
                VALUES (:printer_name, :printer_ip, :printer_port, :printer_type, :is_default, :status, :description)";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':printer_name', $input['printer_name']);
        $stmt->bindParam(':printer_ip', $input['printer_ip']);
        $stmt->bindParam(':printer_port', $port);
        $stmt->bindParam(':printer_type', $input['printer_type']);
        $stmt->bindParam(':is_default', $isDefault);
        $status = isset($input['status']) ? intval($input['status']) : 1;
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':description', $input['description']);

        if ($stmt->execute()) {
            $newId = $db->lastInsertId();
            $selectStmt = $db->prepare("SELECT * FROM tblPrinters WHERE id = :id");
            $selectStmt->bindParam(':id', $newId);
            $selectStmt->execute();
            $newData = $selectStmt->fetch();

            echo json_encode(['success' => true, 'message' => '添加成功', 'data' => $newData]);
        } else {
            throw new Exception('添加失败');
        }
    }

    /**
     * 编辑打印机
     */
    elseif ($method === 'POST' && $action === 'edit') {
        // 验证必填字段
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机ID不能为空']);
            exit;
        }
        if (empty($input['printer_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机名称不能为空']);
            exit;
        }
        if (empty($input['printer_ip'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机IP地址不能为空']);
            exit;
        }
        if (empty($input['printer_port'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机端口不能为空']);
            exit;
        }

        // 验证IP格式
        if (!filter_var($input['printer_ip'], FILTER_VALIDATE_IP)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'IP地址格式不正确']);
            exit;
        }

        // 验证端口范围
        $port = intval($input['printer_port']);
        if ($port < 1 || $port > 65535) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '端口范围必须在1-65535之间']);
            exit;
        }

        // 检查是否设置为默认打印机
        $isDefault = isset($input['is_default']) ? intval($input['is_default']) : 0;

        // 如果设置为默认打印机，先取消其他默认打印机
        if ($isDefault) {
            $db->exec("UPDATE tblPrinters SET is_default = 0 WHERE is_default = 1 AND id != " . intval($input['id']));
        }

        // 更新打印机
        $sql = "UPDATE tblPrinters
                SET printer_name = :printer_name, printer_ip = :printer_ip, printer_port = :printer_port,
                    printer_type = :printer_type, is_default = :is_default, status = :status, description = :description
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $input['id']);
        $stmt->bindParam(':printer_name', $input['printer_name']);
        $stmt->bindParam(':printer_ip', $input['printer_ip']);
        $stmt->bindParam(':printer_port', $port);
        $stmt->bindParam(':printer_type', $input['printer_type']);
        $stmt->bindParam(':is_default', $isDefault);
        $status = isset($input['status']) ? intval($input['status']) : 1;
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':description', $input['description']);

        if ($stmt->execute()) {
            $selectStmt = $db->prepare("SELECT * FROM tblPrinters WHERE id = :id");
            $selectStmt->bindParam(':id', $input['id']);
            $selectStmt->execute();
            $updatedData = $selectStmt->fetch();

            echo json_encode(['success' => true, 'message' => '更新成功', 'data' => $updatedData]);
        } else {
            throw new Exception('更新失败');
        }
    }

    /**
     * 删除打印机
     */
    elseif ($method === 'POST' && $action === 'delete') {
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机ID不能为空']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM tblPrinters WHERE id = :id");
        $stmt->bindParam(':id', $input['id']);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '删除成功']);
        } else {
            throw new Exception('删除失败');
        }
    }

    /**
     * 设置默认打印机
     */
    elseif ($method === 'POST' && $action === 'setDefault') {
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机ID不能为空']);
            exit;
        }

        // 取消所有默认打印机
        $db->exec("UPDATE tblPrinters SET is_default = 0");

        // 设置新的默认打印机
        $stmt = $db->prepare("UPDATE tblPrinters SET is_default = 1 WHERE id = :id");
        $stmt->bindParam(':id', $input['id']);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '默认打印机设置成功']);
        } else {
            throw new Exception('设置失败');
        }
    }

    /**
     * 测试打印机连接
     */
    elseif ($method === 'POST' && $action === 'test') {
        if (empty($input['printer_ip'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机IP不能为空']);
            exit;
        }
        if (empty($input['printer_port'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '打印机端口不能为空']);
            exit;
        }

        $port = intval($input['printer_port']);

        // 发送测试打印
        $testData = "\x1B\x40";                      // 初始化
        $testData .= "\x1B\x61\x01";                 // 居中
        $testData .= "\x1D\x21\x11";                 // 双倍宽高
        $testData .= "TEST PRINT\n";                 // 测试文字
        $testData .= "\x1D\x21\x00";                 // 正常字体
        $testData .= "Printer OK!\n";                // 状态
        $testData .= "\x1D\x56\x00";                 // 切纸

        $result = sendToPrinter($input['printer_ip'], $port, $testData);

        echo json_encode($result);
    }

    /**
     * 网络打印到货记录
     */
    elseif ($method === 'POST' && $action === 'printLanding') {
        if (empty($input['landing_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '到货记录ID不能为空']);
            exit;
        }

        // 获取打印机ID
        $printerId = isset($input['printer_id']) ? intval($input['printer_id']) : 0;

        // 如果没有指定打印机，获取默认打印机
        if (!$printerId) {
            $stmt = $db->prepare("SELECT * FROM tblPrinters WHERE is_default = 1 AND status = 1 LIMIT 1");
            $stmt->execute();
            $printer = $stmt->fetch();

            if (!$printer) {
                // 如果没有默认打印机，获取第一个启用状态的打印机
                $stmt = $db->prepare("SELECT * FROM tblPrinters WHERE status = 1 ORDER BY id LIMIT 1");
                $stmt->execute();
                $printer = $stmt->fetch();
            }

            if (!$printer) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '没有可用的打印机，请先在数据管理中配置打印机']);
                exit;
            }
        } else {
            $stmt = $db->prepare("SELECT * FROM tblPrinters WHERE id = :id AND status = 1");
            $stmt->bindParam(':id', $printerId);
            $stmt->execute();
            $printer = $stmt->fetch();

            if (!$printer) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => '指定的打印机不存在或已禁用']);
                exit;
            }
        }

        // 获取到货记录
        $stmt = $db->prepare("SELECT lr.*,
            (SELECT JSON_ARRAYAGG(JSON_OBJECT('fish_type', fish_type, 'quantity', quantity, 'unit_price', unit_price))
             FROM landing_records_detail WHERE landing_id = lr.id) as details
            FROM landing_records lr WHERE lr.id = :id");
        $stmt->bindParam(':id', $input['landing_id']);
        $stmt->execute();
        $landing = $stmt->fetch();

        if (!$landing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '到货记录不存在']);
            exit;
        }

        // 解析details JSON
        if (!empty($landing['details'])) {
            $landing['details'] = json_decode($landing['details'], true);
        }

        // 生成打印数据
        $printData = generateLandingPrintData($landing);

        // 发送到打印机
        $result = sendToPrinter($printer['printer_ip'], $printer['printer_port'], $printData);

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => "已通过打印机 [{$printer['printer_name']}] 打印",
                'printer' => $printer['printer_name']
            ]);
        } else {
            echo json_encode($result);
        }
    }

    /**
     * 不支持的请求
     */
    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '不支持的请求']);
    }

} catch (PDOException $e) {
    error_log("打印机操作错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '数据库错误，请稍后重试']);
} catch (Exception $e) {
    error_log("打印机操作错误: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '系统错误，请稍后重试']);
}
?>

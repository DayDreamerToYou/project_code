<?php
/**
 * ============================================
 * 船舶管理 API
 * 说明：处理船舶信息的增删改查操作，包括船队关联
 * ============================================
 */

// 定义访问常量
if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}

session_start();
require_once '../config/db.php';

// 设置JSON响应头
header('Content-Type: application/json');

// 检查用户登录状态
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 获取请求操作
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// 获取数据库连接
$db = Database::getConnection();

try {
    switch ($action) {
        case 'list':
            // 获取船只列表
            if ($method === 'GET') {
                $stmt = $db->prepare("
                    SELECT b.BoatID, b.BoatNo, b.BoatName, b.Person,
                           GROUP_CONCAT(DISTINCT f.FleetName SEPARATOR ', ') as FleetNames
                    FROM tblBoat b
                    LEFT JOIN tblFleetDetail fd ON b.BoatID = fd.BoatID AND fd.is_del = 0
                    LEFT JOIN tblFleet f ON fd.FleetID = f.FleetID AND f.is_del = 0
                    WHERE b.is_del = 0
                    GROUP BY b.BoatID, b.BoatNo, b.BoatName, b.Person
                    ORDER BY b.BoatID
                ");
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        case 'add':
            // 添加船只
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);

                if (empty($input['BoatNo']) || empty($input['BoatName'])) {
                    echo json_encode(['success' => false, 'error' => 'BoatNo and BoatName are required']);
                    exit;
                }

                $stmt = $db->prepare("INSERT INTO tblBoat (BoatNo, BoatName, Person) VALUES (?, ?, ?)");
                $result = $stmt->execute([
                    $input['BoatNo'],
                    $input['BoatName'],
                    $input['Person'] ?? null
                ]);

                if ($result) {
                    $boatId = $db->lastInsertId();
                    // 关联船队
                    if (!empty($input['FleetIDs']) && is_array($input['FleetIDs'])) {
                        foreach ($input['FleetIDs'] as $fleetId) {
                            $stmtDetail = $db->prepare("INSERT INTO tblFleetDetail (FleetID, BoatID) VALUES (?, ?)");
                            $stmtDetail->execute([$fleetId, $boatId]);
                        }
                    }
                    echo json_encode(['success' => true, 'message' => 'Boat added successfully', 'BoatID' => $boatId]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to add boat']);
                }
            }
            break;

        case 'update':
            // 更新船只
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);

                if (empty($input['BoatID']) || empty($input['BoatNo']) || empty($input['BoatName'])) {
                    echo json_encode(['success' => false, 'error' => 'BoatID, BoatNo and BoatName are required']);
                    exit;
                }

                $stmt = $db->prepare("UPDATE tblBoat SET BoatNo = ?, BoatName = ?, Person = ? WHERE BoatID = ?");
                $result = $stmt->execute([
                    $input['BoatNo'],
                    $input['BoatName'],
                    $input['Person'] ?? null,
                    $input['BoatID']
                ]);

                if ($result) {
                    // 更新关联的船队
                    if (isset($input['FleetIDs']) && is_array($input['FleetIDs'])) {
                        // 软删除旧的关联
                        $stmtDelete = $db->prepare("UPDATE tblFleetDetail SET is_del = 1 WHERE BoatID = ?");
                        $stmtDelete->execute([$input['BoatID']]);

                        // 添加新的关联
                        foreach ($input['FleetIDs'] as $fleetId) {
                            // 检查是否已存在
                            $stmtCheck = $db->prepare("SELECT FleetDetailID FROM tblFleetDetail WHERE BoatID = ? AND FleetID = ? AND is_del = 1");
                            $stmtCheck->execute([$input['BoatID'], $fleetId]);
                            $existing = $stmtCheck->fetch();

                            if ($existing) {
                                // 恢复已删除的记录
                                $stmtRestore = $db->prepare("UPDATE tblFleetDetail SET is_del = 0 WHERE FleetDetailID = ?");
                                $stmtRestore->execute([$existing['FleetDetailID']]);
                            } else {
                                // 插入新记录
                                $stmtDetail = $db->prepare("INSERT INTO tblFleetDetail (FleetID, BoatID) VALUES (?, ?)");
                                $stmtDetail->execute([$fleetId, $input['BoatID']]);
                            }
                        }
                    }

                    echo json_encode(['success' => true, 'message' => 'Boat updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update boat']);
                }
            }
            break;

        case 'delete':
            // 删除船只（软删除）
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $boatId = $input['id'] ?? 0;

                if (!$boatId) {
                    echo json_encode(['success' => false, 'error' => 'BoatID is required']);
                    exit;
                }

                try {
                    // 开始事务
                    $db->beginTransaction();

                    // 软删除船只
                    $stmt = $db->prepare("UPDATE tblBoat SET is_del = 1 WHERE BoatID = ?");
                    $result = $stmt->execute([$boatId]);

                    if (!$result) {
                        throw new Exception('Failed to delete boat');
                    }

                    // 软删除关联的船队记录
                    $stmtDetail = $db->prepare("UPDATE tblFleetDetail SET is_del = 1 WHERE BoatID = ?");
                    $stmtDetail->execute([$boatId]);

                    // 提交事务
                    $db->commit();

                    echo json_encode(['success' => true, 'message' => 'Boat and fleet associations deleted successfully']);
                } catch (Exception $e) {
                    // 回滚事务
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log("Boat Delete Error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => 'Failed to delete boat: ' . $e->getMessage()]);
                }
            }
            break;

        case 'fleets':
            // 获取船只关联的船队
            if ($method === 'GET') {
                $boatId = $_GET['boatId'] ?? 0;

                if (!$boatId) {
                    echo json_encode(['success' => false, 'error' => 'BoatID is required']);
                    exit;
                }

                $stmt = $db->prepare("
                    SELECT f.FleetID, f.FleetName
                    FROM tblFleet f
                    INNER JOIN tblFleetDetail fd ON f.FleetID = fd.FleetID
                    WHERE fd.BoatID = ? AND fd.is_del = 0 AND f.is_del = 0
                    ORDER BY f.FleetID
                ");
                $stmt->execute([$boatId]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        case 'available-boats':
            // 获取所有可用船只（用于船队关联）
            if ($method === 'GET') {
                $stmt = $db->prepare("
                    SELECT BoatID, BoatNo, BoatName
                    FROM tblBoat
                    WHERE is_del = 0
                    ORDER BY BoatID
                ");
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        case 'available-fleets':
            // 获取所有可用船队（用于船只关联）
            if ($method === 'GET') {
                $stmt = $db->prepare("
                    SELECT FleetID, FleetName
                    FROM tblFleet
                    WHERE is_del = 0
                    ORDER BY FleetID
                ");
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log("Boat Management API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>

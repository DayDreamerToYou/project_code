<?php
/**
 * ============================================
 * 船队管理 API
 * 说明：处理船队信息的增删改查操作
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
            // 获取船队列表
            if ($method === 'GET') {
                $stmt = $db->prepare("
                    SELECT f.FleetID, f.FleetName,
                           COUNT(DISTINCT fd.BoatID) as BoatCount,
                           COUNT(DISTINCT s.SupplierID) as SupplierCount
                    FROM tblFleet f
                    LEFT JOIN tblFleetDetail fd ON f.FleetID = fd.FleetID AND fd.is_del = 0
                    LEFT JOIN tblSuppliers s ON f.FleetID = s.FleetID AND s.is_del = 0
                    WHERE f.is_del = 0
                    GROUP BY f.FleetID, f.FleetName
                    ORDER BY f.FleetID
                ");
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        case 'add':
            // 添加船队
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);

                if (empty($input['FleetName'])) {
                    echo json_encode(['success' => false, 'error' => 'Fleet name is required']);
                    exit;
                }

                $stmt = $db->prepare("INSERT INTO tblFleet (FleetName) VALUES (?)");
                $result = $stmt->execute([$input['FleetName']]);

                if ($result) {
                    $fleetId = $db->lastInsertId();
                    // 关联船只
                    if (!empty($input['BoatIDs']) && is_array($input['BoatIDs'])) {
                        foreach ($input['BoatIDs'] as $boatId) {
                            $stmtDetail = $db->prepare("INSERT INTO tblFleetDetail (FleetID, BoatID) VALUES (?, ?)");
                            $stmtDetail->execute([$fleetId, $boatId]);
                        }
                    }
                    echo json_encode(['success' => true, 'message' => 'Fleet added successfully', 'FleetID' => $fleetId]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to add fleet']);
                }
            }
            break;

        case 'update':
            // 更新船队
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);

                if (empty($input['FleetID']) || empty($input['FleetName'])) {
                    echo json_encode(['success' => false, 'error' => 'FleetID and FleetName are required']);
                    exit;
                }

                $stmt = $db->prepare("UPDATE tblFleet SET FleetName = ? WHERE FleetID = ?");
                $result = $stmt->execute([$input['FleetName'], $input['FleetID']]);

                if ($result) {
                    // 更新关联的船只
                    if (isset($input['BoatIDs']) && is_array($input['BoatIDs'])) {
                        // 软删除旧的关联
                        $stmtDelete = $db->prepare("UPDATE tblFleetDetail SET is_del = 1 WHERE FleetID = ?");
                        $stmtDelete->execute([$input['FleetID']]);

                        // 添加新的关联
                        foreach ($input['BoatIDs'] as $boatId) {
                            // 检查是否已存在
                            $stmtCheck = $db->prepare("SELECT FleetDetailID FROM tblFleetDetail WHERE FleetID = ? AND BoatID = ? AND is_del = 1");
                            $stmtCheck->execute([$input['FleetID'], $boatId]);
                            $existing = $stmtCheck->fetch();

                            if ($existing) {
                                // 恢复已删除的记录
                                $stmtRestore = $db->prepare("UPDATE tblFleetDetail SET is_del = 0 WHERE FleetDetailID = ?");
                                $stmtRestore->execute([$existing['FleetDetailID']]);
                            } else {
                                // 插入新记录
                                $stmtDetail = $db->prepare("INSERT INTO tblFleetDetail (FleetID, BoatID) VALUES (?, ?)");
                                $stmtDetail->execute([$input['FleetID'], $boatId]);
                            }
                        }
                    }

                    echo json_encode(['success' => true, 'message' => 'Fleet updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update fleet']);
                }
            }
            break;

        case 'delete':
            // 删除船队（软删除）
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $fleetId = $input['id'] ?? 0;

                if (!$fleetId) {
                    echo json_encode(['success' => false, 'error' => 'FleetID is required']);
                    exit;
                }

                // 检查是否有关联的供应商
                $stmtCheck = $db->prepare("SELECT COUNT(*) as count FROM tblSuppliers WHERE FleetID = ? AND is_del = 0");
                $stmtCheck->execute([$fleetId]);
                $supplierCount = $stmtCheck->fetch(PDO::FETCH_ASSOC)['count'];

                if ($supplierCount > 0) {
                    echo json_encode(['success' => false, 'error' => 'Cannot delete fleet with associated suppliers']);
                    exit;
                }

                // 软删除船队
                $stmt = $db->prepare("UPDATE tblFleet SET is_del = 1 WHERE FleetID = ?");
                $result = $stmt->execute([$fleetId]);

                // 软删除关联的船只关系
                $stmtDetail = $db->prepare("UPDATE tblFleetDetail SET is_del = 1 WHERE FleetID = ?");
                $stmtDetail->execute([$fleetId]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Fleet deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to delete fleet']);
                }
            }
            break;

        case 'boats':
            // 获取船队关联的船只
            if ($method === 'GET') {
                $fleetId = $_GET['fleetId'] ?? 0;

                if (!$fleetId) {
                    echo json_encode(['success' => false, 'error' => 'FleetID is required']);
                    exit;
                }

                $stmt = $db->prepare("
                    SELECT b.BoatID, b.BoatNo, b.BoatName, b.Person
                    FROM tblBoat b
                    INNER JOIN tblFleetDetail fd ON b.BoatID = fd.BoatID
                    WHERE fd.FleetID = ? AND fd.is_del = 0 AND b.is_del = 0
                    ORDER BY b.BoatID
                ");
                $stmt->execute([$fleetId]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log("Fleet API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>

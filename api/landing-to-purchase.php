<?php
/**
 * ============================================
 * API: Generate Purchase from Landing Records
 * Description: Auto-generate tblPurchase records from tblLanding data
 * ============================================
 */

// 防止重复引入
if (!defined('LANDING_TO_PURCHASE_INCLUDED')) {
    // Define access constant (only if not already defined)
    if (!defined('APP_ACCESS')) {
        define('APP_ACCESS', true);
    }

    // Include database configuration (only if not already included)
    require_once dirname(__DIR__) . '/config/db.php';

    // 标记已引入
    define('LANDING_TO_PURCHASE_INCLUDED', true);
}

// 如果是直接访问此文件（不是被引入），则执行主逻辑
if (basename($_SERVER['PHP_SELF']) === 'landing-to-purchase.php') {

    // Set response headers
    header('Content-Type: application/json; charset=utf-8');

    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];

    // Start Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    try {
        // Get database connection
        $conn = Database::getConnection();

        // Verify login status
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Not logged in or session has expired'
            ]);
            exit;
        }

        // Only handle POST requests
        if ($method === 'POST') {
            handleGeneratePurchase($conn);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Unsupported request method'
            ]);
        }

    } catch (Exception $e) {
        error_log("Purchase generation API error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Server error, please try again later'
        ]);
    }
}

/**
 * Generate Purchase from Landing (can be called from other modules)
 * @param PDO $conn Database connection
 * @param int $landingId Landing ID
 * @return array Generated purchase data
 * @throws Exception if generation fails
 */
function generatePurchaseFromLanding($conn, $landingId) {
    // ========== Step 1: Query landing record main table ==========
    $sql = "SELECT LandingID, LandingDate, SupplierID, PortID, BoatID
            FROM tblLanding
            WHERE LandingID = ? AND is_del = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$landingId]);
    $landingData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$landingData) {
        throw new Exception('Landing record not found or has been deleted');
    }

    // ========== Step 2: Query landing details and related data ==========
    $detailSql = "SELECT
                    ld.StockID,
                    ld.BinID,
                    ld.BinQty,
                    ld.ICE,
                    ld.`L-Weight`,
                    ld.WeightUnitID,
                    ld.Price,
                    b.`B-Weight` AS BinWeight,
                    s.Conversion,
                    s.State,
                    s.Description
                 FROM tblLandingDetail ld
                 LEFT JOIN tblBin b ON ld.BinID = b.BinID
                 LEFT JOIN tblStock s ON ld.StockID = s.StockID
                 WHERE ld.LandingID = ? AND ld.is_del = 0
                 ORDER BY ld.ID";
    $detailStmt = $conn->prepare($detailSql);
    $detailStmt->execute([$landingId]);
    $landingDetails = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($landingDetails)) {
        throw new Exception('Landing record has no detail data, cannot generate purchase order');
    }

    // ========== Step 3: Calculate fields for each detail and aggregate ==========
    $subtotal = 0;
    $purchaseDetails = [];

    foreach ($landingDetails as $detail) {
        // Get base data
        $lWeight = $detail['L-Weight']; // Total weight with bin (keep as null if empty)
        $weightUnitId = isset($detail['WeightUnitID']) ? intval($detail['WeightUnitID']) : 1; // Weight unit from landing, default KG
        $binWeight = floatval($detail['BinWeight'] ?? 0); // Single bin weight
        $binQty = intval($detail['BinQty'] ?? 1); // Bin quantity
        $conversion = floatval($detail['Conversion'] ?? 1); // Conversion factor, default 1
        $price = floatval($detail['Price'] ?? 0); // Unit price (from landing detail)

        // Check if L-Weight is empty or null
        if (empty($lWeight) || $lWeight === '') {
            // L-Weight is empty: set all weight-related fields to 0
            $landedKG = 0;
            $greenKG = 0;
            $total = 0;
        } else {
            // L-Weight has value: calculate normally
            $lWeight = floatval($lWeight);
            // Calculate total bin weight (total bin weight = single bin weight × bin quantity)
            $totalBinWeight = $binWeight * $binQty;

            // Calculate LandedKG (net weight = total weight with bin - total bin weight)
            $landedKG = $lWeight - $totalBinWeight;

            // Calculate GreenKG (converted net weight = landedKG × conversion factor)
            $greenKG = $landedKG * $conversion;

            // Calculate line total (Total = LandedKG × Price)
            $total = $landedKG * $price;
        }

        // Accumulate subtotal
        $subtotal += $total;

        // Build purchase detail data
        $purchaseDetails[] = [
            'StockID' => $detail['StockID'],
            'BinQty' => $binQty,
            'UnloadingDocket' => $landingId, // Sync corresponding LandingID
            'ICE' => intval($detail['ICE'] ?? 0), // Sync ICE field
            'GreenKG' => round($greenKG, 3),
            'LandedKG' => round($landedKG, 3),
            'LandedWeightUnitID' => $weightUnitId, // Pass through the weight unit
            'GreenWeightUnitID' => $weightUnitId, // Same unit for GreenKG
            'Price' => round($price, 2),
            'Total' => round($total, 2)
        ];
    }

    // ========== Step 4: Calculate main table amounts ==========
    $gst = $subtotal * 0.15; // Tax 15%
    $totalAmount = $subtotal + $gst; // Total

    // ========== Step 5: Generate purchase order ID ==========
    $purchaseId = $landingId + 50000;

    // ========== Step 6: Check if purchase order exists (including deleted) ==========
    $checkSql = "SELECT PurchaseID, is_del FROM tblPurchase WHERE PurchaseID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$purchaseId]);
    $existingPurchase = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingPurchase) {
        if ($existingPurchase['is_del'] == 0) {
            // Active purchase order exists
            throw new Exception('Purchase order already exists for this landing record (PurchaseID: ' . $purchaseId . ')');
        } else {
            // Deleted purchase order exists - restore it
            restorePurchaseOrder($conn, $purchaseId, $landingData, $subtotal, $gst, $totalAmount, $purchaseDetails);
            return [
                'PurchaseID' => $purchaseId,
                'LandingID' => $landingId,
                'PurchaseDate' => $landingData['LandingDate'],
                'SupplierID' => $landingData['SupplierID'],
                'Subtotal' => round($subtotal, 2),
                'GST' => round($gst, 2),
                'Total' => round($totalAmount, 2),
                'details_count' => count($purchaseDetails),
                'details' => $purchaseDetails,
                'restored' => true
            ];
        }
    }

    // ========== Step 7: Insert purchase order main table ==========
    $insertSql = "INSERT INTO tblPurchase (PurchaseID, PurchaseDate, SupplierID, Subtotal, GST, Total, LandingID)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->execute([
        $purchaseId,
        $landingData['LandingDate'],
        $landingData['SupplierID'],
        round($subtotal, 2),
        round($gst, 2),
        round($totalAmount, 2),
        $landingId
    ]);

    // ========== Step 8: Insert purchase order detail table ==========
    $detailInsertSql = "INSERT INTO tblPurchaseDetail (PurchaseID, StockID, BinQty, UnloadingDocket, ICE, GreenKG, LandedKG, LandedWeightUnitID, GreenWeightUnitID, Price, Total)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $detailInsertStmt = $conn->prepare($detailInsertSql);

    foreach ($purchaseDetails as $detail) {
        $detailInsertStmt->execute([
            $purchaseId,
            $detail['StockID'],
            $detail['BinQty'],
            $detail['UnloadingDocket'],
            $detail['ICE'],
            $detail['GreenKG'],
            $detail['LandedKG'],
            $detail['LandedWeightUnitID'],
            $detail['GreenWeightUnitID'],
            $detail['Price'],
            $detail['Total']
        ]);
    }

    return [
        'PurchaseID' => $purchaseId,
        'LandingID' => $landingId,
        'PurchaseDate' => $landingData['LandingDate'],
        'SupplierID' => $landingData['SupplierID'],
        'Subtotal' => round($subtotal, 2),
        'GST' => round($gst, 2),
        'Total' => round($totalAmount, 2),
        'details_count' => count($purchaseDetails),
        'details' => $purchaseDetails,
        'restored' => false
    ];
}

/**
 * Handle POST request - Generate purchase order
 */
function handleGeneratePurchase($conn) {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (empty($input['LandingID'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Landing record ID cannot be empty'
        ]);
        return;
    }

    $landingId = intval($input['LandingID']);

    try {
        $conn->beginTransaction();

        // Call the shared function to generate purchase
        $purchaseData = generatePurchaseFromLanding($conn, $landingId);

        $conn->commit();

        // Return success result
        echo json_encode([
            'success' => true,
            'message' => $purchaseData['restored'] ? 'Purchase order restored successfully' : 'Purchase order generated successfully',
            'data' => $purchaseData
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Purchase order generation failed: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate purchase order: ' . $e->getMessage()
        ]);
    }
}

/**
 * Restore deleted purchase order
 */
function restorePurchaseOrder($conn, $purchaseId, $landingData, $subtotal, $gst, $totalAmount, $purchaseDetails) {
    // Update main table
    $updateMainSql = "UPDATE tblPurchase SET
                        is_del = 0,
                        PurchaseDate = ?,
                        SupplierID = ?,
                        Subtotal = ?,
                        GST = ?,
                        Total = ?,
                        update_time = NOW()
                      WHERE PurchaseID = ?";
    $updateMainStmt = $conn->prepare($updateMainSql);
    $updateMainStmt->execute([
        $landingData['LandingDate'],
        $landingData['SupplierID'],
        round($subtotal, 2),
        round($gst, 2),
        round($totalAmount, 2),
        $purchaseId
    ]);

    // Delete old details and insert new ones
    $deleteDetailsSql = "DELETE FROM tblPurchaseDetail WHERE PurchaseID = ?";
    $deleteDetailsStmt = $conn->prepare($deleteDetailsSql);
    $deleteDetailsStmt->execute([$purchaseId]);

    // Insert new details
    $detailInsertSql = "INSERT INTO tblPurchaseDetail (PurchaseID, StockID, BinQty, UnloadingDocket, ICE, GreenKG, LandedKG, LandedWeightUnitID, GreenWeightUnitID, Price, Total)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $detailInsertStmt = $conn->prepare($detailInsertSql);

    foreach ($purchaseDetails as $detail) {
        $detailInsertStmt->execute([
            $purchaseId,
            $detail['StockID'],
            $detail['BinQty'],
            $detail['UnloadingDocket'],
            $detail['ICE'],
            $detail['GreenKG'],
            $detail['LandedKG'],
            $detail['LandedWeightUnitID'],
            $detail['GreenWeightUnitID'],
            $detail['Price'],
            $detail['Total']
        ]);
    }
}

/**
 * Update Purchase from Landing (called when Landing is edited)
 * @param PDO $conn Database connection
 * @param int $landingId Landing ID
 * @return array Updated purchase data
 * @throws Exception if update fails
 */
function updatePurchaseFromLanding($conn, $landingId) {
    $purchaseId = $landingId + 50000;

    // Check if Purchase exists
    $checkSql = "SELECT PurchaseID, is_del FROM tblPurchase WHERE PurchaseID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$purchaseId]);
    $existingPurchase = $checkStmt->fetch(PDO::FETCH_ASSOC);

    // If Purchase doesn't exist or is deleted, generate new one
    if (!$existingPurchase || $existingPurchase['is_del'] == 1) {
        return generatePurchaseFromLanding($conn, $landingId);
    }

    // Query landing record main table
    $sql = "SELECT LandingID, LandingDate, SupplierID, PortID, BoatID
            FROM tblLanding
            WHERE LandingID = ? AND is_del = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$landingId]);
    $landingData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$landingData) {
        throw new Exception('Landing record not found or has been deleted');
    }

    // Query landing details and related data
    $detailSql = "SELECT
                    ld.StockID,
                    ld.BinID,
                    ld.BinQty,
                    ld.ICE,
                    ld.`L-Weight`,
                    ld.WeightUnitID,
                    ld.Price,
                    b.`B-Weight` AS BinWeight,
                    s.Conversion,
                    s.State,
                    s.Description
                 FROM tblLandingDetail ld
                 LEFT JOIN tblBin b ON ld.BinID = b.BinID
                 LEFT JOIN tblStock s ON ld.StockID = s.StockID
                 WHERE ld.LandingID = ? AND ld.is_del = 0
                 ORDER BY ld.ID";
    $detailStmt = $conn->prepare($detailSql);
    $detailStmt->execute([$landingId]);
    $landingDetails = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate fields for each detail and aggregate
    $subtotal = 0;
    $purchaseDetails = [];

    foreach ($landingDetails as $detail) {
        $lWeight = $detail['L-Weight'];
        $weightUnitId = isset($detail['WeightUnitID']) ? intval($detail['WeightUnitID']) : 1;
        $binWeight = floatval($detail['BinWeight'] ?? 0);
        $binQty = intval($detail['BinQty'] ?? 1);
        $conversion = floatval($detail['Conversion'] ?? 1);
        $price = floatval($detail['Price'] ?? 0);

        if (empty($lWeight) || $lWeight === '') {
            $landedKG = 0;
            $greenKG = 0;
            $total = 0;
        } else {
            $lWeight = floatval($lWeight);
            $totalBinWeight = $binWeight * $binQty;
            $landedKG = $lWeight - $totalBinWeight;
            $greenKG = $landedKG * $conversion;
            $total = $landedKG * $price;
        }

        $subtotal += $total;

        $purchaseDetails[] = [
            'StockID' => $detail['StockID'],
            'BinQty' => $binQty,
            'UnloadingDocket' => $landingId,
            'ICE' => intval($detail['ICE'] ?? 0),
            'GreenKG' => round($greenKG, 3),
            'LandedKG' => round($landedKG, 3),
            'LandedWeightUnitID' => $weightUnitId,
            'GreenWeightUnitID' => $weightUnitId,
            'Price' => round($price, 2),
            'Total' => round($total, 2)
        ];
    }

    // Calculate main table amounts
    $gst = $subtotal * 0.15;
    $totalAmount = $subtotal + $gst;

    // Update purchase main table
    $updateMainSql = "UPDATE tblPurchase SET
                        PurchaseDate = ?,
                        SupplierID = ?,
                        Subtotal = ?,
                        GST = ?,
                        Total = ?,
                        update_time = NOW()
                      WHERE PurchaseID = ?";
    $updateMainStmt = $conn->prepare($updateMainSql);
    $updateMainStmt->execute([
        $landingData['LandingDate'],
        $landingData['SupplierID'],
        round($subtotal, 2),
        round($gst, 2),
        round($totalAmount, 2),
        $purchaseId
    ]);

    // Delete old details
    $deleteDetailsSql = "DELETE FROM tblPurchaseDetail WHERE PurchaseID = ?";
    $deleteDetailsStmt = $conn->prepare($deleteDetailsSql);
    $deleteDetailsStmt->execute([$purchaseId]);

    // Insert new details
    $detailInsertSql = "INSERT INTO tblPurchaseDetail (PurchaseID, StockID, BinQty, UnloadingDocket, ICE, GreenKG, LandedKG, LandedWeightUnitID, GreenWeightUnitID, Price, Total)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $detailInsertStmt = $conn->prepare($detailInsertSql);

    foreach ($purchaseDetails as $detail) {
        $detailInsertStmt->execute([
            $purchaseId,
            $detail['StockID'],
            $detail['BinQty'],
            $detail['UnloadingDocket'],
            $detail['ICE'],
            $detail['GreenKG'],
            $detail['LandedKG'],
            $detail['LandedWeightUnitID'],
            $detail['GreenWeightUnitID'],
            $detail['Price'],
            $detail['Total']
        ]);
    }

    return [
        'PurchaseID' => $purchaseId,
        'LandingID' => $landingId,
        'PurchaseDate' => $landingData['LandingDate'],
        'SupplierID' => $landingData['SupplierID'],
        'Subtotal' => round($subtotal, 2),
        'GST' => round($gst, 2),
        'Total' => round($totalAmount, 2),
        'details_count' => count($purchaseDetails),
        'details' => $purchaseDetails,
        'updated' => true
    ];
}
?>

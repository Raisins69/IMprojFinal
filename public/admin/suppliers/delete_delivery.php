<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

// Initialize variables
$error = '';
$delivery_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$supplier_id = filter_input(INPUT_GET, 'supplier_id', FILTER_VALIDATE_INT);
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

// Validate CSRF token
if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid or missing CSRF token';
    header('Location: deliveries.php' . ($supplier_id ? '?supplier_id=' . $supplier_id : ''));
    exit();
}

// Validate IDs
if (!$delivery_id || !$supplier_id) {
    $_SESSION['error'] = 'Invalid delivery or supplier ID';
    header('Location: deliveries.php' . ($supplier_id ? '?supplier_id=' . $supplier_id : ''));
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get delivery details for logging
        $stmt = $conn->prepare("
            SELECT id, reference_number, delivery_date, status 
            FROM supplier_deliveries 
            WHERE id = ? AND supplier_id = ?
        ");
        
        if ($stmt === false) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $delivery_id, $supplier_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Query execution failed: ' . $stmt->error);
        }
        
        $delivery = $stmt->get_result()->fetch_assoc();
        
        if (!$delivery) {
            throw new Exception('Delivery not found or does not belong to the specified supplier');
        }
        
        // Check if delivery can be deleted (e.g., not in certain statuses)
        if (in_array($delivery['status'], ['Received', 'In Transit'])) {
            throw new Exception('Cannot delete a delivery that is already ' . $delivery['status']);
        }
        
        // Delete delivery items first (if any)
        $deleteItemsStmt = $conn->prepare("DELETE FROM delivery_items WHERE delivery_id = ?");
        if ($deleteItemsStmt === false) {
            throw new Exception('Failed to prepare delivery items deletion: ' . $conn->error);
        }
        
        $deleteItemsStmt->bind_param("i", $delivery_id);
        if (!$deleteItemsStmt->execute()) {
            throw new Exception('Failed to delete delivery items: ' . $deleteItemsStmt->error);
        }
        
        // Delete the delivery
        $deleteStmt = $conn->prepare("DELETE FROM supplier_deliveries WHERE id = ? AND supplier_id = ?");
        if ($deleteStmt === false) {
            throw new Exception('Failed to prepare delivery deletion: ' . $conn->error);
        }
        
        $deleteStmt->bind_param("ii", $delivery_id, $supplier_id);
        
        if (!$deleteStmt->execute()) {
            throw new Exception('Failed to delete delivery: ' . $deleteStmt->error);
        }
        
        if ($deleteStmt->affected_rows === 0) {
            throw new Exception('No delivery was deleted. It may have already been deleted.');
        }
        
        // Log the deletion
        $logMessage = sprintf(
            "Delivery #%s (Ref: %s) deleted by user ID %s",
            $delivery_id,
            $delivery['reference_number'] ?? 'N/A',
            $_SESSION['user_id'] ?? 'unknown'
        );
        
        error_log($logMessage);
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = 'Delivery deleted successfully';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Delete delivery error: ' . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete delivery: ' . $e->getMessage();
}

// Redirect back to deliveries page
header('Location: deliveries.php?supplier_id=' . $supplier_id);
exit();

<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

// Initialize variables
$error = '';
$success = '';
$supplier_id = filter_input(INPUT_GET, 'supplier_id', FILTER_VALIDATE_INT);
$supplier = null;
$deliveries = [];

try {
    // Validate supplier ID
    if (!$supplier_id) {
        throw new Exception('Invalid supplier ID');
    }

    // Get supplier details with prepared statement
    $stmt = $conn->prepare("SELECT id, name, contact_person, email FROM suppliers WHERE id = ?");
    if ($stmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $supplier_id);
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    
    if (!$supplier) {
        throw new Exception('Supplier not found');
    }

    // Handle delivery status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }
        
        $delivery_id = filter_input(INPUT_POST, 'delivery_id', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        
        if (!$delivery_id || !in_array($status, ['Pending', 'In Transit', 'Received', 'Cancelled'])) {
            throw new Exception('Invalid delivery status update request');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update delivery status
            $updateStmt = $conn->prepare("
                UPDATE supplier_deliveries 
                SET status = ?, 
                    received_by = IF(? = 'Received', ?, received_by),
                    received_at = IF(? = 'Received' AND received_at IS NULL, NOW(), received_at)
                WHERE id = ? AND supplier_id = ?
            ");
            
            if ($updateStmt === false) {
                throw new Exception('Database prepare failed: ' . $conn->error);
            }
            
            $current_user_id = $_SESSION['user_id'] ?? 0;
            $updateStmt->bind_param("ssisii", $status, $status, $current_user_id, $status, $delivery_id, $supplier_id);
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update delivery status: ' . $updateStmt->error);
            }
            
            if ($updateStmt->affected_rows === 0) {
                throw new Exception('No delivery found with the specified ID');
            }
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success'] = 'Delivery status updated successfully';
            header("Location: deliveries.php?supplier_id=" . $supplier_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
    }

    // Get all deliveries for this supplier with pagination
    $page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // Get total count for pagination
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM supplier_deliveries WHERE supplier_id = ?");
    $countStmt->bind_param("i", $supplier_id);
    $countStmt->execute();
    $total_deliveries = $countStmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_deliveries / $per_page);
    
    // Get deliveries with pagination
    $stmt = $conn->prepare("
        SELECT d.*, p.name as product_name
        FROM supplier_deliveries d
        LEFT JOIN products p ON d.product_id = p.id
        WHERE d.supplier_id = ?
        ORDER BY d.delivery_date DESC
        LIMIT ? OFFSET ?
    ");
    
    if ($stmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("iii", $supplier_id, $per_page, $offset);
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $deliveries = $stmt->get_result();
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log('Deliveries page error: ' . $e->getMessage());
    if (!isset($supplier)) {
        $_SESSION['error'] = $error;
        header("Location: read.php");
        exit();
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();
 require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Deliveries from: <?= htmlspecialchars($supplier['name']) ?></h2>
        <p><strong>Contact:</strong> <?= htmlspecialchars($supplier['contact_person']) ?> - <?= htmlspecialchars($supplier['email']) ?></p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>Deliveries for <?= htmlspecialchars($supplier['name']) ?></h2>
            <div class="actions">
                <a href="add_delivery.php?supplier_id=<?= $supplier_id ?>" class="btn-primary">
                    <span class="btn-icon">➕</span> Add Delivery
                </a>
                <a href="read.php" class="btn-secondary">
                    <span class="btn-icon">←</span> Back to Suppliers
                </a>
            </div>
        </div>

        <?php if ($deliveries->num_rows > 0): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Cost</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_cost = 0;
                    $total_quantity = 0;
                    while($delivery = $deliveries->fetch_assoc()): 
                        $total_cost += $delivery['cost'];
                        $total_quantity += $delivery['quantity'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($delivery['id']) ?></td>
                            <td><?= htmlspecialchars($delivery['product_name']) ?></td>
                            <td><?= htmlspecialchars($delivery['quantity']) ?></td>
                            <td>₱<?= number_format($delivery['cost'], 2) ?></td>
                            <td><?= htmlspecialchars($delivery['delivery_date']) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($delivery['status'] ?? 'Pending') ?>">
                                    <?= htmlspecialchars($delivery['status'] ?? 'Pending') ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_delivery.php?id=<?= $delivery['id'] ?>" class="btn-edit" title="Edit Delivery">
                                    <span class="btn-icon">✏️</span> Edit
                                </a>
                                
                                <?php if (($delivery['status'] ?? '') !== 'Received'): ?>
                                    <a href="#" class="btn-receive" 
                                       onclick="if(confirm('Mark this delivery as received?')) { 
                                           document.getElementById('delivery-<?= $delivery['id'] ?>').submit(); 
                                       } return false;"
                                       title="Mark as Received">
                                        <span class="btn-icon">✓</span> Receive
                                    </a>
                                    <form id="delivery-<?= $delivery['id'] ?>" method="post" style="display: none;">
                                        <input type="hidden" name="delivery_id" value="<?= $delivery['id'] ?>">
                                        <input type="hidden" name="status" value="Received">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    </form>
                                <?php endif; ?>
                                
                                <a href="#" 
                                   class="btn-delete" 
                                   onclick="if(confirm('Are you sure you want to delete this delivery? This action cannot be undone.')) { 
                                       window.location.href='delete_delivery.php?id=<?= $delivery['id'] ?>&token=<?= $_SESSION['csrf_token'] ?? '' ?>';
                                   } return false;"
                                   title="Delete Delivery">
                                    <span class="btn-icon">🗑</span> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div style="margin-top: 20px; padding: 15px; background: #12121A; border-radius: 8px;">
                <h3>Summary</h3>
                <p><strong>Total Deliveries:</strong> <?= $deliveries->num_rows ?></p>
                <p><strong>Total Quantity Supplied:</strong> <?= $total_quantity ?></p>
                <p><strong>Total Cost:</strong> ₱<?= number_format($total_cost, 2) ?></p>
            </div>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; color: #888;">No deliveries recorded yet.</p>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?supplier_id=<?= $supplier_id ?>&page=<?= $page - 1 ?>" class="btn-prev">Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current-page"><?= $i ?></span>
                <?php else: ?>
                    <a href="?supplier_id=<?= $supplier_id ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?supplier_id=<?= $supplier_id ?>&page=<?= $page + 1 ?>" class="btn-next">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<style>
.pagination {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 5px;
}

.pagination a, .pagination span {
    display: inline-block;
    padding: 5px 12px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    border-radius: 3px;
}

.pagination a:hover {
    background-color: #f5f5f5;
}

.pagination .current-page {
    background-color: #4CAF50;
    color: white;
    border-color: #4CAF50;
}

.btn-prev, .btn-next {
    background-color: #f8f9fa;
}

.alert {
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: 500;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-in-transit {
    background-color: #cce5ff;
    color: #004085;
}

.status-received {
    background-color: #d4edda;
    color: #155724;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
    text-decoration: line-through;
}

.actions {
    white-space: nowrap;
}

.actions a {
    margin-right: 5px;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.85em;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.actions a .btn-icon {
    margin-right: 3px;
}

.actions a.btn-edit {
    background-color: #ffc107;
    color: #000;
}

.actions a.btn-receive {
    background-color: #28a745;
    color: white;
}

.actions a.btn-delete {
    background-color: #dc3545;
    color: white;
}

.actions a:hover {
    opacity: 0.9;
    text-decoration: none;
}
</style>

<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();
 require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['customer_id']) || !is_numeric($_GET['customer_id'])) {
    header("Location: read.php");
    exit();
}

$user_customer_id = intval($_GET['customer_id']);

// Get user info
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ? AND role = 'customer'");
$stmt->bind_param("i", $user_customer_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: read.php");
    exit();
}

// Get customer record from customers table
$stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
$stmt->bind_param("s", $user['email']);
$stmt->execute();
$customer_result = $stmt->get_result();
$customer = $customer_result->fetch_assoc();

if ($customer) {
    $customer_record_id = $customer['id'];
    
    // Get purchase history (FR2.2)
    $stmt = $conn->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.order_date DESC
    ");
    $stmt->bind_param("i", $customer_record_id);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = null;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-container">
    <?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();
 require_once '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Purchase History: <?= htmlspecialchars($user['username']) ?></h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

        <?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();
 if ($orders && $orders->num_rows > 0): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();
 while($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars($order['order_date']) ?></td>
                            <td><?= htmlspecialchars($order['total_items']) ?> items</td>
                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($order['payment_method']) ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td>
                                <a class="btn-view" href="../transactions/view.php?id=<?= intval($order['id']) ?>">👁 View</a>
                            </td>
                        </tr>
                    <?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();
 endwhile; ?>
                </tbody>
            </table>

            <?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

            // Calculate total spent
            $stmt = $conn->prepare("SELECT SUM(total_amount) as total_spent, COUNT(*) as order_count 
                                   FROM orders WHERE customer_id = ?");
            $stmt->bind_param("i", $customer_record_id);
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();
            ?>

            <div style="margin-top: 20px; padding: 15px; background: #12121A; border-radius: 8px;">
                <h3>Customer Statistics</h3>
                <p><strong>Total Orders:</strong> <?= $stats['order_count'] ?></p>
                <p><strong>Total Spent:</strong> ₱<?= number_format($stats['total_spent'], 2) ?></p>
            </div>
        <?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();
 else: ?>
            <p style="text-align: center; padding: 20px; color: #888;">This customer has no purchase history yet.</p>
        <?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();
 endif; ?>

        <br>
        <a href="read.php" class="btn-secondary">⬅ Back to Customers</a>
    </main>
</div>

<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();
 require_once __DIR__ . '/../../includes/footer.php'; ?>

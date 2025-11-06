<?php
include __DIR__ . '/../../includes/config.php';

// Prevent direct access if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get customer_id
$customer_id = $_SESSION['customer_id'] ?? null;

// Get stats
$total_orders = 0;
$total_spent = 0;
$pending_orders = 0;

if ($customer_id) {
    // Total orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $total_orders = $stmt->get_result()->fetch_assoc()['count'];

    // Total spent
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $total_spent = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Pending orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ? AND status = 'Pending'");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $pending_orders = $stmt->get_result()->fetch_assoc()['count'];

    // Recent orders
    $stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                           FROM orders o 
                           LEFT JOIN order_items oi ON o.id = oi.order_id
                           WHERE o.customer_id = ? 
                           GROUP BY o.id
                           ORDER BY o.order_date DESC 
                           LIMIT 5");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $recent_orders = $stmt->get_result();
}

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | UrbanThrift</title>
    <link rel="stylesheet" href="/IMProj/public/css/style.css">
    <style>
        .dashboard-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--dark-light) 0%, var(--dark) 100%);
            padding: 3rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(155, 77, 224, 0.2);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(155, 77, 224, 0.2) 0%, transparent 70%);
            filter: blur(60px);
        }

        .dashboard-header-content {
            position: relative;
            z-index: 1;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .dashboard-header .greeting {
            font-size: 1.2rem;
            color: var(--text-secondary);
        }

        .dashboard-header .greeting span {
            color: var(--primary-light);
            font-weight: 700;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--dark-light);
            padding: 2rem;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(155, 77, 224, 0.2);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(155, 77, 224, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-glow);
            border-color: var(--primary);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-light);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .action-card {
            background: linear-gradient(135deg, var(--dark-light) 0%, var(--dark) 100%);
            padding: 2rem;
            border-radius: var(--radius-lg);
            border: 2px solid rgba(155, 77, 224, 0.2);
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(155, 77, 224, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: var(--transition);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-glow);
            border-color: var(--primary);
        }

        .action-card:hover::before {
            opacity: 1;
            animation: rotate 3s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
            position: relative;
            z-index: 1;
        }

        .action-card h3 {
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .action-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .recent-orders-section {
            background: var(--dark-light);
            padding: 2rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(155, 77, 224, 0.2);
        }

        .recent-orders-section h2 {
            font-size: 1.8rem;
            color: var(--primary-light);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table thead {
            background: rgba(155, 77, 224, 0.1);
        }

        .orders-table th {
            padding: 1rem;
            text-align: left;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .orders-table td {
            padding: 1.25rem 1rem;
            color: var(--text-secondary);
            border-bottom: 1px solid rgba(155, 77, 224, 0.1);
        }

        .orders-table tbody tr {
            transition: var(--transition);
        }

        .orders-table tbody tr:hover {
            background: rgba(155, 77, 224, 0.05);
        }

        .order-id {
            color: var(--primary-light);
            font-weight: 600;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(255, 176, 32, 0.2);
            color: var(--warning);
        }

        .status-completed {
            background: rgba(0, 217, 165, 0.2);
            color: var(--success);
        }

        .view-all-orders {
            text-align: center;
            margin-top: 2rem;
        }

        .view-all-orders a {
            display: inline-block;
            padding: 1rem 2rem;
            background: rgba(155, 77, 224, 0.1);
            border: 2px solid var(--primary);
            color: var(--primary-light);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
        }

        .view-all-orders a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 968px) {
            .dashboard-header h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .orders-table {
                font-size: 0.9rem;
            }

            .orders-table th,
            .orders-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="dashboard-header-content">
            <h1>👋 Welcome Back!</h1>
            <p class="greeting">Good to see you, <span><?= htmlspecialchars($user['username']) ?></span></p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-icon">📦</span>
            <div class="stat-value"><?= $total_orders ?></div>
            <div class="stat-label">Total Orders</div>
        </div>

        <div class="stat-card">
            <span class="stat-icon">💰</span>
            <div class="stat-value">₱<?= number_format($total_spent, 2) ?></div>
            <div class="stat-label">Total Spent</div>
        </div>

        <div class="stat-card">
            <span class="stat-icon">⏳</span>
            <div class="stat-value"><?= $pending_orders ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>

        <div class="stat-card">
            <span class="stat-icon">🎯</span>
            <div class="stat-value"><?= $customer_id ? 'Active' : 'Guest' ?></div>
            <div class="stat-label">Account Status</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="../shop.php" class="action-card">
            <span class="action-icon">🛍️</span>
            <h3>Browse Shop</h3>
            <p>Discover new arrivals</p>
        </a>

        <a href="orders.php" class="action-card">
            <span class="action-icon">📋</span>
            <h3>My Orders</h3>
            <p>View order history</p>
        </a>

        <a href="profile.php" class="action-card">
            <span class="action-icon">👤</span>
            <h3>Edit Profile</h3>
            <p>Update your info</p>
        </a>

        <a href="../cart/cart.php" class="action-card">
            <span class="action-icon">🛒</span>
            <h3>Shopping Cart</h3>
            <p>Review your items</p>
        </a>
    </div>

    <!-- Recent Orders -->
    <div class="recent-orders-section">
        <h2>📦 Recent Orders</h2>

        <?php if ($customer_id && $recent_orders->num_rows > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $recent_orders->fetch_assoc()): ?>
                    <tr>
                        <td class="order-id">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                        <td><?= $order['item_count'] ?> item(s)</td>
                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                        <td><?= htmlspecialchars($order['payment_method']) ?></td>
                        <td>
                            <span class="order-status status-<?= strtolower($order['status']) ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="../admin/transactions/view.php?id=<?= $order['id'] ?>" 
                               style="color: var(--primary-light); text-decoration: none; font-weight: 600;">
                                View →
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="view-all-orders">
                <a href="orders.php">View All Orders →</a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">No orders yet</h3>
                <p>Start shopping to see your orders here!</p>
                <a href="../shop.php" class="btn-primary" style="display: inline-block; margin-top: 1rem; padding: 1rem 2rem; text-decoration: none;">
                    Browse Shop
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

</body>
</html>

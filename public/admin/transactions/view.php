<?php
include '../../../includes/config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
SELECT o.*, c.name AS customer_name, c.contact_number
FROM orders o
JOIN customers c ON o.customer_id = c.id
WHERE o.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$order_q = $stmt->get_result();
$order = $order_q->fetch_assoc();

if (!$order) {
    header("Location: read.php");
    exit();
}

$stmt = $conn->prepare("
SELECT oi.*, p.name AS product_name
FROM order_items oi 
JOIN products p ON oi.product_id = p.id
WHERE oi.order_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$items_q = $stmt->get_result();
?>

<?php include '../../../includes/header.php'; ?>

<h2>Transaction Receipt</h2>

<p><strong>Order ID:</strong> <?= htmlspecialchars($order['id']); ?></p>
<p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']); ?></p>
<p><strong>Contact:</strong> <?= htmlspecialchars($order['contact_number']); ?></p>
<p><strong>Date:</strong> <?= htmlspecialchars($order['order_date']); ?></p>
<p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']); ?></p>

<hr>

<table border="1" width="100%">
<tr>
    <th>Product</th>
    <th>Qty</th>
    <th>Unit Price</th>
    <th>Subtotal</th>
</tr>

<?php while ($item = $items_q->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($item['product_name']); ?></td>
    <td><?= htmlspecialchars($item['quantity']); ?></td>
    <td>₱<?= number_format($item['price'],2); ?></td>
    <td>₱<?= number_format($item['quantity'] * $item['price'],2); ?></td>
</tr>
<?php endwhile; ?>
</table>

<h3>Total: ₱<?= number_format($order['total_amount'],2); ?></h3>

<a href="read.php">⬅ Back</a>

<?php include '../../../includes/footer.php'; ?>

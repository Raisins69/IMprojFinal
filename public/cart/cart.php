<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT c.id, c.quantity, p.name as product_name, p.price 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$query = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Cart</title>
<style>
body {
    background:#0A0A0F; color:#fff;
    font-family:Arial, Helvetica;
}
table {
    width:90%; margin:30px auto;
    border-collapse:collapse;
    background:#12121A;
}
th, td {
    padding:12px; text-align:center;
    border-bottom:1px solid #24242E;
}
th {
    background:#7B1FA2;
}
button, .btn {
    background:#9b4de0; padding:7px 14px;
    border:none; border-radius:6px;
    color:#fff; cursor:pointer;
}
button:hover, .btn:hover {
    background:#7e32bb;
}
</style>

<script>
function confirmDelete(id) {
    if (confirm("Remove this item from cart?")) {
        window.location.href = "remove.php?id=" + id;
    }
}
</script>

</head>
<body>

<h2 style="text-align:center;">🛒 My Shopping Cart</h2>

<table>
<tr>
    <th>Product</th>
    <th>Price</th>
    <th>Qty</th>
    <th>Total</th>
    <th>Action</th>
</tr>

<?php
$grandTotal = 0;

while($row = mysqli_fetch_assoc($query)):
$total = $row['quantity'] * $row['price'];
$grandTotal += $total;
?>

<tr>
    <td><?= htmlspecialchars($row['product_name']) ?></td>
    <td>₱<?= number_format($row['price'],2) ?></td>
    <td><?= htmlspecialchars($row['quantity']) ?></td>
    <td>₱<?= number_format($total,2) ?></td>
    <td><button onclick="confirmDelete(<?= intval($row['id']) ?>)">Delete</button></td>
</tr>

<?php endwhile; ?>

</table>

<div style="text-align:center; margin-top:20px;">
    <h3>Total: ₱<?= number_format($grandTotal,2) ?></h3>
    <a class="btn" href="checkout.php">Checkout ✅</a>
</div>

</body>
</html>

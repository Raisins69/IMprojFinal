<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$delivery_id = intval($_GET['id']);

// Get delivery info
$stmt = $conn->prepare("
    SELECT sd.*, p.name as product_name, s.name as supplier_name
    FROM supplier_deliveries sd
    JOIN products p ON sd.product_id = p.id
    JOIN suppliers s ON sd.supplier_id = s.id
    WHERE sd.id = ?
");
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$delivery = $stmt->get_result()->fetch_assoc();

if (!$delivery) {
    header("Location: read.php");
    exit();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quantity = intval($_POST['quantity']);
    $cost = floatval($_POST['cost']);
    $delivery_date = trim($_POST['delivery_date']);

    if ($quantity <= 0 || $cost < 0 || empty($delivery_date)) {
        $msg = "❌ Please fill all fields with valid data.";
    } else {
        $stmt = $conn->prepare("UPDATE supplier_deliveries SET quantity = ?, cost = ?, delivery_date = ? WHERE id = ?");
        $stmt->bind_param("idsi", $quantity, $cost, $delivery_date, $delivery_id);
        
        if ($stmt->execute()) {
            header("Location: deliveries.php?supplier_id=" . $delivery['supplier_id']);
            exit();
        } else {
            $msg = "❌ Failed to update delivery.";
        }
    }
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
        <h2>Edit Delivery</h2>

        <p class="msg"><?= $msg ?></p>

        <p><strong>Supplier:</strong> <?= htmlspecialchars($delivery['supplier_name']) ?></p>
        <p><strong>Product:</strong> <?= htmlspecialchars($delivery['product_name']) ?></p>

        <form class="form-box" method="POST">
            <label>Quantity *</label>
            <input type="number" name="quantity" value="<?= htmlspecialchars($delivery['quantity']) ?>" min="1" required>

            <label>Cost (₱) *</label>
            <input type="number" name="cost" value="<?= htmlspecialchars($delivery['cost']) ?>" step="0.01" min="0" required>

            <label>Delivery Date *</label>
            <input type="date" name="delivery_date" value="<?= htmlspecialchars($delivery['delivery_date']) ?>" required>

            <button type="submit" class="btn-primary">Update Delivery</button>
            <a href="deliveries.php?supplier_id=<?= $delivery['supplier_id'] ?>" class="btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();
 require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['supplier_id']) || !is_numeric($_GET['supplier_id'])) {
    header("Location: read.php");
    exit();
}

$supplier_id = intval($_GET['supplier_id']);

// Get supplier info
$stmt = $conn->prepare("SELECT name FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();

if (!$supplier) {
    header("Location: read.php");
    exit();
}

// Get products that can be supplied by this supplier
$products = $conn->query("SELECT p.* 
    FROM products p
    INNER JOIN supplier_products sp ON p.id = sp.product_id
    WHERE sp.supplier_id = $supplier_id
    ORDER BY p.name");
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $cost = floatval($_POST['cost']);
    $delivery_date = trim($_POST['delivery_date']);
    $update_stock = isset($_POST['update_stock']);

    if (empty($product_id) || $quantity <= 0 || $cost < 0 || empty($delivery_date)) {
        $msg = "❌ Please fill all required fields with valid data.";
    } else {
        $conn->begin_transaction();
        
        try {
            // Insert delivery record
            $stmt = $conn->prepare("INSERT INTO supplier_deliveries (supplier_id, product_id, quantity, cost, delivery_date) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiids", $supplier_id, $product_id, $quantity, $cost, $delivery_date);
            $stmt->execute();
            
            // Optionally update product stock (FR1.3)
            if ($update_stock) {
                $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $product_id);
                $stmt->execute();
            }
            
            $conn->commit();
            header("Location: deliveries.php?supplier_id=$supplier_id");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "❌ Failed to add delivery: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php
require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Add Delivery for: <?= htmlspecialchars($supplier['name']) ?></h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST">
            <label>Product *</label>
            <select name="product_id" required>
                <option value="">Select Product</option>
                <?php if ($products && $products->num_rows > 0): ?>
                    <?php while($product = $products->fetch_assoc()): ?>
                        <option value="<?= $product['id'] ?>">
                            <?= htmlspecialchars($product['name']) ?> (Current Stock: <?= $product['stock'] ?>)
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="" disabled>No products available for this supplier</option>
                <?php endif; ?>
            </select>

            <label>Quantity Delivered *</label>
            <input type="number" name="quantity" min="1" required>

            <label>Cost (₱) *</label>
            <input type="number" name="cost" step="0.01" min="0" required>

            <label>Delivery Date *</label>
            <input type="date" name="delivery_date" value="<?= date('Y-m-d') ?>" required>

            <label>
                <input type="checkbox" name="update_stock" checked>
                Automatically add to product stock
            </label>

            <button type="submit" class="btn-primary">Save Delivery</button>
            <a href="deliveries.php?supplier_id=<?= $supplier_id ?>" class="btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();
 require_once __DIR__ . '/../../includes/footer.php'; ?>

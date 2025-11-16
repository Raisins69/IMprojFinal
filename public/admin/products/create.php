<?php
require_once __DIR__ . '/../../../includes/config.php';

// Check admin access
checkAdmin();

$msg = "";

// Fetch all active suppliers
$suppliers = [];
try {
    $supplierQuery = "SELECT id, name FROM suppliers ORDER BY name";
    $supplierResult = $conn->query($supplierQuery);
    if ($supplierResult) {
        $suppliers = $supplierResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching suppliers: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $category = trim($_POST['category']);
    $size = trim($_POST['size']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $condition_type = trim($_POST['condition_type']);
    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;

    // Validate inputs
    if (empty($name) || empty($brand) || empty($category) || $price <= 0 || $stock < 0) {
        $msg = "❌ Please fill all required fields with valid data.";
    } elseif (!isset($_FILES["image"]) || $_FILES["image"]["error"] != 0) {
        $msg = "❌ Please upload a valid image.";
    } else {
        $image = $_FILES["image"]["name"];
        $target = "../../uploads/" . basename($image);
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES["image"]["type"], $allowed_types)) {
            $msg = "❌ Only JPG, PNG, GIF, and WEBP images are allowed.";
        } else {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert product
                    $stmt = $conn->prepare("INSERT INTO products (name, brand, category, size, price, stock, condition_type, image)
                                        VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->bind_param("ssssdiis", $name, $brand, $category, $size, $price, $stock, $condition_type, $image);
                    
                    if ($stmt->execute()) {
                        $product_id = $conn->insert_id;
                        
                        // If supplier is selected, add to supplier_products
                        if ($supplier_id > 0) {
                            $supplierStmt = $conn->prepare("INSERT INTO supplier_products (supplier_id, product_id, is_primary) VALUES (?, ?, 1)");
                            $supplierStmt->bind_param("ii", $supplier_id, $product_id);
                            $supplierStmt->execute();
                            $supplierStmt->close();
                        }
                        
                        $conn->commit();
                        $msg = "✅ Product Added Successfully!";
                        // Clear the form
                        $name = $brand = $category = $size = '';
                        $price = $stock = 0;
                } else {
                    throw new Exception("Failed to add product");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "❌ Failed to add product: " . $e->getMessage();
                // Delete the uploaded image if database insert failed
                if (file_exists($target)) {
                    unlink($target);
                }
            }
            } else {
                $msg = "❌ Error uploading image. Please try again.";
            }
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Add Product</h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST" enctype="multipart/form-data">
            <label>Product Name</label>
            <input type="text" name="name" required>

            <label>Brand</label>
            <input type="text" name="brand">

            <label>Category</label>
            <input type="text" name="category" required>

            <label>Size</label>
            <input type="text" name="size" required>

            <label>Price (₱)</label>
            <input type="number" name="price" step="0.01" required>

            <label>Stock Quantity</label>
            <input type="number" name="stock" required>

            <label>Condition Type</label>
            <select name="condition_type">
                <option value="Like New">Like New</option>
                <option value="Good">Good</option>
                <option value="Slightly Used">Slightly Used</option>
            </select>

            <label>Supplier</label>
            <select name="supplier_id" class="form-control" required>
                <option value="">-- Select Supplier --</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= htmlspecialchars($supplier['id']) ?>">
                        <?= htmlspecialchars($supplier['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($suppliers)): ?>
                <p class="text-warning">No suppliers available. <a href="../suppliers/create.php">Add a supplier first</a>.</p>
            <?php endif; ?>

            <label>Product Image</label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
            <small class="text-muted">Allowed formats: JPG, PNG, GIF, WebP. Max size: 2MB</small>

            <button type="submit" class="btn-primary">Save Product</button>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
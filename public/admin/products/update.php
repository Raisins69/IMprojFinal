<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging setup
$debugLogFile = __DIR__ . '/update_debug.log';
file_put_contents($debugLogFile, "[" . date('Y-m-d H:i:s') . "] Script started\n", FILE_APPEND);

function debugLog($message) {
    global $debugLogFile;
    $timestamp = date('Y-m-d H:i:s');
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $caller = isset($backtrace[0]) ? basename($backtrace[0]['file']) . ':' . $backtrace[0]['line'] : '';
    $logMessage = "[$timestamp] [$caller] $message\n";
    file_put_contents($debugLogFile, $logMessage, FILE_APPEND);
}

require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

debugLog("Session data: " . print_r($_SESSION, true));

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$id = intval($_GET['id']);
$msg = "";

// Debug: Log the current request
error_log("Update product request for ID: " . $id);

// Fetch all suppliers
$suppliers = [];
try {
    $supplierResult = $conn->query("SELECT id, name FROM suppliers ORDER BY name");
    if ($supplierResult) {
        $suppliers = $supplierResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching suppliers: " . $e->getMessage());
}

// Fetch existing product data with supplier information
$stmt = $conn->prepare("
    SELECT p.*, sp.supplier_id, s.name as supplier_name 
    FROM products p
    LEFT JOIN supplier_products sp ON p.id = sp.product_id AND sp.is_primary = 1
    LEFT JOIN suppliers s ON sp.supplier_id = s.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: read.php");
    exit();
}

// Log basic request info for debugging
error_log("Processing update for product ID: " . $id);

// Update processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    debugLog("=== FORM SUBMITTED ===");
    debugLog("POST data: " . print_r($_POST, true));
    debugLog("FILES data: " . print_r($_FILES, true));
    
    // Log current directory and permissions
    debugLog("Current directory: " . __DIR__);
    debugLog("Upload directory exists: " . (is_dir('../../uploads') ? 'Yes' : 'No'));
    debugLog("Upload directory writable: " . (is_writable('../../uploads') ? 'Yes' : 'No'));
    
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $size = isset($_POST['size']) ? trim($_POST['size']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $condition_type = isset($_POST['condition_type']) ? trim($_POST['condition_type']) : '';
    $imageName = $product['image']; // default: no image change
    $supplier_id = (isset($_POST['supplier_id']) && $_POST['supplier_id'] !== '') ? intval($_POST['supplier_id']) : null;
    
    // Debug output - removed for cleaner UI
    // echo '<div style="background: #e2f0fd; padding: 10px; margin: 10px 0; border: 1px solid #b8daff; border-radius: 4px;">';
    // echo '<h4>Processed Form Data:</h4>';
    // echo '<pre>';
    // echo "Name: $name\n";
    // echo "Brand: $brand\n";
    // echo "Category: $category\n";
    // echo "Size: $size\n";
    // echo "Price: $price\n";
    // echo "Stock: $stock\n";
    // echo "Condition: $condition_type\n";
    // echo "Supplier ID: $supplier_id\n";
    // echo "Current Image: $imageName\n";
    // echo '</pre>';
    // echo '</div>';
    
    // Debug log form data
    error_log("Form data: " . print_r([
        'name' => $name,
        'brand' => $brand,
        'category' => $category,
        'size' => $size,
        'price' => $price,
        'stock' => $stock,
        'condition' => $condition_type,
        'supplier_id' => $supplier_id
    ], true));

    // Validate inputs
    $validationErrors = [];
    if (empty($name)) $validationErrors[] = "Product name is required";
    if (empty($category)) $validationErrors[] = "Category is required";
    if ($price <= 0) $validationErrors[] = "Price must be greater than 0";
    if ($stock < 0) $validationErrors[] = "Stock cannot be negative";
    
    if (!empty($validationErrors)) {
        $msg = "❌ " . implode(", ", $validationErrors);
        debugLog("Validation failed: " . $msg);
    } else {
        // Start transaction
        $conn->begin_transaction();
        $success = false;
        
        try {
            // Handle file upload if a new image is provided
            if (!empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES["image"]["type"];
                
                if (in_array($file_type, $allowed_types)) {
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
                    $imageName = uniqid() . '.' . $file_extension;
                    $target = "../../uploads/" . $imageName;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
                        // Delete old image if it exists and is not the default
                        if (!empty($product['image']) && $product['image'] !== 'default.jpg' && file_exists("../../uploads/" . $product['image'])) {
                            @unlink("../../uploads/" . $product['image']);
                        }
                    } else {
                        throw new Exception("Failed to move uploaded file");
                    }
                } else {
                    throw new Exception("Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.");
                }
            }
            
            // Update the product with supplier_id directly
            $updateQuery = "UPDATE products SET 
                name = ?,
                brand = ?,
                category = ?,
                size = ?,
                price = ?,
                stock = ?,
                condition_type = ?,
                image = ?,
                supplier_id = ?
                WHERE id = ?";
            
            debugLog("Preparing query: $updateQuery");
            $stmt = $conn->prepare($updateQuery);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Bind parameters with proper NULL handling for supplier_id
            $types = "ssssdsss";
            $params = [
                $name, 
                $brand, 
                $category, 
                $size, 
                $price, 
                $stock, 
                $condition_type, 
                $imageName
            ];
            
            // Add supplier_id with proper type
            if ($supplier_id !== null) {
                $types .= "i";
                $params[] = $supplier_id;
            } else {
                $types .= "s";
                $params[] = null;
            }
            
            // Add the ID
            $types .= "i";
            $params[] = $id;
            
            // Bind parameters
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update product: " . $stmt->error);
            }
            $stmt->close();
            
            // Update or insert supplier relationship
            if ($supplier_id !== null) {
                // Check if relationship already exists
                $checkStmt = $conn->prepare("SELECT id FROM supplier_products WHERE product_id = ?");
                $checkStmt->bind_param("i", $id);
                $checkStmt->execute();
                $exists = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();
                
                if ($exists) {
                    // Update existing relationship
                    $relStmt = $conn->prepare("UPDATE supplier_products SET supplier_id = ?, is_primary = 1 WHERE product_id = ?");
                    $relStmt->bind_param("ii", $supplier_id, $id);
                } else {
                    // Insert new relationship
                    $relStmt = $conn->prepare("INSERT INTO supplier_products (supplier_id, product_id, is_primary) VALUES (?, ?, 1)");
                    $relStmt->bind_param("ii", $supplier_id, $id);
                }
                
                if (!$relStmt->execute()) {
                    throw new Exception("Failed to update supplier relationship: " . $relStmt->error);
                }
                $relStmt->close();
            } else {
                // If no supplier is selected, remove any existing relationship
                $delStmt = $conn->prepare("DELETE FROM supplier_products WHERE product_id = ?");
                $delStmt->bind_param("i", $id);
                $delStmt->execute();
                $delStmt->close();
            }
            
            // Commit transaction
            $conn->commit();
            $success = true;
            $msg = "✅ Product updated successfully!";
            $_SESSION['success'] = $msg;
            debugLog($msg);
            
            // Redirect after successful update
            header("Location: read.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $msg = "❌ Error: " . $e->getMessage();
            error_log("Update failed: " . $msg);
            
            // Clean up uploaded file if something went wrong
            if (isset($imageName) && $imageName !== $product['image'] && file_exists("../../uploads/" . $imageName)) {
                @unlink("../../uploads/" . $imageName);
            }
        }

        // Refresh product data with supplier info for display
        debugLog("Refreshing product data for display");
        $refreshQuery = "SELECT p.*, s.name as supplier_name 
                        FROM products p 
                        LEFT JOIN suppliers s ON p.supplier_id = s.id 
                        WHERE p.id = ?";
        $stmt = $conn->prepare($refreshQuery);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result) {
                    $product = $result->fetch_assoc();
                    debugLog("Refreshed product data: " . print_r($product, true));
                }
            }
            $stmt->close();
        }
    }
}

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Product</h2>
        <?php if (!empty($msg)): ?>
            <div class="alert <?= strpos($msg, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        
        <form class="form-box" method="POST" enctype="multipart/form-data" id="productForm" onsubmit="return validateForm()">
    <input type="hidden" name="debug" value="1">
            <label>Product Name</label>
            <input type="text" name="name" value="<?= $product['name']; ?>" required>

            <label>Brand</label>
            <input type="text" name="brand" value="<?= $product['brand']; ?>">

            <label>Category</label>
            <input type="text" name="category" value="<?= $product['category']; ?>" required>

            <label>Size</label>
            <input type="text" name="size" value="<?= $product['size']; ?>" required>

            <label>Price (₱)</label>
            <input type="number" name="price" step="0.01" value="<?= $product['price']; ?>" required>

            <label>Stock Quantity</label>
            <input type="number" name="stock" value="<?= $product['stock']; ?>" required>

            <label>Condition</label>
            <select name="condition_type">
                <option <?= $product['condition_type']=="Like New"?"selected":""; ?>>Like New</option>
                <option <?= $product['condition_type']=="Good"?"selected":""; ?>>Good</option>
                <option <?= $product['condition_type']=="Slightly Used"?"selected":""; ?>>Slightly Used</option>
            </select>

            <label>Product Image (Upload only if you want to replace)</label>
            <img src="../../uploads/<?= $product['image']; ?>" height="80"><br><br>
            <input type="file" name="image">

            <div class="form-group">
                <label>Supplier</label>
                <select name="supplier_id" class="form-control">
                    <option value="">-- No Supplier --</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= $supplier['id'] ?>" <?= (isset($product['supplier_id']) && $product['supplier_id'] == $supplier['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($supplier['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($product['supplier_name']) && $product['supplier_name']): ?>
                    <small class="form-text text-muted">Current supplier: <?= htmlspecialchars($product['supplier_name']) ?></small>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Product
            </button>
            <a href="read.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </form>
    </main>
</div>

<script>
function validateForm() {
    console.log("Form validation started");
    const requiredFields = ['name', 'category', 'price', 'stock'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const element = document.querySelector(`[name="${field}"]`);
        if (element && !element.value.trim()) {
            alert(`Please fill in the ${field.replace('_', ' ')} field`);
            element.focus();
            isValid = false;
            return false;
        }
    });
    
    const price = document.querySelector('[name="price"]');
    if (price && parseFloat(price.value) <= 0) {
        alert('Price must be greater than 0');
        price.focus();
        return false;
    }
    
    const stock = document.querySelector('[name="stock"]');
    if (stock && parseInt(stock.value) < 0) {
        alert('Stock cannot be negative');
        stock.focus();
        return false;
    }
    
    return isValid;
}
</script>

<?php include '../../../includes/footer.php'; ?>
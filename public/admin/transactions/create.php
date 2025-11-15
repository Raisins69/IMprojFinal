<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

$msg = "";

// Debug customer query
$customers = $conn->query("SELECT * FROM customers ORDER BY name");
if (!$customers) {
    die("Error fetching customers: " . $conn->error);
}

// Debug: Check number of customers
$customer_count = $customers->num_rows;
error_log("Number of customers found: " . $customer_count);

// Debug: Fetch all customers and log them
$all_customers = [];
while ($row = $customers->fetch_assoc()) {
    $all_customers[] = $row;
}
error_log("Customers data: " . print_r($all_customers, true));

// Reset pointer back to start for the actual display
$customers->data_seek(0);

// Debug products query
$products = $conn->query("SELECT id, name, price, stock, image FROM products WHERE stock > 0 ORDER BY name");
if (!$products) {
    die("Error fetching products: " . $conn->error);
}

// Debug: Check number of products
$product_count = $products->num_rows;
error_log("Number of products found: " . $product_count);

// Debug: Fetch all products and log them
$all_products = [];
while ($row = $products->fetch_assoc()) {
    $all_products[] = $row;
}
error_log("Products data: " . print_r($all_products, true));

// Reset pointer back to start for the actual display
$products->data_seek(0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = intval($_POST['customer_id']);
    $payment_method = trim($_POST['payment_method']);
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    
    if (empty($customer_id) || empty($product_ids)) {
        $msg = "❌ Please select customer and at least one product.";
    } else {
        $conn->begin_transaction();
        
        try {
            $total = 0;
            $order_items = [];
            
            // Validate products and calculate total
            foreach ($product_ids as $index => $product_id) {
                $qty = intval($quantities[$index]);
                if ($qty <= 0) continue;
                
                $stmt = $conn->prepare("SELECT price, stock FROM products WHERE id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                if (!$product || $product['stock'] < $qty) {
                    throw new Exception("Insufficient stock for product ID: $product_id");
                }
                
                $order_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $qty,
                    'price' => $product['price']
                ];
                
                $total += $product['price'] * $qty;
            }
            
            if (empty($order_items)) {
                throw new Exception("No valid products selected");
            }
            
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date, payment_method, total_amount, status) 
                                   VALUES (?, NOW(), ?, ?, 'Completed')");
            $stmt->bind_param("isd", $customer_id, $payment_method, $total);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Insert order items and update stock
            foreach ($order_items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
                
                // Update stock
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt->execute();
            }
            
            $conn->commit();
            $msg = "✅ Transaction created successfully! Order ID: $order_id";
            
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "❌ Failed: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php
    // Config already included at the top
    require_once '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Create Manual Transaction</h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST">
            <label>Customer *</label>
            <select name="customer_id" required>
                <option value="">Select Customer</option>
                <?php if ($customer_count > 0): ?>
                    <?php while($customer = $customers->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($customer['id']) ?>">
                            <?= htmlspecialchars($customer['name']) ?> - 
                            <?= htmlspecialchars($customer['email']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="">No customers found in database</option>
                <?php endif; ?>
            </select>

            <label>Payment Method</label>
            <select name="payment_method">
                <option value="Cash">Cash</option>
                <option value="GCash">GCash</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Debit Card">Debit Card</option>
                <option value="Bank Transfer">Bank Transfer</option>
            </select>

            <h3>Products</h3>
            <div id="products-container">
                <div class="product-row" style="display: grid; grid-template-columns: 2fr 1fr 50px; gap: 10px; margin-bottom: 10px;">
                    <select name="product_id[]" required>
                        <option value="">Select Product</option>
                        <?php 
                        if ($products && $products->num_rows > 0) {
                            $products->data_seek(0); // Reset pointer to start
                            while($product = $products->fetch_assoc()): 
                        ?>
                            <option value="<?= $product['id'] ?>">
                                <?= htmlspecialchars($product['name']) ?> - 
                                ₱<?= number_format($product['price'], 2) ?> 
                                (Stock: <?= $product['stock'] ?>)
                            </option>
                        <?php 
                            endwhile;
                            $products->data_seek(0); // Reset pointer again for any future use
                        } else {
                            echo '<option value="">No products available</option>';
                        }
                        ?>
                    </select>
                    <input type="number" name="quantity[]" placeholder="Qty" min="1" value="1" required>
                    <button type="button" onclick="removeProduct(this)" class="btn-delete">✖</button>
                </div>
            </div>

            <button type="button" onclick="addProduct()" class="btn-secondary">➕ Add Product</button>
            <br><br>

            <button type="submit" class="btn-primary">Create Transaction</button>
            <a href="read.php" class="btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<script>
function addProduct() {
    const container = document.getElementById('products-container');
    const firstRow = container.querySelector('.product-row');
    const newRow = firstRow.cloneNode(true);
    container.appendChild(newRow);
}

function removeProduct(btn) {
    const container = document.getElementById('products-container');
    if (container.querySelectorAll('.product-row').length > 1) {
        btn.closest('.product-row').remove();
    } else {
        alert('At least one product is required');
    }
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

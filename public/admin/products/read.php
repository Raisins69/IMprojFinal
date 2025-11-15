<?php
include __DIR__ . '/../../../includes/config.php';

// Check admin access
checkAdmin();

include '../../../includes/header.php';

// Handle search and filter (FR1.4)
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$size = $_GET['size'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

$query = "SELECT p.*, s.name as supplier_name 
          FROM products p
          LEFT JOIN supplier_products sp ON p.id = sp.product_id AND sp.is_primary = 1
          LEFT JOIN suppliers s ON sp.supplier_id = s.id
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($size)) {
    $query .= " AND size = ?";
    $params[] = $size;
    $types .= "s";
}

if (!empty($min_price)) {
    $query .= " AND price >= ?";
    $params[] = floatval($min_price);
    $types .= "d";
}

if (!empty($max_price)) {
    $query .= " AND price <= ?";
    $params[] = floatval($max_price);
    $types .= "d";
}

$query .= " ORDER BY id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get distinct categories and sizes for filters
$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
$sizes = $conn->query("SELECT DISTINCT size FROM products ORDER BY size");
?>

<div class="admin-container">
    <?php include __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Products List</h2>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <a href="<?= ADMIN_URL ?>/products/create.php" class="btn-primary">➕ Add Product</a>

        <!-- Search and Filter Form -->
        <form method="GET" class="form-box" style="margin: 20px 0;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
                <div>
                    <label>Search Name/Brand</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
                </div>
                <div>
                    <label>Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category == $cat['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label>Size</label>
                    <select name="size">
                        <option value="">All Sizes</option>
                        <?php while($sz = $sizes->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($sz['size']) ?>" <?= $size == $sz['size'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sz['size']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label>Min Price</label>
                    <input type="number" name="min_price" value="<?= htmlspecialchars($min_price) ?>" placeholder="Min" step="0.01">
                </div>
                <div>
                    <label>Max Price</label>
                    <input type="number" name="max_price" value="<?= htmlspecialchars($max_price) ?>" placeholder="Max" step="0.01">
                </div>
            </div>
            <button type="submit" class="btn-primary">🔍 Filter</button>
            <a href="read.php" class="btn-secondary">Clear</a>
        </form>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Size</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Condition</th>
                    <th>Supplier</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($row['image']); ?>" height="50" alt="<?= htmlspecialchars($row['name']) ?>"></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['brand']); ?></td>
                        <td><?= htmlspecialchars($row['category']); ?></td>
                        <td><?= htmlspecialchars($row['size']); ?></td>
                        <td>₱<?= number_format($row['price'], 2); ?></td>
                        <td><?= htmlspecialchars($row['stock']); ?></td>
                        <td><?= htmlspecialchars($row['condition_type']); ?></td>
                        <td><?= !empty($row['supplier_name']) ? htmlspecialchars($row['supplier_name']) : 'No Supplier'; ?></td>
                        <td>
                            <a class="btn-edit" href="<?= ADMIN_URL ?>/products/update.php?id=<?= intval($row['id']); ?>">✏ Edit</a>
                            <a class="btn-delete" href="<?= ADMIN_URL ?>/products/delete.php?id=<?= intval($row['id']); ?>" onclick="return confirm('Delete this product?');">🗑 Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
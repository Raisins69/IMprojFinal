<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
$error = '';
$suppliers = [];

try {
    // Build query with prepared statements
    $query = "SELECT * FROM suppliers WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR contact_person LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    $query .= " ORDER BY id DESC";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception('Failed to get result set: ' . $stmt->error);
    }
    
    $suppliers = $result->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error = 'Error loading suppliers: ' . $e->getMessage();
    error_log($e->getMessage());
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php
// Include config and check admin access
require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Suppliers List</h2>
        <a href="create.php" class="btn-primary">➕ Add Supplier</a>
        <a href="view_products.php" class="btn-primary">📦 View Products by Supplier</a>

        <!-- Search Form -->
        <form method="GET" class="form-box" style="margin: 20px 0;">
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or contact person...">
                <div>
                    <button type="submit" class="btn-primary">🔍 Search</button>
                    <a href="read.php" class="btn-secondary">Clear</a>
                </div>
            </div>
        </form>

        <table class="styled-table">
            <thead>
                <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Contact Person</th>
    <th>Contact Number</th>
    <th>Email</th>
    <th>Address</th>
    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($error)): ?>
                    <tr><td colspan="7" class="error"><?= htmlspecialchars($error) ?></td></tr>
                <?php elseif (empty($suppliers)): ?>
                    <tr><td colspan="7">No suppliers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['id']); ?></td>
    <td><?= htmlspecialchars($row['name']); ?></td>
    <td><?= htmlspecialchars($row['contact_person']); ?></td>
    <td><?= htmlspecialchars($row['contact_number']); ?></td>
    <td><?= htmlspecialchars($row['email']); ?></td>
    <td><?= htmlspecialchars($row['address']); ?></td>
    <td>
        <a class="btn-view" href="deliveries.php?supplier_id=<?= intval($row['id']); ?>">📦 Deliveries</a>
        <a class="btn-edit" href="update.php?id=<?= intval($row['id']); ?>">✏ Edit</a>
        <a class="btn-delete" href="#" 
           onclick="if(confirm('Are you sure you want to delete this supplier? This action cannot be undone.')) { 
               window.location.href='delete.php?id=<?= intval($row['id']) ?>&token=<?= $_SESSION['csrf_token'] ?>';
           } return false;" 
           title="Delete Supplier">🗑 Delete</a>
    </td>
</tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

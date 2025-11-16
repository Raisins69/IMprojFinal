<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

// Initialize variables
$message = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_STRING) ?? '';
$users = [];
$error = '';

try {
    // Prepare and execute query
    $query = "SELECT id, username, email, phone, role, is_active, created_at 
              FROM users 
              ORDER BY id DESC";
              
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception('Failed to get result set: ' . $stmt->error);
    }
    
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error = 'Error loading users: ' . $e->getMessage();
    error_log($e->getMessage());
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Users Management</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($error)): ?>
                    <tr><td colspan="8" class="error"><?= htmlspecialchars($error) ?></td></tr>
                <?php elseif (empty($users)): ?>
                    <tr><td colspan="8">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $row): 
                        $is_active = isset($row['is_active']) ? (bool)$row['is_active'] : true;
                        $status_color = $is_active ? '#10b981' : '#ef4444';
                        $status_text = $is_active ? 'Active' : 'Inactive';
                        $is_current_user = isset($_SESSION['user_id']) && $row['id'] == $_SESSION['user_id'];
                    ?>
                <tr style="<?= isset($row['is_active']) && !$row['is_active'] ? 'opacity: 0.5;' : '' ?>">
                    <td><?= htmlspecialchars($row['id']); ?></td>
                    <td><?= htmlspecialchars($row['username']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span style="padding: 0.25rem 0.75rem; border-radius: 12px; background: <?= $row['role'] == 'admin' ? '#9b4de0' : '#10b981' ?>; color: white; font-size: 0.85rem;">
                            <?= htmlspecialchars($row['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $is_active = isset($row['is_active']) ? $row['is_active'] : 1;
                        $status_color = $is_active ? '#10b981' : '#ef4444';
                        $status_text = $is_active ? 'Active' : 'Inactive';
                        ?>
                        <span style="padding: 0.25rem 0.75rem; border-radius: 12px; background: <?= $status_color ?>; color: white; font-size: 0.85rem;">
                            <?= $status_text ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['created_at'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if ($row['role'] == 'customer'): ?>
                            <a class="btn-view" href="view.php?id=<?= intval($row['id']); ?>">👁 View</a>
                        <?php endif; ?>
                        <a class="btn-edit" href="update.php?id=<?= intval($row['id']); ?>">✏ Edit</a>
                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                            <a class="btn-<?= $is_active ? 'delete' : 'edit' ?>" 
                               href="toggle_status.php?id=<?= intval($row['id']); ?>" 
                               onclick="return confirm('<?= $is_active ? 'Deactivate' : 'Activate' ?> this user?');">
                                <?= $is_active ? '🚫 Deactivate' : '✅ Activate' ?>
                            </a>
                            <a class="btn-delete" href="delete.php?id=<?= intval($row['id']); ?>" 
                               onclick="return confirm('Delete this user permanently?');">🗑 Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

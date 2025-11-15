<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

// Initialize variables
$error = '';
$success = '';
$customer = null;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Validate ID
if (!$id) {
    $_SESSION['error'] = 'Invalid user ID';
    header("Location: read.php");
    exit();
}

try {
    // Get user data
    $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
    if ($stmt === false) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    if (!$customer) {
        $_SESSION['error'] = 'User not found';
        header("Location: read.php");
        exit();
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
        // Validate and sanitize input
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $role = in_array($_POST['role'], ['customer', 'admin']) ? $_POST['role'] : 'customer';
        
        // Basic validation
        if (empty($username) || empty($email)) {
            throw new Exception('Username and email are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check for duplicate email
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $email, $id);
        if (!$checkStmt->execute()) {
            throw new Exception('Database error checking for duplicate email');
        }
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            throw new Exception('Email already exists');
        }
        
        // Update user
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("sssi", $username, $email, $role, $id);
        if (!$stmt->execute()) {
            throw new Exception('Update failed: ' . $stmt->error);
        }
        
        $_SESSION['success'] = 'User updated successfully';
        header("Location: read.php");
        exit();
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log('User update error: ' . $e->getMessage());
}

require_once __DIR__ . '/../../../includes/header.php';
?>

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit User</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form class="form-box" method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?= htmlspecialchars($customer['username'] ?? '') ?>" 
                       required minlength="3" maxlength="50"
                       pattern="[a-zA-Z0-9_\-.]{3,50}"
                       title="Username must be 3-50 characters and can only contain letters, numbers, underscores, hyphens, and periods">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($customer['email'] ?? '') ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="customer" <?= ($customer['role'] ?? '') == 'customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="admin" <?= ($customer['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            
            <?php if (isset($customer['created_at'])): ?>
            <div class="form-group">
                <label>Member Since</label>
                <p class="form-control-static"><?= htmlspecialchars($customer['created_at']) ?></p>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" name="update" class="btn-primary">
                    <span class="btn-icon">💾</span> Update User
                </button>
                <a href="read.php" class="btn-secondary">
                    <span class="btn-icon">✕</span> Cancel
                </a>
            </div>
            
            <script>
            function validateForm() {
                const username = document.getElementById('username').value.trim();
                const email = document.getElementById('email').value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (username.length < 3 || username.length > 50) {
                    alert('Username must be between 3 and 50 characters');
                    return false;
                }
                
                if (!emailRegex.test(email)) {
                    alert('Please enter a valid email address');
                    return false;
                }
                
                return confirm('Are you sure you want to update this user?');
            }
            </script>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

// Initialize variables
$error = '';
$formData = [
    'name' => '',
    'contact_person' => '',
    'contact_number' => '',
    'email' => '',
    'address' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    try {
        // Sanitize and validate input
        $formData = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'contact_person' => filter_input(INPUT_POST, 'contact_person', FILTER_SANITIZE_STRING),
            'contact_number' => filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'address' => filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING)
        ];

        // Basic validation
        if (empty($formData['name']) || empty($formData['contact_person'])) {
            throw new Exception('Name and Contact Person are required');
        }

        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }

        // Check for duplicate supplier name or email
        $checkStmt = $conn->prepare("SELECT id FROM suppliers WHERE name = ? OR email = ?");
        $checkStmt->bind_param("ss", $formData['name'], $formData['email']);
        if (!$checkStmt->execute()) {
            throw new Exception('Database error checking for duplicate supplier');
        }
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            throw new Exception('A supplier with this name or email already exists');
        }

        // Insert new supplier
        $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, contact_number, email, address) VALUES (?, ?, ?, ?, ?)");
        if ($stmt === false) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("sssss", 
            $formData['name'], 
            $formData['contact_person'], 
            $formData['contact_number'], 
            $formData['email'], 
            $formData['address']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to add supplier: ' . $stmt->error);
        }

        // Redirect with success message
        $_SESSION['success'] = 'Supplier added successfully';
        header("Location: read.php");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('Supplier creation error: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();
 require_once '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Add New Supplier</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form class="form-box" method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="name">Supplier Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" 
                       value="<?= htmlspecialchars($formData['name']) ?>" 
                       required minlength="2" maxlength="100"
                       pattern="[\w\s\-\.]{2,100}"
                       title="Supplier name must be 2-100 characters">
            </div>

            <div class="form-group">
                <label for="contact_person">Contact Person <span class="required">*</span></label>
                <input type="text" id="contact_person" name="contact_person"
                       value="<?= htmlspecialchars($formData['contact_person']) ?>" 
                       required minlength="2" maxlength="100">
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number"
                       value="<?= htmlspecialchars($formData['contact_number']) ?>"
                       pattern="[\d\s\-+()]{10,20}"
                       title="Please enter a valid phone number">
            </div>

            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($formData['email']) ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?= 
                    htmlspecialchars($formData['address']) 
                ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="save" class="btn-primary">
                    <span class="btn-icon">💾</span> Save Supplier
                </button>
                <a href="read.php" class="btn-secondary">
                    <span class="btn-icon">✕</span> Cancel
                </a>
            </div>
            
            <script>
            function validateForm() {
                const name = document.getElementById('name').value.trim();
                const contactPerson = document.getElementById('contact_person').value.trim();
                const email = document.getElementById('email').value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (name.length < 2 || name.length > 100) {
                    alert('Supplier name must be between 2 and 100 characters');
                    return false;
                }
                
                if (contactPerson.length < 2 || contactPerson.length > 100) {
                    alert('Contact person name must be between 2 and 100 characters');
                    return false;
                }
                
                if (email && !emailRegex.test(email)) {
                    alert('Please enter a valid email address');
                    return false;
                }
                
                return confirm('Are you sure you want to add this supplier?');
            }
            </script>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

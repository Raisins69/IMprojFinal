<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

// Initialize variables
$msg = '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Validate ID
if (!$id) {
    header("Location: read.php");
    exit();
}

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();

if (!$supplier) {
    header("Location: read.php");
    exit();
}

// Update processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    // Validate inputs
    if (empty($name) || empty($contact_person) || empty($email)) {
        $msg = "❌ Please fill all required fields.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "❌ Please enter a valid email address.";
    } else {
        // Check for duplicate supplier (name or email)
        $checkStmt = $conn->prepare("SELECT id FROM suppliers WHERE (name = ? OR email = ?) AND id != ?");
        $checkStmt->bind_param("ssi", $name, $email, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            $msg = "❌ A supplier with this name or email already exists.";
        } else {
            if (empty($msg)) {
                $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, contact_number=?, email=?, address=? WHERE id=?");
                $stmt->bind_param("sssssi", $name, $contact_person, $contact_number, $email, $address, $id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = '✅ Supplier updated successfully';
                    header("refresh:1; url=read.php");
                    exit();
                } else {
                    $msg = "❌ Update failed: " . $conn->error;
                }
            }
        }
    }
    
    // Refresh supplier data
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $supplier = $stmt->get_result()->fetch_assoc();
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php
 require_once '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Supplier</h2>
        
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
                <label for="name">Supplier Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" 
                       value="<?= htmlspecialchars($supplier['name'] ?? '') ?>" 
                       required minlength="2" maxlength="100"
                       pattern="[\w\s\-\.]{2,100}"
                       title="Supplier name must be 2-100 characters">
            </div>

            <div class="form-group">
                <label for="contact_person">Contact Person <span class="required">*</span></label>
                <input type="text" id="contact_person" name="contact_person"
                       value="<?= htmlspecialchars($supplier['contact_person'] ?? '') ?>" 
                       required minlength="2" maxlength="100">
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number"
                       value="<?= htmlspecialchars($supplier['contact_number'] ?? '') ?>"
                       pattern="[\d\s\-+()]{10,20}"
                       title="Please enter a valid phone number">
            </div>

            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($supplier['email'] ?? '') ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?= 
                    htmlspecialchars($supplier['address'] ?? '') 
                ?></textarea>
            </div>
            

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <span class="btn-icon">💾</span> Update Supplier
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
                
                // Basic validation
                if (name.length < 2 || name.length > 100) {
                    alert('Supplier name must be between 2 and 100 characters');
                    return false;
                }
                
                if (contactPerson.length < 2 || contactPerson.length > 100) {
                    alert('Contact person name must be between 2 and 100 characters');
                    return false;
                }
                
                if (!emailRegex.test(email)) {
                    alert('Please enter a valid email address');
                    return false;
                }
                
                // File validation if a file is selected
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const fileSize = file.size / 1024 / 1024; // in MB
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    
                    if (!validTypes.includes(file.type)) {
                        alert('Only JPG, PNG, GIF, and WEBP images are allowed');
                        return false;
                    }
                    
                    if (fileSize > 5) { // 5MB limit
                        alert('File size must be less than 5MB');
                        return false;
                    }
                }
                
                return confirm('Are you sure you want to update this supplier?');
            }
            </script>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

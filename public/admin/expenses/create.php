<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $expense_date = trim($_POST['expense_date']);

    // Validate inputs
    if (empty($description) || $amount <= 0 || empty($expense_date)) {
        $msg = "❌ Please fill all required fields with valid data.";
    } else {
        $stmt = $conn->prepare("INSERT INTO expenses (description, amount, category, expense_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $description, $amount, $category, $expense_date);

        if ($stmt->execute()) {
            $msg = "✅ Expense Added Successfully!";
        } else {
            $msg = "❌ Failed to add expense.";
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Add Expense</h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST">
            <label>Description *</label>
            <input type="text" name="description" required>

            <label>Amount (₱) *</label>
            <input type="number" name="amount" step="0.01" required>

            <label>Category</label>
            <select name="category">
                <option value="Utilities">Utilities</option>
                <option value="Rent">Rent</option>
                <option value="Supplies">Supplies</option>
                <option value="Marketing">Marketing</option>
                <option value="Salary">Salary</option>
                <option value="Other">Other</option>
            </select>

            <label>Expense Date *</label>
            <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required>

            <button type="submit" class="btn-primary">Save Expense</button>
            <a href="read.php" class="btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

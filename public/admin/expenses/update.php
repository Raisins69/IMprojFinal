<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$id = intval($_GET['id']);
$msg = "";

// Fetch expense data
$stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$expense = $result->fetch_assoc();

if (!$expense) {
    header("Location: read.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $expense_date = trim($_POST['expense_date']);

    if (empty($description) || $amount <= 0 || empty($expense_date)) {
        $msg = "❌ Please fill all required fields with valid data.";
    } else {
        $stmt = $conn->prepare("UPDATE expenses SET description = ?, amount = ?, category = ?, expense_date = ? WHERE id = ?");
        $stmt->bind_param("sdssi", $description, $amount, $category, $expense_date, $id);

        if ($stmt->execute()) {
            $msg = "✅ Expense Updated Successfully!";
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $expense = $result->fetch_assoc();
        } else {
            $msg = "❌ Failed to update expense.";
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Expense</h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST">
            <label>Description *</label>
            <input type="text" name="description" value="<?= htmlspecialchars($expense['description']) ?>" required>

            <label>Amount (₱) *</label>
            <input type="number" name="amount" step="0.01" value="<?= htmlspecialchars($expense['amount']) ?>" required>

            <label>Category</label>
            <select name="category">
                <option value="Utilities" <?= $expense['category'] == 'Utilities' ? 'selected' : '' ?>>Utilities</option>
                <option value="Rent" <?= $expense['category'] == 'Rent' ? 'selected' : '' ?>>Rent</option>
                <option value="Supplies" <?= $expense['category'] == 'Supplies' ? 'selected' : '' ?>>Supplies</option>
                <option value="Marketing" <?= $expense['category'] == 'Marketing' ? 'selected' : '' ?>>Marketing</option>
                <option value="Salary" <?= $expense['category'] == 'Salary' ? 'selected' : '' ?>>Salary</option>
                <option value="Other" <?= $expense['category'] == 'Other' ? 'selected' : '' ?>>Other</option>
            </select>

            <label>Expense Date *</label>
            <input type="date" name="expense_date" value="<?= htmlspecialchars($expense['expense_date']) ?>" required>

            <button type="submit" class="btn-primary">Update Expense</button>
            <a href="read.php" class="btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

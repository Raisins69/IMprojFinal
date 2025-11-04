<?php
include '../includes/config.php';

$message = "";

if (isset($_GET['registered'])) {
    $message = "Registration Successful! Please Login.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            
            // Set role-specific session variables for backward compatibility
            if ($row['role'] == "admin") {
                $_SESSION['admin_id'] = $row['id'];
                header("Location: admin/dashboard.php");
            } else {
                $_SESSION['customer_id'] = $row['id'];
                header("Location: customer/dashboard.php");
            }
            exit();
        } else {
            $message = "Incorrect Password!";
        }
    } else {
        $message = "Email not found!";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="form-container">
    <h2>Login</h2>

    <?php if($message) echo "<p class='alert'>" . htmlspecialchars($message) . "</p>"; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>

        <input type="password" name="password" placeholder="Password" required>
        
        <button type="submit">Login</button>
    </form>

    <p>Don't have an account?
    <a href="register.php">Register</a></p>
</div>

<?php include '../includes/footer.php'; ?>

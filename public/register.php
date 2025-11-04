<?php
include '../includes/config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = "Email already registered!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
            header("Location: login.php?registered=true");
            exit();
        } else {
            $message = "Registration failed!";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="form-container">
    <h2>Create an Account</h2>
    
    <?php if($message) echo "<p class='alert'>" . htmlspecialchars($message) . "</p>"; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        
        <input type="email" name="email" placeholder="Email" required>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account?
    <a href="login.php">Login</a></p>
</div>

<?php include '../includes/footer.php'; ?>

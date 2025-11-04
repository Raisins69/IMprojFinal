<?php
// includes/config.php

// Start session globally
if (!isset($_SESSION)) {
    session_start();
}

// Database configuration
$host = "localhost:3306";
$user = "root";
$pass = ""; // Add your database password here
$dbname = "urbanthrift_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Function: Check Login Redirect
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /IMProj/public/login.php");
        exit();
    }
}

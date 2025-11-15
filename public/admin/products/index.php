<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

// Redirect to products list
header("Location: /projectIManagement/public/admin/products/read.php");
exit();
?>

<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

// Redirect to expenses list
header("Location: /projectIManagement/public/admin/expenses/read.php");
exit();
?>

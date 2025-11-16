<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

// Redirect to transactions list
header("Location: /projectIManagement/public/admin/transactions/read.php");
exit();
?>

<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

// Redirect to customers list
header("Location: /projectIManagement/public/admin/customers/read.php");
exit();
?>

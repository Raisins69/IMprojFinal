<?php
// Include config and check admin access
require_once __DIR__ . '/../../includes/config.php';
checkAdmin();

// Redirect to suppliers list
header("Location: /projectIManagement/public/admin/suppliers/read.php");
exit();
?>

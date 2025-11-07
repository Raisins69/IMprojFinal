<?php
// Determine the base path dynamically
$base_path = '/projectIManagement/public/admin/';
?>

<aside class="sidebar">
    <h3>Admin Panel</h3>
    <ul>
        <li><a href="<?= $base_path ?>admin/dashboard.php">📊 Dashboard</a></li>
        <li><a href="<?= $base_path ?>admin/products/read.php">👕 Products</a></li>
        <li><a href="<?= $base_path ?>admin/customers/read.php">👥 Customers</a></li>
        <li><a href="<?= $base_path ?>admin/suppliers/read.php">🚚 Suppliers</a></li>
        <li><a href="<?= $base_path ?>admin/expenses/read.php">💰 Expenses</a></li>
        <li><a href="<?= $base_path ?>admin/transactions/read.php">🧾 Sales</a></li>
        <li><a href="<?= $base_path ?>admin/reports/sales_report.php">📈 Reports</a></li>
        <li><a href="/projectIManagement/public/logout.php">🚪 Logout</a></li>
    </ul>
</aside>
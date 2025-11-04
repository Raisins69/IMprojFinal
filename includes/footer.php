</main>

<footer class="footer">
    <div class="footer-content">

        <div class="footer-section">
            <h3>UrbanThrift</h3>
            <p>Thrift Clothing Shop Management System</p>
        </div>

        <div class="footer-section">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="/public/index.php">Shop</a></li>
                <li><a href="/public/about.php">About</a></li>
                <li><a href="/public/contact.php">Contact</a></li>

                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === "customer"): ?>
                    <li><a href="/public/customer/dashboard.php">My Account</a></li>
                    <li><a href="/public/cart/cart.php">Cart</a></li>

                <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === "admin"): ?>
                    <li><a href="/public/admin/dashboard.php">Admin Panel</a></li>

                <?php else: ?>
                    <li><a href="/public/login.php">Login</a></li>
                    <li><a href="/public/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="footer-section">
            <h4>Help</h4>
            <ul>
                <li><a href="#">FAQs</a></li>
                <li><a href="#">Support</a></li>
                <li><a href="#">Policies</a></li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date("Y") ?> UrbanThrift — All Rights Reserved</p>
    </div>
</footer>

</body>
</html>

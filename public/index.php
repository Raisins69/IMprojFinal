<?php include "../includes/header.php"; ?>
<?php require_once "../includes/config.php"; ?>

<h2 style="color:#E0AAFF;">Shop Thrift Clothing</h2>
<div class="grid">
<?php
$result = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC");
if ($result->num_rows > 0):
    while($row = $result->fetch_assoc()):
?>
    <div class="product-card" onclick="location.href='product_view.php?id=<?= intval($row['id']); ?>'">
        <img src="uploads/<?= htmlspecialchars($row['image']); ?>" style="width:100%; height:180px; object-fit:cover;">
        <h3><?= htmlspecialchars($row['name']); ?></h3>
        <p><?= htmlspecialchars($row['brand']); ?> • <?= htmlspecialchars($row['size']); ?></p>
        <strong>₱<?= number_format($row['price'],2); ?></strong>
    </div>
<?php
    endwhile;
else:
    echo "<p>No products available.</p>";
endif;
?>
</div>

<?php include "../includes/footer.php"; ?>

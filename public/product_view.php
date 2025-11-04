<?php
session_start();
include '../includes/config.php';
include '../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($data['name']) ?> | Product Details</title>
<style>
body { background:#0A0A0F; color:#fff; font-family:Arial; }

.container {
    width:80%; margin:50px auto;
    display:flex; gap:40px;
    align-items:flex-start;
    background:#12121A;
    padding:20px;
    border-radius:10px;
}

img {
    width:280px;
    height:350px;
    border-radius:10px;
    object-fit:cover;
}

button {
    background:#9b4de0; border:none;
    padding:10px 18px; border-radius:6px;
    color:#fff; cursor:pointer;
}
button:hover { background:#7e32bb; }

h2 { color:#C77DFF; }
p { font-size:18px; }
</style>
</head>

<body>

<div class="container">

    <img src="uploads/<?= htmlspecialchars($data['image']) ?>" alt="<?= htmlspecialchars($data['name']) ?>">

    <div>
        <h2><?= htmlspecialchars($data['name']) ?></h2>
        <p><strong>Brand:</strong> <?= htmlspecialchars($data['brand']) ?></p>
        <p><strong>Category:</strong> <?= htmlspecialchars($data['category']) ?></p>
        <p><strong>Size:</strong> <?= htmlspecialchars($data['size']) ?></p>
        <p><strong>Condition:</strong> <?= htmlspecialchars($data['condition_type']) ?></p>
        <p style="font-size:24px;">💸 Price: ₱<?= number_format($data['price'],2) ?></p>

        <a href="cart/add.php?id=<?= intval($data['id']) ?>">
            <button>Add to Cart 🛒</button>
        </a>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>

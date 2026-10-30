
<?php
require "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = (int)($_POST["product_id"] ?? 0);
    $qty = filter_var($_POST["quantity"] ?? 1, FILTER_VALIDATE_INT);

    $stmt = $conn->prepare(
        "SELECT id, stock FROM products WHERE id=?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();

    if ($p && $qty !== false && $qty > 0 && $qty <= 100
        && $p["stock"] >= $qty) {
        $_SESSION["cart"][$id] =
            ($_SESSION["cart"][$id] ?? 0) + $qty;
        header("Location: cart.php");
        exit;
    }
    $message = "Please check the quantity and available stock.";
}

$search = trim($_GET["q"] ?? "");
$term = "%" . $search . "%";

$stmt = $conn->prepare(
    "SELECT * FROM products
     WHERE name LIKE ? OR category LIKE ?
     ORDER BY id DESC"
);
$stmt->bind_param("ss", $term, $term);
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Shop | Noor Jewels</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <h2>Noor Jewels ✨</h2>
  <nav>
    <a href="index.php">Home</a>
    <a href="products.php">Shop</a>
    <a href="cart.php">My Cart</a>
    <a href="admin.php">Management</a>
  </nav>
</header>

<main class="container">
<h1>Our Jewellery Collection</h1>
<form method="get">
  <input name="q" value="<?= e($search) ?>"
         placeholder="Search necklaces, rings, earrings...">
  <button>Search</button>
</form>

<?php if (!empty($message)): ?>
  <p><?= e($message) ?></p>
<?php endif; ?>

<div class="grid">
<?php while ($p = $products->fetch_assoc()): ?>
  <article class="card">
    <?php if ($p["image_url"]): ?>
      <img src="<?= e($p["image_url"]) ?>"
           alt="<?= e($p["name"]) ?>" loading="lazy">
    <?php endif; ?>
    <h3><?= e($p["name"]) ?></h3>
    <p><?= e($p["description"]) ?></p>
    <p>Category: <?= e($p["category"]) ?></p>
    <p class="price">₹<?= number_format($p["price"], 2) ?></p>
    <p><?= (int)$p["stock"] > 0
      ? "In stock: " . (int)$p["stock"]
      : "Currently out of stock" ?></p>

    <?php if ((int)$p["stock"] > 0): ?>
    <form method="post">
      <input type="hidden" name="product_id"
             value="<?= (int)$p["id"] ?>">
      <label>Quantity</label>
      <input type="number" name="quantity"
             value="1" min="1"
             max="<?= (int)$p["stock"] ?>" required>
      <button type="submit">Add to Cart</button>
    </form>
    <?php endif; ?>
  </article>
<?php endwhile; ?>
</div>
</main>
<footer>Noor Jewels — Elegance in every detail.</footer>
</body>
</html>


<?php
require "config.php";
$result = $conn->query(
    "SELECT * FROM products ORDER BY id DESC LIMIT 6"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Noor Jewels | Jewellery Boutique</title>
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
  <section class="hero">
    <p>TIMELESS BEAUTY • EVERYDAY ELEGANCE</p>
    <h1>Jewellery That Tells Your Story</h1>
    <p>Discover beautiful pieces for your special moments.</p>
    <a class="btn" href="products.php">Explore Collection</a>
  </section>

  <h2>Our Latest Collection</h2>
  <div class="grid">
  <?php while ($p = $result->fetch_assoc()): ?>
    <article class="card">
      <?php if ($p["image_url"]): ?>
        <img src="<?= e($p["image_url"]) ?>"
             alt="<?= e($p["name"]) ?>"
             loading="lazy">
      <?php endif; ?>
      <h3><?= e($p["name"]) ?></h3>
      <p><?= e($p["category"]) ?></p>
      <p class="price">₹<?= number_format($p["price"], 2) ?></p>
      <a class="btn" href="products.php">View Collection</a>
    </article>
  <?php endwhile; ?>
  </div>
</main>

<footer>© <?= date("Y") ?> Noor Jewels | Made with elegance ✨</footer>
</body>
</html>
<?php
require_once "db.php";
?>

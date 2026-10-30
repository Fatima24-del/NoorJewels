
<?php
require "config.php";

$orderId = (int)($_GET["id"] ?? 0);
$lastOrderId = (int)($_SESSION["last_order_id"] ?? 0);

if ($orderId < 1 || $orderId !== $lastOrderId) {
    http_response_code(403);
    exit("Receipt unavailable. Please place an order first.");
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE id=?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    http_response_code(404);
    exit("Order not found.");
}

$stmt = $conn->prepare(
    "SELECT product_name, price, quantity
     FROM order_items WHERE order_id=?"
);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Order Receipt | Noor Jewels</title>
<link rel="stylesheet" href="style.css">
<style>
.receipt {
    max-width: 720px;
    margin: 30px auto;
    background: white;
    padding: 28px;
    border: 1px solid #eadcc9;
    border-radius: 14px;
}
@media print {
    header, .no-print { display: none !important; }
    body { background: white; }
    .receipt { border: 0; margin: 0; max-width: none; }
}
</style>
</head>
<body>
<header class="no-print">
<h2>Noor Jewels ✨</h2>
<nav><a href="index.php">Home</a><a href="products.php">Shop</a></nav>
</header>

<main class="container">
<section class="receipt">
<h1>Noor Jewels</h1>
<h2>Order Receipt</h2>
<p><strong>Receipt Number:</strong> NJ-<?= (int)$order["id"] ?></p>
<p><strong>Date:</strong> <?= e($order["created_at"]) ?></p>
<hr>
<p><strong>Customer:</strong> <?= e($order["customer_name"]) ?></p>
<p><strong>Phone:</strong> <?= e($order["phone"]) ?></p>
<p><strong>Address:</strong><br><?= nl2br(e($order["address"])) ?></p>

<div class="table-wrap">
<table>
<tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
<?php while ($p = $items->fetch_assoc()): ?>
<tr>
<td><?= e($p["product_name"]) ?></td>
<td>₹<?= number_format($p["price"], 2) ?></td>
<td><?= (int)$p["quantity"] ?></td>
<td>₹<?= number_format($p["price"] * $p["quantity"], 2) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<h2>Total: ₹<?= number_format($order["total"], 2) ?></h2>
<p>Order Status: <?= e($order["status"]) ?></p>
<p>Thank you for shopping with Noor Jewels!</p>

<div class="no-print">
<button onclick="window.print()">Print / Save PDF</button>
<a class="btn" href="products.php">Continue Shopping</a>
</div>
</section>
</main>
</body>
</html>

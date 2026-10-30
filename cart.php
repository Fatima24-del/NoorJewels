
<?php
require "config.php";

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["remove_id"])) {
    unset($_SESSION["cart"][(int)$_POST["remove_id"]]);
    header("Location: cart.php");
    exit;
}

$error = "";
$items = [];
$total = 0;

foreach ($_SESSION["cart"] as $id => $qty) {
    $id = (int)$id;
    $qty = (int)$qty;
    if ($qty < 1) continue;

    $stmt = $conn->prepare(
        "SELECT id,name,price,stock FROM products WHERE id=?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();

    if ($p) {
        $p["quantity"] = $qty;
        $p["line_total"] = $p["price"] * $qty;
        $items[] = $p;
        $total += $p["line_total"];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["place_order"])) {
    $name = trim($_POST["customer_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");

    if ($name === "" || strlen($name) > 150
        || !preg_match('/^[0-9+() -]{7,20}$/', $phone)
        || $address === "" || strlen($address) > 2000
        || !$items) {
        $error = "Please enter valid details and check your cart.";
    } else {
        try {
            $conn->begin_transaction();

            // Lock stock records and recheck prices at checkout.
            $fresh = [];
            $total = 0;

            foreach ($_SESSION["cart"] as $id => $qty) {
                $id = (int)$id;
                $qty = (int)$qty;
                if ($qty < 1 || $qty > 100) {
                    throw new Exception("Invalid quantity.");
                }

                $stmt = $conn->prepare(
                    "SELECT id,name,price,stock FROM products
                     WHERE id=? FOR UPDATE"
                );
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $p = $stmt->get_result()->fetch_assoc();

                if (!$p || $p["stock"] < $qty) {
                    throw new Exception(
                        "Stock changed. Please update your cart."
                    );
                }

                $p["quantity"] = $qty;
                $total += $p["price"] * $qty;
                $fresh[] = $p;
            }

            $stmt = $conn->prepare(
                "INSERT INTO orders
                 (customer_name,phone,address,total)
                 VALUES (?,?,?,?)"
            );
            $stmt->bind_param("sssd", $name, $phone, $address, $total);
            $stmt->execute();
            $orderId = $conn->insert_id;

            foreach ($fresh as $p) {
                $stmt = $conn->prepare(
                    "INSERT INTO order_items
                     (order_id,product_id,product_name,price,quantity)
                     VALUES (?,?,?,?,?)"
                );
                $stmt->bind_param(
                    "iisdi",
                    $orderId, $p["id"], $p["name"],
                    $p["price"], $p["quantity"]
                );
                $stmt->execute();

                $stmt = $conn->prepare(
                    "UPDATE products SET stock=stock-?
                     WHERE id=? AND stock>=?"
                );
                $stmt->bind_param(
                    "iii", $p["quantity"], $p["id"], $p["quantity"]
                );
                $stmt->execute();

                if ($stmt->affected_rows !== 1) {
                    throw new Exception("Please try again.");
                }
            }

           $conn->commit();

$_SESSION["last_order_id"] = $orderId;
$_SESSION["cart"] = [];

header("Location: receipt.php?id=" . $orderId);
exit;
        } catch (Throwable $ex) {
            $conn->rollback();
            $error = $ex instanceof mysqli_sql_exception
                ? "Order could not be saved. Please try again."
                : $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your Cart | Noor Jewels</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <h2>Noor Jewels ✨</h2>
  <nav><a href="index.php">Home</a>
       <a href="products.php">Shop</a>
       <a href="admin.php">Management</a></nav>
</header>

<main class="container">
<h1>Your Shopping Cart</h1>
<?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>

<?php if (!$items): ?>
  <p>Your cart is empty. <a href="products.php">Shop now</a></p>
<?php else: ?>
<div class="table-wrap"><table>
<tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr>
<?php foreach ($items as $p): ?>
<tr>
<td><?= e($p["name"]) ?></td>
<td>₹<?= number_format($p["price"], 2) ?></td>
<td><?= (int)$p["quantity"] ?></td>
<td>₹<?= number_format($p["line_total"], 2) ?></td>
<td><form method="post">
<input type="hidden" name="remove_id" value="<?= (int)$p["id"] ?>">
<button>Remove</button>
</form></td>
</tr>
<?php endforeach; ?>
</table></div>

<h2>Total: ₹<?= number_format($total, 2) ?></h2>

<h2>Delivery Details</h2>
<form method="post">
<label>Customer Name</label>
<input name="customer_name" maxlength="150" required>
<label>Mobile Number</label>
<input name="phone" type="tel" maxlength="20" required>
<label>Delivery Address</label>
<textarea name="address" maxlength="2000" required></textarea>
<button name="place_order" value="1">Place Order</button>
</form>
<?php endif; ?>
</main>
</body>
</html>

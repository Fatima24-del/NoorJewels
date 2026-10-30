
<?php
require "config.php";

$adminPassword = "Noor@123"; // Demo only; change before public launch.
$error = "";

if (isset($_POST["logout"])) {
    unset($_SESSION["is_admin"]);
    header("Location: admin.php");
    exit;
}

if (isset($_POST["login_password"])) {
    if (hash_equals($adminPassword, (string)$_POST["login_password"])) {
        session_regenerate_id(true);
        $_SESSION["is_admin"] = true;
        header("Location: admin.php");
        exit;
    }
    $error = "Incorrect password. Please try again.";
}

if (empty($_SESSION["is_admin"])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Owner Login | Noor Jewels</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header><h2>Noor Jewels ✨</h2><nav><a href="index.php">Store</a></nav></header>
<main class="container">
<section class="card">
<h1>Management Login 🔐</h1>
<p>Private access for the shop owner.</p>
<?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>
<form method="post">
<label>Management Password</label>
<input type="password" name="login_password" required>
<button type="submit">Login</button>
</form>
</section>
</main>
</body>
</html>
<?php
exit;
}

// Save a new product or update an existing product.
if (isset($_POST["save_product"])) {
    $id = (int)($_POST["id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = filter_var($_POST["price"] ?? null, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST["stock"] ?? null, FILTER_VALIDATE_INT);
    $image = trim($_POST["image_url"] ?? "");

    $scheme = strtolower((string)parse_url($image, PHP_URL_SCHEME));
    $validImage = $image === "" ||
        (filter_var($image, FILTER_VALIDATE_URL) &&
         in_array($scheme, ["https", "http"], true));

    if ($name === "" || strlen($name) > 150 ||
        $category === "" || strlen($category) > 80 ||
        strlen($description) > 5000 ||
        !$validImage ||
        $price === false || $price < 0 || $price > 99999999 ||
        $stock === false || $stock < 0 || $stock > 100000 ||
        $id < 0) {
        $error = "Please check all product details and the image URL.";
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare(
                "UPDATE products
                 SET name=?, category=?, description=?,
                     price=?, image_url=?, stock=?
                 WHERE id=?"
            );
            $stmt->bind_param(
                "sssd sii", $name, $category, $description,
                $price, $image, $stock, $id
            );
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO products
                 (name, category, description, price, image_url, stock)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sssdsi", $name, $category, $description,
                $price, $image, $stock
            );
        }

       
if ($id > 0) {
    $stmt = $conn->prepare(
        "UPDATE products SET name=?, category=?, description=?,
         price=?, image_url=?, stock=? WHERE id=?"
    );
    $stmt->bind_param(
        "sssdsii", $name, $category, $description,
        $price, $image, $stock, $id
    );
} else {
    $stmt = $conn->prepare(
        "INSERT INTO products
         (name, category, description, price, image_url, stock)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "sssdsi", $name, $category, $description,
        $price, $image, $stock
    );
}
$stmt->execute();
header("Location: admin.php?saved=1");
exit;


        $stmt->execute();
        header("Location: admin.php?saved=1");
        exit;
    }
}

// Delete a product. Existing order history is kept.
if (isset($_POST["delete_product"])) {
    $id = (int)$_POST["delete_product"];
    $stmt = $conn->prepare(
        "UPDATE products SET stock=0 WHERE id=?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Update order status.
if (isset($_POST["update_order"])) {
    $id = (int)($_POST["order_id"] ?? 0);
    $status = $_POST["status"] ?? "";
    $allowed = ["Pending", "Confirmed", "Shipped", "Delivered", "Cancelled"];

    if ($id > 0 && in_array($status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    }
    header("Location: admin.php");
    exit;
}

$edit = null;
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
$orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Management | Noor Jewels</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
<h2>Noor Jewels ✨</h2>
<nav><a href="index.php">Home</a><a href="products.php">Shop</a></nav>
</header>

<main class="container">
<h1>Owner Dashboard</h1>
<?php if (isset($_GET["saved"])): ?>
<p>Product saved successfully!</p>
<?php endif; ?>
<?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>

<section class="card">
<h2><?= $edit ? "Edit Product" : "Add New Product" ?></h2>
<form method="post">
<input type="hidden" name="id" value="<?= (int)($edit["id"] ?? 0) ?>">

<label>Product Name</label>
<input name="name" maxlength="150" required value="<?= e($edit["name"] ?? "") ?>">

<label>Category</label>
<input name="category" maxlength="80" required placeholder="Earrings, Necklace, Bracelet" value="<?= e($edit["category"] ?? "") ?>">

<label>Description</label>
<textarea name="description" maxlength="5000"><?= e($edit["description"] ?? "") ?></textarea>

<label>Price (₹)</label>
<input type="number" name="price" min="0" max="99999999" step="0.01" required value="<?= e($edit["price"] ?? "") ?>">

<label>Stock</label>
<input type="number" name="stock" min="0" max="100000" required value="<?= e($edit["stock"] ?? "1") ?>">

<label>Direct Image URL</label>
<input type="url" id="image_url" name="image_url" placeholder="https://example.com/photo.jpg" value="<?= e($edit["image_url"] ?? "") ?>">
<img id="preview" alt="Image preview" style="display:none;width:160px;height:160px;object-fit:cover;border-radius:10px;margin:8px 0">

<button type="submit" name="save_product" value="1">Save Product</button>
</form>
<p>Image URL public hona chahiye. Save karne se pehle preview check karo.</p>
</section>

<h2>Products</h2>
<div class="table-wrap"><table>
<tr><th>Photo</th><th>Name</th><th>Price</th><th>Stock</th><th>Action</th></tr>
<?php while ($p = $products->fetch_assoc()): ?>
<tr>
<td>
<?php if (!empty($p["image_url"])): ?>
<img src="<?= e($p["image_url"]) ?>" alt="<?= e($p["name"]) ?>"
style="width:65px;height:65px;object-fit:cover"
onerror="this.style.display='none'">
<?php else: ?>No photo<?php endif; ?>
</td>
<td><?= e($p["name"]) ?></td>
<td>₹<?= number_format($p["price"], 2) ?></td>
<td><?= (int)$p["stock"] ?></td>
<td>
<a href="admin.php?edit=<?= (int)$p["id"] ?>">Edit</a>
<form method="post" onsubmit="return confirm('Set this product stock to zero?')">
<button name="delete_product" value="<?= (int)$p["id"] ?>">Remove from Shop</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table></div>

<h2>Customer Orders</h2>
<div class="table-wrap"><table>
<tr><th>Receipt</th><th>Customer</th><th>Phone</th><th>Total</th><th>Status</th><th>Update</th></tr>
<?php while ($o = $orders->fetch_assoc()): ?>
<tr>
<td>NJ-<?= (int)$o["id"] ?></td>
<td><?= e($o["customer_name"]) ?></td>
<td><?= e($o["phone"]) ?></td>
<td>₹<?= number_format($o["total"], 2) ?></td>
<td><?= e($o["status"]) ?></td>
<td><form method="post">
<input type="hidden" name="order_id" value="<?= (int)$o["id"] ?>">
<select name="status">
<?php foreach (["Pending","Confirmed","Shipped","Delivered","Cancelled"] as $s): ?>
<option value="<?= e($s) ?>" <?= $o["status"] === $s ? "selected" : "" ?>><?= e($s) ?></option>
<?php endforeach; ?>
</select>
<button name="update_order" value="1">Update</button>
</form></td>
</tr>
<?php endwhile; ?>
</table></div>

<form method="post"><button name="logout" value="1">Logout</button></form>
</main>
<script>
const urlInput = document.getElementById("image_url");
const preview = document.getElementById("preview");

function previewImage() {
    const url = urlInput.value.trim();
    preview.style.display = "none";
    if (!url) return;
    try {
        const parsed = new URL(url);
        if (!["https:", "http:"].includes(parsed.protocol)) return;
        preview.onload = () => preview.style.display = "block";
        preview.onerror = () => preview.style.display = "none";
        preview.src = url;
    } catch (_) {}
}
urlInput.addEventListener("input", previewImage);
previewImage();
</script>
</body>
</html>


<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "noor_jewels";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed. Check XAMPP and config.php.");
}

$conn->set_charset("utf8mb4");

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

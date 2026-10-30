
<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "noor_jewels";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>

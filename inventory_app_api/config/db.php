<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";                     // XAMPP default
$pass = "";                         // XAMPP default (empty)
$db   = "stock_inventory_system";   // 👈 your DB name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}
?>

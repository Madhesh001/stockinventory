<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id = $data["shop_id"] ?? 0;
$barcode = $data["barcode"] ?? "";

if ($shop_id == 0 || $barcode == "") {
    echo json_encode(["success" => false, "message" => "shop_id and barcode required"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT p.id, p.name, p.category_id, c.name AS category_name,
           p.cost_price, p.selling_price,
           p.current_stock, p.expiry_date
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.shop_id = ? AND p.barcode = ?
");


$stmt->bind_param("is", $shop_id, $barcode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["success" => true, "found" => false]);
    exit;
}

$row = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "found" => true,
    "product" => $row
]);

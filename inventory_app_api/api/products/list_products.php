<?php
require_once("../../config/db.php");

// Read shop_id from URL: ?shop_id=1
$shop_id = isset($_GET["shop_id"]) ? intval($_GET["shop_id"]) : 0;

if ($shop_id == 0) {
    echo json_encode(["success" => false, "message" => "shop_id is required"]);
    exit;
}

$sql = "
    SELECT p.id, p.name, p.sku, p.barcode, p.current_stock, p.reorder_level,
           c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.shop_id = ?
    ORDER BY p.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = [
        "id"            => (int)$row["id"],
        "name"          => $row["name"],
        "sku"           => $row["sku"],
        "barcode"       => $row["barcode"],
        "category_name" => $row["category_name"],
        "current_stock" => (int)$row["current_stock"],
        "reorder_level" => (int)$row["reorder_level"]
    ];
}

$stmt->close();

echo json_encode([
    "success" => true,
    "products" => $products
]);

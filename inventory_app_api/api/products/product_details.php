<?php
require_once("../../config/db.php");

// Read product id from URL: ?id=1
$product_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($product_id == 0) {
    echo json_encode(["success" => false, "message" => "Product id is required"]);
    exit;
}

$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    "success" => true,
    "product" => [
        "id"            => (int)$row["id"],
        "shop_id"       => (int)$row["shop_id"],
        "name"          => $row["name"],
        "sku"           => $row["sku"],
        "barcode"       => $row["barcode"],
        "category_id"   => (int)$row["category_id"],
        "category_name" => $row["category_name"],
        "cost_price"    => (float)$row["cost_price"],
        "selling_price" => (float)$row["selling_price"],
        "opening_stock" => (int)$row["opening_stock"],
        "current_stock" => (int)$row["current_stock"],
        "reorder_level" => (int)$row["reorder_level"],
        "expiry_date"   => $row["expiry_date"]
    ]
]);

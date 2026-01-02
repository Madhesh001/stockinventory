<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id       = $data["shop_id"]       ?? 0;
$name          = $data["name"]          ?? "";
$sku           = $data["sku"]           ?? "";
$barcode       = $data["barcode"]       ?? "";
$category_id   = $data["category_id"]   ?? 0;
$cost_price    = $data["cost_price"]    ?? 0;
$selling_price = $data["selling_price"] ?? 0;
$opening_stock = $data["opening_stock"] ?? 0;
$reorder_level = $data["reorder_level"] ?? 0;
$expiry_date   = $data["expiry_date"]   ?? null;
$created_by    = $data["created_by"]    ?? 0;

if (
    $shop_id == 0 || $name == "" || $category_id == 0 ||
    $cost_price == 0 || $selling_price == 0 || $created_by == 0
) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$current_stock = $opening_stock;

$stmt = $conn->prepare("
    INSERT INTO products
    (shop_id, name, sku, barcode, category_id, cost_price, selling_price,
     opening_stock, current_stock, reorder_level, expiry_date, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isssiddiiisi",
    $shop_id,
    $name,
    $sku,
    $barcode,
    $category_id,
    $cost_price,
    $selling_price,
    $opening_stock,
    $current_stock,
    $reorder_level,
    $expiry_date,
    $created_by
);

$stmt->execute();
$product_id = $stmt->insert_id;
$stmt->close();

echo json_encode([
    "success" => true,
    "message" => "Product created",
    "product_id" => $product_id
]);

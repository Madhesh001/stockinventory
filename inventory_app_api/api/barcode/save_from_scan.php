<?php
header("Content-Type: application/json");
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

// Required fields
$shop_id       = $data["shop_id"] ?? 0;
$name          = $data["name"] ?? "";
$barcode       = $data["barcode"] ?? "";
$category_id   = $data["category_id"] ?? 0;
$cost_price    = $data["cost_price"] ?? 0;
$selling_price = $data["selling_price"] ?? 0;

// Optional / default fields
$opening_stock = $data["opening_stock"] ?? 0;
$current_stock = $opening_stock;     // stock starts from opening stock
$expiry_date   = $data["expiry_date"] ?? null;
$created_by    = 1;                  // default admin (SAFE FIX)

// Validation (only important fields)
if (
    $shop_id == 0 ||
    $name == "" ||
    $barcode == "" ||
    $category_id == 0 ||
    $cost_price <= 0 ||
    $selling_price <= 0
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

// Insert product
$stmt = $conn->prepare("
    INSERT INTO products 
    (shop_id, name, barcode, category_id, cost_price, selling_price, opening_stock, current_stock, expiry_date, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issiddiisi",
    $shop_id,
    $name,
    $barcode,
    $category_id,
    $cost_price,
    $selling_price,
    $opening_stock,
    $current_stock,
    $expiry_date,
    $created_by
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Product saved successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save product"
    ]);
}

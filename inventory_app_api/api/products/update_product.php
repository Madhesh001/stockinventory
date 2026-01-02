<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$id            = $data["id"]            ?? 0;
$name          = $data["name"]          ?? "";
$sku           = $data["sku"]           ?? "";
$barcode       = $data["barcode"]       ?? "";
$category_id   = $data["category_id"]   ?? 0;
$cost_price    = $data["cost_price"]    ?? 0;
$selling_price = $data["selling_price"] ?? 0;

if ($id == 0 || $name == "" || $category_id == 0 || $cost_price == 0 || $selling_price == 0) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE products
    SET name = ?, sku = ?, barcode = ?, category_id = ?, cost_price = ?, selling_price = ?, updated_at = NOW()
    WHERE id = ?
");

$stmt->bind_param(
    "sssiddi",
    $name,
    $sku,
    $barcode,
    $category_id,
    $cost_price,
    $selling_price,
    $id
);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Update failed"]);
    exit;
}

$stmt->close();

echo json_encode([
    "success" => true,
    "message" => "Product updated"
]);

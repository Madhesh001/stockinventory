<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id  = $data["shop_id"]  ?? 0;
$admin_id = $data["admin_id"] ?? 0;   // who is requesting (must be ADMIN)

if ($shop_id == 0 || $admin_id == 0) {
    echo json_encode(["success" => false, "message" => "shop_id and admin_id are required"]);
    exit;
}

/**
 * 1) Check that this user is an ADMIN
 */
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $admin_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "User not found for this shop"]);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if ($user["role"] !== "ADMIN") {
    echo json_encode(["success" => false, "message" => "Only admin can view low stock alerts"]);
    exit;
}

/**
 * 2) Low stock rule: current_stock < 10
 */
$threshold = 10;

$stmt = $conn->prepare("
    SELECT p.id, p.name, p.sku, p.barcode,
           p.current_stock,
           c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.shop_id = ? AND p.current_stock < ?
    ORDER BY p.current_stock ASC
");
$stmt->bind_param("ii", $shop_id, $threshold);
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
        "current_stock" => (int)$row["current_stock"]
    ];
}

$stmt->close();

echo json_encode([
    "success" => true,
    "threshold" => $threshold,
    "low_stock_products" => $products
]);

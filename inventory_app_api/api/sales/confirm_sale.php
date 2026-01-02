<?php
header('Content-Type: application/json');
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id = $data["shop_id"] ?? 0;
$user_id = $data["user_id"] ?? 0;
$sku     = $data["sku"] ?? "";
$qty     = $data["quantity"] ?? 0;

if ($shop_id==0 || $user_id==0 || $sku=="" || $qty<=0) {
    echo json_encode(["success"=>false,"message"=>"All fields required"]);
    exit;
}

// Fetch product
$stmt = $conn->prepare("
    SELECT id, name, selling_price, current_stock
    FROM products
    WHERE sku=? AND shop_id=?
");
$stmt->bind_param("si",$sku,$shop_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows==0) {
    echo json_encode(["success"=>false,"message"=>"Product not found"]);
    exit;
}

$p = $res->fetch_assoc();

if ($p["current_stock"] < $qty) {
    echo json_encode(["success"=>false,"message"=>"Insufficient stock"]);
    exit;
}

$total = $qty * $p["selling_price"];

// Update stock
$stmt = $conn->prepare("
    UPDATE products
    SET current_stock = current_stock - ?
    WHERE id = ?
");
$stmt->bind_param("ii",$qty,$p["id"]);
$stmt->execute();

// Insert sale
$stmt = $conn->prepare("
    INSERT INTO sales (shop_id, product_id, sku, quantity, price_per_unit, total_amount, sold_by)
    VALUES (?,?,?,?,?,?,?)
");
$stmt->bind_param(
    "iisiddi",
    $shop_id,
    $p["id"],
    $sku,
    $qty,
    $p["selling_price"],
    $total,
    $user_id
);
$stmt->execute();

echo json_encode([
    "success"=>true,
    "message"=>"Sale completed",
    "product"=>$p["name"],
    "quantity"=>$qty,
    "remaining_stock"=>$p["current_stock"] - $qty,
    "total"=>$total
]);

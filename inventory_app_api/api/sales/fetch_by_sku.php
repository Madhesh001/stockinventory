<?php
header('Content-Type: application/json');
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$sku     = $data["sku"] ?? "";
$shop_id = $data["shop_id"] ?? 0;

if ($sku=="" || $shop_id==0) {
    echo json_encode(["success"=>false,"message"=>"SKU required"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, name, sku, selling_price, current_stock
    FROM products
    WHERE sku = ? AND shop_id = ?
");
$stmt->bind_param("si", $sku, $shop_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows==0) {
    echo json_encode(["success"=>false,"message"=>"Product not found"]);
    exit;
}

$p = $res->fetch_assoc();

echo json_encode([
    "success"=>true,
    "product"=>[
        "id"=>$p["id"],
        "name"=>$p["name"],
        "sku"=>$p["sku"],
        "price"=>$p["selling_price"],
        "stock"=>$p["current_stock"]
    ]
]);

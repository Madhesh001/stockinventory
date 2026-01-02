<?php
header('Content-Type: application/json');
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id  = $data["shop_id"] ?? 0;
$admin_id = $data["admin_id"] ?? 0;

if ($shop_id == 0 || $admin_id == 0) {
    echo json_encode(["success" => false, "message" => "shop_id and admin_id required"]);
    exit;
}

// Check if user is admin
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $admin_id, $shop_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Admin not found"]);
    exit;
}
$admin = $res->fetch_assoc();
$stmt->close();

if ($admin["role"] !== "ADMIN") {
    echo json_encode(["success" => false, "message" => "Only admin can view AI insights"]);
    exit;
}

// Start building recommendations
$recommendations = [];

/*
----------------------------------------------------
  RULE 1 – RESTOCK SUGGESTIONS
----------------------------------------------------
*/
$q1 = $conn->query("
    SELECT id, name, current_stock
    FROM products
    WHERE shop_id = $shop_id AND current_stock < 10
    ORDER BY current_stock ASC
    LIMIT 5
");

while ($row = $q1->fetch_assoc()) {
    $recommendations[] = [
        "type"    => "RESTOCK",
        "title"   => "Restock: " . $row["name"],
        "message" => "Stock is low (" . $row["current_stock"] . " units). Consider ordering more.",
        "product_id" => (int)$row["id"]
    ];
}

/*
----------------------------------------------------
  RULE 2 – SLOW MOVING PRODUCTS
----------------------------------------------------
  Products with high inventory but very low sales.
*/
$q2 = $conn->query("
    SELECT id, name, opening_stock, current_stock
    FROM products
    WHERE shop_id = $shop_id
      AND current_stock >= 50
      AND (opening_stock - current_stock) <= 5
    LIMIT 5
");

while ($row = $q2->fetch_assoc()) {
    $recommendations[] = [
        "type"    => "SLOW_MOVING",
        "title"   => "Slow Moving: " . $row["name"],
        "message" => "Sales are low. Consider discount or promotion.",
        "product_id" => (int)$row["id"]
    ];
}

/*
----------------------------------------------------
  RULE 3 – HIGH DEMAND PRODUCTS
----------------------------------------------------
  Products that are selling quickly.
*/
$q3 = $conn->query("
    SELECT id, name, opening_stock, current_stock
    FROM products
    WHERE shop_id = $shop_id
      AND (opening_stock - current_stock) >= 20
    LIMIT 5
");

while ($row = $q3->fetch_assoc()) {
    $recommendations[] = [
        "type"    => "HIGH_DEMAND",
        "title"   => "High Demand: " . $row["name"],
        "message" => "Selling fast. Stock up for next week.",
        "product_id" => (int)$row["id"]
    ];
}

// Final output
echo json_encode([
    "success" => true,
    "count" => count($recommendations),
    "recommendations" => $recommendations
]);
?>

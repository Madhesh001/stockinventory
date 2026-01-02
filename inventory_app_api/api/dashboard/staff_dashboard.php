<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id  = $data["shop_id"]  ?? 0;
$staff_id = $data["staff_id"] ?? 0;

if ($shop_id == 0 || $staff_id == 0) {
    echo json_encode(["success" => false, "message" => "shop_id and staff_id required"]);
    exit;
}

/* 1) Check STAFF */
$stmt = $conn->prepare("SELECT role, name FROM users WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $staff_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Staff not found"]);
    exit;
}
$user = $result->fetch_assoc();
$stmt->close();

if ($user["role"] !== "STAFF") {
    echo json_encode(["success" => false, "message" => "Not authorized"]);
    exit;
}

/* 2) Staff summary – basic */
$total_products = $conn->query("SELECT COUNT(*) AS c FROM products WHERE shop_id = $shop_id")->fetch_assoc()["c"];

$recent = $conn->query("
    SELECT id, name, current_stock, created_at
    FROM products
    WHERE shop_id = $shop_id
    ORDER BY id DESC
    LIMIT 5
");

$recent_products = [];
while ($row = $recent->fetch_assoc()) {
    $recent_products[] = [
        "id"            => (int)$row["id"],
        "name"          => $row["name"],
        "current_stock" => (int)$row["current_stock"],
        "created_at"    => $row["created_at"]
    ];
}

echo json_encode([
    "success" => true,
    "staff_name" => $user["name"],
    "summary" => [
        "total_products" => (int)$total_products
    ],
    "recent_products" => $recent_products
]);

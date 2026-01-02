<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id  = $data["shop_id"]  ?? 0;
$admin_id = $data["admin_id"] ?? 0;

if ($shop_id == 0 || $admin_id == 0) {
    echo json_encode(["success" => false, "message" => "shop_id and admin_id are required"]);
    exit;
}

/* 1) Check admin */
$stmt = $conn->prepare("SELECT role, name FROM users WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $admin_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}
$user = $result->fetch_assoc();
$stmt->close();

if ($user["role"] !== "ADMIN") {
    echo json_encode(["success" => false, "message" => "Only admin can view dashboard"]);
    exit;
}

/* 2) Summary cards */
$total_products   = $conn->query("SELECT COUNT(*) AS c FROM products    WHERE shop_id = $shop_id")->fetch_assoc()["c"];
$low_stock        = $conn->query("SELECT COUNT(*) AS c FROM products    WHERE shop_id = $shop_id AND current_stock < 10 AND current_stock > 0")->fetch_assoc()["c"];
$out_of_stock     = $conn->query("SELECT COUNT(*) AS c FROM products    WHERE shop_id = $shop_id AND current_stock = 0")->fetch_assoc()["c"];
$total_categories = $conn->query("SELECT COUNT(*) AS c FROM categories")->fetch_assoc()["c"];
$total_users      = $conn->query("SELECT COUNT(*) AS c FROM users       WHERE shop_id = $shop_id AND role IN ('MANAGER','STAFF')")->fetch_assoc()["c"];

/* 3) Recent products (you can treat as recent transactions later) */
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
    "admin_name" => $user["name"],
    "summary" => [
        "total_products"   => (int)$total_products,
        "low_stock"        => (int)$low_stock,
        "out_of_stock"     => (int)$out_of_stock,
        "total_categories" => (int)$total_categories,
        "total_users"      => (int)$total_users
    ],
    "recent_products" => $recent_products
]);

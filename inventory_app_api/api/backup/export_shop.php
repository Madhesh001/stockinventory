<?php
header('Content-Type: application/json');

require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id  = $data["shop_id"]  ?? 0;
$admin_id = $data["admin_id"] ?? 0;

if ($shop_id == 0 || $admin_id == 0) {
    echo json_encode([
        "success" => false,
        "message" => "shop_id and admin_id required"
    ]);
    exit;
}

// Check admin
$stmt = $conn->prepare("
    SELECT role FROM users WHERE id = ? AND shop_id = ?
");
$stmt->bind_param("ii", $admin_id, $shop_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Admin not found"
    ]);
    exit;
}

$a = $res->fetch_assoc();
$stmt->close();

if ($a["role"] !== "ADMIN") {
    echo json_encode([
        "success" => false,
        "message" => "Only admin can export backup"
    ]);
    exit;
}

// Shop info
$shopRes = $conn->query("
    SELECT * FROM shops WHERE id = $shop_id
");
$shop = $shopRes->fetch_assoc();

// Users
$usersRes = $conn->query("
    SELECT id, name, email, phone, monthly_salary, role, notes, created_at
    FROM users WHERE shop_id = $shop_id
");
$users = [];
while ($row = $usersRes->fetch_assoc()) {
    $users[] = $row;
}

// Categories
$catRes = $conn->query("SELECT * FROM categories");
$categories = [];
while ($row = $catRes->fetch_assoc()) {
    $categories[] = $row;
}

// Products
$prodRes = $conn->query("
    SELECT * FROM products WHERE shop_id = $shop_id
");
$products = [];
while ($row = $prodRes->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode([
    "success"      => true,
    "generated_at" => date("Y-m-d H:i:s"),
    "shop"         => $shop,
    "users"        => $users,
    "categories"   => $categories,
    "products"     => $products
]);
?>

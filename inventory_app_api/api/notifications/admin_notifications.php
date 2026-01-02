<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id    = $data["shop_id"]  ?? 0;
$admin_id   = $data["admin_id"] ?? 0;   // must be ADMIN
$days_ahead = 30;                       // days to look ahead for expiry

if ($shop_id == 0 || $admin_id == 0) {
    echo json_encode(["success" => false, "message" => "shop_id and admin_id are required"]);
    exit;
}

/**
 * 1) Check that this user is an ADMIN for this shop
 */
$stmt = $conn->prepare("SELECT role, name FROM users WHERE id = ? AND shop_id = ?");
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
    echo json_encode(["success" => false, "message" => "Only admin can view notifications"]);
    exit;
}

/**
 * 2) Low stock: current_stock < 10 AND > 0
 */
$threshold = 10;

$stmt = $conn->prepare("
    SELECT p.id, p.name, p.sku, p.barcode,
           p.current_stock,
           c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.shop_id = ?
      AND p.current_stock > 0
      AND p.current_stock < ?
    ORDER BY p.current_stock ASC
");
$stmt->bind_param("ii", $shop_id, $threshold);
$stmt->execute();
$result = $stmt->get_result();

$low_stock = [];
while ($row = $result->fetch_assoc()) {
    $low_stock[] = [
        "id"            => (int)$row["id"],
        "name"          => $row["name"],
        "sku"           => $row["sku"],
        "barcode"       => $row["barcode"],
        "category_name" => $row["category_name"],
        "current_stock" => (int)$row["current_stock"]
    ];
}
$stmt->close();

/**
 * 3) Out of stock: current_stock = 0
 */
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.sku, p.barcode,
           p.current_stock,
           c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.shop_id = ?
      AND p.current_stock = 0
    ORDER BY p.name ASC
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();

$out_of_stock = [];
while ($row = $result->fetch_assoc()) {
    $out_of_stock[] = [
        "id"            => (int)$row["id"],
        "name"          => $row["name"],
        "sku"           => $row["sku"],
        "barcode"       => $row["barcode"],
        "category_name" => $row["category_name"],
        "current_stock" => (int)$row["current_stock"]
    ];
}
$stmt->close();

/**
 * 4) Expiring soon: expiry_date between today and today + days_ahead
 *    and stock > 0
 */
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.sku, p.barcode,
           p.current_stock, p.expiry_date,
           c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.shop_id = ?
      AND p.current_stock > 0
      AND p.expiry_date IS NOT NULL
      AND p.expiry_date >= CURDATE()
      AND p.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ORDER BY p.expiry_date ASC
");
$stmt->bind_param("ii", $shop_id, $days_ahead);
$stmt->execute();
$result = $stmt->get_result();

$expiring_soon = [];
while ($row = $result->fetch_assoc()) {
    $expiring_soon[] = [
        "id"            => (int)$row["id"],
        "name"          => $row["name"],
        "sku"           => $row["sku"],
        "barcode"       => $row["barcode"],
        "category_name" => $row["category_name"],
        "current_stock" => (int)$row["current_stock"],
        "expiry_date"   => $row["expiry_date"]
    ];
}
$stmt->close();

/**
 * 5) Already expired: expiry_date < today and stock > 0
 */
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.sku, p.barcode,
           p.current_stock, p.expiry_date,
           c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.shop_id = ?
      AND p.current_stock > 0
      AND p.expiry_date IS NOT NULL
      AND p.expiry_date < CURDATE()
    ORDER BY p.expiry_date ASC
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();

$expired = [];
while ($row = $result->fetch_assoc()) {
    $expired[] = [
        "id"            => (int)$row["id"],
        "name"          => $row["name"],
        "sku"           => $row["sku"],
        "barcode"       => $row["barcode"],
        "category_name" => $row["category_name"],
        "current_stock" => (int)$row["current_stock"],
        "expiry_date"   => $row["expiry_date"]
    ];
}
$stmt->close();

/**
 * 6) Total notifications count
 */
$total_notifications =
    count($low_stock) +
    count($out_of_stock) +
    count($expiring_soon) +
    count($expired);

echo json_encode([
    "success"             => true,
    "admin_name"          => $user["name"],
    "threshold"           => $threshold,
    "days_ahead"          => $days_ahead,
    "total_notifications" => $total_notifications,
    "low_stock"           => $low_stock,
    "out_of_stock"        => $out_of_stock,
    "expiring_soon"       => $expiring_soon,
    "expired"             => $expired
]);

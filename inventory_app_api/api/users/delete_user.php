<?php
header('Content-Type: application/json');
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id  = $data["shop_id"]  ?? 0;
$admin_id = $data["admin_id"] ?? 0;
$user_id  = $data["user_id"]  ?? 0;

if ($shop_id == 0 || $admin_id == 0 || $user_id == 0) {
    echo json_encode([
        "success" => false,
        "message" => "shop_id, admin_id, user_id required"
    ]);
    exit;
}

// 1) check admin
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND shop_id = ?");
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
        "message" => "Only admin can delete users"
    ]);
    exit;
}

// 2) cannot delete admin user
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $user_id, $shop_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}
$u = $res->fetch_assoc();
$stmt->close();

if ($u["role"] === "ADMIN") {
    echo json_encode([
        "success" => false,
        "message" => "Cannot delete admin"
    ]);
    exit;
}

// 3) delete
$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $user_id, $shop_id);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Delete failed"
    ]);
    exit;
}
$stmt->close();

echo json_encode([
    "success" => true,
    "message" => "User deleted"
]);
?>

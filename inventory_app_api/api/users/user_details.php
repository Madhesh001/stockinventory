<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id  = $data["shop_id"]  ?? 0;
$admin_id = $data["admin_id"] ?? 0;
$user_id  = $data["user_id"]  ?? 0;

if ($shop_id == 0 || $admin_id == 0 || $user_id == 0) {
    echo json_encode(["success" => false, "message" => "shop_id, admin_id, user_id required"]);
    exit;
}

// admin check
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $admin_id, $shop_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Admin not found"]);
    exit;
}
$a = $res->fetch_assoc();
$stmt->close();

if ($a["role"] !== "ADMIN") {
    echo json_encode(["success" => false, "message" => "Only admin can view user details"]);
    exit;
}

// get user details
$stmt = $conn->prepare("
    SELECT id, name, email, phone, monthly_salary, role, notes, created_at
    FROM users
    WHERE id = ? AND shop_id = ? AND role IN ('MANAGER','STAFF')
");
$stmt->bind_param("ii", $user_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$u = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    "success" => true,
    "user" => [
        "id"             => (int)$u["id"],
        "name"           => $u["name"],
        "email"          => $u["email"],
        "phone"          => $u["phone"],
        "monthly_salary" => $u["monthly_salary"] === null ? null : (float)$u["monthly_salary"],
        "role"           => $u["role"],
        "notes"          => $u["notes"],
        "created_at"     => $u["created_at"]
    ]
]);

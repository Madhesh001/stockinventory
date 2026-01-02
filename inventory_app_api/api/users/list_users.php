<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id  = $data["shop_id"]  ?? 0;
$admin_id = $data["admin_id"] ?? 0;

if ($shop_id == 0 || $admin_id == 0) {
    echo json_encode(["success" => false, "message" => "shop_id and admin_id are required"]);
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
    echo json_encode(["success" => false, "message" => "Only admin can view users"]);
    exit;
}

// list managers & staff
$stmt = $conn->prepare("
    SELECT id, name, email, role, monthly_salary
    FROM users
    WHERE shop_id = ? AND role IN ('MANAGER','STAFF')
    ORDER BY role, name
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        "id"             => (int)$row["id"],
        "name"           => $row["name"],
        "email"          => $row["email"],
        "role"           => $row["role"],
        "monthly_salary" => $row["monthly_salary"] === null ? null : (float)$row["monthly_salary"]
    ];
}
$stmt->close();

echo json_encode([
    "success" => true,
    "users"   => $users
]);

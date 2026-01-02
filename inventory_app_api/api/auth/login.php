<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$email    = $data["email"] ?? "";
$password = $data["password"] ?? "";
$role     = $data["role"] ?? "";   // ADMIN / MANAGER / STAFF

if ($email == "" || $password == "" || $role == "") {
    echo json_encode(["success" => false, "message" => "Email, password, role required"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, shop_id, name, email, password, role FROM users WHERE email = ? AND role = ?");
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Invalid credentials"]);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user["password"])) {
    echo json_encode(["success" => false, "message" => "Invalid credentials"]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "user" => [
        "id"      => (int)$user["id"],
        "shop_id" => (int)$user["shop_id"],
        "name"    => $user["name"],
        "email"   => $user["email"],
        "role"    => $user["role"]
    ]
]);

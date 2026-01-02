<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_name     = $data["shop_name"]     ?? "";
$shop_category = $data["shop_category"] ?? "";
$owner_name    = $data["owner_name"]    ?? "";
$email         = $data["email"]         ?? "";
$password      = $data["password"]      ?? "";
$confirm       = $data["confirm_password"] ?? "";

if ($shop_name == "" || $shop_category == "" || $owner_name == "" || $email == "" || $password == "" || $confirm == "") {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

if ($password !== $confirm) {
    echo json_encode(["success" => false, "message" => "Passwords do not match"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already registered"]);
    exit;
}
$stmt->close();

$stmt = $conn->prepare("INSERT INTO shops (name, category) VALUES (?, ?)");
$stmt->bind_param("ss", $shop_name, $shop_category);
$stmt->execute();
$shop_id = $stmt->insert_id;
$stmt->close();

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (shop_id, name, email, password, role, created_by) VALUES (?, ?, ?, ?, 'ADMIN', NULL)");
$stmt->bind_param("isss", $shop_id, $owner_name, $email, $hash);
$stmt->execute();
$user_id = $stmt->insert_id;
$stmt->close();

echo json_encode([
    "success" => true,
    "message" => "Owner account created",
    "shop_id" => $shop_id,
    "user_id" => $user_id,
    "role"    => "ADMIN"
]);

<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$shop_id    = $data["shop_id"]    ?? 0;
$name       = $data["name"]       ?? "";
$email      = $data["email"]      ?? "";
$password   = $data["password"]   ?? "";
$role       = $data["role"]       ?? "";      // MANAGER or STAFF
$created_by = $data["created_by"] ?? 0;       // admin user id

$phone          = $data["phone"]          ?? null;
$monthly_salary = $data["monthly_salary"] ?? null; // example 35000
$notes          = $data["notes"]          ?? null;

if ($shop_id == 0 || $name == "" || $email == "" || $password == "" || $role == "" || $created_by == 0) {
    echo json_encode(["success" => false, "message" => "All required fields must be filled"]);
    exit;
}

// 1) ensure creator is ADMIN
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND shop_id = ?");
$stmt->bind_param("ii", $created_by, $shop_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Admin not found"]);
    exit;
}
$admin = $res->fetch_assoc();
$stmt->close();

if ($admin["role"] !== "ADMIN") {
    echo json_encode(["success" => false, "message" => "Only admin can create users"]);
    exit;
}

if ($role !== "MANAGER" && $role !== "STAFF") {
    echo json_encode(["success" => false, "message" => "Role must be MANAGER or STAFF"]);
    exit;
}

// 2) check email unique
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already registered"]);
    exit;
}
$stmt->close();

// 3) insert
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("
    INSERT INTO users (shop_id, name, email, phone, monthly_salary, notes, password, role, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "isssdsssi",
    $shop_id,
    $name,
    $email,
    $phone,
    $monthly_salary,
    $notes,
    $hash,
    $role,
    $created_by
);
$stmt->execute();
$user_id = $stmt->insert_id;
$stmt->close();

echo json_encode([
    "success" => true,
    "message" => "User created",
    "user_id" => $user_id
]);

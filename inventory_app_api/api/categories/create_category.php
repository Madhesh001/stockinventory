<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$name       = $data["name"]       ?? "";
$created_by = $data["created_by"] ?? 0;   // admin user id

if ($name == "" || $created_by == 0) {
    echo json_encode(["success" => false, "message" => "Category name and created_by are required"]);
    exit;
}

// 1) Check that created_by is an ADMIN
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $created_by);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

if ($row["role"] !== "ADMIN") {
    echo json_encode(["success" => false, "message" => "Only admin can create categories"]);
    exit;
}

// 2) Check if category already exists (same name)
$stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Category already exists"]);
    exit;
}
$stmt->close();

// 3) Insert new category
$stmt = $conn->prepare("INSERT INTO categories (name, created_by) VALUES (?, ?)");
$stmt->bind_param("si", $name, $created_by);
$stmt->execute();
$category_id = $stmt->insert_id;
$stmt->close();

echo json_encode([
    "success" => true,
    "message" => "Category created",
    "category" => [
        "id"   => $category_id,
        "name" => $name
    ]
]);

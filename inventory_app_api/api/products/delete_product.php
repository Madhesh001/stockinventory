<?php
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"] ?? 0;

if ($id == 0) {
    echo json_encode(["success" => false, "message" => "Product id is required"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Delete failed"]);
    exit;
}

$stmt->close();

echo json_encode([
    "success" => true,
    "message" => "Product deleted"
]);

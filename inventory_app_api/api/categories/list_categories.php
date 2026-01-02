<?php
require_once("../../config/db.php");

$sql = "SELECT id, name, created_at FROM categories ORDER BY name ASC";
$result = $conn->query($sql);

$categories = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            "id"         => (int)$row["id"],
            "name"       => $row["name"],
            "created_at" => $row["created_at"]
        ];
    }
}

echo json_encode([
    "success" => true,
    "categories" => $categories
]);

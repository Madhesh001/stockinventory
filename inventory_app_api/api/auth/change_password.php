<?php
header('Content-Type: application/json');
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$user_id           = $data["user_id"]           ?? 0;
$role              = $data["role"]              ?? ""; // ADMIN / MANAGER / STAFF
$current_password  = $data["current_password"]  ?? "";
$new_password      = $data["new_password"]      ?? "";
$confirm_password  = $data["confirm_password"]  ?? "";

// 1) Basic validation
if ($user_id == 0 || $role === "" || $current_password === "" || $new_password === "" || $confirm_password === "") {
    echo json_encode([
        "success" => false,
        "message" => "All fields are required"
    ]);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode([
        "success" => false,
        "message" => "New passwords do not match"
    ]);
    exit;
}

// 2) Get user by id + role
$stmt = $conn->prepare("
    SELECT password 
    FROM users 
    WHERE id = ? AND role = ?
");
$stmt->bind_param("is", $user_id, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// 3) Verify current password
if (!password_verify($current_password, $user["password"])) {
    echo json_encode([
        "success" => false,
        "message" => "Current password is incorrect"
    ]);
    exit;
}

// 4) Prevent same password reuse
if (password_verify($new_password, $user["password"])) {
    echo json_encode([
        "success" => false,
        "message" => "New password must be different from current password"
    ]);
    exit;
}

// 5) Update password
$hash = password_hash($new_password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("
    UPDATE users
    SET password = ?
    WHERE id = ? AND role = ?
");
$stmt->bind_param("sis", $hash, $user_id, $role);
$stmt->execute();
$stmt->close();

echo json_encode([
    "success" => true,
    "message" => "Password changed successfully"
]);
?>

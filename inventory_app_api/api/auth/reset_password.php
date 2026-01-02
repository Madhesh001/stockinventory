<?php
header('Content-Type: application/json');
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$email            = $data["email"] ?? "";
$role             = $data["role"] ?? "";
$otp_code         = $data["otp_code"] ?? "";
$new_password     = $data["new_password"] ?? "";
$confirm_password = $data["confirm_password"] ?? "";

if ($email === "" || $role === "" || $otp_code === "" || $new_password === "" || $confirm_password === "") {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(["success" => false, "message" => "Passwords do not match"]);
    exit;
}

// Find user
$stmt = $conn->prepare(
    "SELECT id, reset_code, reset_expires 
     FROM users 
     WHERE email = ? AND role = ?"
);
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Check OTP
if ($user["reset_code"] !== $otp_code) {
    echo json_encode(["success" => false, "message" => "Invalid OTP code"]);
    exit;
}

// Check expiry
if (strtotime($user["reset_expires"]) < time()) {
    echo json_encode(["success" => false, "message" => "OTP expired"]);
    exit;
}

// Update password
$hashed = password_hash($new_password, PASSWORD_BCRYPT);

$stmt = $conn->prepare(
    "UPDATE users 
     SET password = ?, reset_code = NULL, reset_expires = NULL 
     WHERE id = ?"
);
$stmt->bind_param("si", $hashed, $user["id"]);
$stmt->execute();
$stmt->close();

echo json_encode([
    "success" => true,
    "message" => "Password reset successful"
]);
?>

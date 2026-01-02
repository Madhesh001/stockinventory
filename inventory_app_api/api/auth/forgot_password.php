<?php
header('Content-Type: application/json');
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$email = $data["email"] ?? "";
$role  = $data["role"]  ?? "";

if ($email === "" || $role === "") {
    echo json_encode(["success"=>false,"message"=>"Email and role required"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = ?");
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["success"=>false,"message"=>"User not found"]);
    exit;
}

$user = $res->fetch_assoc();
$user_id = $user["id"];
$stmt->close();

$otp = (string)rand(100000,999999);
$expires = date("Y-m-d H:i:s", time() + 900); // 15 min

$stmt = $conn->prepare(
    "UPDATE users SET reset_code=?, reset_expires=? WHERE id=?"
);
$stmt->bind_param("ssi", $otp, $expires, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode([
    "success"=>true,
    "message"=>"OTP generated",
    "otp_for_testing"=>$otp
]);

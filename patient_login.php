<?php
header("Content-Type: application/json");
require_once "db.php";
session_start();

$input = json_decode(file_get_contents("php://input"), true);

$national_id = trim($input["national_id"] ?? "");
$phone = trim($input["phone"] ?? "");

if (!preg_match('/^\d{14}$/', $national_id)) {
  http_response_code(400);
  echo json_encode(["ok"=>false, "message"=>"National ID must be 14 digits"]);
  exit;
}

if (!preg_match('/^(010|011|012|015)\d{8}$/', $phone)) {
  http_response_code(400);
  echo json_encode(["ok"=>false, "message"=>"Invalid phone number"]);
  exit;
}

$stmt = $conn->prepare("
  SELECT patient_id, full_name
  FROM patients
  WHERE national_id=? AND phone=? AND is_active=1
  LIMIT 1
");
$stmt->bind_param("ss", $national_id, $phone);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
  http_response_code(401);
  echo json_encode(["ok"=>false, "message"=>"Patient not found. Ask the hospital to add you."]);
  exit;
}

$_SESSION["auth_type"] = "patient";
$_SESSION["patient_id"] = (int)$row["patient_id"];
$_SESSION["patient_name"] = $row["full_name"];

echo json_encode(["ok"=>true, "redirect"=>"PatientDashboard.php"]);

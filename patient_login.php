<?php
header("Content-Type: application/json; charset=utf-8");
require_once "db.php";
session_start();

$input = json_decode(file_get_contents("php://input"), true);

$national_id = trim($input["national_id"] ?? "");
$phone       = trim($input["phone"] ?? "");

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
  SELECT p.patient_id, p.full_name, p.insurance_id, mi.name AS insurance_name
  FROM patients p
  LEFT JOIN medical_insurances mi ON mi.insurance_id = p.insurance_id
  WHERE p.national_id=? AND p.phone=? AND p.is_active=1
  LIMIT 1
");
$stmt->bind_param("ss", $national_id, $phone);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  http_response_code(401);
  echo json_encode(["ok"=>false, "message"=>"Patient not found. Ask the hospital/insurance to add you."]);
  exit;
}

/* ✅ prevent session fixation */
session_regenerate_id(true);

$_SESSION["auth_type"]     = "patient";
$_SESSION["patient_id"]    = (int)$row["patient_id"];
$_SESSION["patient_name"]  = $row["full_name"];
$_SESSION["insurance_id"]  = (int)($row["insurance_id"] ?? 0);
$_SESSION["insurance_name"]= $row["insurance_name"] ?? "";

echo json_encode(["ok"=>true, "redirect"=>"PatientDashboard.php"]);
exit;

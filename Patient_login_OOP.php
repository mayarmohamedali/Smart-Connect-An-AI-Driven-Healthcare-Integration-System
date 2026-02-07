<?php
/**
 * Patient Login API - OOP Version
 * Fully functional with database authentication
 */

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Validator.php';

// Initialize
$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// Get input
$input = json_decode(file_get_contents("php://input"), true);

$national_id = trim($input["national_id"] ?? "");
$phone = trim($input["phone"] ?? "");

// Validate using OOP
if (!Validator::validateNationalId($national_id)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "message" => "National ID must be 14 digits"]);
  exit;
}

if (!Validator::validatePhone($phone)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "message" => "Invalid phone number"]);
  exit;
}

// Login using OOP
$result = $auth->loginPatient($national_id, $phone);

if ($result['ok']) {
  http_response_code(200);
  echo json_encode($result);
} else {
  http_response_code(401);
  echo json_encode($result);
}

$db->close();
exit;
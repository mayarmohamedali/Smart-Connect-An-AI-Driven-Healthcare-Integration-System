<?php
header("Content-Type: application/json");
require_once "db.php";
session_start();

$input = json_decode(file_get_contents("php://input"), true);

$email    = trim($input["email"] ?? "");
$password = (string)($input["password"] ?? "");
$portal   = trim($input["portal"] ?? ""); // hospital / insurance / admin

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
  http_response_code(400);
  echo json_encode(["ok" => false, "message" => "Invalid email/password"]);
  exit;
}

/* ✅ Fetch full_name too */
$stmt = $conn->prepare("
  SELECT u.user_id, u.full_name, u.password_hash, u.role_id, r.role_name
  FROM users u
  JOIN roles r ON r.role_id = u.role_id
  WHERE u.email=? AND u.is_active=1
  LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user["password_hash"])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "message" => "Wrong email or password"]);
  exit;
}

$role = $user["role_name"];

/* ✅ Portal restriction (hospital/insurance/admin) */
$allowed = [
  "hospital"   => "HOSPITAL_STAFF",
  "insurance"  => "INSURANCE_STAFF",
  "admin"      => "ADMIN"
];

if ($portal !== "" && isset($allowed[$portal]) && $allowed[$portal] !== $role) {
  http_response_code(403);
  echo json_encode(["ok" => false, "message" => "You don’t have access to this portal"]);
  exit;
}

/* ✅ Save session (THIS is what your navbar needs) */
$_SESSION["auth_type"]  = "staff";
$_SESSION["user_id"]    = (int)$user["user_id"];
$_SESSION["role"]       = $role;
$_SESSION["staff_name"] = $user["full_name"];   // ✅ now navbar will show it

/* ✅ OPTIONAL: auto-set hospital_id based on staff account (1..4) */
if ($role === "HOSPITAL_STAFF") {
  // Because your seed users are user_id 1..4 for hospital staff
  // Map them to hospitals 1..4
  $_SESSION["hospital_id"] = (int)$user["user_id"];
}

/* ✅ Redirect */
$redirect = "landing_page.html";
if ($role === "HOSPITAL_STAFF")  $redirect = "HospitalDashboard.php";
if ($role === "INSURANCE_STAFF") $redirect = "InsuranceDashboard.php";
if ($role === "ADMIN")           $redirect = "AdminDashboard.php";

echo json_encode(["ok" => true, "redirect" => $redirect]);

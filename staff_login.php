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

/* ✅ Fetch user data */
$stmt = $conn->prepare("
  SELECT
    u.user_id,
    u.full_name,
    u.password_hash,
    u.role_id,
    r.role_name,
    u.insurance_id
  FROM users u
  JOIN roles r ON r.role_id = u.role_id
  WHERE u.email=? AND u.is_active=1
  LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user["password_hash"])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "message" => "Wrong email or password"]);
  exit;
}

$role = $user["role_name"];

/* ✅ Portal restriction */
$allowed = [
  "hospital"  => "HOSPITAL_STAFF",
  "insurance" => "INSURANCE_STAFF",
  "admin"     => "ADMIN"
];

if ($portal !== "" && isset($allowed[$portal]) && $allowed[$portal] !== $role) {
  http_response_code(403);
  echo json_encode(["ok" => false, "message" => "You don't have access to this portal"]);
  exit;
}

/* ✅ Save session data */
$_SESSION["auth_type"]  = "staff";
$_SESSION["user_id"]    = (int)$user["user_id"];
$_SESSION["role"]       = $role;
$_SESSION["staff_name"] = $user["full_name"];

/* ✅ Initialize policy_completed variable */
$policy_completed = null;

/* ✅ Set hospital_id for hospital staff */
if ($role === "HOSPITAL_STAFF") {
  // For hospital staff, use user_id as hospital_id (based on your data structure)
  $_SESSION["hospital_id"] = (int)$user["user_id"];
}

/* ✅ Set insurance_id and check policy for insurance staff */
if ($role === "INSURANCE_STAFF") {
  $insurance_id = (int)($user["insurance_id"] ?? 0);
  
  if ($insurance_id <= 0) {
    http_response_code(500);
    echo json_encode(["ok" => false, "message" => "Your account is missing insurance_id in users table."]);
    exit;
  }
  
  $_SESSION["insurance_id"] = $insurance_id;
  
  // Check if policy is completed
  $stmt = $conn->prepare("
    SELECT policy_completed
    FROM medical_insurances
    WHERE insurance_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $insurance_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();
  
  $policy_completed = (int)($row["policy_completed"] ?? 0);
  $_SESSION["insurance_policy_completed"] = $policy_completed;
}

/* ✅ Redirect logic */
$redirect = "landing_page.html";

if ($role === "HOSPITAL_STAFF") {
  $redirect = "HospitalDashboard.php";
} 
elseif ($role === "INSURANCE_STAFF") {
  // If policy not completed, redirect to policy setup
  if ($policy_completed === 0) {
    $redirect = "policy.php";
  } else {
    $redirect = "InsuranceDashboard.php";
  }
} 
elseif ($role === "ADMIN") {
  $redirect = "AdminDashboard.php";
}

/* ✅ Final response */
echo json_encode([
  "ok" => true,
  "redirect" => $redirect,
  "policy_completed" => $policy_completed,
  "insurance_id" => $_SESSION["insurance_id"] ?? null
]);
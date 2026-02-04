<?php
header("Content-Type: application/json; charset=utf-8");
require_once "db.php";
session_start();

/* ✅ Prevent PHP warnings/notices from breaking JSON */
ini_set('display_errors', 0);
error_reporting(E_ALL);

/* ✅ Read JSON input */
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "message" => "Invalid JSON body"]);
  exit;
}

$email    = trim($input["email"] ?? "");
$password = (string)($input["password"] ?? "");
$portal   = trim($input["portal"] ?? ""); // hospital / insurance / admin

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
  http_response_code(400);
  echo json_encode(["ok" => false, "message" => "Invalid email/password"]);
  exit;
}

/* ✅ Fetch user */
$stmt = $conn->prepare("
  SELECT
    u.user_id,
    u.full_name,
    u.password_hash,
    u.hospital_id,
    u.insurance_id,
    r.role_name
  FROM users u
  JOIN roles r ON r.role_id = u.role_id
  WHERE u.email=? AND u.is_active=1
  LIMIT 1
");
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["ok" => false, "message" => "DB prepare failed: " . $conn->error]);
  exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user || !password_verify($password, $user["password_hash"])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "message" => "Wrong email or password"]);
  exit;
}

$role = (string)$user["role_name"];

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

/* ✅ Save base session */
$_SESSION["auth_type"]  = "staff";
$_SESSION["user_id"]    = (int)$user["user_id"];
$_SESSION["role"]       = $role;
$_SESSION["staff_name"] = (string)$user["full_name"];

/* ✅ Defaults (IMPORTANT: to avoid undefined variable issues) */
$policy_completed = 1; // assume completed unless proven otherwise

/* ✅ Role-specific session + policy check */
if ($role === "HOSPITAL_STAFF") {
  $hid = (int)($user["hospital_id"] ?? 0);
  if ($hid <= 0) {
    http_response_code(500);
    echo json_encode(["ok" => false, "message" => "Your account is missing hospital_id in users table."]);
    exit;
  }
  $_SESSION["hospital_id"] = $hid;
}

if ($role === "INSURANCE_STAFF") {
  $iid = (int)($user["insurance_id"] ?? 0);
  if ($iid <= 0) {
    http_response_code(500);
    echo json_encode(["ok" => false, "message" => "Your account is missing insurance_id in users table."]);
    exit;
  }
  $_SESSION["insurance_id"] = $iid;

  /* ✅ Check policy completion (adjust table/column names if yours differ) */
  $policy_completed = 0;
  $q = $conn->prepare("SELECT COUNT(*) AS c FROM plan_service_coverage WHERE insurance_id=?");
  if ($q) {
    $q->bind_param("i", $iid);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    $policy_completed = ((int)($row["c"] ?? 0) > 0) ? 1 : 0;
  } else {
    // If table/column doesn't exist, don't crash JSON—return a clear message
    http_response_code(500);
    echo json_encode([
      "ok" => false,
      "message" => "Policy check query failed. Make sure plan_service_coverage(insurance_id) exists. Error: " . $conn->error
    ]);
    exit;
  }
}

/* ✅ Redirect */
$redirect = "landing_page.html";

if ($role === "HOSPITAL_STAFF") {
  $redirect = "HospitalDashboard.php";
} elseif ($role === "INSURANCE_STAFF") {
  $redirect = ($policy_completed === 0) ? "policy.php" : "InsuranceDashboard.php";
} elseif ($role === "ADMIN") {
  $redirect = "AdminDashboard.php";
}

/* ✅ Final JSON response */
echo json_encode([
  "ok" => true,
  "redirect" => $redirect,
  "policy_completed" => $policy_completed,
  "insurance_id" => $_SESSION["insurance_id"] ?? null,
  "role" => $role,
  "staff_name" => $_SESSION["staff_name"]
]);

<?php
session_start();
if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "staff" || ($_SESSION["role"] ?? "") !== "INSURANCE_STAFF") {
  header("Location: login.html");
  exit;
}

require_once "db.php";

$national_id = trim($_GET["national_id"] ?? "");
if ($national_id === "") {
  die("Missing national_id");
}

$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE national_id=? LIMIT 1");
$stmt->bind_param("s", $national_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  die("Patient not found for National ID: " . htmlspecialchars($national_id));
}

$patient_id = (int)$row["patient_id"];
header("Location: AddPatientPolicy.php?patient_id=" . $patient_id);
exit;

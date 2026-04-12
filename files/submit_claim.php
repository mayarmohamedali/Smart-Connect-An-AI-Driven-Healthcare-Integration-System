<?php
/**
 * submit_claim.php
 * Receives the claim form from PatientDashboard.php and inserts into the claims table.
 *
 * Claims table columns used:
 *   record_id, patient_id, insurance_id, service_id,
 *   treatment_cost, claim_amount, claim_status (default Pending)
 *
 * coverage_percentage, cost_coverage_ratio, avg_claim are left NULL here —
 * they are calculated by the insurance staff when they review the claim.
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// ── Must be a logged-in patient ───────────────────────────────────────────────
$auth->checkPatientAuth();
$patient_id = (int)$auth->getSessionData("patient_id");
if ($patient_id <= 0) {
    header("Location: login.html");
    exit;
}

// ── Only accept POST ──────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: PatientDashboard.php");
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function ni($v) {
    $v = trim((string)($v ?? ""));
    return $v === "" ? null : $v;
}
function redirect($status, $msg = "") {
    $url = "PatientDashboard.php?claim=" . urlencode($status);
    if ($msg) $url .= "&msg=" . urlencode($msg);
    header("Location: $url");
    exit;
}

// ── Read & validate form input ────────────────────────────────────────────────
$service_name  = ni($_POST["service_type"]  ?? null);   // e.g. "Checkup"
$claim_amount  = ni($_POST["claim_amount"]  ?? null);   // e.g. "500.00"
// description/notes — no column in claims table, we ignore or can log it

if (!$service_name || !$claim_amount) {
    redirect("error", "Service type and claim amount are required.");
}

if ((float)$claim_amount <= 0) {
    redirect("error", "Claim amount must be greater than zero.");
}

// ── Fetch patient's insurance_id ──────────────────────────────────────────────
$stmt = $conn->prepare("SELECT insurance_id FROM patients WHERE patient_id = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !$row["insurance_id"]) {
    redirect("error", "No active insurance found for your account.");
}
$insurance_id = (int)$row["insurance_id"];

// ── Resolve service name → service.id ────────────────────────────────────────
// The form sends display names like "Checkup", "Operation", "Dentistry", etc.
// Map them to the service table names (case-insensitive LIKE for safety)
$stmt = $conn->prepare("SELECT id FROM service WHERE name = ? LIMIT 1");
$stmt->bind_param("s", $service_name);
$stmt->execute();
$svcRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fallback: try case-insensitive match for slight naming differences
if (!$svcRow) {
    $stmt = $conn->prepare("SELECT id FROM service WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $stmt->bind_param("s", $service_name);
    $stmt->execute();
    $svcRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$service_id = $svcRow ? (int)$svcRow["id"] : null;

// ── Find the patient's most recent medical record to attach the claim to ──────
// A claim must reference a record_id (FK). We use the latest record.
$stmt = $conn->prepare("
    SELECT record_id
    FROM medical_records
    WHERE patient_id = ?
    ORDER BY record_id DESC
    LIMIT 1
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$recRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$recRow) {
    redirect("error", "No medical record found. A claim requires at least one medical record on file.");
}
$record_id = (int)$recRow["record_id"];

// ── Insert the claim ──────────────────────────────────────────────────────────
// treatment_cost = claim_amount at submission time (patient's stated cost).
// coverage_percentage, cost_coverage_ratio, avg_claim stay NULL until insurance reviews.
// claim_status defaults to 'Pending' (DB default).
$treatment_cost = (float)$claim_amount;
$claim_amount_f = (float)$claim_amount;

$stmt = $conn->prepare("
    INSERT INTO claims (
        record_id,
        patient_id,
        insurance_id,
        service_id,
        treatment_cost,
        claim_amount,
        claim_status
    ) VALUES (?, ?, ?, ?, ?, ?, 'Pending')
");

$stmt->bind_param(
    "iiiids",          // wait — all numeric except status string
    $record_id,
    $patient_id,
    $insurance_id,
    $service_id,       // can be null — mysqli bind_param handles null for int type
    $treatment_cost,
    $claim_amount_f
);

// Note: mysqli bind_param doesn't support binding null to "i" cleanly.
// Use the string type workaround for service_id if it may be null.
// Re-bind using all strings (MySQL coerces safely):
$stmt->close();

$stmt = $conn->prepare("
    INSERT INTO claims (
        record_id,
        patient_id,
        insurance_id,
        service_id,
        treatment_cost,
        claim_amount,
        claim_status
    ) VALUES (?, ?, ?, ?, ?, ?, 'Pending')
");

// Use "s" for all — MySQL coerces, and null stays null with "s" binding
$svc_bind = $service_id !== null ? (string)$service_id : null;

$stmt->bind_param(
    "ssssss",
    $record_id,
    $patient_id,
    $insurance_id,
    $svc_bind,
    $treatment_cost,
    $claim_amount_f
);

if ($stmt->execute()) {
    $stmt->close();
    redirect("success");
} else {
    $err = $stmt->error;
    $stmt->close();
    redirect("error", "Database error: " . $err);
}

if ($conn instanceof mysqli) { $conn->close(); }
?>
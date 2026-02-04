<?php
session_start();
if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "staff" || ($_SESSION["role"] ?? "") !== "INSURANCE_STAFF") {
  header("Location: login.html");
  exit;
}

require_once "db.php";

$insurance_id = (int)($_SESSION["insurance_id"] ?? 0);
$patient_id   = (int)($_GET["patient_id"] ?? 0);

if ($insurance_id <= 0) die("Missing insurance_id in session.");
if ($patient_id <= 0) die("Invalid patient_id");

// helper
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

// ✅ Fetch patient
$stmt = $conn->prepare("SELECT patient_id, full_name, national_id, phone FROM patients WHERE patient_id=? LIMIT 1");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) die("Patient not found.");

// ✅ Load insurance name
$stmt = $conn->prepare("SELECT name FROM medical_insurances WHERE insurance_id=? LIMIT 1");
$stmt->bind_param("i", $insurance_id);
$stmt->execute();
$ins = $stmt->get_result()->fetch_assoc();
$stmt->close();
$insurance_name = $ins["name"] ?? "Insurance";

// ✅ Load plans for this insurance (dropdown)
$plans = [];
$stmt = $conn->prepare("
  SELECT ip.id, ip.plan_name,
         c.name AS category_name,
         ct.name AS customer_type_name
  FROM insurance_plan ip
  JOIN category c ON c.id = ip.category_id
  JOIN customer_type ct ON ct.id = ip.customer_type_id
  WHERE ip.insurance_id = ?
  ORDER BY ip.id DESC
");
$stmt->bind_param("i", $insurance_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $plans[] = $row;
$stmt->close();

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $insurance_plan_id = (int)($_POST["insurance_plan_id"] ?? 0);
  $policy_number     = trim($_POST["policy_number"] ?? "");
  $start_date        = trim($_POST["start_date"] ?? "");
  $end_date          = trim($_POST["end_date"] ?? "");
  $status            = trim($_POST["status"] ?? "active");

  if ($insurance_plan_id <= 0) {
    $error = "Please select a plan.";
  } elseif ($policy_number === "") {
    $error = "Policy number is required.";
  } elseif ($start_date === "") {
    $error = "Start date is required.";
  } else {

    // ✅ Safety: ensure the plan belongs to this insurance
    $chk = $conn->prepare("SELECT id FROM insurance_plan WHERE id=? AND insurance_id=? LIMIT 1");
    $chk->bind_param("ii", $insurance_plan_id, $insurance_id);
    $chk->execute();
    $ok = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$ok) {
      $error = "Invalid plan selected.";
    } else {

      // Convert empty end_date to NULL
      $end_date_db = ($end_date === "") ? null : $end_date;

      $stmt = $conn->prepare("
        INSERT INTO patient_policy
        (patient_id, insurance_id, insurance_plan_id, policy_number, start_date, end_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param(
        "iiissss",
        $patient_id,
        $insurance_id,
        $insurance_plan_id,
        $policy_number,
        $start_date,
        $end_date_db,
        $status
      );

      if ($stmt->execute()) {
        $success = "Patient policy added successfully ✅";
      } else {
        $error = "Insert failed: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Patient Policy</title>

  <link href="css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
  <a href="InsuranceDashboard.php" class="btn btn-outline-secondary mb-3">
    <i class="fas fa-arrow-left mr-1"></i> Back
  </a>

  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">
        <i class="fas fa-file-signature mr-2"></i>
        Add Patient Policy — <?= e($patient["full_name"]) ?>
      </h5>
      <small>National ID: <?= e($patient["national_id"]) ?> | Insurance: <?= e($insurance_name) ?></small>
    </div>

    <div class="card-body">

      <?php if ($success): ?>
        <div class="alert alert-success">
          <?= e($success) ?><br>
          <small>Redirecting to dashboard...</small>
        </div>

        <script>
          setTimeout(function () {
            window.location.href = "InsuranceDashboard.php";
          }, 2000);
        </script>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST">

        <h6 class="text-primary font-weight-bold">
          <i class="fas fa-id-card mr-1"></i> Patient
        </h6>

        <div class="row">
          <div class="col-md-4 form-group">
            <label>Patient ID</label>
            <input class="form-control" value="<?= (int)$patient["patient_id"] ?>" readonly>
          </div>
          <div class="col-md-4 form-group">
            <label>Phone</label>
            <input class="form-control" value="<?= e($patient["phone"]) ?>" readonly>
          </div>
          <div class="col-md-4 form-group">
            <label>Status</label>
            <select class="form-control" name="status">
              <option value="active">active</option>
              <option value="suspended">suspended</option>
              <option value="expired">expired</option>
            </select>
          </div>
        </div>

        <hr>

        <h6 class="text-primary font-weight-bold">
          <i class="fas fa-shield-alt mr-1"></i> Plan & Policy Details
        </h6>

        <div class="row">
          <div class="col-md-6 form-group">
            <label>Insurance Plan</label>
            <select class="form-control" name="insurance_plan_id" required>
              <option value="">Select plan...</option>
              <?php foreach ($plans as $p): ?>
                <option value="<?= (int)$p["id"] ?>">
                  <?= e($p["plan_name"]) ?> (<?= e($p["category_name"]) ?> / <?= e($p["customer_type_name"]) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!$plans): ?>
              <small class="text-danger">No plans found for this insurance. Create plans first.</small>
            <?php endif; ?>
          </div>

          <div class="col-md-6 form-group">
            <label>Policy Number</label>
            <input class="form-control" name="policy_number" placeholder="e.g. AXA-2026-0001" required>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 form-group">
            <label>Start Date</label>
            <input type="date" class="form-control" name="start_date" required>
          </div>
          <div class="col-md-6 form-group">
            <label>End Date (optional)</label>
            <input type="date" class="form-control" name="end_date">
          </div>
        </div>

        <div class="mt-4">
          <button class="btn btn-primary btn-block" type="submit" <?= !$plans ? "disabled" : "" ?>>
            <i class="fas fa-save mr-1"></i> Save Patient Policy
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Add Patient Policy - OOP Version
 * Fully functional - saves to database
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ .'/Patient.php';
require_once __DIR__ . '/Insurance.php';
require_once __DIR__ . '/InsurancePlan.php';
require_once __DIR__ . '/PatientPolicy.php';
require_once __DIR__ .'/Validator.php';

// Initialize
$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// Check authentication
$auth->checkStaffAuth("INSURANCE_STAFF");

$insurance_id = (int)$auth->getSessionData("insurance_id");
$patient_id = (int)($_GET["patient_id"] ?? 0);

if ($insurance_id <= 0) die("Missing insurance_id in session.");
if ($patient_id <= 0) die("Invalid patient_id");

$success = "";
$error = "";

// Load patient using OOP
$patient = new Patient($conn);
if (!$patient->loadById($patient_id)) {
  die("Patient not found.");
}

// Load insurance using OOP
$insurance = new Insurance($conn);
$insurance->loadById($insurance_id);
$insurance_name = $insurance->getName();

// Load plans using OOP
$insurancePlan = new InsurancePlan($conn);
$plans = $insurancePlan->getPlansByInsurance($insurance_id);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  
  $insurance_plan_id = (int)($_POST["insurance_plan_id"] ?? 0);
  $policy_number = trim($_POST["policy_number"] ?? "");
  $start_date = trim($_POST["start_date"] ?? "");
  $end_date = trim($_POST["end_date"] ?? "");
  $status = trim($_POST["status"] ?? "active");
  
  if ($insurance_plan_id <= 0) {
    $error = "Please select a plan.";
  } elseif ($policy_number === "") {
    $error = "Policy number is required.";
  } elseif ($start_date === "") {
    $error = "Start date is required.";
  } else {
    
    // Validate plan belongs to insurance
    if (!$insurancePlan->validatePlanBelongsToInsurance($insurance_plan_id, $insurance_id)) {
      $error = "Invalid plan selected.";
    } else {
      
      // Create policy using OOP
      $policy = new PatientPolicy($conn);
      $policy->setPatientId($patient_id);
      $policy->setInsuranceId($insurance_id);
      $policy->setInsurancePlanId($insurance_plan_id);
      $policy->setPolicyNumber($policy_number);
      $policy->setStartDate($start_date);
      $policy->setEndDate($end_date === "" ? null : $end_date);
      $policy->setStatus($status);
      
      if ($policy->create()) {
        $success = "Patient policy added successfully ✅";
      } else {
        $error = "Failed to create policy";
      }
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
        Add Patient Policy — <?= Validator::sanitizeInput($patient->getFullName()) ?>
      </h5>
      <small>National ID: <?= Validator::sanitizeInput($patient->getNationalId()) ?> | Insurance: <?= Validator::sanitizeInput($insurance_name) ?> | <span class="badge badge-light"></span></small>
    </div>

    <div class="card-body">

      <?php if ($success): ?>
        <div class="alert alert-success">
          <?= Validator::sanitizeInput($success) ?><br>
          <small>Redirecting to dashboard...</small>
        </div>

        <script>
          setTimeout(function () {
            window.location.href = "InsuranceDashboard.php";
          }, 2000);
        </script>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= Validator::sanitizeInput($error) ?></div>
      <?php endif; ?>

      <form method="POST">

        <h6 class="text-primary font-weight-bold">
          <i class="fas fa-id-card mr-1"></i> Patient
        </h6>

        <div class="row">
          <div class="col-md-4 form-group">
            <label>Patient ID</label>
            <input class="form-control" value="<?= (int)$patient->getPatientId() ?>" readonly>
          </div>
          <div class="col-md-4 form-group">
            <label>Phone</label>
            <input class="form-control" value="<?= Validator::sanitizeInput($patient->getPhone()) ?>" readonly>
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
                  <?= Validator::sanitizeInput($p["plan_name"]) ?> (<?= Validator::sanitizeInput($p["category_name"]) ?> / <?= Validator::sanitizeInput($p["customer_type_name"]) ?>)
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
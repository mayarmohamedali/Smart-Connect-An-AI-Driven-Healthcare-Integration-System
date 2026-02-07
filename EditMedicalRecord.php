<?php
/**
 * Edit Medical Record - OOP Version
 * Fully functional - updates database
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ .'/Auth.php';
require_once __DIR__ . '/Patient.php';
require_once __DIR__ .'/MedicalRecord.php';
require_once __DIR__ .'/Validator.php';

// Initialize
$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// Check authentication
$auth->checkStaffAuth("HOSPITAL_STAFF");

$patient_id = (int)($_GET["patient_id"] ?? 0);
$record_id = (int)($_GET["record_id"] ?? 0);

if ($patient_id <= 0 || $record_id <= 0) {
  die("Missing patient_id or record_id");
}

$error = "";

// Dropdown options
$smokingOptions = ["Never", "Former", "Current", "Unknown"];
$activityOptions = ["Low", "Moderate", "High", "Unknown"];

// Load patient using OOP
$patient = new Patient($conn);
if (!$patient->loadById($patient_id)) {
  die("Patient not found.");
}

// Load medical record using OOP
$medicalRecord = new MedicalRecord($conn);
$record = $medicalRecord->loadById($record_id);

if (!$record || $record['patient_id'] != $patient_id) {
  die("Record not found for this patient.");
}

// Handle UPDATE
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "update_record") {
  
  // Set all values
  $medicalRecord->setAge((int)($_POST["age"] ?? 0));
  $medicalRecord->setCheckinDate(trim($_POST["checkin_date"] ?? ""));
  $medicalRecord->setCheckoutDate(trim($_POST["checkout_date"] ?? ""));
  
  // Lab values
  $medicalRecord->setLabValues(
    (float)($_POST["cbc_hb1"] ?? 0),
    (float)($_POST["cbc_tlc1"] ?? 0),
    (float)($_POST["cbc_plat1"] ?? 0),
    (float)($_POST["blood_uria1"] ?? 0),
    (float)($_POST["blood_creatinine1"] ?? 0),
    (float)($_POST["cbc_hb2"] ?? 0),
    (float)($_POST["cbc_tlc2"] ?? 0),
    (float)($_POST["cbc_plat2"] ?? 0),
    (float)($_POST["blood_uria2"] ?? 0),
    (float)($_POST["blood_creatinine2"] ?? 0)
  );
  
  // Vitals
  $medicalRecord->setBMI((float)($_POST["bmi"] ?? 0));
  $medicalRecord->setGlucose((float)($_POST["glucose"] ?? 0));
  $medicalRecord->setSystolicBP((float)($_POST["systolic_bp"] ?? 0));
  
  // Aggregates
  $medicalRecord->setAggregates(
    (int)($_POST["month"] ?? 0),
    (int)($_POST["admission_count"] ?? 0),
    (float)($_POST["avg_creatinine"] ?? 0),
    (float)($_POST["avg_urea"] ?? 0),
    (float)($_POST["avg_hb"] ?? 0),
    (float)($_POST["avg_tlc"] ?? 0),
    (float)($_POST["avg_platelets"] ?? 0)
  );
  
  // Lifestyle
  $smoking_status = trim($_POST["smoking_status"] ?? "Unknown");
  if (!in_array($smoking_status, $smokingOptions, true)) $smoking_status = "Unknown";
  
  $physical_activity_level = trim($_POST["physical_activity_level"] ?? "Unknown");
  if (!in_array($physical_activity_level, $activityOptions, true)) $physical_activity_level = "Unknown";
  
  $medicalRecord->setLifestyle(
    (int)($_POST["length_of_stay"] ?? 0),
    $smoking_status,
    $physical_activity_level
  );
  
  // Risk flags
  $medicalRecord->setRiskFlags(
    isset($_POST["has_diabetes"]) ? 1 : 0,
    isset($_POST["has_hypertension"]) ? 1 : 0,
    isset($_POST["has_kidney_disease"]) ? 1 : 0,
    isset($_POST["has_heart_disease"]) ? 1 : 0
  );
  
  // Diagnosis
  $medicalRecord->setDiagnosis(trim($_POST["diagnosis"] ?? ""));
  
  // Update in database
  if ($medicalRecord->update()) {
    header("Location: EditMedicalRecord.php?patient_id=$patient_id&record_id=$record_id&success=1");
    exit;
  } else {
    $error = "Failed to update record";
  }
}

function selected($current, $value) { 
  return ((string)$current === (string)$value) ? "selected" : ""; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Edit Medical Record</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
      <h4 class="mb-0 text-primary">
        <i class="fas fa-edit mr-2"></i> Edit Record #<?= (int)$record_id ?>
      </h4>
      <small class="text-muted">
        Patient: <?= Validator::sanitizeInput($patient->getFullName()) ?> (<?= Validator::sanitizeInput($patient->getNationalId()) ?>) | <span class="badge badge-info"></span>
      </small>
    </div>

    <a class="btn btn-outline-secondary"
       href="HospitalDashboard.php#patients">
      <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Record updated successfully ✅</div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= Validator::sanitizeInput($error) ?></div>
  <?php endif; ?>

  <div class="card shadow">
    <div class="card-body">

      <form method="POST">
        <input type="hidden" name="action" value="update_record">

        <!-- BASIC -->
        <h6 class="text-primary font-weight-bold mb-3">Basic</h6>
        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Age</label>
            <input type="number" name="age" class="form-control" value="<?= Validator::sanitizeInput($record["age"]) ?>" required>
          </div>
          <div class="form-group col-md-3">
            <label>Check-in Date</label>
            <input type="date" name="checkin_date" class="form-control" value="<?= Validator::sanitizeInput($record["checkin_date"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Check-out Date</label>
            <input type="date" name="checkout_date" class="form-control" value="<?= Validator::sanitizeInput($record["checkout_date"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Length of Stay</label>
            <input type="number" name="length_of_stay" class="form-control" value="<?= Validator::sanitizeInput($record["length_of_stay"]) ?>">
          </div>
        </div>

        <hr>

        <!-- VITALS -->
        <h6 class="text-primary font-weight-bold mb-3">Vitals</h6>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label>BMI</label>
            <input type="number" step="0.01" name="bmi" class="form-control" value="<?= Validator::sanitizeInput($record["bmi"]) ?>">
          </div>
          <div class="form-group col-md-4">
            <label>Glucose</label>
            <input type="number" step="0.01" name="glucose" class="form-control" value="<?= Validator::sanitizeInput($record["glucose"]) ?>">
          </div>
          <div class="form-group col-md-4">
            <label>Systolic BP</label>
            <input type="number" step="0.01" name="systolic_bp" class="form-control" value="<?= Validator::sanitizeInput($record["systolic_bp"]) ?>">
          </div>
        </div>

        <hr>

        <!-- LABS 1 -->
        <h6 class="text-primary font-weight-bold mb-3">Lab Results (1)</h6>
        <div class="form-row">
          <div class="form-group col-md-3"><label>CBC-HB1</label><input type="number" step="0.01" name="cbc_hb1" class="form-control" value="<?= Validator::sanitizeInput($record["cbc_hb1"]) ?>"></div>
          <div class="form-group col-md-3"><label>CBC-TLC1</label><input type="number" step="0.01" name="cbc_tlc1" class="form-control" value="<?= Validator::sanitizeInput($record["cbc_tlc1"]) ?>"></div>
          <div class="form-group col-md-3"><label>CBC-PLAT1</label><input type="number" step="0.01" name="cbc_plat1" class="form-control" value="<?= Validator::sanitizeInput($record["cbc_plat1"]) ?>"></div>
          <div class="form-group col-md-3"><label>Blood Urea 1</label><input type="number" step="0.01" name="blood_uria1" class="form-control" value="<?= Validator::sanitizeInput($record["blood_uria1"]) ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-3"><label>Creatinine 1</label><input type="number" step="0.01" name="blood_creatinine1" class="form-control" value="<?= Validator::sanitizeInput($record["blood_creatinine1"]) ?>"></div>
        </div>

        <hr>

        <!-- LABS 2 -->
        <h6 class="text-primary font-weight-bold mb-3">Lab Results (2)</h6>
        <div class="form-row">
          <div class="form-group col-md-3"><label>CBC-HB2</label><input type="number" step="0.01" name="cbc_hb2" class="form-control" value="<?= Validator::sanitizeInput($record["cbc_hb2"]) ?>"></div>
          <div class="form-group col-md-3"><label>CBC-TLC2</label><input type="number" step="0.01" name="cbc_tlc2" class="form-control" value="<?= Validator::sanitizeInput($record["cbc_tlc2"]) ?>"></div>
          <div class="form-group col-md-3"><label>CBC-PLAT2</label><input type="number" step="0.01" name="cbc_plat2" class="form-control" value="<?= Validator::sanitizeInput($record["cbc_plat2"]) ?>"></div>
          <div class="form-group col-md-3"><label>Blood Urea 2</label><input type="number" step="0.01" name="blood_uria2" class="form-control" value="<?= Validator::sanitizeInput($record["blood_uria2"]) ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-3"><label>Creatinine 2</label><input type="number" step="0.01" name="blood_creatinine2" class="form-control" value="<?= Validator::sanitizeInput($record["blood_creatinine2"]) ?>"></div>
        </div>

        <hr>

        <!-- AGGREGATES -->
        <h6 class="text-primary font-weight-bold mb-3">Timeline & Aggregates</h6>
        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Month</label>
            <select name="month" class="form-control">
              <option value="0">Select month</option>
              <?php for ($m=1; $m<=12; $m++): ?>
                <option value="<?= $m ?>" <?= selected((int)$record["month"], $m) ?>><?= $m ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label>Admission Count</label>
            <input type="number" name="admission_count" class="form-control" value="<?= Validator::sanitizeInput($record["admission_count"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Avg Creatinine</label>
            <input type="number" step="0.01" name="avg_creatinine" class="form-control" value="<?= Validator::sanitizeInput($record["avg_creatinine"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Avg Urea</label>
            <input type="number" step="0.01" name="avg_urea" class="form-control" value="<?= Validator::sanitizeInput($record["avg_urea"]) ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Avg HB</label>
            <input type="number" step="0.01" name="avg_hb" class="form-control" value="<?= Validator::sanitizeInput($record["avg_hb"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Avg TLC</label>
            <input type="number" step="0.01" name="avg_tlc" class="form-control" value="<?= Validator::sanitizeInput($record["avg_tlc"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Avg Platelets</label>
            <input type="number" step="0.01" name="avg_platelets" class="form-control" value="<?= Validator::sanitizeInput($record["avg_platelets"]) ?>">
          </div>
        </div>

        <hr>

        <!-- LIFESTYLE -->
        <h6 class="text-primary font-weight-bold mb-3">Lifestyle</h6>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Smoking Status</label>
            <select name="smoking_status" class="form-control">
              <?php foreach ($smokingOptions as $opt): ?>
                <option value="<?= Validator::sanitizeInput($opt) ?>" <?= selected(trim($record["smoking_status"] ?? "Unknown"), $opt) ?>>
                  <?= Validator::sanitizeInput($opt) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-6">
            <label>Physical Activity Level</label>
            <select name="physical_activity_level" class="form-control">
              <?php foreach ($activityOptions as $opt): ?>
                <option value="<?= Validator::sanitizeInput($opt) ?>" <?= selected(trim($record["physical_activity_level"] ?? "Unknown"), $opt) ?>>
                  <?= Validator::sanitizeInput($opt) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <hr>

        <!-- DIAGNOSIS + RISKS -->
        <h6 class="text-primary font-weight-bold mb-3">Diagnosis & Risks</h6>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Diagnosis</label>
            <input type="text" name="diagnosis" class="form-control" value="<?= Validator::sanitizeInput($record["diagnosis"] ?? "") ?>" placeholder="e.g. CKD, Diabetes, Hypertension">
          </div>

          <div class="form-group col-md-6">
            <label class="d-block">Risks</label>
            <div class="custom-control custom-checkbox custom-control-inline">
              <input type="checkbox" class="custom-control-input" id="dm" name="has_diabetes" <?= !empty($record["has_diabetes"]) ? "checked" : "" ?>>
              <label class="custom-control-label" for="dm">Has Diabetes</label>
            </div>
            <div class="custom-control custom-checkbox custom-control-inline">
              <input type="checkbox" class="custom-control-input" id="htn" name="has_hypertension" <?= !empty($record["has_hypertension"]) ? "checked" : "" ?>>
              <label class="custom-control-label" for="htn">Has Hypertension</label>
            </div>
            <div class="custom-control custom-checkbox custom-control-inline">
              <input type="checkbox" class="custom-control-input" id="kd" name="has_kidney_disease" <?= !empty($record["has_kidney_disease"]) ? "checked" : "" ?>>
              <label class="custom-control-label" for="kd">Has Kidney Disease</label>
            </div>
            <div class="custom-control custom-checkbox custom-control-inline">
              <input type="checkbox" class="custom-control-input" id="hd" name="has_heart_disease" <?= !empty($record["has_heart_disease"]) ? "checked" : "" ?>>
              <label class="custom-control-label" for="hd">Has Heart Disease</label>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Save Changes
          </button>
        </div>

      </form>

    </div>
  </div>

</div>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>

</body>
</html>
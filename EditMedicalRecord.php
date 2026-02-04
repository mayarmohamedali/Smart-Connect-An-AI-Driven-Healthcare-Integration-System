<?php
session_start();

if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "staff" || ($_SESSION["role"] ?? "") !== "HOSPITAL_STAFF") {
  header("Location: login.html");
  exit;
}

require_once "db.php";

$patient_id = (int)($_GET["patient_id"] ?? 0);
$record_id  = (int)($_GET["record_id"] ?? 0);

if ($patient_id <= 0 || $record_id <= 0) {
  die("Missing patient_id or record_id");
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
function selected($current, $value) { return ((string)$current === (string)$value) ? "selected" : ""; }

/* =========================
   Dropdown Options
========================= */
$smokingOptions = ["Never", "Former", "Current", "Unknown"];
$activityOptions = ["Low", "Moderate", "High", "Unknown"];

// You can edit this list as you like
$diagnosisOptions = [
  "Hypertension",
  "Diabetes",
  "Kidney Disease",
  "Heart Disease",
  "Infection",
  "Other"
];

/* =========================
   Fetch Patient (optional header info)
========================= */
$stmt = $conn->prepare("SELECT patient_id, full_name, national_id FROM patients WHERE patient_id=? LIMIT 1");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) die("Patient not found.");

/* =========================
   Fetch Record
========================= */
$stmt = $conn->prepare("SELECT * FROM medical_records WHERE record_id=? AND patient_id=? LIMIT 1");
$stmt->bind_param("ii", $record_id, $patient_id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) die("Record not found for this patient.");

/* =========================
   UPDATE (POST)
========================= */
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "update_record") {

  // Basic
  $age          = (int)($_POST["age"] ?? 0);
  $checkin_date = trim($_POST["checkin_date"] ?? "");
  $checkout_date= trim($_POST["checkout_date"] ?? "");
  $length_of_stay = (int)($_POST["length_of_stay"] ?? 0);

  // Labs 1
  $cbc_hb1   = (float)($_POST["cbc_hb1"] ?? 0);
  $cbc_tlc1  = (float)($_POST["cbc_tlc1"] ?? 0);
  $cbc_plat1 = (float)($_POST["cbc_plat1"] ?? 0);
  $blood_uria1 = (float)($_POST["blood_uria1"] ?? 0);
  $blood_creatinine1 = (float)($_POST["blood_creatinine1"] ?? 0);

  // Labs 2
  $cbc_hb2   = (float)($_POST["cbc_hb2"] ?? 0);
  $cbc_tlc2  = (float)($_POST["cbc_tlc2"] ?? 0);
  $cbc_plat2 = (float)($_POST["cbc_plat2"] ?? 0);
  $blood_uria2 = (float)($_POST["blood_uria2"] ?? 0);
  $blood_creatinine2 = (float)($_POST["blood_creatinine2"] ?? 0);

  // Vitals
  $bmi       = (float)($_POST["bmi"] ?? 0);
  $glucose   = (float)($_POST["glucose"] ?? 0);
  $systolic_bp = (float)($_POST["systolic_bp"] ?? 0);

  // Timeline / aggregated fields
  $month = (int)($_POST["month"] ?? 0);
  $admission_count = (int)($_POST["admission_count"] ?? 0);

  $avg_creatinine = (float)($_POST["avg_creatinine"] ?? 0);
  $avg_urea       = (float)($_POST["avg_urea"] ?? 0);
  $avg_hb         = (float)($_POST["avg_hb"] ?? 0);
  $avg_tlc        = (float)($_POST["avg_tlc"] ?? 0);
  $avg_platelets  = (float)($_POST["avg_platelets"] ?? 0);

  // Lifestyle (dropdown values)
  $smoking_status = trim($_POST["smoking_status"] ?? "Unknown");
  if (!in_array($smoking_status, $smokingOptions, true)) $smoking_status = "Unknown";

  $physical_activity_level = trim($_POST["physical_activity_level"] ?? "Unknown");
  if (!in_array($physical_activity_level, $activityOptions, true)) $physical_activity_level = "Unknown";

  // Diagnosis (dropdown + custom)
  $diagnosis = trim($_POST["diagnosis"] ?? "");
  if ($diagnosis === "__custom__") {
    $diagnosis = trim($_POST["diagnosis_custom"] ?? "");
  }

  // Risks (checkboxes)
  $has_diabetes       = isset($_POST["has_diabetes"]) ? 1 : 0;
  $has_hypertension   = isset($_POST["has_hypertension"]) ? 1 : 0;
  $has_kidney_disease = isset($_POST["has_kidney_disease"]) ? 1 : 0;
  $has_heart_disease  = isset($_POST["has_heart_disease"]) ? 1 : 0;

  // Small validation example (optional)
  if ($age < 0 || $age > 130) {
    $error = "Age looks invalid.";
  }

  if ($error === "") {
    $upd = $conn->prepare("
      UPDATE medical_records
      SET
        age=?,
        checkin_date=?,
        checkout_date=?,
        length_of_stay=?,

        cbc_hb1=?,
        cbc_tlc1=?,
        cbc_plat1=?,
        blood_uria1=?,
        blood_creatinine1=?,

        cbc_hb2=?,
        cbc_tlc2=?,
        cbc_plat2=?,
        blood_uria2=?,
        blood_creatinine2=?,

        bmi=?,
        glucose=?,
        systolic_bp=?,

        month=?,
        admission_count=?,

        avg_creatinine=?,
        avg_urea=?,
        avg_hb=?,
        avg_tlc=?,
        avg_platelets=?,

        smoking_status=?,
        physical_activity_level=?,

        has_diabetes=?,
        has_hypertension=?,
        has_kidney_disease=?,
        has_heart_disease=?,

        diagnosis=?
      WHERE record_id=? AND patient_id=?
      LIMIT 1
    ");

    // bind types: i=int, s=string, d=double
    $upd->bind_param(
      "issidddddddddd" . "ddd" . "ii" . "ddddd" . "ss" . "iiiis" . "ii",
      $age, $checkin_date, $checkout_date, $length_of_stay,

      $cbc_hb1, $cbc_tlc1, $cbc_plat1, $blood_uria1, $blood_creatinine1,
      $cbc_hb2, $cbc_tlc2, $cbc_plat2, $blood_uria2, $blood_creatinine2,

      $bmi, $glucose, $systolic_bp,

      $month, $admission_count,

      $avg_creatinine, $avg_urea, $avg_hb, $avg_tlc, $avg_platelets,

      $smoking_status, $physical_activity_level,

      $has_diabetes, $has_hypertension, $has_kidney_disease, $has_heart_disease,

      $diagnosis,
      $record_id, $patient_id
    );

    $upd->execute();
    $upd->close();

    header("Location: ViewMedicalRecords.php?patient_id=$patient_id&record_id=$record_id#details");
    exit;
  }
}

/* =========================
   Decide if diagnosis is custom
========================= */
$currentDiagnosis = trim($record["diagnosis"] ?? "");
$isCustomDiagnosis = ($currentDiagnosis !== "" && !in_array($currentDiagnosis, $diagnosisOptions, true));
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
        Patient: <?= e($patient["full_name"] ?? "") ?> (<?= e($patient["national_id"] ?? "") ?>)
      </small>
    </div>

    <a class="btn btn-outline-secondary"
       href="ViewMedicalRecords.php?patient_id=<?= (int)$patient_id ?>&record_id=<?= (int)$record_id ?>#details">
      <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
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
            <input type="number" name="age" class="form-control" value="<?= e($record["age"]) ?>" required>
          </div>
          <div class="form-group col-md-3">
            <label>Check-in Date</label>
            <input type="date" name="checkin_date" class="form-control" value="<?= e($record["checkin_date"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Check-out Date</label>
            <input type="date" name="checkout_date" class="form-control" value="<?= e($record["checkout_date"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Length of Stay</label>
            <input type="number" name="length_of_stay" class="form-control" value="<?= e($record["length_of_stay"]) ?>">
          </div>
        </div>

        <hr>

        <!-- VITALS -->
        <h6 class="text-primary font-weight-bold mb-3">Vitals</h6>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label>BMI</label>
            <input type="number" step="0.01" name="bmi" class="form-control" value="<?= e($record["bmi"]) ?>">
          </div>
          <div class="form-group col-md-4">
            <label>Glucose</label>
            <input type="number" step="0.01" name="glucose" class="form-control" value="<?= e($record["glucose"]) ?>">
          </div>
          <div class="form-group col-md-4">
            <label>Systolic BP</label>
            <input type="number" step="0.01" name="systolic_bp" class="form-control" value="<?= e($record["systolic_bp"]) ?>">
          </div>
        </div>

        <hr>

        <!-- LABS 1 -->
        <h6 class="text-primary font-weight-bold mb-3">Lab Results (1)</h6>
        <div class="form-row">
          <div class="form-group col-md-3"><label>CBC-HB1</label><input type="number" step="0.01" name="cbc_hb1" class="form-control" value="<?= e($record["cbc_hb1"]) ?>"></div>
          <div class="form-group col-md-3"><label>CBC-TLC1</label><input type="number" step="0.01" name="cbc_tlc1" class="form-control" value="<?= e($record["cbc_tlc1"]) ?>"></div>
          <div class="form-group col-md-3"><label>CBC-PLAT1</label><input type="number" step="0.01" name="cbc_plat1" class="form-control" value="<?= e($record["cbc_plat1"]) ?>"></div>
          <div class="form-group col-md-3"><label>Blood Urea 1</label><input type="number" step="0.01" name="blood_uria1" class="form-control" value="<?= e($record["blood_uria1"]) ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-3"><label>Creatinine 1</label><input type="number" step="0.01" name="blood_creatinine1" class="form-control" value="<?= e($record["blood_creatinine1"]) ?>"></div>
        </div>

        <hr>

        <!-- LABS 2 -->
        <h6 class="text-primary font-weight-bold mb-3">Lab Results (2)</h6>
        <div class="form-row">
          <div class="form-group col-md-3"><label>CBC-HB2</label><input type="number" step="0.01" name="cbc_hb2" class="form-control" value="<?= e($record["cbc_hb2"]) ?>"></div>
          <div class="form-group col-md-3"><label>CBC-TLC2</label><input type="number" step="0.01" name="cbc_tlc2" class="form-control" value="<?= e($record["cbc_tlc2"]) ?>"></div>
          <div class="form-group col-md-3"><label>CBC-PLAT2</label><input type="number" step="0.01" name="cbc_plat2" class="form-control" value="<?= e($record["cbc_plat2"]) ?>"></div>
          <div class="form-group col-md-3"><label>Blood Urea 2</label><input type="number" step="0.01" name="blood_uria2" class="form-control" value="<?= e($record["blood_uria2"]) ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-3"><label>Creatinine 2</label><input type="number" step="0.01" name="blood_creatinine2" class="form-control" value="<?= e($record["blood_creatinine2"]) ?>"></div>
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
            <input type="number" name="admission_count" class="form-control" value="<?= e($record["admission_count"]) ?>">
          </div>

          <div class="form-group col-md-3">
            <label>Avg Creatinine</label>
            <input type="number" step="0.01" name="avg_creatinine" class="form-control" value="<?= e($record["avg_creatinine"]) ?>">
          </div>

          <div class="form-group col-md-3">
            <label>Avg Urea</label>
            <input type="number" step="0.01" name="avg_urea" class="form-control" value="<?= e($record["avg_urea"]) ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Avg HB</label>
            <input type="number" step="0.01" name="avg_hb" class="form-control" value="<?= e($record["avg_hb"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Avg TLC</label>
            <input type="number" step="0.01" name="avg_tlc" class="form-control" value="<?= e($record["avg_tlc"]) ?>">
          </div>
          <div class="form-group col-md-3">
            <label>Avg Platelets</label>
            <input type="number" step="0.01" name="avg_platelets" class="form-control" value="<?= e($record["avg_platelets"]) ?>">
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
                <option value="<?= e($opt) ?>" <?= selected(trim($record["smoking_status"] ?? "Unknown"), $opt) ?>>
                  <?= e($opt) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group col-md-6">
            <label>Physical Activity Level</label>
            <select name="physical_activity_level" class="form-control">
              <?php foreach ($activityOptions as $opt): ?>
                <option value="<?= e($opt) ?>" <?= selected(trim($record["physical_activity_level"] ?? "Unknown"), $opt) ?>>
                  <?= e($opt) ?>
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
           <div class="form-row">
    <input type="text"
           name="diagnosis"
           class="form-control"
           value="<?= e($record["diagnosis"] ?? "") ?>"
           placeholder="e.g. CKD, Diabetes, Hypertension">
  </div>
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

<script>
function toggleDiagnosisCustom(){
  var sel = document.getElementById("diagnosisSelect");
  var custom = document.getElementById("diagnosisCustom");
  if(!sel || !custom) return;
  custom.style.display = (sel.value === "__custom__") ? "block" : "none";
}
toggleDiagnosisCustom();
</script>

</body>
</html>

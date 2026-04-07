<?php
/**
 * Edit Medical Record - OOP Version
 * Updated to match AddMedicalRecord.php (all new fields)
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Patient.php';
require_once __DIR__ . '/MedicalRecord.php';
require_once __DIR__ . '/Validator.php';

// Initialize
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// Check authentication
$auth->checkStaffAuth("HOSPITAL_STAFF");

$patient_id = (int)($_GET["patient_id"] ?? 0);
$record_id  = (int)($_GET["record_id"]  ?? 0);

if ($patient_id <= 0 || $record_id <= 0) {
    die("Missing patient_id or record_id");
}

$error = "";

// Dropdown options
$smokingOptions  = ["Non-Smoker", "Smoker", "Former Smoker"];
$activityOptions = ["Low", "Moderate", "High"];
$dietOptions     = ["Poor", "Average", "Good"];
$daysOfWeek      = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];

// Load patient
$patient = new Patient($conn);
if (!$patient->loadById($patient_id)) {
    die("Patient not found.");
}

// Load medical record
$medicalRecord = new MedicalRecord($conn);
$record = $medicalRecord->loadById($record_id);

if (!$record || (int)$record['patient_id'] !== $patient_id) {
    die("Record not found for this patient.");
}

// ── Handle UPDATE ──────────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "update_record") {

    // Basic
    $medicalRecord->setAge((int)Validator::nullIfEmpty($_POST["age"] ?? null));
    $medicalRecord->setCheckinDate(Validator::nullIfEmpty($_POST["checkin_date"]  ?? null));
    $medicalRecord->setCheckoutDate(Validator::nullIfEmpty($_POST["checkout_date"] ?? null));

    // Lab values
    $medicalRecord->setLabValues(
        Validator::nullIfEmpty($_POST["cbc_hb1"]           ?? null),
        Validator::nullIfEmpty($_POST["cbc_tlc1"]          ?? null),
        Validator::nullIfEmpty($_POST["cbc_plat1"]         ?? null),
        Validator::nullIfEmpty($_POST["blood_uria1"]       ?? null),
        Validator::nullIfEmpty($_POST["blood_creatinine1"] ?? null),
        Validator::nullIfEmpty($_POST["cbc_hb2"]           ?? null),
        Validator::nullIfEmpty($_POST["cbc_tlc2"]          ?? null),
        Validator::nullIfEmpty($_POST["cbc_plat2"]         ?? null),
        Validator::nullIfEmpty($_POST["blood_uria2"]       ?? null),
        Validator::nullIfEmpty($_POST["blood_creatinine2"] ?? null)
    );

    // Vitals
    $medicalRecord->setBMI(Validator::nullIfEmpty($_POST["bmi"]               ?? null));
    $medicalRecord->setGlucose(Validator::nullIfEmpty($_POST["glucose"]       ?? null));
    $medicalRecord->setCholesterolLevel(Validator::nullIfEmpty($_POST["cholesterol_level"] ?? null));
    $medicalRecord->setSystolicBP(Validator::nullIfEmpty($_POST["systolic_bp"] ?? null));

    // Aggregates (now includes year, day_of_week, avg_length_stay)
    $medicalRecord->setAggregates(
        Validator::nullIfEmpty($_POST["month"]           ?? null),
        Validator::nullIfEmpty($_POST["year"]            ?? null),
        Validator::nullIfEmpty($_POST["day_of_week"]     ?? null),
        Validator::nullIfEmpty($_POST["admission_count"] ?? null),
        Validator::nullIfEmpty($_POST["avg_creatinine"]  ?? null),
        Validator::nullIfEmpty($_POST["avg_urea"]        ?? null),
        Validator::nullIfEmpty($_POST["avg_hb"]          ?? null),
        Validator::nullIfEmpty($_POST["avg_tlc"]         ?? null),
        Validator::nullIfEmpty($_POST["avg_platelets"]   ?? null),
        Validator::nullIfEmpty($_POST["avg_length_stay"] ?? null)
    );

    // Deltas
    $medicalRecord->setDeltas(
        Validator::nullIfEmpty($_POST["delta_hb"]          ?? null),
        Validator::nullIfEmpty($_POST["delta_tlc"]         ?? null),
        Validator::nullIfEmpty($_POST["delta_plat"]        ?? null),
        Validator::nullIfEmpty($_POST["delta_uria"]        ?? null),
        Validator::nullIfEmpty($_POST["delta_creatinine"]  ?? null)
    );

    // Lifestyle (now includes diet_quality, alcohol_consumption, sleep_hours)
    $medicalRecord->setLifestyle(
        Validator::nullIfEmpty($_POST["length_of_stay"]          ?? null),
        Validator::nullIfEmpty($_POST["smoking_status"]          ?? null),
        Validator::nullIfEmpty($_POST["physical_activity_level"] ?? null),
        Validator::nullIfEmpty($_POST["diet_quality"]            ?? null),
        Validator::boolToInt($_POST["alcohol_consumption"]       ?? 0),
        Validator::nullIfEmpty($_POST["sleep_hours"]             ?? null)
    );

    // Risk flags
    $medicalRecord->setRiskFlags(
        Validator::boolToInt($_POST["has_diabetes"]      ?? 0),
        Validator::boolToInt($_POST["has_hypertension"]  ?? 0),
        Validator::boolToInt($_POST["has_kidney_disease"] ?? 0),
        Validator::boolToInt($_POST["has_heart_disease"] ?? 0)
    );

    // Risk scores
    $medicalRecord->setRiskScores(
        Validator::nullIfEmpty($_POST["stress_level"]      ?? null),
        Validator::nullIfEmpty($_POST["family_history"]    ?? null),
        Validator::nullIfEmpty($_POST["medications_count"] ?? null),
        Validator::nullIfEmpty($_POST["risk_score"]        ?? null),
        Validator::nullIfEmpty($_POST["symptom_burden"]    ?? null),
        Validator::nullIfEmpty($_POST["seasonal_weight"]   ?? null)
    );

    // Symptoms
    $medicalRecord->setSymptoms(
        Validator::nullIfEmpty($_POST["fever"]               ?? null),
        Validator::nullIfEmpty($_POST["cough"]               ?? null),
        Validator::nullIfEmpty($_POST["fatigue"]             ?? null),
        isset($_POST["chest_pain"]) ? 1 : null,
        Validator::nullIfEmpty($_POST["shortness_of_breath"] ?? null),
        isset($_POST["headache"]) ? 1 : null
    );

    // Diagnosis
    $medicalRecord->setDiagnosis(Validator::nullIfEmpty($_POST["diagnosis"]        ?? null));
    $medicalRecord->setDiseaseCategory(Validator::nullIfEmpty($_POST["disease_category"] ?? null));

    if ($medicalRecord->update()) {
        header("Location: EditMedicalRecord.php?patient_id=$patient_id&record_id=$record_id&success=1");
        exit;
    } else {
        $error = "Failed to update record. Please check all fields and try again.";
    }
}

// Helpers
function sel($current, $value): string {
    return ((string)$current === (string)$value) ? "selected" : "";
}
function chk($val): string {
    return !empty($val) ? "checked" : "";
}
function e($v): string {
    return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Edit Medical Record</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    .section-title {
      font-size: .85rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: #4e73df;
      margin-bottom: .75rem;
    }
  </style>
</head>
<body class="bg-light">

<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
      <h4 class="mb-0 text-primary">
        <i class="fas fa-edit mr-2"></i> Edit Record #<?= (int)$record_id ?>
      </h4>
      <small class="text-muted">
        Patient: <?= e($patient->getFullName()) ?> (<?= e($patient->getNationalId()) ?>)
      </small>
    </div>
    <a class="btn btn-outline-secondary" href="HospitalDashboard.php#patients">
      <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Record updated successfully ✅</div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-notes-medical mr-2"></i> Edit Medical Record</h5>
    </div>
    <div class="card-body">

      <form method="POST">
        <input type="hidden" name="action" value="update_record">

        <!-- ══ ADMISSION INFO ══════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-calendar-alt mr-1"></i> Admission Info</p>
        <div class="row">
          <div class="col-md-3 form-group">
            <label>Age</label>
            <input type="number" name="age" min="0" max="150" class="form-control" value="<?= e($record["age"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-in Date</label>
            <input type="date" name="checkin_date" class="form-control" value="<?= e($record["checkin_date"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-out Date</label>
            <input type="date" name="checkout_date" class="form-control" value="<?= e($record["checkout_date"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Length of Stay (days)</label>
            <input type="number" name="length_of_stay" min="0" class="form-control" value="<?= e($record["length_of_stay"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Month (1–12)</label>
            <input type="number" name="month" min="1" max="12" class="form-control" value="<?= e($record["month"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Year</label>
            <input type="number" name="year" min="2000" max="2100" class="form-control" value="<?= e($record["year"]) ?>" placeholder="e.g. 2026">
          </div>
          <div class="col-md-3 form-group">
            <label>Day of Week</label>
            <select name="day_of_week" class="form-control">
              <option value="">Select</option>
              <?php foreach ($daysOfWeek as $d): ?>
                <option value="<?= $d ?>" <?= sel($record["day_of_week"], $d) ?>><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 form-group">
            <label>Admission Count</label>
            <input type="number" name="admission_count" min="0" class="form-control" value="<?= e($record["admission_count"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Avg Length of Stay</label>
            <input type="number" step="0.000001" name="avg_length_stay" class="form-control" value="<?= e($record["avg_length_stay"]) ?>">
          </div>
        </div>

        <hr>

        <!-- ══ LAB RESULTS ════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-vial mr-1"></i> Lab Results — Round 1</p>
        <div class="row">
          <div class="col-md-3 form-group"><label>CBC-HB1</label><input type="number" step="0.01" name="cbc_hb1" class="form-control" value="<?= e($record["cbc_hb1"]) ?>"></div>
          <div class="col-md-3 form-group"><label>CBC-TLC1</label><input type="number" step="0.01" name="cbc_tlc1" class="form-control" value="<?= e($record["cbc_tlc1"]) ?>"></div>
          <div class="col-md-3 form-group"><label>CBC-PLAT1</label><input type="number" step="0.01" name="cbc_plat1" class="form-control" value="<?= e($record["cbc_plat1"]) ?>"></div>
          <div class="col-md-3 form-group"><label>Blood Urea1</label><input type="number" step="0.01" name="blood_uria1" class="form-control" value="<?= e($record["blood_uria1"]) ?>"></div>
          <div class="col-md-3 form-group"><label>Blood Creatinine1</label><input type="number" step="0.01" name="blood_creatinine1" class="form-control" value="<?= e($record["blood_creatinine1"]) ?>"></div>
        </div>

        <p class="section-title mt-2"><i class="fas fa-vial mr-1"></i> Lab Results — Round 2</p>
        <div class="row">
          <div class="col-md-3 form-group"><label>CBC-HB2</label><input type="number" step="0.01" name="cbc_hb2" class="form-control" value="<?= e($record["cbc_hb2"]) ?>"></div>
          <div class="col-md-3 form-group"><label>CBC-TLC2</label><input type="number" step="0.01" name="cbc_tlc2" class="form-control" value="<?= e($record["cbc_tlc2"]) ?>"></div>
          <div class="col-md-3 form-group"><label>CBC-PLAT2</label><input type="number" step="0.01" name="cbc_plat2" class="form-control" value="<?= e($record["cbc_plat2"]) ?>"></div>
          <div class="col-md-3 form-group"><label>Blood Urea2</label><input type="number" step="0.01" name="blood_uria2" class="form-control" value="<?= e($record["blood_uria2"]) ?>"></div>
          <div class="col-md-3 form-group"><label>Blood Creatinine2</label><input type="number" step="0.01" name="blood_creatinine2" class="form-control" value="<?= e($record["blood_creatinine2"]) ?>"></div>
        </div>

        <hr>

        <!-- ══ VITALS ═════════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-heartbeat mr-1"></i> Vitals</p>
        <div class="row">
          <div class="col-md-3 form-group"><label>BMI</label><input type="number" step="0.01" name="bmi" class="form-control" value="<?= e($record["bmi"]) ?>"></div>
          <div class="col-md-3 form-group"><label>Glucose</label><input type="number" step="0.01" name="glucose" class="form-control" value="<?= e($record["glucose"]) ?>"></div>
          <div class="col-md-3 form-group"><label>Cholesterol Level</label><input type="number" step="0.01" name="cholesterol_level" class="form-control" value="<?= e($record["cholesterol_level"]) ?>"></div>
          <div class="col-md-3 form-group"><label>Systolic BP</label><input type="number" name="systolic_bp" class="form-control" value="<?= e($record["systolic_bp"]) ?>"></div>
        </div>

        <hr>

        <!-- ══ AVERAGES ═══════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-calculator mr-1"></i> Averages</p>
        <div class="row">
          <div class="col-md-2 form-group"><label>Avg Creatinine</label><input type="number" step="0.01" name="avg_creatinine" class="form-control" value="<?= e($record["avg_creatinine"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Avg Urea</label><input type="number" step="0.01" name="avg_urea" class="form-control" value="<?= e($record["avg_urea"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Avg HB</label><input type="number" step="0.01" name="avg_hb" class="form-control" value="<?= e($record["avg_hb"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Avg TLC</label><input type="number" step="0.01" name="avg_tlc" class="form-control" value="<?= e($record["avg_tlc"]) ?>"></div>
          <div class="col-md-4 form-group"><label>Avg Platelets</label><input type="number" step="0.01" name="avg_platelets" class="form-control" value="<?= e($record["avg_platelets"]) ?>"></div>
        </div>

        <hr>

        <!-- ══ DELTAS ═════════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-exchange-alt mr-1"></i> Delta Values (Round 2 − Round 1)</p>
        <div class="row">
          <div class="col-md-2 form-group"><label>Delta HB</label><input type="number" step="0.001" name="delta_hb" class="form-control" value="<?= e($record["delta_hb"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Delta TLC</label><input type="number" step="0.001" name="delta_tlc" class="form-control" value="<?= e($record["delta_tlc"]) ?>"></div>
          <div class="col-md-3 form-group"><label>Delta Platelets</label><input type="number" step="0.001" name="delta_plat" class="form-control" value="<?= e($record["delta_plat"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Delta Urea</label><input type="number" step="0.001" name="delta_uria" class="form-control" value="<?= e($record["delta_uria"]) ?>"></div>
          <div class="col-md-3 form-group"><label>Delta Creatinine</label><input type="number" step="0.001" name="delta_creatinine" class="form-control" value="<?= e($record["delta_creatinine"]) ?>"></div>
        </div>

        <hr>

        <!-- ══ LIFESTYLE ══════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-running mr-1"></i> Lifestyle</p>
        <div class="row">
          <div class="col-md-3 form-group">
            <label>Smoking Status</label>
            <select class="form-control" name="smoking_status">
              <option value="">Select</option>
              <?php foreach ($smokingOptions as $opt): ?>
                <option value="<?= e($opt) ?>" <?= sel($record["smoking_status"], $opt) ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Physical Activity Level</label>
            <select class="form-control" name="physical_activity_level">
              <option value="">Select</option>
              <?php foreach ($activityOptions as $opt): ?>
                <option value="<?= e($opt) ?>" <?= sel($record["physical_activity_level"], $opt) ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Diet Quality</label>
            <select class="form-control" name="diet_quality">
              <option value="">Select</option>
              <?php foreach ($dietOptions as $opt): ?>
                <option value="<?= e($opt) ?>" <?= sel($record["diet_quality"], $opt) ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Sleep Hours</label>
            <input type="number" step="0.1" min="0" max="24" name="sleep_hours" class="form-control" value="<?= e($record["sleep_hours"]) ?>" placeholder="e.g. 7.5">
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 form-check ml-3 mt-2">
            <input class="form-check-input" type="checkbox" name="alcohol_consumption" value="1" id="alc" <?= chk($record["alcohol_consumption"]) ?>>
            <label class="form-check-label" for="alc">Alcohol Consumption</label>
          </div>
        </div>

        <hr>

        <!-- ══ RISK SCORES ════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-chart-line mr-1"></i> Risk Scores</p>
        <div class="row">
          <div class="col-md-2 form-group"><label>Stress Level</label><input type="number" step="0.01" name="stress_level" class="form-control" value="<?= e($record["stress_level"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Family History</label><input type="number" step="0.01" name="family_history" class="form-control" value="<?= e($record["family_history"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Medications Count</label><input type="number" name="medications_count" class="form-control" value="<?= e($record["medications_count"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Risk Score</label><input type="number" step="0.0001" name="risk_score" class="form-control" value="<?= e($record["risk_score"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Symptom Burden</label><input type="number" step="0.0001" name="symptom_burden" class="form-control" value="<?= e($record["symptom_burden"]) ?>"></div>
          <div class="col-md-2 form-group"><label>Seasonal Weight</label><input type="number" step="0.0001" name="seasonal_weight" class="form-control" value="<?= e($record["seasonal_weight"]) ?>"></div>
        </div>

        <hr>

        <!-- ══ SYMPTOMS ═══════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-thermometer-half mr-1"></i> Symptoms</p>
        <div class="row">
          <div class="col-md-2 form-group"><label>Fever</label><input type="number" step="0.0001" name="fever" class="form-control" value="<?= e($record["fever"]) ?>" placeholder="0–1 score"></div>
          <div class="col-md-2 form-group"><label>Cough</label><input type="number" step="0.0001" name="cough" class="form-control" value="<?= e($record["cough"]) ?>" placeholder="0–1 score"></div>
          <div class="col-md-2 form-group"><label>Fatigue</label><input type="number" step="0.0001" name="fatigue" class="form-control" value="<?= e($record["fatigue"]) ?>" placeholder="0–1 score"></div>
          <div class="col-md-3 form-group"><label>Shortness of Breath</label><input type="number" step="0.0001" name="shortness_of_breath" class="form-control" value="<?= e($record["shortness_of_breath"]) ?>" placeholder="0–1 score"></div>
          <div class="col-md-3 form-check mt-4 ml-3">
            <input class="form-check-input" type="checkbox" name="chest_pain" value="1" id="cp" <?= chk($record["chest_pain"]) ?>>
            <label class="form-check-label" for="cp">Chest Pain</label>
          </div>
          <div class="col-md-3 form-check mt-1 ml-3">
            <input class="form-check-input" type="checkbox" name="headache" value="1" id="ha" <?= chk($record["headache"]) ?>>
            <label class="form-check-label" for="ha">Headache</label>
          </div>
        </div>

        <hr>

        <!-- ══ MEDICAL HISTORY FLAGS ══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-clipboard-check mr-1"></i> Medical History Flags</p>
        <div class="row">
          <div class="col-md-3 form-check ml-3">
            <input class="form-check-input" type="checkbox" name="has_diabetes" value="1" id="d1" <?= chk($record["has_diabetes"]) ?>>
            <label class="form-check-label" for="d1">Has Diabetes</label>
          </div>
          <div class="col-md-3 form-check">
            <input class="form-check-input" type="checkbox" name="has_hypertension" value="1" id="h1" <?= chk($record["has_hypertension"]) ?>>
            <label class="form-check-label" for="h1">Has Hypertension</label>
          </div>
          <div class="col-md-3 form-check">
            <input class="form-check-input" type="checkbox" name="has_kidney_disease" value="1" id="k1" <?= chk($record["has_kidney_disease"]) ?>>
            <label class="form-check-label" for="k1">Has Kidney Disease</label>
          </div>
          <div class="col-md-3 form-check">
            <input class="form-check-input" type="checkbox" name="has_heart_disease" value="1" id="c1" <?= chk($record["has_heart_disease"]) ?>>
            <label class="form-check-label" for="c1">Has Heart Disease</label>
          </div>
        </div>

        <hr>

        <!-- ══ DIAGNOSIS ══════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-stethoscope mr-1"></i> Diagnosis</p>
        <div class="row">
          <div class="col-md-6 form-group">
            <label>Diagnosis</label>
            <input type="text" name="diagnosis" class="form-control" value="<?= e($record["diagnosis"]) ?>" placeholder="e.g. CKD, Diabetes, Hypertension">
          </div>
          <div class="col-md-6 form-group">
            <label>Disease Category</label>
            <input type="text" name="disease_category" class="form-control" value="<?= e($record["disease_category"]) ?>" placeholder="e.g. Cardiovascular, Metabolic">
          </div>
        </div>

        <div class="mt-4">
          <button class="btn btn-primary btn-block" type="submit">
            <i class="fas fa-save mr-1"></i> Save Changes
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>
</body>
</html>
<?php
if ($conn instanceof mysqli) { $conn->close(); }
?>
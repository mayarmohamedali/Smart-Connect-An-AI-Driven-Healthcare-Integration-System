<?php
/**
 * Add Medical Record - OOP Version
 * Updated to match new database schema
 */

session_start();

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

$hospital_id = (int)$auth->getSessionData("hospital_id");
$patient_id  = (int)($_GET["patient_id"] ?? 0);

$success = "";
$error   = "";

if ($patient_id <= 0) {
    die("Invalid patient_id");
}

$patientObj = new Patient($conn);
if (!$patientObj->loadById($patient_id)) {
    die("Patient not found");
}

// Fetch patient + insurance (with contract check)
$stmt = $conn->prepare("
    SELECT
        p.patient_id,
        p.full_name,
        p.national_id,
        p.insurance_id,
        mi.name AS insurance_name
    FROM patients p
    JOIN insurance_hospitals ih
        ON ih.insurance_id = p.insurance_id
       AND ih.hospital_id  = ?
    LEFT JOIN medical_insurances mi
        ON mi.insurance_id = p.insurance_id
    WHERE p.patient_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $hospital_id, $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    die("This patient's insurance is not contracted with your hospital.");
}

$insuranceName = trim((string)($patient["insurance_name"] ?? ""));
$insuranceId   = (int)($patient["insurance_id"] ?? 0);

// ── Handle form submission ─────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $record = new MedicalRecord($conn);

    // Basic
    $record->setPatientId($patient_id);
    $record->setAge((int)Validator::nullIfEmpty($_POST["age"] ?? null));
    $record->setCheckinDate(Validator::nullIfEmpty($_POST["checkin_date"] ?? null));
    $record->setCheckoutDate(Validator::nullIfEmpty($_POST["checkout_date"] ?? null));

    // Lab values
    $record->setLabValues(
        Validator::nullIfEmpty($_POST["cbc_hb1"] ?? null),
        Validator::nullIfEmpty($_POST["cbc_tlc1"] ?? null),
        Validator::nullIfEmpty($_POST["cbc_plat1"] ?? null),
        Validator::nullIfEmpty($_POST["blood_uria1"] ?? null),
        Validator::nullIfEmpty($_POST["blood_creatinine1"] ?? null),
        Validator::nullIfEmpty($_POST["cbc_hb2"] ?? null),
        Validator::nullIfEmpty($_POST["cbc_tlc2"] ?? null),
        Validator::nullIfEmpty($_POST["cbc_plat2"] ?? null),
        Validator::nullIfEmpty($_POST["blood_uria2"] ?? null),
        Validator::nullIfEmpty($_POST["blood_creatinine2"] ?? null)
    );

    // Vitals
    $record->setBMI(Validator::nullIfEmpty($_POST["bmi"] ?? null));
    $record->setGlucose(Validator::nullIfEmpty($_POST["glucose"] ?? null));
    $record->setCholesterolLevel(Validator::nullIfEmpty($_POST["cholesterol_level"] ?? null));
    $record->setSystolicBP(Validator::nullIfEmpty($_POST["systolic_bp"] ?? null));

    // Aggregates (now includes year, day_of_week, avg_length_stay)
    $record->setAggregates(
        Validator::nullIfEmpty($_POST["month"] ?? null),
        Validator::nullIfEmpty($_POST["year"] ?? null),
        Validator::nullIfEmpty($_POST["day_of_week"] ?? null),
        Validator::nullIfEmpty($_POST["admission_count"] ?? null),
        Validator::nullIfEmpty($_POST["avg_creatinine"] ?? null),
        Validator::nullIfEmpty($_POST["avg_urea"] ?? null),
        Validator::nullIfEmpty($_POST["avg_hb"] ?? null),
        Validator::nullIfEmpty($_POST["avg_tlc"] ?? null),
        Validator::nullIfEmpty($_POST["avg_platelets"] ?? null),
        Validator::nullIfEmpty($_POST["avg_length_stay"] ?? null)
    );

    // Deltas
    $record->setDeltas(
        Validator::nullIfEmpty($_POST["delta_hb"] ?? null),
        Validator::nullIfEmpty($_POST["delta_tlc"] ?? null),
        Validator::nullIfEmpty($_POST["delta_plat"] ?? null),
        Validator::nullIfEmpty($_POST["delta_uria"] ?? null),
        Validator::nullIfEmpty($_POST["delta_creatinine"] ?? null)
    );

    // Lifestyle (now includes diet_quality, alcohol_consumption, sleep_hours)
    $record->setLifestyle(
        Validator::nullIfEmpty($_POST["length_of_stay"] ?? null),
        Validator::nullIfEmpty($_POST["smoking_status"] ?? null),
        Validator::nullIfEmpty($_POST["physical_activity_level"] ?? null),
        Validator::nullIfEmpty($_POST["diet_quality"] ?? null),
        Validator::boolToInt($_POST["alcohol_consumption"] ?? 0),
        Validator::nullIfEmpty($_POST["sleep_hours"] ?? null)
    );

    // Risk flags
    $record->setRiskFlags(
        Validator::boolToInt($_POST["has_diabetes"] ?? 0),
        Validator::boolToInt($_POST["has_hypertension"] ?? 0),
        Validator::boolToInt($_POST["has_kidney_disease"] ?? 0),
        Validator::boolToInt($_POST["has_heart_disease"] ?? 0)
    );

    // Risk scores (computed / optional — staff can leave blank)
    $record->setRiskScores(
        Validator::nullIfEmpty($_POST["stress_level"] ?? null),
        Validator::nullIfEmpty($_POST["family_history"] ?? null),
        Validator::nullIfEmpty($_POST["medications_count"] ?? null),
        Validator::nullIfEmpty($_POST["risk_score"] ?? null),
        Validator::nullIfEmpty($_POST["symptom_burden"] ?? null),
        Validator::nullIfEmpty($_POST["seasonal_weight"] ?? null)
    );

    // Symptoms
    $record->setSymptoms(
        Validator::nullIfEmpty($_POST["fever"] ?? null),
        Validator::nullIfEmpty($_POST["cough"] ?? null),
        Validator::nullIfEmpty($_POST["fatigue"] ?? null),
        isset($_POST["chest_pain"]) ? 1 : null,
        Validator::nullIfEmpty($_POST["shortness_of_breath"] ?? null),
        isset($_POST["headache"]) ? 1 : null
    );

    // Diagnosis
    $record->setDiagnosis(Validator::nullIfEmpty($_POST["diagnosis"] ?? null));
    $record->setDiseaseCategory(Validator::nullIfEmpty($_POST["disease_category"] ?? null));

    if ($record->create()) {
        $success = "Medical record added successfully ✅";
    } else {
        $error = "Failed to create medical record. Please check all fields and try again.";
    }
}

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Medical Record</title>
  <link href="css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    .ins-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 4px 10px;
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,.5);
      background: rgba(255,255,255,.18);
      color: #fff;
      font-weight: 700;
      font-size: .85rem;
    }
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
  <a href="HospitalDashboard.php#patients" class="btn btn-outline-secondary mb-3">
    <i class="fas fa-arrow-left mr-1"></i> Back
  </a>

  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">
        <i class="fas fa-notes-medical mr-2"></i>
        Add Medical Record — <?= e($patient["full_name"]) ?>
      </h5>
      <small>
        National ID: <?= e($patient["national_id"]) ?>
        <?php if ($insuranceId > 0): ?>
          <span class="ins-badge ml-2">
            <i class="fas fa-shield-alt"></i>
            <?= e($insuranceName !== "" ? $insuranceName : ("Insurance #" . $insuranceId)) ?>
          </span>
        <?php else: ?>
          <span class="badge badge-warning ml-2">No Insurance</span>
        <?php endif; ?>
      </small>
    </div>

    <div class="card-body">

      <?php if ($success): ?>
        <div class="alert alert-success">
          <?= e($success) ?><br>
          <small>Redirecting to dashboard...</small>
        </div>
        <script>
          setTimeout(function () {
            window.location.href = "HospitalDashboard.php#patients";
          }, 2000);
        </script>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST">

        <!-- ══ ADMISSION INFO ══════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-calendar-alt mr-1"></i> Admission Info</p>
        <div class="row">
          <div class="col-md-3 form-group">
            <label>Age</label>
            <input type="number" name="age" min="0" max="150" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-in Date</label>
            <input type="date" name="checkin_date" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-out Date</label>
            <input type="date" name="checkout_date" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Length of Stay (days)</label>
            <input type="number" name="length_of_stay" min="0" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Month (1–12)</label>
            <input type="number" name="month" min="1" max="12" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Year</label>
            <input type="number" name="year" min="2000" max="2100" class="form-control" placeholder="e.g. 2026">
          </div>
          <div class="col-md-3 form-group">
            <label>Day of Week</label>
            <select name="day_of_week" class="form-control">
              <option value="">Select</option>
              <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                <option><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 form-group">
            <label>Admission Count</label>
            <input type="number" name="admission_count" min="0" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Avg Length of Stay</label>
            <input type="number" step="0.000001" name="avg_length_stay" class="form-control">
          </div>
        </div>

        <hr>

        <!-- ══ LAB RESULTS ════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-vial mr-1"></i> Lab Results — Round 1</p>
        <div class="row">
          <div class="col-md-3 form-group"><label>CBC-HB1</label><input name="cbc_hb1" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>CBC-TLC1</label><input name="cbc_tlc1" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>CBC-PLAT1</label><input name="cbc_plat1" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>Blood Urea1</label><input name="blood_uria1" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>Blood Creatinine1</label><input name="blood_creatinine1" class="form-control" step="0.01"></div>
        </div>

        <p class="section-title mt-2"><i class="fas fa-vial mr-1"></i> Lab Results — Round 2</p>
        <div class="row">
          <div class="col-md-3 form-group"><label>CBC-HB2</label><input name="cbc_hb2" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>CBC-TLC2</label><input name="cbc_tlc2" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>CBC-PLAT2</label><input name="cbc_plat2" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>Blood Urea2</label><input name="blood_uria2" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>Blood Creatinine2</label><input name="blood_creatinine2" class="form-control" step="0.01"></div>
        </div>

        <hr>

        <!-- ══ VITALS ═════════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-heartbeat mr-1"></i> Vitals</p>
        <div class="row">
          <div class="col-md-3 form-group"><label>BMI</label><input name="bmi" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>Glucose</label><input name="glucose" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>Cholesterol Level</label><input name="cholesterol_level" class="form-control" step="0.01"></div>
          <div class="col-md-3 form-group"><label>Systolic BP</label><input name="systolic_bp" type="number" class="form-control"></div>
        </div>

        <hr>

        <!-- ══ AVERAGES ═══════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-calculator mr-1"></i> Averages</p>
        <div class="row">
          <div class="col-md-2 form-group"><label>Avg Creatinine</label><input name="avg_creatinine" class="form-control" step="0.01"></div>
          <div class="col-md-2 form-group"><label>Avg Urea</label><input name="avg_urea" class="form-control" step="0.01"></div>
          <div class="col-md-2 form-group"><label>Avg HB</label><input name="avg_hb" class="form-control" step="0.01"></div>
          <div class="col-md-2 form-group"><label>Avg TLC</label><input name="avg_tlc" class="form-control" step="0.01"></div>
          <div class="col-md-4 form-group"><label>Avg Platelets</label><input name="avg_platelets" class="form-control" step="0.01"></div>
        </div>

        <hr>

        <!-- ══ DELTAS ═════════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-exchange-alt mr-1"></i> Delta Values (Round 2 − Round 1)</p>
        <div class="row">
          <div class="col-md-2 form-group"><label>Delta HB</label><input name="delta_hb" class="form-control" step="0.001"></div>
          <div class="col-md-2 form-group"><label>Delta TLC</label><input name="delta_tlc" class="form-control" step="0.001"></div>
          <div class="col-md-3 form-group"><label>Delta Platelets</label><input name="delta_plat" class="form-control" step="0.001"></div>
          <div class="col-md-2 form-group"><label>Delta Urea</label><input name="delta_uria" class="form-control" step="0.001"></div>
          <div class="col-md-3 form-group"><label>Delta Creatinine</label><input name="delta_creatinine" class="form-control" step="0.001"></div>
        </div>

        <hr>

        <!-- ══ LIFESTYLE ══════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-running mr-1"></i> Lifestyle</p>
        <div class="row">
          <div class="col-md-3 form-group">
            <label>Smoking Status</label>
            <select class="form-control" name="smoking_status">
              <option value="">Select</option>
              <option>Non-Smoker</option>
              <option>Smoker</option>
              <option>Former Smoker</option>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Physical Activity Level</label>
            <select class="form-control" name="physical_activity_level">
              <option value="">Select</option>
              <option>Low</option>
              <option>Moderate</option>
              <option>High</option>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Diet Quality</label>
            <select class="form-control" name="diet_quality">
              <option value="">Select</option>
              <option>Poor</option>
              <option>Average</option>
              <option>Good</option>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Sleep Hours</label>
            <input type="number" name="sleep_hours" step="0.1" min="0" max="24" class="form-control" placeholder="e.g. 7.5">
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 form-check ml-3 mt-2">
            <input class="form-check-input" type="checkbox" name="alcohol_consumption" value="1" id="alc">
            <label class="form-check-label" for="alc">Alcohol Consumption</label>
          </div>
        </div>

        <hr>

        <!-- ══ SYMPTOMS ═══════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-thermometer-half mr-1"></i> Symptoms</p>
        <div class="row">
          <div class="col-md-2 form-group"><label>Fever</label><input name="fever" step="0.0001" class="form-control" placeholder="0–1 score"></div>
          <div class="col-md-2 form-group"><label>Cough</label><input name="cough" step="0.0001" class="form-control" placeholder="0–1 score"></div>
          <div class="col-md-2 form-group"><label>Fatigue</label><input name="fatigue" step="0.0001" class="form-control" placeholder="0–1 score"></div>
          <div class="col-md-3 form-group"><label>Shortness of Breath</label><input name="shortness_of_breath" step="0.0001" class="form-control" placeholder="0–1 score"></div>
          <div class="col-md-3 form-check mt-4 ml-3">
            <input class="form-check-input" type="checkbox" name="chest_pain" value="1" id="cp">
            <label class="form-check-label" for="cp">Chest Pain</label>
          </div>
          <div class="col-md-3 form-check mt-1 ml-3">
            <input class="form-check-input" type="checkbox" name="headache" value="1" id="ha">
            <label class="form-check-label" for="ha">Headache</label>
          </div>
        </div>

        <hr>

        <!-- ══ MEDICAL HISTORY FLAGS ══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-clipboard-check mr-1"></i> Medical History Flags</p>
        <div class="row">
          <div class="col-md-3 form-check ml-3">
            <input class="form-check-input" type="checkbox" name="has_diabetes" value="1" id="d1">
            <label class="form-check-label" for="d1">Has Diabetes</label>
          </div>
          <div class="col-md-3 form-check">
            <input class="form-check-input" type="checkbox" name="has_hypertension" value="1" id="h1">
            <label class="form-check-label" for="h1">Has Hypertension</label>
          </div>
          <div class="col-md-3 form-check">
            <input class="form-check-input" type="checkbox" name="has_kidney_disease" value="1" id="k1">
            <label class="form-check-label" for="k1">Has Kidney Disease</label>
          </div>
          <div class="col-md-3 form-check">
            <input class="form-check-input" type="checkbox" name="has_heart_disease" value="1" id="c1">
            <label class="form-check-label" for="c1">Has Heart Disease</label>
          </div>
        </div>

        <hr>

        <!-- ══ DIAGNOSIS ══════════════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-stethoscope mr-1"></i> Diagnosis</p>
        <div class="row">
          <div class="col-md-6 form-group">
            <label>Diagnosis</label>
            <input name="diagnosis" class="form-control" placeholder="e.g. CKD, Diabetes, Hypertension">
          </div>
          <div class="col-md-6 form-group">
            <label>Disease Category</label>
            <input name="disease_category" class="form-control" placeholder="e.g. Cardiovascular, Metabolic">
          </div>
        </div>

        <div class="mt-4">
          <button class="btn btn-primary btn-block" type="submit">
            <i class="fas fa-save mr-1"></i> Save Medical Record
          </button>
        </div>

      </form>
    </div><!-- /.card-body -->
  </div><!-- /.card -->
</div><!-- /.container -->

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
if ($conn instanceof mysqli) { $conn->close(); }
?>
<?php
/**
 * AddMedicalRecord.php (Hospital Staff)
 * ✅ Averages and deltas are AUTO-CALCULATED from lab values — not entered manually
 * ✅ smoking_status stored as TINYINT boolean (1 = Smoker, 0 = Non-Smoker)
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Patient.php';
require_once __DIR__ . '/MedicalRecord.php';
require_once __DIR__ . '/Validator.php';

$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

$auth->checkStaffAuth("HOSPITAL_STAFF");

$hospital_id = (int)$auth->getSessionData("hospital_id");
$patient_id  = (int)($_GET["patient_id"] ?? 0);

$success = "";
$error   = "";

if ($patient_id <= 0) die("Invalid patient_id");

$patientObj = new Patient($conn);
if (!$patientObj->loadById($patient_id)) die("Patient not found");

// Fetch patient + insurance (with contract check)
$stmt = $conn->prepare("
    SELECT p.patient_id, p.full_name, p.national_id,
           p.insurance_id, mi.name AS insurance_name
    FROM patients p
    JOIN insurance_hospitals ih
        ON ih.insurance_id = p.insurance_id AND ih.hospital_id = ?
    LEFT JOIN medical_insurances mi ON mi.insurance_id = p.insurance_id
    WHERE p.patient_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $hospital_id, $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) die("This patient's insurance is not contracted with your hospital.");

$insuranceName = trim((string)($patient["insurance_name"] ?? ""));
$insuranceId   = (int)($patient["insurance_id"] ?? 0);

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $record = new MedicalRecord($conn);

    $record->setPatientId($patient_id);
    $record->setAge((int)Validator::nullIfEmpty($_POST["age"] ?? null));
    $record->setCheckinDate(Validator::nullIfEmpty($_POST["checkin_date"]  ?? null));
    $record->setCheckoutDate(Validator::nullIfEmpty($_POST["checkout_date"] ?? null));

    // Lab values — averages & deltas are AUTO-CALCULATED inside setLabValues()
    $record->setLabValues(
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
    $record->setBMI(Validator::nullIfEmpty($_POST["bmi"]                            ?? null));
    $record->setGlucose(Validator::nullIfEmpty($_POST["glucose"]                    ?? null));
    $record->setCholesterolLevel(Validator::nullIfEmpty($_POST["cholesterol_level"] ?? null));
    $record->setSystolicBP(Validator::nullIfEmpty($_POST["systolic_bp"]             ?? null));

    // Admission metadata (no avg_* or delta_* — those are calculated automatically)
    $record->setAggregates(
        Validator::nullIfEmpty($_POST["month"]           ?? null),
        Validator::nullIfEmpty($_POST["year"]            ?? null),
        Validator::nullIfEmpty($_POST["day_of_week"]     ?? null),
        Validator::nullIfEmpty($_POST["admission_count"] ?? null),
        Validator::nullIfEmpty($_POST["avg_length_stay"] ?? null)
    );

    // Lifestyle — smoking stored as boolean int (1/0/null), NOT via nullIfEmpty
    $smokingRaw = $_POST["smoking_status"] ?? "";
    $smokingVal = ($smokingRaw === "") ? null : (int)$smokingRaw;

    $record->setLifestyle(
        Validator::nullIfEmpty($_POST["length_of_stay"]          ?? null),
        $smokingVal,
        Validator::nullIfEmpty($_POST["physical_activity_level"] ?? null),
        Validator::nullIfEmpty($_POST["diet_quality"]            ?? null),
        Validator::boolToInt($_POST["alcohol_consumption"]       ?? 0),
        Validator::nullIfEmpty($_POST["sleep_hours"]             ?? null)
    );

    // Risk flags
    $record->setRiskFlags(
        Validator::boolToInt($_POST["has_diabetes"]       ?? 0),
        Validator::boolToInt($_POST["has_hypertension"]   ?? 0),
        Validator::boolToInt($_POST["has_kidney_disease"] ?? 0),
        Validator::boolToInt($_POST["has_heart_disease"]  ?? 0)
    );

    // Risk scores
    $record->setRiskScores(
        Validator::nullIfEmpty($_POST["stress_level"]      ?? null),
        Validator::nullIfEmpty($_POST["family_history"]    ?? null),
        Validator::nullIfEmpty($_POST["medications_count"] ?? null),
        Validator::nullIfEmpty($_POST["risk_score"]        ?? null),
        Validator::nullIfEmpty($_POST["symptom_burden"]    ?? null),
        Validator::nullIfEmpty($_POST["seasonal_weight"]   ?? null)
    );

    // Symptoms
    $record->setSymptoms(
        Validator::nullIfEmpty($_POST["fever"]               ?? null),
        Validator::nullIfEmpty($_POST["cough"]               ?? null),
        Validator::nullIfEmpty($_POST["fatigue"]             ?? null),
        isset($_POST["chest_pain"]) ? 1 : null,
        Validator::nullIfEmpty($_POST["shortness_of_breath"] ?? null),
        isset($_POST["headache"])   ? 1 : null
    );

    // Diagnosis
    $record->setDiagnosis(Validator::nullIfEmpty($_POST["diagnosis"]               ?? null));
    $record->setDiseaseCategory(Validator::nullIfEmpty($_POST["disease_category"]  ?? null));

    if ($record->create()) {
        $success = "Medical record added successfully ✅";
    } else {
        $error = "Failed to create medical record. Please check all fields and try again.";
    }
}

function e($v): string {
    return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Medical Record</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    .ins-badge {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 4px 10px; border-radius: 20px;
      border: 1px solid rgba(255,255,255,.5);
      background: rgba(255,255,255,.18);
      color: #fff; font-weight: 700; font-size: .85rem;
    }
    .section-title {
      font-size: .85rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .05em;
      color: #4e73df; margin-bottom: .75rem;
    }
    .calc-preview {
      background: #f0f4ff;
      border: 1px dashed #4e73df;
      border-radius: 8px;
      padding: 12px 16px;
      font-size: 13px;
    }
    .calc-preview .calc-row { display: flex; flex-wrap: wrap; gap: 16px; }
    .calc-item { min-width: 120px; }
    .calc-item .ci-label { color: #888; font-size: 11px; text-transform: uppercase; }
    .calc-item .ci-value { font-weight: 700; font-size: 15px; color: #2e2e3a; }
    .ci-pos { color: #1cc88a !important; }
    .ci-neg { color: #e74a3b !important; }
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
            <?= e($insuranceName !== "" ? $insuranceName : ("Insurance #".$insuranceId)) ?>
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
          setTimeout(function () { window.location.href = "HospitalDashboard.php#patients"; }, 2000);
        </script>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" id="recordForm">

        <!-- ══ 1. ADMISSION INFO ════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-calendar-alt mr-1"></i> 1. Admission Info</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>Age</label>
            <input type="number" name="age" min="0" max="150" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-in Date</label>
            <input type="date" name="checkin_date" class="form-control" id="checkin_date">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-out Date</label>
            <input type="date" name="checkout_date" class="form-control" id="checkout_date">
          </div>
          <div class="col-md-2 form-group">
            <label>Length of Stay <small class="text-muted">(days)</small></label>
            <input type="number" name="length_of_stay" min="0" class="form-control" id="length_of_stay" readonly
                   style="background:#f0f4ff;" title="Auto-calculated from dates">
          </div>
          <div class="col-md-2 form-group">
            <label>Avg Length of Stay</label>
            <input type="number" step="0.01" name="avg_length_stay" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Month <small class="text-muted">(1–12)</small></label>
            <input type="number" name="month" min="1" max="12" class="form-control" id="month" readonly
                   style="background:#f0f4ff;" title="Auto-filled from check-in date">
          </div>
          <div class="col-md-2 form-group">
            <label>Year</label>
            <input type="number" name="year" class="form-control" id="year" readonly
                   style="background:#f0f4ff;" title="Auto-filled from check-in date">
          </div>
          <div class="col-md-3 form-group">
            <label>Day of Week</label>
            <input type="text" name="day_of_week" class="form-control" id="day_of_week" readonly
                   style="background:#f0f4ff;" title="Auto-filled from check-in date">
          </div>
          <div class="col-md-2 form-group">
            <label>Total Admission Count</label>
            <input type="number" name="admission_count" min="0" class="form-control">
          </div>
        </div>

        <hr>

        <!-- ══ 2. LAB RESULTS ══════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-vial mr-1"></i> 2. Lab Results — Round 1</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>CBC-HB1</label>
            <input type="number" step="0.01" name="cbc_hb1" id="cbc_hb1" class="form-control lab-input">
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC1</label>
            <input type="number" step="0.01" name="cbc_tlc1" id="cbc_tlc1" class="form-control lab-input">
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT1</label>
            <input type="number" step="0.01" name="cbc_plat1" id="cbc_plat1" class="form-control lab-input">
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 1</label>
            <input type="number" step="0.01" name="blood_uria1" id="blood_uria1" class="form-control lab-input">
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 1</label>
            <input type="number" step="0.01" name="blood_creatinine1" id="blood_creatinine1" class="form-control lab-input">
          </div>
        </div>

        <p class="section-title mt-1"><i class="fas fa-vial mr-1"></i> Lab Results — Round 2</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>CBC-HB2</label>
            <input type="number" step="0.01" name="cbc_hb2" id="cbc_hb2" class="form-control lab-input">
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC2</label>
            <input type="number" step="0.01" name="cbc_tlc2" id="cbc_tlc2" class="form-control lab-input">
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT2</label>
            <input type="number" step="0.01" name="cbc_plat2" id="cbc_plat2" class="form-control lab-input">
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 2</label>
            <input type="number" step="0.01" name="blood_uria2" id="blood_uria2" class="form-control lab-input">
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 2</label>
            <input type="number" step="0.01" name="blood_creatinine2" id="blood_creatinine2" class="form-control lab-input">
          </div>
        </div>

        <!-- Live preview of auto-calculated values -->
        <div class="calc-preview mb-3" id="calcPreview" style="display:none;">
          <div class="mb-2 font-weight-bold text-primary" style="font-size:12px;">
            <i class="fas fa-magic mr-1"></i> AUTO-CALCULATED FROM LAB VALUES
          </div>
          <div class="calc-row">
            <div class="calc-item"><div class="ci-label">Avg HB</div><div class="ci-value" id="prev_avg_hb">—</div></div>
            <div class="calc-item"><div class="ci-label">Avg TLC</div><div class="ci-value" id="prev_avg_tlc">—</div></div>
            <div class="calc-item"><div class="ci-label">Avg Platelets</div><div class="ci-value" id="prev_avg_plat">—</div></div>
            <div class="calc-item"><div class="ci-label">Avg Urea</div><div class="ci-value" id="prev_avg_urea">—</div></div>
            <div class="calc-item"><div class="ci-label">Avg Creatinine</div><div class="ci-value" id="prev_avg_crn">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ HB</div><div class="ci-value" id="prev_d_hb">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ TLC</div><div class="ci-value" id="prev_d_tlc">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ Platelets</div><div class="ci-value" id="prev_d_plat">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ Urea</div><div class="ci-value" id="prev_d_urea">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ Creatinine</div><div class="ci-value" id="prev_d_crn">—</div></div>
          </div>
        </div>

        <hr>

        <!-- ══ 3. VITALS ════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-heartbeat mr-1"></i> 3. Vitals</p>
        <div class="row">
          <div class="col-md-3 form-group">
            <label>BMI</label>
            <input type="number" step="0.01" name="bmi" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Glucose <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="glucose" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Cholesterol <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="cholesterol_level" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Pressure <small class="text-muted">(mmHg)</small></label>
            <div class="input-group">
              <input type="number" name="systolic_bp" class="form-control" min="0">
              <div class="input-group-prepend input-group-append">
                
              </div>
              
            </div>
          
          </div>
        </div>

        <hr>

        <!-- ══ 4. LIFESTYLE ══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-running mr-1"></i> 4. Lifestyle</p>
        <div class="row">
          <div class="col-md-3 form-group">
            <label>Smoking Status</label>
            <select class="form-control" name="smoking_status">
              <option value="">— Select —</option>
              <option value="1">Smoker</option>
              <option value="0">Non-Smoker</option>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Physical Activity Level</label>
            <select class="form-control" name="physical_activity_level">
              <option value="">— Select —</option>
              <option>Low</option>
              <option>Moderate</option>
              <option>High</option>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Diet Quality</label>
            <select class="form-control" name="diet_quality">
              <option value="">— Select —</option>
              <option>Poor</option>
              <option>Average</option>
              <option>Good</option>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Sleep Hours</label>
            <input type="number" step="0.1" min="0" max="24" name="sleep_hours"
                   class="form-control" placeholder="e.g. 7.5">
          </div>
          <div class="col-md-3 form-group">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="alcohol_consumption" value="1" id="alc">
              <label class="form-check-label" for="alc">Alcohol Consumption</label>
            </div>
          </div>
        </div>

        <hr>

        <!-- ══ 5. RISK SCORES ════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-chart-line mr-1"></i> 5. Risk Scores</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>Stress Level</label>
            <input type="number" step="0.01" name="stress_level" class="form-control" placeholder="0–10">
          </div>
          <div class="col-md-2 form-group">
            <label>Family History</label>
            <input type="number" step="0.0001" name="family_history" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Medications Count</label>
            <input type="number" step="0.01" name="medications_count" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Risk Score</label>
            <input type="number" step="0.000001" name="risk_score" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Symptom Burden</label>
            <input type="number" step="0.000001" name="symptom_burden" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Seasonal Weight</label>
            <input type="number" step="0.000001" name="seasonal_weight" class="form-control">
          </div>
        </div>

        <hr>

        <!-- ══ 6. SYMPTOMS ═══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-thermometer-half mr-1"></i> 6. Symptoms</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>Fever</label>
            <input type="number" step="0.0001" name="fever" class="form-control" placeholder="0–1 score">
          </div>
          <div class="col-md-2 form-group">
            <label>Cough</label>
            <input type="number" step="0.0001" name="cough" class="form-control" placeholder="0–1 score">
          </div>
          <div class="col-md-2 form-group">
            <label>Fatigue</label>
            <input type="number" step="0.0001" name="fatigue" class="form-control" placeholder="0–1 score">
          </div>
          <div class="col-md-3 form-group">
            <label>Shortness of Breath</label>
            <input type="number" step="0.0001" name="shortness_of_breath" class="form-control" placeholder="0–1 score">
          </div>
          <div class="col-md-3 form-group">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" name="chest_pain" value="1" id="cp">
              <label class="form-check-label" for="cp">Chest Pain</label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="headache" value="1" id="ha">
              <label class="form-check-label" for="ha">Headache</label>
            </div>
          </div>
        </div>

        <hr>

        <!-- ══ 7. MEDICAL HISTORY FLAGS ═════════════════════════════ -->
        <p class="section-title"><i class="fas fa-clipboard-check mr-1"></i> 7. Medical History Flags</p>
        <div class="row">
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_diabetes" value="1" id="d1">
              <label class="form-check-label" for="d1">Has Diabetes</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_hypertension" value="1" id="h1">
              <label class="form-check-label" for="h1">Has Hypertension</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_kidney_disease" value="1" id="k1">
              <label class="form-check-label" for="k1">Has Kidney Disease</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_heart_disease" value="1" id="c1">
              <label class="form-check-label" for="c1">Has Heart Disease</label>
            </div>
          </div>
        </div>

        <hr>

        <!-- ══ 8. DIAGNOSIS ══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-stethoscope mr-1"></i> 8. Diagnosis</p>
        <div class="row">
          <div class="col-md-6 form-group">
            <label>Primary Diagnosis</label>
            <input type="text" name="diagnosis" class="form-control"
                   placeholder="e.g. Diabetes, Hypertension, CKD">
          </div>
          <div class="col-md-6 form-group">
            <label>Disease Category</label>
            <select name="disease_category" class="form-control">
              <option value="">— Select Category —</option>
              <option value="Cardiovascular">Cardiovascular</option>
              <option value="Oncological">Oncological</option>
              <option value="Metabolic">Metabolic</option>
              <option value="Respiratory">Respiratory</option>
              <option value="Renal">Renal</option>
              <option value="Neurological">Neurological</option>
              <option value="Healthy">Healthy</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>

        <div class="mt-4">
          <button class="btn btn-primary btn-block btn-lg" type="submit">
            <i class="fas fa-save mr-1"></i> Save Medical Record
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script>
// ── Auto-fill date-derived fields from check-in date ──────────────────────────
const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

document.getElementById('checkin_date').addEventListener('change', function () {
  const d = new Date(this.value);
  if (isNaN(d)) return;
  document.getElementById('month').value       = d.getMonth() + 1;
  document.getElementById('year').value        = d.getFullYear();
  document.getElementById('day_of_week').value = days[d.getDay()];
  updateLOS();
});

document.getElementById('checkout_date').addEventListener('change', updateLOS);

function updateLOS() {
  const ci = document.getElementById('checkin_date').value;
  const co = document.getElementById('checkout_date').value;
  if (!ci || !co) return;
  const diff = Math.round((new Date(co) - new Date(ci)) / 86400000);
  if (diff >= 0) document.getElementById('length_of_stay').value = diff;
}

// ── Live preview of auto-calculated averages & deltas ─────────────────────────
const labIds = ['cbc_hb1','cbc_hb2','cbc_tlc1','cbc_tlc2',
                'cbc_plat1','cbc_plat2','blood_uria1','blood_uria2',
                'blood_creatinine1','blood_creatinine2'];

labIds.forEach(id => {
  document.getElementById(id).addEventListener('input', updateCalcPreview);
});

function v(id) {
  const val = parseFloat(document.getElementById(id).value);
  return isNaN(val) ? null : val;
}

function fmt(val, el) {
  if (val === null) { el.textContent = '—'; el.className = 'ci-value'; return; }
  el.textContent = val.toFixed(3);
  el.className   = 'ci-value ' + (val > 0 ? 'ci-pos' : val < 0 ? 'ci-neg' : '');
}

function fmtAvg(val, el) {
  if (val === null) { el.textContent = '—'; el.className = 'ci-value'; return; }
  el.textContent = val.toFixed(3);
  el.className   = 'ci-value';
}

function calcAvg(a, b)   { return (a !== null && b !== null) ? Math.round((a+b)/2*1000)/1000 : null; }
function calcDelta(a, b) { return (a !== null && b !== null) ? Math.round((b-a)*1000)/1000   : null; }

function updateCalcPreview() {
  const hb1  = v('cbc_hb1'),           hb2  = v('cbc_hb2');
  const tlc1 = v('cbc_tlc1'),          tlc2 = v('cbc_tlc2');
  const pl1  = v('cbc_plat1'),         pl2  = v('cbc_plat2');
  const ur1  = v('blood_uria1'),       ur2  = v('blood_uria2');
  const cr1  = v('blood_creatinine1'), cr2  = v('blood_creatinine2');

  const anyFilled = [hb1,hb2,tlc1,tlc2,pl1,pl2,ur1,ur2,cr1,cr2].some(x => x !== null);
  document.getElementById('calcPreview').style.display = anyFilled ? '' : 'none';

  fmtAvg(calcAvg(hb1,  hb2),  document.getElementById('prev_avg_hb'));
  fmtAvg(calcAvg(tlc1, tlc2), document.getElementById('prev_avg_tlc'));
  fmtAvg(calcAvg(pl1,  pl2),  document.getElementById('prev_avg_plat'));
  fmtAvg(calcAvg(ur1,  ur2),  document.getElementById('prev_avg_urea'));
  fmtAvg(calcAvg(cr1,  cr2),  document.getElementById('prev_avg_crn'));

  fmt(calcDelta(hb1,  hb2),  document.getElementById('prev_d_hb'));
  fmt(calcDelta(tlc1, tlc2), document.getElementById('prev_d_tlc'));
  fmt(calcDelta(pl1,  pl2),  document.getElementById('prev_d_plat'));
  fmt(calcDelta(ur1,  ur2),  document.getElementById('prev_d_urea'));
  fmt(calcDelta(cr1,  cr2),  document.getElementById('prev_d_crn'));
}
</script>

</body>
</html>
<?php if ($conn instanceof mysqli) { $conn->close(); } ?>
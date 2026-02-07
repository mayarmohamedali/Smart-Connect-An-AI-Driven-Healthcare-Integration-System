<?php
/**
 * Add Medical Record - OOP Version
 * Fully functional - saves to database
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Patient.php';
require_once __DIR__ . '/MedicalRecord.php';
require_once __DIR__ . '/Validator.php';

// Initialize
$db = new Database();
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

// Load patient using OOP (optional - kept as you had it)
$patientObj = new Patient($conn);
if (!$patientObj->loadById($patient_id)) {
  die("Patient not found");
}

/**
 * ✅ FIX: Fetch patient + insurance name (and contract check)
 * Hospital can add record only if insurance contracted
 */
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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // Create Medical Record object
  $record = new MedicalRecord($conn);

  // Set basic info
  $record->setPatientId($patient_id);
  $record->setAge((int)Validator::nullIfEmpty($_POST["age"]));
  $record->setCheckinDate(Validator::nullIfEmpty($_POST["checkin_date"]));
  $record->setCheckoutDate(Validator::nullIfEmpty($_POST["checkout_date"]));

  // Set lab values round 1 & 2
  $record->setLabValues(
    Validator::nullIfEmpty($_POST["cbc_hb1"]),
    Validator::nullIfEmpty($_POST["cbc_tlc1"]),
    Validator::nullIfEmpty($_POST["cbc_plat1"]),
    Validator::nullIfEmpty($_POST["blood_uria1"]),
    Validator::nullIfEmpty($_POST["blood_creatinine1"]),
    Validator::nullIfEmpty($_POST["cbc_hb2"]),
    Validator::nullIfEmpty($_POST["cbc_tlc2"]),
    Validator::nullIfEmpty($_POST["cbc_plat2"]),
    Validator::nullIfEmpty($_POST["blood_uria2"]),
    Validator::nullIfEmpty($_POST["blood_creatinine2"])
  );

  // Set vitals
  $record->setBMI(Validator::nullIfEmpty($_POST["bmi"]));
  $record->setGlucose(Validator::nullIfEmpty($_POST["glucose"]));
  $record->setSystolicBP(Validator::nullIfEmpty($_POST["systolic_bp"]));

  // Set aggregates
  $record->setAggregates(
    Validator::nullIfEmpty($_POST["month"]),
    Validator::nullIfEmpty($_POST["admission_count"]),
    Validator::nullIfEmpty($_POST["avg_creatinine"]),
    Validator::nullIfEmpty($_POST["avg_urea"]),
    Validator::nullIfEmpty($_POST["avg_hb"]),
    Validator::nullIfEmpty($_POST["avg_tlc"]),
    Validator::nullIfEmpty($_POST["avg_platelets"])
  );

  // Set lifestyle
  $record->setLifestyle(
    Validator::nullIfEmpty($_POST["length_of_stay"]),
    Validator::nullIfEmpty($_POST["smoking_status"]),
    Validator::nullIfEmpty($_POST["physical_activity_level"])
  );

  // Set risk flags
  $record->setRiskFlags(
    Validator::boolToInt($_POST["has_diabetes"] ?? 0),
    Validator::boolToInt($_POST["has_hypertension"] ?? 0),
    Validator::boolToInt($_POST["has_kidney_disease"] ?? 0),
    Validator::boolToInt($_POST["has_heart_disease"] ?? 0)
  );

  // Set diagnosis
  $record->setDiagnosis(Validator::nullIfEmpty($_POST["diagnosis"]));

  // Save to database
  if ($record->create()) {
    $success = "Medical record added successfully ✅";
  } else {
    $error = "Failed to create medical record";
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
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding: 4px 10px;
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,.5);
      background: rgba(255,255,255,.18);
      color: #fff;
      font-weight: 700;
      font-size: .85rem;
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
          setTimeout(function () {
            window.location.href = "HospitalDashboard.php#patients";
          }, 2000);
        </script>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST">

        <div class="row">
          

          <div class="col-md-3 form-group">
            <label>Check-in Date</label>
            <input type="date" name="checkin_date" class="form-control">
          </div>

          <div class="col-md-3 form-group">
            <label>Check-out Date</label>
            <input type="date" name="checkout_date" class="form-control">
          </div>

          <div class="col-md-3 form-group">
            <label>Month (1–12)</label>
            <input type="number" name="month" min="1" max="12" class="form-control">
          </div>
        </div>

        <hr>

        <h6 class="text-primary font-weight-bold"><i class="fas fa-vial mr-1"></i> Lab Results (Round 1)</h6>
        <div class="row">
          <div class="col-md-3 form-group"><label>CBC-HB1</label><input name="cbc_hb1" class="form-control"></div>
          <div class="col-md-3 form-group"><label>CBC-TLC1</label><input name="cbc_tlc1" class="form-control"></div>
          <div class="col-md-3 form-group"><label>CBC-PLAT1</label><input name="cbc_plat1" class="form-control"></div>
          <div class="col-md-3 form-group"><label>BLOOD-URIA1</label><input name="blood_uria1" class="form-control"></div>
          <div class="col-md-3 form-group"><label>BLOOD-Creatinine1</label><input name="blood_creatinine1" class="form-control"></div>
        </div>

        <h6 class="text-primary font-weight-bold mt-3"><i class="fas fa-vial mr-1"></i> Lab Results (Round 2)</h6>
        <div class="row">
          <div class="col-md-3 form-group"><label>CBC-HB2</label><input name="cbc_hb2" class="form-control"></div>
          <div class="col-md-3 form-group"><label>CBC-TLC2</label><input name="cbc_tlc2" class="form-control"></div>
          <div class="col-md-3 form-group"><label>CBC-PLAT2</label><input name="cbc_plat2" class="form-control"></div>
          <div class="col-md-3 form-group"><label>BLOOD-URIA2</label><input name="blood_uria2" class="form-control"></div>
          <div class="col-md-3 form-group"><label>BLOOD-Creatinine2</label><input name="blood_creatinine2" class="form-control"></div>
        </div>

        <hr>

        <h6 class="text-primary font-weight-bold"><i class="fas fa-heartbeat mr-1"></i> Vitals & Risk</h6>
        <div class="row">
          <div class="col-md-3 form-group"><label>BMI</label><input name="bmi" class="form-control"></div>
          <div class="col-md-3 form-group"><label>Glucose</label><input name="glucose" class="form-control"></div>
          <div class="col-md-3 form-group"><label>Systolic BP</label><input name="systolic_bp" class="form-control"></div>
          <div class="col-md-3 form-group"><label>Admission Count</label><input name="admission_count" class="form-control"></div>
        </div>

        <div class="row">
          <div class="col-md-3 form-group"><label>Avg Creatinine</label><input name="avg_creatinine" class="form-control"></div>
          <div class="col-md-3 form-group"><label>Avg Urea</label><input name="avg_urea" class="form-control"></div>
          <div class="col-md-2 form-group"><label>Avg HB</label><input name="avg_hb" class="form-control"></div>
          <div class="col-md-2 form-group"><label>Avg TLC</label><input name="avg_tlc" class="form-control"></div>
          <div class="col-md-2 form-group"><label>Avg Platelets</label><input name="avg_platelets" class="form-control"></div>
        </div>

        <div class="row">
          <div class="col-md-3 form-group"><label>Length of Stay</label><input name="length_of_stay" class="form-control"></div>

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
            <label>Diagnosis</label>
            <input name="diagnosis" class="form-control" placeholder="e.g. CKD, Diabetes, Hypertension">
          </div>
        </div>

        <hr>

        <h6 class="text-primary font-weight-bold"><i class="fas fa-clipboard-check mr-1"></i> Medical History Flags</h6>
        <div class="row">
          <div class="col-md-3 form-check">
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

        <div class="mt-4">
          <button class="btn btn-primary btn-block" type="submit">
            <i class="fas fa-save mr-1"></i> Save Medical Record
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
<?php
if ($conn instanceof mysqli) { $conn->close(); }
?>

<?php
/**
 * ViewMedicalRecords.php (Hospital Staff)
 * ✅ Full Data Display for New Schema
 */

session_start();

require_once __DIR__ . "/Database.php";
require_once __DIR__ . "/Auth.php";
require_once __DIR__ . "/Validator.php";

$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// auth check
$auth->checkStaffAuth("HOSPITAL_STAFF");

$hospital_id = (int)($auth->getSessionData("hospital_id") ?? 0);
$patient_id  = (int)($_GET["patient_id"] ?? 0);

if ($hospital_id <= 0 || $patient_id <= 0) {
  die("Missing hospital_id or patient_id");
}

function e($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

/* Fetch Patient Info */
$sql = "
  SELECT p.*, mi.name AS insurance_name
  FROM patients p
  LEFT JOIN medical_insurances mi ON p.insurance_id = mi.insurance_id
  JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id AND ih.hospital_id = ?
  WHERE p.patient_id = ?
  LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $hospital_id, $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
  die("Patient not found OR insurance not contracted with your hospital.");
}

/* DELETE Record */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete_record") {
  $rid = (int)($_POST["record_id"] ?? 0);
  if ($rid > 0) {
    $del = $conn->prepare("DELETE FROM medical_records WHERE record_id=? AND patient_id=?");
    $del->bind_param("ii", $rid, $patient_id);
    $del->execute();
    $del->close();
  }
  header("Location: ViewMedicalRecords.php?patient_id=$patient_id");
  exit;
}

/* LIST Records */
$records = [];
$stmt = $conn->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY record_id DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $records[] = $row;
$stmt->close();

/* Selected Record Details */
$selected_record = null;
if (isset($_GET["record_id"])) {
  $rid = (int)$_GET["record_id"];
  $stmt = $conn->prepare("SELECT * FROM medical_records WHERE record_id=? AND patient_id=? LIMIT 1");
  $stmt->bind_param("ii", $rid, $patient_id);
  $stmt->execute();
  $selected_record = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>View Medical Records</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    body { background:#f8f9fc; }
    .badge-soft { border:1px solid rgba(0,0,0,.08); }
    .label { font-weight:600; color:#4e73df; }
    .hr-thin { margin: 10px 0; border-top: 1px solid rgba(0,0,0,.05); }
  </style>
</head>
<body>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="HospitalDashboard.php#patients" class="btn btn-outline-secondary mr-2">
      <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
    <div>
        <span class="badge badge-info badge-soft p-2 mr-2">Patient: <?= e($patient["full_name"]) ?></span>
        <span class="badge badge-success badge-soft p-2">Insurance: <?= e($patient["insurance_name"] ?? "N/A") ?></span>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-medical mr-1"></i> Medical Records History</h6>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <th>Created At</th>
              <th>Diagnosis</th>
              <th>LOS</th>
              <th>Risk Flags</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $r): ?>
              <tr>
                <td>#<?= (int)$r["record_id"] ?></td>
                <td><?= e($r["created_at"]) ?></td>
                <td><?= e($r["diagnosis"]) ?></td>
                <td><?= e($r["length_of_stay"]) ?> Days</td>
                <td>
                  <?php if (!empty($r["has_diabetes"])): ?><span class="badge badge-warning">DM</span><?php endif; ?>
                  <?php if (!empty($r["has_hypertension"])): ?><span class="badge badge-danger">HTN</span><?php endif; ?>
                  <?php if (!empty($r["has_kidney_disease"])): ?><span class="badge badge-info">KD</span><?php endif; ?>
                  <?php if (!empty($r["has_heart_disease"])): ?><span class="badge badge-primary">HD</span><?php endif; ?>
                </td>
                <td style="white-space:nowrap;">
                  <a class="btn btn-sm btn-outline-primary" href="?patient_id=<?= $patient_id ?>&record_id=<?= $r["record_id"] ?>#details">
                    <i class="fas fa-eye"></i> View
                  </a>
                  <form method="POST" class="d-inline" onsubmit="return confirm('Delete this record?');">
                    <input type="hidden" name="action" value="delete_record">
                    <input type="hidden" name="record_id" value="<?= $r["record_id"] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if ($selected_record): ?>
  <div id="details" class="card shadow mt-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
      <h6 class="m-0 font-weight-bold"><i class="fas fa-id-card-alt mr-2"></i> Detailed Report: #<?= (int)$selected_record["record_id"] ?></h6>
      <a href="?patient_id=<?= $patient_id ?>" class="text-white"><i class="fas fa-times"></i></a>
    </div>
    <div class="card-body">
      
      <h6 class="text-primary font-weight-bold mt-2">1. Admission & Visit Info</h6>
      <div class="row">
        <div class="col-md-3 mb-2"><span class="label">Age:</span> <?= e($selected_record["age"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Check-in:</span> <?= e($selected_record["checkin_date"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Check-out:</span> <?= e($selected_record["checkout_date"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Length of Stay:</span> <?= e($selected_record["length_of_stay"]) ?> Days</div>
        <div class="col-md-3 mb-2"><span class="label">Admission Count:</span> <?= e($selected_record["admission_count"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Timeframe:</span> <?= e($selected_record["day_of_week"]) ?>, <?= e($selected_record["month"]) ?>/<?= e($selected_record["year"]) ?></div>
      </div>
      <hr class="hr-thin">

      <h6 class="text-primary font-weight-bold mt-3">2. Laboratory Analysis (Round 1 vs Round 2)</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="bg-light">
                <tr><th>Test</th><th>Round 1</th><th>Round 2</th><th>Average</th><th>Delta (Change)</th></tr>
            </thead>
            <tbody>
                <tr><td>Hemoglobin (HB)</td><td><?= e($selected_record["cbc_hb1"]) ?></td><td><?= e($selected_record["cbc_hb2"]) ?></td><td><?= e($selected_record["avg_hb"]) ?></td><td class="text-primary font-weight-bold"><?= e($selected_record["delta_hb"]) ?></td></tr>
                <tr><td>TLC</td><td><?= e($selected_record["cbc_tlc1"]) ?></td><td><?= e($selected_record["cbc_tlc2"]) ?></td><td><?= e($selected_record["avg_tlc"]) ?></td><td class="text-primary font-weight-bold"><?= e($selected_record["delta_tlc"]) ?></td></tr>
                <tr><td>Platelets</td><td><?= e($selected_record["cbc_plat1"]) ?></td><td><?= e($selected_record["cbc_plat2"]) ?></td><td><?= e($selected_record["avg_platelets"]) ?></td><td class="text-primary font-weight-bold"><?= e($selected_record["delta_plat"]) ?></td></tr>
                <tr><td>Blood Urea</td><td><?= e($selected_record["blood_uria1"]) ?></td><td><?= e($selected_record["blood_uria2"]) ?></td><td><?= e($selected_record["avg_urea"]) ?></td><td class="text-primary font-weight-bold"><?= e($selected_record["delta_uria"]) ?></td></tr>
                <tr><td>Creatinine</td><td><?= e($selected_record["blood_creatinine1"]) ?></td><td><?= e($selected_record["blood_creatinine2"]) ?></td><td><?= e($selected_record["avg_creatinine"]) ?></td><td class="text-primary font-weight-bold"><?= e($selected_record["delta_creatinine"]) ?></td></tr>
            </tbody>
        </table>
      </div>
      <hr class="hr-thin">

      <h6 class="text-primary font-weight-bold mt-3">3. Vitals & Lifestyle Metrics</h6>
      <div class="row">
        <div class="col-md-3 mb-2"><span class="label">BMI:</span> <?= e($selected_record["bmi"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Glucose:</span> <?= e($selected_record["glucose"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Systolic BP:</span> <?= e($selected_record["systolic_bp"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Cholesterol:</span> <?= e($selected_record["cholesterol_level"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Smoking:</span> <?= e($selected_record["smoking_status"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Activity:</span> <?= e($selected_record["physical_activity_level"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Sleep:</span> <?= e($selected_record["sleep_hours"]) ?> Hrs</div>
        <div class="col-md-3 mb-2"><span class="label">Stress:</span> <?= e($selected_record["stress_level"]) ?>/10</div>
        <div class="col-md-3 mb-2"><span class="label">Diet:</span> <?= e($selected_record["diet_quality"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Alcohol:</span> <?= $selected_record["alcohol_consumption"] ? "Yes" : "No" ?></div>
      </div>
      <hr class="hr-thin">

      <h6 class="text-primary font-weight-bold mt-3">4. Clinical Symptoms (Scores)</h6>
      <div class="row">
        <div class="col-md-2 mb-2"><span class="label">Fever:</span> <?= e($selected_record["fever"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Cough:</span> <?= e($selected_record["cough"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Fatigue:</span> <?= e($selected_record["fatigue"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Headache:</span> <?= e($selected_record["headache"]) ?></div>
        <div class="col-md-4 mb-2">
            <?php if($selected_record["chest_pain"]): ?><span class="badge badge-danger">Chest Pain Reported</span><?php endif; ?>
            <?php if($selected_record["shortness_of_breath"]): ?><span class="badge badge-warning">Shortness of Breath</span><?php endif; ?>
        </div>
      </div>
      <hr class="hr-thin">

      <h6 class="text-primary font-weight-bold mt-3">5. Diagnosis & Risk Assessment</h6>
      <div class="p-3 border bg-light rounded">
          <div class="row">
              <div class="col-md-6"><strong>Primary Diagnosis:</strong> <?= e($selected_record["diagnosis"]) ?></div>
              <div class="col-md-6"><strong>Category:</strong> <?= e($selected_record["disease_category"]) ?></div>
              <div class="col-md-12 mt-2"><strong>Risk Score:</strong> <span class="text-danger font-weight-bold"><?= e($selected_record["risk_score"]) ?></span></div>
          </div>
      </div>

    </div>
  </div>
  <?php endif; ?>
</div>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
</body>
</html>
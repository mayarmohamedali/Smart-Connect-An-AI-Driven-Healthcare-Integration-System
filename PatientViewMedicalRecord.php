<?php
session_start();
if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "patient") {
  header("Location: login.html");
  exit;
}

require_once "db.php";

$patient_id = (int)($_SESSION["patient_id"] ?? 0);
$record_id  = (int)($_GET["record_id"] ?? 0);

if ($patient_id <= 0 || $record_id <= 0) {
  die("Invalid request.");
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

/* =========================
   Fetch record (belongs to this patient only)
========================= */
$stmt = $conn->prepare("
  SELECT *
  FROM medical_records
  WHERE record_id = ? AND patient_id = ?
  LIMIT 1
");
$stmt->bind_param("ii", $record_id, $patient_id);
$stmt->execute();
$rec = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rec) {
  die("Record not found or access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Medical Record #<?= (int)$record_id ?></title>

  <link href="css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    body { background:#f8f9fc; }
    .label { font-weight:600; color:#4e73df; }
  </style>
</head>
<body>

<div class="container py-4">

  <a href="PatientDashboard.php#medical" class="btn btn-outline-secondary mb-3">
    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
  </a>

  <div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">
        <i class="fas fa-file-medical-alt mr-2"></i>
        Medical Record Details — #<?= (int)$record_id ?>
      </h5>
      <small>Created at: <?= e($rec["created_at"] ?? "-") ?></small>
    </div>

    <div class="card-body">

      <!-- BASIC -->
      <h6 class="text-primary font-weight-bold">Basic</h6>
      <div class="row">
        <div class="col-md-4 mb-2"><span class="label">Age:</span> <?= e($rec["age"] ?? "-") ?></div>
        <div class="col-md-4 mb-2"><span class="label">Check-in:</span> <?= e($rec["checkin_date"] ?? "-") ?></div>
        <div class="col-md-4 mb-2"><span class="label">Check-out:</span> <?= e($rec["checkout_date"] ?? "-") ?></div>
        <div class="col-md-4 mb-2"><span class="label">Length of Stay:</span> <?= e($rec["length_of_stay"] ?? "-") ?></div>
        <div class="col-md-4 mb-2"><span class="label">Month:</span> <?= e($rec["month"] ?? "-") ?></div>
        <div class="col-md-4 mb-2"><span class="label">Admission Count:</span> <?= e($rec["admission_count"] ?? "-") ?></div>
      </div>

      <hr>

      <!-- LABS ROUND 1 -->
      <h6 class="text-primary font-weight-bold">Lab Results – Round 1</h6>
      <div class="row">
        <div class="col-md-3 mb-2"><span class="label">CBC-HB1:</span> <?= e($rec["cbc_hb1"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">CBC-TLC1:</span> <?= e($rec["cbc_tlc1"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">CBC-PLAT1:</span> <?= e($rec["cbc_plat1"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Blood Urea 1:</span> <?= e($rec["blood_uria1"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Creatinine 1:</span> <?= e($rec["blood_creatinine1"] ?? "-") ?></div>
      </div>

      <hr>

      <!-- LABS ROUND 2 -->
      <h6 class="text-primary font-weight-bold">Lab Results – Round 2</h6>
      <div class="row">
        <div class="col-md-3 mb-2"><span class="label">CBC-HB2:</span> <?= e($rec["cbc_hb2"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">CBC-TLC2:</span> <?= e($rec["cbc_tlc2"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">CBC-PLAT2:</span> <?= e($rec["cbc_plat2"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Blood Urea 2:</span> <?= e($rec["blood_uria2"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Creatinine 2:</span> <?= e($rec["blood_creatinine2"] ?? "-") ?></div>
      </div>

      <hr>

      <!-- VITALS -->
      <h6 class="text-primary font-weight-bold">Vitals</h6>
      <div class="row">
        <div class="col-md-3 mb-2"><span class="label">BMI:</span> <?= e($rec["bmi"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Glucose:</span> <?= e($rec["glucose"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Systolic BP:</span> <?= e($rec["systolic_bp"] ?? "-") ?></div>
      </div>

      <hr>

      <!-- AVERAGES -->
      <h6 class="text-primary font-weight-bold">Averages</h6>
      <div class="row">
        <div class="col-md-3 mb-2"><span class="label">Avg Creatinine:</span> <?= e($rec["avg_creatinine"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Avg Urea:</span> <?= e($rec["avg_urea"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Avg HB:</span> <?= e($rec["avg_hb"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Avg TLC:</span> <?= e($rec["avg_tlc"] ?? "-") ?></div>
        <div class="col-md-3 mb-2"><span class="label">Avg Platelets:</span> <?= e($rec["avg_platelets"] ?? "-") ?></div>
      </div>

      <hr>

      <!-- LIFESTYLE -->
      <h6 class="text-primary font-weight-bold">Lifestyle</h6>
      <div class="row">
        <div class="col-md-4 mb-2"><span class="label">Smoking Status:</span> <?= e($rec["smoking_status"] ?? "-") ?></div>
        <div class="col-md-4 mb-2"><span class="label">Physical Activity:</span> <?= e($rec["physical_activity_level"] ?? "-") ?></div>
      </div>

      <hr>

      <!-- FLAGS + DIAGNOSIS -->
      <h6 class="text-primary font-weight-bold">Diagnosis & Risks</h6>
      <div class="row">
        <div class="col-md-12 mb-2"><span class="label">Diagnosis:</span> <?= e($rec["diagnosis"] ?? "-") ?></div>
        <div class="col-md-12 mb-2">
          <span class="label">Risks:</span>
          <?php if (!empty($rec["has_diabetes"])): ?><span class="badge badge-warning mr-1">Diabetes</span><?php endif; ?>
          <?php if (!empty($rec["has_hypertension"])): ?><span class="badge badge-danger mr-1">Hypertension</span><?php endif; ?>
          <?php if (!empty($rec["has_kidney_disease"])): ?><span class="badge badge-info mr-1">Kidney Disease</span><?php endif; ?>
          <?php if (!empty($rec["has_heart_disease"])): ?><span class="badge badge-primary mr-1">Heart Disease</span><?php endif; ?>

          <?php
            $noFlags = empty($rec["has_diabetes"]) && empty($rec["has_hypertension"]) &&
                       empty($rec["has_kidney_disease"]) && empty($rec["has_heart_disease"]);
            if ($noFlags) echo '<span class="text-muted">No risk flags</span>';
          ?>
        </div>
      </div>

    </div>
  </div>

</div>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>

</body>
</html>

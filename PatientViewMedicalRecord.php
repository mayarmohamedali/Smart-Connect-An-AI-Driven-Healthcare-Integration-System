<?php
/**
 * PatientViewMedicalRecord.php
 * ✅ Full Data Visibility for Patient
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/MedicalRecord.php';

$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

$auth->checkPatientAuth();

$patient_id = (int)$auth->getSessionData("patient_id");
$record_id  = (int)($_GET["record_id"] ?? 0);

if ($patient_id <= 0 || $record_id <= 0) {
  die("Invalid request.");
}

function e($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

$stmt = $conn->prepare("SELECT * FROM medical_records WHERE record_id = ? AND patient_id = ? LIMIT 1");
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
  <title>My Medical Record Details</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    body { background:#f8f9fc; }
    .label { font-weight:600; color:#4e73df; }
    .section-header { border-bottom: 2px solid #4e73df; margin-bottom: 15px; padding-bottom: 5px; color: #4e73df; font-weight: bold; }
  </style>
</head>
<body>

<div class="container py-4">
  <a href="PatientDashboard.php#medical" class="btn btn-outline-secondary mb-3 shadow-sm">
    <i class="fas fa-arrow-left mr-1"></i> Back to My Records
  </a>

  <div class="card shadow mb-4">
    <div class="card-header bg-primary text-white py-3 shadow">
      <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-invoice-medical mr-2"></i> Report #<?= (int)$record_id ?></h5>
    </div>

    <div class="card-body">
      
      <div class="section-header">Admission Details</div>
      <div class="row mb-4">
        <div class="col-md-3"><span class="label">Date:</span> <?= e($rec["checkin_date"]) ?></div>
        <div class="col-md-3"><span class="label">Duration:</span> <?= e($rec["length_of_stay"]) ?> Days</div>
        <div class="col-md-3"><span class="label">Age:</span> <?= e($rec["age"]) ?></div>
        <div class="col-md-3"><span class="label">Visit ID:</span> <?= e($rec["admission_count"]) ?></div>
      </div>

      <div class="section-header">Laboratory Trends (Round 1 → Round 2)</div>
      <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm text-center">
            <thead class="bg-light">
                <tr><th>Metric</th><th>First Reading</th><th>Second Reading</th><th>Change</th></tr>
            </thead>
            <tbody>
                <tr><td>Hemoglobin (HB)</td><td><?= e($rec["cbc_hb1"]) ?></td><td><?= e($rec["cbc_hb2"]) ?></td><td class="text-info"><?= e($rec["delta_hb"]) ?></td></tr>
                <tr><td>Blood Urea</td><td><?= e($rec["blood_uria1"]) ?></td><td><?= e($rec["blood_uria2"]) ?></td><td class="text-info"><?= e($rec["delta_uria"]) ?></td></tr>
                <tr><td>Creatinine</td><td><?= e($rec["blood_creatinine1"]) ?></td><td><?= e($rec["blood_creatinine2"]) ?></td><td class="text-info"><?= e($rec["delta_creatinine"]) ?></td></tr>
            </tbody>
        </table>
      </div>

      <div class="section-header">Lifestyle & Wellness Profile</div>
      <div class="row mb-4">
        <div class="col-md-4 mb-2"><span class="label">Activity Level:</span> <?= e($rec["physical_activity_level"]) ?></div>
        <div class="col-md-4 mb-2"><span class="label">Sleep:</span> <?= e($rec["sleep_hours"]) ?> Hours</div>
        <div class="col-md-4 mb-2"><span class="label">Diet Quality:</span> <?= e($rec["diet_quality"]) ?></div>
        <div class="col-md-4 mb-2"><span class="label">Smoking Status:</span> <?= e($rec["smoking_status"]) ?></div>
        <div class="col-md-4 mb-2"><span class="label">Stress Level:</span> <?= e($rec["stress_level"]) ?>/10</div>
        <div class="col-md-4 mb-2"><span class="label">BMI:</span> <?= e($rec["bmi"]) ?></div>
      </div>

      <div class="section-header">Reported Symptoms Check</div>
      <div class="row mb-4">
        <div class="col-md-12">
            <?php 
            $syms = [];
            if($rec["fever"] > 0) $syms[] = "Fever";
            if($rec["cough"] > 0) $syms[] = "Cough";
            if($rec["fatigue"] > 0) $syms[] = "Fatigue";
            if($rec["headache"] > 0) $syms[] = "Headache";
            if($rec["chest_pain"] > 0) $syms[] = "Chest Pain";
            if($rec["shortness_of_breath"] > 0) $syms[] = "Shortness of Breath";

            if(empty($syms)) {
                echo '<span class="text-muted">No significant symptoms recorded.</span>';
            } else {
                foreach($syms as $s) {
                    echo '<span class="badge badge-pill badge-danger p-2 mr-2 mb-2">'.$s.'</span>';
                }
            }
            ?>
        </div>
      </div>

      <div class="p-4 bg-light border-left border-primary rounded">
        <h6 class="label text-uppercase mb-2">Final Diagnosis</h6>
        <h4 class="font-weight-bold text-dark"><?= e($rec["diagnosis"]) ?></h4>
        <p class="mb-0 text-muted">Condition Category: <?= e($rec["disease_category"]) ?></p>
      </div>

    </div>
    <div class="card-footer text-center text-muted small">
        Generated for patient ID <?= (int)$patient_id ?> on <?= date('Y-m-d') ?>
    </div>
  </div>
</div>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
</body>
</html>
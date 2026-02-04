<?php
session_start();

if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "staff" || ($_SESSION["role"] ?? "") !== "HOSPITAL_STAFF") {
  header("Location: login.html");
  exit;
}

require_once "db.php";

$hospital_id = (int)($_SESSION["hospital_id"] ?? 1);
$patient_id  = (int)($_GET["patient_id"] ?? 0);

if ($patient_id <= 0) die("Missing patient_id");

function e($v) {
  return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

/* =========================
   Detect columns (MariaDB-safe)
========================= */
function table_has_column(mysqli $conn, string $table, string $col): bool {
  $table = $conn->real_escape_string($table);
  $col   = $conn->real_escape_string($col);
  $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
  return $res && $res->num_rows > 0;
}

$patient_ins_col = null;
foreach (["insurance_id","medical_insurance_id","ins_id"] as $c) {
  if (table_has_column($conn, "patients", $c)) { $patient_ins_col = $c; break; }
}

if (!$patient_ins_col) {
  die("patients table does not contain insurance_id (or medical_insurance_id / ins_id).");
}

/* medical_insurances PK + name + logo auto detect */
$ins_pk = null;
$ins_name_col = null;
$ins_logo_col = null;

$cols = $conn->query("SHOW COLUMNS FROM medical_insurances");
while ($r = $cols->fetch_assoc()) {
  $f = strtolower($r["Field"]);
  if (!$ins_pk && in_array($f, ["id","insurance_id","medical_insurance_id"])) $ins_pk = $r["Field"];
  if (!$ins_name_col && in_array($f, ["name","insurance_name"])) $ins_name_col = $r["Field"];
  if (!$ins_logo_col && in_array($f, ["logo","logo_path","icon","icon_path","image","image_path"])) $ins_logo_col = $r["Field"];
}
if (!$ins_pk) die("medical_insurances table PK not detected (id / insurance_id / medical_insurance_id).");
if (!$ins_name_col) $ins_name_col = "name";

/* =========================
   Fetch Patient Info WITH CONTRACT CHECK
   Hospital can view patient only if:
   insurance_hospitals has (patient_insurance_id, hospital_id)
========================= */
$stmt = $conn->prepare("
  SELECT p.patient_id, p.full_name, p.national_id, p.phone, p.gender, p.address,
         p.`$patient_ins_col` AS insurance_fk
  FROM patients p
  JOIN insurance_hospitals ih
    ON ih.insurance_id = p.`$patient_ins_col`
   AND ih.hospital_id  = ?
  WHERE p.patient_id = ?
  LIMIT 1
");
$stmt->bind_param("ii", $hospital_id, $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
  die("Patient not found OR insurance not contracted with your hospital.");
}

/* =========================
   Fetch Insurance Info (name/logo)
========================= */
$insurance_name = null;
$insurance_logo = null;

$insurance_fk = (int)($patient["insurance_fk"] ?? 0);

if ($insurance_fk > 0) {
  $sql = "SELECT `$ins_name_col` AS nm";
  if ($ins_logo_col) $sql .= ", `$ins_logo_col` AS lg";
  $sql .= " FROM medical_insurances WHERE `$ins_pk`=? LIMIT 1";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $insurance_fk);
  $stmt->execute();
  $ins = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($ins) {
    $insurance_name = $ins["nm"] ?? null;
    $insurance_logo = $ins["lg"] ?? null;
  }
}

/* =========================
   DELETE Record (POST)
========================= */
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

/* =========================
   LIST Records
========================= */
$records = [];
$stmt = $conn->prepare("
  SELECT *
  FROM medical_records
  WHERE patient_id = ?
  ORDER BY record_id DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $records[] = $row;
$stmt->close();

/* =========================
   Selected Record Details
========================= */
$selected_record = null;
if (isset($_GET["record_id"])) {
  $rid = (int)$_GET["record_id"];
  if ($rid > 0) {
    $stmt = $conn->prepare("SELECT * FROM medical_records WHERE record_id=? AND patient_id=? LIMIT 1");
    $stmt->bind_param("ii", $rid, $patient_id);
    $stmt->execute();
    $selected_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>View Medical Records</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    body { background:#f8f9fc; }
    .badge-soft { border:1px solid rgba(0,0,0,.08); }
    .ins-badge { display:inline-flex; align-items:center; gap:8px; }
    .ins-logo { width:26px; height:26px; object-fit:contain; border-radius:6px; background:#fff; border:1px solid rgba(0,0,0,.08); }
  </style>
</head>

<body>

<div class="container-fluid py-4">

  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
      <a href="HospitalDashboard.php#patients" class="btn btn-outline-secondary mr-2">
        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
      </a>
    </div>

    <div class="mt-2 mt-md-0">
      <span class="badge badge-info badge-soft p-2">
        <i class="fas fa-user-injured mr-1"></i> Patient ID: <?= (int)$patient_id ?>
      </span>
      <span class="badge badge-secondary badge-soft p-2 ml-2">
        <i class="fas fa-notes-medical mr-1"></i> Records: <?= count($records) ?>
      </span>

      <?php if ($insurance_fk > 0): ?>
        <span class="badge badge-success badge-soft p-2 ml-2 ins-badge">
          <i class="fas fa-shield-alt"></i>
          <?php if (!empty($insurance_logo)): ?>
            <img class="ins-logo" src="<?= e($insurance_logo) ?>" alt="Insurance">
          <?php endif; ?>
          <?= e($insurance_name ?: ("Insurance #".$insurance_fk)) ?>
        </span>
      <?php endif; ?>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-id-card mr-1"></i> Patient Info
      </h6>

      <?php if ($insurance_fk > 0): ?>
        <div class="mt-2 mt-md-0">
          <span class="badge badge-success badge-soft p-2 ins-badge">
            <i class="fas fa-shield-alt mr-1"></i>
            <?php if (!empty($insurance_logo)): ?>
              <img class="ins-logo" src="<?= e($insurance_logo) ?>" alt="Insurance">
            <?php endif; ?>
            <strong><?= e($insurance_name ?: ("Insurance #".$insurance_fk)) ?></strong>
          </span>
        </div>
      <?php endif; ?>
    </div>

    <div class="card-body">
      <div class="row">
        <div class="col-md-4"><strong>Name:</strong> <?= e($patient["full_name"]) ?></div>
        <div class="col-md-4"><strong>Phone:</strong> <?= e($patient["phone"]) ?></div>
        <div class="col-md-4"><strong>Address:</strong> <?= e($patient["address"]) ?></div>

        <div class="col-md-4 mt-2"><strong>National ID:</strong> <?= e($patient["national_id"]) ?></div>
        <div class="col-md-4 mt-2"><strong>Gender:</strong> <?= e($patient["gender"]) ?></div>

        <?php if ($insurance_fk > 0): ?>
          <div class="col-md-4 mt-2">
            <strong>Insurance ID:</strong> <?= (int)$insurance_fk ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- rest of your page stays the same -->
  <!-- (Records table + details block unchanged) -->

  <!-- Records Table -->
  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-file-medical mr-1"></i> Medical Records
      </h6>
    </div>

    <div class="card-body">

      <?php if (!$records): ?>
        <p class="text-muted mb-0">No medical records found for this patient.</p>
      <?php else: ?>

        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Created</th>
                <th>Age</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>LOS</th>
                <th>Diagnosis</th>
                <th>Risks</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <?php $i=0; foreach ($records as $r): $i++; ?>
                <tr>
                  <td><?= $i ?></td>
                  <td><?= e($r["created_at"] ?? "") ?></td>
                  <td><?= e($r["age"] ?? "") ?></td>
                  <td><?= e($r["checkin_date"] ?? "") ?></td>
                  <td><?= e($r["checkout_date"] ?? "") ?></td>
                  <td><?= e($r["length_of_stay"] ?? "") ?></td>
                  <td><?= e($r["diagnosis"] ?? "") ?></td>

                  <td>
                    <?php if (!empty($r["has_diabetes"])): ?><span class="badge badge-warning">DM</span><?php endif; ?>
                    <?php if (!empty($r["has_hypertension"])): ?><span class="badge badge-danger">HTN</span><?php endif; ?>
                    <?php if (!empty($r["has_kidney_disease"])): ?><span class="badge badge-info">KD</span><?php endif; ?>
                    <?php if (!empty($r["has_heart_disease"])): ?><span class="badge badge-primary">HD</span><?php endif; ?>
                  </td>

                  <td style="white-space:nowrap;">
                    <a class="btn btn-sm btn-outline-primary"
                       href="ViewMedicalRecords.php?patient_id=<?= (int)$patient_id ?>&record_id=<?= (int)$r["record_id"] ?>#details">
                      <i class="fas fa-eye"></i> View
                    </a>

                    <a class="btn btn-sm btn-outline-success"
                       href="EditMedicalRecord.php?patient_id=<?= (int)$patient_id ?>&record_id=<?= (int)$r["record_id"] ?>">
                      <i class="fas fa-edit"></i> Edit
                    </a>

                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this record?');">
                      <input type="hidden" name="action" value="delete_record">
                      <input type="hidden" name="record_id" value="<?= (int)$r["record_id"] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">
                        <i class="fas fa-trash"></i> Delete
                      </button>
                    </form>
                  </td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php endif; ?>

    </div>
  </div>

  <!-- DETAILS BLOCK remains as you had it -->
  <?php if ($selected_record): ?>
    <div id="details" class="card shadow mt-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
          <i class="fas fa-file-medical-alt mr-1"></i>
          Record Details — #<?= (int)$selected_record["record_id"] ?>
        </h6>
        <a href="ViewMedicalRecords.php?patient_id=<?= (int)$patient_id ?>" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-times"></i> Close
        </a>
      </div>

     <div class="card-body">
  <div class="row">

    <div class="col-md-6 mb-2"><strong>Created At:</strong> <?= e($selected_record["created_at"] ?? "") ?></div>
    <div class="col-md-6 mb-2"><strong>Age:</strong> <?= e($selected_record["age"] ?? "") ?></div>

    <div class="col-md-6 mb-2"><strong>Check-in Date:</strong> <?= e($selected_record["checkin_date"] ?? "") ?></div>
    <div class="col-md-6 mb-2"><strong>Check-out Date:</strong> <?= e($selected_record["checkout_date"] ?? "") ?></div>

    <div class="col-md-6 mb-2"><strong>Length of Stay:</strong> <?= e($selected_record["length_of_stay"] ?? "") ?></div>
    <div class="col-md-6 mb-2"><strong>BMI:</strong> <?= e($selected_record["bmi"] ?? "") ?></div>

    <div class="col-md-6 mb-2"><strong>Glucose:</strong> <?= e($selected_record["glucose"] ?? "") ?></div>
    <div class="col-md-6 mb-2"><strong>Systolic BP:</strong> <?= e($selected_record["systolic_bp"] ?? "") ?></div>

    <hr class="w-100">

    <div class="col-md-4 mb-2"><strong>CBC-HB1:</strong> <?= e($selected_record["cbc_hb1"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>CBC-TLC1:</strong> <?= e($selected_record["cbc_tlc1"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>CBC-PLAT1:</strong> <?= e($selected_record["cbc_plat1"] ?? "") ?></div>

    <div class="col-md-4 mb-2"><strong>Blood Urea 1:</strong> <?= e($selected_record["blood_uria1"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>Creatinine 1:</strong> <?= e($selected_record["blood_creatinine1"] ?? "") ?></div>

    <div class="col-md-4 mb-2"><strong>CBC-HB2:</strong> <?= e($selected_record["cbc_hb2"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>CBC-TLC2:</strong> <?= e($selected_record["cbc_tlc2"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>CBC-PLAT2:</strong> <?= e($selected_record["cbc_plat2"] ?? "") ?></div>

    <div class="col-md-4 mb-2"><strong>Blood Urea 2:</strong> <?= e($selected_record["blood_uria2"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>Creatinine 2:</strong> <?= e($selected_record["blood_creatinine2"] ?? "") ?></div>

    <hr class="w-100">

    <div class="col-md-4 mb-2"><strong>Smoking Status:</strong> <?= e($selected_record["smoking_status"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>Physical Activity:</strong> <?= e($selected_record["physical_activity_level"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>Admission Count:</strong> <?= e($selected_record["admission_count"] ?? "") ?></div>

    <div class="col-md-4 mb-2"><strong>Avg Creatinine:</strong> <?= e($selected_record["avg_creatinine"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>Avg Urea:</strong> <?= e($selected_record["avg_urea"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>Avg HB:</strong> <?= e($selected_record["avg_hb"] ?? "") ?></div>

    <div class="col-md-4 mb-2"><strong>Avg TLC:</strong> <?= e($selected_record["avg_tlc"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>Avg Platelets:</strong> <?= e($selected_record["avg_platelets"] ?? "") ?></div>
    <div class="col-md-4 mb-2"><strong>Month:</strong> <?= e($selected_record["month"] ?? "") ?></div>

    <hr class="w-100">

    <div class="col-md-6 mb-2"><strong>Diagnosis:</strong> <?= e($selected_record["diagnosis"] ?? "") ?></div>
    <div class="col-md-6 mb-2">
      <strong>Risks:</strong>
      <?php if (!empty($selected_record["has_diabetes"])): ?><span class="badge badge-warning">Diabetes</span><?php endif; ?>
      <?php if (!empty($selected_record["has_hypertension"])): ?><span class="badge badge-danger">Hypertension</span><?php endif; ?>
      <?php if (!empty($selected_record["has_kidney_disease"])): ?><span class="badge badge-info">Kidney Disease</span><?php endif; ?>
      <?php if (!empty($selected_record["has_heart_disease"])): ?><span class="badge badge-primary">Heart Disease</span><?php endif; ?>
    </div>

  </div>
</div>

    </div>
  <?php endif; ?>

</div>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>

</body>
</html>

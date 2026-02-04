<?php
session_start();
if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "staff" || ($_SESSION["role"] ?? "") !== "HOSPITAL_STAFF") {
  header("Location: login.html");
  exit;
}

require_once "db.php";

$hospital_id = (int)($_SESSION["hospital_id"] ?? 1);
$patient_id = (int)($_GET["patient_id"] ?? 0);

$success = "";
$error   = "";

if ($patient_id <= 0) {
  die("Invalid patient_id");
}
$stmt = $conn->prepare("
  SELECT p.patient_id, p.full_name, p.national_id
  FROM patients p
  JOIN insurance_hospitals ih
    ON ih.insurance_id = p.insurance_id
   AND ih.hospital_id = ?
  WHERE p.patient_id = ?
  LIMIT 1
");
$stmt->bind_param("ii", $hospital_id, $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
  die("This patient’s insurance is not contracted with your hospital.");
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // helper: convert empty to null
  function n($v) { $v = trim($v ?? ""); return $v === "" ? null : $v; }
  function b($v) { return isset($v) && $v == "1" ? 1 : 0; }

  $age = n($_POST["age"]);
  $checkin_date = n($_POST["checkin_date"]);
  $checkout_date = n($_POST["checkout_date"]);

  $cbc_hb1 = n($_POST["cbc_hb1"]);
  $cbc_tlc1 = n($_POST["cbc_tlc1"]);
  $cbc_plat1 = n($_POST["cbc_plat1"]);
  $blood_uria1 = n($_POST["blood_uria1"]);
  $blood_creatinine1 = n($_POST["blood_creatinine1"]);

  $cbc_hb2 = n($_POST["cbc_hb2"]);
  $cbc_tlc2 = n($_POST["cbc_tlc2"]);
  $cbc_plat2 = n($_POST["cbc_plat2"]);
  $blood_uria2 = n($_POST["blood_uria2"]);
  $blood_creatinine2 = n($_POST["blood_creatinine2"]);

  $bmi = n($_POST["bmi"]);
  $glucose = n($_POST["glucose"]);
  $systolic_bp = n($_POST["systolic_bp"]);

  $month = n($_POST["month"]);
  $admission_count = n($_POST["admission_count"]);

  $avg_creatinine = n($_POST["avg_creatinine"]);
  $avg_urea = n($_POST["avg_urea"]);
  $avg_hb = n($_POST["avg_hb"]);
  $avg_tlc = n($_POST["avg_tlc"]);
  $avg_platelets = n($_POST["avg_platelets"]);

  $length_of_stay = n($_POST["length_of_stay"]);
  $smoking_status = n($_POST["smoking_status"]);
  $physical_activity_level = n($_POST["physical_activity_level"]);

  $has_diabetes = b($_POST["has_diabetes"] ?? 0);
  $has_hypertension = b($_POST["has_hypertension"] ?? 0);
  $has_kidney_disease = b($_POST["has_kidney_disease"] ?? 0);
  $has_heart_disease = b($_POST["has_heart_disease"] ?? 0);

  $diagnosis = n($_POST["diagnosis"]);

  $sql = "
    INSERT INTO medical_records (
      patient_id, age, checkin_date, checkout_date,
      cbc_hb1, cbc_tlc1, cbc_plat1, blood_uria1, blood_creatinine1,
      cbc_hb2, cbc_tlc2, cbc_plat2, blood_uria2, blood_creatinine2,
      bmi, glucose, systolic_bp,
      month, admission_count,
      avg_creatinine, avg_urea, avg_hb, avg_tlc, avg_platelets,
      length_of_stay,
      smoking_status, physical_activity_level,
      has_diabetes, has_hypertension, has_kidney_disease, has_heart_disease,
      diagnosis
    ) VALUES (
      ?, ?, ?, ?,
      ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?,
      ?, ?, ?,
      ?, ?,
      ?, ?, ?, ?, ?,
      ?,
      ?, ?,
      ?, ?, ?, ?,
      ?
    )
  ";

  $stmt = $conn->prepare($sql);

  $stmt->bind_param(
    "iissddddddddd" . "dddiidddddd" . "issiiii" . "s",
    $patient_id, $age, $checkin_date, $checkout_date,

    $cbc_hb1, $cbc_tlc1, $cbc_plat1, $blood_uria1, $blood_creatinine1,
    $cbc_hb2, $cbc_tlc2, $cbc_plat2, $blood_uria2, $blood_creatinine2,

    $bmi, $glucose, $systolic_bp,

    $month, $admission_count,

    $avg_creatinine, $avg_urea, $avg_hb, $avg_tlc, $avg_platelets,

    $length_of_stay,

    $smoking_status, $physical_activity_level,

    $has_diabetes, $has_hypertension, $has_kidney_disease, $has_heart_disease,

    $diagnosis
  );

  if ($stmt->execute()) {
    $success = "Medical record added successfully ✅";
  } else {
    $error = "Insert failed: " . $stmt->error;
  }
  $stmt->close();
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
        Add Medical Record — <?= htmlspecialchars($patient["full_name"]) ?>
      </h5>
      <small>National ID: <?= htmlspecialchars($patient["national_id"]) ?></small>
    </div>

    <div class="card-body">
     <?php if ($success): ?>
  <div class="alert alert-success">
    <?= htmlspecialchars($success) ?><br>
    <small>Redirecting to dashboard...</small>
  </div>

  <script>
    setTimeout(function () {
      window.location.href = "HospitalDashboard.php#patients";
    }, 2000); // 2 seconds
  </script>
<?php endif; ?>

      <form method="POST">

        <div class="row">
          <div class="col-md-3 form-group">
            <label>Age</label>
            <input type="number" name="age" class="form-control">
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

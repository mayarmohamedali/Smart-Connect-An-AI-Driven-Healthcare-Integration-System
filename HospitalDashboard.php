<?php
/**
 * HospitalDashboard.php - OOP Version (FULL PAGE)
 * ✅ NO added_by_hospital_id
 * ✅ Shows Insurance name per patient (NO N+1 queries)
 * ✅ ML Epidemic Forecast — REAL integration from medical_records table
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Patient.php';
require_once __DIR__ . '/Hospital.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/EpidemicForecast.php';

// Initialize
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// Auth
$auth->checkStaffAuth("HOSPITAL_STAFF");

// Session data
$hospital_id = (int)($auth->getSessionData("hospital_id") ?? 0);
if ($hospital_id <= 0) {
  header("Location: login.html");
  exit;
}

$success_msg = "";
$error_msg   = "";

// Load hospital
$hospital = new Hospital($conn);
$hospital->loadById($hospital_id);
$hospital_name = $hospital->getName() ?: "Hospital";

// Search / list patients
$patient  = new Patient($conn);
$q        = trim($_GET["q"] ?? "");
$patients = $patient->getPatientsByHospital($hospital_id, $q) ?? [];

// ── KPI helpers ───────────────────────────────────────────────────────────────
function fetch_int(mysqli $conn, string $sql, string $types = "", array $params = []): int {
  $stmt = $conn->prepare($sql);
  if ($types !== "") $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_row() : null;
  $stmt->close();
  return $row ? (int)$row[0] : 0;
}

$kpi_patients = fetch_int($conn,
  "SELECT COUNT(DISTINCT p.patient_id)
   FROM patients p
   INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
   WHERE ih.hospital_id = ? AND p.is_active = 1",
  "i", [$hospital_id]);

$kpi_medical_records = fetch_int($conn,
  "SELECT COUNT(*)
   FROM medical_records mr
   INNER JOIN patients p ON p.patient_id = mr.patient_id
   INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
   WHERE ih.hospital_id = ?",
  "i", [$hospital_id]);

$kpi_insured_patients = fetch_int($conn,
  "SELECT COUNT(DISTINCT p.patient_id)
   FROM patients p
   INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
   WHERE ih.hospital_id = ?
     AND p.insurance_id IS NOT NULL AND p.is_active = 1",
  "i", [$hospital_id]);

$kpi_recent_admissions = fetch_int($conn,
  "SELECT COUNT(DISTINCT p.patient_id)
   FROM patients p
   INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
   WHERE ih.hospital_id = ?
     AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     AND p.is_active = 1",
  "i", [$hospital_id]);

function e($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}
function severity_color(string $s): string {
  return match($s) { "critical"=>"danger","high"=>"warning","medium"=>"info",default=>"secondary" };
}
function severity_icon(string $s): string {
  return match($s) { "critical"=>"🔴","high"=>"🟠","medium"=>"🟡",default=>"🟢" };
}

$month_names  = ["","Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
$today_month  = (int)date("n");
$next_month_n = $today_month % 12 + 1;

// ── REAL ML Integration: pull this month's medical_records for this hospital ──
// Physical_Activity: DB stores 'Moderate', ML model expects 'Medium' — mapped below
$stmt = $conn->prepare("
  SELECT
    COALESCE(mr.age, 35)                             AS Age,
    COALESCE(p.gender, 'Male')                       AS Gender,
    COALESCE(mr.bmi, 25.0)                           AS BMI,
    COALESCE(mr.systolic_bp, 120)                    AS Blood_Pressure,
    COALESCE(mr.cholesterol_level, 200)              AS Cholesterol_Level,
    COALESCE(mr.glucose, 90)                         AS Glucose_Level,
    COALESCE(mr.smoking_status, 0)                   AS Smoking_Status,
    COALESCE(mr.physical_activity_level, 'Moderate') AS Physical_Activity,
    COALESCE(mr.diet_quality, 'Average')             AS Diet_Quality,
    COALESCE(mr.alcohol_consumption, 0)              AS Alcohol_Consumption,
    COALESCE(mr.sleep_hours, 7)                      AS Sleep_Hours,
    COALESCE(mr.stress_level, 5)                     AS Stress_Level,
    COALESCE(mr.family_history, 0)                   AS Family_History,
    COALESCE(mr.medications_count, 0)                AS Medications_Count,
    COALESCE(mr.fever, 0)                            AS Fever,
    COALESCE(mr.cough, 0)                            AS Cough,
    COALESCE(mr.fatigue, 0)                          AS Fatigue,
    COALESCE(mr.chest_pain, 0)                       AS Chest_Pain,
    COALESCE(mr.shortness_of_breath, 0)              AS Shortness_of_Breath,
    COALESCE(mr.headache, 0)                         AS Headache,
    COALESCE(mr.month, MONTH(CURDATE()))             AS Month
  FROM medical_records mr
  INNER JOIN patients p ON p.patient_id = mr.patient_id
  INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
  WHERE ih.hospital_id = ?
AND mr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  LIMIT 500
");
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$raw_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Build patient batch — map 'Moderate' -> 'Medium' for the ML model
$patients_for_forecast = [];
foreach ($raw_records as $row) {
  $pa = $row["Physical_Activity"];
  if ($pa === "Moderate") $pa = "Medium";

  $patients_for_forecast[] = [
    "Age"                 => (float)$row["Age"],
    "Gender"              => (string)$row["Gender"],
    "BMI"                 => (float)$row["BMI"],
    "Blood_Pressure"      => (float)$row["Blood_Pressure"],
    "Cholesterol_Level"   => (float)$row["Cholesterol_Level"],
    "Glucose_Level"       => (float)$row["Glucose_Level"],
    "Smoking_Status"      => (int)$row["Smoking_Status"],
    "Physical_Activity"   => $pa,
    "Diet_Quality"        => (string)$row["Diet_Quality"],
    "Alcohol_Consumption" => (int)$row["Alcohol_Consumption"],
    "Sleep_Hours"         => (float)$row["Sleep_Hours"],
    "Stress_Level"        => (float)$row["Stress_Level"],
    "Family_History"      => (float)$row["Family_History"],
    "Medications_Count"   => (int)$row["Medications_Count"],
    "Fever"               => (float)$row["Fever"],
    "Cough"               => (float)$row["Cough"],
    "Fatigue"             => (float)$row["Fatigue"],
    "Chest_Pain"          => (int)$row["Chest_Pain"],
    "Shortness_of_Breath" => (float)$row["Shortness_of_Breath"],
    "Headache"            => (int)$row["Headache"],
    "Month"               => (int)$row["Month"],
  ];
}

$records_this_month = count($patients_for_forecast);

// Call Flask ML API
$ef        = new EpidemicForecast("http://127.0.0.1:5000");
$api_alive = $ef->isApiAlive();
$calendar  = $api_alive ? $ef->getHistoricalCalendar() : [];

$live_forecast = [];
if ($api_alive && $records_this_month > 0) {
  $live_forecast = $ef->getNextMonthForecast($patients_for_forecast);
}
// ─────────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Hospital Dashboard</title>
  <link href="css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    body { background: #f8f9fc; }
    .section-title { font-weight: 800; }
    .anchor-offset { scroll-margin-top: 90px; }
    .dash-title { font-weight:800; letter-spacing:.2px; }
    .kpi-card { border-radius: 12px; }
    .kpi-card .card-body { padding: 14px 16px; }
    .kpi-label { font-size:.72rem; font-weight:800; letter-spacing:.6px; text-transform:uppercase; margin-bottom:6px; }
    .kpi-value { font-size:1.25rem; font-weight:800; line-height:1.1; }
  </style>
</head>
<body id="page-top">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#dashboard">
      <i class="fas fa-clinic-medical mr-2"></i>
      <strong><?= e($hospital_name) ?> Dashboard</strong>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
        <li class="nav-item"><a class="nav-link" href="#dashboard"><i class="fas fa-tachometer-alt mr-1"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="#patients"><i class="fas fa-user-injured mr-1"></i> Patients</a></li>
        <li class="nav-item"><a class="nav-link" href="#insights"><i class="fas fa-chart-line mr-1"></i> Insights</a></li>
        <li class="nav-item"><a class="nav-link" href="#epidemic-alerts"><i class="fas fa-exclamation-triangle mr-1"></i> Epidemic Alerts</a></li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
            <span class="d-none d-lg-inline mr-2"><?= e($auth->getSessionData("staff_name") ?? "Hospital Staff") ?></span>
            <i class="fas fa-user-circle fa-2x text-white"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="#"><i class="fas fa-user mr-2"></i> Profile</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout</a>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">

  <?php if ($success_msg): ?><div class="alert alert-success"><?= e($success_msg) ?></div><?php endif; ?>
  <?php if ($error_msg): ?><div class="alert alert-danger"><?= e($error_msg) ?></div><?php endif; ?>

  <!-- ================= DASHBOARD OVERVIEW ================= -->
  <div id="dashboard" class="anchor-offset mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <h5 class="dash-title text-gray-800 mb-0">
        <i class="fas fa-chart-pie mr-2 text-primary"></i> Dashboard Overview
      </h5>
      <span class="badge badge-light">
        <i class="fas fa-calendar-alt mr-1"></i> <?= date('F Y') ?>
      </span>
    </div>
    <div class="row">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-primary">Total Patients</div>
            <div class="kpi-value text-gray-800"><?= (int)$kpi_patients ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-success">Medical Records</div>
            <div class="kpi-value text-gray-800"><?= (int)$kpi_medical_records ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-info">Insured Patients</div>
            <div class="kpi-value text-gray-800"><?= (int)$kpi_insured_patients ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-warning">New This Week</div>
            <div class="kpi-value text-gray-800"><?= (int)$kpi_recent_admissions ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= INSIGHTS ================= -->
  <div id="insights" class="anchor-offset mt-4">
    <h3 class="text-primary section-title mb-3">Operational Insights</h3>
    <div class="row">
      <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Patient Registrations Over Time</h6>
          </div>
          <div class="card-body"><canvas id="registrationChart" height="120"></canvas></div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Patient Demographics</h6>
          </div>
          <div class="card-body"><canvas id="demographicsChart" height="180"></canvas></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= EPIDEMIC ALERT SYSTEM (ML — Real DB Data) ================= -->
  <div id="epidemic-alerts" class="anchor-offset mt-4">

    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
      <h3 class="text-danger section-title mb-0">🏥 Hospital Epidemic Alert System</h3>
      <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
        <?php if (!$api_alive): ?>
          <span class="badge badge-danger p-2">
            <i class="fas fa-times-circle mr-1"></i> ML API Offline
          </span>
        <?php else: ?>
          <span class="badge badge-success p-2">
            <i class="fas fa-check-circle mr-1"></i> ML Model Connected
          </span>
        <?php endif; ?>
        <span class="badge badge-<?= $records_this_month >= 5 ? 'primary' : 'secondary' ?> p-2">
          <i class="fas fa-database mr-1"></i>
          <?= $records_this_month ?> records this month
          <?php if ($records_this_month < 10): ?>
            &mdash; need <?= 10 - $records_this_month ?> more for forecast
          <?php endif; ?>
        </span>
      </div>
    </div>

    <?php
      $next_month_label = $month_names[$next_month_n] ?? "Next Month";
      $forecast_entry   = null;
      $forecast_source  = "none";

      if (!empty($live_forecast)) {
        if (($live_forecast["status"] ?? "") === "ok") {
          $forecast_entry  = $live_forecast;
          $forecast_source = "live";
        } elseif (($live_forecast["status"] ?? "") === "insufficient_data") {
          $forecast_source = "insufficient";
        }
      }

      // Fall back to historical baseline
      if (in_array($forecast_source, ["none", "insufficient"])) {
        foreach ($calendar as $entry) {
          if ((int)($entry["month"] ?? 0) === $next_month_n) {
            $forecast_entry  = $entry;
            $forecast_source = ($forecast_source === "insufficient")
                               ? "insufficient_with_history" : "historical";
            break;
          }
        }
      }
    ?>

    <?php if (in_array($forecast_source, ["insufficient", "insufficient_with_history"])): ?>
      <div class="alert alert-info d-flex align-items-start mb-3" style="gap:12px;">
        <i class="fas fa-info-circle fa-2x mt-1"></i>
        <div style="flex:1;">
          <strong>Collecting patient data for forecast...</strong><br>
          <small><?= $records_this_month ?> of 20 records needed this month. Showing historical baseline until enough records are collected.</small>
          <div class="progress mt-2" style="height:8px; border-radius:4px;">
            <div class="progress-bar bg-info" style="width:<?= min(100, ($records_this_month/20)*100) ?>%"></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($forecast_entry):
      $disease  = e($forecast_entry["dominant_display"] ?? str_replace("_"," ",$forecast_entry["dominant_disease"] ?? "Unknown"));
      $severity = $forecast_entry["severity"] ?? "medium";
      $color    = severity_color($severity);
      $icon     = severity_icon($severity);
      $recs     = $forecast_entry["recommendations"] ?? [];
      $pts_used = $forecast_entry["total_patients"] ?? null;
    ?>
    <div class="card shadow mb-4 border-left-<?= $color ?>">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between flex-wrap mb-2">
          <div>
            <h5 class="font-weight-bold text-<?= $color ?> mb-1">
              <?= $icon ?> <?= $next_month_label ?> Forecast: <?= $disease ?>
            </h5>
            <p class="text-muted mb-0" style="font-size:.85rem;">
              <?php if ($forecast_source === "live"): ?>
                <span class="badge badge-success mr-1">LIVE</span>
                Based on <strong><?= (int)$pts_used ?></strong> real patient records from your database this month
              <?php else: ?>
                <span class="badge badge-secondary mr-1">HISTORICAL</span>
                Trained model baseline &mdash; add more medical records this month to get a live prediction
              <?php endif; ?>
              &nbsp;&middot;&nbsp;
              Severity: <span class="badge badge-<?= $color ?>"><?= ucfirst($severity) ?></span>
            </p>
          </div>
          <span class="badge badge-<?= $color ?> badge-pill p-2" style="font-size:1rem;">
            <?= strtoupper($next_month_label) ?>
          </span>
        </div>

        <?php if (!empty($forecast_entry["distribution_pct"])): ?>
          <hr class="my-2">
          <p class="mb-2" style="font-size:.78rem; font-weight:800; color:#555; text-transform:uppercase; letter-spacing:.5px;">
            Predicted disease distribution for <?= $next_month_label ?>
          </p>
          <?php foreach ($forecast_entry["distribution_pct"] as $dis => $pct):
            $dis_label   = str_replace("_", " ", $dis);
            $pct_float   = (float)$pct;
            $is_dominant = ($dis === ($forecast_entry["dominant_disease"] ?? ""));
          ?>
          <div class="d-flex align-items-center mb-1">
            <div style="min-width:200px; font-size:.8rem; color:#444;">
              <?= $is_dominant ? '<strong>' : '' ?><?= e($dis_label) ?><?= $is_dominant ? '</strong>' : '' ?>
            </div>
            <div class="progress flex-grow-1" style="height:16px; border-radius:6px;">
              <div class="progress-bar <?= $is_dominant ? 'bg-'.$color : 'bg-secondary' ?>"
                   role="progressbar"
                   style="width:<?= min(100, $pct_float) ?>%; font-size:.75rem;">
                <?= $pct_float ?>%
              </div>
            </div>
            <?php if ($forecast_source === "live"): ?>
              <span style="min-width:70px; text-align:right; font-size:.78rem; color:#666; padding-left:8px;">
                <?= (int)($forecast_entry["distribution"][$dis] ?? 0) ?> patients
              </span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($recs)): ?>
          <div class="alert alert-<?= $color ?> py-2 mb-0 mt-3">
            <strong>🔧 Preparation Checklist for <?= $next_month_label ?>:</strong>
            <ul class="mb-0 mt-1 pl-4" style="font-size:.85rem;">
              <?php foreach ($recs as $rec): ?>
                <li><?= e($rec) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Full Year Calendar -->
    <?php if (!empty($calendar)): ?>
    <div class="card shadow mb-4">
      <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
          <i class="fas fa-calendar-alt mr-2"></i> Full-Year Epidemic Forecast Calendar
        </h6>
        <small class="text-muted">Trained ML model baseline</small>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" style="font-size:.88rem;">
            <thead class="thead-light">
              <tr>
                <th style="width:80px;">Month</th>
                <th>Dominant Disease</th>
                <th style="width:110px;">Severity</th>
                <th>Top Recommendation</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($calendar as $entry):
                $m_num   = (int)($entry["month"] ?? 0);
                $m_name  = e($entry["month_name"] ?? "");
                $dis     = e($entry["dominant_display"] ?? str_replace("_"," ",$entry["dominant_disease"] ?? ""));
                $sev     = $entry["severity"] ?? "medium";
                $col     = severity_color($sev);
                $ico     = severity_icon($sev);
                $rec1    = $entry["recommendations"][0] ?? "General preparedness recommended.";
                $is_now  = ($m_num === $today_month);
                $is_next = ($m_num === $next_month_n);
              ?>
              <tr <?= $is_next ? 'class="table-warning font-weight-bold"' : ($is_now ? 'class="table-light"' : '') ?>>
                <td>
                  <?= $m_name ?>
                  <?php if ($is_next): ?>
                    <span class="badge badge-warning" style="font-size:.6rem;">Next</span>
                  <?php elseif ($is_now): ?>
                    <span class="badge badge-primary" style="font-size:.6rem;">Now</span>
                  <?php endif; ?>
                </td>
                <td><?= $ico ?> <?= $dis ?></td>
                <td><span class="badge badge-<?= $col ?>"><?= ucfirst($sev) ?></span></td>
                <td style="color:#555; font-size:.82rem;"><?= e($rec1) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!$api_alive): ?>
      <div class="alert alert-warning">
        <i class="fas fa-plug mr-2"></i>
        <strong>ML API is not running.</strong>
        Open a terminal in your project folder and run: <code>py app.py</code> — then refresh this page.
      </div>
    <?php endif; ?>

  </div>
  <!-- ================= END EPIDEMIC ALERTS ================= -->

  <!-- ================= PATIENTS ================= -->
  <div id="patients" class="anchor-offset mt-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <h3 class="text-primary section-title mb-2">Patient Management</h3>
    </div>
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
            <h6 class="m-0 font-weight-bold text-primary">View Patients</h6>
            <form class="d-flex mt-2 mt-md-0" method="GET"
                  action="HospitalDashboard.php#patients"
                  style="gap:8px; flex:1; max-width:520px;">
              <input class="form-control form-control-lg" name="q" value="<?= e($q) ?>"
                     placeholder="Search by name, national ID, or phone" style="font-size:.95rem;">
              <button class="btn btn-primary btn-lg" type="submit" style="min-width:50px;">
                <i class="fas fa-search"></i>
              </button>
            </form>
          </div>
          <div class="card-body">
            <?php if (!$patients || count($patients) === 0): ?>
              <p class="text-muted mb-0">No patients found.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>ID</th><th>Name</th><th>National ID</th>
                      <th>Phone</th><th>Gender</th><th>Insurance</th><th>Medical Record</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($patients as $pt): ?>
                      <tr>
                        <td><?= (int)($pt["patient_id"] ?? 0) ?></td>
                        <td><?= e($pt["full_name"] ?? "") ?></td>
                        <td><?= e($pt["national_id"] ?? "") ?></td>
                        <td><?= e($pt["phone"] ?? "") ?></td>
                        <td><?= e($pt["gender"] ?? "") ?></td>
                        <td>
                          <?php
                            $insId   = (int)($pt["insurance_id"] ?? 0);
                            $insName = $pt["insurance_name"] ?? null;
                            if ($insId > 0) {
                              echo '<span class="badge badge-success">'.e($insName ?: "#".$insId).'</span>';
                            } else {
                              echo '<span class="badge badge-secondary">No Insurance</span>';
                            }
                          ?>
                        </td>
                        <td style="white-space:nowrap;">
                          <a class="btn btn-sm btn-primary"
                             href="AddMedicalRecord.php?patient_id=<?= (int)($pt["patient_id"] ?? 0) ?>">
                            <i class="fas fa-notes-medical mr-1"></i> Add Record
                          </a>
                          <a class="btn btn-sm btn-outline-secondary ml-2"
                             href="ViewMedicalRecords.php?patient_id=<?= (int)($pt["patient_id"] ?? 0) ?>">
                            <i class="fas fa-folder-open mr-1"></i> View
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- end container-fluid -->

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>
<script src="Js/chart.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  if (typeof Chart === "undefined") return;

  var regCanvas = document.getElementById("registrationChart");
  if (regCanvas) {
    new Chart(regCanvas.getContext("2d"), {
      type: "line",
      data: {
        labels: ["Jan","Feb","Mar","Apr","May","Jun"],
        datasets: [{
          label: "New Patients", data: [5,8,12,7,15,11],
          borderColor: "#4e73df", backgroundColor: "rgba(78,115,223,0.15)",
          pointBackgroundColor: "#1cc88a", borderWidth: 3, tension: 0.35, fill: true
        }]
      },
      options: { responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:true}}, scales:{y:{beginAtZero:true}} }
    });
  }

  var demoCanvas = document.getElementById("demographicsChart");
  if (demoCanvas) {
    new Chart(demoCanvas.getContext("2d"), {
      type: "doughnut",
      data: {
        labels: ["Male","Female"],
        datasets: [{ data:[1,4],
          backgroundColor:["#36b9cc","#f6c23e"],
          hoverBackgroundColor:["#2c9faf","#dda20a"], borderWidth:1 }]
      },
      options: { responsive:true, maintainAspectRatio:false,
        cutout:"65%", plugins:{legend:{position:"bottom"}} }
    });
  }
});
</script>
</body>
</html>
<?php if ($conn instanceof mysqli) { $conn->close(); } ?>
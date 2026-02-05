<?php
session_start();
if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "patient") {
  header("Location: login.html");
  exit;
}
require_once "db.php";

$patient_id = (int)($_SESSION["patient_id"] ?? 0);
if ($patient_id <= 0) {
  header("Location: login.html");
  exit;
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

/* ===== DOB from Egyptian National ID (14 digits) ===== */
function dob_from_national_id(string $nid): ?string {
  $nid = trim($nid);
  if (!preg_match('/^\d{14}$/', $nid)) return null;

  $centuryDigit = (int)$nid[0];
  $yy = (int)substr($nid, 1, 2);
  $mm = (int)substr($nid, 3, 2);
  $dd = (int)substr($nid, 5, 2);

  $century = ($centuryDigit === 2) ? 1900 : (($centuryDigit === 3) ? 2000 : null);
  if ($century === null) return null;

  $year = $century + $yy;
  if (!checkdate($mm, $dd, $year)) return null;

  return sprintf("%04d-%02d-%02d", $year, $mm, $dd);
}

function age_from_dob(?string $dob): ?int {
  if (!$dob) return null;
  try {
    $d = new DateTime($dob);
    $now = new DateTime();
    return (int)$now->diff($d)->y;
  } catch (Exception $e) {
    return null;
  }
}

/* =========================
   Patient Profile (with insurance)
========================= */
$stmt = $conn->prepare("
  SELECT p.patient_id, p.full_name, p.national_id, p.phone, p.gender, p.address, p.insurance_id, p.created_at,
         mi.name AS insurance_name
  FROM patients p
  LEFT JOIN medical_insurances mi ON mi.insurance_id = p.insurance_id
  WHERE p.patient_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
  session_destroy();
  header("Location: login.html");
  exit;
}

$dob = dob_from_national_id($patient["national_id"] ?? "");
$age = age_from_dob($dob);

/* =========================
   Policy (one per patient in your schema: UNIQUE patient_id)
========================= */
$policy = null;
$stmt = $conn->prepare("
  SELECT pp.patient_policy_id, pp.policy_number, pp.start_date, pp.end_date, pp.status,
         ip.plan_name
  FROM patient_policy pp
  LEFT JOIN insurance_plan ip ON ip.id = pp.insurance_plan_id
  WHERE pp.patient_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$policy = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* =========================
   Medical Records (last 20)
========================= */
$records = [];
$stmt = $conn->prepare("
  SELECT record_id, created_at, checkin_date, checkout_date, diagnosis,
         has_diabetes, has_hypertension, has_kidney_disease, has_heart_disease
  FROM medical_records
  WHERE patient_id = ?
  ORDER BY record_id DESC
  LIMIT 20
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $records[] = $r;
$stmt->close();

$latest = $records[0] ?? null;

/* simple counts for insurance section */
$kpi_records = count($records);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Patient Dashboard</title>

  <link href="css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    .anchor-offset { scroll-margin-top: 90px; }
    .badge-soft { border:1px solid rgba(0,0,0,.08); }
    
    /* AI Insights Styling */
    .prediction-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
    }
    
    .prediction-header {
      font-size: 1.3rem;
      font-weight: bold;
      margin-bottom: 10px;
    }
    
    .confidence-badge {
      background: rgba(255,255,255,0.2);
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: bold;
      display: inline-block;
      margin: 5px 0;
    }
    
    .risk-level-high {
      background: #dc3545;
      color: white;
      padding: 5px 12px;
      border-radius: 15px;
      font-weight: bold;
      font-size: 0.85rem;
      display: inline-block;
    }
    
    .risk-level-medium {
      background: #ffc107;
      color: #000;
      padding: 5px 12px;
      border-radius: 15px;
      font-weight: bold;
      font-size: 0.85rem;
      display: inline-block;
    }
    
    .risk-level-low {
      background: #28a745;
      color: white;
      padding: 5px 12px;
      border-radius: 15px;
      font-weight: bold;
      font-size: 0.85rem;
      display: inline-block;
    }
    
    .timeline-badge {
      background: rgba(255,255,255,0.15);
      padding: 5px 10px;
      border-radius: 12px;
      font-size: 0.9rem;
      display: inline-block;
    }
    
    .risk-predictions-list {
      background: rgba(255,255,255,0.1);
      border-radius: 8px;
      padding: 15px;
      margin-top: 15px;
    }
    
    .risk-item {
      padding: 8px 0;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .risk-item:last-child {
      border-bottom: none;
    }
    
    .risk-percentage {
      font-weight: bold;
      font-size: 1.1rem;
      color: #ffd700;
    }
    
    .risk-factors-section {
      background: #fff3cd;
      color: #856404;
      border-left: 4px solid #ffc107;
      padding: 15px;
      border-radius: 5px;
      margin: 15px 0;
    }
    
    .risk-factors-section h5 {
      color: #856404;
      margin-bottom: 10px;
      font-weight: bold;
    }
    
    .risk-factor-item {
      padding: 5px 0;
      font-weight: 500;
    }
    
    .recommendations-section {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .recommendations-section h5 {
      color: #495057;
      font-weight: bold;
      margin-bottom: 15px;
    }
    
    .recommendation-category {
      margin-bottom: 20px;
    }
    
    .recommendation-category h6 {
      color: #667eea;
      font-weight: bold;
      margin-bottom: 10px;
      font-size: 1rem;
    }
    
    .recommendation-item {
      padding: 8px 0;
      padding-left: 25px;
      position: relative;
    }
    
    .recommendation-item:before {
      content: "✓";
      position: absolute;
      left: 0;
      color: #28a745;
      font-weight: bold;
      font-size: 1.2rem;
    }
    
    /* User Dropdown Styling */
    .user-dropdown {
      position: relative;
    }
    
    .user-dropdown .dropdown-menu {
      right: 0;
      left: auto;
    }
    
    .user-info-btn {
      background: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.3);
      border-radius: 25px;
      padding: 5px 15px;
      color: white;
      display: flex;
      align-items: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .user-info-btn:hover {
      background: rgba(255,255,255,0.2);
      border-color: rgba(255,255,255,0.5);
    }
    
    .user-avatar {
      width: 35px;
      height: 35px;
      background: white;
      color: #4e73df;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      margin-right: 10px;
    }


    
  </style>
</head>

<body id="page-top" class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="PatientDashboard.php">
      <i class="fas fa-user-injured mr-2"></i>
      <strong>Patient Portal</strong>
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#patientNav"
      aria-controls="patientNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="patientNav">
      <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
        <li class="nav-item active"><a class="nav-link" href="#profile"><i class="fas fa-user mr-1"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="#medical"><i class="fas fa-notes-medical mr-1"></i> Medical</a></li>
        <li class="nav-item"><a class="nav-link" href="#insurance"><i class="fas fa-shield-alt mr-1"></i> Insurance</a></li>
        <li class="nav-item"><a class="nav-link" href="#ai"><i class="fas fa-robot mr-1"></i> AI Insights</a></li>
        <li class="nav-item"><a class="nav-link" href="#claim"><i class="fas fa-file-invoice-dollar mr-1"></i> Claim</a></li>
      </ul>

     <!-- User Dropdown Menu -->
<ul class="navbar-nav ml-auto">
  <li class="nav-item dropdown user-dropdown">
    <a class="nav-link dropdown-toggle user-info-btn" href="#" id="userDropdown" role="button" 
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

      <div class="user-avatar">
        <?= strtoupper(substr($patient["full_name"], 0, 1)) ?>
      </div>
      <span><?= htmlspecialchars($patient["full_name"]) ?></span>
    </a>

    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="logout.php">
        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
      </a>
    </div>
  </li>
</ul>

        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid mt-4">

  <!-- PROFILE -->
  <div id="profile" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">👤 Patient Profile</div>
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6"><b>Full Name:</b> <?= e($patient["full_name"]) ?></div>
        <div class="col-sm-6"><b>National ID:</b> <?= e($patient["national_id"]) ?></div>

        <div class="col-sm-6 mt-2"><b>Date of Birth:</b> <?= $dob ? e($dob) : "-" ?></div>
        <div class="col-sm-6 mt-2"><b>Age:</b> <?= $age !== null ? (int)$age : "-" ?></div>

        <div class="col-sm-6 mt-2"><b>Gender:</b> <?= e($patient["gender"] ?? "-") ?></div>
        <div class="col-sm-6 mt-2"><b>Phone:</b> <?= e($patient["phone"] ?? "-") ?></div>

        <div class="col-sm-12 mt-2"><b>Address:</b> <?= e($patient["address"] ?? "-") ?></div>

        <div class="col-sm-12 mt-3">
          <span class="badge badge-info badge-soft p-2">
            <i class="fas fa-id-badge mr-1"></i> Patient ID: <?= (int)$patient_id ?>
          </span>

          <?php if (!empty($patient["insurance_id"])): ?>
            <span class="badge badge-success badge-soft p-2 ml-2">
              <i class="fas fa-shield-alt mr-1"></i>
              Insurance: <?= e($patient["insurance_name"] ?? ("#".$patient["insurance_id"])) ?>
            </span>
          <?php else: ?>
            <span class="badge badge-warning badge-soft p-2 ml-2">
              <i class="fas fa-exclamation-triangle mr-1"></i> No insurance assigned
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- MEDICAL -->
  <div id="medical" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">🩺 Medical</div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4 mb-2">
          <b>Total Records:</b> <?= (int)$kpi_records ?>
        </div>

        <div class="col-md-8 mb-2">
          <b>Latest Diagnosis:</b> <?= e($latest["diagnosis"] ?? "-") ?>
          <?php if ($latest): ?>
            <small class="text-muted ml-2">(<?= e($latest["created_at"]) ?>)</small>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($latest): ?>
        <hr>
        <b>Risk Flags (Latest Record):</b>
        <div class="mt-2">
          <?php if (!empty($latest["has_diabetes"])): ?><span class="badge badge-warning mr-1">Diabetes</span><?php endif; ?>
          <?php if (!empty($latest["has_hypertension"])): ?><span class="badge badge-danger mr-1">Hypertension</span><?php endif; ?>
          <?php if (!empty($latest["has_kidney_disease"])): ?><span class="badge badge-info mr-1">Kidney Disease</span><?php endif; ?>
          <?php if (!empty($latest["has_heart_disease"])): ?><span class="badge badge-primary mr-1">Heart Disease</span><?php endif; ?>

          <?php
            $noFlags = empty($latest["has_diabetes"]) && empty($latest["has_hypertension"]) &&
                       empty($latest["has_kidney_disease"]) && empty($latest["has_heart_disease"]);
            if ($noFlags) echo '<span class="text-muted">No flags</span>';
          ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- MEDICAL RECORDS -->
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">📁 Medical Records (Latest 20)</div>
    <div class="card-body">
      <?php if (!$records): ?>
        <div class="text-muted">No medical records found.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Date Created</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Diagnosis</th>
                <th>View</th>
              </tr>
            </thead>
            <tbody>
              <?php $i=0; foreach ($records as $r): $i++; ?>
                <tr>
                  <td><?= $i ?></td>
                  <td><?= e($r["created_at"]) ?></td>
                  <td><?= e($r["checkin_date"] ?? "-") ?></td>
                  <td><?= e($r["checkout_date"] ?? "-") ?></td>
                  <td><?= e($r["diagnosis"] ?? "-") ?></td>
                  <td style="white-space:nowrap;">
                    <a class="btn btn-sm btn-outline-primary"
                       href="PatientViewMedicalRecord.php?record_id=<?= (int)$r["record_id"] ?>">
                      <i class="fas fa-eye"></i> View
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

  <!-- INSURANCE -->
  <div id="insurance" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">🛡 Insurance Information </div>
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6"><b>Provider:</b> <?= e($patient["insurance_name"] ?? "-") ?></div>
        <div class="col-sm-6"><b>Status:</b>
          <?php
            $st = $policy["status"] ?? "";
            if ($st === "active") echo '<span class="badge badge-success">active</span>';
            elseif ($st === "suspended") echo '<span class="badge badge-warning">suspended</span>';
            elseif ($st === "expired") echo '<span class="badge badge-secondary">expired</span>';
            else echo '<span class="badge badge-light">no policy</span>';
          ?>
        </div>

        <div class="col-sm-6 mt-2"><b>Plan:</b> <?= e($policy["plan_name"] ?? "-") ?></div>
        <div class="col-sm-6 mt-2"><b>Policy #:</b> <?= e($policy["policy_number"] ?? "-") ?></div>

        <div class="col-sm-6 mt-2"><b>Start Date:</b> <?= e($policy["start_date"] ?? "-") ?></div>
        <div class="col-sm-6 mt-2"><b>End Date:</b> <?= e($policy["end_date"] ?? "-") ?></div>
      </div>
    </div>
  </div>

  <!-- AI INSIGHTS - ENHANCED -->
  <div id="ai" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">🤖 AI Health Insights</div>
    <div class="card-body p-0">
      
      <!-- Main Prediction Card -->
      <div class="prediction-card">
        <div class="prediction-header">
          <i class="fas fa-brain mr-2"></i>Primary Health Prediction
        </div>
        
        <div class="mt-3">
          <h4 class="mb-2">Diabetes Mellitus</h4>
          <div class="mb-2">
            <span class="confidence-badge">
              <i class="fas fa-chart-line mr-1"></i>Confidence: 74.8%
            </span>
            <span class="risk-level-high ml-2">
              <i class="fas fa-exclamation-triangle mr-1"></i>RISK LEVEL: HIGH
            </span>
            <span class="timeline-badge ml-2">
              <i class="fas fa-clock mr-1"></i>Timeline: 1–3 months
            </span>
          </div>
        </div>

        <!-- Top 3 Risk Predictions -->
        <div class="risk-predictions-list">
          <h6 class="mb-3"><i class="fas fa-list-ol mr-2"></i>Top 3 Risk Predictions</h6>
          <div class="risk-item">
            <span class="risk-percentage">74.8%</span>
            <span class="ml-2">— Diabetes Mellitus</span>
          </div>
          <div class="risk-item">
            <span class="risk-percentage">11.6%</span>
            <span class="ml-2">— Hypertension</span>
          </div>
          <div class="risk-item">
            <span class="risk-percentage">5.1%</span>
            <span class="ml-2">— Heart Disease</span>
          </div>
        </div>
      </div>

      <!-- Active Risk Factors -->
      <div class="p-4">
        <div class="risk-factors-section">
          <h5><i class="fas fa-exclamation-circle mr-2"></i>Active Risk Factors</h5>
          <div class="risk-factor-item">
            <i class="fas fa-arrow-circle-right mr-2"></i>
            <strong>ELEVATED BLOOD GLUCOSE RISK</strong>
          </div>
          <div class="risk-factor-item">
            <i class="fas fa-arrow-circle-right mr-2"></i>
            Check fasting blood sugar & HbA1c within 3 days
          </div>
          <div class="risk-factor-item">
            <i class="fas fa-arrow-circle-right mr-2"></i>
            Family history of diabetes
          </div>
          <div class="risk-factor-item">
            <i class="fas fa-arrow-circle-right mr-2"></i>
            Sedentary lifestyle / overweight
          </div>
        </div>

        <!-- Recommendations -->
<div class="recommendations-section mt-4 p-3 rounded shadow-sm bg-light">
  <h5 class="mb-3">
    <i class="fas fa-clipboard-check mr-2 text-success"></i>
    Recommended Preventive Measures 💡
  </h5>
  
  <!-- Short Term -->
  <div class="recommendation-category mb-4">
    <h6 class="mb-2 text-primary">
      <i class="fas fa-calendar-alt mr-2"></i> Short-term (Next 3 months) ⏱️
    </h6>

    <div class="recommendation-item mb-2">
      🩸 Monitor fasting blood glucose weekly
    </div>
    <div class="recommendation-item mb-2">
      🍭 Reduce sugar & refined carbs intake
    </div>
    <div class="recommendation-item mb-2">
      🚶‍♂️ Walk 30 minutes daily
    </div>
    <div class="recommendation-item mb-2">
      💧 Maintain hydration
    </div>
  </div>

  <!-- Long Term -->
  <div class="recommendation-category">
    <h6 class="mb-2 text-success">
      <i class="fas fa-calendar-check mr-2"></i> Long-term (6–12 months) 📅
    </h6>

    <div class="recommendation-item mb-2">
      📊 Maintain HbA1c &lt; 7%
    </div>
    <div class="recommendation-item mb-2">
      🥗 Follow diabetic-friendly diet plan
    </div>
    <div class="recommendation-item mb-2">
      ⚖️ Maintain healthy BMI (18.5–24.9)
    </div>
    <div class="recommendation-item mb-2">
      👨‍⚕️ Regular follow-up with endocrinologist
    </div>
  </div>
</div>


  <!-- CLAIM -->
   <!-- 
  <div id="claim" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">📝 Request Insurance Claim</div>
    <div class="card-body">
      CLAIM -->
      <!-- Claim form content here -->
    </div>
  </div>

</div>

<footer class="sticky-footer bg-white">
  <div class="my-auto text-center"><span>Smart-Connect © 2026</span></div>
</footer>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>

<script>
  const sections = {
    profile: document.querySelector("#profile"),
    medical: document.querySelector("#medical"),
    insurance: document.querySelector("#insurance"),
    ai: document.querySelector("#ai"),
    claim: document.querySelector("#claim"),
  };

  window.addEventListener("scroll", () => {
    for (let key in sections) {
      let section = sections[key];
      if (section && section.getBoundingClientRect().top <= 120) {
        document.querySelectorAll(".nav-item").forEach(item => item.classList.remove("active"));
        const a = document.querySelector(`a[href="#${key}"]`);
        if (a && a.parentElement) a.parentElement.classList.add("active");
      }
    }
  });
</script>

</body>
</html>
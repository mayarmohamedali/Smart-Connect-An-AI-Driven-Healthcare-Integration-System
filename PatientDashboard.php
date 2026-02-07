<?php
/**
 * PatientDashboard.php - OOP Version (FIXED)
 * ✅ Includes Insurance Name (same appearance as old version)
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Patient.php';
require_once __DIR__ . '/MedicalRecord.php';
require_once __DIR__ . '/PatientPolicy.php';

$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

$auth->checkPatientAuth();
$patient_id = (int)$auth->getSessionData("patient_id");
if ($patient_id <= 0) {
  header("Location: login.html");
  exit;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
function rec_get($rec, string $key, $default = null) {
  if (is_array($rec)) return $rec[$key] ?? $default;
  if (is_object($rec)) return $rec->$key ?? $default;
  return $default;
}

/* Load patient */
$patient = new Patient($conn);
if (!$patient->loadById($patient_id)) {
  session_destroy();
  header("Location: login.html");
  exit;
}

$dob = $patient->getDOBFromNationalId();
$age = $patient->getAge();

/* Load policy */
$policy     = new PatientPolicy($conn);
$policyData = $policy->loadByPatientId($patient_id) ?? [];

/* Load medical records */
$medicalRecord = new MedicalRecord($conn);
$records = $medicalRecord->getRecordsByPatient($patient_id, 20) ?? [];
$latest  = $records[0] ?? null;
$kpi_records = is_array($records) ? count($records) : 0;

/* ✅ Insurance Name (fallback if null) */
$insuranceName = $patient->getInsuranceName();
if (!$insuranceName && $patient->getInsuranceId()) {
  $insuranceName = "#" . $patient->getInsuranceId();
}
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

    .prediction-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .prediction-header { font-size: 1.3rem; font-weight: bold; margin-bottom: 10px; }
    .confidence-badge { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; font-weight: bold; display: inline-block; margin: 5px 0; }
    .risk-level-high { background: #dc3545; color: white; padding: 5px 12px; border-radius: 15px; font-weight: bold; font-size: 0.85rem; display: inline-block; }
    .timeline-badge { background: rgba(255,255,255,0.15); padding: 5px 10px; border-radius: 12px; font-size: 0.9rem; display: inline-block; }
    .risk-predictions-list { background: rgba(255,255,255,0.1); border-radius: 8px; padding: 15px; margin-top: 15px; }
    .risk-item { padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .risk-item:last-child { border-bottom: none; }
    .risk-percentage { font-weight: bold; font-size: 1.1rem; color: #ffd700; }

    .risk-factors-section {
      background: #fff3cd;
      color: #856404;
      border-left: 4px solid #ffc107;
      padding: 15px;
      border-radius: 5px;
      margin: 15px 0;
    }

    .recommendations-section {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .recommendation-item { padding: 6px 0; font-size: .95rem; }
  </style>
</head>

<body id="page-top" class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="PatientDashboard.php">
      <i class="fas fa-user-injured mr-2"></i>
      <strong>Patient Portal</strong>
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#patientNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="patientNav">
      <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
        <li class="nav-item active"><a class="nav-link" href="#profile"><i class="fas fa-user mr-1"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="#medical"><i class="fas fa-notes-medical mr-1"></i> Medical</a></li>
        <li class="nav-item"><a class="nav-link" href="#insurance"><i class="fas fa-shield-alt mr-1"></i> Insurance</a></li>
        <li class="nav-item"><a class="nav-link" href="#ai"><i class="fas fa-robot mr-1"></i> AI Insights</a></li>
      </ul>

      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
             data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span><?= e($patient->getFullName()) ?></span>
          </a>
          <div class="dropdown-menu dropdown-menu-right shadow">
            <a class="dropdown-item" href="logout.php">
              <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i> Logout
            </a>
          </div>
        </li>
      </ul>

    </div>
  </div>
</nav>

<div class="container-fluid mt-4">

  <!-- PROFILE -->
  <div id="profile" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">👤  Profile</div>
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6"><b>Full Name:</b> <?= e($patient->getFullName()) ?></div>
        <div class="col-sm-6"><b>National ID:</b> <?= e($patient->getNationalId()) ?></div>

        <div class="col-sm-6 mt-2"><b>Date of Birth:</b> <?= $dob ? e($dob) : "-" ?></div>
        <div class="col-sm-6 mt-2"><b>Age:</b> <?= $age !== null ? (int)$age : "-" ?></div>

        <div class="col-sm-6 mt-2"><b>Gender:</b> <?= e($patient->getGender() ?: "-") ?></div>
        <div class="col-sm-6 mt-2"><b>Phone:</b> <?= e($patient->getPhone() ?: "-") ?></div>

        <div class="col-sm-12 mt-2"><b>Address:</b> <?= e($patient->getAddress() ?: "-") ?></div>

        <div class="col-sm-12 mt-3">
          <span class="badge badge-info badge-soft p-2">
            <i class="fas fa-id-badge mr-1"></i> Patient ID: <?= (int)$patient->getPatientId() ?>
          </span>

          <?php if ($patient->getInsuranceId()): ?>
            <span class="badge badge-success badge-soft p-2 ml-2">
              <i class="fas fa-shield-alt mr-1"></i>
              <!-- ✅ SAME as old version -->
              Insurance: <?= e($insuranceName) ?>
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
<footer class="sticky-footer bg-white">
  <div class="my-auto text-center"><span>Smart-Connect © 2026</span></div>

  <!-- MEDICAL -->
  <div id="medical" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">🩺 Medical</div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4 mb-2"><b>Total Records:</b> <?= (int)$kpi_records ?></div>
        <div class="col-md-8 mb-2">
          <b>Latest Diagnosis:</b> <?= e(rec_get($latest, "diagnosis", "-")) ?>
          <?php if ($latest): ?><small class="text-muted ml-2">(<?= e(rec_get($latest, "created_at", "")) ?>)</small><?php endif; ?>
        </div>
      </div>
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
                  <td><?= e(rec_get($r, "created_at", "-")) ?></td>
                  <td><?= e(rec_get($r, "checkin_date", "-")) ?></td>
                  <td><?= e(rec_get($r, "checkout_date", "-")) ?></td>
                  <td><?= e(rec_get($r, "diagnosis", "-")) ?></td>
                  <td style="white-space:nowrap;">
                    <a class="btn btn-sm btn-outline-primary"
                       href="PatientViewMedicalRecord.php?record_id=<?= (int)rec_get($r, "record_id", 0) ?>">
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
    <div class="card-header font-weight-bold">🛡 Insurance Information</div>
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6"><b>Provider:</b> <?= e($insuranceName ?: "-") ?></div>

        <div class="col-sm-6"><b>Status:</b>
          <?php
            $st = $policyData["status"] ?? "";
            if ($st === "active") echo '<span class="badge badge-success">active</span>';
            elseif ($st === "suspended") echo '<span class="badge badge-warning">suspended</span>';
            elseif ($st === "expired") echo '<span class="badge badge-secondary">expired</span>';
            else echo '<span class="badge badge-light">no policy</span>';
          ?>
        </div>

        <div class="col-sm-6 mt-2"><b>Plan:</b> <?= e($policyData["plan_name"] ?? "-") ?></div>
        <div class="col-sm-6 mt-2"><b>Policy #:</b> <?= e($policyData["policy_number"] ?? "-") ?></div>
        <div class="col-sm-6 mt-2"><b>Start Date:</b> <?= e($policyData["start_date"] ?? "-") ?></div>
        <div class="col-sm-6 mt-2"><b>End Date:</b> <?= e($policyData["end_date"] ?? "-") ?></div>
      </div>
    </div>
  </div>


<!-- =========================
     REQUEST CLAIM (FRONTEND ONLY) - MORE COLORFUL
========================= -->

<style>
  /* Hero / Header */
  .claim-hero {
    background: linear-gradient(135deg, #06b6d4 0%, #6366f1 55%, #a855f7 100%);
    color: #fff;
    border-radius: 16px;
    padding: 18px 18px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 14px 28px rgba(99,102,241,.18);
  }
  .claim-hero:before{
    content:"";
    position:absolute; right:-60px; top:-60px;
    width:220px; height:220px;
    background: radial-gradient(circle, rgba(255,255,255,.28), transparent 60%);
  }
  .claim-hero .hero-badge{
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.22);
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 800;
    letter-spacing: .2px;
  }

  /* Card */
  .claim-card {
    border-radius: 16px;
    border: 0;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(0,0,0,.08);
  }
  .claim-card .card-body{ background: #fff; }

  /* Section Titles */
  .claim-section-title{
    display:flex; align-items:center; gap:10px;
    font-weight: 900;
    margin: 12px 0 10px;
  }
  .icon-pill{
    width:34px; height:34px;
    border-radius: 10px;
    display:flex; align-items:center; justify-content:center;
    color:#fff;
    box-shadow: 0 10px 18px rgba(0,0,0,.12);
  }
  .bg-grad-green{ background: linear-gradient(135deg,#22c55e,#16a34a); }
  .bg-grad-blue{ background: linear-gradient(135deg,#3b82f6,#6366f1); }
  .bg-grad-orange{ background: linear-gradient(135deg,#fb923c,#f59e0b); }
  .bg-grad-pink{ background: linear-gradient(135deg,#ec4899,#a855f7); }

  /* Inputs highlight */
  .claim-form .form-control:focus{
    border-color: rgba(99,102,241,.55);
    box-shadow: 0 0 0 .2rem rgba(99,102,241,.20);
  }

  /* Record / Manual blocks */
  .soft-block{
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,.06);
    background: linear-gradient(180deg,#ffffff 0%, #f8fafc 100%);
    padding: 14px;
  }

  /* Item cards */
  .claim-item{
    border-radius: 14px !important;
    border: 1px solid rgba(0,0,0,.07) !important;
    background: #fff;
    box-shadow: 0 10px 22px rgba(0,0,0,.06);
  }

  /* Badges */
  .badge-soft{
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 999px;
    padding: 8px 12px;
    font-weight: 800;
    letter-spacing: .2px;
  }
  .badge-total{
    background: linear-gradient(135deg,#22c55e,#16a34a);
    color:#fff;
    border: none;
    box-shadow: 0 12px 20px rgba(34,197,94,.20);
  }

  /* Colorful buttons */
  .btn-add{
    background: linear-gradient(135deg,#3b82f6,#6366f1);
    border: none;
    color:#fff;
    box-shadow: 0 10px 18px rgba(99,102,241,.22);
  }
  .btn-add:hover{ opacity:.95; color:#fff; }
  .btn-submit{
    background: linear-gradient(135deg,#22c55e,#16a34a);
    border: none;
    box-shadow: 0 10px 18px rgba(34,197,94,.22);
  }
  .btn-submit:hover{ opacity:.95; }

  /* Little rainbow divider */
  .rainbow-line{
    height: 4px;
    border-radius: 999px;
    background: linear-gradient(90deg,#22c55e,#06b6d4,#3b82f6,#a855f7,#ec4899,#f59e0b);
    opacity: .9;
  }
</style>

<div class="claim-hero mb-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap">
    <div class="d-flex align-items-center">
      <div class="mr-3" style="font-size:1.6rem;"><i class="fas fa-file-invoice-dollar"></i></div>
      <div>
        <div style="font-size:1.15rem; font-weight:900;">Request a Claim</div>
        
      </div>
    </div>
    
  </div>
  <div class="rainbow-line mt-3"></div>
</div>

<div class="card claim-card">
  <div class="card-body claim-form">

    <!-- Alerts -->
    <div id="claimAlert" class="alert d-none" role="alert"></div>

    <form id="claimForm">

      <!-- Claim Type -->
      <div class="claim-section-title">
        <span class="icon-pill bg-grad-blue"><i class="fas fa-layer-group"></i></span>
        <span>Claim Details</span>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Claim Type</label>
          <select class="form-control" id="claimType" required>
            <option value="record" selected>From Medical Record (recommended)</option>
            <option value="manual">Manual Claim (no record)</option>
          </select>
        </div>

        <div class="form-group col-md-6">
          <label class="font-weight-bold">Policy Number</label>
          <input type="text" class="form-control" id="policyNumber" placeholder="e.g., POL-2026-00123" required>
        </div>
      </div>

      <!-- From Record -->
      <div id="recordBlock" class="soft-block">
        <label class="font-weight-bold">
          <i class="fas fa-notes-medical text-primary mr-1"></i>
          Select Medical Record <small class="text-muted">(demo list)</small>
        </label>
        <select class="form-control" id="recordSelect">
          <option value="">-- choose record --</option>
          <option value="R-1001">#R-1001 — 2026-02-01 — Diabetes Follow-up</option>
          <option value="R-1002">#R-1002 — 2026-01-18 — Hypertension Check</option>
          <option value="R-1003">#R-1003 — 2026-01-05 — Lab Tests</option>
        </select>
       
      </div>

      <!-- Manual Claim -->
      <div id="manualBlock" class="soft-block d-none mt-2">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label class="font-weight-bold">Hospital / Provider Name</label>
            <input type="text" class="form-control" id="providerName" placeholder="e.g., Al Salam Hospital">
          </div>
          <div class="form-group col-md-3">
            <label class="font-weight-bold">Visit Date</label>
            <input type="date" class="form-control" id="visitDate">
          </div>
          <div class="form-group col-md-3">
            <label class="font-weight-bold">Total Expected Amount (EGP)</label>
            <input type="number" class="form-control" id="expectedAmount" min="0" step="1" placeholder="0">
          </div>
        </div>

        <label class="font-weight-bold">Short Description</label>
        <textarea class="form-control" id="manualNotes" rows="2" placeholder="e.g., Consultation + lab tests..."></textarea>
      </div>

      <!-- Services -->
      <div class="claim-section-title mt-4">
        <span class="icon-pill bg-grad-orange"><i class="fas fa-stethoscope"></i></span>
        <span>Claim Items (Services)</span>
      </div>

      <div class="d-flex align-items-center justify-content-between flex-wrap">
        <p class="text-muted mb-2">Add services, quantities and prices. Total updates automatically.</p>
        <button type="button" class="btn btn-sm btn-add mb-2" id="addItemBtn">
          <i class="fas fa-plus mr-1"></i> Add Item
        </button>
      </div>

      <div id="itemsWrap">
        <!-- One default item -->
        <div class="p-3 mb-2 claim-item">
          <div class="form-row">
            <div class="form-group col-md-6 mb-2">
              <label class="small font-weight-bold mb-1">Service</label>
              <select class="form-control serviceSelect" required>
                <option value="">-- select service --</option>
                <option value="Consultation" data-price="200">Consultation (200 EGP)</option>
                <option value="Lab Tests" data-price="450">Lab Tests (450 EGP)</option>
                <option value="X-Ray" data-price="600">X-Ray (600 EGP)</option>
                <option value="Medication" data-price="300">Medication (300 EGP)</option>
                <option value="Physiotherapy" data-price="250">Physiotherapy (250 EGP)</option>
              </select>
            </div>

            <div class="form-group col-md-2 mb-2">
              <label class="small font-weight-bold mb-1">Qty</label>
              <input type="number" class="form-control qtyInput" value="1" min="1" required>
            </div>

            <div class="form-group col-md-3 mb-2">
              <label class="small font-weight-bold mb-1">Unit Price (EGP)</label>
              <input type="number" class="form-control priceInput" value="0" min="0" step="1" required>
            </div>

            <div class="form-group col-md-1 mb-2 d-flex align-items-end">
              <button type="button" class="btn btn-danger btn-sm w-100 removeItemBtn" title="Remove">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted"><i class="fas fa-lightbulb mr-1 text-warning"></i>Choose service to auto-fill price.</small>
            <span class="badge badge-light border p-2 badge-soft">
              Line Total: <b class="lineTotal">0</b> EGP
            </span>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end mt-2">
        <span class="badge badge-total p-3">
          <i class="fas fa-coins mr-1"></i> Total Requested:
          <span id="grandTotal">0</span> EGP
        </span>
      </div>

      <!-- Attachments -->
      <div class="claim-section-title mt-4">
        <span class="icon-pill bg-grad-pink"><i class="fas fa-paperclip"></i></span>
        <span>Attachments</span>
      </div>

      <div class="soft-block">
        <div class="custom-file">
          <input type="file" class="custom-file-input" id="claimFiles" multiple>
          <label class="custom-file-label" for="claimFiles">Choose files</label>
        </div>
        <small class="text-muted">Upload PDF / images of invoices, prescriptions, or reports.</small>
      </div>

      <!-- Consent -->
      <div class="mt-3 soft-block">
        <div class="custom-control custom-checkbox">
          <input type="checkbox" class="custom-control-input" id="consentCheck" required>
          <label class="custom-control-label font-weight-bold" for="consentCheck">
            I confirm the information is accurate and I agree to share it with insurance for review.
          </label>
        </div>
      </div>

      <!-- Submit -->
      <div class="mt-4 d-flex justify-content-end">
        <button class="btn btn-submit text-white" type="submit">
          <i class="fas fa-paper-plane mr-1"></i> Submit Claim Request
        </button>
      </div>

    </form>
  </div>
</div>
</footer>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>

</body>
</html>
<?php
if ($conn instanceof mysqli) { $conn->close(); }
?>

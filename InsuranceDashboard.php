<?php
/**
 * Insurance Dashboard - OOP Version - COMPLETE WITH CHARTS
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ .'/Auth.php';
require_once __DIR__ .'/Patient.php';
require_once __DIR__ .'/Insurance.php';
require_once __DIR__ .'/Validator.php';

$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

$auth->checkStaffAuth("INSURANCE_STAFF");

$insurance_id = (int)$auth->getSessionData("insurance_id");
if ($insurance_id <= 0) die("Missing insurance_id in session.");

$success_msg = "";
$error_msg   = "";

$insurance = new Insurance($conn);
$insurance->loadById($insurance_id);
$insurance_name = $insurance->getName();

$kpi_patients        = $insurance->getKPIPatients();
$kpi_policies_active = $insurance->getKPIActivePolicies();
$kpi_cases_month     = $insurance->getKPICasesThisMonth();
$kpi_pending_reviews = $insurance->getKPIPendingReviews();




/*
|--------------------------------------------------------------------------
| Insurance Prediction Section
|--------------------------------------------------------------------------
| Show ONLY the prediction for the logged-in company
*/
$prediction_map = [
    "AXA" => [
        "predicted_increase" => 20,
        "confidence" => 96.4,
        "period" => date('F'),
        "action" => "Review AXA pricing changes and prepare policyholder communication."
    ],
    "MetLife" => [
        "predicted_increase" => 14,
        "confidence" => 93.8,
        "period" => date('F'),
        "action" => "Review MetLife pricing changes and assess claim-cost drivers."
    ],
    "Bupa" => [
        "predicted_increase" => 18,
        "confidence" => 95.1,
        "period" => date('F'),
        "action" => "Prepare Bupa premium review and monitor cost escalation."
    ],
    "Allianz" => [
        "predicted_increase" => 12,
        "confidence" => 92.7,
        "period" => date('F'),
        "action" => "Assess Allianz pricing trend and notify relevant teams."
    ]
];

$current_prediction = $prediction_map[$insurance_name] ?? [
    "predicted_increase" => 0,
    "confidence" => 0,
    "period" => date('F'),
    "action" => "No prediction available yet for this insurance company."
];

// Handle ADD PATIENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add_patient") {
  $full_name   = trim($_POST["full_name"]   ?? "");
  $national_id = trim($_POST["national_id"] ?? "");
  $phone       = trim($_POST["phone"]       ?? "");
  $gender      = trim($_POST["gender"]      ?? "");
  $address     = trim($_POST["address"]     ?? "");
  if ($full_name===""||$national_id===""||$phone===""||$gender===""||$address==="") {
    $error_msg = "Please fill all fields.";
  } elseif (!Validator::validateNationalId($national_id)) {
    $error_msg = "National ID must be 14 digits.";
  } elseif (!Validator::validatePhone($phone)) {
    $error_msg = "Phone must be Egyptian format (010/011/012/015 + 8 digits).";
  } else {
    $check = $conn->prepare("SELECT patient_id FROM patients WHERE national_id=? LIMIT 1");
    $check->bind_param("s", $national_id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();
    if ($exists) {
      $error_msg = "Patient already exists (ID: ".(int)$exists["patient_id"].").";
    } else {
      $patient = new Patient($conn);
      $patient->setFullName($full_name);
      $patient->setNationalId($national_id);
      $patient->setPhone($phone);
      $patient->setGender($gender);
      $patient->setAddress($address);
      if ($patient->create(null, $insurance_id)) {
        header("Location: InsuranceDashboard.php#patients");
        exit;
      } else { $error_msg = "Failed to create patient."; }
    }
  }
}

$patient  = new Patient($conn);
$q        = trim($_GET["q"] ?? "");
$patients = $patient->getPatientsByInsurance($insurance_id, $q);

// ── QUERY 1: FRAUD DETECTION ─────────────────────────────────
$fraud_query = $conn->prepare("
    SELECT p.patient_id, p.full_name, p.national_id, p.phone,
           pp.policy_number, pp.status AS policy_status,
           mr.admission_count, mr.length_of_stay,
           mr.checkin_date, mr.checkout_date, mr.diagnosis,
           COUNT(c.claim_id)                                      AS total_claims,
           SUM(c.claim_amount)                                    AS total_claimed,
           COUNT(CASE WHEN c.claim_status='Pending' THEN 1 END)  AS pending_claims,
           DATEDIFF(mr.checkout_date, mr.checkin_date)            AS actual_stay
    FROM patients p
    LEFT JOIN patient_policy  pp ON p.patient_id = pp.patient_id
    LEFT JOIN medical_records mr ON p.patient_id = mr.patient_id
    LEFT JOIN claims          c  ON p.patient_id = c.patient_id AND c.insurance_id=?
    WHERE p.insurance_id=? AND mr.admission_count >= 3
    GROUP BY p.patient_id, mr.record_id
    ORDER BY mr.admission_count DESC, total_claimed DESC
");
$fraud_query->bind_param("ii", $insurance_id, $insurance_id);
$fraud_query->execute();
$fraud_patients = $fraud_query->get_result()->fetch_all(MYSQLI_ASSOC);
$fraud_query->close();

// ── QUERY 2: POLICY RENEWAL PRICING ──────────────────────────
$renewal_query = $conn->prepare("
    SELECT p.patient_id, p.full_name, p.gender,
           pp.policy_number, pp.end_date, pp.status AS policy_status,
           ip.plan_name,
           DATEDIFF(pp.end_date, CURDATE()) AS days_until_expiry,
           mr.risk_score,
           mr.has_diabetes, mr.has_hypertension,
           mr.has_kidney_disease, mr.has_heart_disease,
           mr.admission_count, mr.avg_length_stay,
           (mr.has_diabetes + mr.has_hypertension +
            mr.has_kidney_disease + mr.has_heart_disease) AS chronic_count,
           SUM(c.claim_amount) AS total_claimed,
           COUNT(c.claim_id)   AS claim_count
    FROM patients p
    JOIN  patient_policy  pp  ON p.patient_id         = pp.patient_id
    JOIN  insurance_plan  ip  ON pp.insurance_plan_id = ip.id
    LEFT JOIN medical_records mr ON p.patient_id      = mr.patient_id
    LEFT JOIN claims          c  ON p.patient_id      = c.patient_id AND c.insurance_id=?
    WHERE p.insurance_id=?
      AND pp.status='active'
      AND pp.end_date IS NOT NULL
      AND DATEDIFF(pp.end_date, CURDATE()) <= 90
    GROUP BY p.patient_id, mr.record_id
    ORDER BY mr.risk_score DESC, chronic_count DESC
");
$renewal_query->bind_param("ii", $insurance_id, $insurance_id);
$renewal_query->execute();
$renewal_patients = $renewal_query->get_result()->fetch_all(MYSQLI_ASSOC);
$renewal_query->close();

// Helper
function riskTier($score) {
    if ($score===null||$score==0) return ['Unknown','secondary'];
    if ($score>=0.7) return ['High Risk','danger'];
    if ($score>=0.4) return ['Medium Risk','warning'];
    return ['Low Risk','success'];
}

// ── Pre-build JS chart data ───────────────────────────────────
$fraud_names      = array_map(fn($r)=>$r['full_name'],                 $fraud_patients);
$fraud_admissions = array_map(fn($r)=>(int)$r['admission_count'],      $fraud_patients);
$fraud_claimed    = array_map(fn($r)=>(float)($r['total_claimed']??0), $fraud_patients);
$fraud_pending    = array_map(fn($r)=>(int)($r['pending_claims']??0),  $fraud_patients);

$ren_names = array_map(fn($r)=>$r['full_name'],                        $renewal_patients);
$ren_days  = array_map(fn($r)=>(int)($r['days_until_expiry']??0),      $renewal_patients);
$ren_risk  = array_map(fn($r)=>round((float)($r['risk_score']??0),4),  $renewal_patients);

// Renewal advice counts for pie
$raise_c = $review_c = $std_c = 0;
foreach ($renewal_patients as $rp) {
    [$tl] = riskTier((float)($rp['risk_score']??0));
    $ch = (int)($rp['chronic_count']??0);
    if ($tl==='High Risk'||$ch>=2)        $raise_c++;
    elseif ($tl==='Medium Risk'||$ch===1) $review_c++;
    else                                  $std_c++;
}

$high_risk_count   = $raise_c;
$total_renewal_exp = array_sum(array_column($renewal_patients,'total_claimed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Insurance Dashboard</title>
  <link href="css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    .anchor-offset{scroll-margin-top:90px}
    .dash-title{font-weight:800;letter-spacing:.2px}
    .kpi-card{border-radius:12px}
    .kpi-label{font-size:.72rem;font-weight:800;letter-spacing:.6px;text-transform:uppercase}
    .kpi-value{font-size:1.25rem;font-weight:800}
    .chart-wrap{background:#fff;border-radius:10px;padding:14px 12px 10px;box-shadow:0 2px 10px rgba(0,0,0,.07)}
    .insight-box{border-radius:8px;padding:11px 15px;font-size:.84rem}
    .leg-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:4px}
    .accent-fraud{border-top:4px solid #e74c3c!important}
    .accent-renew{border-top:4px solid #8e44ad!important}
    .table-sm td,.table-sm th{padding:.42rem .6rem}
  
        .anchor-offset { scroll-margin-top: 90px; }
        .dash-title { font-weight: 800; letter-spacing: .2px; }
        .kpi-card { border-radius: 12px; }
        .kpi-label { font-size: .72rem; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; }
        .kpi-value { font-size: 1.25rem; font-weight: 800; }

        .insurance-alert-banner {
            border-left: 5px solid #e74a3b;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 .15rem 1rem 0 rgba(58,59,69,.08);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
        }

        .insurance-alert-title {
            font-weight: 800;
            color: #e74a3b;
            margin-bottom: .25rem;
        }

        .insurance-alert-subtitle {
            color: #6c757d;
            margin-bottom: 0;
        }

        .insurance-prediction-count {
            background: #e74a3b;
            color: #fff;
            border-radius: 999px;
            padding: .65rem 1rem;
            font-weight: 700;
            font-size: .85rem;
            white-space: nowrap;
        }

        .prediction-card {
            border-left: 5px solid #f6c23e;
            border-radius: 12px;
            box-shadow: 0 .15rem 1rem 0 rgba(58,59,69,.08);
        }

        .prediction-card h5 {
            color: #f6a800;
            font-weight: 800;
        }

        .confidence-badge {
            display: inline-block;
            background: #e74a3b;
            color: #fff;
            padding: .15rem .5rem;
            border-radius: .35rem;
            font-size: .75rem;
            font-weight: 700;
        }

        .prediction-action-box {
            background: #fff3cd;
            border-radius: .5rem;
            padding: .85rem 1rem;
            color: #856404;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .prediction-meta p {
            margin-bottom: .55rem;
            color: #5a5c69;
        }

        .prediction-meta strong {
            color: #4e5361;
        }

        .prediction-list {
            margin-bottom: 0;
            padding-left: 1.2rem;
            color: #6c757d;
        }

        .prediction-list li {
            margin-bottom: .4rem;
        }
  
  </style>
</head>
<body id="page-top" class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="InsuranceDashboard.php">
      <i class="fas fa-shield-alt mr-2"></i>
      <strong><?= Validator::sanitizeInput($insurance_name) ?> Dashboard</strong>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topNavbar">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item"><a class="nav-link" href="#dashboard"><i class="fas fa-tachometer-alt mr-1"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="#patients"><i class="fas fa-users mr-1"></i> Patients</a></li>
        <li class="nav-item"><a class="nav-link" href="#addPatient"><i class="fas fa-user-plus mr-1"></i> Add Patient</a></li>
        <li class="nav-item"><a class="nav-link" href="#claimManagement"><i class="fas fa-file-medical mr-1"></i> Claims</a></li>
        <li class="nav-item"><a class="nav-link" href="#insuranceProfile"><i class="fas fa-building mr-1"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="Policy.php"><i class="fas fa-file-alt mr-1"></i> Policy</a></li>
        <li class="nav-item"><a class="nav-link" href="#riskAnalysis"><i class="fas fa-brain mr-1"></i> Risk Analysis</a></li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-toggle="dropdown">
            <span class="mr-2 d-none d-lg-inline text-white small">
              <?= Validator::sanitizeInput($auth->getSessionData("staff_name") ?? "Insurance Staff") ?>
            </span>
            <i class="fas fa-user-circle fa-2x text-white"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-right shadow">
            <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i> Logout</a>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid mt-4">

  <!-- KPIs -->
  <div id="dashboard" class="anchor-offset mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <h5 class="dash-title text-gray-800 mb-0"><i class="fas fa-chart-pie mr-2 text-success"></i> Dashboard Overview</h5>
      <span class="badge badge-light"><i class="fas fa-calendar-alt mr-1"></i> <?= date('F Y') ?></span>
    </div>
    <div class="row">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success shadow-sm kpi-card"><div class="card-body">
          <div class="kpi-label text-success">Total Patients</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_patients ?></div>
        </div></div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary shadow-sm kpi-card"><div class="card-body">
          <div class="kpi-label text-primary">Policies Active</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_policies_active ?></div>
        </div></div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info shadow-sm kpi-card"><div class="card-body">
          <div class="kpi-label text-info">Claims This Month</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_cases_month ?></div>
        </div></div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow-sm kpi-card"><div class="card-body">
          <div class="kpi-label text-warning">Pending Reviews</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_pending_reviews ?></div>
        </div></div>
      </div>
    </div>
  </div>

  <!-- ALERTS -->
  <?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $success_msg ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= Validator::sanitizeInput($error_msg) ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
  <?php endif; ?>

  <!-- ADD PATIENT + PATIENTS TABLE -->
  <div class="row">
    <div class="col-lg-5 mb-4" id="addPatient">
      <div class="card shadow h-100">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
          <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-user-plus mr-2"></i> Add Patient (<?= Validator::sanitizeInput($insurance_name) ?>)</h6>
          <span class="badge badge-light">ID: <?= (int)$insurance_id ?></span>
        </div>
        <div class="card-body">
          <form method="POST" action="InsuranceDashboard.php#addPatient">
            <input type="hidden" name="action" value="add_patient">
            <div class="form-group"><label>Full Name</label><input class="form-control" name="full_name" required></div>
            <div class="form-group"><label>National ID (14 digits)</label><input class="form-control" name="national_id" maxlength="14" required></div>
            <div class="form-group"><label>Phone (Egypt)</label><input class="form-control" name="phone" placeholder="010xxxxxxxx" required></div>
            <div class="form-group">
              <label>Gender</label>
              <select class="form-control" name="gender" required>
                <option value="">Select</option><option value="M">Male</option><option value="F">Female</option>
              </select>
            </div>
            <div class="form-group"><label>Assigned Insurance</label><input class="form-control" value="<?= Validator::sanitizeInput($insurance_name) ?>" readonly></div>
            <div class="form-group"><label>Address</label><input class="form-control" name="address" required></div>
            <button class="btn btn-success btn-block" type="submit"><i class="fas fa-save mr-1"></i> Save Patient</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7 mb-4 anchor-offset" id="patients">
      <div class="card shadow h-100">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
          <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-users mr-2"></i> Patients (<?= Validator::sanitizeInput($insurance_name) ?>)</h6>
          <form class="d-flex mt-2 mt-md-0" method="GET" action="InsuranceDashboard.php#patients" style="gap:8px;flex:1;max-width:500px;">
            <input class="form-control form-control-lg" name="q" value="<?= Validator::sanitizeInput($q) ?>" placeholder="Search by name, national ID, or phone" style="font-size:.95rem;">
            <button class="btn btn-success btn-lg" type="submit" style="min-width:50px;"><i class="fas fa-search"></i></button>
          </form>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
              <thead class="thead-light">
                <tr><th>ID</th><th>Name</th><th>National ID</th><th>Phone</th><th>Gender</th><th>Plan</th><th>Status</th><th>Policy #</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php if (!$patients): ?>
                  <tr><td colspan="9" class="text-center text-muted">No patients found.</td></tr>
                <?php else: foreach ($patients as $p): ?>
                  <tr>
                    <td><?= (int)$p["patient_id"] ?></td>
                    <td><?= Validator::sanitizeInput($p["full_name"]) ?></td>
                    <td><?= Validator::sanitizeInput($p["national_id"]) ?></td>
                    <td><?= Validator::sanitizeInput($p["phone"]) ?></td>
                    <td><?= Validator::sanitizeInput($p["gender"]) ?></td>
                    <td><?= Validator::sanitizeInput($p["plan_name"]??"-") ?></td>
                    <td>
                      <?php $st=$p["status"]??"";
                        if($st==="active") echo '<span class="badge badge-success">active</span>';
                        elseif($st==="suspended") echo '<span class="badge badge-warning">suspended</span>';
                        elseif($st==="expired") echo '<span class="badge badge-secondary">expired</span>';
                        else echo '<span class="badge badge-light">no policy</span>';
                      ?>
                    </td>
                    <td><?= Validator::sanitizeInput($p["policy_number"]??"-") ?></td>
                    <td style="white-space:nowrap;">
                      <a class="btn btn-sm btn-outline-success" href="AddPatientPolicy.php?patient_id=<?= (int)$p["patient_id"] ?>">
                        <i class="fas fa-plus"></i> Add Policy
                      </a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CLAIMS -->
  <div class="row">
    <div class="col-xl-6 col-md-6 mb-4 anchor-offset" id="claimManagement">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-header font-weight-bold text-primary"><i class="fas fa-file-medical mr-2"></i> Claim Management</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered mb-0">
              <thead class="thead-light"><tr><th>Claim ID</th><th>Patient Name</th><th>Hospital</th><th>Amount</th><th>Status</th></tr></thead>
              <tbody>
                <tr><td>CL001</td><td>John Doe</td><td>City Hospital</td><td>EGP 5000</td><td><span class="badge badge-warning">Under Review</span></td></tr>
                <tr><td>CL002</td><td>Jane Smith</td><td>Metro Clinic</td><td>EGP 12000</td><td><span class="badge badge-success">Approved</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-6 col-md-6 mb-4 anchor-offset" id="claimDecision">
      <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-header font-weight-bold text-warning"><i class="fas fa-gavel mr-2"></i> Claim Decision Processing</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
              <thead class="thead-light"><tr><th>Claim ID</th><th>Status</th><th>Reason</th><th>Updated At</th></tr></thead>
              <tbody>
                <tr><td>CL001</td><td><span class="badge badge-warning">Under Review</span></td><td>-</td><td>2026-01-14 09:00</td></tr>
                <tr><td>CL003</td><td><span class="badge badge-danger">Rejected</span></td><td>More documents needed</td><td>2026-01-12 15:30</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- INSURANCE PROFILE -->
  <div class="row">
    <div class="col-12 mb-4 anchor-offset" id="insuranceProfile">
      <div class="card border-left-secondary shadow py-2">
        <div class="card-header font-weight-bold text-secondary"><i class="fas fa-building mr-2"></i> Insurance Platform Profile</div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-info-circle mr-2"></i>Basic Information</h6>
              <p class="mb-2"><strong>Insurance Provider:</strong> <?= Validator::sanitizeInput($insurance_name) ?></p>
              <p class="mb-2"><strong>Insurance ID:</strong> <?= (int)$insurance_id ?></p>
              <p class="mb-2"><strong>Regulatory License:</strong> FRA-MED-2024-<?= str_pad($insurance_id,4,'0',STR_PAD_LEFT) ?></p>
              <p class="mb-2"><strong>Registration Date:</strong> January 2024</p>
            </div>
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-clipboard-list mr-2"></i>Available Plans</h6>
              <p class="mb-1"><strong>Normal - Individual:</strong> Essential health coverage</p>
              <p class="mb-1"><strong>Normal - Company:</strong> Group corporate coverage</p>
              <p class="mb-1"><strong>VIP - Individual:</strong> Premium private hospital access</p>
              <p class="mb-1"><strong>VIP - Company:</strong> Elite corporate benefits</p>
            </div>
          </div>
          <hr>
          <div class="row">
            <div class="col-md-6">
              <h6 class="text-success mb-3"><i class="fas fa-hospital mr-2"></i>Network Hospitals</h6>
              <p class="mb-1">• El Shifa Hospital</p><p class="mb-1">• Cleopatra Hospital</p>
              <p class="mb-1">• Air Force Hospital</p><p class="mb-1">• Nasaeem Hospital</p>
            </div>
            <div class="col-md-6">
              <h6 class="text-warning mb-3"><i class="fas fa-medkit mr-2"></i>Services Covered</h6>
              <p class="mb-1">✓ <strong>Checkup:</strong> 100% up to EGP 10,000</p>
              <p class="mb-1">✓ <strong>Operations:</strong> 80% up to EGP 1,000,000</p>
              <p class="mb-1">✓ <strong>Maternity:</strong> 70% up to EGP 50,000</p>
              <p class="mb-1">✓ <strong>Dental:</strong> 60% up to EGP 30,000</p>
              <p class="mb-1">✓ <strong>Optical:</strong> 50% up to EGP 20,000</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card prediction-card h-100">
    <div class="card-body">
        <h5 class="mb-3">
            <i class="fas fa-exclamation-circle mr-2"></i>
            ALERT: <?= Validator::sanitizeInput($insurance_name) ?> Premium Increase
        </h5>

        <div class="prediction-meta">
            <p><strong>Month:</strong> <?= Validator::sanitizeInput($current_prediction["period"]) ?></p>
            <p>
                <strong>Confidence:</strong>
                <span class="confidence-badge">
                    <?= number_format((float)$current_prediction["confidence"], 1) ?>%
                </span>
            </p>
            <p><strong>Predicted Increase:</strong>
                <?= (int)$current_prediction["predicted_increase"] ?>%
            </p>
        </div>

        <div class="prediction-action-box">
            <strong>Action:</strong>
            <?= Validator::sanitizeInput($current_prediction["action"]) ?>
        </div>
    </div>
</div>

  <!-- ═══════════════════════════════════════════════════════
       SMART RISK ANALYSIS
       ═══════════════════════════════════════════════════════ -->
  <div id="riskAnalysis" class="anchor-offset mb-3">
    <div class="d-flex align-items-center mb-3" style="gap:12px;">
      <div style="width:5px;height:42px;background:linear-gradient(180deg,#e74c3c,#8e44ad);border-radius:3px;"></div>
      <div>
        <h5 class="mb-0 font-weight-bold text-gray-800"><i class="fas fa-brain mr-2 text-danger"></i>Smart Risk Analysis</h5>
        <small class="text-muted">Fraud Detection &nbsp;·&nbsp; Policy Renewal Pricing</small>
      </div>
    </div>

    

    <!-- ══ SECTION 1: FRAUD DETECTION ══════════════════════ -->
    <div class="card shadow mb-4 accent-fraud anchor-offset" id="fraudDetection">
      <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap"
           style="background:linear-gradient(135deg,#fff5f5,#fff);">
        <div class="d-flex align-items-center" style="gap:10px;">
          <div style="width:38px;height:38px;background:#e74c3c;border-radius:9px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-exclamation-triangle text-white"></i>
          </div>
          <div>
            <h6 class="m-0 font-weight-bold text-danger">🚨 Fraud Detection Flags</h6>
            <small class="text-muted">Patients with repeated admissions (≥3) — possible system abuse</small>
          </div>
        </div>
        <span class="badge badge-danger badge-pill px-3 py-2"><?= count($fraud_patients) ?> Flagged</span>
      </div>

      <?php if (!$fraud_patients): ?>
        <div class="card-body text-center text-muted py-4">
          <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>No fraud flags detected.
        </div>
      <?php else: ?>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6 mb-3">
            <div class="chart-wrap">
              <div class="mb-2"><span class="leg-dot" style="background:#e74c3c;"></span><strong style="font-size:.8rem;color:#555;">Admissions Count per Patient</strong></div>
              <div style="position:relative;height:210px;"><canvas id="chartFraudAdmissions"></canvas></div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="chart-wrap">
              <div class="mb-2">
                <span class="leg-dot" style="background:#c0392b;"></span><small style="color:#555;">Claimed (EGP)</small>&nbsp;&nbsp;
                <span class="leg-dot" style="background:#f39c12;"></span><small style="color:#555;">Pending Claims</small>
              </div>
              <div style="position:relative;height:210px;"><canvas id="chartFraudClaimed"></canvas></div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0" style="font-size:.87rem;">
            <thead style="background:#fff0f0;">
              <tr>
                <th class="pl-3">Patient</th><th>National ID</th><th>Policy #</th>
                <th class="text-center">Admissions</th><th class="text-center">Stay</th>
                <th class="text-center">Claims</th><th class="text-right">Claimed (EGP)</th>
                <th class="text-center">Pending</th><th class="text-center">Diagnosis</th><th class="text-center">Flag</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($fraud_patients as $fp):
                $adm  = (int)($fp['admission_count']??0);
                $flag = $adm>=6?['CRITICAL','danger']:($adm>=4?['HIGH','warning']:['MODERATE','info']);
              ?>
              <tr>
                <td class="pl-3 font-weight-bold"><?= Validator::sanitizeInput($fp['full_name']) ?></td>
                <td class="text-muted"><?= Validator::sanitizeInput($fp['national_id']) ?></td>
                <td><?= Validator::sanitizeInput($fp['policy_number']??'—') ?></td>
                <td class="text-center"><strong class="text-danger"><?= $adm ?></strong></td>
                <td class="text-center"><?= (int)($fp['length_of_stay']??0) ?>d</td>
                <td class="text-center"><?= (int)($fp['total_claims']??0) ?></td>
                <td class="text-right font-weight-bold"><?= number_format((float)($fp['total_claimed']??0),2) ?></td>
                <td class="text-center">
                  <?php if((int)($fp['pending_claims']??0)>0): ?>
                    <span class="badge badge-warning"><?= (int)$fp['pending_claims'] ?></span>
                  <?php else: echo '—'; endif; ?>
                </td>
                <td class="text-center"><span class="badge badge-light"><?= Validator::sanitizeInput($fp['diagnosis']??'—') ?></span></td>
                <td class="text-center"><span class="badge badge-<?= $flag[1] ?>"><?= $flag[0] ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="insight-box mt-3" style="background:#fff8f8;border-left:4px solid #e74c3c;">
          <strong class="text-danger"><i class="fas fa-lightbulb mr-1"></i>Action:</strong>
          Patients with ≥6 admissions should have claims placed on hold pending a full case audit.
          Cross-check checkin/checkout dates for overlap or unusual patterns.
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══ SECTION 2: POLICY RENEWAL PRICING ═══════════════ -->
    <div class="card shadow mb-4 accent-renew anchor-offset" id="policyRenewal">
      <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap"
           style="background:linear-gradient(135deg,#fdf8ff,#fff);">
        <div class="d-flex align-items-center" style="gap:10px;">
          <div style="width:38px;height:38px;background:#8e44ad;border-radius:9px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-file-contract text-white"></i>
          </div>
          <div>
            <h6 class="m-0 font-weight-bold" style="color:#8e44ad;">📋 Policy Renewal Pricing</h6>
            <small class="text-muted">Active policies expiring within 90 days — ranked by risk score</small>
          </div>
        </div>
        <span class="badge badge-pill px-3 py-2" style="background:#8e44ad;color:#fff;"><?= count($renewal_patients) ?> Expiring Soon</span>
      </div>

      <?php if (!$renewal_patients): ?>
        <div class="card-body text-center text-muted py-4">
          <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>No policies expiring within 90 days.
        </div>
      <?php else: ?>
      <div class="card-body">
        <!-- Mini KPI summary -->
        <div class="row mb-4">
          <div class="col-md-4">
            <div class="p-3 rounded text-center" style="background:#fdf5ff;border:1px solid #e8d5f5;">
              <div style="font-size:1.6rem;font-weight:800;color:#8e44ad;"><?= count($renewal_patients) ?></div>
              <div style="font-size:.75rem;color:#888;">Total Expiring Policies</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 rounded text-center" style="background:#fff5f5;border:1px solid #f5c6cb;">
              <div style="font-size:1.6rem;font-weight:800;color:#e74c3c;"><?= $high_risk_count ?></div>
              <div style="font-size:.75rem;color:#888;">High Risk — Need Premium Raise</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 rounded text-center" style="background:#f8f9fa;border:1px solid #dee2e6;">
              <div style="font-size:1.3rem;font-weight:800;color:#333;">EGP <?= number_format($total_renewal_exp,0) ?></div>
              <div style="font-size:.75rem;color:#888;">Total Claimed by These Patients</div>
            </div>
          </div>
        </div>

        <!-- Charts row -->
        <div class="row mb-3">
          <div class="col-md-5 mb-3">
            <div class="chart-wrap">
              <div class="mb-2"><span class="leg-dot" style="background:#8e44ad;"></span><strong style="font-size:.8rem;color:#555;">Risk Score per Patient</strong></div>
              <div style="position:relative;height:210px;"><canvas id="chartRenewRisk"></canvas></div>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="chart-wrap">
              <div class="mb-2"><span class="leg-dot" style="background:#3498db;"></span><strong style="font-size:.8rem;color:#555;">Days Until Policy Expiry</strong></div>
              <div style="position:relative;height:210px;"><canvas id="chartRenewDays"></canvas></div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="chart-wrap h-100">
              <div class="mb-2"><strong style="font-size:.8rem;color:#555;">Renewal Advice Split</strong></div>
              <div style="position:relative;height:210px;"><canvas id="chartRenewPie"></canvas></div>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0" style="font-size:.87rem;">
            <thead style="background:#fdf5ff;">
              <tr>
                <th class="pl-3">Patient</th><th>Policy #</th><th>Plan</th>
                <th class="text-center">Expires In</th><th class="text-center">Risk Score</th>
                <th class="text-center">Chronic Conditions</th>
                <th class="text-center">Admissions</th><th class="text-right">Total Claimed</th>
                <th class="text-center">Renewal Advice</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($renewal_patients as $rp):
                [$tierLabel,$tierColor] = riskTier((float)($rp['risk_score']??0));
                $days    = (int)($rp['days_until_expiry']??0);
                $chronic = (int)($rp['chronic_count']??0);
                $conds   = [];
                if(!empty($rp['has_diabetes']))       $conds[]='<span class="badge badge-danger mr-1">Diabetes</span>';
                if(!empty($rp['has_hypertension']))   $conds[]='<span class="badge badge-warning mr-1">Hypertension</span>';
                if(!empty($rp['has_kidney_disease'])) $conds[]='<span class="badge badge-info mr-1">Kidney</span>';
                if(!empty($rp['has_heart_disease']))  $conds[]='<span class="badge badge-dark mr-1">Heart</span>';
                if($tierLabel==='High Risk'||$chronic>=2)        $advice=['Raise Premium +20%','danger'];
                elseif($tierLabel==='Medium Risk'||$chronic===1) $advice=['Review & Adjust +10%','warning'];
                else                                             $advice=['Standard Renewal','success'];
                $dc=$days<=30?'danger':($days<=60?'warning':'info');
              ?>
              <tr>
                <td class="pl-3 font-weight-bold"><?= Validator::sanitizeInput($rp['full_name']) ?></td>
                <td><?= Validator::sanitizeInput($rp['policy_number']??'—') ?></td>
                <td><?= Validator::sanitizeInput($rp['plan_name']??'—') ?></td>
                <td class="text-center"><span class="badge badge-<?= $dc ?>"><?= $days ?>d</span></td>
                <td class="text-center">
                  <?php if(!empty($rp['risk_score'])): ?>
                    <span class="badge badge-<?= $tierColor ?>"><?= $tierLabel ?></span>
                    <br><small class="text-muted"><?= round((float)$rp['risk_score'],4) ?></small>
                  <?php else: echo '<span class="text-muted">No data</span>'; endif; ?>
                </td>
                <td class="text-center"><?= $conds?implode('',$conds):'<span class="text-muted">None</span>' ?></td>
                <td class="text-center"><?= (int)($rp['admission_count']??0) ?></td>
                <td class="text-right font-weight-bold">EGP <?= number_format((float)($rp['total_claimed']??0),2) ?></td>
                <td class="text-center">
                  <span class="badge badge-<?= $advice[1] ?> px-2"><i class="fas fa-tag mr-1"></i><?= $advice[0] ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="insight-box mt-3" style="background:#fdf8ff;border-left:4px solid #8e44ad;">
          <strong style="color:#8e44ad;"><i class="fas fa-lightbulb mr-1"></i>Action:</strong>
          Patients flagged <strong>"Raise Premium +20%"</strong> have high risk scores or multiple chronic conditions —
          adjust renewal rates before expiry.
          <strong>"Standard Renewal"</strong> patients can be auto-renewed without manual review.
        </div>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /riskAnalysis -->

</div><!-- /container-fluid -->

<footer class="sticky-footer bg-white mt-4">
  <div class="container my-auto">
    <div class="copyright text-center my-auto"><span>SmartConnect © 2026</span></div>
  </div>
</footer>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
const fraudNames      = <?= json_encode(array_values($fraud_names)) ?>;
const fraudAdmissions = <?= json_encode(array_values($fraud_admissions)) ?>;
const fraudClaimed    = <?= json_encode(array_values($fraud_claimed)) ?>;
const fraudPending    = <?= json_encode(array_values($fraud_pending)) ?>;

const renNames = <?= json_encode(array_values($ren_names)) ?>;
const renDays  = <?= json_encode(array_values($ren_days)) ?>;
const renRisk  = <?= json_encode(array_values($ren_risk)) ?>;

const raiseCount    = <?= (int)$raise_c ?>;
const reviewCount   = <?= (int)$review_c ?>;
const standardCount = <?= (int)$std_c ?>;

Chart.defaults.font.family = "'Nunito', sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#666';

const short = names => names.map(n => n.split(' ').slice(0,2).join(' '));

function makeChart(id, config) {
  const el = document.getElementById(id);
  if (el && el.getContext) new Chart(el, config);
}



// Chart 1A: Fraud admissions
makeChart('chartFraudAdmissions', {
  type: 'bar',
  data: {
    labels: short(fraudNames),
    datasets: [{
      label: 'Admissions',
      data: fraudAdmissions,
      backgroundColor: fraudAdmissions.map(v =>
        v>=6?'rgba(192,57,43,.85)':v>=4?'rgba(231,76,60,.75)':'rgba(231,76,60,.45)'),
      borderColor: '#c0392b', borderWidth: 1, borderRadius: 5
    }]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{display:false} },
    scales:{
      y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'#f0f0f0'}},
      x:{grid:{display:false}}
    }
  }
});

// Chart 1B: Fraud claimed + pending
makeChart('chartFraudClaimed', {
  type: 'bar',
  data: {
    labels: short(fraudNames),
    datasets: [
      { label:'Claimed (EGP)', data:fraudClaimed, backgroundColor:'rgba(192,57,43,.7)',
        borderRadius:5, yAxisID:'y' },
      { label:'Pending Claims', data:fraudPending, backgroundColor:'rgba(243,156,18,.8)',
        borderRadius:5, yAxisID:'y1' }
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{position:'top',labels:{boxWidth:10}} },
    scales:{
      y: {beginAtZero:true,position:'left', grid:{color:'#f0f0f0'},title:{display:true,text:'EGP'}},
      y1:{beginAtZero:true,position:'right',grid:{drawOnChartArea:false},ticks:{stepSize:1},title:{display:true,text:'Count'}},
      x: {grid:{display:false}}
    }
  }
});

// Chart 2A: Renewal risk score
makeChart('chartRenewRisk', {
  type: 'bar',
  data: {
    labels: short(renNames),
    datasets: [{
      label:'Risk Score',
      data: renRisk,
      backgroundColor: renRisk.map(v =>
        v>=0.7?'rgba(192,57,43,.8)':v>=0.4?'rgba(243,156,18,.8)':'rgba(39,174,96,.7)'),
      borderRadius:5
    }]
  },
  options: {
    indexAxis:'y',
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{display:false} },
    scales:{
      x:{min:0,max:1,grid:{color:'#f0f0f0'},ticks:{callback:v=>(v*100)+'%'}},
      y:{grid:{display:false}}
    }
  }
});

// Chart 2B: Days until expiry
makeChart('chartRenewDays', {
  type: 'bar',
  data: {
    labels: short(renNames),
    datasets: [{
      label:'Days Until Expiry',
      data: renDays,
      backgroundColor: renDays.map(v =>
        v<=30?'rgba(192,57,43,.8)':v<=60?'rgba(243,156,18,.8)':'rgba(52,152,219,.7)'),
      borderRadius:5
    }]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{display:false} },
    scales:{
      y:{beginAtZero:true,grid:{color:'#f0f0f0'},title:{display:true,text:'Days'}},
      x:{grid:{display:false}}
    }
  }
});

// Chart 2C: Renewal advice doughnut
makeChart('chartRenewPie', {
  type: 'doughnut',
  data: {
    labels: ['Raise +20%','Adjust +10%','Standard'],
    datasets: [{
      data: [raiseCount, reviewCount, standardCount],
      backgroundColor:['rgba(192,57,43,.85)','rgba(243,156,18,.85)','rgba(39,174,96,.8)'],
      borderWidth:2, borderColor:'#fff'
    }]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    cutout:'60%',
    plugins:{
      legend:{position:'bottom',labels:{boxWidth:10,padding:8}},
      tooltip:{callbacks:{label:ctx=>` ${ctx.label}: ${ctx.raw} patients`}}
    }
  }
});
</script>
</body>
</html>
<?php $db->close(); ?>
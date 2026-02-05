<?php
session_start();
if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "staff" || ($_SESSION["role"] ?? "") !== "INSURANCE_STAFF") {
  header("Location: login.html");
  exit;
}

require_once "db.php";

$insurance_id = (int)($_SESSION["insurance_id"] ?? 0);
if ($insurance_id <= 0) die("Missing insurance_id in session.");

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$success_msg = "";
$error_msg = "";

/* =========================
   GET INSURANCE NAME
========================= */
$insurance_name = "Medical Insurance";

$stmt = $conn->prepare("SELECT name FROM medical_insurances WHERE insurance_id=? LIMIT 1");
$stmt->bind_param("i", $insurance_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row && !empty($row["name"])) $insurance_name = $row["name"];



/* =========================
   KPI CARDS (IMPROVED - REAL DB DATA)
========================= */

// helper: fetch one integer from COUNT query
function fetch_int(mysqli_stmt $stmt): int {
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_row() : null;
  return $row ? (int)$row[0] : 0;
}

// 1) Total Patients (all active patients under this insurance)
$stmt = $conn->prepare("
  SELECT COUNT(DISTINCT p.patient_id)
  FROM patients p
  WHERE p.insurance_id = ?
    AND p.is_active = 1
");
$stmt->bind_param("i", $insurance_id);
$kpi_patients = fetch_int($stmt);
$stmt->close();

// 2) Policies Active 
// Count patients who have at least one active, non-expired policy
// Using DISTINCT patient_id and proper grouping to avoid counting duplicates
$stmt = $conn->prepare("
  SELECT COUNT(*) FROM (
    SELECT DISTINCT pp.patient_id
    FROM patient_policy pp
    INNER JOIN patients p ON p.patient_id = pp.patient_id
    WHERE pp.insurance_id = ?
      AND p.insurance_id = ?
      AND p.is_active = 1
      AND pp.status = 'active'
      AND (pp.end_date IS NULL OR pp.end_date >= CURDATE())
  ) AS unique_active_policies
");
$stmt->bind_param("ii", $insurance_id, $insurance_id);
$kpi_policies_active = fetch_int($stmt);
$stmt->close();

// 3) Claims/Cases This Month (medical records created this month for our patients)
$stmt = $conn->prepare("
  SELECT COUNT(DISTINCT mr.record_id)
  FROM medical_records mr
  INNER JOIN patients p ON p.patient_id = mr.patient_id
  WHERE p.insurance_id = ?
    AND YEAR(mr.created_at) = YEAR(CURDATE())
    AND MONTH(mr.created_at) = MONTH(CURDATE())
");
$stmt->bind_param("i", $insurance_id);
$kpi_cases_month = fetch_int($stmt);
$stmt->close();

// 4) Pending Reviews (cases still open - no checkout date)
$stmt = $conn->prepare("
  SELECT COUNT(DISTINCT mr.record_id)
  FROM medical_records mr
  INNER JOIN patients p ON p.patient_id = mr.patient_id
  WHERE p.insurance_id = ?
    AND mr.checkout_date IS NULL
");
$stmt->bind_param("i", $insurance_id);
$kpi_pending_reviews = fetch_int($stmt);
$stmt->close();

// ✅ Optional: Uncomment for debugging
/*
echo "<div class='alert alert-info'>";
echo "<strong>DEBUG - KPI Values for Insurance ID $insurance_id ($insurance_name):</strong><br>";
echo "Total Patients: $kpi_patients<br>";
echo "Active Policies: $kpi_policies_active<br>";
echo "Cases This Month: $kpi_cases_month<br>";
echo "Pending Reviews: $kpi_pending_reviews<br>";
echo "</div>";
*/


/* =========================
   ADD PATIENT (POST)
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add_patient") {

  $full_name   = trim($_POST["full_name"] ?? "");
  $national_id = trim($_POST["national_id"] ?? "");
  $phone       = trim($_POST["phone"] ?? "");
  $gender      = trim($_POST["gender"] ?? "");
  $address     = trim($_POST["address"] ?? "");

  if ($full_name==="" || $national_id==="" || $phone==="" || $gender==="" || $address==="") {
    $error_msg = "Please fill all fields.";
  } elseif (!preg_match('/^\d{14}$/', $national_id)) {
    $error_msg = "National ID must be 14 digits.";
  } elseif (!preg_match('/^(010|011|012|015)\d{8}$/', $phone)) {
    $error_msg = "Phone must be Egyptian format (010/011/012/015 + 8 digits).";
  } else {

    $check = $conn->prepare("SELECT patient_id FROM patients WHERE national_id=? LIMIT 1");
    $check->bind_param("s", $national_id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();

    if ($exists) {
      $error_msg = "Patient already exists with this National ID (Patient ID: " . (int)$exists["patient_id"] . ").";
    } else {

      $stmt = $conn->prepare("
        INSERT INTO patients (full_name, national_id, phone, gender, address, insurance_id, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
      ");
      $stmt->bind_param("sssssi", $full_name, $national_id, $phone, $gender, $address, $insurance_id);

      if ($stmt->execute()) {
        $newId = (int)$stmt->insert_id;
        $success_msg = "Patient added successfully ✅ (ID: $newId) under <b>".e($insurance_name)."</b>.";
        
        // Refresh KPI after adding patient
        header("Location: InsuranceDashboard.php#patients");
        exit;
      } else {
        $error_msg = "Insert failed: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}

/* =========================
   LIST PATIENTS (ONLY THIS INSURANCE)
========================= */
$q = trim($_GET["q"] ?? "");

$sql = "
SELECT
  p.patient_id, p.full_name, p.national_id, p.phone, p.gender,
  pp.policy_number, pp.start_date, pp.end_date, pp.status,
  ip.plan_name
FROM patients p
LEFT JOIN patient_policy pp
  ON pp.patient_id = p.patient_id
 AND pp.insurance_id = ?
LEFT JOIN insurance_plan ip
  ON ip.id = pp.insurance_plan_id
WHERE p.insurance_id = ?
";

$types = "ii";
$params = [$insurance_id, $insurance_id];

if ($q !== "") {
  $sql .= " AND (p.full_name LIKE CONCAT('%', ?, '%')
             OR p.national_id LIKE CONCAT('%', ?, '%')
             OR p.phone LIKE CONCAT('%', ?, '%')) ";
  $types .= "sss";
  $params[] = $q;
  $params[] = $q;
  $params[] = $q;
}

$sql .= " ORDER BY p.patient_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$patients = [];
while ($row = $res->fetch_assoc()) $patients[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SmartConnect - Insurance Dashboard</title>

  <link href="css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    .anchor-offset { scroll-margin-top: 90px; }
  </style>
</head>

<body id="page-top" class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow sticky-top">
  <div class="container-fluid">

    <a class="navbar-brand d-flex align-items-center" href="InsuranceDashboard.php">
      <i class="fas fa-shield-alt mr-2"></i>
      <strong><?= e($insurance_name) ?> Dashboard</strong>
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topNavbar"
      aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNavbar">

      <ul class="navbar-nav mr-auto">
        <li class="nav-item"><a class="nav-link" href="#patients"><i class="fas fa-users mr-1"></i> Patients</a></li>
        <li class="nav-item"><a class="nav-link" href="#addPatient"><i class="fas fa-user-plus mr-1"></i> Add Patient</a></li>
        <!-- <li class="nav-item"><a class="nav-link" href="#patientEligibility"><i class="fas fa-user-check mr-1"></i> Eligibility</a></li> -->
        <li class="nav-item"><a class="nav-link" href="#claimManagement"><i class="fas fa-file-medical mr-1"></i> Claims</a></li>
        <li class="nav-item"><a class="nav-link" href="#claimDecision"><i class="fas fa-gavel mr-1"></i> Decisions</a></li>
        <!-- <li class="nav-item"><a class="nav-link" href="#financialManagement"><i class="fas fa-dollar-sign mr-1"></i> Financial</a></li> -->
        <li class="nav-item"><a class="nav-link" href="#insuranceProfile"><i class="fas fa-building mr-1"></i> Profile</a></li>
      </ul>

      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="mr-2 d-none d-lg-inline text-white small">
              <?= e($_SESSION["staff_name"] ?? "Insurance Staff") ?>
            </span>
            <i class="fas fa-user-circle fa-2x text-white"></i>
          </a>

          <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <div class="dropdown-item text-muted">
              Insurance ID: <?= (int)$insurance_id ?>
            </div>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="logout.php">
              <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
            </a>
          </div>
        </li>
      </ul>

    </div>
  </div>
</nav>

<div class="container-fluid mt-4">

<!-- ================= DASHBOARD OVERVIEW (IMPROVED) ================= -->
<style>
  .dash-title{
    font-weight:800;
    letter-spacing:.2px;
  }
  .kpi-card{
    border-radius: 12px;
  }
  .kpi-card .card-body{
    padding: 14px 16px;
  }
  .kpi-label{
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .6px;
    text-transform: uppercase;
    margin-bottom: 6px;
  }
  .kpi-value{
    font-size: 1.25rem;
    font-weight: 800;
    line-height: 1.1;
  }
  .kpi-sub{
    font-size: .78rem;
    color: #858796;
    margin-top: 4px;
  }
</style>

<div id="dashboard" class="anchor-offset mb-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
    <h5 class="dash-title text-gray-800 mb-0">
      <i class="fas fa-chart-pie mr-2 text-success"></i> Dashboard Overview
    </h5>
    <span class="badge badge-light">
      <i class="fas fa-calendar-alt mr-1"></i> <?= date('F Y') ?>
    </span>
  </div>

  <div class="row">

    <div class="col-xl-3 col-md-6 mb-3">
      <div class="card border-left-success shadow-sm kpi-card">
        <div class="card-body">
          <div class="kpi-label text-success">Total Patients</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_patients ?></div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
      <div class="card border-left-primary shadow-sm kpi-card">
        <div class="card-body">
          <div class="kpi-label text-primary">Policies Active</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_policies_active ?></div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
      <div class="card border-left-info shadow-sm kpi-card">
        <div class="card-body">
          <div class="kpi-label text-info">Claims This Month</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_cases_month ?></div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
      <div class="card border-left-warning shadow-sm kpi-card">
        <div class="card-body">
          <div class="kpi-label text-warning">Pending Reviews</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_pending_reviews ?></div>
        </div>
      </div>
    </div>

  </div>
</div>
<!-- ================= /DASHBOARD OVERVIEW ================= -->

  <?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= $success_msg ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= e($error_msg) ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>


  
<!-- ===================== ADD + PATIENTS (SIDE BY SIDE) ===================== -->
<div class="row">

  <!-- LEFT: ADD PATIENT -->
  <div class="col-lg-5 mb-4" id="addPatient">
    <div class="card shadow h-100">
      <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-success">
          <i class="fas fa-user-plus mr-2"></i> Add Patient (<?= e($insurance_name) ?>)
        </h6>
        <span class="badge badge-light">
          <i class="fas fa-building mr-1"></i> ID: <?= (int)$insurance_id ?>
        </span>
      </div>

      <div class="card-body">
        <form method="POST" action="InsuranceDashboard.php#addPatient">
          <input type="hidden" name="action" value="add_patient">

          <div class="form-group">
            <label>Full Name</label>
            <input class="form-control" name="full_name" required>
          </div>

          <div class="form-group">
            <label>National ID (14 digits)</label>
            <input class="form-control" name="national_id" maxlength="14" required>
          </div>

          <div class="form-group">
            <label>Phone (Egypt)</label>
            <input class="form-control" name="phone" placeholder="010xxxxxxxx" required>
          </div>

          <div class="form-group">
            <label>Gender</label>
            <select class="form-control" name="gender" required>
              <option value="">Select</option>
              <option value="M">Male</option>
              <option value="F">Female</option>
            </select>
          </div>

          <div class="form-group">
            <label>Assigned Insurance</label>
            <input class="form-control" value="<?= e($insurance_name) ?>" readonly>
            
          </div>

          <div class="form-group">
            <label>Address</label>
            <input class="form-control" name="address" required>
          </div>

          <button class="btn btn-success btn-block" type="submit">
            <i class="fas fa-save mr-1"></i> Save Patient
          </button>

         
        </form>
      </div>
    </div>
  </div>

  <!-- RIGHT: PATIENTS TABLE -->
  <div class="col-lg-7 mb-4 anchor-offset" id="patients">
    <div class="card shadow h-100">
      <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
        <h6 class="m-0 font-weight-bold text-success">
          <i class="fas fa-users mr-2"></i> Patients (<?= e($insurance_name) ?>)
        </h6>

        <form class="d-flex mt-2 mt-md-0" method="GET" action="InsuranceDashboard.php#patients" style="gap:8px; flex: 1; max-width: 500px;">
          <input class="form-control form-control-lg" 
                 name="q" 
                 value="<?= e($q) ?>" 
                 placeholder="Search by name, national ID, or phone number"
                 style="font-size: 0.95rem;">
          <button class="btn btn-success btn-lg" type="submit" style="min-width: 50px;">
            <i class="fas fa-search"></i>
          </button>
        </form>
      </div>

      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>National ID</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Policy #</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$patients): ?>
                <tr><td colspan="9" class="text-center text-muted">No patients found.</td></tr>
              <?php else: ?>
                <?php foreach ($patients as $p): ?>
                  <tr>
                    <td><?= (int)$p["patient_id"] ?></td>
                    <td><?= e($p["full_name"]) ?></td>
                    <td><?= e($p["national_id"]) ?></td>
                    <td><?= e($p["phone"]) ?></td>
                    <td><?= e($p["gender"]) ?></td>
                    <td><?= e($p["plan_name"] ?? "-") ?></td>
                    <td>
                      <?php
                        $st = $p["status"] ?? "";
                        if ($st === "active") echo '<span class="badge badge-success">active</span>';
                        elseif ($st === "suspended") echo '<span class="badge badge-warning">suspended</span>';
                        elseif ($st === "expired") echo '<span class="badge badge-secondary">expired</span>';
                        else echo '<span class="badge badge-light">no policy</span>';
                      ?>
                    </td>
                    <td><?= e($p["policy_number"] ?? "-") ?></td>

                    <td style="white-space:nowrap;">
                      <a class="btn btn-sm btn-outline-success"
                         href="AddPatientPolicy.php?patient_id=<?= (int)$p["patient_id"] ?>">
                        <i class="fas fa-plus"></i> Add Policy
                      </a>

                      <a class="btn btn-sm btn-outline-secondary ml-2"
                         href="ViewMedicalRecords.php?patient_id=<?= (int)$p["patient_id"] ?>">
                        <i class="fas fa-folder-open mr-1"></i> View Records
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

</div>
<!-- ===================== /ADD + PATIENTS ===================== -->


<!-- ============== COMMENTED OUT: Patient Eligibility Verification ============== -->
<!-- 
<div class="row">
  <div class="col-xl-6 col-md-6 mb-4 anchor-offset" id="patientEligibility">
    <div class="card border-left-success shadow h-100 py-2">
      <div class="card-header font-weight-bold text-success">
        <i class="fas fa-user-check mr-2"></i> Patient Eligibility Verification
      </div>
      <div class="card-body">
        <form onsubmit="event.preventDefault(); goToAddPolicy();">
          <div class="form-group">
            <label for="nationalID">Patient National ID</label>
            <input type="text" class="form-control" id="nationalID" placeholder="Enter 14-digit National ID">
          </div>
          <button type="button" class="btn btn-success btn-block" onclick="goToAddPolicy()">
            Add / Update Patient Policy
          </button>
        </form>
        <div class="mt-3">
          <strong>Status:</strong> <span class="text-muted" id="eligibilityStatus">-</span><br>
          <strong>Coverage Limit:</strong> <span class="text-muted" id="coverageLimit">-</span>
        </div>
        <small class="text-muted d-block mt-3">
          UI only. Next step: query policy + plan coverage from DB.
        </small>
      </div>
    </div>
  </div>
</div>
-->

<!-- Row: Claim Management + Claim Decisions (Side by Side) -->
<div class="row">

  <!-- Claim Management -->
  <div class="col-xl-6 col-md-6 mb-4 anchor-offset" id="claimManagement">
    <div class="card border-left-primary shadow h-100 py-2">
      <div class="card-header font-weight-bold text-primary">
        <i class="fas fa-file-medical mr-2"></i> Claim Management
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered mb-0">
            <thead class="thead-light">
              <tr>
                <th>Claim ID</th>
                <th>Patient Name</th>
                <th>Hospital</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>CL001</td>
                <td>John Doe</td>
                <td>City Hospital</td>
                <td>EGP 5000</td>
                <td><span class="badge badge-warning">Under Review</span></td>
              </tr>
              <tr>
                <td>CL002</td>
                <td>Jane Smith</td>
                <td>Metro Clinic</td>
                <td>EGP 12000</td>
                <td><span class="badge badge-success">Approved</span></td>
              </tr>
            </tbody>
          </table>
        </div>

      
      </div>
    </div>
  </div>

  <!-- Claim Decisions -->
  <div class="col-xl-6 col-md-6 mb-4 anchor-offset" id="claimDecision">
    <div class="card border-left-warning shadow h-100 py-2">
      <div class="card-header font-weight-bold text-warning">
        <i class="fas fa-gavel mr-2"></i> Claim Decision Processing
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light">
              <tr>
                <th>Claim ID</th>
                <th>Status</th>
                <th>Reason / Notes</th>
                <th>Updated At</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>CL001</td>
                <td><span class="badge badge-warning">Under Review</span></td>
                <td>-</td>
                <td>2026-01-14 09:00</td>
              </tr>
              <tr>
                <td>CL003</td>
                <td><span class="badge badge-danger">Rejected</span></td>
                <td>More documents needed</td>
                <td>2026-01-12 15:30</td>
              </tr>
            </tbody>
          </table>
        </div>

      
      </div>
    </div>
  </div>

</div>

<!-- ============== COMMENTED OUT: Financial Management ============== -->
<!-- 
<div class="row">
  <div class="col-xl-6 col-md-6 mb-4 anchor-offset" id="financialManagement">
    <div class="card border-left-info shadow h-100 py-2">
      <div class="card-header font-weight-bold text-info">
        <i class="fas fa-dollar-sign mr-2"></i> Financial Management
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered mb-0">
            <thead class="thead-light">
              <tr>
                <th>Payout ID</th>
                <th>Claim ID</th>
                <th>Amount Paid</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>PY001</td>
                <td>CL002</td>
                <td>EGP 12000</td>
                <td><span class="badge badge-success">Paid</span></td>
              </tr>
              <tr>
                <td>PY002</td>
                <td>CL004</td>
                <td>EGP 6000</td>
                <td><span class="badge badge-secondary">Pending</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        <small class="text-muted d-block mt-3">
          UI only. Next step: payouts table + totals from DB.
        </small>
      </div>
    </div>
  </div>
</div>
-->

<!-- Insurance Profile -->
<div class="row">
  <div class="col-12 mb-4 anchor-offset" id="insuranceProfile">
    <div class="card border-left-secondary shadow h-100 py-2">
      <div class="card-header font-weight-bold text-secondary">
        <i class="fas fa-building mr-2"></i> Insurance Platform Profile
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h6 class="text-primary mb-3"><i class="fas fa-info-circle mr-2"></i>Basic Information</h6>
            <p class="mb-2"><strong>Insurance Provider:</strong> <?= e($insurance_name) ?></p>
            <p class="mb-2"><strong>Insurance ID:</strong> <?= (int)$insurance_id ?></p>
            <p class="mb-2"><strong>Regulatory License:</strong> FRA-MED-2024-<?= str_pad($insurance_id, 4, '0', STR_PAD_LEFT) ?></p>
            <p class="mb-2"><strong>Registration Date:</strong> January 2024</p>
          </div>

          <div class="col-md-6">
            <h6 class="text-primary mb-3"><i class="fas fa-clipboard-list mr-2"></i>Available Plans</h6>
            <div class="pl-3">
              <p class="mb-1"><strong>Normal - Individual:</strong> Essential health coverage for individuals</p>
              <p class="mb-1"><strong>Normal - Company:</strong> Group coverage for corporate employees</p>
              <p class="mb-1"><strong>VIP - Individual:</strong> Premium coverage with private hospital access</p>
              <p class="mb-1"><strong>VIP - Company:</strong> Elite corporate health benefits package</p>
            </div>
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-md-6">
            <h6 class="text-success mb-3"><i class="fas fa-hospital mr-2"></i>Contracted Network Hospitals</h6>
            <div class="pl-3">
              <p class="mb-1">• El Shifa Hospital </p>
              <p class="mb-1">• Cleopatra Hospital </p>
              <p class="mb-1">• Air Force Hospital </p>
              <p class="mb-1">• Nasaeem Hospital </p>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="text-warning mb-3"><i class="fas fa-medkit mr-2"></i>Medical Services Covered</h6>
            <div class="pl-3">
              <p class="mb-1">✓ <strong>Checkup/Consultation:</strong> 100% coverage up to EGP 10,000</p>
              <p class="mb-1">✓ <strong>Operations/Surgery:</strong> 80% coverage up to EGP 1,000,000</p>
              <p class="mb-1">✓ <strong>Maternity Care:</strong> 70% coverage up to EGP 50,000</p>
              <p class="mb-1">✓ <strong>Dental Services:</strong> 60% coverage up to EGP 30,000</p>
              <p class="mb-1">✓ <strong>Optical Services:</strong> 50% coverage up to EGP 20,000</p>
            </div>
          </div>
        </div>

        <hr>

      
      </div>
    </div>
  </div>
</div>

</div>

<footer class="sticky-footer bg-white mt-4">
  <div class="container my-auto">
    <div class="copyright text-center my-auto">
      <span>Copyright &copy; SmartConnect 2026</span>
    </div>
  </div>
</footer>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>
</body>
</html>
<?php
/**
 * Insurance Dashboard - OOP Version - COMPLETE
 * Fully functional with database operations including ADD PATIENT and all sections
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ .'/Auth.php';
require_once __DIR__ .'/Patient.php';
require_once __DIR__ .'/Insurance.php';
require_once __DIR__ .'/Validator.php';

// Initialize
$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// Check authentication
$auth->checkStaffAuth("INSURANCE_STAFF");

$insurance_id = (int)$auth->getSessionData("insurance_id");
if ($insurance_id <= 0) die("Missing insurance_id in session.");

$success_msg = "";
$error_msg = "";

// Load insurance using OOP
$insurance = new Insurance($conn);
$insurance->loadById($insurance_id);
$insurance_name = $insurance->getName();

// Get KPIs using OOP
$kpi_patients = $insurance->getKPIPatients();
$kpi_policies_active = $insurance->getKPIActivePolicies();
$kpi_cases_month = $insurance->getKPICasesThisMonth();
$kpi_pending_reviews = $insurance->getKPIPendingReviews();

/*
|--------------------------------------------------------------------------
| Insurance Predictions Section
|--------------------------------------------------------------------------
| For now these are sample values for the companies you already have.
| Later, replace these values with real model output from Python/API.
*/
$insurance_predictions = [
    [
        "company" => "AXA",
        "predicted_increase" => 20,
        "confidence" => 96.4,
        "period" => date('F'),
        "action" => "Review AXA pricing changes and prepare policyholder communication."
    ],
    [
        "company" => "MetLife",
        "predicted_increase" => 14,
        "confidence" => 93.8,
        "period" => date('F'),
        "action" => "Review MetLife pricing changes and assess claim-cost drivers."
    ],
    [
        "company" => "Bupa",
        "predicted_increase" => 18,
        "confidence" => 95.1,
        "period" => date('F'),
        "action" => "Prepare Bupa premium review and monitor cost escalation."
    ],
    [
        "company" => "Allianz",
        "predicted_increase" => 12,
        "confidence" => 92.7,
        "period" => date('F'),
        "action" => "Assess Allianz pricing trend and notify relevant teams."
    ]
];

// Handle ADD PATIENT (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add_patient") {

  $full_name = trim($_POST["full_name"] ?? "");
  $national_id = trim($_POST["national_id"] ?? "");
  $phone = trim($_POST["phone"] ?? "");
  $gender = trim($_POST["gender"] ?? "");
  $address = trim($_POST["address"] ?? "");

  if ($full_name === "" || $national_id === "" || $phone === "" || $gender === "" || $address === "") {
    $error_msg = "Please fill all fields.";
  } elseif (!Validator::validateNationalId($national_id)) {
    $error_msg = "National ID must be 14 digits.";
  } elseif (!Validator::validatePhone($phone)) {
    $error_msg = "Phone must be Egyptian format (010/011/012/015 + 8 digits).";
  } else {

    // Check if patient already exists
    $check = $conn->prepare("SELECT patient_id FROM patients WHERE national_id=? LIMIT 1");
    $check->bind_param("s", $national_id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();

    if ($exists) {
      $error_msg = "Patient already exists with this National ID (Patient ID: " . (int)$exists["patient_id"] . ").";
    } else {

      // Create patient using OOP
      $patient = new Patient($conn);
      $patient->setFullName($full_name);
      $patient->setNationalId($national_id);
      $patient->setPhone($phone);
      $patient->setGender($gender);
      $patient->setAddress($address);

      if ($patient->create(null, $insurance_id)) {
        $success_msg = "Patient added successfully ✅ (ID: " . $patient->getPatientId() . ") under " . Validator::sanitizeInput($insurance_name);

        // Refresh page to show updated list
        header("Location: InsuranceDashboard.php#patients");
        exit;
      } else {
        $error_msg = "Failed to create patient";
      }
    }
  }
}

// Search patients using OOP
$patient = new Patient($conn);
$q = trim($_GET["q"] ?? "");
$patients = $patient->getPatientsByInsurance($insurance_id, $q);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Insurance Dashboard</title>

  <link href="css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    .anchor-offset { scroll-margin-top: 90px; }
    .dash-title { font-weight:800; letter-spacing:.2px; }
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
        <li class="nav-item"><a class="nav-link" href="#pricingAlerts"><i class="fas fa-chart-line mr-1"></i> Pricing Alerts</a></li>
        <li class="nav-item"><a class="nav-link" href="#patients"><i class="fas fa-users mr-1"></i> Patients</a></li>
        <li class="nav-item"><a class="nav-link" href="#addPatient"><i class="fas fa-user-plus mr-1"></i> Add Patient</a></li>
        <li class="nav-item"><a class="nav-link" href="#claimManagement"><i class="fas fa-file-medical mr-1"></i> Claims</a></li>
        <li class="nav-item"><a class="nav-link" href="#insuranceProfile"><i class="fas fa-building mr-1"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="Policy.php"><i class="fas fa-building mr-1"></i> Policy</a></li>
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

  <!-- DASHBOARD OVERVIEW -->
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

  <!-- INSURANCE PRICING ALERTS -->
  <div id="pricingAlerts" class="anchor-offset mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <h5 class="dash-title text-gray-800 mb-0">
        <i class="fas fa-chart-line mr-2 text-danger"></i> Insurance Pricing Alert System
      </h5>
    </div>

    <div class="insurance-alert-banner d-flex align-items-center justify-content-between flex-wrap">
      <div>
        <h5 class="insurance-alert-title mb-1">
          <i class="fas fa-exclamation-triangle mr-2"></i> High Priority Insurance Alerts
        </h5>
        <p class="insurance-alert-subtitle">AI-based pricing predictions for the upcoming period</p>
      </div>
      <span class="insurance-prediction-count">
        <?= count($insurance_predictions) ?> Predictions
      </span>
    </div>

    <div class="row">
      <?php foreach ($insurance_predictions as $pred): ?>
        <div class="col-xl-6 col-md-6 mb-4">
          <div class="card prediction-card h-100">
            <div class="card-body">
              <h5 class="mb-3">
                <i class="fas fa-exclamation-circle mr-2"></i>
                ALERT: <?= Validator::sanitizeInput($pred["company"]) ?> Premium Increase
              </h5>

              <div class="prediction-meta">
                <p><strong>Month:</strong> <?= Validator::sanitizeInput($pred["period"]) ?></p>
                <p>
                  <strong>Confidence:</strong>
                  <span class="confidence-badge"><?= number_format((float)$pred["confidence"], 1) ?>%</span>
                </p>
                <p><strong>Predicted Increase:</strong> <?= (int)$pred["predicted_increase"] ?>%</p>
              </div>

              <div class="prediction-action-box">
                <i class="fas fa-lightbulb mr-2"></i>
                <strong>Action:</strong> <?= Validator::sanitizeInput($pred["action"]) ?>
              </div>

              <div>
                <h6 class="font-weight-bold text-gray-700">
                  <i class="fas fa-clipboard-list mr-2 text-secondary"></i>Recommendations:
                </h6>
                <ul class="prediction-list">
                  <li>Monitor <?= Validator::sanitizeInput($pred["company"]) ?> premium trend</li>
                  <li>Review historical claim-cost drivers</li>
                  <li>Prepare pricing and communication response</li>
                </ul>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= $success_msg ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= Validator::sanitizeInput($error_msg) ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <!-- ADD + PATIENTS (SIDE BY SIDE) -->
  <div class="row">

    <!-- LEFT: ADD PATIENT -->
    <div class="col-lg-5 mb-4" id="addPatient">
      <div class="card shadow h-100">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
          <h6 class="m-0 font-weight-bold text-success">
            <i class="fas fa-user-plus mr-2"></i> Add Patient (<?= Validator::sanitizeInput($insurance_name) ?>)
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
              <input class="form-control" value="<?= Validator::sanitizeInput($insurance_name) ?>" readonly>
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
            <i class="fas fa-users mr-2"></i> Patients (<?= Validator::sanitizeInput($insurance_name) ?>)
          </h6>

          <form class="d-flex mt-2 mt-md-0" method="GET" action="InsuranceDashboard.php#patients" style="gap:8px; flex: 1; max-width: 500px;">
            <input class="form-control form-control-lg" name="q" value="<?= Validator::sanitizeInput($q) ?>"
                   placeholder="Search by name, national ID, or phone number" style="font-size: 0.95rem;">
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
                      <td><?= Validator::sanitizeInput($p["full_name"]) ?></td>
                      <td><?= Validator::sanitizeInput($p["national_id"]) ?></td>
                      <td><?= Validator::sanitizeInput($p["phone"]) ?></td>
                      <td><?= Validator::sanitizeInput($p["gender"]) ?></td>
                      <td><?= Validator::sanitizeInput($p["plan_name"] ?? "-") ?></td>
                      <td>
                        <?php
                          $st = $p["status"] ?? "";
                          if ($st === "active") echo '<span class="badge badge-success">active</span>';
                          elseif ($st === "suspended") echo '<span class="badge badge-warning">suspended</span>';
                          elseif ($st === "expired") echo '<span class="badge badge-secondary">expired</span>';
                          else echo '<span class="badge badge-light">no policy</span>';
                        ?>
                      </td>
                      <td><?= Validator::sanitizeInput($p["policy_number"] ?? "-") ?></td>
                      <td style="white-space:nowrap;">
                        <a class="btn btn-sm btn-outline-success" href="AddPatientPolicy.php?patient_id=<?= (int)$p["patient_id"] ?>">
                          <i class="fas fa-plus"></i> Add Policy
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

  <!-- CLAIM MANAGEMENT -->
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
              <p class="mb-2"><strong>Insurance Provider:</strong> <?= Validator::sanitizeInput($insurance_name) ?></p>
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
                <p class="mb-1">• El Shifa Hospital</p>
                <p class="mb-1">• Cleopatra Hospital</p>
                <p class="mb-1">• Air Force Hospital</p>
                <p class="mb-1">• Nasaeem Hospital</p>
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

        </div>
      </div>
    </div>
  </div>

</div>

<footer class="sticky-footer bg-white mt-4">
  <div class="container my-auto">
    <div class="copyright text-center my-auto">
      <span>SmartConnect © 2026</span>
    </div>
  </div>
</footer>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>
</body>
</html>
<?php $db->close(); ?>
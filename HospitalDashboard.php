<?php
/**
 * HospitalDashboard.php - OOP Version (FULL PAGE)
 * ✅ NO added_by_hospital_id
 * ✅ Shows Insurance name per patient (NO N+1 queries)
 * ✅ Uses Patient::getPatientsByHospital() that must return insurance_name
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Patient.php';
require_once __DIR__ . '/Hospital.php';
require_once __DIR__ . '/Validator.php';

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

// Search / list patients (MUST be implemented to return insurance_id + insurance_name)
$patient = new Patient($conn);
$q = trim($_GET["q"] ?? "");
$patients = $patient->getPatientsByHospital($hospital_id, $q) ?? [];

// KPI helpers
function fetch_int(mysqli $conn, string $sql, string $types = "", array $params = []): int {
  $stmt = $conn->prepare($sql);
  if ($types !== "") {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_row() : null;
  $stmt->close();
  return $row ? (int)$row[0] : 0;
}

// ✅ KPI: Patients under this hospital (via insurance_hospitals contracts)
$kpi_patients = fetch_int(
  $conn,
  "SELECT COUNT(DISTINCT p.patient_id)
   FROM patients p
   INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
   WHERE ih.hospital_id = ? AND p.is_active = 1",
  "i",
  [$hospital_id]
);

// ✅ KPI: Medical records related to those patients (via join)
$kpi_medical_records = fetch_int(
  $conn,
  "SELECT COUNT(*)
   FROM medical_records mr
   INNER JOIN patients p ON p.patient_id = mr.patient_id
   INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
   WHERE ih.hospital_id = ?",
  "i",
  [$hospital_id]
);

// ✅ KPI: Insured patients (under this hospital contracts)
$kpi_insured_patients = fetch_int(
  $conn,
  "SELECT COUNT(DISTINCT p.patient_id)
   FROM patients p
   INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
   WHERE ih.hospital_id = ?
     AND p.insurance_id IS NOT NULL
     AND p.is_active = 1",
  "i",
  [$hospital_id]
);

// ✅ KPI: New patients in last 7 days (under this hospital contracts)
$kpi_recent_admissions = fetch_int(
  $conn,
  "SELECT COUNT(DISTINCT p.patient_id)
   FROM patients p
   INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
   WHERE ih.hospital_id = ?
     AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     AND p.is_active = 1",
  "i",
  [$hospital_id]
);

function e($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Hospital Dashboard">
  <meta name="author" content="Hospital Admin">
  <title>Hospital Dashboard</title>

  <link href="css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    body { background: #f8f9fc; }
    .section-title { font-weight: 800; }
    .navbar-brand strong { letter-spacing: .3px; }
    .anchor-offset { scroll-margin-top: 90px; }
    .dash-title { font-weight:800; letter-spacing:.2px; }
    .kpi-card { border-radius: 12px; }
    .kpi-card .card-body { padding: 14px 16px; }
    .kpi-label {
      font-size: .72rem; font-weight: 800; letter-spacing: .6px;
      text-transform: uppercase; margin-bottom: 6px;
    }
    .kpi-value { font-size: 1.25rem; font-weight: 800; line-height: 1.1; }
  </style>
</head>

<body id="page-top">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#dashboard">
      <i class="fas fa-clinic-medical mr-2"></i>
      <strong><?= e($hospital_name) ?> Dashboard</strong>
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topNav"
      aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
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
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
             data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="d-none d-lg-inline mr-2">
              <?= e($auth->getSessionData("staff_name") ?? "Hospital Staff") ?>
            </span>
            <i class="fas fa-user-circle fa-2x text-white"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="#">
              <i class="fas fa-user mr-2"></i> Profile
            </a>
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

<div class="container-fluid py-4">

  <?php if ($success_msg): ?>
    <div class="alert alert-success"><?= e($success_msg) ?></div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-danger"><?= e($error_msg) ?></div>
  <?php endif; ?>

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


  
  <!-- ================= INSIGHTS SECTION ================= -->
  <div id="insights" class="anchor-offset mt-4">
    <h3 class="text-primary section-title mb-3">Operational Insights</h3>

    <div class="row">
      <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Patient Registrations Over Time</h6>
          </div>
          <div class="card-body">
            <canvas id="registrationChart" height="120"></canvas>
          </div>
        </div>
      </div>

      <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Patient Demographics</h6>
          </div>
          <div class="card-body">
            <canvas id="demographicsChart" height="180"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= EPIDEMIC ALERT SYSTEM ================= -->
  <div id="epidemic-alerts" class="anchor-offset mt-4">

    <h3 class="text-danger section-title mb-3">
      🏥 Hospital Epidemic Alert System
    </h3>

    <div class="card shadow mb-4 border-left-danger">
      <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div>
          <h5 class="font-weight-bold text-danger mb-1">🚨 High Priority Alerts</h5>
          <p class="mb-0 text-muted">AI-based epidemic predictions for the upcoming period</p>
        </div>
        <span class="badge badge-danger badge-pill p-3">85 Predictions</span>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 border-left-warning">
          <div class="card-body">
            <h5 class="font-weight-bold text-warning">⚠️ ALERT: Hepatic Coma</h5>
            <ul class="list-unstyled mb-3">
              <li>📅 <strong>Month:</strong> January</li>
              <li>📈 <strong>Accuracy percentage:</strong> <span class="badge badge-danger">99.6%</span></li>
              <li>📊 <strong>Expected Cases:</strong> 3</li>
            </ul>
            <div class="alert alert-warning py-2">
              💡 <strong>Action:</strong> Prepare hepatic coma treatment capacity
            </div>
            <h6 class="font-weight-bold mt-3">🏥 Recommendations:</h6>
            <ul class="mb-0">
              <li>Review admission patterns</li>
              <li>Ensure adequate general capacity</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 border-left-warning">
          <div class="card-body">
            <h5 class="font-weight-bold text-warning">⚠️ ALERT: Stroke</h5>
            <ul class="list-unstyled mb-3">
              <li>📅 <strong>Month:</strong> January</li>
              <li>📈 <strong>Accuracy percentage:</strong> <span class="badge badge-danger">99.5%</span></li>
              <li>📊 <strong>Expected Cases:</strong> 6</li>
            </ul>
            <div class="alert alert-warning py-2">
              💡 <strong>Action:</strong> Prepare stroke treatment capacity
            </div>
            <h6 class="font-weight-bold mt-3">🏥 Recommendations:</h6>
            <ul class="mb-0">
              <li>Review admission patterns</li>
              <li>Ensure ICU & neurology readiness</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </div>
  <!-- ================= PATIENTS SECTION ================= -->
  <div id="patients" class="anchor-offset mt-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <h3 class="text-primary section-title mb-2">Patient Management</h3>
    </div>

    <div class="row">
      <div class="col-12 mb-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
            <h6 class="m-0 font-weight-bold text-primary">View Patients</h6>

            <form class="d-flex mt-2 mt-md-0"
                  method="GET"
                  action="HospitalDashboard.php#patients"
                  style="gap:8px; flex: 1; max-width: 520px;">

              <input class="form-control form-control-lg"
                     name="q"
                     value="<?= e($q) ?>"
                     placeholder="Search by name, national ID, or phone"
                     style="font-size: 0.95rem;">

              <button class="btn btn-primary btn-lg" type="submit" style="min-width: 50px;">
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
                      <th>ID</th>
                      <th>Name</th>
                      <th>National ID</th>
                      <th>Phone</th>
                      <th>Gender</th>
                      <th>Insurance</th>
                      <th>Medical Record</th>
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

                        <!-- ✅ Insurance Name (from JOIN) -->
                        <td>
                          <?php
                            $insId   = (int)($pt["insurance_id"] ?? 0);
                            $insName = $pt["insurance_name"] ?? null;

                            if ($insId > 0) {
                              echo '<span class="badge badge-success">'.e($insName ?: ("#".$insId)).'</span>';
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

  <!-- ================= CLAIMS SECTION ================= -->
   <!--
  <div id="claims" class="anchor-offset mt-4">
    <h3 class="text-primary section-title mb-3">Insurance Claims</h3>

    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Submit Treatment Costs</h6>
          </div>
          <div class="card-body">
            <p class="text-muted mb-3">
              This is the UI section. Next step we connect it to the <code>claim</code> table.
            </p>

            <form onsubmit="event.preventDefault(); alert('Next step: connect this form to claim table');">
              <div class="form-group">
                <label>Patient National ID</label>
                <input class="form-control" placeholder="14 digits">
              </div>
              <div class="form-group">
                <label>Diagnosis / Service</label>
                <input class="form-control" placeholder="e.g., MRI, Surgery, Medication">
              </div>
              <div class="form-group">
                <label>Total Cost (EGP)</label>
                <input class="form-control" type="number" min="0" step="1">
              </div>
              <button class="btn btn-primary btn-block" type="submit">
                <i class="fas fa-paper-plane mr-1"></i> Submit Claim
              </button>
            </form>

          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Claim Status</h6>
          </div>
          <div class="card-body">
            <div class="list-group">
              <a class="list-group-item list-group-item-action" href="#">Submitted</a>
              <a class="list-group-item list-group-item-action" href="#">Under Review</a>
              <a class="list-group-item list-group-item-action" href="#">Approved</a>
              <a class="list-group-item list-group-item-action" href="#">Rejected</a>
            </div>
            <small class="text-muted d-block mt-3">
              Next step: read status from database and show counts.
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>
-->

  <!-- ================= STAFF SECTION ================= -->
   <!--
  <div id="staff" class="anchor-offset mt-4">
    <h3 class="text-primary section-title mb-3">Staff Roles & Permissions</h3>
    <div class="card shadow mb-4">
      <div class="card-body">
        <p class="text-muted mb-0">
          Next step: connect to your <code>users</code> + <code>roles</code> tables and show staff list by hospital.
        </p>
      </div>
    </div>
  </div>

</div>

<footer class="sticky-footer bg-white">
  <div class="container my-auto">
    <div class="copyright text-center my-auto">
      <span>Hospital Dashboard © 2026 - OOP Version</span>
    </div>
  </div>
  -->
</footer>

<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>
<script src="Js/chart.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  if (typeof Chart === "undefined") {
    console.error("Chart.js not loaded. Check Js/chart.min.js path!");
    return;
  }

  var registrationCanvas = document.getElementById("registrationChart");
  if (registrationCanvas) {
    var ctx = registrationCanvas.getContext("2d");
    new Chart(ctx, {
      type: "line",
      data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
        datasets: [{
          label: "New Patients",
          data: [5, 8, 12, 7, 15, 11],
          borderColor: "#4e73df",
          backgroundColor: "rgba(78, 115, 223, 0.15)",
          pointBackgroundColor: "#1cc88a",
          pointBorderColor: "#1cc88a",
          borderWidth: 3,
          tension: 0.35,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  var demographicsCanvas = document.getElementById("demographicsChart");
  if (demographicsCanvas) {
    var ctx2 = demographicsCanvas.getContext("2d");
    new Chart(ctx2, {
      type: "doughnut",
      data: {
        labels: ["Male", "Female"],
        datasets: [{
          data: [1, 4],
          backgroundColor: ["#36b9cc", "#f6c23e"],
          hoverBackgroundColor: ["#2c9faf", "#dda20a"],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "65%",
        plugins: { legend: { position: "bottom" } }
      }
    });
  }
});
</script>

</body>
</html>
<?php
if ($conn instanceof mysqli) { $conn->close(); }
?>

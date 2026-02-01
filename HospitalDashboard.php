<?php
session_start();
if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "staff" || ($_SESSION["role"] ?? "") !== "HOSPITAL_STAFF") {
  header("Location: login.html");
  exit;
}

require_once "db.php";

/*
  ✅ If you have hospital_id in session it will be used.
  If not, we use 1 temporarily so the system works now.
*/
$hospital_id = (int)($_SESSION["hospital_id"] ?? 1);

$success_msg = "";
$error_msg = "";

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
  } else {

    if (!preg_match('/^\d{14}$/', $national_id)) {
      $error_msg = "National ID must be 14 digits.";
    } elseif (!preg_match('/^(010|011|012|015)\d{8}$/', $phone)) {
      $error_msg = "Phone must be Egyptian format (010/011/012/015 + 8 digits).";
    } else {
      // prevent duplicate national_id
      $check = $conn->prepare("SELECT patient_id FROM patients WHERE national_id=? LIMIT 1");
      $check->bind_param("s", $national_id);
      $check->execute();
      $res = $check->get_result();
      $exists = $res->fetch_assoc();
      $check->close();

      if ($exists) {
        $error_msg = "Patient already exists with this National ID (Patient ID: " . (int)$exists["patient_id"] . ").";
      } else {
        $stmt = $conn->prepare("
          INSERT INTO patients (full_name, national_id, phone, gender, address, added_by_hospital_id, is_active)
          VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->bind_param("sssssi", $full_name, $national_id, $phone, $gender, $address, $hospital_id);

        if ($stmt->execute()) {
          $newId = (int)$stmt->insert_id;
          $success_msg = "Patient added successfully ✅ (ID: $newId). Patient can now login using National ID + Phone.";
        } else {
          $error_msg = "Insert failed: " . $stmt->error;
        }
        $stmt->close();
      }
    }
  }
}

/* =========================
   LIST + SEARCH PATIENTS
========================= */
$q = trim($_GET["q"] ?? "");
$patients = [];

if ($q === "") {
  $stmt = $conn->prepare("
    SELECT patient_id, full_name, national_id, phone, gender, address, is_active, created_at
    FROM patients
    WHERE added_by_hospital_id = ?
    ORDER BY patient_id DESC
    LIMIT 50
  ");
  $stmt->bind_param("i", $hospital_id);
} else {
  $like = "%".$q."%";
  $stmt = $conn->prepare("
    SELECT patient_id, full_name, national_id, phone, gender, address, is_active, created_at
    FROM patients
    WHERE added_by_hospital_id = ?
      AND (full_name LIKE ? OR national_id LIKE ? OR phone LIKE ?)
    ORDER BY patient_id DESC
    LIMIT 50
  ");
  $stmt->bind_param("isss", $hospital_id, $like, $like, $like);
}

$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $patients[] = $row;
$stmt->close();

/* KPI counts */
$kpi_patients = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM patients WHERE added_by_hospital_id=?");
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$kpi_patients = (int)($stmt->get_result()->fetch_assoc()["c"] ?? 0);
$stmt->close();
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
    .badge-active { background:#1cc88a; }
    .badge-inactive { background:#e74a3b; }
    .anchor-offset { scroll-margin-top: 90px; } /* navbar height */
  </style>
</head>

<body id="page-top">


<!-- ✅ TOP NAVBAR (Responsive) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#dashboard">
  <i class="fas fa-clinic-medical mr-2"></i>
  <strong>Hospital Dashboard</strong>
</a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topNav"
      aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
        <li class="nav-item"><a class="nav-link" href="#dashboard"><i class="fas fa-tachometer-alt mr-1"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="#patients"><i class="fas fa-user-injured mr-1"></i> Patients</a></li>
        <li class="nav-item"><a class="nav-link" href="#claims"><i class="fas fa-file-invoice-dollar mr-1"></i> Claims</a></li>
        <li class="nav-item"><a class="nav-link" href="#insights"><i class="fas fa-chart-line mr-1"></i> Insights</a></li>
        <li class="nav-item"><a class="nav-link" href="#profile"><i class="fas fa-hospital mr-1"></i> Hospital Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="#staff"><i class="fas fa-user-shield mr-1"></i> Staff</a></li>
      </ul>

      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
             data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="d-none d-lg-inline mr-2">
              <?= htmlspecialchars($_SESSION["staff_name"] ?? "Hospital Staff") ?>
            </span>
         <i class="fas fa-user-circle fa-2x text-white"></i>

          </a>
          <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="#profile"><i class="fas fa-hospital fa-sm fa-fw mr-2 text-gray-400"></i> Profile</a>
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
    <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <!-- ================= DASHBOARD SECTION ================= -->
  <div id="dashboard" class="anchor-offset">
    <h3 class="text-primary section-title mb-3">Dashboard Overview</h3>

    <div class="row">
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
          <div class="card-body">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Patients Added</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int)$kpi_patients ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
          <div class="card-body">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Outpatients Today</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">45</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
          <div class="card-body">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Insurance Claims</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">32</div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
          <div class="card-body">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Lab Results</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">16</div>
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
      <!-- Add Patient -->
      <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Add Patient</h6>
          </div>
          <div class="card-body">
            <form method="POST" action="HospitalDashboard.php#patients">
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
                <label>Address</label>
                <input class="form-control" name="address" required>
              </div>

              <button class="btn btn-primary btn-block" type="submit">
                <i class="fas fa-user-plus mr-1"></i> Save Patient
              </button>

              <small class="text-muted d-block mt-2">
                After adding, patient can login using National ID + Phone.
              </small>
            </form>
          </div>
        </div>
      </div>

      <!-- Patients List -->
      <div class="col-lg-7 mb-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
            <h6 class="m-0 font-weight-bold text-primary">View Patients</h6>
            <form class="form-inline mt-2 mt-md-0" method="GET" action="HospitalDashboard.php#patients">
              <input class="form-control mr-2" name="q" placeholder="Search name / national id / phone"
                     value="<?= htmlspecialchars($q) ?>">
              <button class="btn btn-outline-primary" type="submit">
                <i class="fas fa-search"></i>
              </button>
            </form>
          </div>
          <div class="card-body">
            <?php if (!$patients): ?>
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
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($patients as $pt): ?>
                      <tr>
                        <td><?= (int)$pt["patient_id"] ?></td>
                        <td><?= htmlspecialchars($pt["full_name"]) ?></td>
                        <td><?= htmlspecialchars($pt["national_id"]) ?></td>
                        <td><?= htmlspecialchars($pt["phone"]) ?></td>
                        <td><?= htmlspecialchars($pt["gender"]) ?></td>
                        <td>
                          <?php if ((int)$pt["is_active"] === 1): ?>
                            <span class="badge badge-active text-white">Active</span>
                          <?php else: ?>
                            <span class="badge badge-inactive text-white">Inactive</span>
                          <?php endif; ?>
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

  <!-- ================= CLAIMS SECTION (UI ready) ================= -->
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

  <!-- ================= INSIGHTS SECTION (keep charts) ================= -->
  <div id="insights" class="anchor-offset mt-4">
    <h3 class="text-primary section-title mb-3">Operational Insights</h3>

    <div class="row">
      <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Disease Outbreak Patterns</h6>
          </div>
          <div class="card-body">
            <canvas id="outbreakChart"></canvas>
          </div>
        </div>
      </div>

      <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Claim Status Overview</h6>
          </div>
          <div class="card-body">
            <canvas id="claimStatusChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= PROFILE SECTION (keep your profile) ================= -->
  <div id="profile" class="anchor-offset mt-4">
    <h3 class="text-primary section-title mb-3">Hospital Profile</h3>

    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Hospital Profile</h6>
          </div>
          <div class="card-body">
            <p><strong>Name:</strong> City Hospital</p>
            <p><strong>Departments:</strong> Cardiology, Radiology, Neurology, Pediatrics</p>
            <p><strong>Insurance Partners:</strong> Aetna, Blue Cross, United Healthcare</p>
            <p><strong>Staff Roles:</strong> Doctors, Nurses, Admin, IT Support</p>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
          </div>
          <div class="card-body">
            <ul>
              <li>New patient admitted: John Doe</li>
              <li>Lab results uploaded: Jane Smith</li>
              <li>Insurance claim approved: Patient #123</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= STAFF SECTION ================= -->
   
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

</div><!-- /container -->

<!-- Footer -->
<footer class="sticky-footer bg-white">
  <div class="container my-auto">
    <div class="copyright text-center my-auto">
      <span>Copyright &copy; Hospital Dashboard 2026</span>
    </div>
  </div>
</footer>

<!-- JS Libraries -->
<script src="Js/jquery.min.js"></script>
<script src="Js/bootstrap.bundle.min.js"></script>
<script src="Js/jquery.easing.min.js"></script>
<script src="Js/sb-admin-2.min.js"></script>
<script src="Js/chart.min.js"></script>

<script>
  // Keep your charts (same as your old file)
  var ctx = document.getElementById('outbreakChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
      datasets: [{
        label: 'Cases',
        data: [12, 19, 8, 15, 23],
        fill: false
      }]
    }
  });

  var ctx2 = document.getElementById('claimStatusChart').getContext('2d');
  new Chart(ctx2, {
    type: 'doughnut',
    data: {
      labels: ['Submitted', 'Under Review', 'Approved', 'Rejected'],
      datasets: [{
        data: [10, 5, 12, 3]
      }]
    }
  });
</script>

</body>
</html>

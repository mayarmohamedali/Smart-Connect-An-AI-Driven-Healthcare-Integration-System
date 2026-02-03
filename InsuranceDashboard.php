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

// Search
$q = trim($_GET["q"] ?? "");

// OPTIONAL: handle delete policy
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete_policy") {
  $policy_id = (int)($_POST["policy_id"] ?? 0);
  if ($policy_id > 0) {
   $del = $conn->prepare("DELETE FROM patient_policy WHERE patient_policy_id=? AND insurance_id=?");
    $del->bind_param("ii", $policy_id, $insurance_id);
    $del->execute();
    $del->close();
  }
  header("Location: InsuranceDashboard.php#patients");
  exit;
}

/*
  Get patients + latest policy for THIS insurance (LEFT JOIN)
  - If patient has no policy yet -> policy fields will be NULL
*/
$params = [];
$types = "";
$where = "1=1";

if ($q !== "") {
  $where .= " AND (p.full_name LIKE CONCAT('%', ?, '%') OR p.national_id LIKE CONCAT('%', ?, '%')) ";
  $types .= "ss";
  $params[] = $q;
  $params[] = $q;
}

$sql = "
SELECT
  p.patient_id, p.full_name, p.national_id, p.phone, p.gender,
  pp.patient_policy_id AS policy_id,
  pp.policy_number, pp.start_date, pp.end_date, pp.status,
  ip.plan_name
FROM patients p
LEFT JOIN patient_policy pp
  ON pp.patient_id = p.patient_id AND pp.insurance_id = ?
LEFT JOIN insurance_plan ip
  ON ip.id = pp.insurance_plan_id
WHERE $where
ORDER BY p.patient_id DESC
";

$stmt = $conn->prepare($sql);

if ($types === "") {
  $stmt->bind_param("i", $insurance_id);
} else {
  // insurance_id + (q,q)
  $stmt->bind_param("i".$types, $insurance_id, ...$params);
}

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

  <!-- Fonts / Icons -->
  <link href="css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <!-- SB Admin 2 CSS -->
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top" class="bg-light">

  <!-- ✅ TOP NAVBAR (replaces sidebar) -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow">
    <div class="container-fluid">

      <!-- Brand -->
      <a class="navbar-brand d-flex align-items-center" href="insurance_dashboard.php">
        <i class="fas fa-shield-alt mr-2"></i>
        <strong>Insurance Dashboard</strong>
      </a>

      <!-- Mobile Toggle -->
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topNavbar"
        aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topNavbar">
        <!-- Links -->
        <ul class="navbar-nav mr-auto">
          <li class="nav-item">
            <a class="nav-link" href="#patientEligibility">
              <i class="fas fa-user-check mr-1"></i> Patient Eligibility
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="#claimManagement">
              <i class="fas fa-file-medical mr-1"></i> Claim Management
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="#claimDecision">
              <i class="fas fa-gavel mr-1"></i> Claim Decisions
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="#financialManagement">
              <i class="fas fa-dollar-sign mr-1"></i> Financial Management
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="#insuranceProfile">
              <i class="fas fa-building mr-1"></i> Insurance Profile
            </a>
          </li>
        </ul>

        <!-- User Dropdown -->
        <ul class="navbar-nav ml-auto">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
              data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="mr-2 d-none d-lg-inline text-gray-600 small">
  <?= htmlspecialchars($_SESSION["staff_name"] ?? "Insurance Staff") ?>
</span>
              <img class="img-profile rounded-circle" src="img/undraw_profile.svg" style="width:32px;height:32px;">
            </a>

            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
              <a class="dropdown-item" href="#">
                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile
              </a>
              <a class="dropdown-item" href="#">
                <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i> Settings
              </a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="login.html">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
              </a>
            </div>
          </li>
        </ul>

      </div>
    </div>
  </nav>

  <!-- ✅ PAGE CONTENT -->
  <div class="container-fluid mt-4">

    <h1 class="h3 mb-4 text-gray-800">Insurance Dashboard</h1>

    <div class="row" id="patients">
  <div class="col-12 mb-4">
    <div class="card shadow">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-success">
          <i class="fas fa-users mr-2"></i> Patients
        </h6>

        <form class="d-flex" method="GET" action="InsuranceDashboard.php#patients" style="gap:8px;">
          <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name / national id">
          <button class="btn btn-success" type="submit">
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
                      <!-- Add Policy always available -->
                      <a class="btn btn-sm btn-outline-success"
                         href="AddPatientPolicy.php?patient_id=<?= (int)$p["patient_id"] ?>">
                        <i class="fas fa-plus"></i> Add Policy
                      </a>

                      
                       <a class="btn btn-sm btn-outline-secondary ml-2"
     href="ViewMedicalRecords.php?patient_id=<?= (int)$pt["patient_id"] ?>">
    <i class="fas fa-folder-open mr-1"></i> View
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

    <!-- Row: Patient Eligibility & Claim Management -->
    <div class="row">

      <!-- Patient Eligibility -->
      <div class="col-xl-6 col-md-6 mb-4" id="patientEligibility">
        <div class="card border-left-success shadow h-100 py-2">
          <div class="card-header font-weight-bold text-success">Patient Eligibility Verification</div>
          <div class="card-body">
            <form onsubmit="event.preventDefault(); goToAddPolicy();">
              <div class="form-group">
                <label for="nationalID">Patient National ID</label>
                <input type="text" class="form-control" id="nationalID" placeholder="Enter ID">
              </div>

              <button type="button" class="btn btn-success btn-block" onclick="goToAddPolicy()">
                Add / Update Patient Policy
              </button>
            </form>

            <div class="mt-3">
              <strong>Status:</strong> <span id="eligibilityStatus">-</span><br>
              <strong>Coverage Limit:</strong> <span id="coverageLimit">-</span>
            </div>

          </div>
        </div>
      </div>

      <!-- Claim Management -->
      <div class="col-xl-6 col-md-6 mb-4" id="claimManagement">
        <div class="card border-left-primary shadow h-100 py-2">
          <div class="card-header font-weight-bold text-primary">Claim Management</div>
          <div class="card-body">
            <table class="table table-bordered">
              <thead>
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
                  <td>$500</td>
                  <td>Under Review</td>
                </tr>
                <tr>
                  <td>CL002</td>
                  <td>Jane Smith</td>
                  <td>Metro Clinic</td>
                  <td>$1200</td>
                  <td>Approved</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Row: Claim Decisions & Financial Management -->
    <div class="row">

      <!-- Claim Decisions -->
      <div class="col-xl-6 col-md-6 mb-4" id="claimDecision">
        <div class="card border-left-warning shadow h-100 py-2">
          <div class="card-header font-weight-bold text-warning">Claim Decision Processing</div>
          <div class="card-body">
            <table class="table table-sm table-bordered">
              <thead>
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
                  <td>Under Review</td>
                  <td>-</td>
                  <td>2026-01-14 09:00</td>
                </tr>
                <tr>
                  <td>CL003</td>
                  <td>Rejected</td>
                  <td>More documents needed</td>
                  <td>2026-01-12 15:30</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Financial Management -->
      <div class="col-xl-6 col-md-6 mb-4" id="financialManagement">
        <div class="card border-left-info shadow h-100 py-2">
          <div class="card-header font-weight-bold text-info">Financial Management</div>
          <div class="card-body">
            <table class="table table-bordered">
              <thead>
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
                  <td>$1200</td>
                  <td>Paid</td>
                </tr>
                <tr>
                  <td>PY002</td>
                  <td>CL004</td>
                  <td>$600</td>
                  <td>Pending</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Row: Insurance Profile -->
    <div class="row">
      <div class="col-xl-12 col-md-12 mb-4" id="insuranceProfile">
        <div class="card border-left-secondary shadow h-100 py-2">
          <div class="card-header font-weight-bold text-secondary">Insurance Platform Profile</div>
          <div class="card-body">
            <p><strong>Insurance Plans:</strong> Basic Health, Premium Care, Family Plan</p>
            <p><strong>Contracted Hospitals:</strong> City Hospital, Metro Clinic, Sunshine Medical</p>
            <p><strong>Premium Rules:</strong> Monthly, Annual, Co-payment rules</p>
            <p><strong>Policy Rules:</strong> Coverage exclusions, co-pays, limits</p>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /.container-fluid -->

  <!-- Footer -->
  <footer class="sticky-footer bg-white mt-4">
    <div class="container my-auto">
      <div class="copyright text-center my-auto">
        <span>Copyright &copy; SmartConnect 2026</span>
      </div>
    </div>
  </footer>

  <!-- Scroll to Top Button -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script>
    function goToAddPolicy() {
      const nationalId = document.getElementById("nationalID").value.trim();
      if (!nationalId) {
        alert("Please enter National ID");
        return;
      }
      window.location.href = "FindPatientForPolicy.php?national_id=" + encodeURIComponent(nationalId);
    }
  </script>

  <!-- JS (order matters) -->
  <script src="Js/jquery.min.js"></script>
  <script src="Js/bootstrap.bundle.min.js"></script>
  <script src="Js/jquery.easing.min.js"></script>
  <script src="Js/sb-admin-2.min.js"></script>

</body>
</html>

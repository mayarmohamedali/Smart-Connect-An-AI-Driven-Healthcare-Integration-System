<?php
// Variables from InsuranceController::dashboard():
// $insurance_name, $insurance_id, $kpi_patients, $kpi_policies_active,
// $kpi_cases_month, $kpi_pending_reviews, $patients, $q, $success_msg, $error_msg
function e(mixed $v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Insurance Dashboard – Smart-Connect</title>

  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    .anchor-offset { scroll-margin-top:90px; }
    .kpi-card { border-radius:12px; }
    .kpi-card .card-body { padding:14px 16px; }
    .kpi-label { font-size:.72rem; font-weight:800; letter-spacing:.6px; text-transform:uppercase; margin-bottom:6px; }
    .kpi-value { font-size:1.25rem; font-weight:800; line-height:1.1; }
    .navbar-nav .nav-link.active { background:rgba(255,255,255,.15); border-radius:6px; }
  </style>
</head>

<body id="page-top" class="bg-light">

<!-- ── Navbar ─────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/insurance/dashboard">
      <i class="fas fa-shield-alt mr-2"></i>
      <strong><?= e($insurance_name) ?> Dashboard</strong>
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNavbar">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item active">
          <a class="nav-link" href="<?= BASE_URL ?>/insurance/dashboard">
            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#patients">
            <i class="fas fa-users mr-1"></i> Patients
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#addPatient">
            <i class="fas fa-user-plus mr-1"></i> Add Patient
          </a>
        </li>
        <!-- ✅ FIXED: Policy Management link added -->
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/insurance/policy">
            <i class="fas fa-file-contract mr-1"></i> Policy Management
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#claimManagement">
            <i class="fas fa-file-medical mr-1"></i> Claims
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#insuranceProfile">
            <i class="fas fa-building mr-1"></i> Profile
          </a>
        </li>
      </ul>

      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
             role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="mr-2 d-none d-lg-inline text-white small"><?= e($_SESSION['staff_name'] ?? 'Insurance Staff') ?></span>
            <i class="fas fa-user-circle fa-2x text-white"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="<?= BASE_URL ?>/insurance/policy">
              <i class="fas fa-file-contract fa-sm fa-fw mr-2 text-gray-400"></i> Policy Management
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout">
              <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
            </a>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid mt-4">

  <!-- ── KPI CARDS ─────────────────────────────────────── -->
  <div id="dashboard" class="anchor-offset mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <h5 class="font-weight-bold text-gray-800 mb-0">
        <i class="fas fa-chart-pie mr-2 text-success"></i> Dashboard Overview
      </h5>
      <span class="badge badge-light"><i class="fas fa-calendar-alt mr-1"></i> <?= date('F Y') ?></span>
    </div>

    <div class="row">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-success">Total Patients</div>
            <div class="kpi-value text-gray-800"><?= $kpi_patients ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-primary">Policies Active</div>
            <div class="kpi-value text-gray-800"><?= $kpi_policies_active ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-info">Claims This Month</div>
            <div class="kpi-value text-gray-800"><?= $kpi_cases_month ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-warning">Pending Reviews</div>
            <div class="kpi-value text-gray-800"><?= $kpi_pending_reviews ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick-action button to Policy Management -->
  <div class="mb-4">
    <a href="<?= BASE_URL ?>/insurance/policy" class="btn btn-outline-success">
      <i class="fas fa-file-contract mr-2"></i> Manage Insurance Policies
    </a>
  </div>

  <!-- ── Alerts ──────────────────────────────────────── -->
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

  <!-- ── ADD PATIENT + PATIENT TABLE ──────────────────── -->
  <div class="row">

    <!-- Add Patient -->
    <div class="col-lg-5 mb-4" id="addPatient">
      <div class="card shadow h-100">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
          <h6 class="m-0 font-weight-bold text-success">
            <i class="fas fa-user-plus mr-2"></i> Add Patient (<?= e($insurance_name) ?>)
          </h6>
          <span class="badge badge-light"><i class="fas fa-building mr-1"></i> ID: <?= (int) $insurance_id ?></span>
        </div>
        <div class="card-body">
          <form method="POST" action="<?= BASE_URL ?>/insurance/dashboard#addPatient">
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

    <!-- Patient table -->
    <div class="col-lg-7 mb-4 anchor-offset" id="patients">
      <div class="card shadow h-100">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
          <h6 class="m-0 font-weight-bold text-success">
            <i class="fas fa-users mr-2"></i> Patients (<?= e($insurance_name) ?>)
          </h6>
          <form class="d-flex mt-2 mt-md-0"
                method="GET"
                action="<?= BASE_URL ?>/insurance/dashboard#patients"
                style="gap:8px; flex:1; max-width:500px;">
            <input class="form-control form-control-lg"
                   name="q"
                   value="<?= e($q) ?>"
                   placeholder="Search by name, national ID, or phone"
                   style="font-size:.95rem;">
            <button class="btn btn-success btn-lg" type="submit" style="min-width:50px;">
              <i class="fas fa-search"></i>
            </button>
          </form>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
              <thead class="thead-light">
                <tr>
                  <th>ID</th><th>Name</th><th>National ID</th><th>Phone</th>
                  <th>Gender</th><th>Plan</th><th>Status</th><th>Policy #</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$patients): ?>
                  <tr><td colspan="9" class="text-center text-muted">No patients found.</td></tr>
                <?php else: ?>
                  <?php foreach ($patients as $p): ?>
                    <tr>
                      <td><?= (int) $p['patient_id'] ?></td>
                      <td><?= e($p['full_name']) ?></td>
                      <td><?= e($p['national_id']) ?></td>
                      <td><?= e($p['phone']) ?></td>
                      <td><?= e($p['gender']) ?></td>
                      <td><?= e($p['plan_name'] ?? '-') ?></td>
                      <td>
                        <?php
                          $st = $p['status'] ?? '';
                          if      ($st === 'active')    echo '<span class="badge badge-success">active</span>';
                          elseif  ($st === 'suspended') echo '<span class="badge badge-warning">suspended</span>';
                          elseif  ($st === 'expired')   echo '<span class="badge badge-secondary">expired</span>';
                          else                          echo '<span class="badge badge-light">no policy</span>';
                        ?>
                      </td>
                      <td><?= e($p['policy_number'] ?? '-') ?></td>
                      <td style="white-space:nowrap;">
                        <a class="btn btn-sm btn-outline-success"
                           href="<?= BASE_URL ?>/insurance/addPolicy?patient_id=<?= (int) $p['patient_id'] ?>">
                          <i class="fas fa-plus"></i> Add Policy
                        </a>
                        <a class="btn btn-sm btn-outline-secondary ml-1"
                           href="<?= BASE_URL ?>/insurance/viewRecords?patient_id=<?= (int) $p['patient_id'] ?>">
                          <i class="fas fa-folder-open mr-1"></i> Records
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
  <!-- ── /ADD + PATIENTS ──────────────────────────────── -->

  <!-- ── CLAIMS + DECISIONS ──────────────────────────── -->
  <div class="row">
    <div class="col-xl-6 col-md-6 mb-4 anchor-offset" id="claimManagement">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-header font-weight-bold text-primary">
          <i class="fas fa-file-medical mr-2"></i> Claim Management
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered mb-0">
              <thead class="thead-light">
                <tr><th>Claim ID</th><th>Patient</th><th>Hospital</th><th>Amount</th><th>Status</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td>CL001</td><td>John Doe</td><td>City Hospital</td><td>EGP 5,000</td>
                  <td><span class="badge badge-warning">Under Review</span></td>
                </tr>
                <tr>
                  <td>CL002</td><td>Jane Smith</td><td>Metro Clinic</td><td>EGP 12,000</td>
                  <td><span class="badge badge-success">Approved</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-6 col-md-6 mb-4 anchor-offset" id="claimDecision">
      <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-header font-weight-bold text-warning">
          <i class="fas fa-gavel mr-2"></i> Claim Decision Processing
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
              <thead class="thead-light">
                <tr><th>Claim ID</th><th>Status</th><th>Notes</th><th>Updated At</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td>CL001</td>
                  <td><span class="badge badge-warning">Under Review</span></td>
                  <td>-</td><td>2026-01-14 09:00</td>
                </tr>
                <tr>
                  <td>CL003</td>
                  <td><span class="badge badge-danger">Rejected</span></td>
                  <td>More documents needed</td><td>2026-01-12 15:30</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── INSURANCE PROFILE ───────────────────────────── -->
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
              <p class="mb-2"><strong>Insurance ID:</strong> <?= (int) $insurance_id ?></p>
              <p class="mb-2"><strong>Regulatory License:</strong> FRA-MED-2024-<?= str_pad($insurance_id, 4, '0', STR_PAD_LEFT) ?></p>
              <p class="mb-2"><strong>Registration Date:</strong> January 2024</p>
            </div>
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-clipboard-list mr-2"></i>Available Plans</h6>
              <p class="mb-1"><strong>Normal – Individual:</strong> Essential health coverage for individuals</p>
              <p class="mb-1"><strong>Normal – Company:</strong> Group coverage for corporate employees</p>
              <p class="mb-1"><strong>VIP – Individual:</strong> Premium coverage with private hospital access</p>
              <p class="mb-1"><strong>VIP – Company:</strong> Elite corporate health benefits package</p>
              <a href="<?= BASE_URL ?>/insurance/policy" class="btn btn-sm btn-success mt-2">
                <i class="fas fa-cog mr-1"></i> Configure Policies
              </a>
            </div>
          </div>

          <hr>

          <div class="row">
            <div class="col-md-6">
              <h6 class="text-success mb-3"><i class="fas fa-hospital mr-2"></i>Contracted Network Hospitals</h6>
              <p class="mb-1">• El Shifa Hospital</p>
              <p class="mb-1">• Cleopatra Hospital</p>
              <p class="mb-1">• Air Force Hospital</p>
              <p class="mb-1">• Nasaeem Hospital</p>
            </div>
            <div class="col-md-6">
              <h6 class="text-warning mb-3"><i class="fas fa-medkit mr-2"></i>Medical Services Covered</h6>
              <p class="mb-1">✓ <strong>Checkup / Consultation:</strong> 100% up to EGP 10,000</p>
              <p class="mb-1">✓ <strong>Operations / Surgery:</strong> 80% up to EGP 1,000,000</p>
              <p class="mb-1">✓ <strong>Maternity Care:</strong> 70% up to EGP 50,000</p>
              <p class="mb-1">✓ <strong>Dental Services:</strong> 60% up to EGP 30,000</p>
              <p class="mb-1">✓ <strong>Optical Services:</strong> 50% up to EGP 20,000</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /container-fluid -->

<footer class="sticky-footer bg-white mt-4">
  <div class="container my-auto text-center">
    <span>Copyright &copy; SmartConnect 2026</span>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/jquery.easing.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sb-admin-2.min.js"></script>

</body>
</html>
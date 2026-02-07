<?php
/**
 * Policy.php - OOP Version
 * Insurance staff views and manages all 4 policy configurations
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/InsurancePlan.php';
require_once __DIR__ . '/PolicyAutoSetup.php';
require_once __DIR__ . '/Validator.php';

$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// Check authentication
$auth->checkStaffAuth("INSURANCE_STAFF");

$insurance_id = (int)$auth->getSessionData("insurance_id");
if ($insurance_id <= 0) die("Missing insurance_id in session");

$insurancePlan = new InsurancePlan($conn);
$insurance_name = $auth->getSessionData("insurance_name") ?? "Medical Insurance";

// Auto-create all 4 policies if they don't exist
$autoSetup = new PolicyAutoSetup($conn);
if (!$autoSetup->hasAllPolicies($insurance_id)) {
    $autoSetup->createDefaultPolicies($insurance_id);
}

// Get all 4 policy combinations
$policies = $insurancePlan->getAllPoliciesForInsurance($insurance_id);

// Check completion status
$completion_status = $insurancePlan->getPolicyCompletionStatus($insurance_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Policy Management - <?= Validator::sanitizeInput($insurance_name) ?></title>

  <link href="css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    .policy-card {
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    .policy-card:hover {
      box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
    }
    .policy-status {
      position: absolute;
      top: 15px;
      right: 15px;
    }
    .service-badge {
      display: inline-block;
      margin: 3px;
      padding: 5px 10px;
      font-size: 0.75rem;
      border-radius: 15px;
    }
    .completion-bar {
      height: 8px;
      border-radius: 4px;
      background: #e9ecef;
      overflow: hidden;
    }
    .completion-bar .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #28a745, #20c997);
      transition: width 0.5s ease;
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
        <li class="nav-item"><a class="nav-link" href="InsuranceDashboard.php#dashboard"><i class="fas fa-tachometer-alt mr-1"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="InsuranceDashboard.php#patients"><i class="fas fa-users mr-1"></i> Patients</a></li>
        <li class="nav-item active"><a class="nav-link" href="Policy.php"><i class="fas fa-file-contract mr-1"></i> Policy Management</a></li>
      </ul>

      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
            data-toggle="dropdown">
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

  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="text-gray-800 mb-1">
        <i class="fas fa-file-contract mr-2 text-success"></i>
        Policy Management
      </h3>
      <p class="text-muted mb-0">Configure and manage all insurance policy types</p>
    </div>
    
    <div class="text-right">
      <a href="CreateAllPolicies.php" class="btn btn-primary mb-2">
        <i class="fas fa-plus-circle mr-2"></i>
        Configure All 4 Policies
      </a>
      <div class="mb-2">
        <small class="text-muted d-block">Overall Completion</small>
        <h4 class="mb-1">
          <?= $completion_status['completed'] ?> / <?= $completion_status['total'] ?> Policies
        </h4>
      </div>
      <div class="completion-bar" style="width: 200px;">
        <div class="progress-fill" style="width: <?= $completion_status['percentage'] ?>%"></div>
      </div>
      <small class="text-muted"><?= $completion_status['percentage'] ?>% Complete</small>
    </div>
  </div>

  <?php if ($completion_status['completed'] < $completion_status['total']): ?>
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="fas fa-info-circle mr-2"></i>
    <strong>Notice:</strong> All 4 policy types have been automatically created with default values. You can now customize each policy as needed.
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
  <?php else: ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle mr-2"></i>
    <strong>Complete!</strong> All 4 policy types are configured and ready to use.
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
  <?php endif; ?>

  <!-- Policy Grid -->
  <div class="row">
    <?php foreach ($policies as $policy): ?>
    <div class="col-lg-6 mb-4">
      <div class="card policy-card shadow-sm h-100">
        <div class="card-header bg-white py-3 position-relative">
          <div class="policy-status">
            <?php if ($policy['is_configured']): ?>
              <span class="badge badge-success">
                <i class="fas fa-check-circle mr-1"></i> Configured
              </span>
            <?php else: ?>
              <span class="badge badge-warning">
                <i class="fas fa-exclamation-circle mr-1"></i> Not Configured
              </span>
            <?php endif; ?>
          </div>
          
          <h5 class="mb-1 font-weight-bold text-primary">
            <i class="fas <?= $policy['category_id'] == 1 ? 'fa-user' : 'fa-crown' ?> mr-2"></i>
            <?= Validator::sanitizeInput($policy['category_name']) ?> - <?= Validator::sanitizeInput($policy['customer_type_name']) ?>
          </h5>
          <small class="text-muted">
            Policy ID: <?= $policy['plan_id'] ?? 'Not Created' ?>
          </small>
        </div>

        <div class="card-body">
          <?php if ($policy['is_configured']): ?>
            <!-- Show configured services -->
            <div class="mb-3">
              <h6 class="text-gray-700 mb-2">
                <i class="fas fa-medkit mr-1"></i> Covered Services
              </h6>
              <div>
                <?php 
                $services = $insurancePlan->getServicesByPlanId($policy['plan_id']);
                foreach ($services as $service): 
                  if ($service['is_enabled']):
                ?>
                  <span class="service-badge bg-success text-white">
                    <i class="fas fa-check mr-1"></i>
                    <?= Validator::sanitizeInput($service['service_name']) ?>
                    (<?= $service['coverage_percent'] ?>%)
                  </span>
                <?php 
                  endif;
                endforeach; 
                ?>
              </div>
            </div>

            <!-- Quick Stats -->
            <div class="row text-center mt-3 pt-3 border-top">
              <div class="col-4">
                <small class="text-muted d-block">Services</small>
                <strong class="text-success"><?= count(array_filter($services, fn($s) => $s['is_enabled'])) ?></strong>
              </div>
              <div class="col-4">
                <small class="text-muted d-block">Last Updated</small>
                <strong><?= date('M d, Y', strtotime($policy['updated_at'] ?? 'now')) ?></strong>
              </div>
              <div class="col-4">
                <small class="text-muted d-block">Status</small>
                <strong class="text-success">Active</strong>
              </div>
            </div>

          <?php else: ?>
            <div class="text-center py-4">
              <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
              <p class="text-muted mb-0">This policy type has not been configured yet.</p>
              <small class="text-muted">Click "Configure Policy" below to set it up.</small>
            </div>
          <?php endif; ?>
        </div>

        <div class="card-footer bg-white border-top">
          <a href="EditPolicy.php?category_id=<?= $policy['category_id'] ?>&customer_type_id=<?= $policy['customer_type_id'] ?>" 
             class="btn btn-primary btn-block">
            <i class="fas fa-edit mr-2"></i> <?= $policy['is_configured'] ? 'Edit Policy' : 'Configure Policy' ?>
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Information Section -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h6 class="mb-0">
            <i class="fas fa-info-circle mr-2"></i> Policy Configuration Guide
          </h6>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6 class="text-primary mb-3">Default Policy Types (Auto-Created)</h6>
              <p class="mb-3">All insurance providers automatically have 4 policy types created with default coverage values:</p>
              <ul class="list-unstyled">
                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Normal - Individual</li>
                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Normal - Company</li>
                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> VIP - Individual</li>
                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> VIP - Company</li>
              </ul>
              <small class="text-muted">You can customize each policy to match your business needs.</small>
            </div>
            <div class="col-md-6">
              <h6 class="text-primary mb-3">Available Services</h6>
              <p class="mb-3">Each policy includes these medical services by default:</p>
              <ul class="list-unstyled">
                <li class="mb-2"><i class="fas fa-stethoscope text-info mr-2"></i> Checkup/Consultation (Required)</li>
                <li class="mb-2"><i class="fas fa-procedures text-info mr-2"></i> Operations/Surgery (Required)</li>
                <li class="mb-2"><i class="fas fa-baby text-info mr-2"></i> Maternity (Included)</li>
                <li class="mb-2"><i class="fas fa-tooth text-info mr-2"></i> Dental (Included)</li>
                <li class="mb-2"><i class="fas fa-glasses text-info mr-2"></i> Optical (Included)</li>
              </ul>
              <small class="text-muted">VIP policies have better coverage rates and higher limits.</small>
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
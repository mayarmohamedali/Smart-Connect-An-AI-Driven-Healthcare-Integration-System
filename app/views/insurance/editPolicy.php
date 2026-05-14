<?php
// Variables from InsuranceController::editPolicy()
// $insurance_id, $insurance_name, $category_id, $customer_type_id
// $category_name, $customer_type_name, $existing_plan, $is_editing, $service_data
function e($v): string { return htmlspecialchars((string)($v??''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $is_editing ? 'Edit' : 'Create' ?> Policy - <?= $category_name ?> <?= $customer_type_name ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="<?= BASE_URL ?>/assets/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    .service-section {
      background: #f8f9fc;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      border-left: 4px solid #4e73df;
    }
    .service-section.disabled {
      opacity: 0.6;
      border-left-color: #858796;
    }
    .service-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid #dee2e6;
    }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }
    .required-badge {
      background: #e74a3b;
      color: white;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 0.7rem;
      font-weight: bold;
    }
    .optional-badge {
      background: #858796;
      color: white;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 0.7rem;
    }
    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 24px;
    }
    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 24px;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 16px;
      width: 16px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    input:checked + .slider {
      background-color: #1cc88a;
    }
    input:checked + .slider:before {
      transform: translateX(26px);
    }
  </style>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/insurance/dashboard">
      <i class="fas fa-shield-alt mr-2"></i>
      <strong><?= Validator::sanitizeInput($insurance_name) ?></strong>
    </a>
    <a href="<?= BASE_URL ?>/insurance/policy" class="btn btn-light btn-sm ml-auto">
      <i class="fas fa-arrow-left mr-1"></i> Back to Policies
    </a>
  </div>
</nav>

<div class="container mt-4 mb-5">

  <!-- Header -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3 class="mb-1 text-primary">
            <i class="fas <?= $category_id == 1 ? 'fa-user' : 'fa-crown' ?> mr-2"></i>
            <?= $is_editing ? 'Edit' : 'Configure' ?> Policy: <?= $category_name ?> - <?= $customer_type_name ?>
          </h3>
          <p class="text-muted mb-0">
            <?= $is_editing ? 'Update the settings for this policy type' : 'Set up coverage rules and service parameters' ?>
          </p>
        </div>
        <div>
          <?php if ($is_editing): ?>
            <span class="badge badge-success badge-lg p-2">
              <i class="fas fa-check-circle mr-1"></i> Existing Policy
            </span>
          <?php else: ?>
            <span class="badge badge-warning badge-lg p-2">
              <i class="fas fa-plus-circle mr-1"></i> New Policy
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Policy Form -->
  <form method="POST" action="<?= BASE_URL ?>/insurance/savePolicy" id="policyForm">
    <input type="hidden" name="category_id" value="<?= $category_id ?>">
    <input type="hidden" name="customer_type_id" value="<?= $customer_type_id ?>">

    <!-- Required Services -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
          <i class="fas fa-star mr-2"></i> Required Services
        </h5>
      </div>
      <div class="card-body">
        
        <!-- CHECKUP -->
        <div class="service-section" id="service-checkup">
          <div class="service-header">
            <div>
              <h5 class="mb-0">
                <i class="fas fa-stethoscope text-primary mr-2"></i>
                Checkup / Consultation
                <span class="required-badge ml-2">REQUIRED</span>
              </h5>
            </div>
          </div>
          
          <input type="hidden" name="coverage_services[]" value="checkup">
          
          <div class="form-grid">
            <div class="form-group">
              <label>Coverage (%)</label>
              <input type="number" class="form-control" name="coverage_checkup" 
                     value="<?= $service_data[1]['coverage_percent'] ?? 100 ?>" 
                     min="0" max="100" required>
              <small class="text-muted">Percentage covered by insurance</small>
            </div>
            
            <div class="form-group">
              <label>Threshold (EGP)</label>
              <input type="number" class="form-control" name="threshold_checkup" 
                     value="<?= $service_data[1]['threshold_egp'] ?? 10000 ?>" 
                     min="0" required>
              <small class="text-muted">Maximum coverage limit</small>
            </div>
            
            <div class="form-group">
              <label>Co-Payment (%)</label>
              <input type="number" class="form-control" name="copay_checkup" 
                     value="<?= $service_data[1]['copayment_percent'] ?? 0 ?>" 
                     min="0" max="100" required>
              <small class="text-muted">Patient pays this %</small>
            </div>
            
            <div class="form-group">
              <label>Deductible (EGP)</label>
              <input type="number" class="form-control" name="deductible_checkup" 
                     value="<?= $service_data[1]['deductible_egp'] ?? 0 ?>" 
                     min="0" required>
              <small class="text-muted">Patient pays first</small>
            </div>
          </div>
        </div>

        <!-- OPERATIONS -->
        <div class="service-section" id="service-operations">
          <div class="service-header">
            <div>
              <h5 class="mb-0">
                <i class="fas fa-procedures text-primary mr-2"></i>
                Operations / Surgery
                <span class="required-badge ml-2">REQUIRED</span>
              </h5>
            </div>
          </div>
          
          <input type="hidden" name="coverage_services[]" value="operations">
          
          <div class="form-grid">
            <div class="form-group">
              <label>Coverage (%)</label>
              <input type="number" class="form-control" name="coverage_operations" 
                     value="<?= $service_data[2]['coverage_percent'] ?? 80 ?>" 
                     min="0" max="100" required>
            </div>
            
            <div class="form-group">
              <label>Threshold (EGP)</label>
              <input type="number" class="form-control" name="threshold_operations" 
                     value="<?= $service_data[2]['threshold_egp'] ?? 1000000 ?>" 
                     min="0" required>
            </div>
            
            <div class="form-group">
              <label>Co-Payment (%)</label>
              <input type="number" class="form-control" name="copay_operations" 
                     value="<?= $service_data[2]['copayment_percent'] ?? 20 ?>" 
                     min="0" max="100" required>
            </div>
            
            <div class="form-group">
              <label>Deductible (EGP)</label>
              <input type="number" class="form-control" name="deductible_operations" 
                     value="<?= $service_data[2]['deductible_egp'] ?? 5000 ?>" 
                     min="0" required>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Optional Services -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-secondary text-white">
        <h5 class="mb-0">
          <i class="fas fa-plus-circle mr-2"></i> Optional Services
        </h5>
      </div>
      <div class="card-body">

        <!-- MATERNITY -->
        <div class="service-section <?= empty($service_data[3]) || !$service_data[3]['is_enabled'] ? 'disabled' : '' ?>" 
             id="service-maternity">
          <div class="service-header">
            <div>
              <h5 class="mb-0">
                <i class="fas fa-baby text-info mr-2"></i>
                Maternity Care
                <span class="optional-badge ml-2">OPTIONAL</span>
              </h5>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" name="coverage_services[]" value="maternity" 
                     onchange="toggleServiceSection('maternity')"
                     <?= !empty($service_data[3]) && $service_data[3]['is_enabled'] ? 'checked' : '' ?>>
              <span class="slider"></span>
            </label>
          </div>
          
          <div class="form-grid service-inputs">
            <div class="form-group">
              <label>Coverage (%)</label>
              <input type="number" class="form-control" name="coverage_maternity" 
                     value="<?= $service_data[3]['coverage_percent'] ?? 70 ?>" 
                     min="0" max="100">
            </div>
            
            <div class="form-group">
              <label>Threshold (EGP)</label>
              <input type="number" class="form-control" name="threshold_maternity" 
                     value="<?= $service_data[3]['threshold_egp'] ?? 50000 ?>" 
                     min="0">
            </div>
            
            <div class="form-group">
              <label>Co-Payment (%)</label>
              <input type="number" class="form-control" name="copay_maternity" 
                     value="<?= $service_data[3]['copayment_percent'] ?? 30 ?>" 
                     min="0" max="100">
            </div>
            
            <div class="form-group">
              <label>Deductible (EGP)</label>
              <input type="number" class="form-control" name="deductible_maternity" 
                     value="<?= $service_data[3]['deductible_egp'] ?? 2000 ?>" 
                     min="0">
            </div>
          </div>
        </div>

        <!-- DENTAL -->
        <div class="service-section <?= empty($service_data[4]) || !$service_data[4]['is_enabled'] ? 'disabled' : '' ?>" 
             id="service-dental">
          <div class="service-header">
            <div>
              <h5 class="mb-0">
                <i class="fas fa-tooth text-info mr-2"></i>
                Dental Services
                <span class="optional-badge ml-2">OPTIONAL</span>
              </h5>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" name="coverage_services[]" value="dental" 
                     onchange="toggleServiceSection('dental')"
                     <?= !empty($service_data[4]) && $service_data[4]['is_enabled'] ? 'checked' : '' ?>>
              <span class="slider"></span>
            </label>
          </div>
          
          <div class="form-grid service-inputs">
            <div class="form-group">
              <label>Coverage (%)</label>
              <input type="number" class="form-control" name="coverage_dental" 
                     value="<?= $service_data[4]['coverage_percent'] ?? 60 ?>" 
                     min="0" max="100">
            </div>
            
            <div class="form-group">
              <label>Threshold (EGP)</label>
              <input type="number" class="form-control" name="threshold_dental" 
                     value="<?= $service_data[4]['threshold_egp'] ?? 30000 ?>" 
                     min="0">
            </div>
            
            <div class="form-group">
              <label>Co-Payment (%)</label>
              <input type="number" class="form-control" name="copay_dental" 
                     value="<?= $service_data[4]['copayment_percent'] ?? 40 ?>" 
                     min="0" max="100">
            </div>
            
            <div class="form-group">
              <label>Deductible (EGP)</label>
              <input type="number" class="form-control" name="deductible_dental" 
                     value="<?= $service_data[4]['deductible_egp'] ?? 1000 ?>" 
                     min="0">
            </div>
          </div>
        </div>

        <!-- OPTICAL -->
        <div class="service-section <?= empty($service_data[5]) || !$service_data[5]['is_enabled'] ? 'disabled' : '' ?>" 
             id="service-optical">
          <div class="service-header">
            <div>
              <h5 class="mb-0">
                <i class="fas fa-glasses text-info mr-2"></i>
                Optical Services
                <span class="optional-badge ml-2">OPTIONAL</span>
              </h5>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" name="coverage_services[]" value="optical" 
                     onchange="toggleServiceSection('optical')"
                     <?= !empty($service_data[5]) && $service_data[5]['is_enabled'] ? 'checked' : '' ?>>
              <span class="slider"></span>
            </label>
          </div>
          
          <div class="form-grid service-inputs">
            <div class="form-group">
              <label>Coverage (%)</label>
              <input type="number" class="form-control" name="coverage_optical" 
                     value="<?= $service_data[5]['coverage_percent'] ?? 50 ?>" 
                     min="0" max="100">
            </div>
            
            <div class="form-group">
              <label>Threshold (EGP)</label>
              <input type="number" class="form-control" name="threshold_optical" 
                     value="<?= $service_data[5]['threshold_egp'] ?? 20000 ?>" 
                     min="0">
            </div>
            
            <div class="form-group">
              <label>Co-Payment (%)</label>
              <input type="number" class="form-control" name="copay_optical" 
                     value="<?= $service_data[5]['copayment_percent'] ?? 50 ?>" 
                     min="0" max="100">
            </div>
            
            <div class="form-group">
              <label>Deductible (EGP)</label>
              <input type="number" class="form-control" name="deductible_optical" 
                     value="<?= $service_data[5]['deductible_egp'] ?? 500 ?>" 
                     min="0">
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Submit -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <a href="<?= BASE_URL ?>/insurance/policy" class="btn btn-secondary">
            <i class="fas fa-times mr-2"></i> Cancel
          </a>
          <button type="submit" class="btn btn-success btn-lg">
            <i class="fas fa-save mr-2"></i>
            <?= $is_editing ? 'Update Policy' : 'Create Policy' ?>
          </button>
        </div>
      </div>
    </div>

  </form>

</div>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>

<script>
function toggleServiceSection(serviceName) {
  const section = document.getElementById('service-' + serviceName);
  const checkbox = section.querySelector('input[type="checkbox"][value="' + serviceName + '"]');
  const inputs = section.querySelectorAll('.service-inputs input');
  
  if (checkbox.checked) {
    section.classList.remove('disabled');
    inputs.forEach(input => input.removeAttribute('disabled'));
  } else {
    section.classList.add('disabled');
    inputs.forEach(input => input.setAttribute('disabled', 'disabled'));
  }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  ['maternity', 'dental', 'optical'].forEach(service => {
    toggleServiceSection(service);
  });
});
</script>

</body>
</html>
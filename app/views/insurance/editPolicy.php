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
  <title><?= $is_editing ? 'Edit' : 'Create' ?> Policy — <?= e($category_name) ?> <?= e($customer_type_name) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="<?= BASE_URL ?>/assets/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    /* ── Layout ──────────────────────────────────────────── */
    .service-section {
      background: #f8f9fc; border-radius: 8px;
      padding: 20px; margin-bottom: 20px;
      border-left: 4px solid #4e73df;
      transition: opacity .2s;
    }
    .service-section.disabled { opacity: 0.5; border-left-color: #858796; }
    .service-header {
      display: flex; justify-content: space-between;
      align-items: center; margin-bottom: 15px;
      padding-bottom: 10px; border-bottom: 2px solid #dee2e6;
    }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }
    .required-badge {
      background: #e74a3b; color: #fff;
      padding: 2px 8px; border-radius: 10px;
      font-size: .7rem; font-weight: 700;
    }
    .optional-badge {
      background: #858796; color: #fff;
      padding: 2px 8px; border-radius: 10px;
      font-size: .7rem;
    }

    /* ── Toggle switch ───────────────────────────────────── */
    .toggle-switch { position: relative; display: inline-block; width: 50px; height: 24px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider {
      position: absolute; cursor: pointer;
      inset: 0; background: #ccc;
      transition: .4s; border-radius: 24px;
    }
    .slider:before {
      position: absolute; content: "";
      height: 16px; width: 16px;
      left: 4px; bottom: 4px;
      background: #fff; transition: .4s; border-radius: 50%;
    }
    input:checked + .slider { background: #1cc88a; }
    input:checked + .slider:before { transform: translateX(26px); }

    /* ── Validation ──────────────────────────────────────── */
    .field-error {
      display: none; font-size: 11.5px;
      color: #e74a3b; margin-top: 3px; font-weight: 600;
    }
    .field-error.visible { display: block; }
    .form-control.is-invalid { border-color: #e74a3b !important; background-image: none; }
    .form-control.is-valid   { border-color: #1cc88a !important; background-image: none; }

    /* ── Coverage + copay sum warning ────────────────────── */
    .sum-warning {
      display: none; font-size: 11.5px; font-weight: 600;
      color: #856404; background: #fff3cd;
      border: 1px solid #ffc107; border-radius: 4px;
      padding: 4px 8px; margin-top: 6px;
    }
    .sum-warning.visible { display: block; }

    /* ── Submit error summary ────────────────────────────── */
    #validationSummary {
      display: none; margin-bottom: 16px;
      border-left: 4px solid #e74a3b;
      background: #fff5f5; padding: 10px 14px;
      border-radius: 6px; font-size: 13px; color: #c0392b;
    }
    #validationSummary strong { display: block; margin-bottom: 4px; }
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
            <?= $is_editing ? 'Edit' : 'Configure' ?> Policy: <?= e($category_name) ?> — <?= e($customer_type_name) ?>
          </h3>
          <p class="text-muted mb-0">
            <?= $is_editing ? 'Update the settings for this policy type.' : 'Set up coverage rules and service parameters.' ?>
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

  <!-- Validation summary -->
  <div id="validationSummary">
    <strong><i class="fas fa-exclamation-triangle mr-1"></i> Please fix the following before saving:</strong>
    <ul id="summaryList" style="margin:0;padding-left:18px;"></ul>
  </div>

  <!-- Policy Form -->
  <form method="POST" action="<?= BASE_URL ?>/insurance/savePolicy" id="policyForm" novalidate>
    <input type="hidden" name="category_id"       value="<?= (int)$category_id ?>">
    <input type="hidden" name="customer_type_id"  value="<?= (int)$customer_type_id ?>">

    <!-- ══ REQUIRED SERVICES ══════════════════════════════════════════ -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-star mr-2"></i> Required Services</h5>
      </div>
      <div class="card-body">

        <?php
        $services = [
          ['checkup',    1, 'fa-stethoscope', 'Checkup / Consultation',  true,  [100, 10000,   0,    0]],
          ['operations', 2, 'fa-procedures',  'Operations / Surgery',    true,  [80,  1000000, 20,   5000]],
          ['maternity',  3, 'fa-baby',        'Maternity Care',          false, [70,  50000,   30,   2000]],
          ['dental',     4, 'fa-tooth',       'Dental Services',         false, [60,  30000,   40,   1000]],
          ['optical',    5, 'fa-glasses',     'Optical Services',        false, [50,  20000,   50,    500]],
        ];

        $required_svcs = array_filter($services, fn($s) => $s[4]);
        $optional_svcs = array_filter($services, fn($s) => !$s[4]);

        function renderService($svc, $service_data): void {
          [$key, $dataId, $icon, $label, $required, $defaults] = $svc;
          [$defCov, $defThresh, $defCopay, $defDed] = $defaults;

          $enabled   = $required || (!empty($service_data[$dataId]) && $service_data[$dataId]['is_enabled']);
          $cov       = $service_data[$dataId]['coverage_percent']   ?? $defCov;
          $thresh    = $service_data[$dataId]['threshold_egp']      ?? $defThresh;
          $copay     = $service_data[$dataId]['copayment_percent']   ?? $defCopay;
          $ded       = $service_data[$dataId]['deductible_egp']      ?? $defDed;
          $iconColor = $required ? 'text-primary' : 'text-info';
          $disClass  = (!$required && !$enabled) ? ' disabled' : '';
          $reqAttr   = $required ? 'required' : '';
          ?>
          <div class="service-section<?= $disClass ?>" id="service-<?= $key ?>">
            <div class="service-header">
              <h5 class="mb-0">
                <i class="fas <?= $icon ?> <?= $iconColor ?> mr-2"></i>
                <?= htmlspecialchars($label) ?>
                <?php if ($required): ?>
                  <span class="required-badge ml-2">REQUIRED</span>
                <?php else: ?>
                  <span class="optional-badge ml-2">OPTIONAL</span>
                <?php endif; ?>
              </h5>
              <?php if (!$required): ?>
                <label class="toggle-switch mb-0">
                  <input type="checkbox" name="coverage_services[]" value="<?= $key ?>"
                         onchange="toggleServiceSection('<?= $key ?>')"
                         <?= $enabled ? 'checked' : '' ?>>
                  <span class="slider"></span>
                </label>
              <?php else: ?>
                <input type="hidden" name="coverage_services[]" value="<?= $key ?>">
              <?php endif; ?>
            </div>

            <div class="form-grid service-inputs">

              <!-- Coverage % -->
              <div class="form-group mb-0">
                <label>Coverage (%) <span class="text-danger">*</span></label>
                <input type="number" class="form-control svc-coverage"
                       name="coverage_<?= $key ?>" id="cov_<?= $key ?>"
                       value="<?= (int)$cov ?>" min="0" max="100"
                       data-service="<?= $key ?>" <?= $reqAttr ?>
                       <?= (!$required && !$enabled) ? 'disabled' : '' ?>>
                <div class="field-error" id="err_cov_<?= $key ?>"></div>
              </div>

              <!-- Threshold -->
              <div class="form-group mb-0">
                <label>Threshold (EGP) <span class="text-danger">*</span></label>
                <input type="number" class="form-control"
                       name="threshold_<?= $key ?>" id="thresh_<?= $key ?>"
                       value="<?= (int)$thresh ?>" min="0" <?= $reqAttr ?>
                       <?= (!$required && !$enabled) ? 'disabled' : '' ?>>
                <div class="field-error" id="err_thresh_<?= $key ?>"></div>
              </div>

              <!-- Co-payment % -->
              <div class="form-group mb-0">
                <label>Co-Payment (%) <span class="text-danger">*</span></label>
                <input type="number" class="form-control svc-copay"
                       name="copay_<?= $key ?>" id="copay_<?= $key ?>"
                       value="<?= (int)$copay ?>" min="0" max="100"
                       data-service="<?= $key ?>" <?= $reqAttr ?>
                       <?= (!$required && !$enabled) ? 'disabled' : '' ?>>
                <div class="field-error" id="err_copay_<?= $key ?>"></div>
                <div class="sum-warning" id="sum_<?= $key ?>">
                  <i class="fas fa-exclamation-triangle mr-1"></i>
                  Coverage + Co-Payment should not exceed 100%.
                </div>
              </div>

              <!-- Deductible -->
              <div class="form-group mb-0">
                <label>Deductible (EGP) <span class="text-danger">*</span></label>
                <input type="number" class="form-control"
                       name="deductible_<?= $key ?>" id="ded_<?= $key ?>"
                       value="<?= (int)$ded ?>" min="0" <?= $reqAttr ?>
                       <?= (!$required && !$enabled) ? 'disabled' : '' ?>>
                <div class="field-error" id="err_ded_<?= $key ?>"></div>
              </div>

            </div><!-- /.form-grid -->
          </div><!-- /.service-section -->
          <?php
        }

        foreach ($required_svcs as $svc) renderService($svc, $service_data);
        ?>

      </div>
    </div>

    <!-- ══ OPTIONAL SERVICES ══════════════════════════════════════════ -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle mr-2"></i> Optional Services</h5>
      </div>
      <div class="card-body">
        <?php foreach ($optional_svcs as $svc) renderService($svc, $service_data); ?>
      </div>
    </div>

    <!-- ══ SUBMIT ═══════════════════════════════════════════════════ -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <a href="<?= BASE_URL ?>/insurance/policy" class="btn btn-secondary">
            <i class="fas fa-times mr-2"></i> Cancel
          </a>
          <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
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
const $id = id => document.getElementById(id);

function setError(id, msg) {
  const el = $id(id);
  if (!el) return;
  el.classList.remove('is-valid');
  el.classList.add('is-invalid');
  const err = $id('err_' + id);
  if (err) { err.textContent = msg; err.classList.add('visible'); }
}

function clearError(id) {
  const el = $id(id);
  if (!el) return;
  el.classList.remove('is-invalid');
  el.classList.add('is-valid');
  const err = $id('err_' + id);
  if (err) { err.textContent = ''; err.classList.remove('visible'); }
}

function clearState(id) {
  const el = $id(id);
  if (!el) return;
  el.classList.remove('is-invalid', 'is-valid');
  const err = $id('err_' + id);
  if (err) { err.textContent = ''; err.classList.remove('visible'); }
}

function numVal(id) {
  const v = parseFloat($id(id)?.value);
  return isNaN(v) ? null : v;
}

function toggleServiceSection(svcName) {
  const section  = $id('service-' + svcName);
  const checkbox = section.querySelector(`input[type="checkbox"][value="${svcName}"]`);
  const inputs   = section.querySelectorAll('.service-inputs input');
  const enabled  = checkbox.checked;

  section.classList.toggle('disabled', !enabled);
  inputs.forEach(inp => {
    if (enabled) {
      inp.removeAttribute('disabled');
    } else {
      inp.setAttribute('disabled', 'disabled');
      clearState(inp.id);
    }
  });

  if (enabled) checkSumWarning(svcName);
}

document.addEventListener('DOMContentLoaded', function () {
  ['maternity', 'dental', 'optical'].forEach(toggleServiceSection);
  ['checkup', 'operations'].forEach(checkSumWarning);
});

const ALL_SERVICES = ['checkup', 'operations', 'maternity', 'dental', 'optical'];

function validatePercent(fieldId, label) {
  const val = numVal(fieldId);
  if (val === null || val < 0 || val > 100) {
    setError(fieldId, `${label} must be between 0 and 100.`);
    return false;
  }
  clearError(fieldId);
  return true;
}

function validateThreshold(fieldId) {
  const val = numVal(fieldId);
  if (val === null || val < 0) {
    setError(fieldId, 'Threshold must be 0 or more.');
    return false;
  }
  clearError(fieldId);
  return true;
}

function validateDeductible(fieldId) {
  const val = numVal(fieldId);
  if (val === null || val < 0) {
    setError(fieldId, 'Deductible must be 0 or more.');
    return false;
  }
  clearError(fieldId);
  return true;
}

function checkSumWarning(svcName) {
  const cov   = numVal('cov_'   + svcName) ?? 0;
  const copay = numVal('copay_' + svcName) ?? 0;
  const warn  = $id('sum_' + svcName);
  if (!warn) return;
  if (cov + copay > 100) {
    warn.classList.add('visible');
  } else {
    warn.classList.remove('visible');
  }
}

ALL_SERVICES.forEach(svc => {
  const covEl = $id('cov_' + svc);
  if (covEl) {
    ['input', 'blur'].forEach(ev => covEl.addEventListener(ev, () => {
      validatePercent('cov_' + svc, 'Coverage');
      checkSumWarning(svc);
    }));
  }

  const thrEl = $id('thresh_' + svc);
  if (thrEl) {
    ['input', 'blur'].forEach(ev => thrEl.addEventListener(ev, () => {
      validateThreshold('thresh_' + svc);
    }));
  }

  const cpEl = $id('copay_' + svc);
  if (cpEl) {
    ['input', 'blur'].forEach(ev => cpEl.addEventListener(ev, () => {
      validatePercent('copay_' + svc, 'Co-Payment');
      checkSumWarning(svc);
    }));
  }

  const dedEl = $id('ded_' + svc);
  if (dedEl) {
    ['input', 'blur'].forEach(ev => dedEl.addEventListener(ev, () => {
      validateDeductible('ded_' + svc);
    }));
  }
});

$id('policyForm').addEventListener('submit', function (e) {
  const errors = [];

  ALL_SERVICES.forEach(svc => {
    const section = $id('service-' + svc);
    if (section.classList.contains('disabled')) return;

    const covId   = 'cov_'    + svc;
    const thrId   = 'thresh_' + svc;
    const cpId    = 'copay_'  + svc;
    const dedId   = 'ded_'    + svc;
    const label   = section.querySelector('h5')?.textContent?.trim()?.split('\n')[0]?.trim() || svc;

    if (!validatePercent(covId, 'Coverage')) {
      errors.push(`${label} — Coverage must be 0–100%`);
    }
    if (!validateThreshold(thrId)) {
      errors.push(`${label} — Threshold must be ≥ 0 EGP`);
    }
    if (!validatePercent(cpId, 'Co-Payment')) {
      errors.push(`${label} — Co-Payment must be 0–100%`);
    }
    if (!validateDeductible(dedId)) {
      errors.push(`${label} — Deductible must be ≥ 0 EGP`);
    }

    const cov   = numVal(covId)  ?? 0;
    const copay = numVal(cpId)   ?? 0;
    if (cov + copay > 100) {
      errors.push(`${label} — Coverage (${cov}%) + Co-Payment (${copay}%) exceeds 100%`);
    }
  });

  if (errors.length > 0) {
    e.preventDefault();
    const summary = $id('validationSummary');
    const list    = $id('summaryList');
    list.innerHTML = [...new Set(errors)].map(er => `<li>${er}</li>`).join('');
    summary.style.display = 'block';
    summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
</script>

</body>
</html>
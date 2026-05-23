<?php
function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$patient = $patientObj ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Patient Policy</title>

  <link href="<?= BASE_URL ?>/assets/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

  <a href="<?= BASE_URL ?>/insurance/dashboard" class="btn btn-outline-secondary mb-3">
    <i class="fas fa-arrow-left mr-1"></i> Back
  </a>

  <div class="card shadow">

    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">
        <i class="fas fa-file-signature mr-2"></i>
        Add Patient Policy — 
        <?= $patient ? e($patient->getFullName()) : 'Unknown Patient' ?>
      </h5>

      <small>
        National ID: <?= $patient ? e($patient->getNationalId()) : '-' ?> |
        Insurance: <?= e($insurance_name ?? '') ?>
      </small>
    </div>

    <div class="card-body">

      <?php if (!empty($success)): ?>
        <div class="alert alert-success">
          <?= e($success) ?><br>
          <small>Redirecting to dashboard...</small>
        </div>

        <script>
          setTimeout(function () {
            window.location.href = "<?= BASE_URL ?>/insurance/dashboard";
          }, 2000);
        </script>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" id="addPolicyForm" novalidate>

        <h6 class="text-primary font-weight-bold">
          <i class="fas fa-id-card mr-1"></i> Patient
        </h6>

        <div class="row">
          <div class="col-md-4 form-group">
            <label>Patient ID</label>
            <input class="form-control bg-light"
                   value="<?= $patient ? (int)$patient->getPatientId() : '' ?>"
                   readonly tabindex="-1">
          </div>

          <div class="col-md-4 form-group">
            <label>Phone</label>
            <input class="form-control bg-light"
                   value="<?= $patient ? e($patient->getPhone()) : '' ?>"
                   readonly tabindex="-1">
          </div>

          <div class="col-md-4 form-group">
            <label for="inp_status">Status <span class="text-danger">*</span></label>
            <select class="form-control" id="inp_status" name="status">
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
              <option value="expired">Expired</option>
            </select>
          </div>
        </div>

        <hr>

        <h6 class="text-primary font-weight-bold">
          <i class="fas fa-shield-alt mr-1"></i> Plan & Policy Details
        </h6>

        <div class="row">

          <div class="col-md-6 form-group">
            <label for="inp_plan">Insurance Plan <span class="text-danger">*</span></label>
            <select class="form-control" id="inp_plan" name="insurance_plan_id">
              <option value="">— Select a plan —</option>
              <?php foreach ($plans as $p): ?>
              <option value="<?= (int)$p['id'] ?>">
                <?= Validator::sanitizeInput($p['plan_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback" id="err_plan"></div>
          </div>

          <div class="col-md-6 form-group">
            <label for="inp_policy_number">Policy Number <span class="text-danger">*</span></label>
            <input class="form-control" id="inp_policy_number" name="policy_number"
                   placeholder="e.g. POL-2024-00123"
                   maxlength="50" autocomplete="off">
            <div class="invalid-feedback" id="err_policy_number"></div>
          </div>

        </div>

        <div class="row">

          <div class="col-md-6 form-group">
            <label for="inp_start_date">Start Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="inp_start_date" name="start_date">
            <div class="invalid-feedback" id="err_start_date"></div>
          </div>

          <div class="col-md-6 form-group">
            <label for="inp_end_date">End Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="inp_end_date" name="end_date">
            <div class="invalid-feedback" id="err_end_date"></div>
          </div>

        </div>

        <button class="btn btn-primary btn-block" type="submit" id="btnSavePolicy"
                <?= empty($plans) ? 'disabled' : '' ?>>
          <i class="fas fa-save mr-1"></i> Save Patient Policy
        </button>

      </form>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>

<script>
(function () {

  function el(id) { return document.getElementById(id); }
  function ok(id) {
    el('inp_' + id).classList.remove('is-invalid');
    el('inp_' + id).classList.add('is-valid');
    el('err_' + id).textContent = '';
  }
  function fail(id, m) {
    el('inp_' + id).classList.remove('is-valid');
    el('inp_' + id).classList.add('is-invalid');
    el('err_' + id).textContent = m;
  }

  function validatePlan() {
    var v = el('inp_plan').value;
    if (!v || v === '') { fail('plan', 'Please select an insurance plan.'); return false; }
    ok('plan'); return true;
  }

  function validatePolicyNumber() {
    var v = el('inp_policy_number').value.trim();
    if (v === '') { fail('policy_number', 'Policy number is required.'); return false; }
    if (v.length < 3) { fail('policy_number', 'Too short'); return false; }
    if (v.length > 50) { fail('policy_number', 'Too long'); return false; }
    ok('policy_number'); return true;
  }

  function validateStartDate() {
    var v = el('inp_start_date').value;
    if (!v) { fail('start_date', 'Start date is required.'); return false; }
    ok('start_date'); return true;
  }

  function validateEndDate() {
    var start = el('inp_start_date').value;
    var end = el('inp_end_date').value;

    if (!end) {
      fail('end_date', 'End date is required.');
      return false;
    }

    if (start && end <= start) {
      fail('end_date', 'End date must be after start date.');
      return false;
    }

    ok('end_date');
    return true;
  }

  el('inp_plan').addEventListener('change', validatePlan);
  el('inp_policy_number').addEventListener('blur', validatePolicyNumber);
  el('inp_start_date').addEventListener('change', validateStartDate);
  el('inp_end_date').addEventListener('change', validateEndDate);

  el('addPolicyForm').addEventListener('submit', function (e) {
    var valid = [
      validatePlan(),
      validatePolicyNumber(),
      validateStartDate(),
      validateEndDate()
    ].every(Boolean);

    if (!valid) e.preventDefault();
  });

})();
</script>

</body>
</html>
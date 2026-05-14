<?php
// Variables from InsuranceController::addPolicy()
// $patientObj (Patient), $insurance_name, $insurance_id, $plans, $success, $error

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// ✅ SAFETY: avoid crash if controller forgets naming
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

      <form method="POST">

        <h6 class="text-primary font-weight-bold">
          <i class="fas fa-id-card mr-1"></i> Patient
        </h6>

        <div class="row">
          <div class="col-md-4 form-group">
            <label>Patient ID</label>
            <input class="form-control"
                   value="<?= $patient ? (int)$patient->getPatientId() : '' ?>"
                   readonly>
          </div>

          <div class="col-md-4 form-group">
            <label>Phone</label>
            <input class="form-control"
                   value="<?= $patient ? e($patient->getPhone()) : '' ?>"
                   readonly>
          </div>

          <div class="col-md-4 form-group">
            <label>Status</label>
            <select class="form-control" name="status">
              <option value="active">active</option>
              <option value="suspended">suspended</option>
              <option value="expired">expired</option>
            </select>
          </div>
        </div>

        <hr>

        <h6 class="text-primary font-weight-bold">
          <i class="fas fa-shield-alt mr-1"></i> Plan & Policy Details
        </h6>

        <div class="row">

          <div class="col-md-6 form-group">
            <label>Insurance Plan</label>

            <select class="form-control" name="insurance_plan_id" required>
              <option value="">Select plan...</option>

              <?php foreach ($plans as $p): ?>
              <option value="<?= (int)$p["id"] ?>">
  <?= Validator::sanitizeInput($p["plan_name"]) ?>
</option>
              <?php endforeach; ?>
            </select>

            <?php if (empty($plans)): ?>
              <small class="text-danger">
                No plans found for this insurance. Create plans first.
              </small>
            <?php endif; ?>
          </div>

          <div class="col-md-6 form-group">
            <label>Policy Number</label>
            <input class="form-control" name="policy_number" required>
          </div>

        </div>

        <div class="row">

          <div class="col-md-6 form-group">
            <label>Start Date</label>
            <input type="date" class="form-control" name="start_date" required>
          </div>

          <div class="col-md-6 form-group">
            <label>End Date (optional)</label>
            <input type="date" class="form-control" name="end_date">
          </div>

        </div>

        <button class="btn btn-primary btn-block" type="submit"
                <?= empty($plans) ? "disabled" : "" ?>>
          <i class="fas fa-save mr-1"></i> Save Patient Policy
        </button>

      </form>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>
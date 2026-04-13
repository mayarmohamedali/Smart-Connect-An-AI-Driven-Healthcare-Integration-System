<?php
// Variables: $patient, $records, $patient_id, $insurance_id
function e($v): string { return htmlspecialchars((string)($v??''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Patient Medical Records</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_URL ?>/insurance/dashboard#patients" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
    <div>
      <span class="badge badge-info p-2 mr-2"><i class="fas fa-user-injured mr-1"></i> <?= e($patient['full_name']) ?></span>
      <span class="badge badge-secondary p-2"><i class="fas fa-notes-medical mr-1"></i> Records: <?= count($records) ?></span>
    </div>
  </div>

  <div class="card shadow">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success">Medical Records</h6></div>
    <div class="card-body">
      <?php if (!$records): ?>
        <p class="text-muted">No medical records found.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
              <tr><th>#</th><th>Created</th><th>Check-in</th><th>Diagnosis</th><th>Category</th><th>LOS</th><th>Risk Flags</th></tr>
            </thead>
            <tbody>
              <?php foreach ($records as $r): ?>
              <tr>
                <td>#<?= (int)$r['record_id'] ?></td>
                <td><?= e($r['created_at']) ?></td>
                <td><?= e($r['checkin_date']) ?></td>
                <td><?= e($r['diagnosis']) ?></td>
                <td><?= e($r['disease_category']) ?></td>
                <td><?= e($r['length_of_stay']) ?> d</td>
                <td>
                  <?php if (!empty($r['has_diabetes'])): ?><span class="badge badge-warning">DM</span><?php endif; ?>
                  <?php if (!empty($r['has_hypertension'])): ?><span class="badge badge-danger">HTN</span><?php endif; ?>
                  <?php if (!empty($r['has_kidney_disease'])): ?><span class="badge badge-info">KD</span><?php endif; ?>
                  <?php if (!empty($r['has_heart_disease'])): ?><span class="badge badge-primary">HD</span><?php endif; ?>
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
<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Variables from PatientController::dashboard():
// $patientObj (Patient), $dob, $age, $insuranceName
// $policyData (array), $records (array), $latest, $kpi_records
// $claimStatus, $claimMsg
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rec_get($rec, string $key, $default = null) {
    if (is_array($rec))  return $rec[$key]  ?? $default;
    if (is_object($rec)) return $rec->$key  ?? $default;
    return $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Patient Dashboard – Smart-Connect</title>
  <link href="<?= BASE_URL ?>/assets/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    .anchor-offset { scroll-margin-top: 90px; }
    .badge-soft { border: 1px solid rgba(0,0,0,.08); }
    .prediction-card { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border-radius:10px; padding:20px; margin-bottom:20px; }
    .prediction-header { font-size:1.3rem; font-weight:bold; margin-bottom:10px; }
    .confidence-badge  { background:rgba(255,255,255,0.2); padding:8px 15px; border-radius:20px; font-weight:bold; display:inline-block; margin:5px 0; }
    .risk-level-high   { background:#dc3545; color:#fff; padding:5px 12px; border-radius:15px; font-weight:bold; font-size:.85rem; display:inline-block; }
    .timeline-badge    { background:rgba(255,255,255,0.15); padding:5px 10px; border-radius:12px; font-size:.9rem; display:inline-block; }
    .risk-predictions-list { background:rgba(255,255,255,0.1); border-radius:8px; padding:15px; margin-top:15px; }
    .risk-item         { padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.1); }
    .risk-item:last-child { border-bottom:none; }
    .risk-percentage   { font-weight:bold; font-size:1.1rem; color:#ffd700; }
    .risk-factors-section { background:#fff3cd; color:#856404; border-left:4px solid #ffc107; padding:15px; border-radius:5px; margin:15px 0; }
    .recommendations-section { background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 4px rgba(0,0,0,.1); }
    .recommendation-item { padding:6px 0; font-size:.95rem; }
    /* Claim */
    .claim-hero { background:linear-gradient(135deg,#06b6d4 0%,#6366f1 55%,#a855f7 100%); color:#fff; border-radius:16px; padding:18px; position:relative; overflow:hidden; box-shadow:0 14px 28px rgba(99,102,241,.18); }
    .claim-hero:before { content:""; position:absolute; right:-60px; top:-60px; width:220px; height:220px; background:radial-gradient(circle,rgba(255,255,255,.28),transparent 60%); }
    .hero-badge { background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.22); padding:6px 12px; border-radius:999px; font-weight:800; display:inline-flex; align-items:center; gap:8px; }
    .claim-card { border-radius:16px; border:0; overflow:hidden; box-shadow:0 12px 30px rgba(0,0,0,.08); }
    .claim-section-title { display:flex; align-items:center; gap:10px; font-weight:900; margin:12px 0 10px; }
    .icon-pill { width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; box-shadow:0 10px 18px rgba(0,0,0,.12); flex:0 0 auto; }
    .bg-grad-blue   { background:linear-gradient(135deg,#3b82f6,#6366f1); }
    .bg-grad-orange { background:linear-gradient(135deg,#fb923c,#f59e0b); }
    .soft-block { border-radius:14px; border:1px solid rgba(0,0,0,.06); background:linear-gradient(180deg,#fff 0%,#f8fafc 100%); padding:14px; }
    .badge-total { background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; border:none; }
    .btn-submit { background:linear-gradient(135deg,#22c55e,#16a34a); border:none; color:#fff; font-weight:800; border-radius:12px; padding:10px 16px; }
    .btn-submit:hover { opacity:.95; color:#fff; }
    .btn-ghost { border-radius:12px; padding:10px 16px; font-weight:800; }
    .rainbow-line { height:4px; border-radius:999px; background:linear-gradient(90deg,#22c55e,#06b6d4,#3b82f6,#a855f7,#ec4899,#f59e0b); opacity:.9; }
    .form-label-custom { font-weight:800; margin-bottom:6px; }
    .chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; border:1px solid rgba(255,255,255,.25); background:rgba(255,255,255,.12); font-weight:800; font-size:.85rem; margin-right:8px; margin-top:8px; }
    .input-group-text { font-weight:900; }
  </style>
</head>

<body id="page-top" class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/patient/dashboard">
      <i class="fas fa-user-injured mr-2"></i><strong>Patient Portal</strong>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#patientNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="patientNav">
      <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
        <li class="nav-item active"><a class="nav-link" href="#profile"><i class="fas fa-user mr-1"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="#medical"><i class="fas fa-notes-medical mr-1"></i> Medical</a></li>
        <li class="nav-item"><a class="nav-link" href="#insurance"><i class="fas fa-shield-alt mr-1"></i> Insurance</a></li>
        <li class="nav-item"><a class="nav-link" href="#ai"><i class="fas fa-robot mr-1"></i> AI Insights</a></li>
        <li class="nav-item"><a class="nav-link" href="#claims"><i class="fas fa-file-invoice-dollar mr-1"></i> Claims</a></li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
             data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span><?= e($patientObj->getFullName()) ?></span>
          </a>
          <div class="dropdown-menu dropdown-menu-right shadow">
            <a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout">
              <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i> Logout
            </a>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid mt-4">

  <?php if ($claimStatus === 'success'): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
      <i class="fas fa-check-circle mr-2"></i>
      <strong>Claim submitted successfully!</strong> Your claim is now <span class="badge badge-warning">Pending</span> review.
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php elseif ($claimStatus === 'error'): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
      <i class="fas fa-exclamation-circle mr-2"></i>
      <strong>Claim submission failed.</strong> <?= e($claimMsg) ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <!-- PROFILE -->
  <div id="profile" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">👤 Profile</div>
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6"><b>Full Name:</b> <?= e($patientObj->getFullName()) ?></div>
        <div class="col-sm-6"><b>National ID:</b> <?= e($patientObj->getNationalId()) ?></div>
        <div class="col-sm-6 mt-2"><b>Date of Birth:</b> <?= $dob ? e($dob) : '-' ?></div>
        <div class="col-sm-6 mt-2"><b>Age:</b> <?= $age !== null ? (int)$age : '-' ?></div>
        <div class="col-sm-6 mt-2"><b>Gender:</b> <?= e($patientObj->getGender() ?: '-') ?></div>
        <div class="col-sm-6 mt-2"><b>Phone:</b> <?= e($patientObj->getPhone() ?: '-') ?></div>
        <div class="col-sm-12 mt-2"><b>Address:</b> <?= e($patientObj->getAddress() ?: '-') ?></div>
        <div class="col-sm-12 mt-3">
          <span class="badge badge-info badge-soft p-2">
            <i class="fas fa-id-badge mr-1"></i> Patient ID: <?= (int)$patientObj->getPatientId() ?>
          </span>
          <?php if ($patientObj->getInsuranceId()): ?>
            <span class="badge badge-success badge-soft p-2 ml-2">
              <i class="fas fa-shield-alt mr-1"></i> Insurance: <?= e($insuranceName) ?>
            </span>
          <?php else: ?>
            <span class="badge badge-warning badge-soft p-2 ml-2">
              <i class="fas fa-exclamation-triangle mr-1"></i> No insurance assigned
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- MEDICAL SUMMARY -->
  <div id="medical" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">🩺 Medical</div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4 mb-2"><b>Total Records:</b> <?= (int)$kpi_records ?></div>
        <div class="col-md-8 mb-2">
          <b>Latest Diagnosis:</b> <?= e(rec_get($latest, 'diagnosis', '-')) ?>
          <?php if ($latest): ?>
            <small class="text-muted ml-2">(<?= e(rec_get($latest, 'created_at', '')) ?>)</small>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- MEDICAL RECORDS TABLE -->
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">📁 Medical Records (Latest 20)</div>
    <div class="card-body">
      <?php if (!$records): ?>
        <div class="text-muted">No medical records found.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
              <tr><th>#</th><th>Date Created</th><th>Check-in</th><th>Check-out</th><th>Diagnosis</th><th>View</th></tr>
            </thead>
            <tbody>
              <?php $i = 0; foreach ($records as $r): $i++; ?>
                <tr>
                  <td><?= $i ?></td>
                  <td><?= e(rec_get($r, 'created_at',    '-')) ?></td>
                  <td><?= e(rec_get($r, 'checkin_date',  '-')) ?></td>
                  <td><?= e(rec_get($r, 'checkout_date', '-')) ?></td>
                  <td><?= e(rec_get($r, 'diagnosis',     '-')) ?></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary"
                       href="<?= BASE_URL ?>/patient/viewRecord?record_id=<?= (int)rec_get($r, 'record_id', 0) ?>">
                      <i class="fas fa-eye"></i> View
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

  <!-- INSURANCE -->
  <div id="insurance" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">🛡 Insurance Information</div>
    <div class="card-body">
      <div class="row">
        <div class="col-sm-6"><b>Provider:</b> <?= e($insuranceName ?: '-') ?></div>
        <div class="col-sm-6"><b>Status:</b>
          <?php
            $st = $policyData['status'] ?? '';
            if     ($st === 'active')    echo '<span class="badge badge-success">active</span>';
            elseif ($st === 'suspended') echo '<span class="badge badge-warning">suspended</span>';
            elseif ($st === 'expired')   echo '<span class="badge badge-secondary">expired</span>';
            else                         echo '<span class="badge badge-light">no policy</span>';
          ?>
        </div>
        <div class="col-sm-6 mt-2"><b>Plan:</b>      <?= e($policyData['plan_name']     ?? '-') ?></div>
        <div class="col-sm-6 mt-2"><b>Policy #:</b>  <?= e($policyData['policy_number'] ?? '-') ?></div>
        <div class="col-sm-6 mt-2"><b>Start Date:</b><?= e($policyData['start_date']    ?? '-') ?></div>
        <div class="col-sm-6 mt-2"><b>End Date:</b>  <?= e($policyData['end_date']      ?? '-') ?></div>
      </div>
    </div>
  </div>

  <!-- AI INSIGHTS -->
  <div id="ai" class="anchor-offset"></div>
  <div class="card shadow mb-4">
    <div class="card-header font-weight-bold">🤖 AI Health Insights</div>
    <div class="card-body p-0">
      <div class="prediction-card">
        <div class="prediction-header"><i class="fas fa-brain mr-2"></i>Primary Health Prediction</div>
        <div class="mt-3">
          <h4 class="mb-2">Diabetes Mellitus</h4>
          <div class="mb-2">
            <span class="confidence-badge"><i class="fas fa-chart-line mr-1"></i>Confidence: 74.8%</span>
            <span class="risk-level-high ml-2"><i class="fas fa-exclamation-triangle mr-1"></i>RISK LEVEL: HIGH</span>
            <span class="timeline-badge ml-2"><i class="fas fa-clock mr-1"></i>Timeline: 1–3 months</span>
          </div>
        </div>
        <div class="risk-predictions-list">
          <h6 class="mb-3"><i class="fas fa-list-ol mr-2"></i>Top 3 Risk Predictions</h6>
          <div class="risk-item"><span class="risk-percentage">74.8%</span><span class="ml-2">— Diabetes Mellitus</span></div>
          <div class="risk-item"><span class="risk-percentage">11.6%</span><span class="ml-2">— Hypertension</span></div>
          <div class="risk-item"><span class="risk-percentage">5.1%</span><span class="ml-2">— Heart Disease</span></div>
        </div>
      </div>
      <div class="p-4">
        <div class="risk-factors-section">
          <h5><i class="fas fa-exclamation-circle mr-2"></i>Active Risk Factors</h5>
          <div><i class="fas fa-arrow-circle-right mr-2"></i><strong>ELEVATED BLOOD GLUCOSE RISK</strong></div>
          <div><i class="fas fa-arrow-circle-right mr-2"></i>Check fasting blood sugar &amp; HbA1c within 3 days</div>
          <div><i class="fas fa-arrow-circle-right mr-2"></i>Family history of diabetes</div>
          <div><i class="fas fa-arrow-circle-right mr-2"></i>Sedentary lifestyle / overweight</div>
        </div>
        <div class="recommendations-section mt-4 p-3 rounded shadow-sm bg-light">
          <h5 class="mb-3"><i class="fas fa-clipboard-check mr-2 text-success"></i>Recommended Preventive Measures 💡</h5>
          <div class="mb-4">
            <h6 class="mb-2 text-primary"><i class="fas fa-calendar-alt mr-2"></i> Short-term (Next 3 months) ⏱️</h6>
            <div class="recommendation-item mb-2">🩸 Monitor fasting blood glucose weekly</div>
            <div class="recommendation-item mb-2">🍭 Reduce sugar &amp; refined carbs intake</div>
            <div class="recommendation-item mb-2">🚶‍♂️ Walk 30 minutes daily</div>
            <div class="recommendation-item mb-2">💧 Maintain hydration</div>
          </div>
          <div>
            <h6 class="mb-2 text-success"><i class="fas fa-calendar-check mr-2"></i> Long-term (6–12 months) 📅</h6>
            <div class="recommendation-item mb-2">📊 Maintain HbA1c &lt; 7%</div>
            <div class="recommendation-item mb-2">🥗 Follow diabetic-friendly diet plan</div>
            <div class="recommendation-item mb-2">⚖️ Maintain healthy BMI (18.5–24.9)</div>
            <div class="recommendation-item mb-2">👨‍⚕️ Regular follow-up with endocrinologist</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CLAIM SUBMISSION -->
  <div id="claims" class="anchor-offset"></div>
  <div class="card claim-card shadow mb-4">
    <div class="card-body p-0">
      <div class="claim-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
          <div>
            <div class="hero-badge"><i class="fas fa-file-invoice-dollar"></i> Claim Request</div>
            <h4 class="mt-3 mb-1 font-weight-bold">Submit a new insurance claim</h4>
            <div class="text-white-50">Fill the details below to request reimbursement for your medical service.</div>
            <div class="d-flex flex-wrap">
              <span class="chip"><i class="fas fa-shield-alt"></i> Provider: <?= e($insuranceName ?: '-') ?></span>
              <span class="chip"><i class="fas fa-user"></i> Patient: <?= e($patientObj->getFullName()) ?></span>
            </div>
          </div>
          <div class="mt-3 mt-md-0">
            <span class="badge badge-soft badge-total p-2">
              <i class="fas fa-layer-group mr-1"></i> Total Records: <?= (int)$kpi_records ?>
            </span>
          </div>
        </div>
        <div class="rainbow-line mt-3"></div>
      </div>

      <div class="p-4">
        <div class="soft-block mb-3">
          <div class="claim-section-title mb-1">
            <span class="icon-pill bg-grad-blue"><i class="fas fa-plus"></i></span>
            <span>Claim Details</span>
          </div>
          <div class="text-muted" style="font-size:.92rem;">Make sure the service type and amount match your receipt.</div>
        </div>

        <form id="claimForm" action="<?= BASE_URL ?>/patient/submitClaim" method="POST">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label-custom"><i class="fas fa-stethoscope text-primary mr-1"></i> Type of Service <span class="text-danger">*</span></label>
              <select class="form-control" name="service_type" required>
                <option value="">-- Select Service Type --</option>
                <option value="Checkup">🩺 Checkup</option>
                <option value="Operations">⚕️ Operations</option>
                <option value="Dental">🦷 Dental</option>
                <option value="Maternity">👶 Maternity</option>
                <option value="Optical">👓 Optical</option>
                <option value="Surgery">🏥 Surgery</option>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label-custom"><i class="fas fa-money-bill-wave text-success mr-1"></i> Claim Amount <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" class="form-control" name="claim_amount" placeholder="0.00" min="0.01" step="0.01" required>
                <div class="input-group-append"><span class="input-group-text">EGP</span></div>
              </div>
            </div>

            <div class="col-md-12 mb-3">
              <div class="soft-block">
                <div class="claim-section-title">
                  <span class="icon-pill bg-grad-orange"><i class="fas fa-comment-dots"></i></span>
                  <span>Additional Notes (Optional)</span>
                </div>
                <textarea class="form-control" name="description" rows="3"
                          placeholder="Doctor name, hospital/clinic, symptoms..."></textarea>
              </div>
            </div>

            <div class="col-md-12">
              <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="text-muted mb-2 mb-md-0" style="font-size:.92rem;">
                  <i class="fas fa-lock mr-1"></i> Your request will be reviewed by your insurance provider.
                </div>
                <div class="d-flex flex-wrap">
                  <button type="submit" class="btn btn-submit mr-2">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Claim
                  </button>
                  <button type="reset" class="btn btn-outline-secondary btn-ghost">
                    <i class="fas fa-redo mr-1"></i> Reset
                  </button>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

</div><!-- /container-fluid -->

<footer class="sticky-footer bg-white mt-4">
  <div class="my-auto text-center py-3"><span>Smart-Connect &copy; 2026</span></div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/jquery.easing.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sb-admin-2.min.js"></script>
</body>
</html>
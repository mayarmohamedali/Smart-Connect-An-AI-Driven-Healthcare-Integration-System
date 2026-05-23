<?php
/**
 * Insurance Dashboard View  — FIXED chart section
 *
 * Changes vs original:
 *   1. History is now [{year, yoy_pct}] — % growth, not EGP sums.
 *      PHP extracts $hist_years and $hist_growth from this list format.
 *   2. The Chart.js dataset now plots YoY % growth values on a % Y-axis.
 *   3. A forecast bar for $ml_year is appended using $ml_blended so the
 *      predicted point is visible alongside the historical series.
 *   4. ML_Change_Pct and Trend_Change_Pct are read from top-level keys
 *      (also present in the live-DB response now).
 *
 * Variables provided by InsuranceController::dashboard()
 *
 * $insurance_name, $insurance_id
 * $kpi_patients, $kpi_policies_active, $kpi_cases_month, $kpi_pending_reviews
 * $success_msg, $error_msg, $q, $patients
 * ML: $api_alive, $ml_ok, $ml_forecast, $ml_source, $ml_blended, $ml_lower,
 *     $ml_upper, $ml_uncertainty, $ml_action, $ml_year, $fc_color, $db_records_count
 * Risk: $fraud_patients, $renewal_patients
 * Charts: $fraud_names, $fraud_admissions, $fraud_claimed, $fraud_pending
 *         $ren_names, $ren_days, $ren_risk
 *         $raise_c, $review_c, $std_c, $high_risk_count, $total_renewal_exp
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Insurance Dashboard</title>
  <link href="<?= BASE_URL ?>/assets/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    .anchor-offset { scroll-margin-top: 90px }
    .dash-title    { font-weight: 800; letter-spacing: .2px }
    .kpi-card      { border-radius: 12px }
    .kpi-label     { font-size:.72rem; font-weight:800; letter-spacing:.6px; text-transform:uppercase }
    .kpi-value     { font-size:1.25rem; font-weight:800 }
    .chart-wrap    { background:#fff; border-radius:10px; padding:14px 12px 10px; box-shadow:0 2px 10px rgba(0,0,0,.07) }
    .leg-dot       { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:4px }
    .accent-fraud  { border-top:4px solid #e74c3c!important }
    .accent-renew  { border-top:4px solid #8e44ad!important }
    .table-sm td, .table-sm th { padding:.42rem .6rem }
    .ml-prediction-card  { border-radius:14px; box-shadow:0 .15rem 1.75rem 0 rgba(58,59,69,.12) }
    .ml-badge-live       { background:#1cc88a; color:#fff; font-size:.7rem; padding:2px 8px; border-radius:10px; font-weight:700 }
    .ml-badge-saved      { background:#858796; color:#fff; font-size:.7rem; padding:2px 8px; border-radius:10px; font-weight:700 }
    .ml-badge-offline    { background:#e74a3b; color:#fff; font-size:.7rem; padding:2px 8px; border-radius:10px; font-weight:700 }
    .forecast-big-num    { font-size:2.8rem; font-weight:800; line-height:1.1 }
    .range-bar-wrap      { background:#f0f0f0; border-radius:8px; height:10px; position:relative; margin:8px 0 }
    .range-bar-fill      { height:10px; border-radius:8px; position:absolute }
  </style>
</head>
<body id="page-top" class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/?url=insurance/dashboard">
      <i class="fas fa-shield-alt mr-2"></i>
      <strong><?= Validator::sanitizeInput($insurance_name) ?> Dashboard</strong>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topNavbar">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item"><a class="nav-link" href="#dashboard"><i class="fas fa-tachometer-alt mr-1"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="#mlPrediction"><i class="fas fa-brain mr-1"></i> ML Prediction</a></li>
        <li class="nav-item"><a class="nav-link" href="#patients"><i class="fas fa-users mr-1"></i> Patients</a></li>
        <li class="nav-item"><a class="nav-link" href="#addPatient"><i class="fas fa-user-plus mr-1"></i> Add Patient</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/?url=insurance/policy"><i class="fas fa-file-alt mr-1"></i> Policy</a></li>
        <li class="nav-item"><a class="nav-link" href="#riskAnalysis"><i class="fas fa-exclamation-triangle mr-1"></i> Risk Analysis</a></li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-toggle="dropdown">
            <span class="mr-2 d-none d-lg-inline text-white small">
              <?= Validator::sanitizeInput($auth->getSessionData('staff_name') ?? 'Insurance Staff') ?>
            </span>
            <i class="fas fa-user-circle fa-2x text-white"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-right shadow">
            <a class="dropdown-item" href="<?= BASE_URL ?>/?url=auth/logout">
              <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i> Logout
            </a>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid mt-4">

  <!-- KPIs -->
  <div id="dashboard" class="anchor-offset mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <h5 class="dash-title text-gray-800 mb-0"><i class="fas fa-chart-pie mr-2 text-success"></i> Dashboard Overview</h5>
      <span class="badge badge-light"><i class="fas fa-calendar-alt mr-1"></i> <?= date('F Y') ?></span>
    </div>
    <div class="row">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success shadow-sm kpi-card"><div class="card-body">
          <div class="kpi-label text-success">Total Patients</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_patients ?></div>
        </div></div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary shadow-sm kpi-card"><div class="card-body">
          <div class="kpi-label text-primary">Policies Active</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_policies_active ?></div>
        </div></div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info shadow-sm kpi-card"><div class="card-body">
          <div class="kpi-label text-info">Claims This Month</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_cases_month ?></div>
        </div></div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow-sm kpi-card"><div class="card-body">
          <div class="kpi-label text-warning">Pending Reviews</div>
          <div class="kpi-value text-gray-800"><?= (int)$kpi_pending_reviews ?></div>
        </div></div>
      </div>
    </div>
  </div>

  <?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $success_msg ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= Validator::sanitizeInput($error_msg) ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
  <?php endif; ?>

  <!-- ═══ ML FORECAST SECTION ═══ -->
  <div id="mlPrediction" class="anchor-offset mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
      <h4 class="font-weight-bold text-gray-800 mb-0">
        <i class="fas fa-brain mr-2 text-success"></i>
        <?= Validator::sanitizeInput($insurance_name) ?> — Coverage Forecast <?= $ml_year ?>
      </h4>
      <div class="d-flex align-items-center" style="gap:8px;">
        <?php if (!$api_alive): ?>
          <span class="ml-badge-offline"><i class="fas fa-times-circle mr-1"></i>ML API Offline</span>
        <?php elseif (in_array($ml_source, ['live_db','live_db_adjusted'])): ?>
          <span class="ml-badge-live">
            <i class="fas fa-circle mr-1"></i>
            LIVE<?= $ml_source === 'live_db_adjusted' ? ' (adjusted)' : '' ?>
            — <?= $db_records_count ?> DB records
          </span>
        <?php else: ?>
          <span class="ml-badge-saved"><i class="fas fa-database mr-1"></i>Trained Model</span>
        <?php endif; ?>
        <span class="badge badge-<?= $db_records_count >= 10 ? 'success' : 'secondary' ?> p-2">
          <i class="fas fa-file-medical mr-1"></i><?= $db_records_count ?> claims in DB
          <?= $db_records_count < 10 ? '(need 10+ for live)' : '' ?>
        </span>
      </div>
    </div>

    <?php if (!$api_alive): ?>
      <div class="alert alert-warning">
        <i class="fas fa-plug mr-2"></i>
        <strong>ML API not running.</strong>
        Open a terminal and run: <code>py app.py</code> — then refresh.
      </div>

    <?php elseif ($ml_ok): ?>
      <div class="row">
        <!-- Main forecast number -->
        <div class="col-xl-4 col-lg-5 mb-4">
          <div class="card ml-prediction-card border-left-<?= $fc_color ?> h-100">
            <div class="card-body d-flex flex-column justify-content-center text-center py-4">
              <p class="text-muted mb-1" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;">
                Predicted Coverage Increase
              </p>
              <div class="forecast-big-num text-<?= $fc_color ?>">
                +<?= number_format($ml_blended, 1) ?>%
              </div>
              <p class="text-muted mt-1 mb-3" style="font-size:.82rem;">for <?= $ml_year ?></p>

              <p class="mb-1" style="font-size:.75rem;color:#888;">
                Forecast range: <strong>+<?= number_format($ml_lower, 1) ?>%</strong>
                to <strong>+<?= number_format($ml_upper, 1) ?>%</strong>
              </p>
              <?php
                $range_total = max(0.1, $ml_upper - $ml_lower);
                $fill_pct    = min(100, max(5, (($ml_blended - $ml_lower) / $range_total) * 100));
              ?>
              <div class="range-bar-wrap">
                <div class="range-bar-fill bg-<?= $fc_color ?>" style="width:<?= round($fill_pct) ?>%;left:0;"></div>
              </div>

             <!-- <p class="text-muted" style="font-size:.72rem;">Uncertainty ±<?= number_format($ml_uncertainty, 1) ?> pp</p> -->

              <?php if ($ml_confidence > 0): ?>
              <?php $ml_conf_color = $ml_confidence >= 90 ? 'success' : ($ml_confidence >= 75 ? 'info' : ($ml_confidence >= 55 ? 'warning' : 'danger')); ?>
              <hr class="my-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;">
                  <i class="fas fa-chart-bar mr-1"></i>Model Confidence
                </small>
                <small class="font-weight-bold text-<?= $ml_conf_color ?>">
                  <?= number_format($ml_confidence, 1) ?>% — <?= Validator::sanitizeInput($ml_conf_label) ?>
                </small>
              </div>
              <div class="progress" style="height:8px;border-radius:6px;">
                <div class="progress-bar bg-<?= $ml_conf_color ?>"
                     role="progressbar"
                     style="width:<?= min(100,$ml_confidence) ?>%;border-radius:6px;">
                </div>
              </div>
              <small class="text-muted" style="font-size:.7rem;">
                Derived from ML vs trend agreement — lower uncertainty = higher confidence
              </small>
              <?php endif; ?>

            </div>
          </div>
        </div>

        <!-- History chart -->
        <div class="col-xl-8 col-lg-7 mb-4">
          <div class="card ml-prediction-card h-100">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
              <h6 class="m-0 font-weight-bold text-success">
                <i class="fas fa-chart-line mr-2"></i>YoY Coverage Growth History &amp; Forecast
              </h6>
              <small class="text-muted">
                <?= $ml_source === 'live_db'
                    ? "Source: {$db_records_count} real DB claims &middot; 60% ML + 40% trend"
                    : 'Source: Trained model (60% ML + 40% weighted trend)' ?>
              </small>
            </div>
            <div class="card-body">
              <!-- Three-box breakdown -->
              <div class="row mb-3">
                <div class="col-md-4 text-center">
                  <div style="background:#f8f9fc;border-radius:8px;padding:12px 8px;">
                    <div style="font-size:1.4rem;font-weight:800;color:#4e73df;">
                      <?php
                        // FIX: read ML_Change_Pct from the top-level key
                        // (was previously: $ml_forecast['ML_Change_Pct'] — still works,
                        //  but now also populated on live-DB responses)
                        echo isset($ml_forecast['ML_Change_Pct'])
                             ? number_format((float)$ml_forecast['ML_Change_Pct'], 1) . '%'
                             : number_format($ml_blended, 1) . '%';
                      ?>
                    </div>
                    <div style="font-size:.72rem;color:#888;text-transform:uppercase;">ML Prediction</div>
                  </div>
                </div>
                <div class="col-md-4 text-center">
                  <div style="background:#f8f9fc;border-radius:8px;padding:12px 8px;">
                    <div style="font-size:1.4rem;font-weight:800;color:#1cc88a;">
                      <?php
                        // FIX: read Trend_Change_Pct from the top-level key
                        echo isset($ml_forecast['Trend_Change_Pct'])
                             ? number_format((float)$ml_forecast['Trend_Change_Pct'], 1) . '%'
                             : '—';
                      ?>
                    </div>
                    <div style="font-size:.72rem;color:#888;text-transform:uppercase;">Historical Trend</div>
                  </div>
                </div>
                <div class="col-md-4 text-center">
                  <div style="background:#f8f9fc;border-radius:8px;padding:12px 8px;">
                    <div class="text-<?= $fc_color ?>" style="font-size:1.4rem;font-weight:800;">
                      +<?= number_format($ml_blended, 1) ?>%
                    </div>
                    <div style="font-size:.72rem;color:#888;text-transform:uppercase;">Blended Forecast</div>
                  </div>
                </div>
              </div>
              <div style="position:relative;height:200px;">
                <canvas id="coverageHistoryChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Source note -->
      <?php if ($ml_source === 'live_db'): ?>
        <div class="alert alert-success py-2">
          <i class="fas fa-check-circle mr-2"></i>
          <strong>Live prediction:</strong> Based on <?= $db_records_count ?> real claims pulled from your database for <?= Validator::sanitizeInput($insurance_name) ?>.
        </div>
      <?php elseif ($db_records_count > 0 && $db_records_count < 10): ?>
        <div class="alert alert-info py-2">
          <i class="fas fa-info-circle mr-2"></i>
          <strong>Nearly live:</strong> Found <?= $db_records_count ?> DB records — need 10+ for live forecast.
          <div class="progress mt-2" style="height:6px;border-radius:4px;">
            <div class="progress-bar bg-info" style="width:<?= min(100, ($db_records_count/10)*100) ?>%"></div>
          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-secondary py-2">
          <i class="fas fa-database mr-2"></i>
          <strong>Trained model baseline:</strong> No claims found yet. Forecast is based on the trained model's learned patterns.
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Forecast unavailable.</strong>
        <?= Validator::sanitizeInput($ml_forecast['error'] ?? $ml_forecast['message'] ?? 'Unknown error from ML API.') ?>
      </div>
    <?php endif; ?>
  </div>
  <!-- END ML FORECAST -->

  <!-- ADD PATIENT + PATIENTS TABLE -->
  <div class="row">
    <div class="col-lg-5 mb-4" id="addPatient">
      <div class="card shadow h-100">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
          <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-user-plus mr-2"></i> Add Patient</h6>
          <span class="badge badge-light">ID: <?= (int)$insurance_id ?></span>
        </div>
        <div class="card-body">
          <form method="POST" action="<?= BASE_URL ?>/?url=insurance/dashboard#addPatient" id="addPatientForm" novalidate>
            <input type="hidden" name="action" value="add_patient">

            <!-- Full Name -->
            <div class="form-group">
              <label for="inp_full_name">Full Name <span class="text-danger">*</span></label>
              <input
                class="form-control" id="inp_full_name" name="full_name"
                placeholder="e.g. Ahmed Hassan"
                autocomplete="off"
                maxlength="100">
              <div class="invalid-feedback" id="err_full_name"></div>
            </div>

            <!-- National ID -->
            <div class="form-group">
              <label for="inp_national_id">National ID <span class="text-danger">*</span></label>
              <input
                class="form-control" id="inp_national_id" name="national_id"
                placeholder="14 digits — e.g. 29901011234567"
                maxlength="14" inputmode="numeric" autocomplete="off">
              <div class="invalid-feedback" id="err_national_id"></div>
              <span id="hint_national_id" style="display:none;" aria-hidden="true"></span>
            </div>

            <!-- Phone -->
            <div class="form-group">
              <label for="inp_phone">Phone <span class="text-danger">*</span></label>
              <input
                class="form-control" id="inp_phone" name="phone"
                placeholder="010XXXXXXXX"
                maxlength="11" inputmode="numeric" autocomplete="off">
              <div class="invalid-feedback" id="err_phone"></div>
            </div>

            <!-- Gender -->
            <div class="form-group">
              <label for="inp_gender">Gender <span class="text-danger">*</span></label>
              <select class="form-control" id="inp_gender" name="gender">
                <option value="">— Select gender —</option>
                <option value="M">Male</option>
                <option value="F">Female</option>
              </select>
              <div class="invalid-feedback" id="err_gender"></div>
            </div>

            <!-- Assigned Insurance (read-only) -->
            <div class="form-group">
              <label>Assigned Insurance</label>
              <input class="form-control bg-light" value="<?= Validator::sanitizeInput($insurance_name) ?>" readonly tabindex="-1">
            </div>

            <!-- Address -->
            <div class="form-group">
              <label for="inp_address">Address <span class="text-danger">*</span></label>
              <input
                class="form-control" id="inp_address" name="address"
                placeholder="e.g. 12 Tahrir St, Cairo"
                maxlength="255" autocomplete="off">
              <div class="invalid-feedback" id="err_address"></div>
            </div>

            <button class="btn btn-success btn-block" type="submit" id="btnSavePatient">
              <i class="fas fa-save mr-1"></i> Save Patient
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7 mb-4 anchor-offset" id="patients">
      <div class="card shadow h-100">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
          <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-users mr-2"></i> Patients</h6>
          <form class="d-flex mt-2 mt-md-0" method="GET" action="<?= BASE_URL ?>/?url=insurance/dashboard#patients" style="gap:8px;flex:1;max-width:500px;">
            <input type="hidden" name="url" value="insurance/dashboard">
            <input class="form-control" name="q" value="<?= Validator::sanitizeInput($q) ?>" placeholder="Search name, national ID, phone">
            <button class="btn btn-success" type="submit"><i class="fas fa-search"></i></button>
          </form>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
              <thead class="thead-light">
                <tr><th>ID</th><th>Name</th><th>National ID</th><th>Phone</th><th>Gender</th><th>Plan</th><th>Status</th><th>Policy #</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php if (!$patients): ?>
                  <tr><td colspan="9" class="text-center text-muted">No patients found.</td></tr>
                <?php else: foreach ($patients as $p): ?>
                  <tr>
                    <td><?= (int)$p['patient_id'] ?></td>
                    <td><?= Validator::sanitizeInput($p['full_name']) ?></td>
                    <td><?= Validator::sanitizeInput($p['national_id']) ?></td>
                    <td><?= Validator::sanitizeInput($p['phone']) ?></td>
                    <td><?= Validator::sanitizeInput($p['gender']) ?></td>
                    <td><?= Validator::sanitizeInput($p['plan_name'] ?? '—') ?></td>
                    <td>
                      <?php $st = $p['status'] ?? '';
                        if ($st === 'active')    echo '<span class="badge badge-success">active</span>';
                        elseif ($st === 'suspended') echo '<span class="badge badge-warning">suspended</span>';
                        elseif ($st === 'expired')   echo '<span class="badge badge-secondary">expired</span>';
                        else echo '<span class="badge badge-light">no policy</span>';
                      ?>
                    </td>
                    <td><?= Validator::sanitizeInput($p['policy_number'] ?? '—') ?></td>
                    <td>
                      <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/?url=insurance/addPolicy&patient_id=<?= (int)$p['patient_id'] ?>">
                        <i class="fas fa-plus"></i> Add Policy
                      </a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SMART RISK ANALYSIS -->
  <div id="riskAnalysis" class="anchor-offset mb-3">
    <div class="d-flex align-items-center mb-3" style="gap:12px;">
      <div style="width:5px;height:42px;background:linear-gradient(180deg,#e74c3c,#8e44ad);border-radius:3px;"></div>
      <div>
        <h5 class="mb-0 font-weight-bold text-gray-800"><i class="fas fa-brain mr-2 text-danger"></i>Smart Risk Analysis</h5>
        <small class="text-muted">Fraud Detection &nbsp;·&nbsp; Policy Renewal Pricing</small>
      </div>
    </div>

    <!-- FRAUD DETECTION -->
    <div class="card shadow mb-4 accent-fraud anchor-offset" id="fraudDetection">
      <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap" style="background:linear-gradient(135deg,#fff5f5,#fff);">
        <div class="d-flex align-items-center" style="gap:10px;">
          <div style="width:38px;height:38px;background:#e74c3c;border-radius:9px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-exclamation-triangle text-white"></i>
          </div>
          <div>
            <h6 class="m-0 font-weight-bold text-danger">🚨 Fraud Detection Flags</h6>
            <small class="text-muted">Patients with repeated admissions (≥3)</small>
          </div>
        </div>
        <span class="badge badge-danger badge-pill px-3 py-2"><?= count($fraud_patients) ?> Flagged</span>
      </div>
      <?php if (!$fraud_patients): ?>
        <div class="card-body text-center text-muted py-4">
          <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>No fraud flags detected.
        </div>
      <?php else: ?>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6 mb-3">
            <div class="chart-wrap">
              <div class="mb-2"><span class="leg-dot" style="background:#e74c3c;"></span><strong style="font-size:.8rem;color:#555;">Admissions Count per Patient</strong></div>
              <div style="position:relative;height:210px;"><canvas id="chartFraudAdmissions"></canvas></div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="chart-wrap">
              <div class="mb-2">
                <span class="leg-dot" style="background:#c0392b;"></span><small style="color:#555;">Claimed (EGP)</small>&nbsp;&nbsp;
                <span class="leg-dot" style="background:#f39c12;"></span><small style="color:#555;">Pending Claims</small>
              </div>
              <div style="position:relative;height:210px;"><canvas id="chartFraudClaimed"></canvas></div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0" style="font-size:.87rem;">
            <thead style="background:#fff0f0;">
              <tr>
                <th class="pl-3">Patient</th><th>National ID</th><th>Policy #</th>
                <th class="text-center">Admissions</th><th class="text-center">Stay</th>
                <th class="text-center">Claims</th><th class="text-right">Claimed (EGP)</th>
                <th class="text-center">Pending</th><th class="text-center">Diagnosis</th><th class="text-center">Flag</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($fraud_patients as $fp):
                $adm  = (int)($fp['admission_count'] ?? 0);
                $flag = $adm >= 6 ? ['CRITICAL','danger'] : ($adm >= 4 ? ['HIGH','warning'] : ['MODERATE','info']);
              ?>
              <tr>
                <td class="pl-3 font-weight-bold"><?= Validator::sanitizeInput($fp['full_name']) ?></td>
                <td class="text-muted"><?= Validator::sanitizeInput($fp['national_id']) ?></td>
                <td><?= Validator::sanitizeInput($fp['policy_number'] ?? '—') ?></td>
                <td class="text-center"><strong class="text-danger"><?= $adm ?></strong></td>
                <td class="text-center"><?= (int)($fp['length_of_stay'] ?? 0) ?>d</td>
                <td class="text-center"><?= (int)($fp['total_claims'] ?? 0) ?></td>
                <td class="text-right font-weight-bold"><?= number_format((float)($fp['total_claimed'] ?? 0), 2) ?></td>
                <td class="text-center">
                  <?php if ((int)($fp['pending_claims'] ?? 0) > 0): ?>
                    <span class="badge badge-warning"><?= (int)$fp['pending_claims'] ?></span>
                  <?php else: echo '—'; endif; ?>
                </td>
                <td class="text-center"><span class="badge badge-light"><?= Validator::sanitizeInput($fp['diagnosis'] ?? '—') ?></span></td>
                <td class="text-center"><span class="badge badge-<?= $flag[1] ?>"><?= $flag[0] ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3 p-3 rounded" style="background:#fff8f8;border-left:4px solid #e74c3c;font-size:.84rem;">
          <strong class="text-danger"><i class="fas fa-lightbulb mr-1"></i>Action:</strong>
          Patients with ≥6 admissions should have claims placed on hold pending a full case audit.
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- POLICY RENEWAL PRICING -->
    <div class="card shadow mb-4 accent-renew anchor-offset" id="policyRenewal">
      <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap" style="background:linear-gradient(135deg,#fdf8ff,#fff);">
        <div class="d-flex align-items-center" style="gap:10px;">
          <div style="width:38px;height:38px;background:#8e44ad;border-radius:9px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-file-contract text-white"></i>
          </div>
          <div>
            <h6 class="m-0 font-weight-bold" style="color:#8e44ad;">📋 Policy Renewal Pricing</h6>
            <small class="text-muted">Active policies expiring within 90 days — ranked by risk score</small>
          </div>
        </div>
        <span class="badge badge-pill px-3 py-2" style="background:#8e44ad;color:#fff;"><?= count($renewal_patients) ?> Expiring Soon</span>
      </div>
      <?php if (!$renewal_patients): ?>
        <div class="card-body text-center text-muted py-4">
          <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>No policies expiring within 90 days.
        </div>
      <?php else: ?>
      <div class="card-body">
        <div class="row mb-4">
          <div class="col-md-4">
            <div class="p-3 rounded text-center" style="background:#fdf5ff;border:1px solid #e8d5f5;">
              <div style="font-size:1.6rem;font-weight:800;color:#8e44ad;"><?= count($renewal_patients) ?></div>
              <div style="font-size:.75rem;color:#888;">Total Expiring Policies</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 rounded text-center" style="background:#fff5f5;border:1px solid #f5c6cb;">
              <div style="font-size:1.6rem;font-weight:800;color:#e74c3c;"><?= $high_risk_count ?></div>
              <div style="font-size:.75rem;color:#888;">High Risk — Need Premium Raise</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 rounded text-center" style="background:#f8f9fa;border:1px solid #dee2e6;">
              <div style="font-size:1.3rem;font-weight:800;color:#333;">EGP <?= number_format($total_renewal_exp, 0) ?></div>
              <div style="font-size:.75rem;color:#888;">Total Claimed by These Patients</div>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-5 mb-3">
            <div class="chart-wrap">
              <div class="mb-2"><span class="leg-dot" style="background:#8e44ad;"></span><strong style="font-size:.8rem;color:#555;">Risk Score per Patient</strong></div>
              <div style="position:relative;height:210px;"><canvas id="chartRenewRisk"></canvas></div>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="chart-wrap">
              <div class="mb-2"><span class="leg-dot" style="background:#3498db;"></span><strong style="font-size:.8rem;color:#555;">Days Until Policy Expiry</strong></div>
              <div style="position:relative;height:210px;"><canvas id="chartRenewDays"></canvas></div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="chart-wrap h-100">
              <div class="mb-2"><strong style="font-size:.8rem;color:#555;">Renewal Advice Split</strong></div>
              <div style="position:relative;height:210px;"><canvas id="chartRenewPie"></canvas></div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0" style="font-size:.87rem;">
            <thead style="background:#fdf5ff;">
              <tr>
                <th class="pl-3">Patient</th><th>Policy #</th><th>Plan</th>
                <th class="text-center">Expires In</th><th class="text-center">Risk Score</th>
                <th class="text-center">Chronic Conditions</th>
                <th class="text-center">Admissions</th><th class="text-right">Total Claimed</th>
                <th class="text-center">Renewal Advice</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($renewal_patients as $rp):
                [$tierLabel, $tierColor] = InsuranceController::riskTier((float)($rp['risk_score'] ?? 0));
                $days    = (int)($rp['days_until_expiry'] ?? 0);
                $chronic = (int)($rp['chronic_count'] ?? 0);
                $conds   = [];
                if (!empty($rp['has_diabetes']))       $conds[] = '<span class="badge badge-danger mr-1">Diabetes</span>';
                if (!empty($rp['has_hypertension']))   $conds[] = '<span class="badge badge-warning mr-1">Hypertension</span>';
                if (!empty($rp['has_kidney_disease'])) $conds[] = '<span class="badge badge-info mr-1">Kidney</span>';
                if (!empty($rp['has_heart_disease']))  $conds[] = '<span class="badge badge-dark mr-1">Heart</span>';
                if ($tierLabel === 'High Risk' || $chronic >= 2)       $advice = ['Raise Premium +20%', 'danger'];
                elseif ($tierLabel === 'Medium Risk' || $chronic === 1) $advice = ['Review & Adjust +10%', 'warning'];
                else                                                     $advice = ['Standard Renewal', 'success'];
                $dc = $days <= 30 ? 'danger' : ($days <= 60 ? 'warning' : 'info');
              ?>
              <tr>
                <td class="pl-3 font-weight-bold"><?= Validator::sanitizeInput($rp['full_name']) ?></td>
                <td><?= Validator::sanitizeInput($rp['policy_number'] ?? '—') ?></td>
                <td><?= Validator::sanitizeInput($rp['plan_name'] ?? '—') ?></td>
                <td class="text-center"><span class="badge badge-<?= $dc ?>"><?= $days ?>d</span></td>
                <td class="text-center">
                  <?php if (!empty($rp['risk_score'])): ?>
                    <span class="badge badge-<?= $tierColor ?>"><?= $tierLabel ?></span>
                    <br><small class="text-muted"><?= round((float)$rp['risk_score'], 4) ?></small>
                  <?php else: echo '<span class="text-muted">No data</span>'; endif; ?>
                </td>
                <td class="text-center"><?= $conds ? implode('', $conds) : '<span class="text-muted">None</span>' ?></td>
                <td class="text-center"><?= (int)($rp['admission_count'] ?? 0) ?></td>
                <td class="text-right font-weight-bold">EGP <?= number_format((float)($rp['total_claimed'] ?? 0), 2) ?></td>
                <td class="text-center">
                  <span class="badge badge-<?= $advice[1] ?> px-2"><i class="fas fa-tag mr-1"></i><?= $advice[0] ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3 p-3 rounded" style="background:#fdf8ff;border-left:4px solid #8e44ad;font-size:.84rem;">
          <strong style="color:#8e44ad;"><i class="fas fa-lightbulb mr-1"></i>Action:</strong>
          Patients flagged <strong>"Raise Premium +20%"</strong> have high risk scores or multiple chronic conditions.
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div><!-- /riskAnalysis -->

</div><!-- /container-fluid -->

<footer class="sticky-footer bg-white mt-4">
  <div class="container my-auto">
    <div class="copyright text-center my-auto"><span>SmartConnect © 2026</span></div>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/jquery.easing.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sb-admin-2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
Chart.defaults.font.family = "'Nunito', sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#666';
const short = names => names.map(n => n.split(' ').slice(0,2).join(' '));
function makeChart(id, config) {
  const el = document.getElementById(id);
  if (el && el.getContext) new Chart(el, config);
}

<?php
/*
 * FIX 1 + 2: Build chart data from the new history format.
 *
 * OLD (broken):
 *   $hist = $ml_forecast['history'];       // was {years:[], coverage:[], growth:[]}
 *   $hist_cov = $hist['coverage'];         // EGP sums — wrong unit for the chart
 *   chart plotted $hist_cov on Y-axis      // → monetary values displayed
 *   $hist['growth'] existed but was never plotted → historical data "missing"
 *
 * NEW (fixed):
 *   $ml_forecast['history'] is now [{year, yoy_pct}, ...]
 *   We extract parallel $chart_years and $chart_growth arrays from it.
 *   A forecast point is appended for $ml_year using $ml_blended.
 *   The Y-axis now shows % values with a '%' tick callback.
 */
$history_items = $ml_forecast['history'] ?? [];   // [{year, yoy_pct}, ...]

$chart_years  = [];
$chart_growth = [];
foreach ($history_items as $item) {
    if (isset($item['year'], $item['yoy_pct']) && $item['yoy_pct'] !== null) {
        $chart_years[]  = (string)$item['year'];
        $chart_growth[] = (float)$item['yoy_pct'];
    }
}

// Append the forecast point so it appears as a distinct bar on the right
$chart_years[]     = (string)$ml_year;
$chart_growth[]    = (float)$ml_blended;

// Flag which bars are forecast vs history — used for color coding
$is_forecast = array_fill(0, count($chart_years) - 1, false);
$is_forecast[] = true;   // last point is the forecast
?>
const histYears  = <?= json_encode(array_values($chart_years)) ?>;
const histGrowth = <?= json_encode(array_values($chart_growth)) ?>;
const isForecast = <?= json_encode(array_values($is_forecast)) ?>;

/*
 * FIX 1: Y-axis now shows percentage values (YoY % coverage growth).
 * FIX 2: All historical bars are now rendered — they come from histGrowth,
 *         which is populated from the [{year, yoy_pct}] history list.
 * FIX 3: The forecast bar value equals blended_change_pct from the model,
 *         so what is displayed matches the number in the prediction card.
 */
makeChart('coverageHistoryChart', {
  type: 'bar',
  data: {
    labels: histYears,
    datasets: [{
      label: 'YoY Coverage Growth (%)',
      data: histGrowth,
      backgroundColor: isForecast.map(f => f ? 'rgba(78,115,223,0.5)' : 'rgba(28,200,138,0.7)'),
      borderColor:     isForecast.map(f => f ? '#4e73df'               : '#1cc88a'),
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => {
            const label = isForecast[ctx.dataIndex] ? '📈 Forecast: ' : 'Growth: ';
            return label + ctx.raw.toFixed(1) + '%';
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: false,
        grid: { color: '#f0f0f0' },
        // FIX 1: Y-axis tick shows % symbol instead of raw EGP value
        ticks: { callback: v => v.toFixed(1) + '%' },
        title: { display: true, text: 'YoY Growth (%)', font: { size: 10 } }
      },
      x: { grid: { display: false } }
    }
  }
});

const fraudNames      = <?= json_encode(array_values($fraud_names)) ?>;
const fraudAdmissions = <?= json_encode(array_values($fraud_admissions)) ?>;
const fraudClaimed    = <?= json_encode(array_values($fraud_claimed)) ?>;
const fraudPending    = <?= json_encode(array_values($fraud_pending)) ?>;
const renNames        = <?= json_encode(array_values($ren_names)) ?>;
const renDays         = <?= json_encode(array_values($ren_days)) ?>;
const renRisk         = <?= json_encode(array_values($ren_risk)) ?>;
const raiseCount      = <?= (int)$raise_c ?>;
const reviewCount     = <?= (int)$review_c ?>;
const standardCount   = <?= (int)$std_c ?>;

makeChart('chartFraudAdmissions', {
  type: 'bar',
  data: { labels: short(fraudNames), datasets:[{ label:'Admissions', data:fraudAdmissions,
    backgroundColor: fraudAdmissions.map(v=>v>=6?'rgba(192,57,43,.85)':v>=4?'rgba(231,76,60,.75)':'rgba(231,76,60,.45)'),
    borderColor:'#c0392b', borderWidth:1, borderRadius:5 }]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
    scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'#f0f0f0'}},x:{grid:{display:false}}}}
});

makeChart('chartFraudClaimed', {
  type:'bar',
  data:{ labels:short(fraudNames), datasets:[
    {label:'Claimed (EGP)',data:fraudClaimed,backgroundColor:'rgba(192,57,43,.7)',borderRadius:5,yAxisID:'y'},
    {label:'Pending Claims',data:fraudPending,backgroundColor:'rgba(243,156,18,.8)',borderRadius:5,yAxisID:'y1'}
  ]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{boxWidth:10}}},
    scales:{
      y:{beginAtZero:true,position:'left',grid:{color:'#f0f0f0'},title:{display:true,text:'EGP'}},
      y1:{beginAtZero:true,position:'right',grid:{drawOnChartArea:false},ticks:{stepSize:1},title:{display:true,text:'Count'}},
      x:{grid:{display:false}}}}
});

makeChart('chartRenewRisk', {
  type:'bar',
  data:{labels:short(renNames), datasets:[{label:'Risk Score',data:renRisk,
    backgroundColor:renRisk.map(v=>v>=0.7?'rgba(192,57,43,.8)':v>=0.4?'rgba(243,156,18,.8)':'rgba(39,174,96,.7)'),
    borderRadius:5}]},
  options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
    scales:{x:{min:0,max:1,grid:{color:'#f0f0f0'},ticks:{callback:v=>(v*100)+'%'}},y:{grid:{display:false}}}}
});

makeChart('chartRenewDays', {
  type:'bar',
  data:{labels:short(renNames), datasets:[{label:'Days Until Expiry',data:renDays,
    backgroundColor:renDays.map(v=>v<=30?'rgba(192,57,43,.8)':v<=60?'rgba(243,156,18,.8)':'rgba(52,152,219,.7)'),
    borderRadius:5}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
    scales:{y:{beginAtZero:true,grid:{color:'#f0f0f0'},title:{display:true,text:'Days'}},x:{grid:{display:false}}}}
});

makeChart('chartRenewPie', {
  type:'doughnut',
  data:{labels:['Raise +20%','Adjust +10%','Standard'], datasets:[{
    data:[raiseCount,reviewCount,standardCount],
    backgroundColor:['rgba(192,57,43,.85)','rgba(243,156,18,.85)','rgba(39,174,96,.8)'],
    borderWidth:2, borderColor:'#fff'}]},
  options:{responsive:true,maintainAspectRatio:false,cutout:'60%',
    plugins:{legend:{position:'bottom',labels:{boxWidth:10,padding:8}},
    tooltip:{callbacks:{label:ctx=>`${ctx.label}: ${ctx.raw} patients`}}}}
});
</script>

<script>
/* ═══════════════════════════════════════════════════
   Add Patient — client-side validation
   Mirrors Validator.php rules exactly so errors show
   instantly, before the PHP round-trip.
═══════════════════════════════════════════════════ */
(function () {

  /* ── helpers ── */
  function el(id)   { return document.getElementById(id); }
  function ok(id)   { el('inp_' + id).classList.remove('is-invalid'); el('inp_' + id).classList.add('is-valid'); el('err_' + id).textContent = ''; }
  function fail(id, msg) { el('inp_' + id).classList.remove('is-valid'); el('inp_' + id).classList.add('is-invalid'); el('err_' + id).textContent = msg; }
  function reset(id) { el('inp_' + id).classList.remove('is-valid', 'is-invalid'); el('err_' + id).textContent = ''; }

  /* ── National ID decoder (Egyptian 14-digit format)
       Digit 1   : 2 = born 1900s, 3 = born 2000s
       Digits 2-7 : YYMMDD (birth date)
       Digits 8-9 : governorate code (01-27)
       Digits 10-13: sequence
       Digit 14  : check digit (odd = male, even = female)
  ── */
  function decodeNationalId(nid) {
    if (!/^\d{14}$/.test(nid)) return null;
    var century = nid[0];
    if (century !== '2' && century !== '3') return null;
    var year  = (century === '2' ? '19' : '20') + nid.substring(1, 3);
    var month = nid.substring(3, 5);
    var day   = nid.substring(5, 7);
    var gov   = parseInt(nid.substring(7, 9), 10);
    var d = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
    if (
      isNaN(d.getTime()) ||
      d.getFullYear() !== parseInt(year) ||
      d.getMonth()    !== parseInt(month) - 1 ||
      d.getDate()     !== parseInt(day)
    ) return null;
    if (gov < 1 || gov > 27) return null;
    var today = new Date();
    var age = today.getFullYear() - d.getFullYear();
    if (today < new Date(today.getFullYear(), d.getMonth(), d.getDate())) age--;
    if (age < 0 || age > 120) return null;
    var genderFromId = parseInt(nid[12]) % 2 === 1 ? 'M' : 'F';
    return { year: year, month: month, day: day, age: age, gender: genderFromId };
  }

  var GOV_NAMES = {
    '01':'Cairo','02':'Alexandria','03':'Port Said','04':'Suez',
    '11':'Damietta','12':'Dakahlia','13':'Sharqia','14':'Qalyubia',
    '15':'Kafr El Sheikh','16':'Gharbia','17':'Monufia','18':'Beheira',
    '19':'Ismailia','21':'Giza','22':'Beni Suef','23':'Fayyum',
    '24':'Minya','25':'Asyut','26':'Sohag','27':'Qena',
    '28':'Aswan','29':'Luxor','31':'Red Sea','32':'New Valley',
    '33':'Matrouh','34':'North Sinai','35':'South Sinai','88':'Foreign'
  };

  /* ── field validators ── */
  function validateFullName() {
    var v = el('inp_full_name').value.trim();
    if (v === '')                           { fail('full_name', 'Full name is required.'); return false; }
    if (!/^[A-Za-z\s]+$/.test(v))          { fail('full_name', 'Name must contain letters and spaces only (no numbers or symbols).'); return false; }
    if (v.replace(/\s+/g, ' ').split(' ').filter(function(w){return w.length>0;}).length < 2)
                                            { fail('full_name', 'Please enter at least a first and last name.'); return false; }
    if (v.length < 3)                       { fail('full_name', 'Name must be at least 3 characters.'); return false; }
    if (v.length > 100)                     { fail('full_name', 'Name must be 100 characters or fewer.'); return false; }
    ok('full_name'); return true;
  }

  function validateNationalId() {
    var v = el('inp_national_id').value.trim();
    var hint = el('hint_national_id');
    hint.style.display = 'none'; hint.textContent = '';
    if (v === '')              { fail('national_id', 'National ID is required.'); return false; }
    if (!/^\d+$/.test(v))     { fail('national_id', 'National ID must contain digits only.'); return false; }
    if (v.length !== 14)      { fail('national_id', 'National ID must be exactly 14 digits (entered: ' + v.length + ').'); return false; }
    var info = decodeNationalId(v);
    if (!info)                 { fail('national_id', 'Invalid National ID — check the birth date and century digit (2 = 1900s, 3 = 2000s).'); return false; }
    var govCode = v.substring(7, 9);
    var govName = GOV_NAMES[govCode] || ('Code ' + govCode);
    /* Store decoded data silently on the input for use in patient portal — not shown in UI */
    var inp = el('inp_national_id');
    inp.dataset.dob    = info.year + '-' + info.month + '-' + info.day;
    inp.dataset.age    = info.age;
    inp.dataset.gov    = govName;
    inp.dataset.gender = info.gender;
    ok('national_id'); return true;
  }

  function validatePhone() {
    var v = el('inp_phone').value.trim();
    if (v === '')                                        { fail('phone', 'Phone number is required.'); return false; }
    if (!/^\d+$/.test(v))                               { fail('phone', 'Phone must contain digits only.'); return false; }
    if (v.length !== 11)                                 { fail('phone', 'Egyptian phone numbers are 11 digits (entered: ' + v.length + ').'); return false; }
    if (!/^(010|011|012|015)\d{8}$/.test(v))            { fail('phone', 'Must start with 010, 011, 012, or 015.'); return false; }
    ok('phone'); return true;
  }

  function validateGender() {
    var v = el('inp_gender').value;
    if (v !== 'M' && v !== 'F') { fail('gender', 'Please select a gender.'); return false; }
    /* Cross-check against National ID if already filled */
    var nid = el('inp_national_id').value.trim();
    var info = decodeNationalId(nid);
    if (info && info.gender !== v) {
      fail('gender', 'Gender does not match the National ID (' + (info.gender === 'M' ? 'Male' : 'Female') + ' based on ID).');
      return false;
    }
    ok('gender'); return true;
  }

  function validateAddress() {
    var v = el('inp_address').value.trim();
    if (v === '')        { fail('address', 'Address is required.'); return false; }
    if (v.length < 5)   { fail('address', 'Address is too short (minimum 5 characters).'); return false; }
    if (v.length > 255) { fail('address', 'Address must be 255 characters or fewer.'); return false; }
    ok('address'); return true;
  }

  /* ── live feedback on blur ── */
  el('inp_full_name').addEventListener('blur',      validateFullName);
  el('inp_national_id').addEventListener('blur',    validateNationalId);
  el('inp_phone').addEventListener('blur',          validatePhone);
  el('inp_gender').addEventListener('change',       validateGender);
  el('inp_address').addEventListener('blur',        validateAddress);

  /* ── digits-only enforcement while typing ── */
  ['inp_national_id', 'inp_phone'].forEach(function(id) {
    el(id).addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '');
    });
  });

  /* ── re-check gender when National ID filled ── */
  el('inp_national_id').addEventListener('blur', function() {
    if (el('inp_gender').value !== '') validateGender();
  });

  /* ── submit guard ── */
  el('addPatientForm').addEventListener('submit', function(e) {
    var valid = [
      validateFullName(),
      validateNationalId(),
      validatePhone(),
      validateGender(),
      validateAddress()
    ].every(Boolean);

    if (!valid) {
      e.preventDefault();
      /* scroll to first error */
      var first = this.querySelector('.is-invalid');
      if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

})();
</script>
</body>
</html>
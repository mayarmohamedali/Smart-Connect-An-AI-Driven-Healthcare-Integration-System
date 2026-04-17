<?php
// ─────────────────────────────────────────────────────────────────────────────
// Variables injected by HospitalController::dashboard():
//   $hospitalObj, $hospital_name, $hospital_id
//   $kpi_patients, $kpi_medical_records, $kpi_insured_patients, $kpi_recent_admissions
//   $patients (array), $q, $success_msg, $error_msg
//   $auth (Auth)
//   $api_alive (bool), $calendar (array), $live_forecast (array), $records_this_month (int)
// ─────────────────────────────────────────────────────────────────────────────

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function severity_color(string $s): string {
    return match($s) { 'critical' => 'danger', 'high' => 'warning', 'medium' => 'info', default => 'secondary' };
}
function severity_icon(string $s): string {
    return match($s) { 'critical' => '🔴', 'high' => '🟠', 'medium' => '🟡', default => '🟢' };
}

$month_names  = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$today_month  = (int)date('n');
$next_month_n = $today_month % 12 + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Hospital Dashboard – Smart-Connect</title>
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fc; }
    .section-title { font-weight: 800; }
    .anchor-offset { scroll-margin-top: 90px; }
    .dash-title { font-weight: 800; letter-spacing: .2px; }
    .kpi-card { border-radius: 12px; }
    .kpi-card .card-body { padding: 14px 16px; }
    .kpi-label { font-size: .72rem; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; margin-bottom: 6px; }
    .kpi-value { font-size: 1.25rem; font-weight: 800; line-height: 1.1; }
  </style>
</head>
<body id="page-top">

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- NAVBAR                                                                      -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#dashboard">
      <i class="fas fa-clinic-medical mr-2"></i>
      <strong><?= e($hospital_name) ?> Dashboard</strong>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
        <li class="nav-item"><a class="nav-link" href="#dashboard"><i class="fas fa-tachometer-alt mr-1"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="#patients"><i class="fas fa-user-injured mr-1"></i> Patients</a></li>
        <li class="nav-item"><a class="nav-link" href="#epidemic-alerts"><i class="fas fa-exclamation-triangle mr-1"></i> Epidemic Alerts</a></li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
            <span class="d-none d-lg-inline mr-2"><?= e($auth->getSessionData('staff_name') ?? 'Hospital Staff') ?></span>
            <i class="fas fa-user-circle fa-2x text-white"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="#"><i class="fas fa-user mr-2"></i> Profile</a>
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

<div class="container-fluid py-4">

  <?php if ($success_msg): ?><div class="alert alert-success"><?= e($success_msg) ?></div><?php endif; ?>
  <?php if ($error_msg):   ?><div class="alert alert-danger"><?= e($error_msg) ?></div><?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- DASHBOARD OVERVIEW — KPI CARDS                                         -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div id="dashboard" class="anchor-offset mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <h5 class="dash-title text-gray-800 mb-0">
        <i class="fas fa-chart-pie mr-2 text-primary"></i> Dashboard Overview
      </h5>
      <span class="badge badge-light">
        <i class="fas fa-calendar-alt mr-1"></i> <?= date('F Y') ?>
      </span>
    </div>
    <div class="row">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-primary">Total Patients</div>
            <div class="kpi-value text-gray-800"><?= (int)$kpi_patients ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-success">Medical Records</div>
            <div class="kpi-value text-gray-800"><?= (int)$kpi_medical_records ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-info">Insured Patients</div>
            <div class="kpi-value text-gray-800"><?= (int)$kpi_insured_patients ?></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow-sm kpi-card">
          <div class="card-body">
            <div class="kpi-label text-warning">New This Week</div>
            <div class="kpi-value text-gray-800"><?= (int)$kpi_recent_admissions ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- EPIDEMIC ALERT SYSTEM — ML / Real DB Data                              -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div id="epidemic-alerts" class="anchor-offset mt-4">

    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
      <h3 class="text-danger section-title mb-0">🏥 Hospital Epidemic Alert System</h3>
      <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
        <?php if (!$api_alive): ?>
          <span class="badge badge-danger p-2">
            <i class="fas fa-times-circle mr-1"></i> ML API Offline
          </span>
        <?php else: ?>
          <span class="badge badge-success p-2">
            <i class="fas fa-check-circle mr-1"></i> ML Model Connected
          </span>
        <?php endif; ?>
        <span class="badge badge-<?= $records_this_month >= 5 ? 'primary' : 'secondary' ?> p-2">
          <i class="fas fa-database mr-1"></i>
          <?= $records_this_month ?> records this month
          <?php if ($records_this_month < 10): ?>
            &mdash; need <?= 10 - $records_this_month ?> more for forecast
          <?php endif; ?>
        </span>
      </div>
    </div>

    <?php
      $next_month_label = $month_names[$next_month_n] ?? 'Next Month';
      $forecast_entry   = null;
      $forecast_source  = 'none';

      if (!empty($live_forecast)) {
          if (($live_forecast['status'] ?? '') === 'ok') {
              $forecast_entry  = $live_forecast;
              $forecast_source = 'live';
          } elseif (($live_forecast['status'] ?? '') === 'insufficient_data') {
              $forecast_source = 'insufficient';
          }
      }

      // Fall back to historical baseline
      if (in_array($forecast_source, ['none', 'insufficient'])) {
          foreach ($calendar as $entry) {
              if ((int)($entry['month'] ?? 0) === $next_month_n) {
                  $forecast_entry  = $entry;
                  $forecast_source = ($forecast_source === 'insufficient')
                                     ? 'insufficient_with_history' : 'historical';
                  break;
              }
          }
      }
    ?>

    <?php if (in_array($forecast_source, ['insufficient', 'insufficient_with_history'])): ?>
      <div class="alert alert-info d-flex align-items-start mb-3" style="gap:12px;">
        <i class="fas fa-info-circle fa-2x mt-1"></i>
        <div style="flex:1;">
          <strong>Collecting patient data for forecast...</strong><br>
          <small><?= $records_this_month ?> of 20 records needed this month. Showing historical baseline until enough records are collected.</small>
          <div class="progress mt-2" style="height:8px; border-radius:4px;">
            <div class="progress-bar bg-info" style="width:<?= min(100, ($records_this_month / 20) * 100) ?>%"></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($forecast_entry):
      $disease  = e($forecast_entry['dominant_display'] ?? str_replace('_', ' ', $forecast_entry['dominant_disease'] ?? 'Unknown'));
      $severity = $forecast_entry['severity'] ?? 'medium';
      $color    = severity_color($severity);
      $icon     = severity_icon($severity);
      $recs     = $forecast_entry['recommendations'] ?? [];
      $pts_used = $forecast_entry['total_patients'] ?? null;
    ?>
    <div class="card shadow mb-4 border-left-<?= $color ?>">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between flex-wrap mb-2">
          <div>
            <h5 class="font-weight-bold text-<?= $color ?> mb-1">
              <?= $icon ?> <?= $next_month_label ?> Forecast: <?= $disease ?>
            </h5>
            <p class="text-muted mb-0" style="font-size:.85rem;">
              <?php if ($forecast_source === 'live'): ?>
                <span class="badge badge-success mr-1">LIVE</span>
                Based on <strong><?= (int)$pts_used ?></strong> real patient records from your database this month
              <?php else: ?>
                <span class="badge badge-secondary mr-1">HISTORICAL</span>
                Trained model baseline &mdash; add more medical records this month to get a live prediction
              <?php endif; ?>
              &nbsp;&middot;&nbsp;
              Severity: <span class="badge badge-<?= $color ?>"><?= ucfirst($severity) ?></span>
            </p>
          </div>
          <span class="badge badge-<?= $color ?> badge-pill p-2" style="font-size:1rem;">
            <?= strtoupper($next_month_label) ?>
          </span>
        </div>

        <?php if (!empty($forecast_entry['distribution_pct'])): ?>
          <hr class="my-2">
          <p class="mb-2" style="font-size:.78rem; font-weight:800; color:#555; text-transform:uppercase; letter-spacing:.5px;">
            Predicted disease distribution for <?= $next_month_label ?>
          </p>
          <?php foreach ($forecast_entry['distribution_pct'] as $dis => $pct):
            $dis_label   = str_replace('_', ' ', $dis);
            $pct_float   = (float)$pct;
            $is_dominant = ($dis === ($forecast_entry['dominant_disease'] ?? ''));
          ?>
          <div class="d-flex align-items-center mb-1">
            <div style="min-width:200px; font-size:.8rem; color:#444;">
              <?= $is_dominant ? '<strong>' : '' ?><?= e($dis_label) ?><?= $is_dominant ? '</strong>' : '' ?>
            </div>
            <div class="progress flex-grow-1" style="height:16px; border-radius:6px;">
              <div class="progress-bar <?= $is_dominant ? 'bg-'.$color : 'bg-secondary' ?>"
                   role="progressbar"
                   style="width:<?= min(100, $pct_float) ?>%; font-size:.75rem;">
                <?= $pct_float ?>%
              </div>
            </div>
            <?php if ($forecast_source === 'live'): ?>
              <span style="min-width:70px; text-align:right; font-size:.78rem; color:#666; padding-left:8px;">
                <?= (int)($forecast_entry['distribution'][$dis] ?? 0) ?> patients
              </span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($recs)): ?>
          <div class="alert alert-<?= $color ?> py-2 mb-0 mt-3">
            <strong>🔧 Preparation Checklist for <?= $next_month_label ?>:</strong>
            <ul class="mb-0 mt-1 pl-4" style="font-size:.85rem;">
              <?php foreach ($recs as $rec): ?>
                <li><?= e($rec) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>

    <!-- Full Year Calendar -->
    <?php if (!empty($calendar)): ?>
    <div class="card shadow mb-4">
      <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
          <i class="fas fa-calendar-alt mr-2"></i> Full-Year Epidemic Forecast Calendar
        </h6>
        <small class="text-muted">Trained ML model baseline</small>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" style="font-size:.88rem;">
            <thead class="thead-light">
              <tr>
                <th style="width:80px;">Month</th>
                <th>Dominant Disease</th>
                <th style="width:110px;">Severity</th>
                <th>Top Recommendation</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($calendar as $entry):
                $m_num   = (int)($entry['month'] ?? 0);
                $m_name  = e($entry['month_name'] ?? '');
                $dis     = e($entry['dominant_display'] ?? str_replace('_', ' ', $entry['dominant_disease'] ?? ''));
                $sev     = $entry['severity'] ?? 'medium';
                $col     = severity_color($sev);
                $ico     = severity_icon($sev);
                $rec1    = $entry['recommendations'][0] ?? 'General preparedness recommended.';
                $is_now  = ($m_num === $today_month);
                $is_next = ($m_num === $next_month_n);
              ?>
              <tr <?= $is_next ? 'class="table-warning font-weight-bold"' : ($is_now ? 'class="table-light"' : '') ?>>
                <td>
                  <?= $m_name ?>
                  <?php if ($is_next): ?>
                    <span class="badge badge-warning" style="font-size:.6rem;">Next</span>
                  <?php elseif ($is_now): ?>
                    <span class="badge badge-primary" style="font-size:.6rem;">Now</span>
                  <?php endif; ?>
                </td>
                <td><?= $ico ?> <?= $dis ?></td>
                <td><span class="badge badge-<?= $col ?>"><?= ucfirst($sev) ?></span></td>
                <td style="color:#555; font-size:.82rem;"><?= e($rec1) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!$api_alive): ?>
      <div class="alert alert-warning">
        <i class="fas fa-plug mr-2"></i>
        <strong>ML API is not running.</strong>
        Open a terminal in your project folder and run: <code>py app.py</code> — then refresh this page.
      </div>
    <?php endif; ?>

  </div>
  <!-- END EPIDEMIC ALERTS -->

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- PATIENT MANAGEMENT                                                      -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div id="patients" class="anchor-offset mt-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <h3 class="text-primary section-title mb-2">Patient Management</h3>
    </div>
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
            <h6 class="m-0 font-weight-bold text-primary">View Patients</h6>
            <form class="d-flex mt-2 mt-md-0" method="GET"
                  action="<?= BASE_URL ?>/hospital/dashboard#patients"
                  style="gap:8px; flex:1; max-width:520px;">
              <input class="form-control form-control-lg" name="q" value="<?= e($q) ?>"
                     placeholder="Search by name, national ID, or phone" style="font-size:.95rem;">
              <button class="btn btn-primary btn-lg" type="submit" style="min-width:50px;">
                <i class="fas fa-search"></i>
              </button>
            </form>
          </div>
          <div class="card-body">
            <?php if (!$patients || count($patients) === 0): ?>
              <p class="text-muted mb-0">No patients found.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>ID</th><th>Name</th><th>National ID</th>
                      <th>Phone</th><th>Gender</th><th>Insurance</th><th>Medical Record</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($patients as $pt): ?>
                      <tr>
                        <td><?= (int)($pt['patient_id'] ?? 0) ?></td>
                        <td><?= e($pt['full_name'] ?? '') ?></td>
                        <td><?= e($pt['national_id'] ?? '') ?></td>
                        <td><?= e($pt['phone'] ?? '') ?></td>
                        <td><?= e($pt['gender'] ?? '') ?></td>
                        <td>
                          <?php
                            $insId   = (int)($pt['insurance_id'] ?? 0);
                            $insName = $pt['insurance_name'] ?? null;
                            if ($insId > 0) {
                                echo '<span class="badge badge-success">' . e($insName ?: '#' . $insId) . '</span>';
                            } else {
                                echo '<span class="badge badge-secondary">No Insurance</span>';
                            }
                          ?>
                        </td>
                        <td style="white-space:nowrap;">
                          <a class="btn btn-sm btn-primary"
                             href="<?= BASE_URL ?>/hospital/addRecord?patient_id=<?= (int)($pt['patient_id'] ?? 0) ?>">
                            <i class="fas fa-notes-medical mr-1"></i> Add Record
                          </a>
                          <a class="btn btn-sm btn-outline-secondary ml-2"
                             href="<?= BASE_URL ?>/hospital/viewRecords?patient_id=<?= (int)($pt['patient_id'] ?? 0) ?>">
                            <i class="fas fa-folder-open mr-1"></i> View
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
      </div>
    </div>
  </div>

</div><!-- end container-fluid -->

<footer class="sticky-footer bg-white">
  <div class="container my-auto text-center">
    <span>Smart-Connect Hospital Dashboard &copy; <?= date('Y') ?></span>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/jquery.easing.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sb-admin-2.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/chart.min.js"></script>
</body>
</html>
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
    return match(strtolower($s)) {
        'critical' => 'danger',
        'high'     => 'warning',
        'medium'   => 'info',
        default    => 'secondary'
    };
}

function severity_icon(string $s): string {
    return match(strtolower($s)) {
        'critical' => '🔴',
        'high'     => '🟠',
        'medium'   => '🟡',
        default    => '🟢'
    };
}

$month_names = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$today_month = (int)date('n');
$next_month_n = $today_month % 12 + 1;
$next_month_label = $month_names[$next_month_n] ?? 'Next Month';

// Support both controller formats:
// 1) $calendar = [rows...]
// 2) $calendar = ['calendar' => [rows...]]
$calendarRows = [];

if (!empty($calendar['calendar']) && is_array($calendar['calendar'])) {
    $calendarRows = $calendar['calendar'];
} elseif (!empty($calendar) && is_array($calendar)) {
    $calendarRows = $calendar;
}

// Find upcoming month forecast from live_forecast first, then fallback to calendar.
$forecast_entry = null;

if (!empty($live_forecast) && is_array($live_forecast) && (($live_forecast['status'] ?? '') === 'ok')) {
    $forecast_entry = [
        'month'            => $live_forecast['predicted_month'] ?? $next_month_n,
        'month_name'       => $live_forecast['month_name'] ?? $next_month_label,
        'dominant_disease' => $live_forecast['dominant_disease'] ?? 'No Records',
        'dominant_display' => $live_forecast['dominant_display'] ?? 'No records this month',
        'severity'         => $live_forecast['severity'] ?? 'low',
        'recommendations'  => $live_forecast['recommendations'] ?? ['No hospital records available for this month.'],
        'recommendation'   => $live_forecast['recommendations'][0] ?? 'No hospital records available for this month.',
        'case_count'       => $live_forecast['total_patients'] ?? 0,
        'source'           => $live_forecast['source'] ?? 'hospital_database'
    ];
}

if (!$forecast_entry) {
    foreach ($calendarRows as $entry) {
        if ((int)($entry['month'] ?? 0) === $next_month_n) {
            $forecast_entry = $entry;
            break;
        }
    }
}

if (!$forecast_entry) {
    $forecast_entry = [
        'month'            => $next_month_n,
        'month_name'       => $next_month_label,
        'dominant_disease' => 'No Records',
        'dominant_display' => 'No records this month',
        'severity'         => 'low',
        'recommendation'   => 'No hospital records available for this month.',
        'recommendations'  => ['No hospital records available for this month.'],
        'case_count'       => 0,
        'source'           => 'hospital_database'
    ];
}

$upcomingDisease = $forecast_entry['dominant_display']
    ?? str_replace('_', ' ', ($forecast_entry['dominant_disease'] ?? 'No records this month'));

$upcomingSeverity = strtolower($forecast_entry['severity'] ?? 'low');
$upcomingColor = severity_color($upcomingSeverity);
$upcomingIcon = severity_icon($upcomingSeverity);
$upcomingCases = (int)($forecast_entry['case_count'] ?? 0);
$upcomingReview = $forecast_entry['recommendation']
    ?? ($forecast_entry['recommendations'][0] ?? 'No hospital records available for this month.');
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
    body {
      background: #f8f9fc;
    }

    .section-title {
      font-weight: 800;
    }

    .anchor-offset {
      scroll-margin-top: 90px;
    }

    .dash-title {
      font-weight: 800;
      letter-spacing: .2px;
    }

    .kpi-card {
      border-radius: 12px;
    }

    .kpi-card .card-body {
      padding: 14px 16px;
    }

    .kpi-label {
      font-size: .72rem;
      font-weight: 800;
      letter-spacing: .6px;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .kpi-value {
      font-size: 1.25rem;
      font-weight: 800;
      line-height: 1.1;
    }

    .epidemic-card {
      border-radius: 12px;
      overflow: hidden;
    }

    .forecast-summary {
      border-radius: 10px;
      background: #f8f9fc;
      padding: 14px 16px;
    }

    .forecast-label {
      font-size: .75rem;
      font-weight: 800;
      text-transform: uppercase;
      color: #6c757d;
      letter-spacing: .5px;
      margin-bottom: 4px;
    }

    .forecast-value {
      font-size: 1.05rem;
      font-weight: 800;
      color: #343a40;
    }

    .table td,
    .table th {
      vertical-align: middle;
    }
  </style>
</head>

<body id="page-top">

<!-- NAVBAR -->
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
        <li class="nav-item">
          <a class="nav-link" href="#dashboard">
            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="#patients">
            <i class="fas fa-user-injured mr-1"></i> Patients
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="#epidemic-alerts">
            <i class="fas fa-exclamation-triangle mr-1"></i> Epidemic Alerts
          </a>
        </li>
      </ul>

      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
            <span class="d-none d-lg-inline mr-2">
              <?= e($auth->getSessionData('staff_name') ?? 'Hospital Staff') ?>
            </span>
            <i class="fas fa-user-circle fa-2x text-white"></i>
          </a>

          <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="#">
              <i class="fas fa-user mr-2"></i> Profile
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

<div class="container-fluid py-4">

  <?php if ($success_msg): ?>
    <div class="alert alert-success"><?= e($success_msg) ?></div>
  <?php endif; ?>

  <?php if ($error_msg): ?>
    <div class="alert alert-danger"><?= e($error_msg) ?></div>
  <?php endif; ?>

  <!-- DASHBOARD OVERVIEW -->
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

  <!-- EPIDEMIC ALERT SYSTEM -->
  <div id="epidemic-alerts" class="anchor-offset mt-4">

    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
      <div>
        <h3 class="text-danger section-title mb-1">🏥 Hospital Epidemic Alert System</h3>
        <small class="text-muted">
          Upcoming-month forecast based only on records created by <?= e($hospital_name) ?>.
        </small>
      </div>

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

        <span class="badge badge-primary p-2">
          <i class="fas fa-database mr-1"></i>
          <?= (int)$records_this_month ?> hospital records this month
        </span>
      </div>
    </div>

    <!-- Upcoming Month Forecast Card -->
    <div class="card shadow mb-4 border-left-<?= $upcomingColor ?> epidemic-card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
          <div>
            <h5 class="font-weight-bold text-<?= $upcomingColor ?> mb-1">
              <?= $upcomingIcon ?> <?= e($next_month_label) ?> Forecast: <?= e($upcomingDisease) ?>
            </h5>

            <p class="text-muted mb-0" style="font-size:.9rem;">
              Upcoming-month forecast based on this hospital’s own medical records.
              <span class="mx-1">•</span>
              Severity:
              <span class="badge badge-<?= $upcomingColor ?>">
                <?= ucfirst(e($upcomingSeverity)) ?>
              </span>
            </p>
          </div>

          <span class="badge badge-<?= $upcomingColor ?> badge-pill p-2" style="font-size:1rem;">
            <?= strtoupper(e($next_month_label)) ?>
          </span>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3 mb-md-0">
            <div class="forecast-summary">
              <div class="forecast-label">Dominant Disease</div>
              <div class="forecast-value"><?= e($upcomingDisease) ?></div>
            </div>
          </div>

          <div class="col-md-4 mb-3 mb-md-0">
            <div class="forecast-summary">
              <div class="forecast-label">Cases Found</div>
              <div class="forecast-value">
                <?= (int)$upcomingCases ?> case<?= $upcomingCases === 1 ? '' : 's' ?>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="forecast-summary">
              <div class="forecast-label">Forecast Month</div>
              <div class="forecast-value"><?= e($next_month_label) ?></div>
            </div>
          </div>
        </div>

        <div class="alert alert-<?= $upcomingColor ?> py-2 mb-0 mt-3">
          <strong>Recommended action for <?= e($next_month_label) ?>:</strong>
          <div class="mt-1">
            <?= e($upcomingReview) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Month Forecast Summary Table -->
    <div class="card shadow mb-4 epidemic-card">
      <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
        <h6 class="m-0 font-weight-bold text-primary">
          <i class="fas fa-calendar-alt mr-2"></i> Upcoming Month Forecast Summary
        </h6>

        <small class="text-muted">
          Same result as the forecast card above
        </small>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" style="font-size:.9rem;">
            <thead class="thead-light">
              <tr>
                <th style="width:90px;">Month</th>
                <th>Predicted Disease</th>
                <th style="width:90px;">Cases</th>
                <th style="width:120px;">Severity</th>
                <th>Top Recommendation</th>
              </tr>
            </thead>

            <tbody>
              <tr class="table-warning font-weight-bold">
                <td>
                  <?= e($next_month_label) ?>
                  <span class="badge badge-warning" style="font-size:.6rem;">Next</span>
                </td>

                <td>
                  <?= $upcomingIcon ?> <?= e($upcomingDisease) ?>
                </td>

                <td>
                  <span class="badge badge-light border">
                    <?= (int)$upcomingCases ?>
                  </span>
                </td>

                <td>
                  <span class="badge badge-<?= $upcomingColor ?>">
                    <?= ucfirst(e($upcomingSeverity)) ?>
                  </span>
                </td>

                <td style="color:#555; font-size:.84rem;">
                  <?= e($upcomingReview) ?>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if (!$api_alive): ?>
      <div class="alert alert-warning">
        <i class="fas fa-plug mr-2"></i>
        <strong>ML API is not running.</strong>
        Open a terminal in your project folder and run:
        <code>python app/api/app.py</code>
        then refresh this page.
      </div>
    <?php endif; ?>

  </div>
  <!-- END EPIDEMIC ALERTS -->

  <!-- PATIENT MANAGEMENT -->
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
              <input class="form-control form-control-lg"
                     name="q"
                     value="<?= e($q) ?>"
                     placeholder="Search by name, national ID, or phone"
                     style="font-size:.95rem;">

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
                      <th>ID</th>
                      <th>Name</th>
                      <th>National ID</th>
                      <th>Phone</th>
                      <th>Gender</th>
                      <th>Insurance</th>
                      <th>Medical Record</th>
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
                            $insId = (int)($pt['insurance_id'] ?? 0);
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

</div>

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
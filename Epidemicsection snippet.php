<?php
/**
 * HospitalDashboard_EpidemicSection.php
 * ─────────────────────────────────────────────────────────────────────────────
 * DROP-IN REPLACEMENT for the static "Epidemic Alert System" section in
 * HospitalDashboard.php.
 *
 * HOW TO INTEGRATE:
 *  1. Copy EpidemicForecast.php to your project root (same folder as HospitalDashboard.php).
 *  2. At the TOP of HospitalDashboard.php (after your existing require_once lines), add:
 *
 *       require_once __DIR__ . '/EpidemicForecast.php';
 *
 *       // ── Epidemic Forecast ─────────────────────────────────────────────
 *       $ef           = new EpidemicForecast("http://localhost:5000");
 *       $api_alive    = $ef->isApiAlive();
 *       $calendar     = $api_alive ? $ef->getHistoricalCalendar() : [];
 *       $today_month  = (int)date("n");
 *       $next_month_n = $today_month % 12 + 1;
 *
 *       // Live forecast using current-month registered patients
 *       // Build $patients_for_forecast from your DB query for this month's patients
 *       // (see the "Build patient batch" comment below for the required fields)
 *       $live_forecast = [];
 *       if ($api_alive && count($patients) >= 20) {
 *           $patients_for_forecast = array_map(function($pt) {
 *               return [
 *                   "Age"                 => (float)($pt["age"]             ?? 35),
 *                   "Gender"              => (string)($pt["gender"]         ?? "Male"),
 *                   "BMI"                 => (float)($pt["bmi"]             ?? 25.0),
 *                   "Blood_Pressure"      => (float)($pt["blood_pressure"]  ?? 120),
 *                   "Cholesterol_Level"   => (float)($pt["cholesterol"]     ?? 200),
 *                   "Glucose_Level"       => (float)($pt["glucose"]         ?? 90),
 *                   "Smoking_Status"      => (int)($pt["smoking"]           ?? 0),
 *                   "Physical_Activity"   => (string)($pt["physical_activity"] ?? "Medium"),
 *                   "Diet_Quality"        => (string)($pt["diet_quality"]   ?? "Average"),
 *                   "Alcohol_Consumption" => (int)($pt["alcohol"]           ?? 0),
 *                   "Sleep_Hours"         => (float)($pt["sleep_hours"]     ?? 7),
 *                   "Stress_Level"        => (float)($pt["stress_level"]    ?? 5),
 *                   "Family_History"      => (float)($pt["family_history"]  ?? 0),
 *                   "Medications_Count"   => (int)($pt["medications_count"] ?? 0),
 *                   "Fever"               => (int)($pt["fever"]             ?? 0),
 *                   "Cough"               => (int)($pt["cough"]             ?? 0),
 *                   "Fatigue"             => (int)($pt["fatigue"]           ?? 0),
 *                   "Chest_Pain"          => (int)($pt["chest_pain"]        ?? 0),
 *                   "Shortness_of_Breath" => (int)($pt["shortness_of_breath"] ?? 0),
 *                   "Headache"            => (int)($pt["headache"]          ?? 0),
 *                   "Month"               => $next_month_n,
 *               ];
 *           }, $patients);
 *           $live_forecast = $ef->getNextMonthForecast($patients_for_forecast);
 *       }
 *       // ─────────────────────────────────────────────────────────────────
 *
 *  3. Replace the static <!-- ================= EPIDEMIC ALERT SYSTEM ====== -->
 *     section with the HTML block below.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Severity → Bootstrap color mapping ───────────────────────────────────────
function severity_color(string $s): string {
    return match($s) {
        "critical" => "danger",
        "high"     => "warning",
        "medium"   => "info",
        default    => "secondary",
    };
}

function severity_icon(string $s): string {
    return match($s) {
        "critical" => "🔴",
        "high"     => "🟠",
        "medium"   => "🟡",
        default    => "🟢",
    };
}

// Month names array for display
$month_names = ["","Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

// ─────────────────────────────────────────────────────────────────────────────
// HTML OUTPUT — paste this into HospitalDashboard.php where the static
// epidemic section was.
// ─────────────────────────────────────────────────────────────────────────────
?>

<!-- ================= EPIDEMIC ALERT SYSTEM (ML-Powered) ================= -->
<div id="epidemic-alerts" class="anchor-offset mt-4">

  <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
    <h3 class="text-danger section-title mb-0">
      🏥 Hospital Epidemic Alert System
      <small class="text-muted" style="font-size:.65rem; font-weight:400;">
        AI-Powered Predictions
      </small>
    </h3>

    <?php if (!$api_alive): ?>
      <span class="badge badge-danger p-2">
        <i class="fas fa-exclamation-circle mr-1"></i> ML API Offline — Showing Cached Baseline
      </span>
    <?php else: ?>
      <span class="badge badge-success p-2">
        <i class="fas fa-check-circle mr-1"></i> ML Model Connected
      </span>
    <?php endif; ?>
  </div>

  <!-- ── Live Forecast Banner (next month) ─────────────────────────────── -->
  <?php
    // Determine the forecast source:
    // Priority 1 — live forecast from this month's patients (if available)
    // Priority 2 — historical baseline for next month
    $next_month_label = $month_names[$next_month_n] ?? "Next Month";
    $forecast_entry   = null;

    if (!empty($live_forecast) && ($live_forecast["status"] ?? "") === "ok") {
        $forecast_entry = $live_forecast;
        $forecast_entry["source"] = "live";
    } else {
        // Fall back to historical baseline for next month
        foreach ($calendar as $entry) {
            if ((int)($entry["month"] ?? 0) === $next_month_n) {
                $forecast_entry = $entry;
                $forecast_entry["source"] = "historical";
                break;
            }
        }
    }

    if ($forecast_entry):
        $disease   = e($forecast_entry["dominant_display"] ?? $forecast_entry["dominant_disease"] ?? "Unknown");
        $severity  = $forecast_entry["severity"] ?? "medium";
        $color     = severity_color($severity);
        $icon      = severity_icon($severity);
        $recs      = $forecast_entry["recommendations"] ?? [];
        $source    = $forecast_entry["source"] ?? "historical";
        $patients_count = $forecast_entry["total_patients"] ?? null;
  ?>
  <div class="card shadow mb-4 border-left-<?= $color ?>">
    <div class="card-body">
      <div class="d-flex align-items-start justify-content-between flex-wrap">
        <div>
          <h5 class="font-weight-bold text-<?= $color ?> mb-1">
            <?= $icon ?> <?= $next_month_label ?> Alert: <?= $disease ?>
          </h5>
          <p class="text-muted mb-2" style="font-size:.85rem;">
            <?php if ($source === "live" && $patients_count): ?>
              Based on <strong><?= (int)$patients_count ?></strong> registered patients this month &nbsp;·&nbsp;
            <?php else: ?>
              Historical baseline prediction &nbsp;·&nbsp;
            <?php endif; ?>
            Severity: <span class="badge badge-<?= $color ?>"><?= ucfirst($severity) ?></span>
          </p>
        </div>
        <span class="badge badge-<?= $color ?> badge-pill p-2" style="font-size:1rem;">
          <?= strtoupper($next_month_label) ?>
        </span>
      </div>

      <?php if (!empty($forecast_entry["distribution_pct"])): ?>
        <!-- Disease distribution bar chart -->
        <div class="mt-2 mb-3">
          <p class="mb-1" style="font-size:.8rem; font-weight:600; color:#555;">
            Predicted disease distribution for <?= $next_month_label ?>:
          </p>
          <?php foreach ($forecast_entry["distribution_pct"] as $dis => $pct): ?>
            <?php
              $dis_label = str_replace("_", " ", $dis);
              $pct_float = (float)$pct;
              $bar_color = ($dis === ($forecast_entry["dominant_disease"] ?? ""))
                           ? $color : "secondary";
            ?>
            <div class="d-flex align-items-center mb-1">
              <div style="min-width:180px; font-size:.78rem; color:#444;">
                <?= e($dis_label) ?>
              </div>
              <div class="progress flex-grow-1" style="height:14px; border-radius:6px;">
                <div class="progress-bar bg-<?= $bar_color ?>"
                     role="progressbar"
                     style="width:<?= min(100, $pct_float) ?>%"
                     aria-valuenow="<?= $pct_float ?>"
                     aria-valuemin="0" aria-valuemax="100">
                  <?= $pct_float ?>%
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($recs)): ?>
        <div class="alert alert-<?= $color ?> py-2 mb-0">
          <strong>🔧 Preparation Checklist:</strong>
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

  <!-- ── Full Year Calendar ─────────────────────────────────────────────── -->
  <?php if (!empty($calendar)): ?>
  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-calendar-alt mr-2"></i> Full-Year Epidemic Forecast Calendar
      </h6>
      <small class="text-muted">Historical AI Baseline</small>
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
              $m_name  = e($entry["month_name"] ?? "");
              $disease = e($entry["dominant_display"] ?? str_replace("_"," ",$entry["dominant_disease"] ?? ""));
              $sev     = $entry["severity"] ?? "medium";
              $col     = severity_color($sev);
              $ico     = severity_icon($sev);
              $rec1    = $entry["recommendations"][0] ?? "General preparedness recommended.";
              $is_next = ((int)($entry["month"] ?? 0) === $next_month_n);
            ?>
            <tr <?= $is_next ? 'class="table-warning font-weight-bold"' : '' ?>>
              <td>
                <?= $m_name ?>
                <?php if ($is_next): ?>
                  <span class="badge badge-warning" style="font-size:.65rem;">Next</span>
                <?php endif; ?>
              </td>
              <td><?= $ico ?> <?= $disease ?></td>
              <td>
                <span class="badge badge-<?= $col ?>"><?= ucfirst($sev) ?></span>
              </td>
              <td style="color:#555;"><?= e($rec1) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php elseif (!$api_alive): ?>
    <div class="alert alert-warning">
      <i class="fas fa-plug mr-2"></i>
      <strong>ML API is not running.</strong>
      Start the Flask server: <code>python app.py</code> &nbsp;(default port 5000).
      The epidemic alert section will populate automatically once the API is reachable.
    </div>
  <?php endif; ?>

</div>
<!-- ======================================================================= -->
 
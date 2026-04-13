<?php
// Variables from PatientController::viewRecord(): $rec, $patient_id, $record_id, $monthName, $catBadge
function e($v): string { return htmlspecialchars((string)($v??''), ENT_QUOTES, 'UTF-8'); }
function delta_class($val): string { if($val===null||$val==='') return 'text-secondary'; return ((float)$val>=0)?'text-success':'text-danger'; }
function delta_icon($val): string { if($val===null||$val==='') return ''; return ((float)$val>=0)?'<i class="fas fa-arrow-up mr-1"></i>':'<i class="fas fa-arrow-down mr-1"></i>'; }
function yn($val): string { return $val?'<span class="badge badge-warning">Yes</span>':'<span class="badge badge-secondary">No</span>'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>My Medical Record #<?= (int)$record_id ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fc; }

    .section-title {
      font-size: .82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #4e73df;
      border-bottom: 2px solid #e3e9ff;
      padding-bottom: 6px;
      margin: 1.5rem 0 1rem;
    }

    .label { font-weight: 600; color: #5a5c69; }

    .stat-card {
      background: #fff;
      border: 1px solid #e3e6f0;
      border-radius: 10px;
      padding: 14px 16px;
      margin-bottom: 12px;
    }
    .stat-card .stat-label {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #b7b9cc;
      margin-bottom: 4px;
    }
    .stat-card .stat-value {
      font-size: 20px;
      font-weight: 700;
      color: #2e2e3a;
    }
    .stat-card .stat-unit {
      font-size: 12px;
      color: #b7b9cc;
      margin-left: 4px;
    }

    .flag-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      margin: 4px;
    }
    .flag-active   { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
    .flag-inactive { background: #f0f0f0; color: #aaa;    border: 1px solid #ddd; text-decoration: line-through; }

    .delta-cell { font-weight: 700; font-size: 14px; }
    .diagnosis-box {
      border-left: 5px solid #4e73df;
      background: #f0f4ff;
      border-radius: 0 10px 10px 0;
      padding: 20px 24px;
    }
    .risk-meter {
      height: 10px;
      border-radius: 5px;
      background: #e9ecef;
      overflow: hidden;
      margin-top: 6px;
    }
    .risk-fill {
      height: 100%;
      border-radius: 5px;
      transition: width .4s;
    }
    .timeline-dot {
      width: 12px; height: 12px;
      border-radius: 50%;
      background: #4e73df;
      display: inline-block;
      margin-right: 8px;
    }
  </style>
</head>
<body>

<div class="container py-4">

  <!-- ── Back + Title ─────────────────────────────────────────────── -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <a href="<?= BASE_URL ?>/patient/dashboard#medical" class="btn btn-outline-secondary shadow-sm">
      <i class="fas fa-arrow-left mr-1"></i> Back to My Records
    </a>
    <span class="text-muted small">
      <i class="fas fa-calendar-check mr-1"></i> Generated on <?= date('d M Y') ?>
    </span>
  </div>

  <!-- ── Main Card ─────────────────────────────────────────────────── -->
  <div class="card shadow mb-4">

    <!-- Header -->
    <div class="card-header bg-primary text-white py-3">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0 font-weight-bold">
          <i class="fas fa-file-invoice-medical mr-2"></i>
          Medical Report &nbsp;#<?= (int)$record_id ?>
        </h5>
        <div class="mt-1 mt-md-0">
          <span class="badge badge-light p-2 mr-1">
            <i class="fas fa-tag mr-1"></i>
            <?= e($rec["diagnosis"] ?: "No diagnosis") ?>
          </span>
          <span class="badge badge-<?= $catBadge ?> p-2">
            <?= e($rec["disease_category"] ?: "Uncategorized") ?>
          </span>
        </div>
      </div>
    </div>

    <div class="card-body">

      <!-- ══ 1. ADMISSION ════════════════════════════════════════════ -->
      <p class="section-title"><i class="fas fa-hospital-alt mr-1"></i> 1. Admission Details</p>
      <div class="row">
        <div class="col-6 col-md-2">
          <div class="stat-card">
            <div class="stat-label">Age</div>
            <div class="stat-value"><?= e($rec["age"]) ?><span class="stat-unit">yrs</span></div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <div class="stat-card">
            <div class="stat-label">Stay Duration</div>
            <div class="stat-value"><?= e($rec["length_of_stay"]) ?><span class="stat-unit">days</span></div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <div class="stat-card">
            <div class="stat-label">Avg Stay</div>
            <div class="stat-value" style="font-size:15px;"><?= e(number_format((float)$rec["avg_length_stay"], 1)) ?><span class="stat-unit">days</span></div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <div class="stat-card">
            <div class="stat-label">Total Visits</div>
            <div class="stat-value"><?= e($rec["admission_count"]) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <div class="stat-card">
            <div class="stat-label">Check-in</div>
            <div class="stat-value" style="font-size:13px; padding-top:4px;"><?= e($rec["checkin_date"]) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <div class="stat-card">
            <div class="stat-label">Check-out</div>
            <div class="stat-value" style="font-size:13px; padding-top:4px;"><?= e($rec["checkout_date"]) ?></div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <p class="mb-0 text-muted small">
            <span class="timeline-dot"></span>
            Admitted on a <strong><?= e($rec["day_of_week"]) ?></strong>,
            <?= $monthName ?> <?= e($rec["year"]) ?>
          </p>
        </div>
      </div>

      <!-- ══ 2. LAB RESULTS ══════════════════════════════════════════ -->
      <p class="section-title"><i class="fas fa-vial mr-1"></i> 2. Laboratory Results</p>
      <div class="table-responsive">
        <table class="table table-bordered table-sm text-center mb-0">
          <thead class="thead-light">
            <tr>
              <th class="text-left">Test</th>
              <th>1st Reading</th>
              <th>2nd Reading</th>
              <th>Average</th>
              <th>Change (Δ)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="text-left font-weight-bold">Hemoglobin (HB)</td>
              <td><?= e($rec["cbc_hb1"]) ?></td>
              <td><?= e($rec["cbc_hb2"]) ?></td>
              <td class="text-primary"><?= e($rec["avg_hb"]) ?></td>
              <td class="delta-cell <?= delta_class($rec["delta_hb"]) ?>">
                <?= delta_icon($rec["delta_hb"]) ?><?= e($rec["delta_hb"]) ?>
              </td>
            </tr>
            <tr>
              <td class="text-left font-weight-bold">TLC (White Cells)</td>
              <td><?= e($rec["cbc_tlc1"]) ?></td>
              <td><?= e($rec["cbc_tlc2"]) ?></td>
              <td class="text-primary"><?= e($rec["avg_tlc"]) ?></td>
              <td class="delta-cell <?= delta_class($rec["delta_tlc"]) ?>">
                <?= delta_icon($rec["delta_tlc"]) ?><?= e($rec["delta_tlc"]) ?>
              </td>
            </tr>
            <tr>
              <td class="text-left font-weight-bold">Platelets</td>
              <td><?= e($rec["cbc_plat1"]) ?></td>
              <td><?= e($rec["cbc_plat2"]) ?></td>
              <td class="text-primary"><?= e($rec["avg_platelets"]) ?></td>
              <td class="delta-cell <?= delta_class($rec["delta_plat"]) ?>">
                <?= delta_icon($rec["delta_plat"]) ?><?= e($rec["delta_plat"]) ?>
              </td>
            </tr>
            <tr>
              <td class="text-left font-weight-bold">Blood Urea</td>
              <td><?= e($rec["blood_uria1"]) ?></td>
              <td><?= e($rec["blood_uria2"]) ?></td>
              <td class="text-primary"><?= e($rec["avg_urea"]) ?></td>
              <td class="delta-cell <?= delta_class($rec["delta_uria"]) ?>">
                <?= delta_icon($rec["delta_uria"]) ?><?= e($rec["delta_uria"]) ?>
              </td>
            </tr>
            <tr>
              <td class="text-left font-weight-bold">Creatinine</td>
              <td><?= e($rec["blood_creatinine1"]) ?></td>
              <td><?= e($rec["blood_creatinine2"]) ?></td>
              <td class="text-primary"><?= e($rec["avg_creatinine"]) ?></td>
              <td class="delta-cell <?= delta_class($rec["delta_creatinine"]) ?>">
                <?= delta_icon($rec["delta_creatinine"]) ?><?= e($rec["delta_creatinine"]) ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="text-muted small mt-2">
        <i class="fas fa-info-circle mr-1"></i>
        <span class="text-success font-weight-bold">Green / ↑</span> = value increased &nbsp;|&nbsp;
        <span class="text-danger font-weight-bold">Red / ↓</span> = value decreased between readings
      </p>

      <!-- ══ 3. VITALS ═══════════════════════════════════════════════ -->
      <p class="section-title"><i class="fas fa-heartbeat mr-1"></i> 3. Vital Signs</p>
      <div class="row">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">BMI</div>
            <div class="stat-value"><?= e(number_format((float)$rec["bmi"], 1)) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Blood Glucose</div>
            <div class="stat-value"><?= e($rec["glucose"]) ?><span class="stat-unit">mg/dL</span></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Systolic BP</div>
            <div class="stat-value"><?= e($rec["systolic_bp"]) ?><span class="stat-unit">mmHg</span></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Cholesterol</div>
            <div class="stat-value"><?= e($rec["cholesterol_level"]) ?><span class="stat-unit">mg/dL</span></div>
          </div>
        </div>
      </div>

      <!-- ══ 4. LIFESTYLE ════════════════════════════════════════════ -->
      <p class="section-title"><i class="fas fa-running mr-1"></i> 4. Lifestyle &amp; Wellness</p>
      <div class="row">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Physical Activity</div>
            <div class="stat-value" style="font-size:15px;"><?= e($rec["physical_activity_level"]) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Sleep</div>
            <div class="stat-value"><?= e($rec["sleep_hours"]) ?><span class="stat-unit">hrs</span></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Diet Quality</div>
            <div class="stat-value" style="font-size:15px;"><?= e($rec["diet_quality"]) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Smoking Status</div>
            <div class="stat-value" style="font-size:13px; padding-top:4px;"><?= e($rec["smoking_status"]) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Stress Level</div>
            <div class="stat-value"><?= e($rec["stress_level"]) ?><span class="stat-unit">/10</span></div>
            <div class="risk-meter">
              <div class="risk-fill" style="width:<?= min(100, (float)$rec["stress_level"] * 10) ?>%; background:#f6c23e;"></div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Alcohol</div>
            <div class="stat-value" style="font-size:15px; padding-top:4px;">
              <?= yn($rec["alcohol_consumption"]) ?>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Family History</div>
            <div class="stat-value" style="font-size:15px; padding-top:4px;">
              <?= yn($rec["family_history"]) ?>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-label">Medications</div>
            <div class="stat-value"><?= e((int)$rec["medications_count"]) ?><span class="stat-unit">active</span></div>
          </div>
        </div>
      </div>

      <!-- ══ 5. SYMPTOMS ════════════════════════════════════════════ -->
      <p class="section-title"><i class="fas fa-thermometer-half mr-1"></i> 5. Reported Symptoms</p>
      <div class="mb-3">
        <?php
        $syms = [
          "Fever"               => $rec["fever"],
          "Cough"               => $rec["cough"],
          "Fatigue"             => $rec["fatigue"],
          "Shortness of Breath" => $rec["shortness_of_breath"],
          "Chest Pain"          => $rec["chest_pain"],
          "Headache"            => $rec["headache"],
        ];
        $hasAny = false;
        foreach ($syms as $label => $val):
          if ($val > 0):
            $hasAny = true; ?>
            <span class="badge badge-pill badge-danger p-2 mr-2 mb-2" style="font-size:13px;">
              <i class="fas fa-exclamation-circle mr-1"></i><?= $label ?>
            </span>
          <?php endif;
        endforeach;
        if (!$hasAny): ?>
          <span class="text-success font-weight-bold">
            <i class="fas fa-check-circle mr-1"></i> No significant symptoms recorded.
          </span>
        <?php endif; ?>
      </div>

      <!-- ══ 6. MEDICAL HISTORY FLAGS ═══════════════════════════════ -->
      <p class="section-title"><i class="fas fa-clipboard-list mr-1"></i> 6. Known Medical Conditions</p>
      <div class="mb-3">
        <?php
        $flags = [
          "Diabetes"      => ["has_diabetes",      "fas fa-tint"],
          "Hypertension"  => ["has_hypertension",  "fas fa-heart"],
          "Kidney Disease"=> ["has_kidney_disease","fas fa-procedures"],
          "Heart Disease" => ["has_heart_disease", "fas fa-heartbeat"],
        ];
        foreach ($flags as $label => [$field, $icon]):
          $active = !empty($rec[$field]);
        ?>
          <span class="flag-badge <?= $active ? "flag-active" : "flag-inactive" ?>">
            <i class="<?= $icon ?>"></i> <?= $label ?>
            <?= $active ? '<i class="fas fa-check ml-1"></i>' : '' ?>
          </span>
        <?php endforeach; ?>
      </div>

      <!-- ══ 7. RISK SCORES ══════════════════════════════════════════ -->
      <p class="section-title"><i class="fas fa-chart-bar mr-1"></i> 7. Risk &amp; Predictive Indicators</p>
      <div class="row">
        <div class="col-md-4 mb-3">
          <div class="stat-card">
            <div class="stat-label">Overall Risk Score</div>
            <div class="stat-value text-danger"><?= e(number_format((float)$rec["risk_score"], 3)) ?></div>
            <div class="risk-meter mt-2">
              <?php $riskPct = min(100, (float)$rec["risk_score"] * 100); ?>
              <div class="risk-fill" style="width:<?= $riskPct ?>%;
                background: <?= $riskPct > 70 ? '#e74a3b' : ($riskPct > 40 ? '#f6c23e' : '#1cc88a') ?>;"></div>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="stat-card">
            <div class="stat-label">Symptom Burden</div>
            <div class="stat-value text-warning"><?= e(number_format((float)$rec["symptom_burden"], 3)) ?></div>
            <div class="risk-meter mt-2">
              <?php $burdenPct = min(100, (float)$rec["symptom_burden"] * 100); ?>
              <div class="risk-fill" style="width:<?= $burdenPct ?>%; background:#f6c23e;"></div>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="stat-card">
            <div class="stat-label">Seasonal Weight</div>
            <div class="stat-value text-info"><?= e(number_format((float)$rec["seasonal_weight"], 3)) ?></div>
          </div>
        </div>
      </div>

      <!-- ══ 8. DIAGNOSIS ════════════════════════════════════════════ -->
      <p class="section-title"><i class="fas fa-stethoscope mr-1"></i> 8. Diagnosis Summary</p>
      <div class="diagnosis-box">
        <div class="row align-items-center">
          <div class="col-md-8">
            <p class="text-muted small text-uppercase mb-1 font-weight-bold">Primary Diagnosis</p>
            <h4 class="font-weight-bold text-dark mb-1"><?= e($rec["diagnosis"] ?: "Not recorded") ?></h4>
            <span class="badge badge-<?= $catBadge ?> p-2" style="font-size:13px;">
              <?= e($rec["disease_category"] ?: "Uncategorized") ?>
            </span>
          </div>
          <div class="col-md-4 text-md-right mt-3 mt-md-0">
            <?php if (!empty($rec["has_diabetes"]) || !empty($rec["has_hypertension"]) ||
                       !empty($rec["has_kidney_disease"]) || !empty($rec["has_heart_disease"])): ?>
              <p class="text-muted small mb-1">Active risk flags</p>
              <?php if (!empty($rec["has_diabetes"])): ?>
                <span class="badge badge-warning mr-1 mb-1">Diabetes</span>
              <?php endif; ?>
              <?php if (!empty($rec["has_hypertension"])): ?>
                <span class="badge badge-danger mr-1 mb-1">Hypertension</span>
              <?php endif; ?>
              <?php if (!empty($rec["has_kidney_disease"])): ?>
                <span class="badge badge-info mr-1 mb-1">Kidney Disease</span>
              <?php endif; ?>
              <?php if (!empty($rec["has_heart_disease"])): ?>
                <span class="badge badge-primary mr-1 mb-1">Heart Disease</span>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-success small"><i class="fas fa-check-circle mr-1"></i>No active risk flags</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div><!-- /card-body -->

    <div class="card-footer text-center text-muted small py-3">
      <i class="fas fa-lock mr-1"></i>
      This report is private and generated for Patient ID #<?= (int)$patient_id ?> on <?= date('d M Y, H:i') ?>
    </div>

  </div><!-- /card -->

</div><!-- /container -->

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php if ($conn instanceof mysqli) { $conn->close(); } ?>
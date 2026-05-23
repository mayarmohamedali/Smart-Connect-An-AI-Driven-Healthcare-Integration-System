<?php
// Variables from HospitalController::addRecord()
// $patient, $insuranceName, $insuranceId, $success, $error, $patient_id
function e($v): string { return htmlspecialchars((string)($v??''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Medical Record</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    /* ── Brand chrome ─────────────────────────────────────── */
    .ins-badge {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 4px 10px; border-radius: 20px;
      border: 1px solid rgba(255,255,255,.5);
      background: rgba(255,255,255,.18);
      color: #fff; font-weight: 700; font-size: .85rem;
    }
    .section-title {
      font-size: .85rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .05em;
      color: #4e73df; margin-bottom: .75rem;
    }

    /* ── Calc preview ─────────────────────────────────────── */
    .calc-preview {
      background: #f0f4ff; border: 1px dashed #4e73df;
      border-radius: 8px; padding: 12px 16px; font-size: 13px;
    }
    .calc-preview .calc-row { display: flex; flex-wrap: wrap; gap: 16px; }
    .calc-item { min-width: 120px; }
    .calc-item .ci-label { color: #888; font-size: 11px; text-transform: uppercase; }
    .calc-item .ci-value { font-weight: 700; font-size: 15px; color: #2e2e3a; }
    .ci-pos { color: #1cc88a !important; }
    .ci-neg { color: #e74a3b !important; }

    /* ── Validation feedback ──────────────────────────────── */
    .field-error {
      display: none; font-size: 11.5px; color: #e74a3b;
      margin-top: 3px; font-weight: 600;
    }
    .field-error.visible { display: block; }
    .form-control.is-invalid { border-color: #e74a3b !important; background-image: none; }
    .form-control.is-valid   { border-color: #1cc88a !important; background-image: none; }

    /* ── 0–1 symptom selects ──────────────────────────────── */
    .score-select { appearance: none; -webkit-appearance: none; }
    .score-select option[value="0"] { color: #1cc88a; }
    .score-select option[value="1"] { color: #e74a3b; }

    /* ── Range hint badge ─────────────────────────────────── */
    .range-hint {
      font-size: 10.5px; color: #888;
      background: #f8f9fc; border: 1px solid #e3e6f0;
      border-radius: 4px; padding: 1px 5px;
      display: inline-block; margin-top: 2px;
    }
    .range-hint.warn { color: #f6c23e; border-color: #f6c23e; background: #fffdf0; }
    .range-hint.danger { color: #e74a3b; border-color: #e74a3b; background: #fff5f5; }

    /* ── Submit error summary ─────────────────────────────── */
    #validationSummary {
      display: none; margin-bottom: 16px;
      border-left: 4px solid #e74a3b;
      background: #fff5f5; padding: 10px 14px;
      border-radius: 6px; font-size: 13px; color: #c0392b;
    }
    #validationSummary strong { display: block; margin-bottom: 4px; }

    /* ── Auto-filled field style ──────────────────────────── */
    .auto-filled { background: #f0f4ff !important; }
  </style>
</head>
<body class="bg-light">

<div class="container py-4">
  <a href="<?= BASE_URL ?>/hospital/dashboard#patients" class="btn btn-outline-secondary mb-3">
    <i class="fas fa-arrow-left mr-1"></i> Back
  </a>

  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">
        <i class="fas fa-notes-medical mr-2"></i>
        Add Medical Record — <?= e($patient["full_name"]) ?>
      </h5>
      <small>
        National ID: <?= e($patient["national_id"]) ?>
        <?php if ($insuranceId > 0): ?>
          <span class="ins-badge ml-2">
            <i class="fas fa-shield-alt"></i>
            <?= e($insuranceName !== "" ? $insuranceName : ("Insurance #".$insuranceId)) ?>
          </span>
        <?php else: ?>
          <span class="badge badge-warning ml-2">No Insurance</span>
        <?php endif; ?>
      </small>
    </div>

    <div class="card-body">

      <?php if ($success): ?>
        <div class="alert alert-success">
          <?= e($success) ?><br>
          <small>Redirecting to dashboard...</small>
        </div>
        <script>
          setTimeout(function () { window.location.href = "<?= BASE_URL ?>/hospital/dashboard#patients"; }, 2000);
        </script>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <!-- Submit error summary (filled by JS) -->
      <div id="validationSummary">
        <strong><i class="fas fa-exclamation-triangle mr-1"></i> Please fix the following before saving:</strong>
        <ul id="summaryList" style="margin:0;padding-left:18px;"></ul>
      </div>

      <form method="POST" id="recordForm" novalidate
            data-national-id="<?= e($patient['national_id']) ?>">

        <!-- ══ 1. ADMISSION INFO ════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-calendar-alt mr-1"></i> 1. Admission Info</p>
        <div class="row">

          <div class="col-md-2 form-group">
            <label>Age <small class="text-muted">(auto)</small></label>
            <input type="number" name="age" id="age" class="form-control auto-filled"
                   readonly title="Auto-calculated from patient National ID">
          </div>

          <div class="col-md-3 form-group">
            <label>Check-in Date <span class="text-danger">*</span></label>
            <input type="date" name="checkin_date" class="form-control" id="checkin_date">
            <div class="field-error" id="err_checkin"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Check-out Date <span class="text-danger">*</span></label>
            <input type="date" name="checkout_date" class="form-control" id="checkout_date">
            <div class="field-error" id="err_checkout"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Length of Stay <small class="text-muted">(days)</small></label>
            <input type="number" name="length_of_stay" min="0" class="form-control auto-filled"
                   id="length_of_stay" readonly title="Auto-calculated from dates">
          </div>

          <!--
          <div class="col-md-2 form-group">
            <label>Avg Length of Stay</label>
            <input type="number" step="0.01" name="avg_length_stay" id="avg_length_stay"
                   min="0" max="365" class="form-control" placeholder="e.g. 5.5">
            <div class="field-error" id="err_avg_los"></div>
          </div>
      -->
          <div class="col-md-2 form-group">
            <label>Month <small class="text-muted">(1–12)</small></label>
            <input type="number" name="month" min="1" max="12" class="form-control auto-filled"
                   id="month" readonly title="Auto-filled from check-in date">
          </div>

          <div class="col-md-2 form-group">
            <label>Year</label>
            <input type="number" name="year" class="form-control auto-filled"
                   id="year" readonly title="Auto-filled from check-in date">
          </div>

          <div class="col-md-3 form-group">
            <label>Day of Week</label>
            <input type="text" name="day_of_week" class="form-control auto-filled"
                   id="day_of_week" readonly title="Auto-filled from check-in date">
          </div>

      <!--
          <div class="col-md-2 form-group">
            <label>Total Admission Count</label>
            <input type="number" name="admission_count" id="admission_count"
                   min="0" max="9999" class="form-control" placeholder="e.g. 3">
            <div class="field-error" id="err_admission_count"></div>
          </div>
-->
        </div>

        <hr>

        <!-- ══ 2. LAB RESULTS ══════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-vial mr-1"></i> 2. Lab Results — Round 1</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>CBC-HB1 <small class="text-muted">(g/dL)</small></label>
            <input type="number" step="0.01" name="cbc_hb1" id="cbc_hb1"
                   min="1" max="25" class="form-control lab-input" placeholder="1–25">
            <div class="field-error" id="err_cbc_hb1"></div>
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC1 <small class="text-muted">(×10³/µL)</small></label>
            <input type="number" step="0.01" name="cbc_tlc1" id="cbc_tlc1"
                   min="0.5" max="100" class="form-control lab-input" placeholder="0.5–100">
            <div class="field-error" id="err_cbc_tlc1"></div>
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT1 <small class="text-muted">(×10³/µL)</small></label>
            <input type="number" step="0.01" name="cbc_plat1" id="cbc_plat1"
                   min="5" max="1500" class="form-control lab-input" placeholder="5–1500">
            <div class="field-error" id="err_cbc_plat1"></div>
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 1 <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="blood_uria1" id="blood_uria1"
                   min="1" max="500" class="form-control lab-input" placeholder="1–500">
            <div class="field-error" id="err_blood_uria1"></div>
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 1 <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="blood_creatinine1" id="blood_creatinine1"
                   min="0.1" max="30" class="form-control lab-input" placeholder="0.1–30">
            <div class="field-error" id="err_blood_creatinine1"></div>
          </div>
        </div>

        <p class="section-title mt-1"><i class="fas fa-vial mr-1"></i> Lab Results — Round 2</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>CBC-HB2 <small class="text-muted">(g/dL)</small></label>
            <input type="number" step="0.01" name="cbc_hb2" id="cbc_hb2"
                   min="1" max="25" class="form-control lab-input" placeholder="1–25">
            <div class="field-error" id="err_cbc_hb2"></div>
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC2 <small class="text-muted">(×10³/µL)</small></label>
            <input type="number" step="0.01" name="cbc_tlc2" id="cbc_tlc2"
                   min="0.5" max="100" class="form-control lab-input" placeholder="0.5–100">
            <div class="field-error" id="err_cbc_tlc2"></div>
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT2 <small class="text-muted">(×10³/µL)</small></label>
            <input type="number" step="0.01" name="cbc_plat2" id="cbc_plat2"
                   min="5" max="1500" class="form-control lab-input" placeholder="5–1500">
            <div class="field-error" id="err_cbc_plat2"></div>
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 2 <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="blood_uria2" id="blood_uria2"
                   min="1" max="500" class="form-control lab-input" placeholder="1–500">
            <div class="field-error" id="err_blood_uria2"></div>
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 2 <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="blood_creatinine2" id="blood_creatinine2"
                   min="0.1" max="30" class="form-control lab-input" placeholder="0.1–30">
            <div class="field-error" id="err_blood_creatinine2"></div>
          </div>
        </div>

        <!-- Live preview of auto-calculated values -->
        <div class="calc-preview mb-3" id="calcPreview" style="display:none;">
          <div class="mb-2 font-weight-bold text-primary" style="font-size:12px;">
            <i class="fas fa-magic mr-1"></i> AUTO-CALCULATED FROM LAB VALUES
          </div>
          <div class="calc-row">
            <div class="calc-item"><div class="ci-label">Avg HB</div><div class="ci-value" id="prev_avg_hb">—</div></div>
            <div class="calc-item"><div class="ci-label">Avg TLC</div><div class="ci-value" id="prev_avg_tlc">—</div></div>
            <div class="calc-item"><div class="ci-label">Avg Platelets</div><div class="ci-value" id="prev_avg_plat">—</div></div>
            <div class="calc-item"><div class="ci-label">Avg Urea</div><div class="ci-value" id="prev_avg_urea">—</div></div>
            <div class="calc-item"><div class="ci-label">Avg Creatinine</div><div class="ci-value" id="prev_avg_crn">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ HB</div><div class="ci-value" id="prev_d_hb">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ TLC</div><div class="ci-value" id="prev_d_tlc">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ Platelets</div><div class="ci-value" id="prev_d_plat">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ Urea</div><div class="ci-value" id="prev_d_urea">—</div></div>
            <div class="calc-item"><div class="ci-label">Δ Creatinine</div><div class="ci-value" id="prev_d_crn">—</div></div>
          </div>
        </div>

        <hr>

        <!-- ══ 3. VITALS ════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-heartbeat mr-1"></i> 3. Vitals</p>
        <div class="row">

          <div class="col-md-3 form-group">
            <label>BMI</label>
            <input type="number" step="0.01" name="bmi" id="bmi"
                   min="10" max="70" class="form-control" placeholder="10–70">
            <div class="field-error" id="err_bmi"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Glucose <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="glucose" id="glucose"
                   min="20" max="600" class="form-control" placeholder="20–600">
            <div class="field-error" id="err_glucose"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Cholesterol <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="cholesterol_level" id="cholesterol_level"
                   min="50" max="500" class="form-control" placeholder="50–500">
            <div class="field-error" id="err_cholesterol"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Systolic BP <small class="text-muted">(mmHg)</small></label>
            <input type="number" step="1" name="systolic_bp" id="systolic_bp"
                   min="50" max="300" class="form-control" placeholder="50–300">
            <div class="field-error" id="err_systolic_bp"></div>
          </div>

        </div>

        <hr>

        <!-- ══ 4. LIFESTYLE ══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-running mr-1"></i> 4. Lifestyle</p>
        <div class="row">

          <div class="col-md-3 form-group">
            <label>Smoking Status</label>
            <select class="form-control" name="smoking_status" id="smoking_status">
              <option value="">— Select —</option>
              <option value="1">Smoker</option>
              <option value="0">Non-Smoker</option>
            </select>
          </div>

          <div class="col-md-3 form-group">
            <label>Physical Activity Level</label>
            <select class="form-control" name="physical_activity_level" id="physical_activity_level">
              <option value="">— Select —</option>
              <option value="Low">Low</option>
              <option value="Moderate">Moderate</option>
              <option value="High">High</option>
            </select>
          </div>

          <div class="col-md-3 form-group">
            <label>Diet Quality</label>
            <select class="form-control" name="diet_quality" id="diet_quality">
              <option value="">— Select —</option>
              <option value="Poor">Poor</option>
              <option value="Average">Average</option>
              <option value="Good">Good</option>
            </select>
          </div>

          <div class="col-md-3 form-group">
            <label>Sleep Hours</label>
            <input type="number" step="0.1" min="0" max="24" name="sleep_hours" id="sleep_hours"
                   class="form-control" placeholder="0–24">
            <div class="field-error" id="err_sleep"></div>
          </div>

          <div class="col-md-3 form-group">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="alcohol_consumption" value="1" id="alc">
              <label class="form-check-label" for="alc">Alcohol Consumption</label>
            </div>
          </div>

        </div>

        <hr>

        <!-- ══ 5. RISK SCORES ════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-chart-line mr-1"></i> 5. Risk Scores</p>
        <div class="row">

          <div class="col-md-2 form-group">
            <label>Stress Level <small class="text-muted">(0–10)</small></label>
            <input type="number" step="0.01" name="stress_level" id="stress_level"
                   min="0" max="10" class="form-control" placeholder="0–10">
            <div class="field-error" id="err_stress"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Family History</label>
            <select class="form-control" name="family_history" id="family_history">
              <option value="">— Select —</option>
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
            <div class="field-error" id="err_family_history"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Medications Count</label>
            <input type="number" step="1" name="medications_count" id="medications_count"
                   min="0" max="50" class="form-control" placeholder="0–50">
            <div class="field-error" id="err_medications"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Risk Score <small class="text-muted">(0–1)</small></label>
            <input type="number" step="0.000001" name="risk_score" id="risk_score"
                   min="0" max="1" class="form-control" placeholder="0.0–1.0">
            <div class="field-error" id="err_risk_score"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Symptom Burden <small class="text-muted">(0–1)</small></label>
            <input type="number" step="0.000001" name="symptom_burden" id="symptom_burden"
                   min="0" max="1" class="form-control" placeholder="0.0–1.0">
            <div class="field-error" id="err_symptom_burden"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Seasonal Weight <small class="text-muted">(0–1)</small></label>
            <input type="number" step="0.000001" name="seasonal_weight" id="seasonal_weight"
                   min="0" max="1" class="form-control" placeholder="0.0–1.0">
            <div class="field-error" id="err_seasonal_weight"></div>
          </div>

        </div>

        <hr>

        <!-- ══ 6. SYMPTOMS ═══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-thermometer-half mr-1"></i> 6. Symptoms</p>
        <div class="row">

          <div class="col-md-2 form-group">
            <label>Fever</label>
            <select class="form-control score-select" name="fever" id="fever">
              <option value="">— Select —</option>
              <option value="0">0 — Absent</option>
              <option value="1">1 — Present</option>
            </select>
            <div class="field-error" id="err_fever"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Cough</label>
            <select class="form-control score-select" name="cough" id="cough">
              <option value="">— Select —</option>
              <option value="0">0 — Absent</option>
              <option value="1">1 — Present</option>
            </select>
            <div class="field-error" id="err_cough"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Fatigue</label>
            <select class="form-control score-select" name="fatigue" id="fatigue">
              <option value="">— Select —</option>
              <option value="0">0 — Absent</option>
              <option value="1">1 — Present</option>
            </select>
            <div class="field-error" id="err_fatigue"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Shortness of Breath</label>
            <select class="form-control score-select" name="shortness_of_breath" id="shortness_of_breath">
              <option value="">— Select —</option>
              <option value="0">0 — Absent</option>
              <option value="1">1 — Present</option>
            </select>
            <div class="field-error" id="err_sob"></div>
          </div>

          <div class="col-md-3 form-group">
            <label class="d-block mb-2">Other Symptoms</label>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="chest_pain" value="1" id="cp">
              <label class="form-check-label" for="cp">Chest Pain</label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="headache" value="1" id="ha">
              <label class="form-check-label" for="ha">Headache</label>
            </div>
          </div>

        </div>

        <hr>

        <!-- ══ 7. MEDICAL HISTORY FLAGS ═════════════════════════════ -->
        <p class="section-title"><i class="fas fa-clipboard-check mr-1"></i> 7. Medical History Flags</p>
        <div class="row">
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_diabetes" value="1" id="d1">
              <label class="form-check-label" for="d1">Has Diabetes</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_hypertension" value="1" id="h1">
              <label class="form-check-label" for="h1">Has Hypertension</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_kidney_disease" value="1" id="k1">
              <label class="form-check-label" for="k1">Has Kidney Disease</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_heart_disease" value="1" id="c1">
              <label class="form-check-label" for="c1">Has Heart Disease</label>
            </div>
          </div>
        </div>

        <hr>

        <!-- ══ 8. DIAGNOSIS ══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-stethoscope mr-1"></i> 8. Diagnosis</p>
        <div class="row">

          <div class="col-md-6 form-group">
            <label>Primary Diagnosis <span class="text-danger">*</span></label>
            <input type="text" name="diagnosis" id="diagnosis" class="form-control"
                   placeholder="e.g. Diabetes, Hypertension, CKD" minlength="2" maxlength="200">
            <div class="field-error" id="err_diagnosis"></div>
          </div>

          <div class="col-md-6 form-group">
            <label>Disease Category <span class="text-danger">*</span></label>
            <select name="disease_category" id="disease_category" class="form-control">
              <option value="">— Select Disease —</option>
              <option value="Cancer">Cancer</option>
              <option value="Diabetes">Diabetes</option>
              <option value="Hypertension">Hypertension</option>
              <option value="Pneumonia">Pneumonia</option>
              <option value="Coronary Artery Disease">Coronary Artery Disease</option>
              <option value="Heart Failure">Heart Failure</option>
              <option value="Chronic Kidney Disease">Chronic Kidney Disease</option>
              <option value="Asthma">Asthma</option>
              <option value="Stroke">Stroke</option>
              <option value="Healthy">Healthy</option>
              <option value="Other">Other</option>
            </select>
            <div class="field-error" id="err_disease_category"></div>
          </div>

        </div>

        <div class="mt-4">
          <button class="btn btn-primary btn-block btn-lg" type="submit" id="submitBtn">
            <i class="fas fa-save mr-1"></i> Save Medical Record
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script>
// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────
const $ = id => document.getElementById(id);
const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

function setError(fieldId, msg) {
  const el = $(fieldId);
  if (!el) return;
  el.classList.remove('is-valid');
  el.classList.add('is-invalid');
  const errEl = $('err_' + fieldId);
  if (errEl) { errEl.textContent = msg; errEl.classList.add('visible'); }
}

function clearError(fieldId) {
  const el = $(fieldId);
  if (!el) return;
  el.classList.remove('is-invalid');
  el.classList.add('is-valid');
  const errEl = $('err_' + fieldId);
  if (errEl) { errEl.textContent = ''; errEl.classList.remove('visible'); }
}

function clearState(fieldId) {
  const el = $(fieldId);
  if (!el) return;
  el.classList.remove('is-invalid', 'is-valid');
  const errEl = $('err_' + fieldId);
  if (errEl) { errEl.textContent = ''; errEl.classList.remove('visible'); }
}

function numVal(id) {
  const v = parseFloat($(id)?.value);
  return isNaN(v) ? null : v;
}

// ─────────────────────────────────────────────────────────────────────────────
// DATE AUTO-FILL & CROSS-VALIDATION
// ─────────────────────────────────────────────────────────────────────────────
$('checkin_date').addEventListener('change', function () {
  const d = new Date(this.value);
  if (isNaN(d)) return;
  $('month').value       = d.getMonth() + 1;
  $('year').value        = d.getFullYear();
  $('day_of_week').value = days[d.getDay()];
  updateLOS();
  validateDates();
});

$('checkout_date').addEventListener('change', function () {
  updateLOS();
  validateDates();
});

function updateLOS() {
  const ci = $('checkin_date').value;
  const co = $('checkout_date').value;
  if (!ci || !co) return;
  const diff = Math.round((new Date(co) - new Date(ci)) / 86400000);
  if (diff >= 0) $('length_of_stay').value = diff;
  else $('length_of_stay').value = '';
}

function validateDates() {
  const today  = new Date(); today.setHours(0,0,0,0);
  const ciVal  = $('checkin_date').value;
  const coVal  = $('checkout_date').value;
  let ok = true;

  if (!ciVal) {
    setError('checkin_date', 'Check-in date is required.');
    ok = false;
  } else {
    const ci = new Date(ciVal);
    if (ci > today) {
      setError('checkin_date', 'Check-in date cannot be in the future.');
      ok = false;
    } else {
      clearError('checkin_date');
    }
  }

  if (ciVal && coVal) {
    const ci = new Date(ciVal), co = new Date(coVal);
    if (co < ci) {
      setError('checkout_date', 'Check-out date must be on or after check-in date.');
      ok = false;
    } else {
      clearError('checkout_date');
    }
  } else if (!coVal && ciVal) {
    clearState('checkout_date');
  }

  return ok;
}

// ─────────────────────────────────────────────────────────────────────────────
// LIVE RANGE HINTS (update badge color as user types)
// ─────────────────────────────────────────────────────────────────────────────
const rangeRules = {
  // id: [min_allowed, max_allowed, normal_low, normal_high]
  cbc_hb1:           [1,    25,   12,   17],
  cbc_hb2:           [1,    25,   12,   17],
  cbc_tlc1:          [0.5,  100,  4,    11],
  cbc_tlc2:          [0.5,  100,  4,    11],
  cbc_plat1:         [5,    1500, 150,  400],
  cbc_plat2:         [5,    1500, 150,  400],
  blood_uria1:       [1,    500,  7,    25],
  blood_uria2:       [1,    500,  7,    25],
  blood_creatinine1: [0.1,  30,   0.6,  1.2],
  blood_creatinine2: [0.1,  30,   0.6,  1.2],
  bmi:               [10,   70,   18.5, 24.9],
  glucose:           [20,   600,  70,   100],
  cholesterol_level: [50,   500,  0,    200],
  systolic_bp:       [50,   300,  90,   120],
  stress_level:      [0,    10,   0,    4],
  risk_score:        [0,    1,    0,    0.4],
  symptom_burden:    [0,    1,    0,    0.4],
  seasonal_weight:   [0,    1,    0,    1],
};

const hintMap = {
  cbc_hb1: 'hint_hb1', cbc_hb2: 'hint_hb2',
  cbc_tlc1: 'hint_tlc1', cbc_tlc2: 'hint_tlc2',
  cbc_plat1: 'hint_plat1', cbc_plat2: 'hint_plat2',
  blood_uria1: 'hint_urea1', blood_uria2: 'hint_urea2',
  blood_creatinine1: 'hint_crn1', blood_creatinine2: 'hint_crn2',
  bmi: 'hint_bmi', glucose: 'hint_glucose',
  cholesterol_level: 'hint_chol', systolic_bp: 'hint_bp',
};

function validateRangeField(id) {
  const rules = rangeRules[id];
  if (!rules) return true;
  const [minA, maxA, normLow, normHigh] = rules;
  const val = numVal(id);
  const errKey = id === 'cholesterol_level' ? 'cholesterol'
               : id === 'systolic_bp'       ? 'systolic_bp'
               : id === 'stress_level'      ? 'stress'
               : id === 'risk_score'        ? 'risk_score'
               : id === 'symptom_burden'    ? 'symptom_burden'
               : id === 'seasonal_weight'   ? 'seasonal_weight'
               : id;

  const hintId = hintMap[id];

  if (val === null) {
    clearState(id);
    if (hintId && $(hintId)) $(hintId).className = 'range-hint';
    return true; // optional — empty is OK
  }
  if (val < minA || val > maxA) {
    setError(id, `Must be between ${minA} and ${maxA}.`);
    if (hintId && $(hintId)) $(hintId).className = 'range-hint danger';
    return false;
  }
  // out of normal range → warning hint but not a hard error
  if (hintId && $(hintId)) {
    if (val < normLow || val > normHigh) {
      $(hintId).className = 'range-hint warn';
    } else {
      $(hintId).className = 'range-hint';
    }
  }
  clearError(id);
  return true;
}

Object.keys(rangeRules).forEach(id => {
  const el = $(id);
  if (el) {
    el.addEventListener('input', () => validateRangeField(id));
    el.addEventListener('blur',  () => validateRangeField(id));
  }
});

// ─────────────────────────────────────────────────────────────────────────────
// AGE — AUTO-CALCULATED FROM NATIONAL ID (Egyptian format: digits 2-7 = YYMMDD)
// ─────────────────────────────────────────────────────────────────────────────
(function () {
  const nid = document.getElementById('recordForm').dataset.nationalId || '';
  if (nid.length >= 7) {
    // Digit 1: century indicator (2 = 1900s, 3 = 2000s)
    const century = nid[0] === '3' ? 2000 : 1900;
    const yy  = parseInt(nid.substring(1, 3), 10);
    const mm  = parseInt(nid.substring(3, 5), 10) - 1; // JS months 0-based
    const dd  = parseInt(nid.substring(5, 7), 10);
    const dob = new Date(century + yy, mm, dd);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) age--;
    if (age >= 0 && age <= 120) {
      $('age').value = age;
    }
  }
})();

// ─────────────────────────────────────────────────────────────────────────────
// OTHER NUMERIC FIELDS
// ─────────────────────────────────────────────────────────────────────────────
function simpleMin(id, errKey, min, label) {
  const el = $(id);
  if (!el) return;
  el.addEventListener('blur', function () {
    if (this.value === '') { clearState(id); return; }
    const v = parseFloat(this.value);
    if (isNaN(v) || v < min) {
      setError(id, `${label} must be ≥ ${min}.`);
    } else {
      clearError(id);
    }
  });
}

simpleMin('avg_length_stay',   'avg_los',         0, 'Avg length of stay');
simpleMin('admission_count',   'admission_count', 0, 'Admission count');
simpleMin('medications_count', 'medications',     0, 'Medications count');

$('sleep_hours').addEventListener('blur', function () {
  if (this.value === '') { clearState('sleep_hours'); return; }
  const v = parseFloat(this.value);
  if (isNaN(v) || v < 0 || v > 24) {
    setError('sleep_hours', 'Sleep hours must be between 0 and 24.');
  } else {
    clearError('sleep_hours');
  }
});

// ─────────────────────────────────────────────────────────────────────────────
// DIAGNOSIS REQUIRED FIELDS
// ─────────────────────────────────────────────────────────────────────────────
$('diagnosis').addEventListener('blur', function () {
  if (this.value.trim().length < 2) {
    setError('diagnosis', 'Please enter a primary diagnosis (at least 2 characters).');
  } else {
    clearError('diagnosis');
  }
});

$('disease_category').addEventListener('change', function () {
  if (!this.value) {
    setError('disease_category', 'Please select a disease category.');
  } else {
    clearError('disease_category');
  }
});

// ─────────────────────────────────────────────────────────────────────────────
// CALC PREVIEW (averages & deltas from lab values)
// ─────────────────────────────────────────────────────────────────────────────
const labIds = ['cbc_hb1','cbc_hb2','cbc_tlc1','cbc_tlc2',
                'cbc_plat1','cbc_plat2','blood_uria1','blood_uria2',
                'blood_creatinine1','blood_creatinine2'];

labIds.forEach(id => {
  const el = $(id);
  if (el) el.addEventListener('input', updateCalcPreview);
});

function fmtN(val, el) {
  if (val === null) { el.textContent = '—'; el.className = 'ci-value'; return; }
  el.textContent = val.toFixed(3);
  el.className   = 'ci-value ' + (val > 0 ? 'ci-pos' : val < 0 ? 'ci-neg' : '');
}
function fmtAvg(val, el) {
  if (val === null) { el.textContent = '—'; el.className = 'ci-value'; return; }
  el.textContent = val.toFixed(3);
  el.className   = 'ci-value';
}
function calcAvg(a, b)   { return (a !== null && b !== null) ? Math.round((a+b)/2*1000)/1000 : null; }
function calcDelta(a, b) { return (a !== null && b !== null) ? Math.round((b-a)*1000)/1000   : null; }

function updateCalcPreview() {
  const hb1  = numVal('cbc_hb1'),           hb2  = numVal('cbc_hb2');
  const tlc1 = numVal('cbc_tlc1'),          tlc2 = numVal('cbc_tlc2');
  const pl1  = numVal('cbc_plat1'),         pl2  = numVal('cbc_plat2');
  const ur1  = numVal('blood_uria1'),       ur2  = numVal('blood_uria2');
  const cr1  = numVal('blood_creatinine1'), cr2  = numVal('blood_creatinine2');

  const anyFilled = [hb1,hb2,tlc1,tlc2,pl1,pl2,ur1,ur2,cr1,cr2].some(x => x !== null);
  $('calcPreview').style.display = anyFilled ? '' : 'none';

  fmtAvg(calcAvg(hb1,  hb2),  $('prev_avg_hb'));
  fmtAvg(calcAvg(tlc1, tlc2), $('prev_avg_tlc'));
  fmtAvg(calcAvg(pl1,  pl2),  $('prev_avg_plat'));
  fmtAvg(calcAvg(ur1,  ur2),  $('prev_avg_urea'));
  fmtAvg(calcAvg(cr1,  cr2),  $('prev_avg_crn'));
  fmtN(calcDelta(hb1,  hb2),  $('prev_d_hb'));
  fmtN(calcDelta(tlc1, tlc2), $('prev_d_tlc'));
  fmtN(calcDelta(pl1,  pl2),  $('prev_d_plat'));
  fmtN(calcDelta(ur1,  ur2),  $('prev_d_urea'));
  fmtN(calcDelta(cr1,  cr2),  $('prev_d_crn'));
}

// ─────────────────────────────────────────────────────────────────────────────
// SUBMIT GUARD — full validation sweep before POST
// ─────────────────────────────────────────────────────────────────────────────
$('recordForm').addEventListener('submit', function (e) {
  const errors = [];

  // Age is auto-calculated from National ID — no manual validation needed

  // 2. Required: check-in date
  if (!validateDates()) {
    errors.push('Admission dates');
  }

  // 3. Check-out required
  if (!$('checkout_date').value) {
    setError('checkout_date', 'Check-out date is required.');
    errors.push('Check-out date');
  }

  // 4. All range fields
  Object.keys(rangeRules).forEach(id => {
    if (!validateRangeField(id)) {
      errors.push(document.querySelector(`[name="${id}"]`)?.closest('.form-group')?.querySelector('label')?.textContent?.trim() || id);
    }
  });

  // 5. Required: diagnosis
  if ($('diagnosis').value.trim().length < 2) {
    setError('diagnosis', 'Primary diagnosis is required (at least 2 characters).');
    errors.push('Primary Diagnosis');
  }

  // 6. Required: disease category
  if (!$('disease_category').value) {
    setError('disease_category', 'Please select a disease category.');
    errors.push('Disease Category');
  }

  if (errors.length > 0) {
    e.preventDefault();
    const summary = $('validationSummary');
    const list    = $('summaryList');
    list.innerHTML = [...new Set(errors)].map(er => `<li>${er}</li>`).join('');
    summary.style.display = 'block';
    summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
</script>

</body>
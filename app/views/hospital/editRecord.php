<?php
// Variables from HospitalController::editRecord()
// $patient, $record, $patient_id, $record_id, $activityOptions, $dietOptions, $daysOfWeek, $error, $success
function e($v): string { return htmlspecialchars((string)($v??''), ENT_QUOTES, 'UTF-8'); }
function sel($current, $value): string { return ((string)$current===(string)$value)?'selected':''; }
function chk($val): string { return !empty($val)?'checked':''; }
function smokingSel($recordVal, $optionVal): string {
  if($recordVal===null||$recordVal==='') return '';
  return ((int)$recordVal===(int)$optionVal)?'selected':'';
}
// Pre-select symptom 0/1 selects from saved record
function symSel($recordVal, $optionVal): string {
  if($recordVal===null||$recordVal==='') return '';
  return ((string)(int)$recordVal===(string)$optionVal)?'selected':'';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Edit Medical Record #<?= (int)$record_id ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    .section-title {
      font-size: .82rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .05em;
      color: #4e73df; margin: 1.25rem 0 .6rem;
    }
    .calc-preview {
      background: #f0f4ff; border: 1px dashed #4e73df;
      border-radius: 8px; padding: 12px 16px; font-size: 13px;
    }
    .calc-preview .calc-row { display: flex; flex-wrap: wrap; gap: 16px; }
    .calc-item { min-width: 110px; }
    .calc-item .ci-label { color: #888; font-size: 11px; text-transform: uppercase; }
    .calc-item .ci-value { font-weight: 700; font-size: 15px; color: #2e2e3a; }
    .ci-pos { color: #1cc88a !important; }
    .ci-neg { color: #e74a3b !important; }

    /* ── Validation ───────────────────────────────────────── */
    .field-error {
      display: none; font-size: 11.5px;
      color: #e74a3b; margin-top: 3px; font-weight: 600;
    }
    .field-error.visible { display: block; }
    .form-control.is-invalid { border-color: #e74a3b !important; background-image: none; }
    .form-control.is-valid   { border-color: #1cc88a !important; background-image: none; }

    /* ── Submit error summary ─────────────────────────────── */
    #validationSummary {
      display: none; margin-bottom: 16px;
      border-left: 4px solid #e74a3b;
      background: #fff5f5; padding: 10px 14px;
      border-radius: 6px; font-size: 13px; color: #c0392b;
    }
    #validationSummary strong { display: block; margin-bottom: 4px; }
  </style>
</head>
<body class="bg-light">

<div class="container py-4">

  <!-- ── Page Header ─────────────────────────────────────────────── -->
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
      <h4 class="mb-0 text-primary">
        <i class="fas fa-edit mr-2"></i> Edit Record #<?= (int)$record_id ?>
      </h4>
      <small class="text-muted">
        Patient: <?= e($patient["full_name"]) ?> &nbsp;|&nbsp; ID: <?= e($patient["national_id"]) ?>
      </small>
    </div>
    <a href="<?= BASE_URL ?>/hospital/viewRecords?patient_id=<?= (int)$patient_id ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left mr-1"></i> Back to Records
    </a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle mr-2"></i> Record updated successfully!
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle mr-2"></i> <?= e($error) ?>
    </div>
  <?php endif; ?>

  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-notes-medical mr-2"></i> Medical Record Form</h5>
    </div>
    <div class="card-body">

      <!-- Submit error summary -->
      <div id="validationSummary">
        <strong><i class="fas fa-exclamation-triangle mr-1"></i> Please fix the following before saving:</strong>
        <ul id="summaryList" style="margin:0;padding-left:18px;"></ul>
      </div>

      <form method="POST" id="editForm" novalidate>
        <input type="hidden" name="action" value="update_record">

        <!-- ══ 1. ADMISSION INFO ════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-calendar-alt mr-1"></i> 1. Admission Info</p>
        <div class="row">

          <div class="col-md-2 form-group">
            <label>Age <span class="text-danger">*</span></label>
            <input type="number" name="age" id="age" min="0" max="120" class="form-control"
                   value="<?= e($record["age"]) ?>" placeholder="0–120">
            <div class="field-error" id="err_age"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Check-in Date <span class="text-danger">*</span></label>
            <input type="date" name="checkin_date" id="checkin_date" class="form-control"
                   value="<?= e($record["checkin_date"]) ?>">
            <div class="field-error" id="err_checkin_date"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Check-out Date <span class="text-danger">*</span></label>
            <input type="date" name="checkout_date" id="checkout_date" class="form-control"
                   value="<?= e($record["checkout_date"]) ?>">
            <div class="field-error" id="err_checkout_date"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Length of Stay <small class="text-muted">(days)</small></label>
            <input type="number" name="length_of_stay" id="length_of_stay" min="0" class="form-control"
                   value="<?= e($record["length_of_stay"]) ?>"
                   style="background:#f0f4ff;" readonly title="Auto-calculated from dates">
          </div>

  <!--
          <div class="col-md-2 form-group">
            <label>Avg Length of Stay</label>
            <input type="number" step="0.000001" name="avg_length_stay" id="avg_length_stay"
                   min="0" max="365" class="form-control"
                   value="<?= e($record["avg_length_stay"]) ?>">
            <div class="field-error" id="err_avg_length_stay"></div>
          </div>
-->

          <div class="col-md-2 form-group">
            <label>Month <small class="text-muted">(1–12)</small></label>
            <input type="number" name="month" id="month" min="1" max="12" class="form-control"
                   value="<?= e($record["month"]) ?>"
                   style="background:#f0f4ff;" readonly title="Auto-filled from check-in date">
          </div>

          <div class="col-md-2 form-group">
            <label>Year</label>
            <input type="number" name="year" id="year" min="2000" max="2100" class="form-control"
                   value="<?= e($record["year"]) ?>"
                   style="background:#f0f4ff;" readonly title="Auto-filled from check-in date">
          </div>

          <div class="col-md-3 form-group">
            <label>Day of Week</label>
            <select name="day_of_week" id="day_of_week" class="form-control">
              <option value="">— Select —</option>
              <?php foreach ($daysOfWeek as $d): ?>
                <option value="<?= $d ?>" <?= sel($record["day_of_week"], $d) ?>><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>

              <!--
          <div class="col-md-3 form-group">
            <label>Total Admission Count</label>
            <input type="number" name="admission_count" id="admission_count"
                   min="0" max="9999" class="form-control"
                   value="<?= e($record["admission_count"]) ?>">
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
                   min="1" max="25" class="form-control" value="<?= e($record["cbc_hb1"]) ?>">
            <div class="field-error" id="err_cbc_hb1"></div>
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC1 <small class="text-muted">(×10³/µL)</small></label>
            <input type="number" step="0.01" name="cbc_tlc1" id="cbc_tlc1"
                   min="0.5" max="100" class="form-control" value="<?= e($record["cbc_tlc1"]) ?>">
            <div class="field-error" id="err_cbc_tlc1"></div>
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT1 <small class="text-muted">(×10³/µL)</small></label>
            <input type="number" step="0.01" name="cbc_plat1" id="cbc_plat1"
                   min="5" max="1500" class="form-control" value="<?= e($record["cbc_plat1"]) ?>">
            <div class="field-error" id="err_cbc_plat1"></div>
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 1 <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="blood_uria1" id="blood_uria1"
                   min="1" max="500" class="form-control" value="<?= e($record["blood_uria1"]) ?>">
            <div class="field-error" id="err_blood_uria1"></div>
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 1 <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="blood_creatinine1" id="blood_creatinine1"
                   min="0.1" max="30" class="form-control" value="<?= e($record["blood_creatinine1"]) ?>">
            <div class="field-error" id="err_blood_creatinine1"></div>
          </div>
        </div>

        <p class="section-title"><i class="fas fa-vial mr-1"></i> Lab Results — Round 2</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>CBC-HB2 <small class="text-muted">(g/dL)</small></label>
            <input type="number" step="0.01" name="cbc_hb2" id="cbc_hb2"
                   min="1" max="25" class="form-control" value="<?= e($record["cbc_hb2"]) ?>">
            <div class="field-error" id="err_cbc_hb2"></div>
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC2 <small class="text-muted">(×10³/µL)</small></label>
            <input type="number" step="0.01" name="cbc_tlc2" id="cbc_tlc2"
                   min="0.5" max="100" class="form-control" value="<?= e($record["cbc_tlc2"]) ?>">
            <div class="field-error" id="err_cbc_tlc2"></div>
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT2 <small class="text-muted">(×10³/µL)</small></label>
            <input type="number" step="0.01" name="cbc_plat2" id="cbc_plat2"
                   min="5" max="1500" class="form-control" value="<?= e($record["cbc_plat2"]) ?>">
            <div class="field-error" id="err_cbc_plat2"></div>
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 2 <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="blood_uria2" id="blood_uria2"
                   min="1" max="500" class="form-control" value="<?= e($record["blood_uria2"]) ?>">
            <div class="field-error" id="err_blood_uria2"></div>
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 2 <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="blood_creatinine2" id="blood_creatinine2"
                   min="0.1" max="30" class="form-control" value="<?= e($record["blood_creatinine2"]) ?>">
            <div class="field-error" id="err_blood_creatinine2"></div>
          </div>
        </div>

        <!-- Live calc preview -->
        <div class="calc-preview mb-3" id="calcPreview">
          <div class="mb-2 font-weight-bold text-primary" style="font-size:12px;">
            <i class="fas fa-magic mr-1"></i> AUTO-CALCULATED FROM LAB VALUES
          </div>
          <div class="calc-row">
            <div class="calc-item"><div class="ci-label">Avg HB</div><div class="ci-value" id="prev_avg_hb"><?= e($record["avg_hb"]) ?: '—' ?></div></div>
            <div class="calc-item"><div class="ci-label">Avg TLC</div><div class="ci-value" id="prev_avg_tlc"><?= e($record["avg_tlc"]) ?: '—' ?></div></div>
            <div class="calc-item"><div class="ci-label">Avg Platelets</div><div class="ci-value" id="prev_avg_plat"><?= e($record["avg_platelets"]) ?: '—' ?></div></div>
            <div class="calc-item"><div class="ci-label">Avg Urea</div><div class="ci-value" id="prev_avg_urea"><?= e($record["avg_urea"]) ?: '—' ?></div></div>
            <div class="calc-item"><div class="ci-label">Avg Creatinine</div><div class="ci-value" id="prev_avg_crn"><?= e($record["avg_creatinine"]) ?: '—' ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ HB</div><div class="ci-value <?= ((float)($record["delta_hb"]??0))>=0?'ci-pos':'ci-neg' ?>" id="prev_d_hb"><?= e($record["delta_hb"]) ?: '—' ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ TLC</div><div class="ci-value <?= ((float)($record["delta_tlc"]??0))>=0?'ci-pos':'ci-neg' ?>" id="prev_d_tlc"><?= e($record["delta_tlc"]) ?: '—' ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ Platelets</div><div class="ci-value <?= ((float)($record["delta_plat"]??0))>=0?'ci-pos':'ci-neg' ?>" id="prev_d_plat"><?= e($record["delta_plat"]) ?: '—' ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ Urea</div><div class="ci-value <?= ((float)($record["delta_uria"]??0))>=0?'ci-pos':'ci-neg' ?>" id="prev_d_urea"><?= e($record["delta_uria"]) ?: '—' ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ Creatinine</div><div class="ci-value <?= ((float)($record["delta_creatinine"]??0))>=0?'ci-pos':'ci-neg' ?>" id="prev_d_crn"><?= e($record["delta_creatinine"]) ?: '—' ?></div></div>
          </div>
        </div>

        <hr>

        <!-- ══ 3. VITALS ═════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-heartbeat mr-1"></i> 3. Vitals</p>
        <div class="row">

          <div class="col-md-3 form-group">
            <label>BMI</label>
            <input type="number" step="0.01" name="bmi" id="bmi"
                   min="10" max="70" class="form-control" value="<?= e($record["bmi"]) ?>">
            <div class="field-error" id="err_bmi"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Glucose <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="glucose" id="glucose"
                   min="20" max="600" class="form-control" value="<?= e($record["glucose"]) ?>">
            <div class="field-error" id="err_glucose"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Cholesterol <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="cholesterol_level" id="cholesterol_level"
                   min="50" max="500" class="form-control" value="<?= e($record["cholesterol_level"]) ?>">
            <div class="field-error" id="err_cholesterol_level"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Systolic BP <small class="text-muted">(mmHg)</small></label>
            <input type="number" step="1" name="systolic_bp" id="systolic_bp"
                   min="50" max="300" class="form-control" value="<?= e($record["systolic_bp"]) ?>">
            <div class="field-error" id="err_systolic_bp"></div>
          </div>

        </div>

        <hr>

        <!-- ══ 4. LIFESTYLE ══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-running mr-1"></i> 4. Lifestyle</p>
        <div class="row">

          <div class="col-md-3 form-group">
            <label>Smoking Status</label>
            <select name="smoking_status" class="form-control">
              <option value="">— Select —</option>
              <option value="1" <?= smokingSel($record["smoking_status"], 1) ?>>Smoker</option>
              <option value="0" <?= smokingSel($record["smoking_status"], 0) ?>>Non-Smoker</option>
            </select>
          </div>

          <div class="col-md-3 form-group">
            <label>Physical Activity Level</label>
            <select name="physical_activity_level" class="form-control">
              <option value="">— Select —</option>
              <?php foreach ($activityOptions as $opt): ?>
                <option value="<?= e($opt) ?>" <?= sel($record["physical_activity_level"], $opt) ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3 form-group">
            <label>Diet Quality</label>
            <select name="diet_quality" class="form-control">
              <option value="">— Select —</option>
              <?php foreach ($dietOptions as $opt): ?>
                <option value="<?= e($opt) ?>" <?= sel($record["diet_quality"], $opt) ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3 form-group">
            <label>Sleep Hours</label>
            <input type="number" step="0.1" min="0" max="24" name="sleep_hours" id="sleep_hours"
                   class="form-control" value="<?= e($record["sleep_hours"]) ?>" placeholder="0–24">
            <div class="field-error" id="err_sleep_hours"></div>
          </div>

          <div class="col-md-3 form-group">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" name="alcohol_consumption" value="1"
                     id="alc" <?= chk($record["alcohol_consumption"]) ?>>
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
                   min="0" max="10" class="form-control" value="<?= e($record["stress_level"]) ?>">
            <div class="field-error" id="err_stress_level"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Family History</label>
            <select name="family_history" id="family_history" class="form-control">
              <option value="">— Select —</option>
              <option value="1" <?= symSel($record["family_history"], '1') ?>>Yes</option>
              <option value="0" <?= symSel($record["family_history"], '0') ?>>No</option>
            </select>
          </div>

          <div class="col-md-2 form-group">
            <label>Medications Count</label>
            <input type="number" step="1" name="medications_count" id="medications_count"
                   min="0" max="50" class="form-control" value="<?= e($record["medications_count"]) ?>">
            <div class="field-error" id="err_medications_count"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Risk Score <small class="text-muted">(0–1)</small></label>
            <input type="number" step="0.000001" name="risk_score" id="risk_score"
                   min="0" max="1" class="form-control" value="<?= e($record["risk_score"]) ?>">
            <div class="field-error" id="err_risk_score"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Symptom Burden <small class="text-muted">(0–1)</small></label>
            <input type="number" step="0.000001" name="symptom_burden" id="symptom_burden"
                   min="0" max="1" class="form-control" value="<?= e($record["symptom_burden"]) ?>">
            <div class="field-error" id="err_symptom_burden"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Seasonal Weight <small class="text-muted">(0–1)</small></label>
            <input type="number" step="0.000001" name="seasonal_weight" id="seasonal_weight"
                   min="0" max="1" class="form-control" value="<?= e($record["seasonal_weight"]) ?>">
            <div class="field-error" id="err_seasonal_weight"></div>
          </div>

        </div>

        <hr>

        <!-- ══ 6. SYMPTOMS ═══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-thermometer-half mr-1"></i> 6. Symptoms</p>
        <div class="row">

          <div class="col-md-2 form-group">
            <label>Fever</label>
            <select name="fever" id="fever" class="form-control">
              <option value="">— Select —</option>
              <option value="0" <?= symSel($record["fever"], '0') ?>>0 — Absent</option>
              <option value="1" <?= symSel($record["fever"], '1') ?>>1 — Present</option>
            </select>
            <div class="field-error" id="err_fever"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Cough</label>
            <select name="cough" id="cough" class="form-control">
              <option value="">— Select —</option>
              <option value="0" <?= symSel($record["cough"], '0') ?>>0 — Absent</option>
              <option value="1" <?= symSel($record["cough"], '1') ?>>1 — Present</option>
            </select>
            <div class="field-error" id="err_cough"></div>
          </div>

          <div class="col-md-2 form-group">
            <label>Fatigue</label>
            <select name="fatigue" id="fatigue" class="form-control">
              <option value="">— Select —</option>
              <option value="0" <?= symSel($record["fatigue"], '0') ?>>0 — Absent</option>
              <option value="1" <?= symSel($record["fatigue"], '1') ?>>1 — Present</option>
            </select>
            <div class="field-error" id="err_fatigue"></div>
          </div>

          <div class="col-md-3 form-group">
            <label>Shortness of Breath</label>
            <select name="shortness_of_breath" id="shortness_of_breath" class="form-control">
              <option value="">— Select —</option>
              <option value="0" <?= symSel($record["shortness_of_breath"], '0') ?>>0 — Absent</option>
              <option value="1" <?= symSel($record["shortness_of_breath"], '1') ?>>1 — Present</option>
            </select>
            <div class="field-error" id="err_shortness_of_breath"></div>
          </div>

          <div class="col-md-3 form-group">
            <label class="d-block mb-2">Other Symptoms</label>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="chest_pain" value="1"
                     id="cp" <?= chk($record["chest_pain"]) ?>>
              <label class="form-check-label" for="cp">Chest Pain</label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="headache" value="1"
                     id="ha" <?= chk($record["headache"]) ?>>
              <label class="form-check-label" for="ha">Headache</label>
            </div>
          </div>

        </div>

        <hr>

        <!-- ══ 7. MEDICAL HISTORY FLAGS ══════════════════════════════ -->
        <p class="section-title"><i class="fas fa-clipboard-check mr-1"></i> 7. Medical History Flags</p>
        <div class="row">
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_diabetes" value="1"
                     id="d1" <?= chk($record["has_diabetes"]) ?>>
              <label class="form-check-label" for="d1">Has Diabetes</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_hypertension" value="1"
                     id="h1" <?= chk($record["has_hypertension"]) ?>>
              <label class="form-check-label" for="h1">Has Hypertension</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_kidney_disease" value="1"
                     id="k1" <?= chk($record["has_kidney_disease"]) ?>>
              <label class="form-check-label" for="k1">Has Kidney Disease</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="has_heart_disease" value="1"
                     id="c1" <?= chk($record["has_heart_disease"]) ?>>
              <label class="form-check-label" for="c1">Has Heart Disease</label>
            </div>
          </div>
        </div>

        <hr>

        <!-- ══ 8. DIAGNOSIS ═════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-stethoscope mr-1"></i> 8. Diagnosis</p>
        <div class="row">

          <div class="col-md-6 form-group">
            <label>Primary Diagnosis <span class="text-danger">*</span></label>
            <input type="text" name="diagnosis" id="diagnosis" class="form-control"
                   value="<?= e($record["diagnosis"]) ?>"
                   placeholder="e.g. Diabetes, Hypertension, CKD"
                   minlength="2" maxlength="200">
            <div class="field-error" id="err_diagnosis"></div>
          </div>

          <div class="col-md-6 form-group">
            <label>Disease Category <span class="text-danger">*</span></label>
            <select name="disease_category" id="disease_category" class="form-control">
              <option value="">— Select Disease —</option>
              <?php
              $categories = ["Cancer","Diabetes","Hypertension","Pneumonia",
                             "Coronary Artery Disease","Heart Failure",
                             "Chronic Kidney Disease","Asthma","Stroke","Healthy","Other"];
              foreach ($categories as $cat):
              ?>
                <option value="<?= e($cat) ?>" <?= sel($record["disease_category"], $cat) ?>><?= e($cat) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="field-error" id="err_disease_category"></div>
          </div>

        </div>

        <!-- ── Submit ──────────────────────────────────────────────── -->
        <div class="mt-4">
          <button class="btn btn-primary btn-lg btn-block" type="submit" id="submitBtn">
            <i class="fas fa-save mr-1"></i> Save Changes
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sb-admin-2.min.js"></script>
<script>
// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────
const $id = id => document.getElementById(id);
const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

function setError(fieldId, msg) {
  const el = $id(fieldId);
  if (!el) return;
  el.classList.remove('is-valid');
  el.classList.add('is-invalid');
  const errEl = $id('err_' + fieldId);
  if (errEl) { errEl.textContent = msg; errEl.classList.add('visible'); }
}

function clearError(fieldId) {
  const el = $id(fieldId);
  if (!el) return;
  el.classList.remove('is-invalid');
  el.classList.add('is-valid');
  const errEl = $id('err_' + fieldId);
  if (errEl) { errEl.textContent = ''; errEl.classList.remove('visible'); }
}

function clearState(fieldId) {
  const el = $id(fieldId);
  if (!el) return;
  el.classList.remove('is-invalid', 'is-valid');
  const errEl = $id('err_' + fieldId);
  if (errEl) { errEl.textContent = ''; errEl.classList.remove('visible'); }
}

function numVal(id) {
  const v = parseFloat($id(id)?.value);
  return isNaN(v) ? null : v;
}

// ─────────────────────────────────────────────────────────────────────────────
// DATE AUTO-FILL & CROSS-VALIDATION
// ─────────────────────────────────────────────────────────────────────────────
$id('checkin_date').addEventListener('change', function () {
  const d = new Date(this.value);
  if (!isNaN(d)) {
    $id('month').value       = d.getMonth() + 1;
    $id('year').value        = d.getFullYear();
    // Sync day_of_week select
    const dayName = days[d.getDay()];
    const sel = $id('day_of_week');
    for (let i = 0; i < sel.options.length; i++) {
      if (sel.options[i].value === dayName) { sel.selectedIndex = i; break; }
    }
  }
  updateLOS();
  validateDates();
});

$id('checkout_date').addEventListener('change', function () {
  updateLOS();
  validateDates();
});

function updateLOS() {
  const ci = $id('checkin_date').value;
  const co = $id('checkout_date').value;
  if (!ci || !co) return;
  const diff = Math.round((new Date(co) - new Date(ci)) / 86400000);
  $id('length_of_stay').value = diff >= 0 ? diff : '';
}

function validateDates() {
  const today = new Date(); today.setHours(0,0,0,0);
  const ciVal = $id('checkin_date').value;
  const coVal = $id('checkout_date').value;
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
    if (new Date(coVal) < new Date(ciVal)) {
      setError('checkout_date', 'Check-out must be on or after check-in date.');
      ok = false;
    } else {
      clearError('checkout_date');
    }
  }

  return ok;
}

// ─────────────────────────────────────────────────────────────────────────────
// RANGE VALIDATION
// ─────────────────────────────────────────────────────────────────────────────
const rangeRules = {
  cbc_hb1:           [1,    25],
  cbc_hb2:           [1,    25],
  cbc_tlc1:          [0.5,  100],
  cbc_tlc2:          [0.5,  100],
  cbc_plat1:         [5,    1500],
  cbc_plat2:         [5,    1500],
  blood_uria1:       [1,    500],
  blood_uria2:       [1,    500],
  blood_creatinine1: [0.1,  30],
  blood_creatinine2: [0.1,  30],
  bmi:               [10,   70],
  glucose:           [20,   600],
  cholesterol_level: [50,   500],
  systolic_bp:       [50,   300],
  stress_level:      [0,    10],
  risk_score:        [0,    1],
  symptom_burden:    [0,    1],
  seasonal_weight:   [0,    1],
  avg_length_stay:   [0,    365],
  medications_count: [0,    50],
  sleep_hours:       [0,    24],
};

function validateRangeField(id) {
  const rules = rangeRules[id];
  if (!rules) return true;
  const [minA, maxA] = rules;
  const val = numVal(id);
  if (val === null) { clearState(id); return true; }
  if (val < minA || val > maxA) {
    setError(id, `Must be between ${minA} and ${maxA}.`);
    return false;
  }
  clearError(id);
  return true;
}

Object.keys(rangeRules).forEach(id => {
  const el = $id(id);
  if (el) {
    el.addEventListener('input', () => validateRangeField(id));
    el.addEventListener('blur',  () => validateRangeField(id));
  }
});

// ─────────────────────────────────────────────────────────────────────────────
// AGE
// ─────────────────────────────────────────────────────────────────────────────
$id('age').addEventListener('blur', function () {
  if (this.value === '') { clearState('age'); return; }
  const v = parseInt(this.value);
  if (isNaN(v) || v < 0 || v > 120) {
    setError('age', 'Age must be between 0 and 120.');
  } else {
    clearError('age');
  }
});

// ─────────────────────────────────────────────────────────────────────────────
// DIAGNOSIS
// ─────────────────────────────────────────────────────────────────────────────
$id('diagnosis').addEventListener('blur', function () {
  if (this.value.trim().length < 2) {
    setError('diagnosis', 'Please enter a primary diagnosis (at least 2 characters).');
  } else {
    clearError('diagnosis');
  }
});

$id('disease_category').addEventListener('change', function () {
  if (!this.value) {
    setError('disease_category', 'Please select a disease category.');
  } else {
    clearError('disease_category');
  }
});

// ─────────────────────────────────────────────────────────────────────────────
// CALC PREVIEW
// ─────────────────────────────────────────────────────────────────────────────
const labIds = ['cbc_hb1','cbc_hb2','cbc_tlc1','cbc_tlc2',
                'cbc_plat1','cbc_plat2','blood_uria1','blood_uria2',
                'blood_creatinine1','blood_creatinine2'];

labIds.forEach(id => {
  const el = $id(id);
  if (el) el.addEventListener('input', updateCalcPreview);
});

function fmtN(val, elId, isDelta) {
  const el = $id(elId);
  if (!el) return;
  if (val === null) { el.textContent = '—'; el.className = 'ci-value'; return; }
  el.textContent = val.toFixed(3);
  if (isDelta) el.className = 'ci-value ' + (val >= 0 ? 'ci-pos' : 'ci-neg');
  else el.className = 'ci-value';
}
function calcAvg(a, b)   { return (a !== null && b !== null) ? Math.round((a+b)/2*1000)/1000 : null; }
function calcDelta(a, b) { return (a !== null && b !== null) ? Math.round((b-a)*1000)/1000   : null; }

function updateCalcPreview() {
  const hb1=numVal('cbc_hb1'), hb2=numVal('cbc_hb2');
  const tl1=numVal('cbc_tlc1'), tl2=numVal('cbc_tlc2');
  const pl1=numVal('cbc_plat1'), pl2=numVal('cbc_plat2');
  const ur1=numVal('blood_uria1'), ur2=numVal('blood_uria2');
  const cr1=numVal('blood_creatinine1'), cr2=numVal('blood_creatinine2');

  fmtN(calcAvg(hb1,hb2),   'prev_avg_hb',   false);
  fmtN(calcAvg(tl1,tl2),   'prev_avg_tlc',  false);
  fmtN(calcAvg(pl1,pl2),   'prev_avg_plat', false);
  fmtN(calcAvg(ur1,ur2),   'prev_avg_urea', false);
  fmtN(calcAvg(cr1,cr2),   'prev_avg_crn',  false);
  fmtN(calcDelta(hb1,hb2), 'prev_d_hb',     true);
  fmtN(calcDelta(tl1,tl2), 'prev_d_tlc',    true);
  fmtN(calcDelta(pl1,pl2), 'prev_d_plat',   true);
  fmtN(calcDelta(ur1,ur2), 'prev_d_urea',   true);
  fmtN(calcDelta(cr1,cr2), 'prev_d_crn',    true);
}

// ─────────────────────────────────────────────────────────────────────────────
// SUBMIT GUARD
// ─────────────────────────────────────────────────────────────────────────────
$id('editForm').addEventListener('submit', function (e) {
  const errors = [];

  // Age
  const age = parseInt($id('age').value);
  if ($id('age').value === '' || isNaN(age) || age < 0 || age > 120) {
    setError('age', 'Age is required and must be between 0 and 120.');
    errors.push('Age');
  }

  // Dates
  if (!validateDates()) errors.push('Admission dates');
  if (!$id('checkout_date').value) {
    setError('checkout_date', 'Check-out date is required.');
    errors.push('Check-out date');
  }

  // All range fields
  Object.keys(rangeRules).forEach(id => {
    if (!validateRangeField(id)) {
      const label = $id(id)?.closest('.form-group')?.querySelector('label')?.textContent?.trim() || id;
      errors.push(label);
    }
  });

  // Diagnosis
  if ($id('diagnosis').value.trim().length < 2) {
    setError('diagnosis', 'Primary diagnosis is required (at least 2 characters).');
    errors.push('Primary Diagnosis');
  }

  // Disease category
  if (!$id('disease_category').value) {
    setError('disease_category', 'Please select a disease category.');
    errors.push('Disease Category');
  }

  if (errors.length > 0) {
    e.preventDefault();
    const summary = $id('validationSummary');
    const list    = $id('summaryList');
    list.innerHTML = [...new Set(errors)].map(er => `<li>${er}</li>`).join('');
    summary.style.display = 'block';
    summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
</script>

</body>
</html>
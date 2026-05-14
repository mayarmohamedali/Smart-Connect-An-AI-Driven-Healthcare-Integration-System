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
      font-size: .82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: #4e73df;
      margin: 1.25rem 0 .6rem;
    }
    .calc-preview {
      background: #f0f4ff;
      border: 1px dashed #4e73df;
      border-radius: 8px;
      padding: 12px 16px;
      font-size: 13px;
    }
    .calc-preview .calc-row { display: flex; flex-wrap: wrap; gap: 16px; }
    .calc-item { min-width: 110px; }
    .calc-item .ci-label { color: #888; font-size: 11px; text-transform: uppercase; }
    .calc-item .ci-value { font-weight: 700; font-size: 15px; color: #2e2e3a; }
    .ci-pos { color: #1cc88a !important; }
    .ci-neg { color: #e74a3b !important; }
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

      <form method="POST">
        <input type="hidden" name="action" value="update_record">

        <!-- ══ 1. ADMISSION INFO ════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-calendar-alt mr-1"></i> 1. Admission Info</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>Age</label>
            <input type="number" name="age" min="0" max="150" class="form-control"
                   value="<?= e($record["age"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-in Date</label>
            <input type="date" name="checkin_date" class="form-control"
                   value="<?= e($record["checkin_date"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-out Date</label>
            <input type="date" name="checkout_date" class="form-control"
                   value="<?= e($record["checkout_date"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Length of Stay (days)</label>
            <input type="number" name="length_of_stay" min="0" class="form-control"
                   value="<?= e($record["length_of_stay"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Avg Length of Stay</label>
            <input type="number" step="0.000001" name="avg_length_stay" class="form-control"
                   value="<?= e($record["avg_length_stay"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Month (1–12)</label>
            <input type="number" name="month" min="1" max="12" class="form-control"
                   value="<?= e($record["month"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Year</label>
            <input type="number" name="year" min="2000" max="2100" class="form-control"
                   value="<?= e($record["year"]) ?>" placeholder="e.g. 2024">
          </div>
          <div class="col-md-3 form-group">
            <label>Day of Week</label>
            <select name="day_of_week" class="form-control">
              <option value="">— Select —</option>
              <?php foreach ($daysOfWeek as $d): ?>
                <option value="<?= $d ?>" <?= sel($record["day_of_week"], $d) ?>><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Total Admission Count</label>
            <input type="number" name="admission_count" min="0" class="form-control"
                   value="<?= e($record["admission_count"]) ?>">
          </div>
        </div>

        <hr>

        <!-- ══ 2. LAB RESULTS ══════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-vial mr-1"></i> 2. Lab Results — Round 1</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>CBC-HB1</label>
            <input type="number" step="0.01" name="cbc_hb1" class="form-control" value="<?= e($record["cbc_hb1"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC1</label>
            <input type="number" step="0.01" name="cbc_tlc1" class="form-control" value="<?= e($record["cbc_tlc1"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT1</label>
            <input type="number" step="0.01" name="cbc_plat1" class="form-control" value="<?= e($record["cbc_plat1"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 1</label>
            <input type="number" step="0.01" name="blood_uria1" class="form-control" value="<?= e($record["blood_uria1"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 1</label>
            <input type="number" step="0.01" name="blood_creatinine1" class="form-control" value="<?= e($record["blood_creatinine1"]) ?>">
          </div>
        </div>

        <p class="section-title"><i class="fas fa-vial mr-1"></i> Lab Results — Round 2</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>CBC-HB2</label>
            <input type="number" step="0.01" name="cbc_hb2" class="form-control" value="<?= e($record["cbc_hb2"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC2</label>
            <input type="number" step="0.01" name="cbc_tlc2" class="form-control" value="<?= e($record["cbc_tlc2"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT2</label>
            <input type="number" step="0.01" name="cbc_plat2" class="form-control" value="<?= e($record["cbc_plat2"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 2</label>
            <input type="number" step="0.01" name="blood_uria2" class="form-control" value="<?= e($record["blood_uria2"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 2</label>
            <input type="number" step="0.01" name="blood_creatinine2" class="form-control" value="<?= e($record["blood_creatinine2"]) ?>">
          </div>
        </div>

        <!-- Live preview of auto-calculated averages & deltas -->
        <div class="calc-preview mb-3" id="calcPreview">
          <div class="mb-2 font-weight-bold text-primary" style="font-size:12px;">
            <i class="fas fa-magic mr-1"></i> AUTO-CALCULATED FROM LAB VALUES (saved automatically — not editable)
          </div>
          <div class="calc-row">
            <div class="calc-item"><div class="ci-label">Avg HB</div><div class="ci-value" id="prev_avg_hb"><?= e($record["avg_hb"]) ?></div></div>
            <div class="calc-item"><div class="ci-label">Avg TLC</div><div class="ci-value" id="prev_avg_tlc"><?= e($record["avg_tlc"]) ?></div></div>
            <div class="calc-item"><div class="ci-label">Avg Platelets</div><div class="ci-value" id="prev_avg_plat"><?= e($record["avg_platelets"]) ?></div></div>
            <div class="calc-item"><div class="ci-label">Avg Urea</div><div class="ci-value" id="prev_avg_urea"><?= e($record["avg_urea"]) ?></div></div>
            <div class="calc-item"><div class="ci-label">Avg Creatinine</div><div class="ci-value" id="prev_avg_crn"><?= e($record["avg_creatinine"]) ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ HB</div><div class="ci-value <?= ((float)($record["delta_hb"] ?? 0) >= 0) ? 'ci-pos' : 'ci-neg' ?>" id="prev_d_hb"><?= e($record["delta_hb"]) ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ TLC</div><div class="ci-value <?= ((float)($record["delta_tlc"] ?? 0) >= 0) ? 'ci-pos' : 'ci-neg' ?>" id="prev_d_tlc"><?= e($record["delta_tlc"]) ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ Platelets</div><div class="ci-value <?= ((float)($record["delta_plat"] ?? 0) >= 0) ? 'ci-pos' : 'ci-neg' ?>" id="prev_d_plat"><?= e($record["delta_plat"]) ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ Urea</div><div class="ci-value <?= ((float)($record["delta_uria"] ?? 0) >= 0) ? 'ci-pos' : 'ci-neg' ?>" id="prev_d_urea"><?= e($record["delta_uria"]) ?></div></div>
            <div class="calc-item"><div class="ci-label">Δ Creatinine</div><div class="ci-value <?= ((float)($record["delta_creatinine"] ?? 0) >= 0) ? 'ci-pos' : 'ci-neg' ?>" id="prev_d_crn"><?= e($record["delta_creatinine"]) ?></div></div>
          </div>
        </div>

        <hr>

        <!-- ══ 3. VITALS ═════════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-heartbeat mr-1"></i> 3. Vitals</p>
        <div class="row">
          <div class="col-md-3 form-group">
            <label>BMI</label>
            <input type="number" step="0.01" name="bmi" class="form-control" value="<?= e($record["bmi"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Glucose</label>
            <input type="number" step="0.01" name="glucose" class="form-control" value="<?= e($record["glucose"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Cholesterol Level</label>
            <input type="number" step="0.01" name="cholesterol_level" class="form-control" value="<?= e($record["cholesterol_level"]) ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Pressure <small class="text-muted">(mmHg)</small></label>
            <div class="input-group">
              <input type="number" name="systolic_bp" class="form-control" placeholder="Systolic"
                     min="0" value="<?= e($record["systolic_bp"]) ?>">
              <div class="input-group-prepend input-group-append">
               
              </div>
              
            </div>
          
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
            <input type="number" step="0.1" min="0" max="24" name="sleep_hours" class="form-control"
                   value="<?= e($record["sleep_hours"]) ?>" placeholder="e.g. 7.5">
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
            <label>Stress Level</label>
            <input type="number" step="0.01" name="stress_level" class="form-control" value="<?= e($record["stress_level"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Family History</label>
            <input type="number" step="0.0001" name="family_history" class="form-control" value="<?= e($record["family_history"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Medications Count</label>
            <input type="number" step="0.01" name="medications_count" class="form-control" value="<?= e($record["medications_count"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Risk Score</label>
            <input type="number" step="0.000001" name="risk_score" class="form-control" value="<?= e($record["risk_score"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Symptom Burden</label>
            <input type="number" step="0.000001" name="symptom_burden" class="form-control" value="<?= e($record["symptom_burden"]) ?>">
          </div>
          <div class="col-md-2 form-group">
            <label>Seasonal Weight</label>
            <input type="number" step="0.000001" name="seasonal_weight" class="form-control" value="<?= e($record["seasonal_weight"]) ?>">
          </div>
        </div>

        <hr>

        <!-- ══ 6. SYMPTOMS ═══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-thermometer-half mr-1"></i> 6. Symptoms</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>Fever Score</label>
            <input type="number" step="0.0001" name="fever" class="form-control"
                   value="<?= e($record["fever"]) ?>" placeholder="0–1">
          </div>
          <div class="col-md-2 form-group">
            <label>Cough Score</label>
            <input type="number" step="0.0001" name="cough" class="form-control"
                   value="<?= e($record["cough"]) ?>" placeholder="0–1">
          </div>
          <div class="col-md-2 form-group">
            <label>Fatigue Score</label>
            <input type="number" step="0.0001" name="fatigue" class="form-control"
                   value="<?= e($record["fatigue"]) ?>" placeholder="0–1">
          </div>
          <div class="col-md-3 form-group">
            <label>Shortness of Breath</label>
            <input type="number" step="0.0001" name="shortness_of_breath" class="form-control"
                   value="<?= e($record["shortness_of_breath"]) ?>" placeholder="0–1">
          </div>
          <div class="col-md-3 form-group">
            <div class="form-check mt-4">
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
            <label>Primary Diagnosis</label>
            <input type="text" name="diagnosis" class="form-control"
                   value="<?= e($record["diagnosis"]) ?>"
                   placeholder="e.g. Diabetes, Hypertension, CKD">
          </div>
          <div class="col-md-6 form-group">
            <label>Disease Category</label>
            <select name="disease_category" class="form-control">
              <option value="">— Select Category —</option>
              <?php
              $categories = ["Cardiovascular","Oncological","Metabolic","Respiratory","Renal","Neurological","Healthy","Other"];
              foreach ($categories as $cat):
              ?>
                <option value="<?= e($cat) ?>" <?= sel($record["disease_category"], $cat) ?>><?= e($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- ── Submit ──────────────────────────────────────────────── -->
        <div class="mt-4">
          <button class="btn btn-primary btn-lg btn-block" type="submit">
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
const labIds = ['cbc_hb1','cbc_hb2','cbc_tlc1','cbc_tlc2',
                'cbc_plat1','cbc_plat2','blood_uria1','blood_uria2',
                'blood_creatinine1','blood_creatinine2'];

labIds.forEach(id => {
  const el = document.querySelector('[name="'+id+'"]');
  if (el) el.addEventListener('input', updateCalcPreview);
});

function v(name) {
  const el = document.querySelector('[name="'+name+'"]');
  if (!el) return null;
  const val = parseFloat(el.value);
  return isNaN(val) ? null : val;
}

function fmt(val, elId, isDelta) {
  const el = document.getElementById(elId);
  if (!el) return;
  if (val === null) { el.textContent = '—'; el.className = 'ci-value'; return; }
  el.textContent = val.toFixed(3);
  if (isDelta) el.className = 'ci-value ' + (val >= 0 ? 'ci-pos' : 'ci-neg');
  else el.className = 'ci-value';
}

function calcAvg(a, b)   { return (a !== null && b !== null) ? Math.round((a+b)/2*1000)/1000 : null; }
function calcDelta(a, b) { return (a !== null && b !== null) ? Math.round((b-a)*1000)/1000   : null; }

function updateCalcPreview() {
  const hb1=v('cbc_hb1'), hb2=v('cbc_hb2');
  const tl1=v('cbc_tlc1'), tl2=v('cbc_tlc2');
  const pl1=v('cbc_plat1'), pl2=v('cbc_plat2');
  const ur1=v('blood_uria1'), ur2=v('blood_uria2');
  const cr1=v('blood_creatinine1'), cr2=v('blood_creatinine2');

  fmt(calcAvg(hb1,hb2),   'prev_avg_hb',   false);
  fmt(calcAvg(tl1,tl2),   'prev_avg_tlc',  false);
  fmt(calcAvg(pl1,pl2),   'prev_avg_plat', false);
  fmt(calcAvg(ur1,ur2),   'prev_avg_urea', false);
  fmt(calcAvg(cr1,cr2),   'prev_avg_crn',  false);
  fmt(calcDelta(hb1,hb2), 'prev_d_hb',     true);
  fmt(calcDelta(tl1,tl2), 'prev_d_tlc',    true);
  fmt(calcDelta(pl1,pl2), 'prev_d_plat',   true);
  fmt(calcDelta(ur1,ur2), 'prev_d_urea',   true);
  fmt(calcDelta(cr1,cr2), 'prev_d_crn',    true);
}
</script>

</body>
</html>
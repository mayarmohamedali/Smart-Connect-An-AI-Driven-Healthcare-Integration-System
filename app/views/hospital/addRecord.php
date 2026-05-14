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
    .calc-preview {
      background: #f0f4ff;
      border: 1px dashed #4e73df;
      border-radius: 8px;
      padding: 12px 16px;
      font-size: 13px;
    }
    .calc-preview .calc-row { display: flex; flex-wrap: wrap; gap: 16px; }
    .calc-item { min-width: 120px; }
    .calc-item .ci-label { color: #888; font-size: 11px; text-transform: uppercase; }
    .calc-item .ci-value { font-weight: 700; font-size: 15px; color: #2e2e3a; }
    .ci-pos { color: #1cc88a !important; }
    .ci-neg { color: #e74a3b !important; }
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

      <form method="POST" id="recordForm">

        <!-- ══ 1. ADMISSION INFO ════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-calendar-alt mr-1"></i> 1. Admission Info</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>Age</label>
            <input type="number" name="age" min="0" max="150" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-in Date</label>
            <input type="date" name="checkin_date" class="form-control" id="checkin_date">
          </div>
          <div class="col-md-3 form-group">
            <label>Check-out Date</label>
            <input type="date" name="checkout_date" class="form-control" id="checkout_date">
          </div>
          <div class="col-md-2 form-group">
            <label>Length of Stay <small class="text-muted">(days)</small></label>
            <input type="number" name="length_of_stay" min="0" class="form-control" id="length_of_stay" readonly
                   style="background:#f0f4ff;" title="Auto-calculated from dates">
          </div>
          <div class="col-md-2 form-group">
            <label>Avg Length of Stay</label>
            <input type="number" step="0.01" name="avg_length_stay" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Month <small class="text-muted">(1–12)</small></label>
            <input type="number" name="month" min="1" max="12" class="form-control" id="month" readonly
                   style="background:#f0f4ff;" title="Auto-filled from check-in date">
          </div>
          <div class="col-md-2 form-group">
            <label>Year</label>
            <input type="number" name="year" class="form-control" id="year" readonly
                   style="background:#f0f4ff;" title="Auto-filled from check-in date">
          </div>
          <div class="col-md-3 form-group">
            <label>Day of Week</label>
            <input type="text" name="day_of_week" class="form-control" id="day_of_week" readonly
                   style="background:#f0f4ff;" title="Auto-filled from check-in date">
          </div>
          <div class="col-md-2 form-group">
            <label>Total Admission Count</label>
            <input type="number" name="admission_count" min="0" class="form-control">
          </div>
        </div>

        <hr>

        <!-- ══ 2. LAB RESULTS ══════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-vial mr-1"></i> 2. Lab Results — Round 1</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>CBC-HB1</label>
            <input type="number" step="0.01" name="cbc_hb1" id="cbc_hb1" class="form-control lab-input">
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC1</label>
            <input type="number" step="0.01" name="cbc_tlc1" id="cbc_tlc1" class="form-control lab-input">
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT1</label>
            <input type="number" step="0.01" name="cbc_plat1" id="cbc_plat1" class="form-control lab-input">
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 1</label>
            <input type="number" step="0.01" name="blood_uria1" id="blood_uria1" class="form-control lab-input">
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 1</label>
            <input type="number" step="0.01" name="blood_creatinine1" id="blood_creatinine1" class="form-control lab-input">
          </div>
        </div>

        <p class="section-title mt-1"><i class="fas fa-vial mr-1"></i> Lab Results — Round 2</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>CBC-HB2</label>
            <input type="number" step="0.01" name="cbc_hb2" id="cbc_hb2" class="form-control lab-input">
          </div>
          <div class="col-md-2 form-group">
            <label>CBC-TLC2</label>
            <input type="number" step="0.01" name="cbc_tlc2" id="cbc_tlc2" class="form-control lab-input">
          </div>
          <div class="col-md-3 form-group">
            <label>CBC-PLAT2</label>
            <input type="number" step="0.01" name="cbc_plat2" id="cbc_plat2" class="form-control lab-input">
          </div>
          <div class="col-md-2 form-group">
            <label>Blood Urea 2</label>
            <input type="number" step="0.01" name="blood_uria2" id="blood_uria2" class="form-control lab-input">
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Creatinine 2</label>
            <input type="number" step="0.01" name="blood_creatinine2" id="blood_creatinine2" class="form-control lab-input">
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
            <input type="number" step="0.01" name="bmi" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Glucose <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="glucose" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Cholesterol <small class="text-muted">(mg/dL)</small></label>
            <input type="number" step="0.01" name="cholesterol_level" class="form-control">
          </div>
          <div class="col-md-3 form-group">
            <label>Blood Pressure <small class="text-muted">(mmHg)</small></label>
            <div class="input-group">
              <input type="number" name="systolic_bp" class="form-control" min="0">
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
            <select class="form-control" name="smoking_status">
              <option value="">— Select —</option>
              <option value="1">Smoker</option>
              <option value="0">Non-Smoker</option>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Physical Activity Level</label>
            <select class="form-control" name="physical_activity_level">
              <option value="">— Select —</option>
              <option>Low</option>
              <option>Moderate</option>
              <option>High</option>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Diet Quality</label>
            <select class="form-control" name="diet_quality">
              <option value="">— Select —</option>
              <option>Poor</option>
              <option>Average</option>
              <option>Good</option>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Sleep Hours</label>
            <input type="number" step="0.1" min="0" max="24" name="sleep_hours"
                   class="form-control" placeholder="e.g. 7.5">
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
            <label>Stress Level</label>
            <input type="number" step="0.01" name="stress_level" class="form-control" placeholder="0–10">
          </div>
          <div class="col-md-2 form-group">
            <label>Family History</label>
            <input type="number" step="0.0001" name="family_history" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Medications Count</label>
            <input type="number" step="0.01" name="medications_count" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Risk Score</label>
            <input type="number" step="0.000001" name="risk_score" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Symptom Burden</label>
            <input type="number" step="0.000001" name="symptom_burden" class="form-control">
          </div>
          <div class="col-md-2 form-group">
            <label>Seasonal Weight</label>
            <input type="number" step="0.000001" name="seasonal_weight" class="form-control">
          </div>
        </div>

        <hr>

        <!-- ══ 6. SYMPTOMS ═══════════════════════════════════════════ -->
        <p class="section-title"><i class="fas fa-thermometer-half mr-1"></i> 6. Symptoms</p>
        <div class="row">
          <div class="col-md-2 form-group">
            <label>Fever</label>
            <input type="number" step="0.0001" name="fever" class="form-control" placeholder="0–1 score">
          </div>
          <div class="col-md-2 form-group">
            <label>Cough</label>
            <input type="number" step="0.0001" name="cough" class="form-control" placeholder="0–1 score">
          </div>
          <div class="col-md-2 form-group">
            <label>Fatigue</label>
            <input type="number" step="0.0001" name="fatigue" class="form-control" placeholder="0–1 score">
          </div>
          <div class="col-md-3 form-group">
            <label>Shortness of Breath</label>
            <input type="number" step="0.0001" name="shortness_of_breath" class="form-control" placeholder="0–1 score">
          </div>
          <div class="col-md-3 form-group">
            <div class="form-check mt-4">
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
            <label>Primary Diagnosis</label>
            <input type="text" name="diagnosis" class="form-control"
                   placeholder="e.g. Diabetes, Hypertension, CKD">
          </div>
          <div class="col-md-6 form-group">
            <label>Disease Category</label>
            <select name="disease_category" class="form-control">
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
  <option value="Healthy">Other</option>
</select>
          </div>
        </div>

        <div class="mt-4">
          <button class="btn btn-primary btn-block btn-lg" type="submit">
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
// ── Auto-fill date-derived fields from check-in date ──────────────────────────
const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

document.getElementById('checkin_date').addEventListener('change', function () {
  const d = new Date(this.value);
  if (isNaN(d)) return;
  document.getElementById('month').value       = d.getMonth() + 1;
  document.getElementById('year').value        = d.getFullYear();
  document.getElementById('day_of_week').value = days[d.getDay()];
  updateLOS();
});

document.getElementById('checkout_date').addEventListener('change', updateLOS);

function updateLOS() {
  const ci = document.getElementById('checkin_date').value;
  const co = document.getElementById('checkout_date').value;
  if (!ci || !co) return;
  const diff = Math.round((new Date(co) - new Date(ci)) / 86400000);
  if (diff >= 0) document.getElementById('length_of_stay').value = diff;
}

// ── Live preview of auto-calculated averages & deltas ─────────────────────────
const labIds = ['cbc_hb1','cbc_hb2','cbc_tlc1','cbc_tlc2',
                'cbc_plat1','cbc_plat2','blood_uria1','blood_uria2',
                'blood_creatinine1','blood_creatinine2'];

labIds.forEach(id => {
  document.getElementById(id).addEventListener('input', updateCalcPreview);
});

function v(id) {
  const val = parseFloat(document.getElementById(id).value);
  return isNaN(val) ? null : val;
}

function fmt(val, el) {
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
  const hb1  = v('cbc_hb1'),           hb2  = v('cbc_hb2');
  const tlc1 = v('cbc_tlc1'),          tlc2 = v('cbc_tlc2');
  const pl1  = v('cbc_plat1'),         pl2  = v('cbc_plat2');
  const ur1  = v('blood_uria1'),       ur2  = v('blood_uria2');
  const cr1  = v('blood_creatinine1'), cr2  = v('blood_creatinine2');

  const anyFilled = [hb1,hb2,tlc1,tlc2,pl1,pl2,ur1,ur2,cr1,cr2].some(x => x !== null);
  document.getElementById('calcPreview').style.display = anyFilled ? '' : 'none';

  fmtAvg(calcAvg(hb1,  hb2),  document.getElementById('prev_avg_hb'));
  fmtAvg(calcAvg(tlc1, tlc2), document.getElementById('prev_avg_tlc'));
  fmtAvg(calcAvg(pl1,  pl2),  document.getElementById('prev_avg_plat'));
  fmtAvg(calcAvg(ur1,  ur2),  document.getElementById('prev_avg_urea'));
  fmtAvg(calcAvg(cr1,  cr2),  document.getElementById('prev_avg_crn'));

  fmt(calcDelta(hb1,  hb2),  document.getElementById('prev_d_hb'));
  fmt(calcDelta(tlc1, tlc2), document.getElementById('prev_d_tlc'));
  fmt(calcDelta(pl1,  pl2),  document.getElementById('prev_d_plat'));
  fmt(calcDelta(ur1,  ur2),  document.getElementById('prev_d_urea'));
  fmt(calcDelta(cr1,  cr2),  document.getElementById('prev_d_crn'));
}
</script>

</body>
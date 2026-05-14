<?php
// Variables from HospitalController::viewRecords()
// $patient, $records, $selected_record, $patient_id
function e($v): string { return htmlspecialchars((string)($v??''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>View Medical Records</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fc; }
    .badge-soft { border: 1px solid rgba(0,0,0,.08); }
    .label { font-weight: 600; color: #4e73df; }
    .hr-thin { margin: 10px 0; border-top: 1px solid rgba(0,0,0,.05); }
    .section-title {
      font-size: .85rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: #4e73df;
      margin: 1rem 0 .5rem;
    }
  </style>
</head>
<body>

<div class="container-fluid py-4">

  <!-- ── Top Bar ──────────────────────────────────────────────────── -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_URL ?>/hospital/dashboard#patients" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
    <div>
      <span class="badge badge-info badge-soft p-2 mr-2">
        <i class="fas fa-user-injured mr-1"></i> <?= e($patient["full_name"]) ?>
      </span>
      <span class="badge badge-success badge-soft p-2 mr-2">
        <i class="fas fa-shield-alt mr-1"></i> <?= e($patient["insurance_name"] ?? "N/A") ?>
      </span>
      <span class="badge badge-secondary badge-soft p-2">
        <i class="fas fa-notes-medical mr-1"></i> Records: <?= count($records) ?>
      </span>
    </div>
  </div>

  <!-- ── Records Table ─────────────────────────────────────────────── -->
  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-file-medical mr-1"></i> Medical Records History
      </h6>
      <a href="<?= BASE_URL ?>/hospital/addRecord?patient_id=<?= (int)$patient_id ?>" class="btn btn-sm btn-primary">
        <i class="fas fa-plus mr-1"></i> Add New Record
      </a>
    </div>
    <div class="card-body">

      <?php if (!$records): ?>
        <p class="text-muted mb-0">No medical records found for this patient.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Created At</th>
                <th>Check-in</th>
                <th>Diagnosis</th>
                <th>Category</th>
                <th>LOS</th>
                <th>Risk Flags</th>
                <th style="min-width:180px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($records as $r): ?>
                <tr>
                  <td>#<?= (int)$r["record_id"] ?></td>
                  <td><?= e($r["created_at"]) ?></td>
                  <td><?= e($r["checkin_date"]) ?></td>
                  <td><?= e($r["diagnosis"]) ?></td>
                  <td><?= e($r["disease_category"]) ?></td>
                  <td><?= e($r["length_of_stay"]) ?> d</td>
                  <td>
                    <?php if (!empty($r["has_diabetes"])): ?>
                      <span class="badge badge-warning">DM</span>
                    <?php endif; ?>
                    <?php if (!empty($r["has_hypertension"])): ?>
                      <span class="badge badge-danger">HTN</span>
                    <?php endif; ?>
                    <?php if (!empty($r["has_kidney_disease"])): ?>
                      <span class="badge badge-info">KD</span>
                    <?php endif; ?>
                    <?php if (!empty($r["has_heart_disease"])): ?>
                      <span class="badge badge-primary">HD</span>
                    <?php endif; ?>
                  </td>
                  <td style="white-space:nowrap;">
                    <!-- VIEW -->
                    <a class="btn btn-sm btn-outline-primary"
                       href="<?= BASE_URL ?>/hospital/viewRecords?patient_id=<?= (int)$patient_id ?>&record_id=<?= (int)$r["record_id"] ?>#details">
                      <i class="fas fa-eye"></i> View
                    </a>

                    <!-- EDIT  ✅ restored -->
                    <a class="btn btn-sm btn-outline-success"
                       href="<?= BASE_URL ?>/hospital/editRecord?patient_id=<?= (int)$patient_id ?>&record_id=<?= (int)$r["record_id"] ?>">
                      <i class="fas fa-edit"></i> Edit
                    </a>

                    <!-- DELETE -->
                    <form method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this record permanently?');">
                      <input type="hidden" name="action"    value="delete_record">
                      <input type="hidden" name="record_id" value="<?= (int)$r["record_id"] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- ── Detail Panel ───────────────────────────────────────────────── -->
  <?php if ($selected_record): ?>
  <div id="details" class="card shadow mt-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
      <h6 class="m-0 font-weight-bold">
        <i class="fas fa-id-card-alt mr-2"></i>
        Detailed Report — Record #<?= (int)$selected_record["record_id"] ?>
      </h6>
      <div>
        <a href="<?= BASE_URL ?>/hospital/editRecord?patient_id=<?= (int)$patient_id ?>&record_id=<?= (int)$selected_record["record_id"] ?>"
           class="btn btn-sm btn-light mr-2">
          <i class="fas fa-edit mr-1"></i> Edit This Record
        </a>
        <a href="<?= BASE_URL ?>/hospital/viewRecords?patient_id=<?= (int)$patient_id ?>"
           class="text-white"><i class="fas fa-times"></i></a>
      </div>
    </div>

    <div class="card-body">

      <!-- 1. Admission -->
      <p class="section-title"><i class="fas fa-calendar-alt mr-1"></i> 1. Admission &amp; Visit Info</p>
      <div class="row">
        <div class="col-md-2 mb-2"><span class="label">Age:</span> <?= e($selected_record["age"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Check-in:</span> <?= e($selected_record["checkin_date"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Check-out:</span> <?= e($selected_record["checkout_date"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">LOS:</span> <?= e($selected_record["length_of_stay"]) ?> days</div>
        <div class="col-md-2 mb-2"><span class="label">Avg LOS:</span> <?= e($selected_record["avg_length_stay"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Admissions:</span> <?= e($selected_record["admission_count"]) ?></div>
        <div class="col-md-3 mb-2">
          <span class="label">Timeframe:</span>
          <?= e($selected_record["day_of_week"]) ?>,
          Month <?= e($selected_record["month"]) ?> /
          <?= e($selected_record["year"]) ?>
        </div>
      </div>
      <hr class="hr-thin">

      <!-- 2. Lab Results -->
      <p class="section-title"><i class="fas fa-vial mr-1"></i> 2. Laboratory Results (Round 1 vs Round 2)</p>
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="bg-light">
            <tr>
              <th>Test</th>
              <th>Round 1</th>
              <th>Round 2</th>
              <th>Average</th>
              <th>Delta (Δ)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Hemoglobin (HB)</td>
              <td><?= e($selected_record["cbc_hb1"]) ?></td>
              <td><?= e($selected_record["cbc_hb2"]) ?></td>
              <td><?= e($selected_record["avg_hb"]) ?></td>
              <td class="font-weight-bold <?= ($selected_record["delta_hb"] < 0) ? "text-danger" : "text-success" ?>">
                <?= e($selected_record["delta_hb"]) ?>
              </td>
            </tr>
            <tr>
              <td>TLC</td>
              <td><?= e($selected_record["cbc_tlc1"]) ?></td>
              <td><?= e($selected_record["cbc_tlc2"]) ?></td>
              <td><?= e($selected_record["avg_tlc"]) ?></td>
              <td class="font-weight-bold <?= ($selected_record["delta_tlc"] < 0) ? "text-danger" : "text-success" ?>">
                <?= e($selected_record["delta_tlc"]) ?>
              </td>
            </tr>
            <tr>
              <td>Platelets</td>
              <td><?= e($selected_record["cbc_plat1"]) ?></td>
              <td><?= e($selected_record["cbc_plat2"]) ?></td>
              <td><?= e($selected_record["avg_platelets"]) ?></td>
              <td class="font-weight-bold <?= ($selected_record["delta_plat"] < 0) ? "text-danger" : "text-success" ?>">
                <?= e($selected_record["delta_plat"]) ?>
              </td>
            </tr>
            <tr>
              <td>Blood Urea</td>
              <td><?= e($selected_record["blood_uria1"]) ?></td>
              <td><?= e($selected_record["blood_uria2"]) ?></td>
              <td><?= e($selected_record["avg_urea"]) ?></td>
              <td class="font-weight-bold <?= ($selected_record["delta_uria"] < 0) ? "text-danger" : "text-success" ?>">
                <?= e($selected_record["delta_uria"]) ?>
              </td>
            </tr>
            <tr>
              <td>Creatinine</td>
              <td><?= e($selected_record["blood_creatinine1"]) ?></td>
              <td><?= e($selected_record["blood_creatinine2"]) ?></td>
              <td><?= e($selected_record["avg_creatinine"]) ?></td>
              <td class="font-weight-bold <?= ($selected_record["delta_creatinine"] < 0) ? "text-danger" : "text-success" ?>">
                <?= e($selected_record["delta_creatinine"]) ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <hr class="hr-thin">

      <!-- 3. Vitals & Lifestyle -->
      <p class="section-title"><i class="fas fa-heartbeat mr-1"></i> 3. Vitals &amp; Lifestyle</p>
      <div class="row">
        <div class="col-md-2 mb-2"><span class="label">BMI:</span> <?= e($selected_record["bmi"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Glucose:</span> <?= e($selected_record["glucose"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Blood Pressure:</span> <?= e($selected_record["systolic_bp"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Cholesterol:</span> <?= e($selected_record["cholesterol_level"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Smoking:</span> <?= e($selected_record["smoking_status"]) ?></div>
        <div class="col-md-3 mb-2"><span class="label">Activity:</span> <?= e($selected_record["physical_activity_level"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Diet:</span> <?= e($selected_record["diet_quality"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Sleep:</span> <?= e($selected_record["sleep_hours"]) ?> hrs</div>
        <div class="col-md-2 mb-2"><span class="label">Stress:</span> <?= e($selected_record["stress_level"]) ?></div>
        <div class="col-md-3 mb-2">
          <span class="label">Alcohol:</span>
          <?= $selected_record["alcohol_consumption"] ? '<span class="badge badge-warning">Yes</span>' : '<span class="badge badge-secondary">No</span>' ?>
        </div>
      </div>
      <hr class="hr-thin">

      <!-- 4. Symptoms -->
      <p class="section-title"><i class="fas fa-thermometer-half mr-1"></i> 4. Clinical Symptoms</p>
      <div class="row">
        <div class="col-md-2 mb-2"><span class="label">Fever:</span> <?= e($selected_record["fever"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Cough:</span> <?= e($selected_record["cough"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Fatigue:</span> <?= e($selected_record["fatigue"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">S.O.B:</span> <?= e($selected_record["shortness_of_breath"]) ?></div>
        <div class="col-md-2 mb-2">
          <?php if (!empty($selected_record["chest_pain"])): ?>
            <span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Chest Pain</span>
          <?php endif; ?>
        </div>
        <div class="col-md-2 mb-2">
          <?php if (!empty($selected_record["headache"])): ?>
            <span class="badge badge-warning">Headache</span>
          <?php endif; ?>
        </div>
      </div>
      <hr class="hr-thin">

      <!-- 5. Risk Scores -->
      <p class="section-title"><i class="fas fa-chart-line mr-1"></i> 5. Risk &amp; Predictive Scores</p>
      <div class="row">
        <div class="col-md-2 mb-2"><span class="label">Risk Score:</span> <span class="text-danger font-weight-bold"><?= e($selected_record["risk_score"]) ?></span></div>
        <div class="col-md-2 mb-2"><span class="label">Symptom Burden:</span> <?= e($selected_record["symptom_burden"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Seasonal Weight:</span> <?= e($selected_record["seasonal_weight"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Stress Level:</span> <?= e($selected_record["stress_level"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Family History:</span> <?= e($selected_record["family_history"]) ?></div>
        <div class="col-md-2 mb-2"><span class="label">Medications:</span> <?= e($selected_record["medications_count"]) ?></div>
      </div>
      <hr class="hr-thin">

      <!-- 6. Diagnosis -->
      <p class="section-title"><i class="fas fa-stethoscope mr-1"></i> 6. Diagnosis &amp; Risk Flags</p>
      <div class="p-3 border bg-light rounded">
        <div class="row">
          <div class="col-md-5 mb-2">
            <strong>Primary Diagnosis:</strong> <?= e($selected_record["diagnosis"]) ?>
          </div>
          <div class="col-md-4 mb-2">
            <strong>Disease Category:</strong> <?= e($selected_record["disease_category"]) ?>
          </div>
          <div class="col-md-3 mb-2">
            <?php if (!empty($selected_record["has_diabetes"])): ?>
              <span class="badge badge-warning mr-1">Diabetes</span>
            <?php endif; ?>
            <?php if (!empty($selected_record["has_hypertension"])): ?>
              <span class="badge badge-danger mr-1">Hypertension</span>
            <?php endif; ?>
            <?php if (!empty($selected_record["has_kidney_disease"])): ?>
              <span class="badge badge-info mr-1">Kidney Disease</span>
            <?php endif; ?>
            <?php if (!empty($selected_record["has_heart_disease"])): ?>
              <span class="badge badge-primary mr-1">Heart Disease</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div><!-- /card-body -->
  </div><!-- /details card -->
  <?php endif; ?>

</div>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sb-admin-2.min.js"></script>
</body>
</html>
<?php if ($conn instanceof mysqli) { $conn->close(); } ?>
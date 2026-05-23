<?php
require_once ROOT . '/app/models/InsurancePlan.php';
require_once ROOT . '/app/views/insurance/PolicyAutoSetup.php';


class InsuranceController {

    // ── GET|POST /insurance/dashboard ────────────────────────────────────────
    public function dashboard(): void {
       Guard::staff('INSURANCE_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $insurance_id = (int)($auth->getSessionData('insurance_id') ?? 0);
        if ($insurance_id <= 0) die('Missing insurance_id in session.');

        $insuranceObj   = new Insurance($conn);
        $insuranceObj->loadById($insurance_id);
        $insurance_name = $insuranceObj->getName() ?: 'Medical Insurance';

        $kpi_patients        = $insuranceObj->getKPIPatients();
        $kpi_policies_active = $insuranceObj->getKPIActivePolicies();
        $kpi_cases_month     = $insuranceObj->getKPICasesThisMonth();
        $kpi_pending_reviews = $insuranceObj->getKPIPendingReviews();

        $success_msg = ''; $error_msg = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_patient') {
            $full_name   = trim($_POST['full_name']   ?? '');
            $national_id = trim($_POST['national_id'] ?? '');
            $phone       = trim($_POST['phone']       ?? '');
            $gender      = trim($_POST['gender']      ?? '');
            $address     = trim($_POST['address']     ?? '');

            if ($full_name===''||$national_id===''||$phone===''||$gender===''||$address==='') {
                $error_msg = 'Please fill all fields.';
            } elseif (!Validator::validateNationalId($national_id)) {
                $error_msg = 'National ID must be 14 digits.';
            } elseif (!Validator::validatePhone($phone)) {
                $error_msg = 'Phone must be Egyptian format (010/011/012/015 + 8 digits).';
            } else {
                $check = $conn->prepare('SELECT patient_id FROM patients WHERE national_id = ? LIMIT 1');
                $check->bind_param('s', $national_id);
                $check->execute();
                $exists = $check->get_result()->fetch_assoc();
                $check->close();
                if ($exists) {
                    $error_msg = 'Patient already exists with this National ID (Patient ID: ' . (int)$exists['patient_id'] . ').';
                } else {
                    $patientObj = new Patient($conn);
                    $patientObj->setFullName($full_name);
                    $patientObj->setNationalId($national_id);
                    $patientObj->setPhone($phone);
                    $patientObj->setGender($gender);
                    $patientObj->setAddress($address);
                    if ($patientObj->create(null, $insurance_id)) {
                        header('Location: ' . BASE_URL . '/insurance/dashboard?added=' . $patientObj->getPatientId() . '#patients');
                        exit;
                    } else { $error_msg = 'Failed to create patient.'; }
                }
            }
        }

        if (isset($_GET['added'])) {
            $success_msg = 'Patient added successfully ✅ (ID: ' . (int)$_GET['added'] . ') under <b>' . Validator::sanitizeInput($insurance_name) . '</b>.';
        }

        $patientObj = new Patient($conn);
        $q          = trim($_GET['q'] ?? '');
        $patients   = $patientObj->getPatientsByInsurance($insurance_id, $q) ?? [];

        // ── ML FORECAST ───────────────────────────────────────────────────────
        require_once ROOT . '/app/models/InsuranceForecast.php';

        // Pull real claims + patient data for this insurance company
        $stmt = $conn->prepare("
            SELECT
                COALESCE(mr.age, 35)                                    AS Age,
                CASE p.gender WHEN 'M' THEN 'Male' ELSE 'Female' END   AS Gender,
                COALESCE(mr.bmi, 25.0)                                  AS BMI,
                COALESCE(mr.systolic_bp, 120)                           AS Blood_Pressure,
                COALESCE(c.treatment_cost, 5000)                        AS Treatment_Cost,
                COALESCE(c.coverage_percentage, 0.75)                   AS Coverage_Percentage,
                COALESCE(c.claim_amount, 3000)                          AS Claim_Amount,
                c.claim_status                                          AS Claim_Status,
                COALESCE(mr.admission_count, 1)                         AS Admission_Count,
                COALESCE(mr.length_of_stay, 3)                          AS Length_of_Stay,
                YEAR(COALESCE(c.created_at, NOW()))                     AS Year
            FROM claims c
            INNER JOIN patients        p  ON p.patient_id  = c.patient_id
            LEFT  JOIN medical_records mr ON mr.patient_id = c.patient_id
            WHERE c.insurance_id = ?
              AND YEAR(c.created_at) >= YEAR(CURDATE()) - 1
            ORDER BY c.created_at DESC
            LIMIT 500
        ");
        $stmt->bind_param('i', $insurance_id);
        $stmt->execute();
        $raw_claims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $patients_for_forecast = [];
        foreach ($raw_claims as $row) {
            $patients_for_forecast[] = [
                'Age'                 => (float)$row['Age'],
                'Gender'              => (string)$row['Gender'],
                'BMI'                 => (float)$row['BMI'],
                'Blood_Pressure'      => (float)$row['Blood_Pressure'],
                'Treatment_Cost'      => (float)$row['Treatment_Cost'],
                'Coverage_Percentage' => (float)$row['Coverage_Percentage'],
                'Claim_Amount'        => (float)$row['Claim_Amount'],
                'Claim_Status'        => (string)$row['Claim_Status'],
                'Admission_Count'     => (int)$row['Admission_Count'],
                'Length_of_Stay'      => (int)$row['Length_of_Stay'],
                'Year'                => (float)$row['Year'],
                'Insurance_Company'   => $insurance_name,
            ];
        }
        $db_records_count = count($patients_for_forecast);

        $forecast    = new InsuranceForecast('http://127.0.0.1:5000');
        $api_alive   = $forecast->isApiAlive();
        $ml_forecast = [];

        if ($api_alive) {
            if ($db_records_count >= 10) {
                $ml_forecast = $forecast->getLiveForecast($insurance_name, $patients_for_forecast);
                if (empty($ml_forecast) || ($ml_forecast['status'] ?? '') !== 'ok') {
                    error_log('[InsuranceController] Live forecast failed: ' . json_encode($ml_forecast));
                    $ml_forecast = $forecast->getSavedForecast($insurance_name);
                }
            } else {
                $ml_forecast = $forecast->getSavedForecast($insurance_name);
            }
        }

        // Flatten forecast values for the view
        $ml_ok          = !empty($ml_forecast) && ($ml_forecast['status'] ?? '') === 'ok';
        $ml_source      = $ml_forecast['source']             ?? 'none';
        $ml_blended     = $ml_ok ? (float)($ml_forecast['blended_change_pct'] ?? 0) : 0;
        $ml_lower       = $ml_ok ? (float)($ml_forecast['lower_pct']          ?? 0) : 0;
        $ml_upper       = $ml_ok ? (float)($ml_forecast['upper_pct']          ?? 0) : 0;
        $ml_uncertainty = $ml_ok ? (float)($ml_forecast['uncertainty_pp']     ?? 0) : 0;
        $ml_action      = $ml_ok ? ($ml_forecast['action'] ?? '')                    : '';
        $ml_year        = $ml_ok ? (int)($ml_forecast['predicted_year'] ?? date('Y') + 1) : (int)date('Y') + 1;

        $fc_color = self::forecastColor($ml_blended);
        // ─────────────────────────────────────────────────────────────────────

        // ── FRAUD detection query ─────────────────────────────────────────────
        $fraud_query = $conn->prepare("
            SELECT p.patient_id, p.full_name, p.national_id, p.phone,
                   pp.policy_number, pp.status AS policy_status,
                   mr.admission_count, mr.length_of_stay,
                   mr.checkin_date, mr.checkout_date, mr.diagnosis,
                   COUNT(c.claim_id)                                     AS total_claims,
                   SUM(c.claim_amount)                                   AS total_claimed,
                   COUNT(CASE WHEN c.claim_status='Pending' THEN 1 END) AS pending_claims
            FROM patients p
            LEFT JOIN patient_policy  pp ON p.patient_id = pp.patient_id
            LEFT JOIN medical_records mr ON p.patient_id = mr.patient_id
            LEFT JOIN claims          c  ON p.patient_id = c.patient_id AND c.insurance_id = ?
            WHERE p.insurance_id = ? AND mr.admission_count >= 3
            GROUP BY p.patient_id, mr.record_id
            ORDER BY mr.admission_count DESC, total_claimed DESC
        ");
        $fraud_query->bind_param('ii', $insurance_id, $insurance_id);
        $fraud_query->execute();
        $fraud_patients = $fraud_query->get_result()->fetch_all(MYSQLI_ASSOC);
        $fraud_query->close();

        // ── RENEWAL query ─────────────────────────────────────────────────────
        $renewal_query = $conn->prepare("
            SELECT p.patient_id, p.full_name, p.gender,
                   pp.policy_number, pp.end_date, pp.status AS policy_status,
                   ip.plan_name,
                   DATEDIFF(pp.end_date, CURDATE()) AS days_until_expiry,
                   mr.risk_score,
                   mr.has_diabetes, mr.has_hypertension,
                   mr.has_kidney_disease, mr.has_heart_disease,
                   mr.admission_count, mr.avg_length_stay,
                   (mr.has_diabetes + mr.has_hypertension +
                    mr.has_kidney_disease + mr.has_heart_disease) AS chronic_count,
                   SUM(c.claim_amount) AS total_claimed,
                   COUNT(c.claim_id)   AS claim_count
            FROM patients p
            JOIN  patient_policy  pp ON p.patient_id         = pp.patient_id
            JOIN  insurance_plan  ip ON pp.insurance_plan_id = ip.id
            LEFT JOIN medical_records mr ON p.patient_id     = mr.patient_id
            LEFT JOIN claims          c  ON p.patient_id     = c.patient_id AND c.insurance_id = ?
            WHERE p.insurance_id = ?
              AND pp.status = 'active'
              AND pp.end_date IS NOT NULL
              AND DATEDIFF(pp.end_date, CURDATE()) <= 90
            GROUP BY p.patient_id, mr.record_id
            ORDER BY mr.risk_score DESC, chronic_count DESC
        ");
        $renewal_query->bind_param('ii', $insurance_id, $insurance_id);
        $renewal_query->execute();
        $renewal_patients = $renewal_query->get_result()->fetch_all(MYSQLI_ASSOC);
        $renewal_query->close();

        // Chart arrays
        $fraud_names      = array_map(fn($r) => $r['full_name'],                 $fraud_patients);
        $fraud_admissions = array_map(fn($r) => (int)$r['admission_count'],      $fraud_patients);
        $fraud_claimed    = array_map(fn($r) => (float)($r['total_claimed']??0), $fraud_patients);
        $fraud_pending    = array_map(fn($r) => (int)($r['pending_claims']??0),  $fraud_patients);
        $ren_names        = array_map(fn($r) => $r['full_name'],                 $renewal_patients);
        $ren_days         = array_map(fn($r) => (int)($r['days_until_expiry']??0), $renewal_patients);
        $ren_risk         = array_map(fn($r) => round((float)($r['risk_score']??0), 4), $renewal_patients);

        $raise_c = $review_c = $std_c = 0;
        foreach ($renewal_patients as $rp) {
            [$tl] = self::riskTier((float)($rp['risk_score'] ?? 0));
            $ch   = (int)($rp['chronic_count'] ?? 0);
            if ($tl === 'High Risk'   || $ch >= 2) $raise_c++;
            elseif ($tl === 'Medium Risk' || $ch === 1) $review_c++;
            else $std_c++;
        }
        $high_risk_count   = $raise_c;
        $total_renewal_exp = array_sum(array_column($renewal_patients, 'total_claimed'));

        require_once ROOT . '/app/views/insurance/dashboard.php';
        $db->close();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public static function forecastColor(float $pct): string {
        if ($pct >= 15) return 'danger';
        if ($pct >= 8)  return 'warning';
        if ($pct >= 3)  return 'info';
        return 'success';
    }

    public static function riskTier($score): array {
        if ($score === null || $score == 0) return ['Unknown', 'secondary'];
        if ($score >= 0.7) return ['High Risk', 'danger'];
        if ($score >= 0.4) return ['Medium Risk', 'warning'];
        return ['Low Risk', 'success'];
    }

    // ── GET|POST /insurance/addPolicy ────────────────────────────────────────
    public function addPolicy(): void {
        Guard::staff('INSURANCE_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $insurance_id = (int)($auth->getSessionData('insurance_id') ?? 0);
        $patient_id   = (int)($_GET['patient_id'] ?? 0);
        if ($insurance_id <= 0) die('Missing insurance_id in session.');
        if ($patient_id <= 0)   die('Invalid patient_id');

        $patientObj = new Patient($conn);
        if (!$patientObj->loadById($patient_id)) die('Patient not found.');

        $insuranceObj = new Insurance($conn);
        $insuranceObj->loadById($insurance_id);
        $insurance_name = $insuranceObj->getName();

        $insurancePlanObj = new InsurancePlan($conn);
        $plans = $insurancePlanObj->getPlansByInsurance($insurance_id);

        $success = ''; $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $plan_id       = (int)($_POST['insurance_plan_id'] ?? 0);
            $policy_number = trim($_POST['policy_number'] ?? '');
            $start_date    = trim($_POST['start_date'] ?? '');
            $end_date      = trim($_POST['end_date']   ?? '');
            $status        = trim($_POST['status'] ?? 'active');

            if ($plan_id <= 0) { $error = 'Please select a plan.'; }
            elseif ($policy_number === '') { $error = 'Policy number is required.'; }
            elseif ($start_date === '') { $error = 'Start date is required.'; }
            elseif (!$insurancePlanObj->validatePlanBelongsToInsurance($plan_id, $insurance_id)) { $error = 'Invalid plan selected.'; }
            else {
                $policyObj = new PatientPolicy($conn);
                $policyObj->setPatientId($patient_id);
                $policyObj->setInsuranceId($insurance_id);
                $policyObj->setInsurancePlanId($plan_id);
                $policyObj->setPolicyNumber($policy_number);
                $policyObj->setStartDate($start_date);
                $policyObj->setEndDate($end_date === '' ? null : $end_date);
                $policyObj->setStatus($status);
                if ($policyObj->create()) { $success = 'Patient policy added successfully ✅'; }
                else { $error = $policyObj->getLastError() ?: 'Failed to create policy. Please try again.'; }
            }
        }

        require_once ROOT . '/app/views/insurance/addPolicy.php';
        $db->close();
    }

    // ── GET /insurance/policy ────────────────────────────────────────────────
    public function policy(): void {
        Guard::staff('INSURANCE_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $insurance_id   = (int)($auth->getSessionData('insurance_id') ?? 0);
        if ($insurance_id <= 0) die('Missing insurance_id in session.');

        $insurance_name   = $auth->getSessionData('staff_name') ?? 'Medical Insurance';

        // Auto-setup: ensure all 4 default policy types exist
        $autoSetup = new PolicyAutoSetup($conn);
        if (!$autoSetup->hasAllPolicies($insurance_id)) {
            $autoSetup->createDefaultPolicies($insurance_id);
        }

        $insurancePlanObj  = new InsurancePlan($conn);
        $policies          = $insurancePlanObj->getAllPoliciesForInsurance($insurance_id);
        $completion_status = $insurancePlanObj->getPolicyCompletionStatus($insurance_id);

        $flash_success = $_SESSION['flash_success'] ?? null;
        $flash_error   = $_SESSION['flash_error']   ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        // Pass $insurancePlanObj as $insurancePlan for backward compat in view
        $insurancePlan = $insurancePlanObj;

        require_once ROOT . '/app/views/insurance/policy.php';
        $db->close();
    }

    // ── GET /insurance/editPolicy ────────────────────────────────────────────
    public function editPolicy(): void {
       Guard::staff('INSURANCE_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $insurance_id     = (int)($auth->getSessionData('insurance_id') ?? 0);
        if ($insurance_id <= 0) die('Missing insurance_id in session.');

        $category_id      = (int)($_GET['category_id']      ?? 0);
        $customer_type_id = (int)($_GET['customer_type_id'] ?? 0);
        if ($category_id <= 0 || $customer_type_id <= 0) die('Invalid policy type');

        $insurancePlanObj   = new InsurancePlan($conn);
        $insurance_name     = $auth->getSessionData('staff_name') ?? 'Medical Insurance';
        $category_name      = ($category_id == 1) ? 'Normal' : 'VIP';
        $customer_type_name = ($customer_type_id == 1) ? 'Individual' : 'Company';

        $existing_plan     = $insurancePlanObj->getPlanByType($insurance_id, $category_id, $customer_type_id);
        $is_editing        = !empty($existing_plan);
       $existing_services = [];

if ($is_editing && !empty($existing_plan['plan_id'])) {
    $existing_services = $insurancePlanObj->getServicesByPlanId((int)$existing_plan['plan_id']);
}

        // Index service_data by service_id for easy lookup in view
        $service_data = [];
        foreach ($existing_services as $s) {
            $service_data[$s['service_id']] = $s;
        }

        // ✅ FIXED: filename is editPolicy.php (not edit_policy.php)
        require_once ROOT . '/app/views/insurance/editPolicy.php';
        $db->close();
    }

    // ── POST /insurance/savePolicy ───────────────────────────────────────────
    public function savePolicy(): void {
        Guard::staff('INSURANCE_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $insurance_id     = (int)($auth->getSessionData('insurance_id') ?? 0);
        if ($insurance_id <= 0) die('Missing insurance_id in session');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/insurance/policy'); exit; }

        $category_id      = (int)($_POST['category_id'] ?? 0);
        $customer_type_id = (int)($_POST['customer_type_id'] ?? 0);
        if ($category_id <= 0 || $customer_type_id <= 0) die('Invalid policy type submitted.');

        $service_map = [
            'checkup'    => ['id'=>1,'required'=>true],
            'operations' => ['id'=>2,'required'=>true],
            'maternity'  => ['id'=>3,'required'=>false],
            'dental'     => ['id'=>4,'required'=>false],
            'optical'    => ['id'=>5,'required'=>false],
        ];
        $enabled_optional = array_filter((array)($_POST['coverage_services'] ?? []),
            fn($s) => !in_array($s, ['checkup','operations']));

        $insurancePlanObj = new InsurancePlan($conn);
        $plan_id = $insurancePlanObj->getOrCreatePlan($insurance_id, $category_id, $customer_type_id);
        if (!$plan_id) die('Failed to create or retrieve the insurance plan.');

        $all_ok = true;
        foreach ($service_map as $slug => $meta) {
            $is_enabled = $meta['required'] ? 1 : (in_array($slug, $enabled_optional) ? 1 : 0);
            $ok = $insurancePlanObj->saveServiceCoverage(
                $plan_id, $meta['id'], $is_enabled,
                min(100, max(0, (float)($_POST["coverage_{$slug}"] ?? 0))),
                max(0, (float)($_POST["threshold_{$slug}"] ?? 0)),
                min(100, max(0, (float)($_POST["copay_{$slug}"] ?? 0))),
                max(0, (float)($_POST["deductible_{$slug}"] ?? 0))
            );
            if (!$ok) $all_ok = false;
        }
        $insurancePlanObj->updateInsuranceCompletionStatus($insurance_id);
        $db->close();

        if ($all_ok) {
            $_SESSION['flash_success'] = 'Policy updated successfully.';
            header('Location: ' . BASE_URL . '/insurance/policy');
        } else {
            $_SESSION['flash_error'] = 'Some services could not be saved.';
            header('Location: ' . BASE_URL . '/insurance/editPolicy?category_id=' . $category_id . '&customer_type_id=' . $customer_type_id);
        }
        exit;
    }



    // ── GET /insurance/createAllPolicies ─────────────────────────────────────
    public function createAllPolicies(): void {
       Guard::staff('INSURANCE_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $insurance_id   = (int)($auth->getSessionData('insurance_id') ?? 0);
        if ($insurance_id <= 0) die('Missing insurance_id in session.');
        $insurance_name = $auth->getSessionData('staff_name') ?? 'Medical Insurance';

        require_once ROOT . '/app/views/insurance/createAllPolicies.php';
        $db->close();
    }

    // ── POST /insurance/saveAllPolicies ──────────────────────────────────────
    public function saveAllPolicies(): void {
        Guard::staff('INSURANCE_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $insurance_id = (int)($auth->getSessionData('insurance_id') ?? 0);
        if ($insurance_id <= 0) die('Missing insurance_id in session');

        $insurancePlanObj = new InsurancePlan($conn);
        $serviceMap = ['checkup'=>1,'operations'=>2,'maternity'=>3,'dental'=>4,'optical'=>5];
        $policy_types = [
            'normal_individual'=>['category_id'=>1,'customer_type_id'=>1],
            'normal_company'   =>['category_id'=>1,'customer_type_id'=>2],
            'vip_individual'   =>['category_id'=>2,'customer_type_id'=>1],
            'vip_company'      =>['category_id'=>2,'customer_type_id'=>2],
        ];

        foreach ($policy_types as $prefix => $type) {
            $plan_id = $insurancePlanObj->getOrCreatePlan($insurance_id, $type['category_id'], $type['customer_type_id']);
            if (!$plan_id) continue;
            foreach ($serviceMap as $name => $id) {
                $is_enabled = ($name==='checkup'||$name==='operations') ? 1 : (isset($_POST["{$prefix}_enabled_{$name}"]) ? 1 : 0);
                $insurancePlanObj->saveServiceCoverage(
                    $plan_id, $id, $is_enabled,
                    (float)($_POST["{$prefix}_coverage_{$name}"] ?? 0),
                    (float)($_POST["{$prefix}_threshold_{$name}"] ?? 0),
                    (float)($_POST["{$prefix}_copay_{$name}"] ?? 0),
                    (float)($_POST["{$prefix}_deductible_{$name}"] ?? 0)
                );
            }
        }
        $insurancePlanObj->updateInsuranceCompletionStatus($insurance_id);
        $_SESSION['insurance_policy_completed'] = 1;
        $db->close();
        header('Location: ' . BASE_URL . '/insurance/policy');
        exit;
    }

    
    // ── GET /insurance/viewRecords ───────────────────────────────────────────
    public function viewRecords(): void {
       Guard::staff('INSURANCE_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $insurance_id = (int)($auth->getSessionData('insurance_id') ?? 0);
        $patient_id   = (int)($_GET['patient_id'] ?? 0);
        if ($patient_id <= 0) die('Invalid patient_id');

        // Verify patient belongs to this insurance
        $stmt = $conn->prepare('SELECT p.*, mi.name AS insurance_name FROM patients p LEFT JOIN medical_insurances mi ON p.insurance_id=mi.insurance_id WHERE p.patient_id=? AND p.insurance_id=? LIMIT 1');
        $stmt->bind_param('ii', $patient_id, $insurance_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$patient) die('Patient not found or not under your insurance.');

        $records = [];
        $stmt = $conn->prepare('SELECT * FROM medical_records WHERE patient_id = ? ORDER BY record_id DESC');
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $records[] = $row;
        $stmt->close();

        require_once ROOT . '/app/views/insurance/viewRecords.php';
       
    }
}
<?php

class PatientController {

    // ── Private: call Flask prediction API ──────────────────────────────────
    private function callFlaskPrediction(int $patient_id): array {
        $url = 'http://127.0.0.1:5000/predict/patient/' . $patient_id;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            return [
                'ok'      => false,
                'message' => $curlErr ?: 'Flask API request failed (HTTP ' . $httpCode . ')',
            ];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Invalid JSON from Flask API'];
        }

        return $data;
    }

    // ── Private: derive risk level from prediction result ────────────────────
    private function deriveRiskLevel(array $ai): string {
        if (!empty($ai['risk_level'])) {
            return strtoupper((string)$ai['risk_level']);
        }

        $disease = strtolower(trim((string)($ai['predicted_disease'] ?? '')));
        $alerts  = $ai['active_alerts'] ?? [];
        $count   = is_array($alerts) ? count($alerts) : 0;

        if (in_array($disease, ['heart disease', 'kidney disease'], true)) return 'HIGH';
        if (in_array($disease, ['diabetes mellitus', 'diabetes', 'hypertension'], true)) {
            return $count >= 2 ? 'HIGH' : 'MODERATE';
        }
        if ($count >= 3) return 'HIGH';
        if ($count >= 1) return 'MODERATE';
        return 'LOW';
    }

    // ── Private: map risk level to CSS class ─────────────────────────────────
    private function riskBadgeClass(string $riskLevel): string {
        $riskLevel = strtoupper($riskLevel);
        if ($riskLevel === 'HIGH')     return 'risk-level-high';
        if ($riskLevel === 'MODERATE') return 'risk-level-moderate';
        return 'risk-level-low';
    }

    // ── GET /patient/dashboard ───────────────────────────────────────────────
    public function dashboard(): void {
       Guard::patient();
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $patient_id = (int)$auth->getSessionData('patient_id');
        if ($patient_id <= 0) { header('Location: ' . BASE_URL . '/auth/login'); exit; }

        $patientObj = new Patient($conn);
        if (!$patientObj->loadById($patient_id)) {
            session_destroy();
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $dob           = $patientObj->getDOBFromNationalId();
        $age           = $patientObj->getAge();
        $insuranceName = $patientObj->getInsuranceName();
        if (!$insuranceName && $patientObj->getInsuranceId()) {
            $insuranceName = '#' . $patientObj->getInsuranceId();
        }

        $policyObj  = new PatientPolicy($conn);
        $policyData = $policyObj->loadByPatientId($patient_id) ?? [];

        $medObj      = new MedicalRecord($conn);
        $records     = $medObj->getRecordsByPatient($patient_id, 20) ?? [];
        $latest      = $records[0] ?? null;
        $kpi_records = count($records);

        $claimStatus = $_GET['claim'] ?? '';
        $claimMsg    = $_GET['msg']   ?? '';

        // ── Fetch claim history for display ─────────────────────────────────
        $claimsHistory = [];
        $stmtC = $conn->prepare("
            SELECT c.claim_id, c.claim_amount, c.claim_status, c.created_at,
                   c.rejection_reason,
                   CASE c.service_id
                       WHEN 1 THEN 'Checkup / Consultation'
                       WHEN 2 THEN 'Operations / Surgery'
                       WHEN 3 THEN 'Maternity Care'
                       WHEN 4 THEN 'Dental Services'
                       WHEN 5 THEN 'Optical Services'
                       ELSE 'Unknown Service'
                   END AS service_name
            FROM claims c
            WHERE c.patient_id = ?
            ORDER BY c.claim_id DESC
            LIMIT 10
        ");
        if ($stmtC) {
            $stmtC->bind_param('i', $patient_id);
            $stmtC->execute();
            $claimsHistory = $stmtC->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtC->close();
        }

        // ── Flask AI prediction ──────────────────────────────────────────────
        $aiPrediction     = $this->callFlaskPrediction($patient_id);
        $riskLevel        = !empty($aiPrediction['ok'])
                                ? $this->deriveRiskLevel($aiPrediction)
                                : 'LOW';
        $riskClass        = $this->riskBadgeClass($riskLevel);
        $predictedDisease = $aiPrediction['predicted_disease']    ?? 'No prediction';
        $diseaseCategory  = $aiPrediction['disease_category']     ?? 'General';
        $activeAlerts     = is_array($aiPrediction['active_alerts']     ?? null) ? $aiPrediction['active_alerts']     : [];
        $shortMeasures    = is_array($aiPrediction['short_term_measures'] ?? null) ? $aiPrediction['short_term_measures'] : [];
        $longMeasures     = is_array($aiPrediction['long_term_measures']  ?? null) ? $aiPrediction['long_term_measures']  : [];
        $confidenceScore  = (int)($aiPrediction['confidence_score'] ?? 0);
        $confidenceLabel  = $aiPrediction['confidence_label']       ?? 'N/A';
        // ────────────────────────────────────────────────────────────────────

        require_once ROOT . '/app/views/patient/dashboard.php';
        $db->close();
    }

    // ── GET /patient/viewRecord ──────────────────────────────────────────────
    public function viewRecord(): void {
       Guard::patient();
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $patient_id = (int)$auth->getSessionData('patient_id');
        $record_id  = (int)($_GET['record_id'] ?? 0);
        if ($patient_id <= 0 || $record_id <= 0) die('Invalid request.');

        $stmt = $conn->prepare('SELECT * FROM medical_records WHERE record_id = ? AND patient_id = ? LIMIT 1');
        $stmt->bind_param('ii', $record_id, $patient_id);
        $stmt->execute();
        $rec = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$rec) die('Record not found or access denied.');

        $monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                       7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
        $monthName  = $monthNames[(int)$rec['month']] ?? (string)$rec['month'];
        $catColors  = ['cardiovascular'=>'danger','renal'=>'info','metabolic'=>'warning',
                       'oncological'=>'dark','respiratory'=>'primary','neurological'=>'secondary','healthy'=>'success'];
        $catKey     = strtolower(trim($rec['disease_category'] ?? ''));
        $catBadge   = $catColors[$catKey] ?? 'secondary';

        require_once ROOT . '/app/views/patient/viewRecord.php';
        
    }

    // ── Helper: map form service_type label → service_id integer ────────────
    private function resolveServiceId(string $service_type): ?int {
        $map = [
            'checkup'    => 1,
            'operations' => 2,
            'surgery'    => 2,
            'maternity'  => 3,
            'dental'     => 4,
            'optical'    => 5,
        ];
        return $map[strtolower(trim($service_type))] ?? null;
    }

    // ── POST /patient/processClaim ───────────────────────────────────────────
    // Fully automated: checks policy, coverage, threshold → sets Accepted or Rejected
    public function processClaim(): void {
        $this->submitClaim();
    }

    // ── POST /patient/submitClaim ────────────────────────────────────────────
    public function submitClaim(): void {
    $db   = new Database();
    $conn = $db->getConnection();
    $auth = new Auth($conn);
    $auth->checkPatientAuth();

    $patient_id = (int)$auth->getSessionData('patient_id');
    if ($patient_id <= 0) { header('Location: ' . BASE_URL . '/auth/login'); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/patient/dashboard'); exit; }

    $redirect = function(string $status, string $msg = '') {
        $url = BASE_URL . '/patient/dashboard?claim=' . urlencode($status);
        if ($msg) $url .= '&msg=' . urlencode($msg);
        header('Location: ' . $url);
        exit;
    };

    // Helper: insert and redirect, always saving the row
    $saveAndRedirect = function(
        string $final_status,
        string $reason,
        int    $record_id,
        int    $patient_id,
        int    $insurance_id,
        int    $db_service_id,
        float  $amt
    ) use ($conn, $db, $redirect) {
        $stmt = $conn->prepare("
            INSERT INTO claims
                (record_id, patient_id, insurance_id, service_id, treatment_cost, claim_amount, claim_status, rejection_reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iiiiddss',
            $record_id, $patient_id, $insurance_id, $db_service_id,
            $amt, $amt, $final_status, $reason
        );
        $stmt->execute();
        $stmt->close();
        $db->close();

        if ($final_status === 'Accepted') {
            $redirect('accepted', 'Your claim has been accepted successfully.');
        } else {
            $redirect('rejected', $reason);
        }
    };

    // Step 1: Validate input
    $service_type = trim($_POST['service_type'] ?? '');
    $claim_amount = trim($_POST['claim_amount'] ?? '');
    if (!$service_type || !$claim_amount) $redirect('error', 'Service type and claim amount are required.');
    $amt = (float)$claim_amount;
    if ($amt <= 0) $redirect('error', 'Claim amount must be greater than zero.');

    // Step 2: Need a medical record before we can insert anything
    $stmt = $conn->prepare('SELECT record_id FROM medical_records WHERE patient_id = ? ORDER BY record_id DESC LIMIT 1');
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $recRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$recRow) {
        $redirect('error', 'No medical record found. A claim requires at least one medical record on file.');
    }
    $record_id     = (int)$recRow['record_id'];
    $service_id    = $this->resolveServiceId($service_type);
    $db_service_id = $service_id ?? 0;

    // Step 3: Patient must be active and have an insurance_id
    $stmt = $conn->prepare('SELECT insurance_id FROM patients WHERE patient_id = ? AND is_active = 1 LIMIT 1');
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $patientRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$patientRow || !$patientRow['insurance_id']) {
        $saveAndRedirect('Rejected', 'No active insurance found for your account.',
            $record_id, $patient_id, 0, $db_service_id, $amt);
    }
    $insurance_id = (int)$patientRow['insurance_id'];

    // Step 4: Load the patient's most recent policy
    $stmt = $conn->prepare('
        SELECT patient_policy_id, insurance_plan_id, status, end_date
        FROM patient_policy
        WHERE patient_id = ?
        ORDER BY patient_policy_id DESC
        LIMIT 1
    ');
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $policy = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$policy) {
        $saveAndRedirect('Rejected', 'No insurance policy found for your account.',
            $record_id, $patient_id, $insurance_id, $db_service_id, $amt);
    }

    if (strtolower($policy['status']) !== 'active') {
        $saveAndRedirect('Rejected', 'Your insurance policy is not active.',
            $record_id, $patient_id, $insurance_id, $db_service_id, $amt);
    }

    if (!empty($policy['end_date'])) {
        $today    = new DateTime('today');
        $end_date = new DateTime($policy['end_date']);
        if ($end_date < $today) {
            $saveAndRedirect('Rejected', 'Your insurance policy has expired.',
                $record_id, $patient_id, $insurance_id, $db_service_id, $amt);
        }
    }

    $insurance_plan_id = (int)$policy['insurance_plan_id'];

    // Step 5: Check coverage
    $final_status  = 'Rejected';
    $reject_reason = '';

    if ($service_id === null) {
        $reject_reason = 'The selected service type is not recognized.';
    } else {
        $stmt = $conn->prepare('
            SELECT is_enabled, threshold_egp
            FROM plan_service_coverage
            WHERE insurance_plan_id = ? AND service_id = ?
            LIMIT 1
        ');
        $stmt->bind_param('ii', $insurance_plan_id, $service_id);
        $stmt->execute();
        $coverage = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$coverage || (int)$coverage['is_enabled'] !== 1) {
            $reject_reason = 'This service is not covered under your insurance plan.';
        } elseif ($amt > (float)$coverage['threshold_egp']) {
            $threshold     = (float)$coverage['threshold_egp'];
            $reject_reason = "Claim amount exceeds the covered threshold of {$threshold} EGP for this service.";
        } else {
            $final_status = 'Accepted';
        }
    }

    $saveAndRedirect($final_status, $reject_reason,
        $record_id, $patient_id, $insurance_id, $db_service_id, $amt);
}
}
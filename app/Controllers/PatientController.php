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
        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkPatientAuth();

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
        // ────────────────────────────────────────────────────────────────────

        require_once ROOT . '/app/views/patient/dashboard.php';
        $db->close();
    }

    // ── GET /patient/viewRecord ──────────────────────────────────────────────
    public function viewRecord(): void {
        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkPatientAuth();

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

        $service_name = trim($_POST['service_type']  ?? '');
        $claim_amount = trim($_POST['claim_amount'] ?? '');
        if (!$service_name || !$claim_amount) $redirect('error', 'Service type and claim amount are required.');
        if ((float)$claim_amount <= 0) $redirect('error', 'Claim amount must be greater than zero.');

        // Patient's insurance
        $stmt = $conn->prepare('SELECT insurance_id FROM patients WHERE patient_id = ? AND is_active = 1 LIMIT 1');
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || !$row['insurance_id']) $redirect('error', 'No active insurance found for your account.');
        $insurance_id = (int)$row['insurance_id'];

        // Resolve service id
        $stmt = $conn->prepare('SELECT id FROM service WHERE name = ? LIMIT 1');
        $stmt->bind_param('s', $service_name);
        $stmt->execute();
        $svcRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$svcRow) {
            $stmt = $conn->prepare('SELECT id FROM service WHERE LOWER(name) = LOWER(?) LIMIT 1');
            $stmt->bind_param('s', $service_name);
            $stmt->execute();
            $svcRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        $svc_id = $svcRow ? (string)$svcRow['id'] : null;

        // Latest medical record
        $stmt = $conn->prepare('SELECT record_id FROM medical_records WHERE patient_id = ? ORDER BY record_id DESC LIMIT 1');
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $recRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$recRow) $redirect('error', 'No medical record found. A claim requires at least one medical record on file.');
        $record_id = (int)$recRow['record_id'];

        $amt = (float)$claim_amount;
        $stmt = $conn->prepare("INSERT INTO claims (record_id,patient_id,insurance_id,service_id,treatment_cost,claim_amount,claim_status) VALUES (?,?,?,?,?,?,'Pending')");
        $stmt->bind_param('ssssss', $record_id, $patient_id, $insurance_id, $svc_id, $amt, $amt);
        if ($stmt->execute()) { $stmt->close(); $redirect('success'); }
        else { $err = $stmt->error; $stmt->close(); $redirect('error', 'Database error: ' . $err); }

        $db->close();
    }
}
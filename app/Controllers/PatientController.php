<?php

class PatientController {

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
        if (!$insuranceName && $patientObj->getInsuranceId()) $insuranceName = '#' . $patientObj->getInsuranceId();

        $policyObj  = new PatientPolicy($conn);
        $policyData = $policyObj->loadByPatientId($patient_id) ?? [];

        $medObj      = new MedicalRecord($conn);
        $records     = $medObj->getRecordsByPatient($patient_id, 20) ?? [];
        $latest      = $records[0] ?? null;
        $kpi_records = count($records);

        $claimStatus = $_GET['claim'] ?? '';
        $claimMsg    = $_GET['msg']   ?? '';

        require_once ROOT . '/app/views/patient/dashboard.php';
       
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

        
    }
}
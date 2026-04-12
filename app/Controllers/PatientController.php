<?php

class PatientController {

    // ──────────────────────────────────────────────
    // GET /patient/dashboard
    // ──────────────────────────────────────────────
    public function dashboard(): void {

        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkPatientAuth(); // redirects to login.html if not authed

        $patient_id = (int) $auth->getSessionData('patient_id');
        if ($patient_id <= 0) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // ── Load patient via OOP ──
        $patientObj = new Patient($conn);
        if (!$patientObj->loadById($patient_id)) {
            session_destroy();
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $dob = $patientObj->getDOBFromNationalId();
        $age = $patientObj->getAge();

        $insuranceName = $patientObj->getInsuranceName();
        if (!$insuranceName && $patientObj->getInsuranceId()) {
            $insuranceName = '#' . $patientObj->getInsuranceId();
        }

        // ── Load policy via OOP ──
        $policyObj  = new PatientPolicy($conn);
        $policyData = $policyObj->loadByPatientId($patient_id) ?? [];

        // ── Load medical records via OOP ──
        $medObj      = new MedicalRecord($conn);
        $records     = $medObj->getRecordsByPatient($patient_id, 20) ?? [];
        $latest      = $records[0] ?? null;
        $kpi_records = count($records);

        // ── Claim feedback from redirect ──
        $claimStatus = $_GET['claim'] ?? '';
        $claimMsg    = $_GET['msg']   ?? '';

        require_once ROOT . '/app/views/patient/dashboard.php';
        $db->close();
    }
}
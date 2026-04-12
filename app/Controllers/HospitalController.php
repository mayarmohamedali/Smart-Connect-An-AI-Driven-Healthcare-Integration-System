<?php

class HospitalController {

    // ──────────────────────────────────────────────
    // GET /hospital/dashboard
    // ──────────────────────────────────────────────
    public function dashboard(): void {

        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkStaffAuth('HOSPITAL_STAFF'); // redirects if not authed

        $hospital_id = (int) ($auth->getSessionData('hospital_id') ?? 0);
        if ($hospital_id <= 0) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // ── Load hospital via OOP ──
        $hospitalObj = new Hospital($conn);
        $hospitalObj->loadById($hospital_id);
        $hospital_name = $hospitalObj->getName() ?: 'Hospital';

        // ── KPI counts via OOP (uses insurance_hospitals join — no added_by_hospital_id) ──
        $kpi_patients          = $hospitalObj->getKPIPatients();
        $kpi_medical_records   = $hospitalObj->getKPIMedicalRecords();
        $kpi_insured_patients  = $this->fetchInt($conn,
            "SELECT COUNT(DISTINCT p.patient_id)
             FROM patients p
             INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
             WHERE ih.hospital_id = ? AND p.insurance_id IS NOT NULL AND p.is_active = 1",
            "i", [$hospital_id]
        );
        $kpi_recent_admissions = $this->fetchInt($conn,
            "SELECT COUNT(DISTINCT p.patient_id)
             FROM patients p
             INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
             WHERE ih.hospital_id = ?
               AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
               AND p.is_active = 1",
            "i", [$hospital_id]
        );

        // ── Patient list / search via OOP ──
        $patientObj = new Patient($conn);
        $q          = trim($_GET['q'] ?? '');
        $patients   = $patientObj->getPatientsByHospital($hospital_id, $q) ?? [];

        $success_msg = '';
        $error_msg   = '';

        require_once ROOT . '/app/views/hospital/dashboard.php';
        $db->close();
    }

    // ── Helper ───────────────────────────────────
    private function fetchInt(mysqli $conn, string $sql, string $types = '', array $params = []): int {
        $stmt = $conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_row() : null;
        $stmt->close();
        return $row ? (int) $row[0] : 0;
    }
}
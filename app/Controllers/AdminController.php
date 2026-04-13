<?php

class AdminController {

    // GET /admin/dashboard
    public function dashboard(): void {

        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkStaffAuth('ADMIN');

        // KPI counts
        $totalPatients   = (int) $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
        $totalHospitals  = (int) $conn->query("SELECT COUNT(*) FROM hospitals")->fetch_row()[0];
        $totalInsurances = (int) $conn->query("SELECT COUNT(*) FROM medical_insurances")->fetch_row()[0];
        $totalUsers      = (int) $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
        $activeUsers     = (int) $conn->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetch_row()[0];
        $completedPolicies = (int) $conn->query("SELECT COUNT(*) FROM medical_insurances WHERE policy_completed = 1")->fetch_row()[0];
        $pendingPolicies   = (int) $conn->query("SELECT COUNT(*) FROM medical_insurances WHERE policy_completed = 0")->fetch_row()[0];

        // Recent patients
        $recentPatients = [];
        $res = $conn->query("SELECT patient_id, full_name, national_id, phone, gender, created_at FROM patients ORDER BY patient_id DESC LIMIT 5");
        while ($row = $res->fetch_assoc()) $recentPatients[] = $row;

        // Recent users
        $recentUsers = [];
        $res = $conn->query("SELECT u.full_name, r.role_name, u.email, u.is_active FROM users u INNER JOIN roles r ON r.role_id = u.role_id ORDER BY u.user_id DESC LIMIT 4");
        while ($row = $res->fetch_assoc()) $recentUsers[] = $row;

        // Recent medical records
        $recentRecords = [];
        $res = $conn->query("SELECT record_id, patient_id, diagnosis, checkin_date, checkout_date, created_at FROM medical_records ORDER BY record_id DESC LIMIT 5");
        while ($row = $res->fetch_assoc()) $recentRecords[] = $row;

        $latestPatientName    = $recentPatients[0]['full_name'] ?? 'N/A';
        $userActivityRate     = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;
        $policyCompletionRate = $totalInsurances > 0 ? round(($completedPolicies / $totalInsurances) * 100) : 0;

$counts = [
    'totalPatients'   => $totalPatients,
    'totalHospitals'  => $totalHospitals,
    'totalInsurances' => $totalInsurances,
    'totalUsers'      => $totalUsers
];
require_once ROOT . '/app/views/admin/dashboard.php';
        
    }
}
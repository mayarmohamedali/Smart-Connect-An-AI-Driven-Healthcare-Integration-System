<?php

class InsuranceController {

    // ──────────────────────────────────────────────
    // GET|POST /insurance/dashboard
    // ──────────────────────────────────────────────
    public function dashboard(): void {

        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkStaffAuth('INSURANCE_STAFF'); // redirects if not authed

        $insurance_id = (int) ($auth->getSessionData('insurance_id') ?? 0);
        if ($insurance_id <= 0) {
            die('Missing insurance_id in session.');
        }

        // ── Load insurance via OOP ──
        $insuranceObj = new Insurance($conn);
        $insuranceObj->loadById($insurance_id);
        $insurance_name = $insuranceObj->getName() ?: 'Medical Insurance';

        // ── KPI counts via OOP ──
        $kpi_patients        = $insuranceObj->getKPIPatients();
        $kpi_policies_active = $insuranceObj->getKPIActivePolicies();
        $kpi_cases_month     = $insuranceObj->getKPICasesThisMonth();
        $kpi_pending_reviews = $insuranceObj->getKPIPendingReviews();

        // ── Handle Add-Patient POST ──
        $success_msg = '';
        $error_msg   = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_patient') {

            $full_name   = trim($_POST['full_name']   ?? '');
            $national_id = trim($_POST['national_id'] ?? '');
            $phone       = trim($_POST['phone']       ?? '');
            $gender      = trim($_POST['gender']      ?? '');
            $address     = trim($_POST['address']     ?? '');

            if ($full_name === '' || $national_id === '' || $phone === '' || $gender === '' || $address === '') {
                $error_msg = 'Please fill all fields.';
            } elseif (!Validator::validateNationalId($national_id)) {
                $error_msg = 'National ID must be 14 digits.';
            } elseif (!Validator::validatePhone($phone)) {
                $error_msg = 'Phone must be Egyptian format (010/011/012/015 + 8 digits).';
            } else {
                // Check duplicate
                $check = $conn->prepare('SELECT patient_id FROM patients WHERE national_id = ? LIMIT 1');
                $check->bind_param('s', $national_id);
                $check->execute();
                $exists = $check->get_result()->fetch_assoc();
                $check->close();

                if ($exists) {
                    $error_msg = 'Patient already exists with this National ID (Patient ID: ' . (int) $exists['patient_id'] . ').';
                } else {
                    // Create via OOP
                    $patientObj = new Patient($conn);
                    $patientObj->setFullName($full_name);
                    $patientObj->setNationalId($national_id);
                    $patientObj->setPhone($phone);
                    $patientObj->setGender($gender);
                    $patientObj->setAddress($address);

                    if ($patientObj->create(null, $insurance_id)) {
                        $new_id = $patientObj->getPatientId();
                        header('Location: ' . BASE_URL . '/insurance/dashboard?added=' . $new_id . '#patients');
                        exit;
                    } else {
                        $error_msg = 'Failed to create patient.';
                    }
                }
            }
        }

        if (isset($_GET['added'])) {
            $success_msg = 'Patient added successfully ✅ (ID: ' . (int) $_GET['added'] . ') under <b>' . Validator::sanitizeInput($insurance_name) . '</b>.';
        }

        // ── Patient list / search via OOP ──
        $patientObj = new Patient($conn);
        $q          = trim($_GET['q'] ?? '');
        $patients   = $patientObj->getPatientsByInsurance($insurance_id, $q) ?? [];

        require_once ROOT . '/app/views/insurance/dashboard.php';
        $db->close();
    }
}
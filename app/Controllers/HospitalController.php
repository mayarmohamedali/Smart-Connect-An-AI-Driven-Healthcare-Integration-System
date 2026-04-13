<?php

class HospitalController {

    // ── GET /hospital/dashboard ──────────────────────────────────────────────
    public function dashboard(): void {
        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkStaffAuth('HOSPITAL_STAFF');

        $hospital_id = (int)($auth->getSessionData('hospital_id') ?? 0);
        if ($hospital_id <= 0) { header('Location: ' . BASE_URL . '/auth/login'); exit; }

        $hospitalObj   = new Hospital($conn);
        $hospitalObj->loadById($hospital_id);
        $hospital_name = $hospitalObj->getName() ?: 'Hospital';

        $kpi_patients          = $hospitalObj->getKPIPatients();
        $kpi_medical_records   = $hospitalObj->getKPIMedicalRecords();
        $kpi_insured_patients  = $this->fetchInt($conn,
            "SELECT COUNT(DISTINCT p.patient_id) FROM patients p
             INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
             WHERE ih.hospital_id = ? AND p.insurance_id IS NOT NULL AND p.is_active = 1", "i", [$hospital_id]);
        $kpi_recent_admissions = $this->fetchInt($conn,
            "SELECT COUNT(DISTINCT p.patient_id) FROM patients p
             INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
             WHERE ih.hospital_id = ? AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND p.is_active = 1", "i", [$hospital_id]);

        $patientObj = new Patient($conn);
        $q          = trim($_GET['q'] ?? '');
        $patients   = $patientObj->getPatientsByHospital($hospital_id, $q) ?? [];
        $success_msg = ''; $error_msg = '';

        require_once ROOT . '/app/views/hospital/dashboard.php';
       
    }

    // ── GET /hospital/viewRecords ────────────────────────────────────────────
    public function viewRecords(): void {
        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkStaffAuth('HOSPITAL_STAFF');

        $hospital_id = (int)($auth->getSessionData('hospital_id') ?? 0);
        $patient_id  = (int)($_GET['patient_id'] ?? 0);
        if ($patient_id <= 0) die('Invalid patient_id');

        // Fetch patient with contract check
        $stmt = $conn->prepare("
            SELECT p.*, mi.name AS insurance_name FROM patients p
            LEFT JOIN medical_insurances mi ON p.insurance_id = mi.insurance_id
            JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id AND ih.hospital_id = ?
            WHERE p.patient_id = ? LIMIT 1
        ");
        $stmt->bind_param('ii', $hospital_id, $patient_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$patient) die('Patient not found or not accessible by your hospital.');

        // Handle delete
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_record') {
            $rid = (int)($_POST['record_id'] ?? 0);
            if ($rid > 0) {
                $del = $conn->prepare('DELETE FROM medical_records WHERE record_id=? AND patient_id=?');
                $del->bind_param('ii', $rid, $patient_id);
                $del->execute(); $del->close();
            }
            header('Location: ' . BASE_URL . '/hospital/viewRecords?patient_id=' . $patient_id);
            exit;
        }

        // List records
        $records = [];
        $stmt = $conn->prepare('SELECT * FROM medical_records WHERE patient_id = ? ORDER BY record_id DESC');
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $records[] = $row;
        $stmt->close();

        // Selected record detail
        $selected_record = null;
        if (isset($_GET['record_id'])) {
            $rid = (int)$_GET['record_id'];
            if ($rid > 0) {
                $stmt = $conn->prepare('SELECT * FROM medical_records WHERE record_id=? AND patient_id=? LIMIT 1');
                $stmt->bind_param('ii', $rid, $patient_id);
                $stmt->execute();
                $selected_record = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        }

        require_once ROOT . '/app/views/hospital/viewRecords.php';
        
    }


    private function fetchInt(mysqli $conn, string $sql, string $types, array $params): int {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_row();
    $stmt->close();

    return (int)($res[0] ?? 0);
}

    // ── GET|POST /hospital/addRecord ─────────────────────────────────────────
    public function addRecord(): void {
        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkStaffAuth('HOSPITAL_STAFF');

        $hospital_id = (int)($auth->getSessionData('hospital_id') ?? 0);
        $patient_id  = (int)($_GET['patient_id'] ?? 0);
        if ($patient_id <= 0) die('Invalid patient_id');

        // Patient with contract check
        $stmt = $conn->prepare("
            SELECT p.patient_id, p.full_name, p.national_id, p.insurance_id, mi.name AS insurance_name
            FROM patients p
            JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id AND ih.hospital_id = ?
            LEFT JOIN medical_insurances mi ON mi.insurance_id = p.insurance_id
            WHERE p.patient_id = ? LIMIT 1
        ");
        $stmt->bind_param('ii', $hospital_id, $patient_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$patient) die("This patient's insurance is not contracted with your hospital.");

        $insuranceName = $patient['insurance_name'] ?? '';
        $insuranceId   = (int)($patient['insurance_id'] ?? 0);
        $success = ''; $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $record = new MedicalRecord($conn);
            $record->setPatientId($patient_id);
            $record->setAge((int)Validator::nullIfEmpty($_POST['age'] ?? null));
            $record->setCheckinDate(Validator::nullIfEmpty($_POST['checkin_date']  ?? null));
            $record->setCheckoutDate(Validator::nullIfEmpty($_POST['checkout_date'] ?? null));
            $record->setLabValues(
                Validator::nullIfEmpty($_POST['cbc_hb1'] ?? null), Validator::nullIfEmpty($_POST['cbc_tlc1'] ?? null),
                Validator::nullIfEmpty($_POST['cbc_plat1'] ?? null), Validator::nullIfEmpty($_POST['blood_uria1'] ?? null),
                Validator::nullIfEmpty($_POST['blood_creatinine1'] ?? null), Validator::nullIfEmpty($_POST['cbc_hb2'] ?? null),
                Validator::nullIfEmpty($_POST['cbc_tlc2'] ?? null), Validator::nullIfEmpty($_POST['cbc_plat2'] ?? null),
                Validator::nullIfEmpty($_POST['blood_uria2'] ?? null), Validator::nullIfEmpty($_POST['blood_creatinine2'] ?? null)
            );
            $record->setBMI(Validator::nullIfEmpty($_POST['bmi'] ?? null));
            $record->setGlucose(Validator::nullIfEmpty($_POST['glucose'] ?? null));
            $record->setCholesterolLevel(Validator::nullIfEmpty($_POST['cholesterol_level'] ?? null));
            $record->setSystolicBP(Validator::nullIfEmpty($_POST['systolic_bp'] ?? null));
            $record->setAggregates(
                Validator::nullIfEmpty($_POST['month'] ?? null), Validator::nullIfEmpty($_POST['year'] ?? null),
                Validator::nullIfEmpty($_POST['day_of_week'] ?? null), Validator::nullIfEmpty($_POST['admission_count'] ?? null),
                Validator::nullIfEmpty($_POST['avg_length_stay'] ?? null)
            );
            $smokingRaw = $_POST['smoking_status'] ?? '';
            $record->setLifestyle(
                Validator::nullIfEmpty($_POST['length_of_stay'] ?? null),
                $smokingRaw === '' ? null : (int)$smokingRaw,
                Validator::nullIfEmpty($_POST['physical_activity_level'] ?? null),
                Validator::nullIfEmpty($_POST['diet_quality'] ?? null),
                Validator::boolToInt($_POST['alcohol_consumption'] ?? 0),
                Validator::nullIfEmpty($_POST['sleep_hours'] ?? null)
            );
            $record->setRiskFlags(
                Validator::boolToInt($_POST['has_diabetes'] ?? 0), Validator::boolToInt($_POST['has_hypertension'] ?? 0),
                Validator::boolToInt($_POST['has_kidney_disease'] ?? 0), Validator::boolToInt($_POST['has_heart_disease'] ?? 0)
            );
            $record->setRiskScores(
                Validator::nullIfEmpty($_POST['stress_level'] ?? null), Validator::nullIfEmpty($_POST['family_history'] ?? null),
                Validator::nullIfEmpty($_POST['medications_count'] ?? null), Validator::nullIfEmpty($_POST['risk_score'] ?? null),
                Validator::nullIfEmpty($_POST['symptom_burden'] ?? null), Validator::nullIfEmpty($_POST['seasonal_weight'] ?? null)
            );
            $record->setSymptoms(
                Validator::nullIfEmpty($_POST['fever'] ?? null), Validator::nullIfEmpty($_POST['cough'] ?? null),
                Validator::nullIfEmpty($_POST['fatigue'] ?? null),
                isset($_POST['chest_pain']) ? 1 : null,
                Validator::nullIfEmpty($_POST['shortness_of_breath'] ?? null),
                isset($_POST['headache']) ? 1 : null
            );
            $record->setDiagnosis(Validator::nullIfEmpty($_POST['diagnosis'] ?? null));
            $record->setDiseaseCategory(Validator::nullIfEmpty($_POST['disease_category'] ?? null));

            if ($record->create()) {
                $success = 'Medical record added successfully ✅';
            } else {
                $error = 'Failed to create medical record. Please check all fields and try again.';
            }
        }

        require_once ROOT . '/app/views/hospital/addRecord.php';
       
    }

    // ── GET|POST /hospital/editRecord ────────────────────────────────────────
    public function editRecord(): void {
        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->checkStaffAuth('HOSPITAL_STAFF');

        $hospital_id = (int)($auth->getSessionData('hospital_id') ?? 0);
        $patient_id  = (int)($_GET['patient_id'] ?? 0);
        $record_id   = (int)($_GET['record_id']  ?? 0);
        if ($patient_id <= 0 || $record_id <= 0) die('Missing patient_id or record_id.');

        // Patient check
        $stmt = $conn->prepare("
            SELECT p.patient_id, p.full_name, p.national_id FROM patients p
            JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id AND ih.hospital_id = ?
            WHERE p.patient_id = ? LIMIT 1
        ");
        $stmt->bind_param('ii', $hospital_id, $patient_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$patient) die('Patient not found or not accessible by your hospital.');

        // Fetch record
        $stmt = $conn->prepare('SELECT * FROM medical_records WHERE record_id = ? AND patient_id = ? LIMIT 1');
        $stmt->bind_param('ii', $record_id, $patient_id);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$record) die('Record not found for this patient.');

        $activityOptions = ['Low','Moderate','High'];
        $dietOptions     = ['Poor','Average','Good'];
        $daysOfWeek      = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $error = ''; $success = isset($_GET['success']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_record') {
            $medObj = new MedicalRecord($conn);
            // Load existing to set patient_id and record_id
            $medObj->loadById($record_id);
            // Now call update — reuse the flat SQL approach from original for simplicity
            $this->performUpdate($conn, $record_id, $patient_id, $_POST);
            header('Location: ' . BASE_URL . '/hospital/editRecord?patient_id=' . $patient_id . '&record_id=' . $record_id . '&success=1');
            exit;
        }

        require_once ROOT . '/app/views/hospital/editRecord.php';
        
    }

 
    private function performUpdate(mysqli $conn, int $record_id, int $patient_id, array $p): void {

        function ni2($v) {
            $v = trim((string)($v ?? ''));
            return $v === '' ? null : $v;
        }

        // Raw values
        $hb1=$p['cbc_hb1']??null; $hb2=$p['cbc_hb2']??null;
        $tl1=$p['cbc_tlc1']??null; $tl2=$p['cbc_tlc2']??null;
        $pl1=$p['cbc_plat1']??null; $pl2=$p['cbc_plat2']??null;
        $ur1=$p['blood_uria1']??null; $ur2=$p['blood_uria2']??null;
        $cr1=$p['blood_creatinine1']??null; $cr2=$p['blood_creatinine2']??null;

        // Calculations
        $avg_hb  = ($hb1!==null&&$hb2!==null)  ? round(((float)$hb1+(float)$hb2)/2,3)  : null;
        $avg_tlc = ($tl1!==null&&$tl2!==null)  ? round(((float)$tl1+(float)$tl2)/2,3)  : null;
        $avg_plat= ($pl1!==null&&$pl2!==null)  ? round(((float)$pl1+(float)$pl2)/2,3)  : null;
        $avg_urea= ($ur1!==null&&$ur2!==null)  ? round(((float)$ur1+(float)$ur2)/2,3)  : null;
        $avg_crn = ($cr1!==null&&$cr2!==null)  ? round(((float)$cr1+(float)$cr2)/2,3)  : null;

        $d_hb  = ($hb1!==null&&$hb2!==null) ? round((float)$hb2-(float)$hb1,3)  : null;
        $d_tlc = ($tl1!==null&&$tl2!==null) ? round((float)$tl2-(float)$tl1,3)  : null;
        $d_plat= ($pl1!==null&&$pl2!==null) ? round((float)$pl2-(float)$pl1,3)  : null;
        $d_uria= ($ur1!==null&&$ur2!==null) ? round((float)$ur2-(float)$ur1,3)  : null;
        $d_crn = ($cr1!==null&&$cr2!==null) ? round((float)$cr2-(float)$cr1,3)  : null;

        $smoke = ($p['smoking_status']??'') === '' ? null : (int)$p['smoking_status'];

        // ✅ IMPORTANT: Convert everything to variables
        $age = (int)($p['age'] ?? 0);
        $checkin_date = ni2($p['checkin_date']);
        $checkout_date = ni2($p['checkout_date']);
        $length_of_stay = ni2($p['length_of_stay']);
        $avg_length_stay = ni2($p['avg_length_stay']);
        $month = ni2($p['month']);
        $year = ni2($p['year']);
        $day_of_week = ni2($p['day_of_week']);
        $admission_count = ni2($p['admission_count']);

        $bmi = ni2($p['bmi']);
        $glucose = ni2($p['glucose']);
        $cholesterol = ni2($p['cholesterol_level']);
        $systolic_bp = ni2($p['systolic_bp']);

        $physical = ni2($p['physical_activity_level']);
        $diet = ni2($p['diet_quality']);
        $sleep = ni2($p['sleep_hours']);
        $alcohol = isset($p['alcohol_consumption']) ? 1 : 0;

        $stress = ni2($p['stress_level']);
        $family = ni2($p['family_history']);
        $medications = ni2($p['medications_count']);
        $risk = ni2($p['risk_score']);
        $symptom = ni2($p['symptom_burden']);
        $season = ni2($p['seasonal_weight']);

        $fever = ni2($p['fever']);
        $cough = ni2($p['cough']);
        $fatigue = ni2($p['fatigue']);
        $breath = ni2($p['shortness_of_breath']);

        $chest = isset($p['chest_pain']) ? 1 : 0;
        $headache = isset($p['headache']) ? 1 : 0;

        $diabetes = isset($p['has_diabetes']) ? 1 : 0;
        $hypertension = isset($p['has_hypertension']) ? 1 : 0;
        $kidney = isset($p['has_kidney_disease']) ? 1 : 0;
        $heart = isset($p['has_heart_disease']) ? 1 : 0;

        $diagnosis = ni2($p['diagnosis']);
        $disease = ni2($p['disease_category']);

        // SQL
        $sql = "UPDATE medical_records SET
            age=?,checkin_date=?,checkout_date=?,length_of_stay=?,avg_length_stay=?,
            month=?,year=?,day_of_week=?,admission_count=?,
            cbc_hb1=?,cbc_tlc1=?,cbc_plat1=?,blood_uria1=?,blood_creatinine1=?,
            cbc_hb2=?,cbc_tlc2=?,cbc_plat2=?,blood_uria2=?,blood_creatinine2=?,
            avg_hb=?,avg_tlc=?,avg_platelets=?,avg_urea=?,avg_creatinine=?,
            delta_hb=?,delta_tlc=?,delta_plat=?,delta_uria=?,delta_creatinine=?,
            bmi=?,glucose=?,cholesterol_level=?,systolic_bp=?,
            smoking_status=?,physical_activity_level=?,diet_quality=?,sleep_hours=?,alcohol_consumption=?,
            stress_level=?,family_history=?,medications_count=?,risk_score=?,symptom_burden=?,seasonal_weight=?,
            fever=?,cough=?,fatigue=?,shortness_of_breath=?,chest_pain=?,headache=?,
            has_diabetes=?,has_hypertension=?,has_kidney_disease=?,has_heart_disease=?,
            diagnosis=?,disease_category=?
            WHERE record_id=? AND patient_id=?";

        $stmt = $conn->prepare($sql);

        $types = str_repeat('s',56).'ii';

        $stmt->bind_param($types,
            $age,$checkin_date,$checkout_date,$length_of_stay,$avg_length_stay,
            $month,$year,$day_of_week,$admission_count,
            $hb1,$tl1,$pl1,$ur1,$cr1,$hb2,$tl2,$pl2,$ur2,$cr2,
            $avg_hb,$avg_tlc,$avg_plat,$avg_urea,$avg_crn,
            $d_hb,$d_tlc,$d_plat,$d_uria,$d_crn,
            $bmi,$glucose,$cholesterol,$systolic_bp,
            $smoke,$physical,$diet,$sleep,$alcohol,
            $stress,$family,$medications,$risk,$symptom,$season,
            $fever,$cough,$fatigue,$breath,$chest,$headache,
            $diabetes,$hypertension,$kidney,$heart,
            $diagnosis,$disease,
            $record_id,$patient_id
        );

        $stmt->execute();
        $stmt->close();
    }

}
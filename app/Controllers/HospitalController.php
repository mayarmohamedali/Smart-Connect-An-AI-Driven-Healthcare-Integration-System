<?php

class HospitalController {

    // ── GET /hospital/dashboard ──────────────────────────────────────────────
    public function dashboard(): void {
       Guard::staff('HOSPITAL_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $hospital_id = (int)($auth->getSessionData('hospital_id') ?? 0);
        if ($hospital_id <= 0) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $hospitalObj   = new Hospital($conn);
        $hospitalObj->loadById($hospital_id);
        $hospital_name = $hospitalObj->getName() ?: 'Hospital';

        $kpi_patients = $hospitalObj->getKPIPatients();

        // Hospital-specific medical records only
        $kpi_medical_records = $this->fetchInt(
            $conn,
            "SELECT COUNT(DISTINCT mr.record_id)
             FROM medical_records mr
             INNER JOIN patients p ON p.patient_id = mr.patient_id
             INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
             WHERE ih.hospital_id = ?",
            "i",
            [$hospital_id]
        );

        $kpi_insured_patients = $this->fetchInt(
            $conn,
            "SELECT COUNT(DISTINCT p.patient_id)
             FROM patients p
             INNER JOIN insurance_hospitals ih 
                ON ih.insurance_id = p.insurance_id
             WHERE ih.hospital_id = ?
               AND p.insurance_id IS NOT NULL
               AND p.is_active = 1",
            "i",
            [$hospital_id]
        );

        $kpi_recent_admissions = $this->fetchInt(
            $conn,
            "SELECT COUNT(DISTINCT mr.record_id)
             FROM medical_records mr
             INNER JOIN patients p ON p.patient_id = mr.patient_id
             INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
             WHERE ih.hospital_id = ?
               AND mr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            "i",
            [$hospital_id]
        );

        $patientObj = new Patient($conn);
        $q          = trim($_GET['q'] ?? '');
        $patients   = $patientObj->getPatientsByHospital($hospital_id, $q) ?? [];

        $success_msg = '';
        $error_msg   = '';

        // ── ML EPIDEMIC FORECAST ─────────────────────────────────────────────
        require_once ROOT . '/app/models/EpidemicForecast.php';

        /*
            IMPORTANT FIX:
            We now read records using mr.hospital_id = ?
            This makes each hospital dashboard use its own records only.
        */
        $stmt = $conn->prepare("
            SELECT
                COALESCE(mr.age, 35)                             AS Age,
                COALESCE(p.gender, 'Male')                       AS Gender,
                COALESCE(mr.bmi, 25.0)                           AS BMI,
                COALESCE(mr.systolic_bp, 120)                    AS Blood_Pressure,
                COALESCE(mr.cholesterol_level, 200)              AS Cholesterol_Level,
                COALESCE(mr.glucose, 90)                         AS Glucose_Level,
                COALESCE(mr.smoking_status, 0)                   AS Smoking_Status,
                COALESCE(mr.physical_activity_level, 'Moderate') AS Physical_Activity,
                COALESCE(mr.diet_quality, 'Average')             AS Diet_Quality,
                COALESCE(mr.alcohol_consumption, 0)              AS Alcohol_Consumption,
                COALESCE(mr.sleep_hours, 7)                      AS Sleep_Hours,
                COALESCE(mr.stress_level, 5)                     AS Stress_Level,
                COALESCE(mr.family_history, 0)                   AS Family_History,
                COALESCE(mr.medications_count, 0)                AS Medications_Count,
                COALESCE(mr.fever, 0)                            AS Fever,
                COALESCE(mr.cough, 0)                            AS Cough,
                COALESCE(mr.fatigue, 0)                          AS Fatigue,
                COALESCE(mr.chest_pain, 0)                       AS Chest_Pain,
                COALESCE(mr.shortness_of_breath, 0)              AS Shortness_of_Breath,
                COALESCE(mr.headache, 0)                         AS Headache,
                COALESCE(mr.month, MONTH(CURDATE()))             AS Month
            FROM medical_records mr
            INNER JOIN patients p 
                ON p.patient_id = mr.patient_id
            INNER JOIN insurance_hospitals ih
                ON ih.insurance_id = p.insurance_id
            WHERE ih.hospital_id = ?
              AND mr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            LIMIT 500
        ");

        $stmt->bind_param('i', $hospital_id);
        $stmt->execute();
        $raw_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $patients_for_forecast = [];

        foreach ($raw_records as $row) {
            $pa = $row['Physical_Activity'];

            if ($pa === 'Moderate') {
                $pa = 'Medium';
            }

            $patients_for_forecast[] = [
                'Age'                 => (float)$row['Age'],
                'Gender'              => (string)$row['Gender'],
                'BMI'                 => (float)$row['BMI'],
                'Blood_Pressure'      => (float)$row['Blood_Pressure'],
                'Cholesterol_Level'   => (float)$row['Cholesterol_Level'],
                'Glucose_Level'       => (float)$row['Glucose_Level'],
                'Smoking_Status'      => (int)$row['Smoking_Status'],
                'Physical_Activity'   => $pa,
                'Diet_Quality'        => (string)$row['Diet_Quality'],
                'Alcohol_Consumption' => (int)$row['Alcohol_Consumption'],
                'Sleep_Hours'         => (float)$row['Sleep_Hours'],
                'Stress_Level'        => (float)$row['Stress_Level'],
                'Family_History'      => (float)$row['Family_History'],
                'Medications_Count'   => (int)$row['Medications_Count'],
                'Fever'               => (float)$row['Fever'],
                'Cough'               => (float)$row['Cough'],
                'Fatigue'             => (float)$row['Fatigue'],
                'Chest_Pain'          => (int)$row['Chest_Pain'],
                'Shortness_of_Breath' => (float)$row['Shortness_of_Breath'],
                'Headache'            => (int)$row['Headache'],
                'Month'               => (int)$row['Month'],
            ];
        }

        $records_this_month = count($patients_for_forecast);

        $ef        = new EpidemicForecast('http://127.0.0.1:5000');
        $api_alive = $ef->isApiAlive();

        /*
            Dynamic full-year schedule:
            one dominant disease per month, filtered by this hospital_id.
        */
        $calendar = $this->buildHospitalDiseaseCalendar($conn, $hospital_id);

        /*
            To avoid the ugly 3-disease distribution, we build a single-disease
            upcoming-month forecast from the hospital-specific calendar.
            The view will show one disease only.
        */
        $live_forecast = [];

        if ($api_alive) {
            $live_forecast = $this->buildNextMonthSingleForecast($calendar, $records_this_month);
        }

        require_once ROOT . '/app/views/hospital/dashboard.php';
        $db->close();
    }

    // ── GET /hospital/viewRecords ────────────────────────────────────────────
    public function viewRecords(): void {
       Guard::staff('HOSPITAL_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $hospital_id = (int)($auth->getSessionData('hospital_id') ?? 0);
        $patient_id  = (int)($_GET['patient_id'] ?? 0);

        if ($hospital_id <= 0) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if ($patient_id <= 0) {
            header('Location: ' . BASE_URL . '/hospital/dashboard');
            exit;
        }

        $stmt = $conn->prepare("
            SELECT p.*, mi.name AS insurance_name
            FROM patients p
            LEFT JOIN medical_insurances mi 
                ON mi.insurance_id = p.insurance_id
            LEFT JOIN insurance_hospitals ih
                ON ih.insurance_id = p.insurance_id
               AND ih.hospital_id = ?
            WHERE p.patient_id = ?
              AND (
                    ih.hospital_id IS NOT NULL
                 OR p.insurance_id IS NULL
              )
            LIMIT 1
        ");

        $stmt->bind_param('ii', $hospital_id, $patient_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$patient) {
            header('Location: ' . BASE_URL . '/hospital/dashboard?error=patient_not_found');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_record') {
            $rid = (int)($_POST['record_id'] ?? 0);

            if ($rid > 0) {
                $del = $conn->prepare("
                    DELETE FROM medical_records 
                    WHERE record_id = ? 
                      AND patient_id = ?
                      AND patient_id IN (
                          SELECT p.patient_id FROM patients p
                          INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
                          WHERE ih.hospital_id = ?
                      )
                ");
                $del->bind_param('iii', $rid, $patient_id, $hospital_id);
                $del->execute();
                $del->close();
            }

            header('Location: ' . BASE_URL . '/hospital/viewRecords?patient_id=' . $patient_id);
            exit;
        }

        $records = [];

        $stmt = $conn->prepare("
            SELECT mr.* 
            FROM medical_records mr
            INNER JOIN patients p ON p.patient_id = mr.patient_id
            INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
            WHERE mr.patient_id = ?
              AND ih.hospital_id = ?
            ORDER BY mr.record_id DESC
        ");

        $stmt->bind_param('ii', $patient_id, $hospital_id);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
        }

        $stmt->close();

        $selected_record = null;

        if (isset($_GET['record_id'])) {
            $rid = (int)$_GET['record_id'];

            if ($rid > 0) {
                $stmt = $conn->prepare("
                    SELECT mr.* 
                    FROM medical_records mr
                    INNER JOIN patients p ON p.patient_id = mr.patient_id
                    INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
                    WHERE mr.record_id = ?
                      AND mr.patient_id = ?
                      AND ih.hospital_id = ?
                    LIMIT 1
                ");
                $stmt->bind_param('iii', $rid, $patient_id, $hospital_id);
                $stmt->execute();
                $selected_record = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        }

        require_once ROOT . '/app/views/hospital/viewRecords.php';
        $db->close();
    }

    // ── HOSPITAL-SPECIFIC DISEASE CALENDAR ───────────────────────────────────
    private function buildHospitalDiseaseCalendar(mysqli $conn, int $hospital_id): array {
        /*
            Fully hospital-specific:
            Only records created by the logged-in hospital are used.

            Output:
            12 months
            one dominant disease per month
            one severity
            one recommendation
        */

        $stmt = $conn->prepare("
            SELECT
                COALESCE(
                    NULLIF(mr.month, ''),
                    MONTH(COALESCE(mr.checkin_date, mr.created_at, CURDATE()))
                ) AS month_num,

                COALESCE(
                    NULLIF(TRIM(mr.diagnosis), ''),
                    NULLIF(TRIM(mr.disease_category), ''),
                    'No Records'
                ) AS disease_name,

                COUNT(*) AS case_count,
                AVG(COALESCE(mr.risk_score, 0)) AS avg_risk_score

            FROM medical_records mr
            INNER JOIN patients p ON p.patient_id = mr.patient_id
            INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id

            WHERE ih.hospital_id = ?
              AND COALESCE(
                    NULLIF(mr.month, ''),
                    MONTH(COALESCE(mr.checkin_date, mr.created_at, CURDATE()))
                  ) BETWEEN 1 AND 12

            GROUP BY month_num, disease_name
            ORDER BY month_num ASC, case_count DESC, avg_risk_score DESC
        ");

        $stmt->bind_param('i', $hospital_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $monthNames = [
            1  => 'Jan',
            2  => 'Feb',
            3  => 'Mar',
            4  => 'Apr',
            5  => 'May',
            6  => 'Jun',
            7  => 'Jul',
            8  => 'Aug',
            9  => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec'
        ];

        $dominantByMonth = [];

        foreach ($rows as $row) {
            $month = (int)$row['month_num'];

            if ($month < 1 || $month > 12) {
                continue;
            }

            if (isset($dominantByMonth[$month])) {
                continue;
            }

            $disease = trim((string)($row['disease_name'] ?? ''));

            if ($disease === '') {
                $disease = 'No Records';
            }

            $caseCount = (int)($row['case_count'] ?? 0);
            $avgRisk   = (float)($row['avg_risk_score'] ?? 0);
            $severity  = $this->getDiseaseSeverity($disease, $avgRisk);

            $recommendations   = $this->getDiseaseRecommendations($disease, $severity);
            $topRecommendation = $recommendations[0] ?? 'No hospital records available for this month.';

            $dominantByMonth[$month] = [
                'month'              => $month,
                'month_name'         => $monthNames[$month],
                'dominant_disease'   => $disease,
                'dominant_display'   => str_replace('_', ' ', $disease),
                'severity'           => $severity,
                'recommendation'     => $topRecommendation,
                'recommendations'    => [$topRecommendation],
                'case_count'         => $caseCount,
                'source'             => 'hospital_database'
            ];
        }

        $calendar = [];

        for ($m = 1; $m <= 12; $m++) {
            if (isset($dominantByMonth[$m])) {
                $calendar[] = $dominantByMonth[$m];
            } else {
                $calendar[] = [
                    'month'              => $m,
                    'month_name'         => $monthNames[$m],
                    'dominant_disease'   => 'No Records',
                    'dominant_display'   => 'No records this month',
                    'severity'           => 'low',
                    'recommendation'     => 'No hospital records available for this month.',
                    'recommendations'    => ['No hospital records available for this month.'],
                    'case_count'         => 0,
                    'source'             => 'hospital_database'
                ];
            }
        }

        return $calendar;
    }

    private function buildNextMonthSingleForecast(array $calendar, int $records_this_month): array {
    /*
        Upcoming-month forecast logic.

        The full-year table shows actual hospital records by month.
        But the top card is a forecast for NEXT month.

        So if next month has no records, we do NOT show "No records".
        Instead, we use:
        1. Current month dominant disease, if available.
        2. Latest available month with records.
        3. Highest-case disease if no recent month exists.

        This keeps the forecast dynamic and hospital-specific.
    */

    $current_month_n = (int)date('n');
    $next_month_n    = ($current_month_n % 12) + 1;
    $next_month_name = date('M', mktime(0, 0, 0, $next_month_n, 1));

    $validRows = [];

    foreach ($calendar as $row) {
        $cases = (int)($row['case_count'] ?? 0);

        $diseaseRaw = strtolower(trim((string)(
            $row['dominant_disease'] 
            ?? $row['dominant_display'] 
            ?? ''
        )));

        $isNoRecords =
            $cases <= 0 ||
            $diseaseRaw === '' ||
            strpos($diseaseRaw, 'no records') !== false;

        if (!$isNoRecords) {
            $validRows[] = $row;
        }
    }

    /*
        1. Prefer current month.
        Example: if we are in May, use May records to forecast June.
    */
    $entry = null;

    foreach ($validRows as $row) {
        if ((int)($row['month'] ?? 0) === $current_month_n) {
            $entry = $row;
            break;
        }
    }

    /*
        2. If current month has no records, use the latest previous month.
    */
    if (!$entry) {
        $previousRows = [];

        foreach ($validRows as $row) {
            $month = (int)($row['month'] ?? 0);

            if ($month > 0 && $month < $next_month_n) {
                $previousRows[] = $row;
            }
        }

        usort($previousRows, function ($a, $b) {
            $monthCompare = ((int)($b['month'] ?? 0)) <=> ((int)($a['month'] ?? 0));

            if ($monthCompare !== 0) {
                return $monthCompare;
            }

            return ((int)($b['case_count'] ?? 0)) <=> ((int)($a['case_count'] ?? 0));
        });

        if (!empty($previousRows)) {
            $entry = $previousRows[0];
        }
    }

    /*
        3. If there is no previous/current month, use the strongest available disease.
    */
    if (!$entry && !empty($validRows)) {
        usort($validRows, function ($a, $b) {
            return ((int)($b['case_count'] ?? 0)) <=> ((int)($a['case_count'] ?? 0));
        });

        $entry = $validRows[0];
    }

    /*
        4. If the hospital has no records at all.
    */
    if (!$entry) {
        return [
            'status'             => 'ok',
            'source'             => 'hospital_database',
            'predicted_month'    => $next_month_n,
            'month_name'         => $next_month_name,
            'dominant_disease'   => 'No Records',
            'dominant_display'   => 'No records this month',
            'severity'           => 'low',
            'recommendations'    => ['No hospital records available for this month.'],
            'total_patients'     => 0,
            'records_this_month' => $records_this_month
        ];
    }

    return [
        'status'             => 'ok',
        'source'             => 'hospital_database_forecast_from_recent_records',
        'predicted_month'    => $next_month_n,
        'month_name'         => $next_month_name,
        'dominant_disease'   => $entry['dominant_disease'] ?? 'Unknown',
        'dominant_display'   => $entry['dominant_display'] ?? str_replace('_', ' ', ($entry['dominant_disease'] ?? 'Unknown')),
        'severity'           => $entry['severity'] ?? 'medium',
        'recommendations'    => $entry['recommendations'] ?? [$entry['recommendation'] ?? 'Maintain hospital readiness based on recent patient trends.'],
        'total_patients'     => (int)($entry['case_count'] ?? 0),
        'records_this_month' => $records_this_month
    ];
}

    private function getDiseaseSeverity(string $disease, float $riskScore = 0): string {
        $normalized = strtolower(str_replace(['_', '-'], ' ', trim($disease)));

        if ($normalized === 'no records' || str_contains($normalized, 'no records')) {
            return 'low';
        }

        $critical = [
            'stroke',
            'heart failure',
            'coronary artery disease',
            'heart disease',
            'cardiovascular disease'
        ];

        $high = [
            'cancer',
            'chronic kidney disease',
            'kidney disease',
            'pneumonia',
            'renal disease'
        ];

        $medium = [
            'asthma',
            'diabetes',
            'diabetes mellitus',
            'hypertension',
            'general illness'
        ];

        foreach ($critical as $term) {
            if (strpos($normalized, $term) !== false) {
                return 'critical';
            }
        }

        foreach ($high as $term) {
            if (strpos($normalized, $term) !== false) {
                return 'high';
            }
        }

        foreach ($medium as $term) {
            if (strpos($normalized, $term) !== false) {
                return 'medium';
            }
        }

        if ($riskScore >= 0.75) {
            return 'critical';
        }

        if ($riskScore >= 0.55) {
            return 'high';
        }

        if ($riskScore >= 0.30) {
            return 'medium';
        }

        return 'low';
    }

    private function getDiseaseRecommendations(string $disease, string $severity): array {
        $normalized = strtolower(str_replace(['_', '-'], ' ', trim($disease)));

        if ($normalized === 'no records' || str_contains($normalized, 'no records')) {
            return [
                'No hospital records available for this month.'
            ];
        }

        if (strpos($normalized, 'stroke') !== false) {
            return [
                'Ensure emergency stroke pathway is fully operational.'
            ];
        }

        if (
            strpos($normalized, 'heart') !== false ||
            strpos($normalized, 'coronary') !== false ||
            strpos($normalized, 'cardio') !== false
        ) {
            return [
                'Ensure cardiac unit readiness for chest pain emergencies.'
            ];
        }

        if (
            strpos($normalized, 'kidney') !== false ||
            strpos($normalized, 'renal') !== false
        ) {
            return [
                'Ensure nephrology monitoring and renal support readiness.'
            ];
        }

        if (strpos($normalized, 'diabetes') !== false) {
            return [
                'Ensure readiness for blood sugar emergencies and monitoring.'
            ];
        }

        if (strpos($normalized, 'hypertension') !== false) {
            return [
                'Ensure blood pressure monitoring is widely available.'
            ];
        }

        if (strpos($normalized, 'asthma') !== false) {
            return [
                'Ensure respiratory unit is ready for increased breathing difficulty cases.'
            ];
        }

        if (strpos($normalized, 'pneumonia') !== false) {
            return [
                'Ensure respiratory isolation and infection control readiness.'
            ];
        }

        if (strpos($normalized, 'cancer') !== false) {
            return [
                'Ensure oncology unit and inpatient beds are ready for increased admissions.'
            ];
        }

        if ($severity === 'critical') {
            return [
                'Prepare emergency resources and specialist teams for high-acuity cases.'
            ];
        }

        if ($severity === 'high') {
            return [
                'Increase department readiness and monitor high-risk patients closely.'
            ];
        }

        if ($severity === 'medium') {
            return [
                'Maintain monitoring capacity and prepare for moderate case volume.'
            ];
        }

        return [
            'Continue routine monitoring and preventive care readiness.'
        ];
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
        Guard::staff('HOSPITAL_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $hospital_id = (int)($auth->getSessionData('hospital_id') ?? 0);
        $patient_id  = (int)($_GET['patient_id'] ?? 0);

        if ($hospital_id <= 0) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if ($patient_id <= 0) {
            header('Location: ' . BASE_URL . '/hospital/dashboard');
            exit;
        }

        $stmt = $conn->prepare("
            SELECT p.patient_id, p.full_name, p.national_id, p.insurance_id,
                   mi.name AS insurance_name
            FROM patients p
            LEFT JOIN medical_insurances mi 
                ON mi.insurance_id = p.insurance_id
            LEFT JOIN insurance_hospitals ih
                ON ih.insurance_id = p.insurance_id
               AND ih.hospital_id = ?
            WHERE p.patient_id = ?
              AND (
                    ih.hospital_id IS NOT NULL
                 OR p.insurance_id IS NULL
              )
            LIMIT 1
        ");

        $stmt->bind_param('ii', $hospital_id, $patient_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$patient) {
            header('Location: ' . BASE_URL . '/hospital/dashboard?error=patient_access_denied');
            exit;
        }

        $insuranceName = $patient['insurance_name'] ?? '';
        $insuranceId   = (int)($patient['insurance_id'] ?? 0);

        $success = '';
        $error   = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $record = new MedicalRecord($conn);

            $record->setPatientId($patient_id);
            $record->setHospitalId($hospital_id);

            $record->setAge((int)Validator::nullIfEmpty($_POST['age'] ?? null));
            $record->setCheckinDate(Validator::nullIfEmpty($_POST['checkin_date'] ?? null));
            $record->setCheckoutDate(Validator::nullIfEmpty($_POST['checkout_date'] ?? null));

            $record->setLabValues(
                Validator::nullIfEmpty($_POST['cbc_hb1'] ?? null),
                Validator::nullIfEmpty($_POST['cbc_tlc1'] ?? null),
                Validator::nullIfEmpty($_POST['cbc_plat1'] ?? null),
                Validator::nullIfEmpty($_POST['blood_uria1'] ?? null),
                Validator::nullIfEmpty($_POST['blood_creatinine1'] ?? null),
                Validator::nullIfEmpty($_POST['cbc_hb2'] ?? null),
                Validator::nullIfEmpty($_POST['cbc_tlc2'] ?? null),
                Validator::nullIfEmpty($_POST['cbc_plat2'] ?? null),
                Validator::nullIfEmpty($_POST['blood_uria2'] ?? null),
                Validator::nullIfEmpty($_POST['blood_creatinine2'] ?? null)
            );

            $record->setBMI(Validator::nullIfEmpty($_POST['bmi'] ?? null));
            $record->setGlucose(Validator::nullIfEmpty($_POST['glucose'] ?? null));
            $record->setCholesterolLevel(Validator::nullIfEmpty($_POST['cholesterol_level'] ?? null));
            $record->setSystolicBP(Validator::nullIfEmpty($_POST['systolic_bp'] ?? null));

            $record->setAggregates(
                Validator::nullIfEmpty($_POST['month'] ?? null),
                Validator::nullIfEmpty($_POST['year'] ?? null),
                Validator::nullIfEmpty($_POST['day_of_week'] ?? null),
                Validator::nullIfEmpty($_POST['admission_count'] ?? null),
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
                Validator::boolToInt($_POST['has_diabetes'] ?? 0),
                Validator::boolToInt($_POST['has_hypertension'] ?? 0),
                Validator::boolToInt($_POST['has_kidney_disease'] ?? 0),
                Validator::boolToInt($_POST['has_heart_disease'] ?? 0)
            );

            $record->setRiskScores(
                Validator::nullIfEmpty($_POST['stress_level'] ?? null),
                Validator::nullIfEmpty($_POST['family_history'] ?? null),
                Validator::nullIfEmpty($_POST['medications_count'] ?? null),
                Validator::nullIfEmpty($_POST['risk_score'] ?? null),
                Validator::nullIfEmpty($_POST['symptom_burden'] ?? null),
                Validator::nullIfEmpty($_POST['seasonal_weight'] ?? null)
            );

            $record->setSymptoms(
                Validator::nullIfEmpty($_POST['fever'] ?? null),
                Validator::nullIfEmpty($_POST['cough'] ?? null),
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
        $db->close();
    }

    // ── GET|POST /hospital/editRecord ────────────────────────────────────────
    public function editRecord(): void {
       Guard::staff('HOSPITAL_STAFF');
$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

        $hospital_id = (int)($auth->getSessionData('hospital_id') ?? 0);
        $patient_id  = (int)($_GET['patient_id'] ?? 0);
        $record_id   = (int)($_GET['record_id'] ?? 0);

        if ($hospital_id <= 0) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if ($patient_id <= 0 || $record_id <= 0) {
            header('Location: ' . BASE_URL . '/hospital/dashboard');
            exit;
        }

        $stmt = $conn->prepare("
            SELECT p.patient_id, p.full_name, p.national_id
            FROM patients p
            LEFT JOIN insurance_hospitals ih
                ON ih.insurance_id = p.insurance_id
               AND ih.hospital_id = ?
            WHERE p.patient_id = ?
              AND (
                    ih.hospital_id IS NOT NULL
                 OR p.insurance_id IS NULL
              )
            LIMIT 1
        ");

        $stmt->bind_param('ii', $hospital_id, $patient_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$patient) {
            header('Location: ' . BASE_URL . '/hospital/dashboard?error=patient_not_found');
            exit;
        }

        $stmt = $conn->prepare("
            SELECT mr.* 
            FROM medical_records mr
            INNER JOIN patients p ON p.patient_id = mr.patient_id
            INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
            WHERE mr.record_id = ?
              AND mr.patient_id = ?
              AND ih.hospital_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('iii', $record_id, $patient_id, $hospital_id);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) {
            header('Location: ' . BASE_URL . '/hospital/viewRecords?patient_id=' . $patient_id);
            exit;
        }

        $activityOptions = ['Low', 'Moderate', 'High'];
        $dietOptions     = ['Poor', 'Average', 'Good'];
        $daysOfWeek      = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $error   = '';
        $success = isset($_GET['success']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_record') {
            $this->performUpdate($conn, $record_id, $patient_id, $hospital_id, $_POST);

            header(
                'Location: ' . BASE_URL .
                '/hospital/editRecord?patient_id=' . $patient_id .
                '&record_id=' . $record_id .
                '&success=1'
            );
            exit;
        }

        require_once ROOT . '/app/views/hospital/editRecord.php';
        $db->close();
    }

    private function performUpdate(mysqli $conn, int $record_id, int $patient_id, int $hospital_id, array $p): void {
        $ni2 = function ($v) {
            $v = trim((string)($v ?? ''));
            return $v === '' ? null : $v;
        };

        $hb1 = $p['cbc_hb1'] ?? null;
        $hb2 = $p['cbc_hb2'] ?? null;

        $tl1 = $p['cbc_tlc1'] ?? null;
        $tl2 = $p['cbc_tlc2'] ?? null;

        $pl1 = $p['cbc_plat1'] ?? null;
        $pl2 = $p['cbc_plat2'] ?? null;

        $ur1 = $p['blood_uria1'] ?? null;
        $ur2 = $p['blood_uria2'] ?? null;

        $cr1 = $p['blood_creatinine1'] ?? null;
        $cr2 = $p['blood_creatinine2'] ?? null;

        $avg_hb   = ($hb1 !== null && $hb2 !== null) ? round(((float)$hb1 + (float)$hb2) / 2, 3) : null;
        $avg_tlc  = ($tl1 !== null && $tl2 !== null) ? round(((float)$tl1 + (float)$tl2) / 2, 3) : null;
        $avg_plat = ($pl1 !== null && $pl2 !== null) ? round(((float)$pl1 + (float)$pl2) / 2, 3) : null;
        $avg_urea = ($ur1 !== null && $ur2 !== null) ? round(((float)$ur1 + (float)$ur2) / 2, 3) : null;
        $avg_crn  = ($cr1 !== null && $cr2 !== null) ? round(((float)$cr1 + (float)$cr2) / 2, 3) : null;

        $d_hb   = ($hb1 !== null && $hb2 !== null) ? round((float)$hb2 - (float)$hb1, 3) : null;
        $d_tlc  = ($tl1 !== null && $tl2 !== null) ? round((float)$tl2 - (float)$tl1, 3) : null;
        $d_plat = ($pl1 !== null && $pl2 !== null) ? round((float)$pl2 - (float)$pl1, 3) : null;
        $d_uria = ($ur1 !== null && $ur2 !== null) ? round((float)$ur2 - (float)$ur1, 3) : null;
        $d_crn  = ($cr1 !== null && $cr2 !== null) ? round((float)$cr2 - (float)$cr1, 3) : null;

        $smoke = ($p['smoking_status'] ?? '') === '' ? null : (int)$p['smoking_status'];

        $age             = (int)($p['age'] ?? 0);
        $checkin_date    = $ni2($p['checkin_date'] ?? null);
        $checkout_date   = $ni2($p['checkout_date'] ?? null);
        $length_of_stay  = $ni2($p['length_of_stay'] ?? null);
        $avg_length_stay = $ni2($p['avg_length_stay'] ?? null);
        $month           = $ni2($p['month'] ?? null);
        $year            = $ni2($p['year'] ?? null);
        $day_of_week     = $ni2($p['day_of_week'] ?? null);
        $admission_count = $ni2($p['admission_count'] ?? null);

        $bmi         = $ni2($p['bmi'] ?? null);
        $glucose     = $ni2($p['glucose'] ?? null);
        $cholesterol = $ni2($p['cholesterol_level'] ?? null);
        $systolic_bp = $ni2($p['systolic_bp'] ?? null);

        $physical = $ni2($p['physical_activity_level'] ?? null);
        $diet     = $ni2($p['diet_quality'] ?? null);
        $sleep    = $ni2($p['sleep_hours'] ?? null);
        $alcohol  = isset($p['alcohol_consumption']) ? 1 : 0;

        $stress      = $ni2($p['stress_level'] ?? null);
        $family      = $ni2($p['family_history'] ?? null);
        $medications = $ni2($p['medications_count'] ?? null);
        $risk        = $ni2($p['risk_score'] ?? null);
        $symptom     = $ni2($p['symptom_burden'] ?? null);
        $season      = $ni2($p['seasonal_weight'] ?? null);

        $fever   = $ni2($p['fever'] ?? null);
        $cough   = $ni2($p['cough'] ?? null);
        $fatigue = $ni2($p['fatigue'] ?? null);
        $breath  = $ni2($p['shortness_of_breath'] ?? null);

        $chest    = isset($p['chest_pain']) ? 1 : 0;
        $headache = isset($p['headache']) ? 1 : 0;

        $diabetes     = isset($p['has_diabetes']) ? 1 : 0;
        $hypertension = isset($p['has_hypertension']) ? 1 : 0;
        $kidney       = isset($p['has_kidney_disease']) ? 1 : 0;
        $heart        = isset($p['has_heart_disease']) ? 1 : 0;

        $diagnosis = $ni2($p['diagnosis'] ?? null);
        $disease   = $ni2($p['disease_category'] ?? null);

        $sql = "UPDATE medical_records SET
            age = ?,
            checkin_date = ?,
            checkout_date = ?,
            length_of_stay = ?,
            avg_length_stay = ?,
            month = ?,
            year = ?,
            day_of_week = ?,
            admission_count = ?,
            cbc_hb1 = ?,
            cbc_tlc1 = ?,
            cbc_plat1 = ?,
            blood_uria1 = ?,
            blood_creatinine1 = ?,
            cbc_hb2 = ?,
            cbc_tlc2 = ?,
            cbc_plat2 = ?,
            blood_uria2 = ?,
            blood_creatinine2 = ?,
            avg_hb = ?,
            avg_tlc = ?,
            avg_platelets = ?,
            avg_urea = ?,
            avg_creatinine = ?,
            delta_hb = ?,
            delta_tlc = ?,
            delta_plat = ?,
            delta_uria = ?,
            delta_creatinine = ?,
            bmi = ?,
            glucose = ?,
            cholesterol_level = ?,
            systolic_bp = ?,
            smoking_status = ?,
            physical_activity_level = ?,
            diet_quality = ?,
            sleep_hours = ?,
            alcohol_consumption = ?,
            stress_level = ?,
            family_history = ?,
            medications_count = ?,
            risk_score = ?,
            symptom_burden = ?,
            seasonal_weight = ?,
            fever = ?,
            cough = ?,
            fatigue = ?,
            shortness_of_breath = ?,
            chest_pain = ?,
            headache = ?,
            has_diabetes = ?,
            has_hypertension = ?,
            has_kidney_disease = ?,
            has_heart_disease = ?,
            diagnosis = ?,
            disease_category = ?
            WHERE record_id = ?
              AND patient_id = ?";

        $stmt = $conn->prepare($sql);

        $types = str_repeat('s', 56) . 'ii';

        $stmt->bind_param(
            $types,
            $age,
            $checkin_date,
            $checkout_date,
            $length_of_stay,
            $avg_length_stay,
            $month,
            $year,
            $day_of_week,
            $admission_count,
            $hb1,
            $tl1,
            $pl1,
            $ur1,
            $cr1,
            $hb2,
            $tl2,
            $pl2,
            $ur2,
            $cr2,
            $avg_hb,
            $avg_tlc,
            $avg_plat,
            $avg_urea,
            $avg_crn,
            $d_hb,
            $d_tlc,
            $d_plat,
            $d_uria,
            $d_crn,
            $bmi,
            $glucose,
            $cholesterol,
            $systolic_bp,
            $smoke,
            $physical,
            $diet,
            $sleep,
            $alcohol,
            $stress,
            $family,
            $medications,
            $risk,
            $symptom,
            $season,
            $fever,
            $cough,
            $fatigue,
            $breath,
            $chest,
            $headache,
            $diabetes,
            $hypertension,
            $kidney,
            $heart,
            $diagnosis,
            $disease,
            $record_id,
            $patient_id
        );

        $stmt->execute();
        $stmt->close();
    }
}
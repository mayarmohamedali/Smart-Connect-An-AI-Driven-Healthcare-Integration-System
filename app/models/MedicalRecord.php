<?php
class MedicalRecord {
    private $conn;

    private $record_id;
    private $patient_id;
    private $hospital_id;

    private $age;
    private $checkin_date;
    private $checkout_date;
    private $diagnosis;
    private $disease_category;
    private $bmi;
    private $glucose;
    private $cholesterol_level;
    private $systolic_bp;

    // Lab values
    private $cbc_hb1;
    private $cbc_tlc1;
    private $cbc_plat1;
    private $blood_uria1;
    private $blood_creatinine1;
    private $cbc_hb2;
    private $cbc_tlc2;
    private $cbc_plat2;
    private $blood_uria2;
    private $blood_creatinine2;

    // Aggregates auto-calculated
    private $avg_hb;
    private $avg_tlc;
    private $avg_platelets;
    private $avg_urea;
    private $avg_creatinine;

    // Deltas auto-calculated
    private $delta_hb;
    private $delta_tlc;
    private $delta_plat;
    private $delta_uria;
    private $delta_creatinine;

    // Admission
    private $month;
    private $year;
    private $day_of_week;
    private $admission_count;
    private $length_of_stay;
    private $avg_length_stay;

    // Lifestyle
    private $smoking_status;
    private $physical_activity_level;
    private $diet_quality;
    private $alcohol_consumption;
    private $sleep_hours;

    // Medical history flags
    private $has_diabetes;
    private $has_hypertension;
    private $has_kidney_disease;
    private $has_heart_disease;

    // Risk / computed
    private $stress_level;
    private $family_history;
    private $medications_count;
    private $risk_score;
    private $symptom_burden;
    private $seasonal_weight;

    // Symptoms
    private $fever;
    private $cough;
    private $fatigue;
    private $chest_pain;
    private $shortness_of_breath;
    private $headache;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ── Getters ───────────────────────────────────────────────────────────────
    public function getRecordId()     { return $this->record_id; }
    public function getPatientId()    { return $this->patient_id; }
    public function getHospitalId()   { return $this->hospital_id; }
    public function getDiagnosis()    { return $this->diagnosis; }
    public function getCheckinDate()  { return $this->checkin_date; }
    public function getCheckoutDate() { return $this->checkout_date; }

    // ── Setters ───────────────────────────────────────────────────────────────
    public function setPatientId($id)       { $this->patient_id = $id; }
    public function setHospitalId($id)      { $this->hospital_id = $id; }
    public function setAge($age)            { $this->age = $age; }
    public function setCheckinDate($date)   { $this->checkin_date = $date; }
    public function setCheckoutDate($date)  { $this->checkout_date = $date; }
    public function setDiagnosis($v)        { $this->diagnosis = $v; }
    public function setDiseaseCategory($v)  { $this->disease_category = $v; }
    public function setBMI($v)              { $this->bmi = $v; }
    public function setGlucose($v)          { $this->glucose = $v; }
    public function setCholesterolLevel($v) { $this->cholesterol_level = $v; }
    public function setSystolicBP($v)       { $this->systolic_bp = $v; }

    /**
     * Set lab values and auto-calculate averages and deltas.
     */
    public function setLabValues(
        $hb1, $tlc1, $plat1, $uria1, $creat1,
        $hb2, $tlc2, $plat2, $uria2, $creat2
    ) {
        $this->cbc_hb1           = $hb1;
        $this->cbc_tlc1          = $tlc1;
        $this->cbc_plat1         = $plat1;
        $this->blood_uria1       = $uria1;
        $this->blood_creatinine1 = $creat1;

        $this->cbc_hb2           = $hb2;
        $this->cbc_tlc2          = $tlc2;
        $this->cbc_plat2         = $plat2;
        $this->blood_uria2       = $uria2;
        $this->blood_creatinine2 = $creat2;

        $this->avg_hb         = $this->calcAvg($hb1, $hb2);
        $this->avg_tlc        = $this->calcAvg($tlc1, $tlc2);
        $this->avg_platelets  = $this->calcAvg($plat1, $plat2);
        $this->avg_urea       = $this->calcAvg($uria1, $uria2);
        $this->avg_creatinine = $this->calcAvg($creat1, $creat2);

        $this->delta_hb         = $this->calcDelta($hb1, $hb2);
        $this->delta_tlc        = $this->calcDelta($tlc1, $tlc2);
        $this->delta_plat       = $this->calcDelta($plat1, $plat2);
        $this->delta_uria       = $this->calcDelta($uria1, $uria2);
        $this->delta_creatinine = $this->calcDelta($creat1, $creat2);
    }

    private function calcAvg($a, $b) {
        if ($a === null || $a === "" || $b === null || $b === "") {
            return null;
        }

        return round(((float)$a + (float)$b) / 2, 3);
    }

    private function calcDelta($a, $b) {
        if ($a === null || $a === "" || $b === null || $b === "") {
            return null;
        }

        return round((float)$b - (float)$a, 3);
    }

    public function setAggregates($month, $year, $day_of_week, $admission_count, $avg_length_stay = null) {
        $this->month           = $month;
        $this->year            = $year;
        $this->day_of_week     = $day_of_week;
        $this->admission_count = $admission_count;
        $this->avg_length_stay = $avg_length_stay;
    }

    public function setLifestyle(
        $length_of_stay,
        $smoking,
        $activity,
        $diet_quality = null,
        $alcohol_consumption = null,
        $sleep_hours = null
    ) {
        $this->length_of_stay          = $length_of_stay;
        $this->smoking_status          = ($smoking === null || $smoking === "") ? null : (int)$smoking;
        $this->physical_activity_level = $activity;
        $this->diet_quality            = $diet_quality;
        $this->alcohol_consumption     = $alcohol_consumption;
        $this->sleep_hours             = $sleep_hours;
    }

    public function setRiskFlags($diabetes, $hypertension, $kidney, $heart) {
        $this->has_diabetes       = $diabetes;
        $this->has_hypertension   = $hypertension;
        $this->has_kidney_disease = $kidney;
        $this->has_heart_disease  = $heart;
    }

    public function setRiskScores(
        $stress_level = null,
        $family_history = null,
        $medications_count = null,
        $risk_score = null,
        $symptom_burden = null,
        $seasonal_weight = null
    ) {
        $this->stress_level      = $stress_level;
        $this->family_history    = $family_history;
        $this->medications_count = $medications_count;
        $this->risk_score        = $risk_score;
        $this->symptom_burden    = $symptom_burden;
        $this->seasonal_weight   = $seasonal_weight;
    }

    public function setSymptoms(
        $fever = null,
        $cough = null,
        $fatigue = null,
        $chest_pain = null,
        $shortness_of_breath = null,
        $headache = null
    ) {
        $this->fever               = $fever;
        $this->cough               = $cough;
        $this->fatigue             = $fatigue;
        $this->chest_pain          = $chest_pain;
        $this->shortness_of_breath = $shortness_of_breath;
        $this->headache            = $headache;
    }

    // ── CREATE ────────────────────────────────────────────────────────────────
    public function create() {
        $sql = "
            INSERT INTO medical_records (
                patient_id, age, checkin_date, checkout_date,
                cbc_hb1, cbc_tlc1, cbc_plat1, blood_uria1, blood_creatinine1,
                cbc_hb2, cbc_tlc2, cbc_plat2, blood_uria2, blood_creatinine2,
                avg_hb, avg_tlc, avg_platelets, avg_urea, avg_creatinine,
                delta_hb, delta_tlc, delta_plat, delta_uria, delta_creatinine,
                bmi, glucose, cholesterol_level, systolic_bp,
                month, year, day_of_week, admission_count,
                length_of_stay, avg_length_stay,
                smoking_status, physical_activity_level, diet_quality,
                alcohol_consumption, sleep_hours,
                stress_level, family_history, medications_count,
                risk_score, symptom_burden, seasonal_weight,
                has_diabetes, has_hypertension, has_kidney_disease, has_heart_disease,
                fever, cough, fatigue, chest_pain, shortness_of_breath, headache,
                diagnosis, disease_category
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?
            )
        ";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $params = [
            $this->patient_id,
            $this->age,
            $this->checkin_date,
            $this->checkout_date,

            $this->cbc_hb1,
            $this->cbc_tlc1,
            $this->cbc_plat1,
            $this->blood_uria1,
            $this->blood_creatinine1,

            $this->cbc_hb2,
            $this->cbc_tlc2,
            $this->cbc_plat2,
            $this->blood_uria2,
            $this->blood_creatinine2,

            $this->avg_hb,
            $this->avg_tlc,
            $this->avg_platelets,
            $this->avg_urea,
            $this->avg_creatinine,

            $this->delta_hb,
            $this->delta_tlc,
            $this->delta_plat,
            $this->delta_uria,
            $this->delta_creatinine,

            $this->bmi,
            $this->glucose,
            $this->cholesterol_level,
            $this->systolic_bp,

            $this->month,
            $this->year,
            $this->day_of_week,
            $this->admission_count,

            $this->length_of_stay,
            $this->avg_length_stay,

            $this->smoking_status,
            $this->physical_activity_level,
            $this->diet_quality,

            $this->alcohol_consumption,
            $this->sleep_hours,

            $this->stress_level,
            $this->family_history,
            $this->medications_count,

            $this->risk_score,
            $this->symptom_burden,
            $this->seasonal_weight,

            $this->has_diabetes,
            $this->has_hypertension,
            $this->has_kidney_disease,
            $this->has_heart_disease,

            $this->fever,
            $this->cough,
            $this->fatigue,
            $this->chest_pain,
            $this->shortness_of_breath,
            $this->headache,

            $this->diagnosis,
            $this->disease_category,
        ];

        $types = str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $this->record_id = $stmt->insert_id;
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    // ── LOAD ─────────────────────────────────────────────────────────────────
    public function loadById($record_id) {
        $stmt = $this->conn->prepare("SELECT * FROM medical_records WHERE record_id = ? LIMIT 1");
        $stmt->bind_param("i", $record_id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if ($row) {
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }

            return $row;
        }

        return false;
    }

    // ── UPDATE ───────────────────────────────────────────────────────────────
    public function update() {
        $sql = "
            UPDATE medical_records SET
                age = ?,
                checkin_date = ?,
                checkout_date = ?,
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
                month = ?,
                year = ?,
                day_of_week = ?,
                admission_count = ?,
                length_of_stay = ?,
                avg_length_stay = ?,
                smoking_status = ?,
                physical_activity_level = ?,
                diet_quality = ?,
                alcohol_consumption = ?,
                sleep_hours = ?,
                stress_level = ?,
                family_history = ?,
                medications_count = ?,
                risk_score = ?,
                symptom_burden = ?,
                seasonal_weight = ?,
                has_diabetes = ?,
                has_hypertension = ?,
                has_kidney_disease = ?,
                has_heart_disease = ?,
                fever = ?,
                cough = ?,
                fatigue = ?,
                chest_pain = ?,
                shortness_of_breath = ?,
                headache = ?,
                diagnosis = ?,
                disease_category = ?
            WHERE record_id = ? AND patient_id = ?
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $params = [
            $this->age,
            $this->checkin_date,
            $this->checkout_date,

            $this->cbc_hb1,
            $this->cbc_tlc1,
            $this->cbc_plat1,
            $this->blood_uria1,
            $this->blood_creatinine1,

            $this->cbc_hb2,
            $this->cbc_tlc2,
            $this->cbc_plat2,
            $this->blood_uria2,
            $this->blood_creatinine2,

            $this->avg_hb,
            $this->avg_tlc,
            $this->avg_platelets,
            $this->avg_urea,
            $this->avg_creatinine,

            $this->delta_hb,
            $this->delta_tlc,
            $this->delta_plat,
            $this->delta_uria,
            $this->delta_creatinine,

            $this->bmi,
            $this->glucose,
            $this->cholesterol_level,
            $this->systolic_bp,

            $this->month,
            $this->year,
            $this->day_of_week,
            $this->admission_count,

            $this->length_of_stay,
            $this->avg_length_stay,

            $this->smoking_status,
            $this->physical_activity_level,
            $this->diet_quality,

            $this->alcohol_consumption,
            $this->sleep_hours,

            $this->stress_level,
            $this->family_history,
            $this->medications_count,

            $this->risk_score,
            $this->symptom_burden,
            $this->seasonal_weight,

            $this->has_diabetes,
            $this->has_hypertension,
            $this->has_kidney_disease,
            $this->has_heart_disease,

            $this->fever,
            $this->cough,
            $this->fatigue,
            $this->chest_pain,
            $this->shortness_of_breath,
            $this->headache,

            $this->diagnosis,
            $this->disease_category,

            $this->record_id,
            $this->patient_id,
        ];

        $types = str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);

        $result = $stmt->execute();

        $stmt->close();

        return $result;
    }

    // ── HELPERS ──────────────────────────────────────────────────────────────
    public function getRecordsByPatient($patient_id, $limit = 20) {
        $stmt = $this->conn->prepare("
            SELECT 
                record_id,
                patient_id,
                created_at,
                checkin_date,
                checkout_date,
                diagnosis,
                disease_category,
                has_diabetes,
                has_hypertension,
                has_kidney_disease,
                has_heart_disease
            FROM medical_records
            WHERE patient_id = ?
            ORDER BY record_id DESC
            LIMIT ?
        ");

        $stmt->bind_param("ii", $patient_id, $limit);
        $stmt->execute();

        $result = $stmt->get_result();

        $records = [];

        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }

        $stmt->close();

        return $records;
    }
}
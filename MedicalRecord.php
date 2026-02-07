<?php
class MedicalRecord {
    private $conn;
    private $record_id;
    private $patient_id;
    private $age;
    private $checkin_date;
    private $checkout_date;
    private $diagnosis;
    private $bmi;
    private $glucose;
    private $systolic_bp;
    private $has_diabetes;
    private $has_hypertension;
    private $has_kidney_disease;
    private $has_heart_disease;
    
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
    
    // Aggregates
    private $month;
    private $admission_count;
    private $avg_creatinine;
    private $avg_urea;
    private $avg_hb;
    private $avg_tlc;
    private $avg_platelets;
    private $length_of_stay;
    private $smoking_status;
    private $physical_activity_level;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Getters
    public function getRecordId() { return $this->record_id; }
    public function getPatientId() { return $this->patient_id; }
    public function getDiagnosis() { return $this->diagnosis; }
    public function getCheckinDate() { return $this->checkin_date; }
    public function getCheckoutDate() { return $this->checkout_date; }
    
    // Setters
    public function setPatientId($id) { $this->patient_id = $id; }
    public function setAge($age) { $this->age = $age; }
    public function setCheckinDate($date) { $this->checkin_date = $date; }
    public function setCheckoutDate($date) { $this->checkout_date = $date; }
    public function setDiagnosis($diagnosis) { $this->diagnosis = $diagnosis; }
    public function setBMI($bmi) { $this->bmi = $bmi; }
    public function setGlucose($glucose) { $this->glucose = $glucose; }
    public function setSystolicBP($bp) { $this->systolic_bp = $bp; }
    
    public function setLabValues($hb1, $tlc1, $plat1, $uria1, $creat1, 
                                  $hb2, $tlc2, $plat2, $uria2, $creat2) {
        $this->cbc_hb1 = $hb1;
        $this->cbc_tlc1 = $tlc1;
        $this->cbc_plat1 = $plat1;
        $this->blood_uria1 = $uria1;
        $this->blood_creatinine1 = $creat1;
        $this->cbc_hb2 = $hb2;
        $this->cbc_tlc2 = $tlc2;
        $this->cbc_plat2 = $plat2;
        $this->blood_uria2 = $uria2;
        $this->blood_creatinine2 = $creat2;
    }
    
    public function setAggregates($month, $admission_count, $avg_creat, $avg_urea, 
                                  $avg_hb, $avg_tlc, $avg_plat) {
        $this->month = $month;
        $this->admission_count = $admission_count;
        $this->avg_creatinine = $avg_creat;
        $this->avg_urea = $avg_urea;
        $this->avg_hb = $avg_hb;
        $this->avg_tlc = $avg_tlc;
        $this->avg_platelets = $avg_plat;
    }
    
    public function setLifestyle($length_of_stay, $smoking, $activity) {
        $this->length_of_stay = $length_of_stay;
        $this->smoking_status = $smoking;
        $this->physical_activity_level = $activity;
    }
    
    public function setRiskFlags($diabetes, $hypertension, $kidney, $heart) {
        $this->has_diabetes = $diabetes;
        $this->has_hypertension = $hypertension;
        $this->has_kidney_disease = $kidney;
        $this->has_heart_disease = $heart;
    }
    
    public function create() {
        $sql = "
            INSERT INTO medical_records (
                patient_id, age, checkin_date, checkout_date,
                cbc_hb1, cbc_tlc1, cbc_plat1, blood_uria1, blood_creatinine1,
                cbc_hb2, cbc_tlc2, cbc_plat2, blood_uria2, blood_creatinine2,
                bmi, glucose, systolic_bp,
                month, admission_count,
                avg_creatinine, avg_urea, avg_hb, avg_tlc, avg_platelets,
                length_of_stay,
                smoking_status, physical_activity_level,
                has_diabetes, has_hypertension, has_kidney_disease, has_heart_disease,
                diagnosis
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?, ?,
                ?,
                ?, ?,
                ?, ?, ?, ?,
                ?
            )
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "iissddddddddddddiiddddddissiiiis",
            $this->patient_id, $this->age, $this->checkin_date, $this->checkout_date,
            $this->cbc_hb1, $this->cbc_tlc1, $this->cbc_plat1, $this->blood_uria1, $this->blood_creatinine1,
            $this->cbc_hb2, $this->cbc_tlc2, $this->cbc_plat2, $this->blood_uria2, $this->blood_creatinine2,
            $this->bmi, $this->glucose, $this->systolic_bp,
            $this->month, $this->admission_count,
            $this->avg_creatinine, $this->avg_urea, $this->avg_hb, $this->avg_tlc, $this->avg_platelets,
            $this->length_of_stay,
            $this->smoking_status, $this->physical_activity_level,
            $this->has_diabetes, $this->has_hypertension, $this->has_kidney_disease, $this->has_heart_disease,
            $this->diagnosis
        );
        
        if ($stmt->execute()) {
            $this->record_id = $stmt->insert_id;
            $stmt->close();
            return true;
        }
        $stmt->close();
        return false;
    }
    
    public function loadById($record_id) {
        $stmt = $this->conn->prepare("SELECT * FROM medical_records WHERE record_id=? LIMIT 1");
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            $this->record_id = $row['record_id'];
            $this->patient_id = $row['patient_id'];
            $this->age = $row['age'];
            $this->checkin_date = $row['checkin_date'];
            $this->checkout_date = $row['checkout_date'];
            $this->diagnosis = $row['diagnosis'];
            $this->bmi = $row['bmi'];
            $this->glucose = $row['glucose'];
            $this->systolic_bp = $row['systolic_bp'];
            $this->has_diabetes = $row['has_diabetes'];
            $this->has_hypertension = $row['has_hypertension'];
            $this->has_kidney_disease = $row['has_kidney_disease'];
            $this->has_heart_disease = $row['has_heart_disease'];
            return $row;
        }
        return false;
    }
    
    public function update() {
        $sql = "
            UPDATE medical_records
            SET age=?, checkin_date=?, checkout_date=?, length_of_stay=?,
                cbc_hb1=?, cbc_tlc1=?, cbc_plat1=?, blood_uria1=?, blood_creatinine1=?,
                cbc_hb2=?, cbc_tlc2=?, cbc_plat2=?, blood_uria2=?, blood_creatinine2=?,
                bmi=?, glucose=?, systolic_bp=?,
                month=?, admission_count=?,
                avg_creatinine=?, avg_urea=?, avg_hb=?, avg_tlc=?, avg_platelets=?,
                smoking_status=?, physical_activity_level=?,
                has_diabetes=?, has_hypertension=?, has_kidney_disease=?, has_heart_disease=?,
                diagnosis=?
            WHERE record_id=? AND patient_id=?
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "issiddddddddddddddiidddddssiiiiisii",
            $this->age, $this->checkin_date, $this->checkout_date, $this->length_of_stay,
            $this->cbc_hb1, $this->cbc_tlc1, $this->cbc_plat1, $this->blood_uria1, $this->blood_creatinine1,
            $this->cbc_hb2, $this->cbc_tlc2, $this->cbc_plat2, $this->blood_uria2, $this->blood_creatinine2,
            $this->bmi, $this->glucose, $this->systolic_bp,
            $this->month, $this->admission_count,
            $this->avg_creatinine, $this->avg_urea, $this->avg_hb, $this->avg_tlc, $this->avg_platelets,
            $this->smoking_status, $this->physical_activity_level,
            $this->has_diabetes, $this->has_hypertension, $this->has_kidney_disease, $this->has_heart_disease,
            $this->diagnosis,
            $this->record_id, $this->patient_id
        );
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    public function getRecordsByPatient($patient_id, $limit = 20) {
        $stmt = $this->conn->prepare("
            SELECT record_id, created_at, checkin_date, checkout_date, diagnosis,
                   has_diabetes, has_hypertension, has_kidney_disease, has_heart_disease
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
<?php

class Hospital {
    private $conn;
    private $hospital_id;
    private $name;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getHospitalId() { return $this->hospital_id; }
    public function getName() { return $this->name; }
    
    public function setName($name) { $this->name = $name; }
    
    public function loadById($hospital_id) {
        $stmt = $this->conn->prepare("
            SELECT hospital_id, name 
            FROM hospitals 
            WHERE hospital_id = ? 
            LIMIT 1
        ");
        $stmt->bind_param("i", $hospital_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            $this->hospital_id = $row['hospital_id'];
            $this->name = $row['name'];
            return true;
        }
        return false;
    }
    
   
    public function getKPIPatients() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT p.patient_id)
            FROM patients p
            INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
            WHERE ih.hospital_id = ? 
              AND p.is_active = 1
        ");
        $stmt->bind_param("i", $this->hospital_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $result ? (int)$result[0] : 0;
    }
    
    /**
     * Get contracted insurances for this hospital
     */
    public function getContractedInsurances() {
        $stmt = $this->conn->prepare("
            SELECT mi.insurance_id, mi.name
            FROM medical_insurances mi
            INNER JOIN insurance_hospitals ih ON ih.insurance_id = mi.insurance_id
            WHERE ih.hospital_id = ?
            ORDER BY mi.name ASC
        ");
        $stmt->bind_param("i", $this->hospital_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $insurances = [];
        while ($row = $result->fetch_assoc()) {
            $insurances[] = $row;
        }
        $stmt->close();
        return $insurances;
    }
    
    /**
     * Get medical records for patients accessible by this hospital
     */
    public function getKPIMedicalRecords() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT mr.record_id)
            FROM medical_records mr
            INNER JOIN patients p ON p.patient_id = mr.patient_id
            INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
            WHERE ih.hospital_id = ?
        ");
        $stmt->bind_param("i", $this->hospital_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $result ? (int)$result[0] : 0;
    }
    
    /**
     * Get records created this month
     */
    public function getKPIRecordsThisMonth() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT mr.record_id)
            FROM medical_records mr
            INNER JOIN patients p ON p.patient_id = mr.patient_id
            INNER JOIN insurance_hospitals ih ON ih.insurance_id = p.insurance_id
            WHERE ih.hospital_id = ?
              AND YEAR(mr.created_at) = YEAR(CURDATE())
              AND MONTH(mr.created_at) = MONTH(CURDATE())
        ");
        $stmt->bind_param("i", $this->hospital_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $result ? (int)$result[0] : 0;
    }
}
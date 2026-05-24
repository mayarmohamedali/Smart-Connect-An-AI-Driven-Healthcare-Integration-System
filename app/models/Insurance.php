<?php
class Insurance {
    private $conn;
    private $insurance_id;
    private $name;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getInsuranceId() { return $this->insurance_id; }
    public function getName() { return $this->name; }
    
    public function setName($name) { $this->name = $name; }
    
    public function loadById($insurance_id) {
        $stmt = $this->conn->prepare("SELECT insurance_id, name FROM medical_insurances WHERE insurance_id=? LIMIT 1");
        $stmt->bind_param("i", $insurance_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            $this->insurance_id = $row['insurance_id'];
            $this->name = $row['name'];
            return true;
        }
        return false;
    }
    
    public function getKPIPatients() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT p.patient_id)
            FROM patients p
            WHERE p.insurance_id = ? AND p.is_active = 1
        ");
        $stmt->bind_param("i", $this->insurance_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $result ? (int)$result[0] : 0;
    }
    
    public function getKPIActivePolicies() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM (
                SELECT DISTINCT pp.patient_id
                FROM patient_policy pp
                INNER JOIN patients p ON p.patient_id = pp.patient_id
                WHERE pp.insurance_id = ?
                  AND p.insurance_id = ?
                  AND p.is_active = 1
                  AND pp.status = 'active'
                  AND (pp.end_date IS NULL OR pp.end_date >= CURDATE())
            ) AS unique_active_policies
        ");
        $stmt->bind_param("ii", $this->insurance_id, $this->insurance_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $result ? (int)$result[0] : 0;
    }
    
    public function getKPICasesThisMonth() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT c.claim_id)
            FROM claims c
            WHERE c.insurance_id = ?
              AND YEAR(c.created_at) = YEAR(CURDATE())
              AND MONTH(c.created_at) = MONTH(CURDATE())
        ");
        $stmt->bind_param("i", $this->insurance_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $result ? (int)$result[0] : 0;
    }
    
    public function getKPIPendingReviews() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT mr.record_id)
            FROM medical_records mr
            INNER JOIN patients p ON p.patient_id = mr.patient_id
            WHERE p.insurance_id = ? AND mr.checkout_date IS NULL
        ");
        $stmt->bind_param("i", $this->insurance_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $result ? (int)$result[0] : 0;
    }
}
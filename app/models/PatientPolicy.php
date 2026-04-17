<?php


class PatientPolicy {

    private $conn;
    private $patient_policy_id;
    private $patient_id;
    private $insurance_id;
    private $insurance_plan_id;
    private $policy_number;
    private $start_date;
    private $end_date;
    private $status;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ───────────── Getters ─────────────
    public function getPatientPolicyId() {
        return $this->patient_policy_id;
    }

    // ───────────── Setters ─────────────
    public function setPatientId($id)       { $this->patient_id = $id; }
    public function setInsuranceId($id)     { $this->insurance_id = $id; }
    public function setInsurancePlanId($id) { $this->insurance_plan_id = $id; }
    public function setPolicyNumber($num)   { $this->policy_number = $num; }
    public function setStartDate($date)     { $this->start_date = $date; }
    public function setEndDate($date)       { $this->end_date = $date; }
    public function setStatus($status)      { $this->status = $status; }

    // ───────────── Create Policy ─────────────
    public function create() {
        $stmt = $this->conn->prepare("
            INSERT INTO patient_policy (
                patient_id, insurance_id, insurance_plan_id,
                policy_number, start_date, end_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iiissss",
            $this->patient_id,
            $this->insurance_id,
            $this->insurance_plan_id,
            $this->policy_number,
            $this->start_date,
            $this->end_date,
            $this->status
        );

        $result = $stmt->execute();

        if ($result) {
            $this->patient_policy_id = $stmt->insert_id;
        }

        $stmt->close();
        return $result;
    }

    // ───────────── Load Latest Policy ─────────────
    public function loadByPatientId($patient_id) {

        $stmt = $this->conn->prepare("
            SELECT 
                pp.patient_policy_id,
                pp.patient_id,
                pp.insurance_id,
                pp.insurance_plan_id,
                pp.policy_number,
                pp.start_date,
                pp.end_date,
                pp.status,
                pp.created_at,
                ip.plan_name
            FROM patient_policy pp
            LEFT JOIN insurance_plan ip 
                ON ip.id = pp.insurance_plan_id
            WHERE pp.patient_id = ?
            ORDER BY pp.patient_policy_id DESC
            LIMIT 1
        ");

        $stmt->bind_param("i", $patient_id);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        return $result;
    }

    // ───────────── Get All Policies ─────────────
    public function getPoliciesByPatient($patient_id) {

        $stmt = $this->conn->prepare("
            SELECT 
                pp.patient_policy_id,
                pp.policy_number,
                pp.start_date,
                pp.end_date,
                pp.status,
                pp.created_at,
                ip.plan_name,
                mi.name AS insurance_name
            FROM patient_policy pp
            LEFT JOIN insurance_plan ip 
                ON ip.id = pp.insurance_plan_id
            LEFT JOIN medical_insurances mi 
                ON mi.insurance_id = pp.insurance_id
            WHERE pp.patient_id = ?
            ORDER BY pp.patient_policy_id DESC
        ");

        $stmt->bind_param("i", $patient_id);
        $stmt->execute();

        $result = $stmt->get_result();

        $policies = [];

        while ($row = $result->fetch_assoc()) {
            $policies[] = $row;
        }

        $stmt->close();

        return $policies;
    }

    // ───────────── Update Status ─────────────
    public function updateStatus($patient_policy_id, $status) {

        $stmt = $this->conn->prepare("
            UPDATE patient_policy
            SET status = ?
            WHERE patient_policy_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("si", $status, $patient_policy_id);

        $result = $stmt->execute();

        $stmt->close();

        return $result;
    }
}
<?php
/**
 * PatientPolicy Class
 *
 * FIXED:
 *  - create(): end_date is nullable; when null is bound as string type "s"
 *    in some MySQLi versions it silently inserts an empty string or fails.
 *    Use bind_param with explicit null handling via a temp variable.
 */
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
    private $last_error = '';

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ───────────── Getters ─────────────
    public function getPatientPolicyId() {
        return $this->patient_policy_id;
    }

    public function getLastError(): string {
        return $this->last_error;
    }

    // ───────────── Setters ─────────────
    public function setPatientId($id)       { $this->patient_id = $id; }
    public function setInsuranceId($id)     { $this->insurance_id = $id; }
    public function setInsurancePlanId($id) { $this->insurance_plan_id = $id; }
    public function setPolicyNumber($num)   { $this->policy_number = $num; }
    public function setStartDate($date)     { $this->start_date = $date; }
    public function setEndDate($date)       { $this->end_date = ($date === '' || $date === null) ? null : $date; }
    public function setStatus($status)      { $this->status = $status; }

    // ───────────── Create Policy ─────────────
    // FIXED: end_date null handling — bind a reference to avoid silent failures
    public function create() {
        $stmt = $this->conn->prepare("
            INSERT INTO patient_policy (
                patient_id, insurance_id, insurance_plan_id,
                policy_number, start_date, end_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        // Use a local variable for end_date so null binds correctly
        $end_date = $this->end_date;   // may be null

        $stmt->bind_param(
            "iiissss",
            $this->patient_id,
            $this->insurance_id,
            $this->insurance_plan_id,
            $this->policy_number,
            $this->start_date,
            $end_date,
            $this->status
        );

        try {
            $result = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            // Duplicate entry — patient already has a policy under this insurance
            if ($e->getCode() === 1062) {
                $this->last_error = 'This patient already has a policy assigned. Only one policy per patient is allowed.';
            } else {
                $this->last_error = 'Database error: ' . $e->getMessage();
                error_log('[PatientPolicy::create] DB error: ' . $e->getMessage());
            }
            return false;
        }

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
                ip.category_id,
                ip.customer_type_id
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
                ip.category_id,
                ip.customer_type_id,
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
            // Build a human-readable plan_name since the table has no such column
            $catMap  = [1 => 'Normal', 2 => 'VIP'];
            $typeMap = [1 => 'Individual', 2 => 'Company'];
            $row['plan_name'] = ($catMap[$row['category_id'] ?? 0] ?? 'Unknown')
                              . ' – '
                              . ($typeMap[$row['customer_type_id'] ?? 0] ?? 'Unknown');
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
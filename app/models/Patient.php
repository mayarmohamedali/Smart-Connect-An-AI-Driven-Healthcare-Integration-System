<?php

class Patient {
    private $conn;

    private $patient_id;
    private $full_name;
    private $national_id;
    private $phone;
    private $gender;
    private $address;
    private $insurance_id;
    private $hospital_id;
    private $is_active;
    private $insurance_name;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ── Getters ───────────────────────────────────────────────────────────────
    public function getPatientId()     { return $this->patient_id; }
    public function getFullName()      { return $this->full_name; }
    public function getNationalId()    { return $this->national_id; }
    public function getPhone()         { return $this->phone; }
    public function getGender()        { return $this->gender; }
    public function getAddress()       { return $this->address; }
    public function getInsuranceId()   { return $this->insurance_id; }
    public function getHospitalId()    { return $this->hospital_id; }
    public function getIsActive()      { return $this->is_active; }
    public function getInsuranceName() { return $this->insurance_name; }

    // ── Setters ───────────────────────────────────────────────────────────────
    public function setFullName($name)    { $this->full_name = $name; }
    public function setNationalId($id)    { $this->national_id = $id; }
    public function setPhone($phone)      { $this->phone = $phone; }
    public function setGender($gender)    { $this->gender = $gender; }
    public function setAddress($address)  { $this->address = $address; }
    public function setInsuranceId($id)   { $this->insurance_id = $id; }
    public function setHospitalId($id)    { $this->hospital_id = $id; }

    // ── Load one patient ─────────────────────────────────────────────────────
    public function loadById($patient_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.patient_id,
                p.full_name,
                p.national_id,
                p.phone,
                p.gender,
                p.address,
                p.insurance_id,
                p.hospital_id,
                p.is_active,
                p.created_at,
                mi.name AS insurance_name
            FROM patients p
            LEFT JOIN medical_insurances mi 
                ON mi.insurance_id = p.insurance_id
            WHERE p.patient_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $this->patient_id     = $row["patient_id"];
            $this->full_name      = $row["full_name"];
            $this->national_id    = $row["national_id"];
            $this->phone          = $row["phone"];
            $this->gender         = $row["gender"];
            $this->address        = $row["address"];
            $this->insurance_id   = $row["insurance_id"];
            $this->hospital_id    = $row["hospital_id"];
            $this->is_active      = $row["is_active"];
            $this->insurance_name = $row["insurance_name"] ?? null;

            return true;
        }

        return false;
    }

    // ── Create patient ───────────────────────────────────────────────────────
    public function create($hospital_id = null, $insurance_id = null) {
        /*
            New hospital-specific logic:
            - insurance_id = patient insurance company
            - hospital_id = hospital assigned to this patient

            If insurance creates the patient and hospital is not known yet,
            hospital_id may stay NULL until assigned later.
        */

        $stmt = $this->conn->prepare("
            INSERT INTO patients (
                full_name,
                national_id,
                phone,
                gender,
                address,
                insurance_id,
                hospital_id,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->bind_param(
            "sssssii",
            $this->full_name,
            $this->national_id,
            $this->phone,
            $this->gender,
            $this->address,
            $insurance_id,
            $hospital_id
        );

        if ($stmt->execute()) {
            $this->patient_id = $stmt->insert_id;
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    // ── General patient search ───────────────────────────────────────────────
    public function searchPatients($search_query, $hospital_id = null) {
        /*
            If hospital_id is provided, search only patients assigned to that hospital.
            If hospital_id is null, search all active patients.
        */

        $sql = "
            SELECT 
                p.patient_id,
                p.full_name,
                p.national_id,
                p.phone,
                p.gender,
                p.address,
                p.insurance_id,
                p.hospital_id,
                p.is_active,
                mi.name AS insurance_name
            FROM patients p
            LEFT JOIN medical_insurances mi
                ON mi.insurance_id = p.insurance_id
            WHERE p.is_active = 1
        ";

        $types = "";
        $params = [];

        if ($hospital_id !== null && (int)$hospital_id > 0) {
            $sql .= " AND p.hospital_id = ? ";
            $types .= "i";
            $params[] = (int)$hospital_id;
        }

        if ($search_query !== "") {
            $sql .= "
                AND (
                    p.full_name LIKE CONCAT('%', ?, '%')
                    OR p.national_id LIKE CONCAT('%', ?, '%')
                    OR p.phone LIKE CONCAT('%', ?, '%')
                )
            ";

            $types .= "sss";
            $params[] = $search_query;
            $params[] = $search_query;
            $params[] = $search_query;
        }

        $sql .= " ORDER BY p.patient_id DESC LIMIT 50";

        $stmt = $this->conn->prepare($sql);

        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $patients = [];

        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }

        $stmt->close();

        return $patients;
    }

    // ── Hospital-specific patient list ───────────────────────────────────────
 public function getPatientsByHospital($hospital_id, $search = "") {
    /*
        Shared patient list mode:
        All active patients appear in all hospital dashboards.
        hospital_id is ignored here intentionally.
    */

    $sql = "
        SELECT DISTINCT
            p.patient_id,
            p.full_name,
            p.national_id,
            p.phone,
            p.gender,
            p.address,
            p.is_active,
            p.created_at,
            p.insurance_id,
            p.hospital_id,
            mi.name AS insurance_name
        FROM patients p
        LEFT JOIN medical_insurances mi
            ON mi.insurance_id = p.insurance_id
        WHERE p.is_active = 1
    ";

    $types = "";
    $params = [];

    if ($search !== "") {
        $sql .= "
            AND (
                p.full_name LIKE CONCAT('%', ?, '%')
                OR p.national_id LIKE CONCAT('%', ?, '%')
                OR p.phone LIKE CONCAT('%', ?, '%')
            )
        ";

        $types .= "sss";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    $sql .= " ORDER BY p.patient_id DESC LIMIT 50";

    $stmt = $this->conn->prepare($sql);

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];

    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }

    $stmt->close();

    return $out;
}

    // ── Insurance-specific patient list ──────────────────────────────────────
    public function getPatientsByInsurance($insurance_id, $search = "") {
        $sql = "
            SELECT 
                p.patient_id,
                p.full_name,
                p.national_id,
                p.phone,
                p.gender,
                p.address,
                p.insurance_id,
                p.hospital_id,
                pp.policy_number,
                pp.start_date,
                pp.end_date,
                pp.status,
                ip.plan_name
            FROM patients p
            LEFT JOIN patient_policy pp 
                ON pp.patient_id = p.patient_id 
               AND pp.insurance_id = ?
            LEFT JOIN insurance_plan ip 
                ON ip.id = pp.insurance_plan_id
            WHERE p.insurance_id = ?
              AND p.is_active = 1
        ";

        $types = "ii";
        $params = [$insurance_id, $insurance_id];

        if ($search !== "") {
            $sql .= "
                AND (
                    p.full_name LIKE CONCAT('%', ?, '%')
                    OR p.national_id LIKE CONCAT('%', ?, '%')
                    OR p.phone LIKE CONCAT('%', ?, '%')
                )
            ";

            $types .= "sss";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY p.patient_id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();

        $patients = [];

        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }

        $stmt->close();

        return $patients;
    }

    // ── DOB from Egyptian National ID ────────────────────────────────────────
    public function getDOBFromNationalId() {
        $nid = trim($this->national_id);

        if (!preg_match('/^\d{14}$/', $nid)) {
            return null;
        }

        $centuryDigit = (int)$nid[0];
        $yy = (int)substr($nid, 1, 2);
        $mm = (int)substr($nid, 3, 2);
        $dd = (int)substr($nid, 5, 2);

        $century = ($centuryDigit === 2)
            ? 1900
            : (($centuryDigit === 3) ? 2000 : null);

        if ($century === null) {
            return null;
        }

        $year = $century + $yy;

        if (!checkdate($mm, $dd, $year)) {
            return null;
        }

        return sprintf("%04d-%02d-%02d", $year, $mm, $dd);
    }

    // ── Age from Egyptian National ID ────────────────────────────────────────
    public function getAge() {
        $dob = $this->getDOBFromNationalId();

        if (!$dob) {
            return null;
        }

        try {
            $d = new DateTime($dob);
            $now = new DateTime();

            return (int)$now->diff($d)->y;
        } catch (Exception $e) {
            return null;
        }
    }
}
<?php

class Admin {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getCounts() {
        $data = [];

        $queries = [
            "totalPatients" => "SELECT COUNT(*) AS total FROM patients",
            "totalHospitals" => "SELECT COUNT(*) AS total FROM hospitals",
            "totalInsurances" => "SELECT COUNT(*) AS total FROM medical_insurances",
            "totalUsers" => "SELECT COUNT(*) AS total FROM users",
            "activeUsers" => "SELECT COUNT(*) AS total FROM users WHERE is_active = 1",
            "completedPolicies" => "SELECT COUNT(*) AS total FROM medical_insurances WHERE policy_completed = 1",
            "pendingPolicies" => "SELECT COUNT(*) AS total FROM medical_insurances WHERE policy_completed = 0"
        ];

        foreach ($queries as $key => $sql) {
            $res = $this->conn->query($sql);
            $row = $res->fetch_assoc();
            $data[$key] = (int)$row['total'];
        }

        return $data;
    }

    public function getRecentPatients() {
        $res = $this->conn->query("
            SELECT patient_id, full_name, national_id, phone, gender
            FROM patients
            ORDER BY patient_id DESC
            LIMIT 5
        ");

        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getRecentUsers() {
        $res = $this->conn->query("
            SELECT u.full_name, r.role_name, u.email, u.is_active
            FROM users u
            INNER JOIN roles r ON r.role_id = u.role_id
            ORDER BY u.user_id DESC
            LIMIT 4
        ");

        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getRecentRecords() {
        $res = $this->conn->query("
            SELECT record_id, patient_id, diagnosis, checkin_date, checkout_date
            FROM medical_records
            ORDER BY record_id DESC
            LIMIT 5
        ");

        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
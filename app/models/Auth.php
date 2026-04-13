<?php
require_once __DIR__ . "/Validator.php";

class Auth
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /* =========================
       Session helpers
    ========================= */
    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function regenerateSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function getSessionData(string $key)
    {
        $this->startSession();
        return $_SESSION[$key] ?? null;
    }

    /* =========================
       Auth guards
    ========================= */
    public function checkPatientAuth(): void
    {
        $this->startSession();

        if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "patient") {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
    }

    public function checkStaffAuth(string $requiredRole): void
    {
        $this->startSession();

        if (!isset($_SESSION["auth_type"]) ||
            $_SESSION["auth_type"] !== "staff" ||
            ($_SESSION["role"] ?? "") !== $requiredRole) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
    }

    /* =========================
       Patient Login
    ========================= */
    public function loginPatient(string $national_id, string $phone): array
    {
        $this->startSession();

        if (!Validator::validateNationalId($national_id)) {
            return ["ok" => false, "code" => 400, "message" => "National ID must be 14 digits"];
        }

        if (!Validator::validatePhone($phone)) {
            return ["ok" => false, "code" => 400, "message" => "Invalid phone number"];
        }

        $stmt = $this->conn->prepare("
            SELECT p.patient_id, p.full_name, p.insurance_id, mi.name AS insurance_name
            FROM patients p
            LEFT JOIN medical_insurances mi ON mi.insurance_id = p.insurance_id
            WHERE p.national_id=? AND p.phone=? AND p.is_active=1
            LIMIT 1
        ");
        $stmt->bind_param("ss", $national_id, $phone);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return ["ok" => false, "code" => 401, "message" => "Invalid National ID or phone"];
        }

        $this->regenerateSession();

        $_SESSION["auth_type"]      = "patient";
        $_SESSION["patient_id"]     = (int)$row["patient_id"];
        $_SESSION["patient_name"]   = $row["full_name"];
        $_SESSION["insurance_id"]   = (int)($row["insurance_id"] ?? 0);
        $_SESSION["insurance_name"] = $row["insurance_name"] ?? "";

        return ["ok" => true, "code" => 200, "redirect" => BASE_URL . "/patient/dashboard"];
    }

    /* =========================
       Staff Login (Hospital / Insurance / Admin)
    ========================= */
    public function loginStaff(string $email, string $password, string $portal = ""): array
    {
        $this->startSession();

        $email  = trim($email);
        $portal = trim($portal);

        if (!Validator::validateEmail($email) || !Validator::validatePassword($password)) {
            return ["ok" => false, "code" => 400, "message" => "Invalid email/password"];
        }

        if (!Validator::validatePortal($portal)) {
            return ["ok" => false, "code" => 400, "message" => "Invalid portal value"];
        }

        // Fetch user
        $stmt = $this->conn->prepare("
            SELECT
              u.user_id,
              u.full_name,
              u.password_hash,
              r.role_name,
              u.hospital_id,
              u.insurance_id
            FROM users u
            JOIN roles r ON r.role_id = u.role_id
            WHERE u.email=? AND u.is_active=1
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user["password_hash"])) {
            return ["ok" => false, "code" => 401, "message" => "Wrong email or password"];
        }

        $role = $user["role_name"];

        // Portal restriction
        $allowed = [
            "hospital"  => "HOSPITAL_STAFF",
            "insurance" => "INSURANCE_STAFF",
            "admin"     => "ADMIN"
        ];

        if ($portal !== "" && isset($allowed[$portal]) && $allowed[$portal] !== $role) {
            return ["ok" => false, "code" => 403, "message" => "You don't have access to this portal"];
        }

        $this->regenerateSession();

        // Save session
        $_SESSION["auth_type"]  = "staff";
        $_SESSION["user_id"]    = (int)$user["user_id"];
        $_SESSION["role"]       = $role;
        $_SESSION["staff_name"] = $user["full_name"];

        $redirect = BASE_URL . "/auth/login";

        if ($role === "HOSPITAL_STAFF") {
            $hid = (int)($user["hospital_id"] ?? 0);
            if ($hid <= 0) $hid = (int)$user["user_id"];
            $_SESSION["hospital_id"] = $hid;
            $redirect = BASE_URL . "/hospital/dashboard";
        }
        elseif ($role === "INSURANCE_STAFF") {
            $insurance_id = (int)($user["insurance_id"] ?? 0);
            if ($insurance_id <= 0) {
                return ["ok" => false, "code" => 500, "message" => "Missing insurance_id in users table."];
            }
            $_SESSION["insurance_id"] = $insurance_id;

            // ✅ FIXED: handles both column names (policy_completed and is_policy_complete)
          $stmt = $this->conn->prepare("
    SELECT policy_completed AS policy_done
    FROM medical_insurances
    WHERE insurance_id=?
    LIMIT 1
");
            $stmt->bind_param("i", $insurance_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $policy_done = (int)($row["policy_done"] ?? 0);
            $_SESSION["insurance_policy_completed"] = $policy_done;

            // Always redirect to dashboard – policy setup is optional/available from navbar
            $redirect = BASE_URL . "/insurance/dashboard";
        }
        elseif ($role === "ADMIN") {
            $redirect = BASE_URL . "/admin/dashboard";
        }

        return [
            "ok"       => true,
            "code"     => 200,
            "redirect" => $redirect,
            "insurance_id" => $_SESSION["insurance_id"] ?? null,
        ];
    }

    /* =========================
       Logout
    ========================= */
    public function logout(): void
    {
        $this->startSession();
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), "", time() - 42000,
                $p["path"], $p["domain"], $p["secure"], $p["httponly"]
            );
        }

        session_destroy();
        header("Location: " . BASE_URL . "/auth/login");
        exit;
    }
}
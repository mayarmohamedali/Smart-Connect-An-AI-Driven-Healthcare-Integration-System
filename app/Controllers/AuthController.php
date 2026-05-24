<?php

class AuthController {

    // ──────────────────────────────────────────────
    // GET  /auth/login  →  show the login page
    // ──────────────────────────────────────────────

    public function policy(): void {
    header('Location: ' . BASE_URL . '/insurance/policy');
    exit;
}
    public function login(): void {
        // If already logged in, destroy the session first so the login form always shows
        if (Guard::isLoggedIn()) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
            session_start();
        }
        require_once ROOT . '/app/views/auth/login.php';
    }

    // ──────────────────────────────────────────────
    // GET /auth/logout
    // ──────────────────────────────────────────────
    public function logout(): void {
        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->startSession();
        // Auth::logout() destroys session + redirects to login.html — override that
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }

    // ──────────────────────────────────────────────
    // POST /auth/patientLogin  (JSON API)
    // ──────────────────────────────────────────────
    public function patientLogin(): void {
        header('Content-Type: application/json; charset=utf-8');

        $db   = new Database();
        $conn = $db->getConnection();
        $auth = new Auth($conn);
        $auth->startSession();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Invalid JSON body']);
            return;
        }

        $national_id = trim($input['national_id'] ?? '');
        $phone       = trim($input['phone']       ?? '');

        if (!Validator::validateNationalId($national_id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'National ID must be 14 digits']);
            return;
        }

        if (!Validator::validatePhone($phone)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Invalid phone number']);
            return;
        }

        $result = $auth->loginPatient($national_id, $phone);

        // Override redirect to MVC route
        if ($result['ok']) {
            $result['redirect'] = BASE_URL . '/patient/dashboard';
        }

        http_response_code($result['code'] ?? ($result['ok'] ? 200 : 401));
        unset($result['code']);
        echo json_encode($result);
        $db->close();
    }



    
    // ──────────────────────────────────────────────
    // POST /auth/staffLogin  (JSON API)
    // ──────────────────────────────────────────────
    public function staffLogin(): void {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $db   = new Database();
            $conn = $db->getConnection();
            $auth = new Auth($conn);
            $auth->startSession();

            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => 'Invalid JSON body']);
                return;
            }

            $email    = trim($input['email']    ?? '');
            $password = (string) ($input['password'] ?? '');
            $portal   = trim($input['portal']   ?? '');

            if (!Validator::validateEmail($email) || !Validator::validatePassword($password)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => 'Invalid email/password']);
                return;
            }

            if (!Validator::validatePortal($portal)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => 'Invalid portal value']);
                return;
            }

            $result = $auth->loginStaff($email, $password, $portal);

            // Override redirects to MVC routes
            if ($result['ok']) {
                $role = $_SESSION['role'] ?? '';
                if ($role === 'HOSPITAL_STAFF') {
                    $result['redirect'] = BASE_URL . '/hospital/dashboard';
                } elseif ($role === 'INSURANCE_STAFF') {
                    $policy_completed = $_SESSION['insurance_policy_completed'] ?? 1;
                    $result['redirect'] = $policy_completed
                        ? BASE_URL . '/insurance/dashboard'
                        : BASE_URL . '/insurance/dashboard';
                } elseif ($role === 'ADMIN') {
                    $result['redirect'] = BASE_URL . '/admin/dashboard';
                }
            }

            http_response_code($result['ok'] ? 200 : (int)($result['code'] ?? 401));
            unset($result['code']);
            echo json_encode($result);
            $db->close();

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Server error', 'debug' => $e->getMessage()]);
        }
    }
}
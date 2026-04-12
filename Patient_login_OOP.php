<?php
/**
 * Patient Login API - OOP Version
 */

header("Content-Type: application/json; charset=utf-8");
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Validator.php';

try {
    // Read input safely
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "message" => "Invalid JSON body"
        ]);
        exit;
    }

    $national_id = trim($input["national_id"] ?? "");
    $phone = trim($input["phone"] ?? "");

    // Validate
    if (!Validator::validateNationalId($national_id)) {
        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "message" => "National ID must be 14 digits"
        ]);
        exit;
    }

    if (!Validator::validatePhone($phone)) {
        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "message" => "Invalid phone number"
        ]);
        exit;
    }

    // Initialize after input validation
    $db = new Database();
    $conn = $db->getConnection();
    $auth = new Auth($conn);

    // Login
    $result = $auth->loginPatient($national_id, $phone);

    if (!($result["ok"] ?? false)) {
        http_response_code((int)($result["code"] ?? 401));
        unset($result["code"]);
        echo json_encode($result);
        $db->close();
        exit;
    }

    http_response_code(200);
    unset($result["code"]);
    echo json_encode($result);

    $db->close();
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "message" => "Server error",
        "debug" => $e->getMessage()
    ]);
    exit;
}
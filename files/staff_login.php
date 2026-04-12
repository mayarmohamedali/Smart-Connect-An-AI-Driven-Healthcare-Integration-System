<?php
/**
 * Staff Login API -
 * Hospital / Insurance / Admin
 */

header("Content-Type: application/json; charset=utf-8");
session_start();

require_once __DIR__ . "/Database.php";
require_once __DIR__ . "/Auth.php";
require_once __DIR__ . "/Validator.php";

try {
    // Initialize
    $db = new Database();
    $conn = $db->getConnection();
    $auth = new Auth($conn);

    // Read JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "message" => "Invalid JSON body"]);
        exit;
    }

    $email    = trim($input["email"] ?? "");
    $password = (string)($input["password"] ?? "");
    $portal   = trim($input["portal"] ?? ""); // hospital / insurance / admin

    // Validate
    if (!Validator::validateEmail($email) || !Validator::validatePassword($password)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "message" => "Invalid email/password"]);
        exit;
    }

    // Login using Auth class
    $result = $auth->loginStaff($email, $password, $portal);

    if (!($result["ok"] ?? false)) {
        http_response_code((int)($result["code"] ?? 401));
        unset($result["code"]);
        echo json_encode($result);
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

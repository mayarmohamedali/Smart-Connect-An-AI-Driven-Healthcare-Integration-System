<?php
session_start();
require_once "db.php";

// Create PDO connection
$pdo = new PDO(
    "mysql:host=localhost;dbname=smart_connect;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/* -----------------------------
   1. Collect main identifiers
------------------------------*/
// Get insurance_id from session (for logged-in insurance staff)
$insurance_id = $_SESSION['insurance_id'] ?? 1; // Use session or default to 1
$category_id      = $_POST['category_id'] ?? null;
$customer_type_id = $_POST['customer_type_id'] ?? null;

// Validate required fields
if (!$category_id || !$customer_type_id) {
    die("Error: Category and Customer Type are required");
}

$plan_name = "Plan {$insurance_id}-{$category_id}-{$customer_type_id}";

/* -----------------------------
   2. Create or update insurance plan
------------------------------*/
$stmt = $pdo->prepare("
    INSERT INTO insurance_plan
        (insurance_id, category_id, customer_type_id, plan_name)
    VALUES
        (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        plan_name = VALUES(plan_name),
        insurance_id = VALUES(insurance_id)
");

$stmt->execute([
    $insurance_id,
    $category_id,
    $customer_type_id,
    $plan_name
]);

/* -----------------------------
   2.5. Get the plan ID
------------------------------*/
$stmt = $pdo->prepare("
    SELECT id FROM insurance_plan
    WHERE insurance_id = ? AND category_id = ? AND customer_type_id = ?
");
$stmt->execute([$insurance_id, $category_id, $customer_type_id]);
$insurance_plan_id = $stmt->fetchColumn();

if (!$insurance_plan_id) {
    die("Error: Failed to create insurance plan");
}

/* -----------------------------
   3. Service name → service.id mapping
------------------------------*/
$serviceMap = [
    'checkup'    => 1,
    'operations' => 2,
    'maternity'  => 3,
    'dental'     => 4,
    'optical'    => 5
];

/* -----------------------------
   4. Insert or update service coverage
------------------------------*/
foreach ($serviceMap as $serviceName => $serviceId) {
    
    // Check if this service has coverage data
    if (!isset($_POST["coverage_$serviceName"])) {
        continue;
    }

    $coverage  = $_POST["coverage_$serviceName"] ?? 0;
    $threshold = $_POST["threshold_$serviceName"] ?? 0;
    $copay     = $_POST["copay_$serviceName"] ?? 0;
    $deductible = $_POST["deductible_$serviceName"] ?? 0;

    $stmt = $pdo->prepare("
        INSERT INTO plan_service_coverage
            (insurance_plan_id, service_id, is_enabled,
             coverage_percent, threshold_egp,
             copayment_percent, deductible_egp)
        VALUES (?, ?, 1, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            is_enabled = VALUES(is_enabled),
            coverage_percent = VALUES(coverage_percent),
            threshold_egp = VALUES(threshold_egp),
            copayment_percent = VALUES(copayment_percent),
            deductible_egp = VALUES(deductible_egp)
    ");

    $stmt->execute([
        $insurance_plan_id,
        $serviceId,
        $coverage,
        $threshold,
        $copay,
        $deductible
    ]);
}

/* -----------------------------
   5. Mark policy as completed for this insurance
------------------------------*/
$update = $pdo->prepare("
    UPDATE medical_insurances
    SET policy_completed = 1
    WHERE insurance_id = ?
");
$update->execute([$insurance_id]);

/* -----------------------------
   6. Update session
------------------------------*/
$_SESSION['insurance_policy_completed'] = 1;

/* -----------------------------
   7. Redirect
------------------------------*/
header("Location: InsuranceDashboard.php?success=1");
exit;
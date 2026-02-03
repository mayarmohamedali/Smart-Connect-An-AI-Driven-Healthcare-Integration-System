<?php
session_start();
require_once "db.php"; // MUST be before using $pdo

$pdo = new PDO(
    "mysql:host=localhost;dbname=smart_connect;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/* -----------------------------
   1. Collect main identifiers
------------------------------*/
$insurance_id     = 1; // AXA for now (can be dropdown later)
$category_id      = $_POST['category_id'];
$customer_type_id = $_POST['customer_type_id'];

$plan_name = "Plan {$insurance_id}-{$category_id}-{$customer_type_id}";

/* -----------------------------
   2. Create insurance plan
------------------------------*/
$stmt = $pdo->prepare("
    INSERT INTO insurance_plan
        (insurance_id, category_id, customer_type_id, plan_name)
    VALUES
        (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        plan_name = VALUES(plan_name)
");

$plan_name = "Plan {$insurance_id}-{$category_id}-{$customer_type_id}";

$stmt->execute([
    $insurance_id,
    $category_id,
    $customer_type_id,
    $plan_name
]);

$update = $conn->prepare("
    UPDATE medical_insurances
    SET policy_completed = 1
    WHERE insurance_id = ?
");
$update->execute([$insurance_id]);


// Check if plan exists or get its ID after insert
$stmt = $pdo->prepare("
    SELECT id FROM insurance_plan
    WHERE insurance_id = ? AND category_id = ? AND customer_type_id = ?
");
$stmt->execute([$insurance_id, $category_id, $customer_type_id]);
$insurance_plan_id = $stmt->fetchColumn();


/* -----------------------------
   3. Service name → service.id
------------------------------*/
$serviceMap = [
    'checkup'    => 1,
    'operations' => 2,
    'maternity'  => 3,
    'dental'     => 4,
    'optical'    => 5
];

/* -----------------------------
   4. Insert service coverage
------------------------------*/
foreach ($serviceMap as $serviceName => $serviceId) {

    if (!isset($_POST["coverage_$serviceName"])) {
        continue;
    }

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
    $_POST["coverage_$serviceName"],
    $_POST["threshold_$serviceName"],
    $_POST["copay_$serviceName"],
    $_POST["deductible_$serviceName"]
]);

}

/* -----------------------------
   5. Redirect
------------------------------*/
header("Location: InsuranceDashboard.php");
exit;

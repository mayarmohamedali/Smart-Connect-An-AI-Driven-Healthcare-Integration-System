<?php
/**
 * save_all_policies.php
 * Processes form submission for all 4 policy types at once
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/InsurancePlan.php';

$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// Check authentication
if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "staff" || ($_SESSION["role"] ?? "") !== "INSURANCE_STAFF") {
    header("Location: login.html");
    exit;
}

$insurance_id = (int)($_SESSION["insurance_id"] ?? 0);
if ($insurance_id <= 0) die("Missing insurance_id in session");

$insurancePlan = new InsurancePlan($conn);

// Service ID mapping
$serviceMap = [
    'checkup'    => 1,
    'operations' => 2,
    'maternity'  => 3,
    'dental'     => 4,
    'optical'    => 5
];

// Define all 4 policy types with their identifiers
$policy_types = [
    'normal_individual' => ['category_id' => 1, 'customer_type_id' => 1],
    'normal_company'    => ['category_id' => 1, 'customer_type_id' => 2],
    'vip_individual'    => ['category_id' => 2, 'customer_type_id' => 1],
    'vip_company'       => ['category_id' => 2, 'customer_type_id' => 2],
];

// Helper function to safely get numeric values
$get_numeric = function(string $key, $default = 0) {
    if (!isset($_POST[$key]) || $_POST[$key] === "") return $default;
    if (!is_numeric($_POST[$key])) return $default;
    return $_POST[$key] + 0;
};

// Process each of the 4 policy types
foreach ($policy_types as $prefix => $type) {
    
    $category_id = $type['category_id'];
    $customer_type_id = $type['customer_type_id'];
    
    // Create or get the plan
    $plan_id = $insurancePlan->getOrCreatePlan($insurance_id, $category_id, $customer_type_id);
    
    if (!$plan_id) {
        die("Error: Failed to create plan for {$prefix}");
    }
    
    // Process each service for this policy type
    foreach ($serviceMap as $serviceName => $serviceId) {
        
        // Check if service is enabled (for optional services)
        $enabled_key = "{$prefix}_enabled_{$serviceName}";
        $is_enabled = isset($_POST[$enabled_key]) && $_POST[$enabled_key] == "1" ? 1 : 0;
        
        // For required services (checkup, operations), always enable
        if ($serviceName === 'checkup' || $serviceName === 'operations') {
            $is_enabled = 1;
        }
        
        // Get values from POST with prefix
        $coverage_key = "{$prefix}_coverage_{$serviceName}";
        $threshold_key = "{$prefix}_threshold_{$serviceName}";
        $copay_key = "{$prefix}_copay_{$serviceName}";
        $deductible_key = "{$prefix}_deductible_{$serviceName}";
        
        $coverage = $get_numeric($coverage_key, 0);
        $threshold = $get_numeric($threshold_key, 0);
        $copay = $get_numeric($copay_key, 0);
        $deductible = $get_numeric($deductible_key, 0);
        
        // Save to database
        $insurancePlan->saveServiceCoverage(
            $plan_id,
            $serviceId,
            $is_enabled,
            $coverage,
            $threshold,
            $copay,
            $deductible
        );
    }
}

// Update insurance completion status
$insurancePlan->updateInsuranceCompletionStatus($insurance_id);

// Update session
$_SESSION["insurance_policy_completed"] = 1;

// Redirect with success message
header("Location: Policy.php?success=all_created");
exit;
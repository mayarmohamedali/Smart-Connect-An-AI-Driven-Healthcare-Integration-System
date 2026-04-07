<?php
/**
 * save_policy.php
 * Handles POST from EditPolicy.php — creates or updates a policy + its service coverage
 */

session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/InsurancePlan.php';
require_once __DIR__ . '/Validator.php';

$db   = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// ── 1. Auth guard ────────────────────────────────────────────────────────────
$auth->checkStaffAuth("INSURANCE_STAFF");

$insurance_id = (int)$auth->getSessionData("insurance_id");
if ($insurance_id <= 0) die("Missing insurance_id in session");

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Policy.php");
    exit;
}

// ── 2. Read & validate required POST fields ──────────────────────────────────
$category_id      = (int)($_POST['category_id']      ?? 0);
$customer_type_id = (int)($_POST['customer_type_id'] ?? 0);

if ($category_id <= 0 || $customer_type_id <= 0) {
    die("Invalid policy type submitted.");
}

// Allowed category / customer type combos
$allowed_categories     = [1, 2];
$allowed_customer_types = [1, 2];

if (!in_array($category_id, $allowed_categories) || !in_array($customer_type_id, $allowed_customer_types)) {
    die("Invalid category or customer type.");
}

// ── 3. Service definitions ───────────────────────────────────────────────────
// Maps service slug → [ service_id, is_required ]
$service_map = [
    'checkup'    => ['id' => 1, 'required' => true],
    'operations' => ['id' => 2, 'required' => true],
    'maternity'  => ['id' => 3, 'required' => false],
    'dental'     => ['id' => 4, 'required' => false],
    'optical'    => ['id' => 5, 'required' => false],
];

// Which optional services were toggled ON
$enabled_optional = (array)($_POST['coverage_services'] ?? []);
// The form also sends required service slugs in coverage_services[], strip them
// so we only use this array for optional toggle detection
$enabled_optional = array_filter($enabled_optional, fn($s) => !in_array($s, ['checkup', 'operations']));

// ── 4. Helper – sanitize a numeric field ─────────────────────────────────────
function getNumeric(string $key, float $default = 0): float {
    $val = $_POST[$key] ?? $default;
    return max(0, (float)$val);
}

// Build per-service data from POST
$services_to_save = [];

foreach ($service_map as $slug => $meta) {
    $is_required = $meta['required'];
    $service_id  = $meta['id'];

    // Required services are always enabled; optional only if checkbox was on
    $is_enabled = $is_required ? 1 : (in_array($slug, $enabled_optional) ? 1 : 0);

    $coverage   = getNumeric("coverage_{$slug}");
    $threshold  = getNumeric("threshold_{$slug}");
    $copay      = getNumeric("copay_{$slug}");
    $deductible = getNumeric("deductible_{$slug}");

    // Basic range validation for percentages
    $coverage = min(100, $coverage);
    $copay    = min(100, $copay);

    $services_to_save[] = [
        'service_id'  => $service_id,
        'is_enabled'  => $is_enabled,
        'coverage'    => $coverage,
        'threshold'   => $threshold,
        'copay'       => $copay,
        'deductible'  => $deductible,
    ];
}

// ── 5. Persist to database ───────────────────────────────────────────────────
$insurancePlan = new InsurancePlan($conn);

// Get existing plan_id or create a new insurance_plan row
$plan_id = $insurancePlan->getOrCreatePlan($insurance_id, $category_id, $customer_type_id);

if (!$plan_id) {
    die("Failed to create or retrieve the insurance plan. Please try again.");
}

// Save / upsert every service row
$all_ok = true;
foreach ($services_to_save as $svc) {
    $ok = $insurancePlan->saveServiceCoverage(
        $plan_id,
        $svc['service_id'],
        $svc['is_enabled'],
        $svc['coverage'],
        $svc['threshold'],
        $svc['copay'],
        $svc['deductible']
    );
    if (!$ok) {
        $all_ok = false;
    }
}

// Update the insurance-level completion flag
$insurancePlan->updateInsuranceCompletionStatus($insurance_id);

$db->close();

// ── 6. Redirect with result ──────────────────────────────────────────────────
if ($all_ok) {
    // Pass a success flash message via session
    $_SESSION['flash_success'] = "Policy updated successfully.";
    header("Location: Policy.php");
} else {
    $_SESSION['flash_error'] = "Some services could not be saved. Please try again.";
    header("Location: EditPolicy.php?category_id={$category_id}&customer_type_id={$customer_type_id}");
}
exit;
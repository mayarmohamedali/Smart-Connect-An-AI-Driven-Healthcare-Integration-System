<?php

/**
 * process_claim.php
 *
 * Handles automatic claim processing after a patient submits a claim.
 *
 * Flow:
 *  1. Receive POST: service_type, claim_amount
 *  2. Verify patient is active and has an insurance_id
 *  3. Load the patient's policy from patient_policy
 *  4. Check policy is active and not expired
 *  5. Resolve the submitted service name to a service_id
 *  6. Look up plan_service_coverage for that plan + service
 *  7. Accept if: policy active + service covered (is_enabled=1) + amount <= threshold
 *     Reject if: any condition above fails
 *  8. Insert claim row, then immediately update claim_status to Accepted or Rejected
 *
 * Place this file at:
 *   app/Controllers/process_claim.php
 * OR call it directly from PatientController::submitClaim() by requiring it.
 *
 * Usage as standalone controller method — add to PatientController:
 *   public function processClaim(): void {
 *       require_once ROOT . '/app/controllers/process_claim.php';
 *       processClaim();
 *   }
 *
 * And update the form action in dashboard.php to:
 *   action="<?= BASE_URL ?>/patient/processClaim"
 */

/**
 * Map form service_type values → plan_service_coverage.service_id
 *
 * The form in dashboard.php sends short labels (e.g. "Checkup").
 * The plan_service_coverage table uses integer service_id values.
 * This map bridges the two without touching the database schema.
 *
 * Based on InsurancePlan::$serviceNames:
 *   1 => 'Checkup / Consultation'
 *   2 => 'Operations / Surgery'
 *   3 => 'Maternity Care'
 *   4 => 'Dental Services'
 *   5 => 'Optical Services'
 */
function resolveServiceId(string $service_type): ?int {
    $map = [
        'checkup'    => 1,
        'operations' => 2,
        'surgery'    => 2,   // Surgery maps to same id as Operations
        'maternity'  => 3,
        'dental'     => 4,
        'optical'    => 5,
    ];

    $key = strtolower(trim($service_type));
    return $map[$key] ?? null;
}

/**
 * Core claim processing function.
 * Reads POST data, runs all checks, inserts claim, sets final status.
 */
function processClaim(): void {

    // ── Bootstrap: load config + core files ──────────────────────────────
    // ROOT is defined in public/index.php as dirname(__DIR__, 2)
    require_once ROOT . '/config/database.php';
    require_once ROOT . '/app/models/Database.php';
    require_once ROOT . '/app/models/Auth.php';
    require_once ROOT . '/app/models/Claim.php';   // the Claim model we created

    $db   = new Database();
    $conn = $db->getConnection();

    // ── Auth check ────────────────────────────────────────────────────────
    $auth = new Auth($conn);
    $auth->checkPatientAuth();

    $patient_id = (int) $auth->getSessionData('patient_id');
    if ($patient_id <= 0) {
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }

    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/patient/dashboard');
        exit;
    }

    // ── Helper: redirect with feedback ───────────────────────────────────
    $redirect = function (string $status, string $msg = '') {
        $url = BASE_URL . '/patient/dashboard?claim=' . urlencode($status);
        if ($msg !== '') {
            $url .= '&msg=' . urlencode($msg);
        }
        header('Location: ' . $url);
        exit;
    };

    // ── Step 1: Validate input ────────────────────────────────────────────
    $service_type = trim($_POST['service_type'] ?? '');
    $claim_amount = trim($_POST['claim_amount'] ?? '');

    if ($service_type === '' || $claim_amount === '') {
        $redirect('error', 'Service type and claim amount are required.');
    }

    $claim_amount = (float) $claim_amount;

    if ($claim_amount <= 0) {
        $redirect('error', 'Claim amount must be greater than zero.');
    }

    // ── Step 2: Get patient's insurance_id ────────────────────────────────
    $stmt = $conn->prepare("
        SELECT insurance_id
        FROM patients
        WHERE patient_id = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $patientRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$patientRow || !$patientRow['insurance_id']) {
        // Reject: patient has no active insurance account
        $redirect('rejected', 'No active insurance found for your account.');
    }

    $insurance_id = (int) $patientRow['insurance_id'];

    // ── Step 3: Load the patient's policy ────────────────────────────────
    $stmt = $conn->prepare("
        SELECT
            patient_policy_id,
            insurance_plan_id,
            status,
            end_date
        FROM patient_policy
        WHERE patient_id = ?
        ORDER BY patient_policy_id DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $policy = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$policy) {
        // Reject: no policy record at all
        $redirect('rejected', 'No insurance policy found for your account.');
    }

    // ── Step 4a: Policy must be active ────────────────────────────────────
    if (strtolower($policy['status']) !== 'active') {
        // Reject: policy is inactive/cancelled
        $redirect('rejected', 'Your insurance policy is not active.');
    }

    // ── Step 4b: Policy must not be expired ───────────────────────────────
    if (!empty($policy['end_date'])) {
        $today    = new DateTime('today');
        $end_date = new DateTime($policy['end_date']);
        if ($end_date < $today) {
            // Reject: policy has passed its end date
            $redirect('rejected', 'Your insurance policy has expired.');
        }
    }

    $insurance_plan_id = (int) $policy['insurance_plan_id'];

    // ── Step 5: Resolve service_type to a service_id ─────────────────────
    $service_id = resolveServiceId($service_type);

    if ($service_id === null) {
        // Reject: submitted service name doesn't match any known service
        $redirect('rejected', 'The selected service is not recognized.');
    }

    // ── Step 6: Check plan_service_coverage ──────────────────────────────
    $stmt = $conn->prepare("
        SELECT
            is_enabled,
            threshold_egp
        FROM plan_service_coverage
        WHERE insurance_plan_id = ?
          AND service_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $insurance_plan_id, $service_id);
    $stmt->execute();
    $coverage = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$coverage) {
        // Reject: this service has no coverage rule configured for this plan
        $redirect('rejected', 'This service is not covered under your insurance plan.');
    }

    if ((int) $coverage['is_enabled'] !== 1) {
        // Reject: service exists in plan but is disabled
        $redirect('rejected', 'This service is not covered under your insurance plan.');
    }

    $threshold = (float) $coverage['threshold_egp'];

    if ($claim_amount > $threshold) {
        // Reject: requested amount exceeds the plan's threshold for this service
        $redirect('rejected', "Claim amount exceeds the covered threshold of {$threshold} EGP for this service.");
    }

    // ── Step 7: Get the patient's latest medical record ──────────────────
    // Claims require a medical record for audit trail
    $stmt = $conn->prepare("
        SELECT record_id
        FROM medical_records
        WHERE patient_id = ?
        ORDER BY record_id DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $recordRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$recordRow) {
        $redirect('error', 'No medical record found. A claim requires at least one medical record on file.');
    }

    $record_id = (int) $recordRow['record_id'];

    // ── Step 8: Insert claim and set final status ─────────────────────────
    $claimModel = new Claim($conn);

    // Insert with status 'Pending' first (same pattern as original submitClaim)
    $claim_id = $claimModel->insertPending(
        $record_id,
        $patient_id,
        $insurance_id,
        $service_id,
        $claim_amount
    );

    if (!$claim_id) {
        $redirect('error', 'Failed to save claim. Please try again.');
    }

    // All checks passed — update status to Accepted immediately
    $claimModel->updateStatus($claim_id, 'Accepted');

    $db->close();

    // Redirect with success feedback
    $redirect('accepted', 'Your claim has been accepted successfully.');
}

// ── Entry point ───────────────────────────────────────────────────────────────
// This file is intended to be called as a controller action, not included
// directly. Calling processClaim() is done from PatientController::processClaim()
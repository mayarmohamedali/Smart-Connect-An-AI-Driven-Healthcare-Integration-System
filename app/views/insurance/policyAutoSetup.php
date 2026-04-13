<?php

/**
 * PolicyAutoSetup
 *
 * Automatically creates the 4 default insurance plan types for an insurer
 * if they don't exist yet, with sensible default coverage values.
 *
 * Used by InsuranceController::policy() to ensure all 4 policy types exist
 * before the policy management page is shown.
 */
class PolicyAutoSetup {

    private mysqli $conn;

    // Default coverage values per policy type
    // [coverage%, threshold_EGP, copay%, deductible_EGP]
    private array $defaults = [
        // service_id => [coverage, threshold, copay, deductible]
        1 => [100.0, 10000.0,   0.0,    0.0],   // Checkup
        2 => [ 80.0, 1000000.0, 20.0,   5000.0], // Operations
        3 => [ 70.0, 50000.0,   30.0,   2000.0], // Maternity
        4 => [ 60.0, 30000.0,   40.0,   1000.0], // Dental
        5 => [ 50.0, 20000.0,   50.0,    500.0], // Optical
    ];

    // VIP gets better coverage (multiplier on coverage%)
    private array $vipMultipliers = [
        1 => 1.0,  // Checkup   – already 100%
        2 => 1.1,  // Operations 80→88%
        3 => 1.1,  // Maternity  70→77%
        4 => 1.1,  // Dental     60→66%
        5 => 1.1,  // Optical    50→55%
    ];

    private array $policyTypes = [
        ['category_id' => 1, 'customer_type_id' => 1], // Normal Individual
        ['category_id' => 1, 'customer_type_id' => 2], // Normal Company
        ['category_id' => 2, 'customer_type_id' => 1], // VIP Individual
        ['category_id' => 2, 'customer_type_id' => 2], // VIP Company
    ];

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Check if all 4 policy plans exist in DB for this insurance
     */
    public function hasAllPolicies(int $insurance_id): bool {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM insurance_plan WHERE insurance_id = ?"
        );
        $stmt->bind_param("i", $insurance_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();

        return (int)($row[0] ?? 0) >= 4;
    }

    /**
     * Create default plans + service coverages for all 4 policy types
     * Only creates what is missing – safe to call multiple times
     */
    public function createDefaultPolicies(int $insurance_id): void {
        foreach ($this->policyTypes as $type) {
            $plan_id = $this->getOrCreatePlan(
                $insurance_id,
                $type['category_id'],
                $type['customer_type_id']
            );
            if (!$plan_id) continue;

            // Only seed services if none exist yet
            $check = $this->conn->prepare(
                "SELECT COUNT(*) FROM insurance_plan_services WHERE plan_id = ?"
            );
            $check->bind_param("i", $plan_id);
            $check->execute();
            $cnt = $check->get_result()->fetch_row();
            $check->close();
            if (($cnt[0] ?? 0) > 0) continue;

            $isVIP = $type['category_id'] === 2;

            foreach ($this->defaults as $service_id => $vals) {
                [$coverage, $threshold, $copay, $deductible] = $vals;

                if ($isVIP) {
                    $coverage = min(100.0, $coverage * $this->vipMultipliers[$service_id]);
                }

                $stmt = $this->conn->prepare("
                    INSERT IGNORE INTO insurance_plan_services
                        (plan_id, service_id, is_enabled, coverage_percentage, threshold, copay_percentage, deductible)
                    VALUES (?, ?, 1, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "iidddd",
                    $plan_id, $service_id,
                    $coverage, $threshold, $copay, $deductible
                );
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // ── Private helpers ────────────────────────────────────

    private function getOrCreatePlan(int $insurance_id, int $category_id, int $customer_type_id): ?int {
        // Check existing
        $stmt = $this->conn->prepare("
            SELECT plan_id FROM insurance_plan
            WHERE insurance_id=? AND category_id=? AND customer_type_id=? LIMIT 1
        ");
        $stmt->bind_param("iii", $insurance_id, $category_id, $customer_type_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) return (int)$row['plan_id'];

        // Create
        $stmt = $this->conn->prepare("
            INSERT INTO insurance_plan (insurance_id, category_id, customer_type_id)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iii", $insurance_id, $category_id, $customer_type_id);
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            $stmt->close();
            return $id;
        }
        $stmt->close();
        return null;
    }
}
<?php

class PolicyAutoSetup {

    private mysqli $conn;

    // Default coverage values per policy type
    private array $defaults = [
        1 => [100.0, 10000.0,   0.0,    0.0],   // Checkup
        2 => [ 80.0, 1000000.0, 20.0,   5000.0], // Operations
        3 => [ 70.0, 50000.0,   30.0,   2000.0], // Maternity
        4 => [ 60.0, 30000.0,   40.0,   1000.0], // Dental
        5 => [ 50.0, 20000.0,   50.0,    500.0], // Optical
    ];

    // VIP coverage multipliers
    private array $vipMultipliers = [
        1 => 1.0,
        2 => 1.1,
        3 => 1.1,
        4 => 1.1,
        5 => 1.1,
    ];

    private array $policyTypes = [
        ['category_id' => 1, 'customer_type_id' => 1],
        ['category_id' => 1, 'customer_type_id' => 2],
        ['category_id' => 2, 'customer_type_id' => 1],
        ['category_id' => 2, 'customer_type_id' => 2],
    ];

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    // Check if all 4 policies exist
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

    // Create default policies
    public function createDefaultPolicies(int $insurance_id): void {
        foreach ($this->policyTypes as $type) {

            $plan_id = $this->getOrCreatePlan(
                $insurance_id,
                $type['category_id'],
                $type['customer_type_id']
            );

            if (!$plan_id) continue;

            // ✅ FIXED: correct table + column name
            $check = $this->conn->prepare(
                "SELECT COUNT(*) FROM plan_service_coverage WHERE insurance_plan_id = ?"
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
    INSERT IGNORE INTO plan_service_coverage
    (insurance_plan_id, service_id, coverage_percent, threshold_egp, copayment_percent, deductible_egp)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iidddd",
    $plan_id,
    $service_id,
    $coverage,
    $threshold,   // keep variable name, only column changes
    $copay,
    $deductible
);

                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Get or create plan
    private function getOrCreatePlan(int $insurance_id, int $category_id, int $customer_type_id): ?int {

        // ✅ FIXED: use id (not plan_id)
        $stmt = $this->conn->prepare("
            SELECT id FROM insurance_plan
            WHERE insurance_id=? AND category_id=? AND customer_type_id=? LIMIT 1
        ");

        $stmt->bind_param("iii", $insurance_id, $category_id, $customer_type_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) return (int)$row['id'];

        // Create new plan
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
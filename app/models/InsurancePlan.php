<?php

class InsurancePlan {

    private mysqli $conn;

    // Service names mapped to service_id
    private array $serviceNames = [
        1 => 'Checkup / Consultation',
        2 => 'Operations / Surgery',
        3 => 'Maternity Care',
        4 => 'Dental Services',
        5 => 'Optical Services',
    ];

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    // ── Get plans for dropdown ─────────────────────────────
    public function getPlansByInsurance(int $insurance_id): array {
        $stmt = $this->conn->prepare("
            SELECT * 
            FROM insurance_plan 
            WHERE insurance_id = ?
        ");
        $stmt->bind_param("i", $insurance_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $plans = [];
        while ($row = $res->fetch_assoc()) {
            $plans[] = $row;
        }

        $stmt->close();
        return $plans;
    }

    // ── Validate plan belongs to insurance ─────────────────
    public function validatePlanBelongsToInsurance(int $plan_id, int $insurance_id): bool {
        $stmt = $this->conn->prepare("
            SELECT id 
            FROM insurance_plan 
            WHERE id = ? AND insurance_id = ?
        ");

        $stmt->bind_param("ii", $plan_id, $insurance_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $exists = $res->fetch_assoc();
        $stmt->close();

        return (bool)$exists;
    }

    // ── Get all policy types ───────────────────────────────
    public function getAllPoliciesForInsurance(int $insurance_id): array {

        $types = [
            ['category_id' => 1, 'customer_type_id' => 1, 'category_name' => 'Normal', 'customer_type_name' => 'Individual'],
            ['category_id' => 1, 'customer_type_id' => 2, 'category_name' => 'Normal', 'customer_type_name' => 'Company'],
            ['category_id' => 2, 'customer_type_id' => 1, 'category_name' => 'VIP', 'customer_type_name' => 'Individual'],
            ['category_id' => 2, 'customer_type_id' => 2, 'category_name' => 'VIP', 'customer_type_name' => 'Company'],
        ];

        $policies = [];

        foreach ($types as $type) {

            $plan = $this->getPlanByType(
                $insurance_id,
                $type['category_id'],
                $type['customer_type_id']
            );

            $plan_id = null;
            $is_configured = false;
            $updated_at = null;

            if ($plan) {

                // check if services exist
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) 
                    FROM plan_service_coverage 
                    WHERE insurance_plan_id = ? 
                    AND is_enabled = 1
                ");

                $stmt->bind_param("i", $plan['id']);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_row();
                $stmt->close();

                $is_configured = ($count[0] ?? 0) > 0;

                $plan_id = $plan['id'];
                $updated_at = $plan['updated_at'] ?? $plan['created_at'] ?? null;
            }

            $policies[] = array_merge($type, [
                'plan_id' => $plan_id,
                'is_configured' => $is_configured,
                'updated_at' => $updated_at
            ]);
        }

        return $policies;
    }

    // ── Completion status ──────────────────────────────────
    public function getPolicyCompletionStatus(int $insurance_id): array {

        $total = 4;
        $completed = 0;

        $types = [
            ['category_id' => 1, 'customer_type_id' => 1],
            ['category_id' => 1, 'customer_type_id' => 2],
            ['category_id' => 2, 'customer_type_id' => 1],
            ['category_id' => 2, 'customer_type_id' => 2],
        ];

        foreach ($types as $type) {

            $plan = $this->getPlanByType(
                $insurance_id,
                $type['category_id'],
                $type['customer_type_id']
            );

            if ($plan) {

                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) 
                    FROM plan_service_coverage 
                    WHERE insurance_plan_id = ? 
                    AND is_enabled = 1
                ");

                $stmt->bind_param("i", $plan['id']);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_row();
                $stmt->close();

                if (($count[0] ?? 0) > 0) {
                    $completed++;
                }
            }
        }

        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $percentage
        ];
    }

    // ── Get plan by type ───────────────────────────────────
    public function getPlanByType(int $insurance_id, int $category_id, int $customer_type_id): ?array {

        $stmt = $this->conn->prepare("
            SELECT * 
            FROM insurance_plan
            WHERE insurance_id = ? 
            AND category_id = ? 
            AND customer_type_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("iii", $insurance_id, $category_id, $customer_type_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $res ?: null;
    }

    // ── Get or create plan ────────────────────────────────
    public function getOrCreatePlan(int $insurance_id, int $category_id, int $customer_type_id): ?int {

        $existing = $this->getPlanByType($insurance_id, $category_id, $customer_type_id);

        if ($existing) {
            return (int)$existing['id'];
        }

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

    // ── Save service coverage ─────────────────────────────
    public function saveServiceCoverage(
        int $plan_id,
        int $service_id,
        int $is_enabled,
        float $coverage,
        float $threshold,
        float $copay,
        float $deductible
    ): bool {

        $stmt = $this->conn->prepare("
            REPLACE INTO plan_service_coverage
            (insurance_plan_id, service_id, is_enabled, coverage_percent, threshold_egp, copayment_percent, deductible_egp)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iiidddd",
            $plan_id,
            $service_id,
            $is_enabled,
            $coverage,
            $threshold,
            $copay,
            $deductible
        );

        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    // ── Get services of a plan ────────────────────────────
    public function getServicesByPlanId(int $plan_id): array {

        $stmt = $this->conn->prepare("
            SELECT * 
            FROM plan_service_coverage 
            WHERE insurance_plan_id = ?
        ");

        $stmt->bind_param("i", $plan_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $services = [];

        while ($row = $res->fetch_assoc()) {

            $row['service_name'] = $this->serviceNames[$row['service_id']] ?? 'Unknown Service';

            $row['coverage_percent']  = $row['coverage_percent'] ?? 0;
            $row['copayment_percent'] = $row['copayment_percent'] ?? 0;
            $row['threshold_egp']     = $row['threshold_egp'] ?? 0;
            $row['deductible_egp']    = $row['deductible_egp'] ?? 0;

            $services[] = $row;
        }

        $stmt->close();
        return $services;
    }

    // ── Update completion status ──────────────────────────
    public function updateInsuranceCompletionStatus(int $insurance_id): void {

        // Count how many of the 4 required policy types have at least
        // one enabled service saved in plan_service_coverage
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT ip.id) AS configured_plans
            FROM insurance_plan ip
            INNER JOIN plan_service_coverage psc
                ON psc.insurance_plan_id = ip.id AND psc.is_enabled = 1
            WHERE ip.insurance_id = ?
        ");
        $stmt->bind_param("i", $insurance_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Only mark completed when all 4 policy types are configured
        $is_completed = ((int)($row['configured_plans'] ?? 0) >= 4) ? 1 : 0;

        $upd = $this->conn->prepare("
            UPDATE medical_insurances
            SET policy_completed = ?
            WHERE insurance_id = ?
        ");
        $upd->bind_param("ii", $is_completed, $insurance_id);
        $upd->execute();
        $upd->close();
    }
}
<?php
/**
 * InsurancePlan.php - OOP Version
 * Handles all insurance plan and policy operations
 */

class InsurancePlan {
    private $conn;
    private $plan_id;
    private $insurance_id;
    private $category_id;
    private $customer_type_id;
    private $plan_name;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all plans for a specific insurance
     */
    public function getPlansByInsurance($insurance_id) {
        $stmt = $this->conn->prepare("
            SELECT ip.id, ip.plan_name, c.name AS category_name, ct.name AS customer_type_name
            FROM insurance_plan ip
            LEFT JOIN category c ON c.id = ip.category_id
            LEFT JOIN customer_type ct ON ct.id = ip.customer_type_id
            WHERE ip.insurance_id = ?
            ORDER BY ip.id DESC
        ");
        $stmt->bind_param("i", $insurance_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $plans = [];
        while ($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
        $stmt->close();
        return $plans;
    }
    
    /**
     * Get all 4 policy combinations with their configuration status
     */
    public function getAllPoliciesForInsurance($insurance_id) {
        // Define all 4 required policy types
        $policy_types = [
            ['category_id' => 1, 'customer_type_id' => 1, 'category_name' => 'Normal', 'customer_type_name' => 'Individual'],
            ['category_id' => 1, 'customer_type_id' => 2, 'category_name' => 'Normal', 'customer_type_name' => 'Company'],
            ['category_id' => 2, 'customer_type_id' => 1, 'category_name' => 'VIP', 'customer_type_name' => 'Individual'],
            ['category_id' => 2, 'customer_type_id' => 2, 'category_name' => 'VIP', 'customer_type_name' => 'Company'],
        ];
        
        $policies = [];
        
        foreach ($policy_types as $type) {
            // Check if this policy exists
            // Try with updated_at first, fall back if column doesn't exist
            $sql = "SELECT id, plan_name";
            
            // Check if updated_at column exists
            $check = $this->conn->query("SHOW COLUMNS FROM insurance_plan LIKE 'updated_at'");
            if ($check && $check->num_rows > 0) {
                $sql .= ", updated_at";
            }
            
            $sql .= " FROM insurance_plan
                WHERE insurance_id = ? AND category_id = ? AND customer_type_id = ?
                LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $insurance_id, $type['category_id'], $type['customer_type_id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $policy = [
                'category_id' => $type['category_id'],
                'customer_type_id' => $type['customer_type_id'],
                'category_name' => $type['category_name'],
                'customer_type_name' => $type['customer_type_name'],
                'is_configured' => !empty($result),
                'plan_id' => $result['id'] ?? null,
                'plan_name' => $result['plan_name'] ?? null,
                'updated_at' => $result['updated_at'] ?? date('Y-m-d H:i:s'),
            ];
            
            $policies[] = $policy;
        }
        
        return $policies;
    }
    
    /**
     * Get policy completion status
     */
    public function getPolicyCompletionStatus($insurance_id) {
        $total = 4; // 4 required policy types
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as completed
            FROM insurance_plan
            WHERE insurance_id = ?
        ");
        $stmt->bind_param("i", $insurance_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $completed = (int)($result['completed'] ?? 0);
        $percentage = ($completed / $total) * 100;
        
        return [
            'total' => $total,
            'completed' => $completed,
            'remaining' => $total - $completed,
            'percentage' => round($percentage, 0)
        ];
    }
    
    /**
     * Get services for a specific plan
     */
    public function getServicesByPlanId($plan_id) {
        // Check which table name exists (service or services)
        $table_name = 'services';
        $check = $this->conn->query("SHOW TABLES LIKE 'services'");
        if (!$check || $check->num_rows == 0) {
            $table_name = 'service'; // fallback to old name
        }
        
        $stmt = $this->conn->prepare("
            SELECT 
                psc.service_id,
                psc.is_enabled,
                psc.coverage_percent,
                psc.threshold_egp,
                psc.copayment_percent,
                psc.deductible_egp,
                s.name as service_name
            FROM plan_service_coverage psc
            INNER JOIN {$table_name} s ON s.id = psc.service_id
            WHERE psc.insurance_plan_id = ?
            ORDER BY psc.service_id
        ");
        $stmt->bind_param("i", $plan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        $stmt->close();
        return $services;
    }
    
    /**
     * Get or create plan ID for specific combination
     */
    public function getOrCreatePlan($insurance_id, $category_id, $customer_type_id) {
        // First, try to get existing plan
        $stmt = $this->conn->prepare("
            SELECT id FROM insurance_plan
            WHERE insurance_id = ? AND category_id = ? AND customer_type_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("iii", $insurance_id, $category_id, $customer_type_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            return (int)$result['id'];
        }
        
        // Create new plan
        $plan_name = "Plan {$insurance_id}-{$category_id}-{$customer_type_id}";
        
        $stmt = $this->conn->prepare("
            INSERT INTO insurance_plan (insurance_id, category_id, customer_type_id, plan_name)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $insurance_id, $category_id, $customer_type_id, $plan_name);
        $stmt->execute();
        $new_id = $stmt->insert_id;
        $stmt->close();
        
        return $new_id;
    }
    
    /**
     * Save or update service coverage for a plan
     */
    public function saveServiceCoverage($plan_id, $service_id, $is_enabled, $coverage, $threshold, $copay, $deductible) {
        $stmt = $this->conn->prepare("
            INSERT INTO plan_service_coverage
                (insurance_plan_id, service_id, is_enabled, coverage_percent, threshold_egp, copayment_percent, deductible_egp)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                is_enabled = VALUES(is_enabled),
                coverage_percent = VALUES(coverage_percent),
                threshold_egp = VALUES(threshold_egp),
                copayment_percent = VALUES(copayment_percent),
                deductible_egp = VALUES(deductible_egp)
        ");
        
        $stmt->bind_param("iiidddd", $plan_id, $service_id, $is_enabled, $coverage, $threshold, $copay, $deductible);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Validate that a plan belongs to a specific insurance
     */
    public function validatePlanBelongsToInsurance($plan_id, $insurance_id) {
        $stmt = $this->conn->prepare("
            SELECT id FROM insurance_plan 
            WHERE id = ? AND insurance_id = ? 
            LIMIT 1
        ");
        $stmt->bind_param("ii", $plan_id, $insurance_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)$result;
    }
    
    /**
     * Get plan details by category and customer type
     */
    public function getPlanByType($insurance_id, $category_id, $customer_type_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                ip.id as plan_id,
                ip.plan_name,
                ip.insurance_id,
                ip.category_id,
                ip.customer_type_id,
                c.name as category_name,
                ct.name as customer_type_name
            FROM insurance_plan ip
            LEFT JOIN category c ON c.id = ip.category_id
            LEFT JOIN customer_type ct ON ct.id = ip.customer_type_id
            WHERE ip.insurance_id = ? AND ip.category_id = ? AND ip.customer_type_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("iii", $insurance_id, $category_id, $customer_type_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Delete all services for a plan (used before updating)
     */
    public function deleteServicesByPlanId($plan_id) {
        $stmt = $this->conn->prepare("DELETE FROM plan_service_coverage WHERE insurance_plan_id = ?");
        $stmt->bind_param("i", $plan_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Update insurance completion status
     */
    public function updateInsuranceCompletionStatus($insurance_id) {
        // Check if all 4 policies are configured
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM insurance_plan
            WHERE insurance_id = ?
        ");
        $stmt->bind_param("i", $insurance_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $is_complete = ($result['count'] >= 4) ? 1 : 0;
        
        // Update insurance table
        $stmt = $this->conn->prepare("
            UPDATE medical_insurances
            SET policy_completed = ?
            WHERE insurance_id = ?
        ");
        $stmt->bind_param("ii", $is_complete, $insurance_id);
        $stmt->execute();
        $stmt->close();
        
        return $is_complete;
    }
}
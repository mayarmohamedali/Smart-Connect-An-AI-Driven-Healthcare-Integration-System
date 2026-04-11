<?php
/**
 * auto_create_policies.php
 * Automatically creates all 4 policy types with default values for an insurance
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/InsurancePlan.php';

class PolicyAutoSetup {
    private $conn;
    private $insurancePlan;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->insurancePlan = new InsurancePlan($conn);
    }
    
    /**
     * Create all 4 default policies for an insurance
     */
    public function createDefaultPolicies($insurance_id) {
        // Define all 4 policy types
        $policy_types = [
            ['category_id' => 1, 'customer_type_id' => 1], // Normal - Individual
            ['category_id' => 1, 'customer_type_id' => 2], // Normal - Company
            ['category_id' => 2, 'customer_type_id' => 1], // VIP - Individual
            ['category_id' => 2, 'customer_type_id' => 2], // VIP - Company
        ];
        
        $created_count = 0;
        
        foreach ($policy_types as $type) {
            // Create the plan
            $plan_id = $this->insurancePlan->getOrCreatePlan(
                $insurance_id, 
                $type['category_id'], 
                $type['customer_type_id']
            );
            
            if ($plan_id) {
                // Add default services to this plan
                $this->addDefaultServices($plan_id, $type['category_id']);
                $created_count++;
            }
        }
        
        // Mark insurance as policy completed
        if ($created_count >= 4) {
            $this->insurancePlan->updateInsuranceCompletionStatus($insurance_id);
        }
        
        return $created_count;
    }
    
    /**
     * Add default service coverage for a plan
     */
    private function addDefaultServices($plan_id, $category_id) {
        // Different defaults for Normal vs VIP
        $is_vip = ($category_id == 2);
        
        // Service 1: Checkup (always 100% coverage)
        $this->insurancePlan->saveServiceCoverage(
            $plan_id, 
            1, // service_id
            1, // is_enabled
            100, // coverage_percent
            10000, // threshold_egp
            0, // copay_percent
            0  // deductible_egp
        );
        
        // Service 2: Operations
        $this->insurancePlan->saveServiceCoverage(
            $plan_id,
            2,
            1,
            $is_vip ? 90 : 80, // VIP gets better coverage
            $is_vip ? 2000000 : 1000000, // VIP gets higher limit
            $is_vip ? 10 : 20, // VIP pays less
            $is_vip ? 3000 : 5000
        );
        
        // Service 3: Maternity (enabled by default)
        $this->insurancePlan->saveServiceCoverage(
            $plan_id,
            3,
            1,
            $is_vip ? 80 : 70,
            $is_vip ? 100000 : 50000,
            $is_vip ? 20 : 30,
            $is_vip ? 1000 : 2000
        );
        
        // Service 4: Dental (enabled by default)
        $this->insurancePlan->saveServiceCoverage(
            $plan_id,
            4,
            1,
            $is_vip ? 70 : 60,
            $is_vip ? 50000 : 30000,
            $is_vip ? 30 : 40,
            $is_vip ? 500 : 1000
        );
        
        // Service 5: Optical (enabled by default)
        $this->insurancePlan->saveServiceCoverage(
            $plan_id,
            5,
            1,
            $is_vip ? 60 : 50,
            $is_vip ? 30000 : 20000,
            $is_vip ? 40 : 50,
            $is_vip ? 0 : 500
        );
    }
    
    /**
     * Check if insurance already has all 4 policies
     */
    public function hasAllPolicies($insurance_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM insurance_plan
            WHERE insurance_id = ?
        ");
        $stmt->bind_param("i", $insurance_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return ($result['count'] >= 4);
    }
}
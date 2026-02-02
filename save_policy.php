<?php
// save_policy.php

// Database connection
$host = "localhost";
$dbname = "smart_connect";
$user = "root";   // replace with your DB user
$pass = "";       // replace with your DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Collect POST data
$category = $_POST['policyCategory'] ?? '';
$benefit = $_POST['benefitType'] ?? '';

// Normal Individual
$annual_limit = $_POST['annual_limit'] ?? null;
$max_visits = $_POST['max_visits'] ?? null;

// Normal Company
$company_size = $_POST['company_size'] ?? null;
$annual_limit_per_employee = $_POST['annual_limit_per_employee'] ?? null;

// VIP Individual
$vip_annual_limit = $_POST['vip_annual_limit'] ?? null;
$private_hospitals_access = isset($_POST['private_hospitals_access']) ? 1 : 0;

// VIP Company
$vip_company_size = $_POST['vip_company_size'] ?? null;
$vip_annual_limit_per_employee = $_POST['vip_annual_limit_per_employee'] ?? null;
$vip_network_access = isset($_POST['vip_network_access']) ? 1 : 0;

// Insert into policies table
$stmt = $pdo->prepare("
    INSERT INTO policies 
    (insurance_id, category, benefit_type, annual_limit, max_visits, company_size, annual_limit_per_employee, private_hospitals_access, vip_network_access)
    VALUES (:insurance_id, :category, :benefit_type, :annual_limit, :max_visits, :company_size, :annual_limit_per_employee, :private_hospitals_access, :vip_network_access)
");

// Assuming insurance_id = 1 for demo, you can add a select dropdown for real
$stmt->execute([
    ':insurance_id' => 1,
    ':category' => $category,
    ':benefit_type' => $benefit,
    ':annual_limit' => $annual_limit ?: $vip_annual_limit,
    ':max_visits' => $max_visits,
    ':company_size' => $company_size ?: $vip_company_size,
    ':annual_limit_per_employee' => $annual_limit_per_employee ?: $vip_annual_limit_per_employee,
    ':private_hospitals_access' => $private_hospitals_access,
    ':vip_network_access' => $vip_network_access
]);

$policy_id = $pdo->lastInsertId();

// Insert service rules
$services = ['checkup','operations','maternity','dental','optical'];

foreach ($services as $service) {
    if(isset($_POST["coverage_$service"])){
        $stmt = $pdo->prepare("
            INSERT INTO policy_services 
            (policy_id, service_name, coverage, threshold, co_payment, deductible)
            VALUES (:policy_id, :service_name, :coverage, :threshold, :co_payment, :deductible)
        ");
        $stmt->execute([
            ':policy_id' => $policy_id,
            ':service_name' => $service,
            ':coverage' => $_POST["coverage_$service"],
            ':threshold' => $_POST["threshold_$service"],
            ':co_payment' => $_POST["copay_$service"],
            ':deductible' => $_POST["deductible_$service"]
        ]);
    }
}

// Redirect after save
header("Location: InsuranceDashboard.php");
exit;

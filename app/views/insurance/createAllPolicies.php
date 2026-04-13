<?php
// Variables from InsuranceController::createAllPolicies()
// $insurance_id, $insurance_name
function e($v): string { return htmlspecialchars((string)($v??''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Configure All Policies - <?= Validator::sanitizeInput($insurance_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="<?= BASE_URL ?>/assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .policy-section {
            background: #f8f9fc;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #4e73df;
        }
        .policy-section.vip {
            border-left-color: #f6c23e;
            background: #fffbf0;
        }
        .section-header {
            background: #4e73df;
            color: white;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-header.vip {
            background: #f6c23e;
            color: #333;
        }
        .service-row {
            background: white;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #e3e6f0;
        }
        .service-header {
            font-weight: bold;
            color: #5a5c69;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3e6f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        @media (max-width: 992px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 576px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 5px;
        }
        .form-control-sm {
            font-size: 0.9rem;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #1cc88a;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .badge-required {
            background: #e74a3b;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        .sticky-submit {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 20px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 100;
            border-top: 3px solid #4e73df;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/insurance/dashboard">
            <i class="fas fa-shield-alt mr-2"></i>
            <strong><?= Validator::sanitizeInput($insurance_name) ?></strong>
        </a>
    </div>
</nav>

<div class="container-fluid mt-4 mb-5">
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="mb-2 text-primary">
                <i class="fas fa-clipboard-list mr-2"></i>
                Configure All 4 Policy Types
            </h2>
            <p class="text-muted mb-0">
                Fill in the coverage details for all 4 policy combinations. You can adjust individual services for each policy type.
            </p>
        </div>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/insurance/saveAllPolicies" id="allPoliciesForm">

        <!-- ========================================
             NORMAL - INDIVIDUAL
        ========================================= -->
        <div class="policy-section">
            <div class="section-header">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-user mr-2"></i>
                        Normal - Individual
                    </h4>
                    <small>Essential coverage for individual patients</small>
                </div>
                <span class="badge badge-light">Policy 1/4</span>
            </div>

            <!-- Checkup -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-stethoscope text-info mr-2"></i>Checkup / Consultation</span>
                    <span class="badge-required">REQUIRED</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_coverage_checkup" value="100" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_threshold_checkup" value="10000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_copay_checkup" value="0" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_deductible_checkup" value="0" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Operations -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-procedures text-danger mr-2"></i>Operations / Surgery</span>
                    <span class="badge-required">REQUIRED</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_coverage_operations" value="80" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_threshold_operations" value="1000000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_copay_operations" value="20" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_deductible_operations" value="5000" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Maternity -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-baby text-warning mr-2"></i>Maternity</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="normal_individual_enabled_maternity" value="1" checked onchange="toggleServiceInputs(this, 'normal_individual_maternity')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="normal_individual_maternity">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_coverage_maternity" value="70" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_threshold_maternity" value="50000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_copay_maternity" value="30" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_deductible_maternity" value="2000" min="0">
                    </div>
                </div>
            </div>

            <!-- Dental -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-tooth text-primary mr-2"></i>Dental</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="normal_individual_enabled_dental" value="1" checked onchange="toggleServiceInputs(this, 'normal_individual_dental')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="normal_individual_dental">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_coverage_dental" value="60" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_threshold_dental" value="30000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_copay_dental" value="40" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_deductible_dental" value="1000" min="0">
                    </div>
                </div>
            </div>

            <!-- Optical -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-glasses text-success mr-2"></i>Optical</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="normal_individual_enabled_optical" value="1" checked onchange="toggleServiceInputs(this, 'normal_individual_optical')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="normal_individual_optical">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_coverage_optical" value="50" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_threshold_optical" value="20000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_copay_optical" value="50" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_individual_deductible_optical" value="500" min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================
             NORMAL - COMPANY
        ========================================= -->
        <div class="policy-section">
            <div class="section-header">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-building mr-2"></i>
                        Normal - Company
                    </h4>
                    <small>Group coverage for corporate employees</small>
                </div>
                <span class="badge badge-light">Policy 2/4</span>
            </div>

            <!-- Same structure as Individual, but with normal_company_ prefix -->
            <!-- Checkup -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-stethoscope text-info mr-2"></i>Checkup / Consultation</span>
                    <span class="badge-required">REQUIRED</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_coverage_checkup" value="100" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_threshold_checkup" value="10000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_copay_checkup" value="0" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_deductible_checkup" value="0" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Operations -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-procedures text-danger mr-2"></i>Operations / Surgery</span>
                    <span class="badge-required">REQUIRED</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_coverage_operations" value="80" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_threshold_operations" value="1000000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_copay_operations" value="20" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_deductible_operations" value="5000" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Maternity -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-baby text-warning mr-2"></i>Maternity</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="normal_company_enabled_maternity" value="1" checked onchange="toggleServiceInputs(this, 'normal_company_maternity')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="normal_company_maternity">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_coverage_maternity" value="70" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_threshold_maternity" value="50000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_copay_maternity" value="30" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_deductible_maternity" value="2000" min="0">
                    </div>
                </div>
            </div>

            <!-- Dental -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-tooth text-primary mr-2"></i>Dental</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="normal_company_enabled_dental" value="1" checked onchange="toggleServiceInputs(this, 'normal_company_dental')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="normal_company_dental">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_coverage_dental" value="60" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_threshold_dental" value="30000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_copay_dental" value="40" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_deductible_dental" value="1000" min="0">
                    </div>
                </div>
            </div>

            <!-- Optical -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-glasses text-success mr-2"></i>Optical</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="normal_company_enabled_optical" value="1" checked onchange="toggleServiceInputs(this, 'normal_company_optical')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="normal_company_optical">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_coverage_optical" value="50" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_threshold_optical" value="20000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_copay_optical" value="50" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="normal_company_deductible_optical" value="500" min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================
             VIP - INDIVIDUAL
        ========================================= -->
        <div class="policy-section vip">
            <div class="section-header vip">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-crown mr-2"></i>
                        VIP - Individual
                    </h4>
                    <small>Premium coverage with enhanced benefits</small>
                </div>
                <span class="badge badge-dark">Policy 3/4</span>
            </div>

            <!-- Checkup -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-stethoscope text-info mr-2"></i>Checkup / Consultation</span>
                    <span class="badge-required">REQUIRED</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_coverage_checkup" value="100" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_threshold_checkup" value="10000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_copay_checkup" value="0" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_deductible_checkup" value="0" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Operations (VIP has better defaults) -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-procedures text-danger mr-2"></i>Operations / Surgery</span>
                    <span class="badge-required">REQUIRED</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_coverage_operations" value="90" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_threshold_operations" value="2000000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_copay_operations" value="10" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_deductible_operations" value="3000" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Maternity (VIP has better defaults) -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-baby text-warning mr-2"></i>Maternity</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="vip_individual_enabled_maternity" value="1" checked onchange="toggleServiceInputs(this, 'vip_individual_maternity')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="vip_individual_maternity">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_coverage_maternity" value="80" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_threshold_maternity" value="100000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_copay_maternity" value="20" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_deductible_maternity" value="1000" min="0">
                    </div>
                </div>
            </div>

            <!-- Dental (VIP has better defaults) -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-tooth text-primary mr-2"></i>Dental</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="vip_individual_enabled_dental" value="1" checked onchange="toggleServiceInputs(this, 'vip_individual_dental')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="vip_individual_dental">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_coverage_dental" value="70" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_threshold_dental" value="50000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_copay_dental" value="30" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_deductible_dental" value="500" min="0">
                    </div>
                </div>
            </div>

            <!-- Optical (VIP has better defaults) -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-glasses text-success mr-2"></i>Optical</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="vip_individual_enabled_optical" value="1" checked onchange="toggleServiceInputs(this, 'vip_individual_optical')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="vip_individual_optical">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_coverage_optical" value="60" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_threshold_optical" value="30000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_copay_optical" value="40" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_individual_deductible_optical" value="0" min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================
             VIP - COMPANY
        ========================================= -->
        <div class="policy-section vip">
            <div class="section-header vip">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-crown mr-2"></i>
                        VIP - Company
                    </h4>
                    <small>Elite corporate health benefits package</small>
                </div>
                <span class="badge badge-dark">Policy 4/4</span>
            </div>

            <!-- Checkup -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-stethoscope text-info mr-2"></i>Checkup / Consultation</span>
                    <span class="badge-required">REQUIRED</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_coverage_checkup" value="100" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_threshold_checkup" value="10000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_copay_checkup" value="0" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_deductible_checkup" value="0" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Operations -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-procedures text-danger mr-2"></i>Operations / Surgery</span>
                    <span class="badge-required">REQUIRED</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_coverage_operations" value="90" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_threshold_operations" value="2000000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_copay_operations" value="10" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_deductible_operations" value="3000" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Maternity -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-baby text-warning mr-2"></i>Maternity</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="vip_company_enabled_maternity" value="1" checked onchange="toggleServiceInputs(this, 'vip_company_maternity')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="vip_company_maternity">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_coverage_maternity" value="80" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_threshold_maternity" value="100000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_copay_maternity" value="20" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_deductible_maternity" value="1000" min="0">
                    </div>
                </div>
            </div>

            <!-- Dental -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-tooth text-primary mr-2"></i>Dental</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="vip_company_enabled_dental" value="1" checked onchange="toggleServiceInputs(this, 'vip_company_dental')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="vip_company_dental">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_coverage_dental" value="70" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_threshold_dental" value="50000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_copay_dental" value="30" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_deductible_dental" value="500" min="0">
                    </div>
                </div>
            </div>

            <!-- Optical -->
            <div class="service-row">
                <div class="service-header">
                    <span><i class="fas fa-glasses text-success mr-2"></i>Optical</span>
                    <label class="toggle-switch mb-0">
                        <input type="checkbox" name="vip_company_enabled_optical" value="1" checked onchange="toggleServiceInputs(this, 'vip_company_optical')">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-grid" id="vip_company_optical">
                    <div class="form-group">
                        <label>Coverage (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_coverage_optical" value="60" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Threshold (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_threshold_optical" value="30000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Co-Payment (%)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_copay_optical" value="40" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Deductible (EGP)</label>
                        <input type="number" class="form-control form-control-sm" name="vip_company_deductible_optical" value="0" min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Submit Bar -->
        <div class="sticky-submit">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-1 text-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        Ready to Save All 4 Policies
                    </h5>
                    <small class="text-muted">All policy types will be created with the values specified above</small>
                </div>
                <div class="col-md-4 text-right">
                    <a href="<?= BASE_URL ?>/insurance/dashboard" class="btn btn-secondary mr-2">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save mr-2"></i>
                        Save All Policies
                    </button>
                </div>
            </div>
        </div>

    </form>

</div>

<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>

<script>
function toggleServiceInputs(checkbox, containerId) {
    const container = document.getElementById(containerId);
    const inputs = container.querySelectorAll('input');
    
    inputs.forEach(input => {
        if (checkbox.checked) {
            input.removeAttribute('disabled');
        } else {
            input.setAttribute('disabled', 'disabled');
        }
    });
}
</script>

</body>
</html>
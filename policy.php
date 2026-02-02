<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Insurance Policy Setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/policy.css" rel="stylesheet">

    <style>
        .d-none { display: none; }
        fieldset { margin-bottom: 20px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        .row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .actions { text-align: right; margin-top: 20px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Medical Insurance Policy Configuration</h2>

    <form method="POST" action="save_policy.php">

        <!-- POLICY TYPE -->
        <fieldset>
            <legend>Policy Type</legend>
            <div class="row">
                <div>
                    <label>Category</label>
                    <select id="policyCategory" name="policyCategory" onchange="togglePolicy()">
                        <option value="">-- Select --</option>
                        <option value="normal">Normal</option>
                        <option value="vip">VIP</option>
                    </select>
                </div>
                <div>
                    <label>Benefit Type</label>
                    <select id="benefitType" name="benefitType" onchange="togglePolicy()">
                        <option value="">-- Select --</option>
                        <option value="individual">Individual</option>
                        <option value="company">Company</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- NORMAL - INDIVIDUAL -->
        <fieldset id="normal-individual" class="d-none">
            <legend>Normal Policy – Individual</legend>
            <div class="row">
                <div>
                    <label>Annual Limit (EGP)</label>
                    <input type="number" name="annual_limit">
                </div>
                <div>
                    <label>Max Visits / Year</label>
                    <input type="number" name="max_visits">
                </div>
            </div>
        </fieldset>

        <!-- NORMAL - COMPANY -->
        <fieldset id="normal-company" class="d-none">
            <legend>Normal Policy – Company</legend>
            <div class="row">
                <div>
                    <label>Company Size</label>
                    <input type="number" name="company_size">
                </div>
                <div>
                    <label>Annual Limit / Employee</label>
                    <input type="number" name="annual_limit_per_employee">
                </div>
            </div>
        </fieldset>

        <!-- VIP - INDIVIDUAL -->
        <fieldset id="vip-individual" class="d-none">
            <legend>VIP Policy – Individual</legend>
            <div class="row">
                <div>
                    <label>Annual Limit (EGP)</label>
                    <input type="number" name="vip_annual_limit">
                </div>
                <div>
                    <label>Private Hospitals Access</label>
                    <select name="private_hospitals_access">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- VIP - COMPANY -->
        <fieldset id="vip-company" class="d-none">
            <legend>VIP Policy – Company</legend>
            <div class="row">
                <div>
                    <label>Company Size</label>
                    <input type="number" name="vip_company_size">
                </div>
                <div>
                    <label>Annual Limit / Employee</label>
                    <input type="number" name="vip_annual_limit_per_employee">
                </div>
                <div>
                    <label>VIP Network Access</label>
                    <select name="vip_network_access">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- MEDICAL COVERAGE -->
        <fieldset>
            <legend>Medical Coverage</legend>
            <div class="checkbox-grid">
                <label><input type="checkbox" name="coverage_services[]" value="checkup" checked disabled> Checkup</label>
                <label><input type="checkbox" name="coverage_services[]" value="operations" checked disabled> Operations</label>
                <label><input type="checkbox" name="coverage_services[]" value="maternity" onchange="toggleService('maternity')"> Maternity</label>
                <label><input type="checkbox" name="coverage_services[]" value="dental" onchange="toggleService('dental')"> Dental</label>
                <label><input type="checkbox" name="coverage_services[]" value="optical" onchange="toggleService('optical')"> Optical</label>
            </div>
        </fieldset>

        <!-- SERVICE RULES -->
        <fieldset>
            <legend>Service-Based Financial Rules</legend>

            <!-- CHECKUP -->
            <div>
                <h4>Checkup / Consultation</h4>
                <div class="row">
                    <div><label>Coverage (%)</label><input type="number" name="coverage_checkup" value="100"></div>
                    <div><label>Threshold (EGP)</label><input type="number" name="threshold_checkup" value="10000"></div>
                    <div><label>Co-Payment (%)</label><input type="number" name="copay_checkup" value="0"></div>
                    <div><label>Deductible (EGP)</label><input type="number" name="deductible_checkup" value="0"></div>
                </div>
            </div>

            <!-- OPERATIONS -->
            <div>
                <h4>Operations / Surgery</h4>
                <div class="row">
                    <div><label>Coverage (%)</label><input type="number" name="coverage_operations" value="80"></div>
                    <div><label>Threshold (EGP)</label><input type="number" name="threshold_operations" value="1000000"></div>
                    <div><label>Co-Payment (%)</label><input type="number" name="copay_operations" value="20"></div>
                    <div><label>Deductible (EGP)</label><input type="number" name="deductible_operations" value="5000"></div>
                </div>
            </div>

            <!-- OPTIONAL SERVICES -->
            <div id="maternity" class="d-none">
                <h4>Maternity</h4>
                <div class="row">
                    <div><label>Coverage (%)</label><input type="number" name="coverage_maternity" value="70"></div>
                    <div><label>Threshold (EGP)</label><input type="number" name="threshold_maternity" value="50000"></div>
                    <div><label>Co-Payment (%)</label><input type="number" name="copay_maternity" value="30"></div>
                    <div><label>Deductible (EGP)</label><input type="number" name="deductible_maternity" value="2000"></div>
                </div>
            </div>

            <div id="dental" class="d-none">
                <h4>Dental</h4>
                <div class="row">
                    <div><label>Coverage (%)</label><input type="number" name="coverage_dental" value="60"></div>
                    <div><label>Threshold (EGP)</label><input type="number" name="threshold_dental" value="30000"></div>
                    <div><label>Co-Payment (%)</label><input type="number" name="copay_dental" value="40"></div>
                    <div><label>Deductible (EGP)</label><input type="number" name="deductible_dental" value="1000"></div>
                </div>
            </div>

            <div id="optical" class="d-none">
                <h4>Optical</h4>
                <div class="row">
                    <div><label>Coverage (%)</label><input type="number" name="coverage_optical" value="50"></div>
                    <div><label>Threshold (EGP)</label><input type="number" name="threshold_optical" value="20000"></div>
                    <div><label>Co-Payment (%)</label><input type="number" name="copay_optical" value="50"></div>
                    <div><label>Deductible (EGP)</label><input type="number" name="deductible_optical" value="500"></div>
                </div>
            </div>

        </fieldset>

        <!-- ACTION -->
        <div class="actions">
            <button type="submit">Save Policy</button>
        </div>

    </form>
</div>

<script>
function togglePolicy() {
    const category = document.getElementById("policyCategory").value;
    const benefit = document.getElementById("benefitType").value;
    document.querySelectorAll("fieldset[id]").forEach(f => f.classList.add("d-none"));
    if(category && benefit) {
        const el = document.getElementById(`${category}-${benefit}`);
        if(el) el.classList.remove("d-none");
    }
}

function toggleService(id) {
    document.getElementById(id).classList.toggle("d-none");
}
</script>

</body>
</html>

<?php
session_start();
if (!isset($_SESSION["auth_type"]) || $_SESSION["auth_type"] !== "staff" || ($_SESSION["role"] ?? "") !== "INSURANCE_STAFF") {
  header("Location: login.html");
  exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SmartConnect - Insurance Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-success sidebar sidebar-dark accordion" id="accordionSidebar">
            <!-- Sidebar Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="insurance_dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Insurance <sup>Dashboard</sup></div>
            </a>

            <hr class="sidebar-divider my-0">
            <li class="nav-item active">
                <a class="nav-link" href="insurance_dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>
            <hr class="sidebar-divider">

            <div class="sidebar-heading">Modules</div>

            <li class="nav-item">
                <a class="nav-link" href="#patientEligibility">
                    <i class="fas fa-user-check"></i>
                    <span>Patient Eligibility</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#claimManagement">
                    <i class="fas fa-file-medical"></i>
                    <span>Claim Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#claimDecision">
                    <i class="fas fa-gavel"></i>
                    <span>Claim Decisions</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#financialManagement">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Financial Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#insuranceProfile">
                    <i class="fas fa-building"></i>
                    <span>Insurance Profile</span>
                </a>
            </li>

            <hr class="sidebar-divider d-none d-md-block">
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        <!-- End of Sidebar -->

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Insurance Admin</span>
                                <img class="img-profile rounded-circle" src="img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#"><i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Profile</a>
                                <a class="dropdown-item" href="#"><i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>Settings</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="login.html"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout</a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Insurance Dashboard</h1>

                    <!-- Row: Patient Eligibility & Claim Management -->
                    <div class="row">
                        <!-- Patient Eligibility -->
                        <div class="col-xl-6 col-md-6 mb-4" id="patientEligibility">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-header font-weight-bold text-success">Patient Eligibility Verification</div>
                                <div class="card-body">
                                    <form>
                                        <div class="form-group">
                                            <label for="nationalID">Patient National ID</label>
                                            <input type="text" class="form-control" id="nationalID" placeholder="Enter ID">
                                        </div>
                                        <button type="button" class="btn btn-success btn-block">Check Eligibility</button>
                                    </form>
                                    <div class="mt-3">
                                        <strong>Status:</strong> <span id="eligibilityStatus">-</span><br>
                                        <strong>Coverage Limit:</strong> <span id="coverageLimit">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Claim Management -->
                        <div class="col-xl-6 col-md-6 mb-4" id="claimManagement">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-header font-weight-bold text-primary">Claim Management</div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Claim ID</th>
                                                <th>Patient Name</th>
                                                <th>Hospital</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>CL001</td>
                                                <td>John Doe</td>
                                                <td>City Hospital</td>
                                                <td>$500</td>
                                                <td>Under Review</td>
                                            </tr>
                                            <tr>
                                                <td>CL002</td>
                                                <td>Jane Smith</td>
                                                <td>Metro Clinic</td>
                                                <td>$1200</td>
                                                <td>Approved</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row: Claim Decisions & Financial Management -->
                    <div class="row">
                        <!-- Claim Decisions -->
                        <div class="col-xl-6 col-md-6 mb-4" id="claimDecision">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-header font-weight-bold text-warning">Claim Decision Processing</div>
                                <div class="card-body">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Claim ID</th>
                                                <th>Status</th>
                                                <th>Reason / Notes</th>
                                                <th>Updated At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>CL001</td>
                                                <td>Under Review</td>
                                                <td>-</td>
                                                <td>2026-01-14 09:00</td>
                                            </tr>
                                            <tr>
                                                <td>CL003</td>
                                                <td>Rejected</td>
                                                <td>More documents needed</td>
                                                <td>2026-01-12 15:30</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Management -->
                        <div class="col-xl-6 col-md-6 mb-4" id="financialManagement">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-header font-weight-bold text-info">Financial Management</div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Payout ID</th>
                                                <th>Claim ID</th>
                                                <th>Amount Paid</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>PY001</td>
                                                <td>CL002</td>
                                                <td>$1200</td>
                                                <td>Paid</td>
                                            </tr>
                                            <tr>
                                                <td>PY002</td>
                                                <td>CL004</td>
                                                <td>$600</td>
                                                <td>Pending</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row: Insurance Profile -->
                    <div class="row">
                        <div class="col-xl-12 col-md-12 mb-4" id="insuranceProfile">
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-header font-weight-bold text-secondary">Insurance Platform Profile</div>
                                <div class="card-body">
                                    <p><strong>Insurance Plans:</strong> Basic Health, Premium Care, Family Plan</p>
                                    <p><strong>Contracted Hospitals:</strong> City Hospital, Metro Clinic, Sunshine Medical</p>
                                    <p><strong>Premium Rules:</strong> Monthly, Annual, Co-payment rules</p>
                                    <p><strong>Policy Rules:</strong> Coverage exclusions, co-pays, limits</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; SmartConnect 2026</span>
                    </div>
                </div>
            </footer>
        </div>
        <!-- End Content Wrapper -->
    </div>
    <!-- End Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="Js/jquery.min.js"></script>
    <script src="Js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="Js/jquery.easing.min.js"></script>

    <!-- Custom scripts -->
    <script src="Js/sb-admin-2.min.js"></script>
</body>

</html>

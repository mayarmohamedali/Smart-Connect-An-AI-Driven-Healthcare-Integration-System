<?php
// Session already started in controller
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Smart-Connect | Admin Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --primary:#0f4c81;
      --primary-dark:#0b3558;
      --primary-soft:#eaf3fb;
      --secondary:#1f9d74;
      --secondary-dark:#157a59;
      --secondary-soft:#e8f7f1;
      --page-bg:#f5f8fc;
      --card-bg:#ffffff;
      --text-dark:#1f2937;
      --text-muted:#6b7280;
      --border-soft:#e5e7eb;
      --warning:#f59e0b;
      --danger:#ef4444;
      --success:#22c55e;
      --shadow:0 10px 24px rgba(15, 76, 129, 0.08);
      --radius:20px;
    }

    *{ box-sizing:border-box; }

    body{
      margin:0;
      font-family:'Inter',sans-serif;
      background:var(--page-bg);
      color:var(--text-dark);
      transition:background 0.25s ease, color 0.25s ease;
    }

    .dashboard-shell{
      min-height:100vh;
      display:flex;
    }

    .sidebar{
      width:270px;
      background:linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 100%);
      color:#fff;
      padding:24px 18px;
      position:sticky;
      top:0;
      height:100vh;
      box-shadow:8px 0 24px rgba(11,53,88,0.16);
      display:flex;
      flex-direction:column;
    }

    .brand{
      display:flex;
      align-items:center;
      gap:14px;
      margin-bottom:22px;
      padding:0 4px;
    }

    .logo-img{
      width:56px;
      height:56px;
      border-radius:16px;
      object-fit:cover;
      background:#fff;
      box-shadow:0 8px 18px rgba(0,0,0,0.16);
      flex-shrink:0;
    }

    .brand-text h2{
      margin:0;
      font-size:17px;
      font-weight:800;
      letter-spacing:0.2px;
      color:#ffffff;
    }

    .brand-text p{
      margin:3px 0 0;
      color:rgba(255,255,255,0.76);
      font-size:12px;
    }

    .sidebar-divider{
      height:1px;
      background:rgba(255,255,255,0.12);
      margin:18px 4px 18px;
    }

    .menu-label{
      color:rgba(255,255,255,0.58);
      font-size:12px;
      text-transform:uppercase;
      letter-spacing:1px;
      margin:0 8px 10px;
      font-weight:700;
    }

    .sidebar-menu{
      display:flex;
      flex-direction:column;
      gap:8px;
    }

    .sidebar-btn{
      width:100%;
      border:none;
      background:transparent;
      color:rgba(255,255,255,0.9);
      text-align:left;
      padding:14px 16px;
      border-radius:16px;
      font-size:15px;
      font-weight:700;
      transition:0.25s ease;
      display:flex;
      align-items:center;
      gap:12px;
      cursor:pointer;
    }

    .sidebar-btn:hover{
      background:rgba(255,255,255,0.10);
      transform:translateX(2px);
    }

    .sidebar-btn.active{
      background:rgba(255,255,255,0.14);
      color:#ffffff;
      border:1px solid rgba(255,255,255,0.16);
    }

    .menu-icon{
      width:18px;
      text-align:center;
      opacity:0.95;
    }

    .sidebar-footer{
      margin-top:auto;
      padding:18px 8px 0;
      color:rgba(255,255,255,0.7);
      font-size:13px;
      line-height:1.45;
    }

    .main{
      flex:1;
      padding:28px;
    }

    .topbar{
      background:#ffffff;
      border:1px solid var(--border-soft);
      border-radius:24px;
      padding:24px 26px;
      box-shadow:var(--shadow);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:20px;
      margin-bottom:24px;
      transition:background 0.25s ease, border-color 0.25s ease;
    }

    .topbar h1{
      margin:0;
      font-size:24px;
      font-weight:800;
      color:var(--primary-dark);
    }

    .topbar p{
      margin:6px 0 0;
      color:var(--text-muted);
      font-size:14px;
    }

    .btn-main{
      border:none;
      background:linear-gradient(135deg, var(--secondary), var(--secondary-dark));
      color:#fff;
      font-weight:800;
      border-radius:999px;
      padding:12px 22px;
      box-shadow:0 10px 20px rgba(31,157,116,0.18);
      transition:0.25s ease;
    }

    .btn-main:hover{
      transform:translateY(-2px);
      filter:brightness(1.02);
    }

    .section{
      display:none;
      animation:fadeIn 0.25s ease;
    }

    .section.active{
      display:block;
    }

    @keyframes fadeIn{
      from{opacity:0; transform:translateY(6px);}
      to{opacity:1; transform:translateY(0);}
    }

    .cards-row{
      margin-bottom:24px;
    }

    .stat-card{
      background:#ffffff;
      border-radius:22px;
      padding:22px 20px;
      box-shadow:var(--shadow);
      border:1px solid var(--border-soft);
      height:100%;
      position:relative;
      overflow:hidden;
      transition:background 0.25s ease, border-color 0.25s ease;
    }

    .stat-card::before{
      content:"";
      position:absolute;
      top:0;
      left:0;
      width:100%;
      height:5px;
      background:linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .stat-title{
      color:var(--text-muted);
      font-size:14px;
      font-weight:700;
      margin-bottom:10px;
    }

    .stat-number{
      font-size:40px;
      font-weight:800;
      color:var(--primary-dark);
      line-height:1;
    }

    .panel{
      background:#ffffff;
      border-radius:22px;
      padding:22px;
      box-shadow:var(--shadow);
      border:1px solid var(--border-soft);
      margin-bottom:20px;
      transition:background 0.25s ease, border-color 0.25s ease;
    }

    .panel-title{
      margin:0 0 8px;
      font-size:18px;
      font-weight:800;
      color:var(--primary-dark);
    }

    .panel-subtitle{
      margin:0 0 18px;
      font-size:14px;
      color:var(--text-muted);
    }

    .table-smart{
      margin:0;
      vertical-align:middle;
    }

    .table-smart thead th{
      color:var(--primary-dark);
      font-size:14px;
      font-weight:800;
      border-bottom:1px solid var(--border-soft);
      padding:14px 12px;
    }

    .table-smart tbody td{
      padding:16px 12px;
      color:#374151;
      border-color:var(--border-soft);
      font-weight:500;
    }

    .users-grid{
      display:grid;
      grid-template-columns:repeat(2, 1fr);
      gap:16px;
    }

    .user-card{
      background:var(--primary-soft);
      border:1px solid #d8e6f2;
      border-radius:18px;
      padding:18px;
      transition:background 0.25s ease, border-color 0.25s ease;
    }

    .user-card h4{
      margin:0 0 6px;
      font-size:17px;
      font-weight:800;
      color:var(--primary-dark);
    }

    .user-card p{
      margin:0 0 12px;
      color:var(--text-muted);
      font-size:14px;
    }

    .mini-badge{
      display:inline-block;
      border-radius:999px;
      padding:6px 10px;
      font-size:12px;
      font-weight:800;
      background:var(--secondary-soft);
      color:var(--secondary-dark);
    }

    .report-grid{
      display:grid;
      grid-template-columns:repeat(2, 1fr);
      gap:16px;
    }

    .report-card{
      background:var(--primary-soft);
      border:1px solid #d8e6f2;
      border-radius:18px;
      padding:18px;
      transition:background 0.25s ease, border-color 0.25s ease;
    }

    .report-card h4{
      margin:0 0 8px;
      font-size:16px;
      font-weight:800;
      color:var(--primary-dark);
    }

    .report-card p{
      margin:0 0 14px;
      font-size:14px;
      color:var(--text-muted);
    }

    .btn-outline-smart{
      border:1px solid #c8d7e6;
      background:#fff;
      color:var(--primary-dark);
      font-weight:700;
      border-radius:999px;
      padding:10px 14px;
    }

    .insights-list{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:14px;
      margin-top:18px;
    }

    .insight-item{
      background:#f8fbff;
      border:1px solid #d8e6f2;
      border-radius:16px;
      padding:16px;
    }

    .insight-item strong{
      display:block;
      font-size:15px;
      color:var(--primary-dark);
      margin-bottom:6px;
    }

    .insight-item span{
      color:var(--text-muted);
      font-size:14px;
    }

    .settings-item{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:20px;
      padding:16px 0;
      border-bottom:1px solid var(--border-soft);
    }

    .settings-item:last-child{
      border-bottom:none;
    }

    .settings-item strong{
      color:var(--primary-dark);
      font-size:15px;
      display:block;
      margin-bottom:4px;
    }

    .settings-item .desc{
      color:var(--text-muted);
      font-size:14px;
    }

    .toggle{
      width:54px;
      height:30px;
      background:#d8e3eb;
      border-radius:999px;
      position:relative;
      cursor:pointer;
      transition:0.25s;
      flex-shrink:0;
    }

    .toggle::after{
      content:"";
      position:absolute;
      width:22px;
      height:22px;
      background:#fff;
      border-radius:50%;
      top:4px;
      left:4px;
      box-shadow:0 3px 10px rgba(0,0,0,0.12);
      transition:0.25s;
    }

    .toggle.active{
      background:linear-gradient(135deg, var(--secondary), var(--secondary-dark));
    }

    .toggle.active::after{
      left:28px;
    }

    body.dark-mode{
      --page-bg:#0b1220;
      --card-bg:#111827;
      --text-dark:#f3f4f6;
      --text-muted:#9ca3af;
      --border-soft:#243041;
      --shadow:0 10px 24px rgba(0, 0, 0, 0.35);
    }

    body.dark-mode{
      background:linear-gradient(180deg, #0b1220 0%, #111827 100%);
      color:var(--text-dark);
    }

    body.dark-mode .topbar,
    body.dark-mode .panel,
    body.dark-mode .stat-card{
      background:#111827;
      border-color:#243041;
      box-shadow:var(--shadow);
    }

    body.dark-mode .report-card,
    body.dark-mode .user-card,
    body.dark-mode .insight-item{
      background:#0f172a;
      border-color:#243041;
    }

    body.dark-mode .topbar h1,
    body.dark-mode .panel-title,
    body.dark-mode .stat-number,
    body.dark-mode .user-card h4,
    body.dark-mode .report-card h4,
    body.dark-mode .settings-item strong,
    body.dark-mode .table-smart thead th,
    body.dark-mode .insight-item strong{
      color:#f9fafb;
    }

    body.dark-mode .topbar p,
    body.dark-mode .panel-subtitle,
    body.dark-mode .user-card p,
    body.dark-mode .report-card p,
    body.dark-mode .settings-item .desc,
    body.dark-mode .stat-title,
    body.dark-mode .table-smart tbody td,
    body.dark-mode .insight-item span{
      color:#9ca3af;
    }

    body.dark-mode .table-smart tbody td,
    body.dark-mode .table-smart thead th,
    body.dark-mode .settings-item{
      border-color:#243041;
    }

    body.dark-mode .btn-outline-smart{
      background:#111827;
      color:#f9fafb;
      border-color:#334155;
    }

    @media (max-width: 992px){
      .sidebar{
        width:110px;
        padding:20px 10px;
      }

      .brand-text,
      .sidebar-footer,
      .menu-label,
      .sidebar-btn span:last-child{
        display:none;
      }

      .sidebar-btn{
        justify-content:center;
        padding:14px 10px;
      }

      .brand{
        justify-content:center;
      }

      .logo-img{
        width:52px;
        height:52px;
      }

      .users-grid,
      .report-grid,
      .insights-list{
        grid-template-columns:1fr;
      }
    }

    @media (max-width: 768px){
      .main{
        padding:16px;
      }

      .topbar{
        flex-direction:column;
        align-items:flex-start;
      }

      .report-grid,
      .insights-list{
        grid-template-columns:1fr;
      }
    }
  </style>
</head>
<body>

  <div class="dashboard-shell">
    <aside class="sidebar">
      <div class="brand">
        <img src="logo-landing.jpg" alt="Smart-Connect Logo" class="logo-img">
        <div class="brand-text">
          <h2>Smart-Connect</h2>
          <p>Admin Control Panel</p>
        </div>
      </div>

      <div class="sidebar-divider"></div>
      <div class="menu-label">Navigation</div>

      <div class="sidebar-menu">
        <button class="sidebar-btn active" data-section="dashboard">
          <span class="menu-icon">🏠</span>
          <span>Dashboard</span>
        </button>

        <button class="sidebar-btn" data-section="users">
          <span class="menu-icon">👥</span>
          <span>Users</span>
        </button>

        <button class="sidebar-btn" data-section="claims">
          <span class="menu-icon">📄</span>
          <span>Records</span>
        </button>

        <button class="sidebar-btn" data-section="reports">
          <span class="menu-icon">📊</span>
          <span>Reports</span>
        </button>

        <button class="sidebar-btn" data-section="settings">
          <span class="menu-icon">⚙️</span>
          <span>Settings</span>
        </button>
      </div>

      <div class="sidebar-footer">
        Welcome, <?php echo htmlspecialchars($_SESSION["staff_name"] ?? "Admin"); ?>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <div>
          <h1 id="pageTitle">Admin Dashboard</h1>
          <p id="pageSubtitle">Monitor the platform and manage Smart-Connect operations.</p>
        </div>
        <button class="btn-main" onclick="generateReport()">Generate Report</button>
      </div>

      <section id="dashboard" class="section active">
        <div class="row g-4 cards-row">
          <div class="col-md-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-title">Total Patients</div>
              <div class="stat-number"><?php echo $totalPatients; ?></div>
            </div>
          </div>

          <div class="col-md-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-title">Hospitals</div>
              <div class="stat-number"><?php echo $totalHospitals; ?></div>
            </div>
          </div>

          <div class="col-md-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-title">Insurance Companies</div>
              <div class="stat-number"><?php echo $totalInsurances; ?></div>
            </div>
          </div>

          <div class="col-md-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-title">Users</div>
              <div class="stat-number"><?php echo $totalUsers; ?></div>
            </div>
          </div>
        </div>

        <div class="panel">
          <h3 class="panel-title">Recent Patients</h3>
          <p class="panel-subtitle">Latest patients registered in the Smart-Connect system.</p>

          <div class="table-responsive">
            <table class="table table-smart">
              <thead>
                <tr>
                  <th>Patient ID</th>
                  <th>Full Name</th>
                  <th>National ID</th>
                  <th>Phone</th>
                  <th>Gender</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($recentPatients)): ?>
                  <?php foreach ($recentPatients as $patient): ?>
                    <tr>
                      <td>#<?php echo (int)$patient["patient_id"]; ?></td>
                      <td><?php echo htmlspecialchars($patient["full_name"]); ?></td>
                      <td><?php echo htmlspecialchars($patient["national_id"]); ?></td>
                      <td><?php echo htmlspecialchars($patient["phone"]); ?></td>
                      <td><?php echo htmlspecialchars($patient["gender"] ?: "N/A"); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5">No patients found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section id="users" class="section">
        <div class="panel">
          <h3 class="panel-title">User Management</h3>
          <p class="panel-subtitle">Recent users from hospitals, insurance, and admin accounts.</p>

          <div class="users-grid">
            <?php if (!empty($recentUsers)): ?>
              <?php foreach ($recentUsers as $user): ?>
                <div class="user-card">
                  <h4><?php echo htmlspecialchars($user["full_name"]); ?></h4>
                  <p><?php echo htmlspecialchars($user["role_name"]); ?><br><?php echo htmlspecialchars($user["email"]); ?></p>
                  <span class="mini-badge"><?php echo ((int)$user["is_active"] === 1) ? "Active" : "Inactive"; ?></span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="user-card">
                <h4>No users found</h4>
                <p>The users table is empty.</p>
                <span class="mini-badge">N/A</span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section id="claims" class="section">
        <div class="panel">
          <h3 class="panel-title">Medical Records Monitoring</h3>
          <p class="panel-subtitle">Track the latest medical records saved in the system.</p>

          <div class="table-responsive">
            <table class="table table-smart">
              <thead>
                <tr>
                  <th>Record ID</th>
                  <th>Patient ID</th>
                  <th>Diagnosis</th>
                  <th>Check-in</th>
                  <th>Check-out</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($recentRecords)): ?>
                  <?php foreach ($recentRecords as $record): ?>
                    <tr>
                      <td>#<?php echo (int)$record["record_id"]; ?></td>
                      <td>#<?php echo (int)$record["patient_id"]; ?></td>
                      <td><?php echo htmlspecialchars($record["diagnosis"] ?: "N/A"); ?></td>
                      <td><?php echo htmlspecialchars($record["checkin_date"] ?: "N/A"); ?></td>
                      <td><?php echo htmlspecialchars($record["checkout_date"] ?: "N/A"); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5">No records found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section id="reports" class="section">
        <div class="panel">
          <h3 class="panel-title">Reports</h3>
          <p class="panel-subtitle">Useful summaries and quick admin insights from the database.</p>

          <div class="report-grid">
            <div class="report-card">
              <h4>Patients Summary</h4>
              <p>Total registered patients in the platform database.</p>
              <button class="btn-outline-smart"><?php echo $totalPatients; ?> Patients</button>
            </div>

            <div class="report-card">
              <h4>Hospitals Summary</h4>
              <p>Total hospitals currently connected to the system.</p>
              <button class="btn-outline-smart"><?php echo $totalHospitals; ?> Hospitals</button>
            </div>

            <div class="report-card">
              <h4>Insurance Summary</h4>
              <p>Total insurance providers stored in the platform.</p>
              <button class="btn-outline-smart"><?php echo $totalInsurances; ?> Providers</button>
            </div>

            <div class="report-card">
              <h4>User Activity</h4>
              <p>Currently active accounts across admin, hospital, and insurance users.</p>
              <button class="btn-outline-smart"><?php echo $activeUsers; ?> Active Users</button>
            </div>
          </div>

          <div class="insights-list">
            <div class="insight-item">
              <strong>Latest Patient Added</strong>
              <span><?php echo htmlspecialchars($latestPatientName); ?></span>
            </div>

            <div class="insight-item">
              <strong>Active User Rate</strong>
              <span><?php echo $userActivityRate; ?>% of system users are active</span>
            </div>

            <div class="insight-item">
              <strong>Completed Insurance Policies</strong>
              <span><?php echo $completedPolicies; ?> provider(s) completed policy setup</span>
            </div>

            <div class="insight-item">
              <strong>Pending Insurance Policies</strong>
              <span><?php echo $pendingPolicies; ?> provider(s) still need policy completion</span>
            </div>
          </div>
        </div>
      </section>

      <section id="settings" class="section">
        <div class="panel">
          <h3 class="panel-title">System Settings</h3>
          <p class="panel-subtitle">Control platform preferences, alerts, appearance, and admin behavior.</p>

          <div class="settings-item">
            <div>
              <strong>Email Notifications</strong>
              <div class="desc">Send email updates for important platform activity and admin actions.</div>
            </div>
            <div class="toggle active"></div>
          </div>

          <div class="settings-item">
            <div>
              <strong>System Alerts</strong>
              <div class="desc">Show alerts for login issues, failed actions, and operational warnings.</div>
            </div>
            <div class="toggle active"></div>
          </div>

          <div class="settings-item">
            <div>
              <strong>Silent Mode</strong>
              <div class="desc">Mute non-critical notifications and reduce unnecessary interruptions.</div>
            </div>
            <div class="toggle"></div>
          </div>

          <div class="settings-item">
            <div>
              <strong>Dark Mode</strong>
              <div class="desc">Enable a darker interface theme for lower brightness and night use.</div>
            </div>
            <div class="toggle" id="darkModeToggle"></div>
          </div>

          <div class="settings-item">
            <div>
              <strong>Maintenance Mode</strong>
              <div class="desc">Temporarily limit access while administrators update the platform.</div>
            </div>
            <div class="toggle"></div>
          </div>

          <div class="settings-item">
            <div>
              <strong>Auto Logout</strong>
              <div class="desc">Automatically sign out inactive users after a period of inactivity.</div>
            </div>
            <div class="toggle active"></div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    const buttons = document.querySelectorAll('.sidebar-btn');
    const sections = document.querySelectorAll('.section');
    const pageTitle = document.getElementById('pageTitle');
    const pageSubtitle = document.getElementById('pageSubtitle');

    const titles = {
      dashboard: {
        title: 'Admin Dashboard',
        subtitle: 'Monitor the platform and manage Smart-Connect operations.'
      },
      users: {
        title: 'Users',
        subtitle: 'Manage hospitals, insurance providers, and platform accounts.'
      },
      claims: {
        title: 'Records',
        subtitle: 'Track and review medical records stored in the system.'
      },
      reports: {
        title: 'Reports',
        subtitle: 'View platform summaries and useful admin insights.'
      },
      settings: {
        title: 'System Settings',
        subtitle: 'Control notifications, appearance, and platform behavior.'
      }
    };

    buttons.forEach(button => {
      button.addEventListener('click', function () {
        const target = this.getAttribute('data-section');

        buttons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');

        sections.forEach(section => section.classList.remove('active'));
        document.getElementById(target).classList.add('active');

        pageTitle.textContent = titles[target].title;
        pageSubtitle.textContent = titles[target].subtitle;
      });
    });

    const darkModeToggle = document.getElementById('darkModeToggle');

    function applyDarkMode(enabled) {
      if (enabled) {
        document.body.classList.add('dark-mode');
        if (darkModeToggle) darkModeToggle.classList.add('active');
        localStorage.setItem('smartconnect_dark_mode', 'on');
      } else {
        document.body.classList.remove('dark-mode');
        if (darkModeToggle) darkModeToggle.classList.remove('active');
        localStorage.setItem('smartconnect_dark_mode', 'off');
      }
    }

    document.querySelectorAll('.toggle').forEach(toggle => {
      toggle.addEventListener('click', function () {
        if (this.id === 'darkModeToggle') {
          const isDark = document.body.classList.contains('dark-mode');
          applyDarkMode(!isDark);
        } else {
          this.classList.toggle('active');
        }
      });
    });

    if (localStorage.getItem('smartconnect_dark_mode') === 'on') {
      applyDarkMode(true);
    }

    function generateReport() {
      alert(
        "Smart-Connect Live Report\n\n" +
        "Total Patients: <?php echo $totalPatients; ?>\n" +
        "Hospitals: <?php echo $totalHospitals; ?>\n" +
        "Insurance Companies: <?php echo $totalInsurances; ?>\n" +
        "Users: <?php echo $totalUsers; ?>\n" +
        "Active Users: <?php echo $activeUsers; ?>\n" +
        "Completed Insurance Policies: <?php echo $completedPolicies; ?>\n" +
        "Pending Insurance Policies: <?php echo $pendingPolicies; ?>\n\n" +
        "This data is loaded from the database."
      );
    }
  </script>
</body>
</html>
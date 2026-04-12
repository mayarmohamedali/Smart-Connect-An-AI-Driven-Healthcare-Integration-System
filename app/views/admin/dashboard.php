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

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="brand">
      <img src="/Smart-Connect-An-AI-Driven-Healthcare-Integration-System/public/logo-landing.jpg" class="logo-img">
      <div class="brand-text">
        <h2>Smart-Connect</h2>
        <p>Admin Control Panel</p>
      </div>
    </div>

    <div class="sidebar-divider"></div>

    <div class="menu-label">Navigation</div>

    <div class="sidebar-menu">
      <button class="sidebar-btn active">🏠 Dashboard</button>
      <button class="sidebar-btn">👥 Users</button>
      <button class="sidebar-btn">📄 Records</button>
      <button class="sidebar-btn">📊 Reports</button>
      <button class="sidebar-btn">⚙️ Settings</button>
    </div>

    <div class="sidebar-footer">
      Welcome, <?php echo htmlspecialchars($_SESSION["staff_name"] ?? "Admin"); ?>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main">

    <!-- TOPBAR -->
    <div class="topbar">
      <div>
        <h1>Admin Dashboard</h1>
        <p>Monitor the platform and manage Smart-Connect operations.</p>
      </div>
      <button class="btn-main">Generate Report</button>
    </div>

    <!-- STATS -->
    <div class="row g-4 cards-row">
      <div class="col-md-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-title">Total Patients</div>
          <div class="stat-number"><?php echo $counts['totalPatients']; ?></div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-title">Hospitals</div>
          <div class="stat-number"><?php echo $counts['totalHospitals']; ?></div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-title">Insurance Companies</div>
          <div class="stat-number"><?php echo $counts['totalInsurances']; ?></div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-title">Users</div>
          <div class="stat-number"><?php echo $counts['totalUsers']; ?></div>
        </div>
      </div>
    </div>

    <!-- RECENT PATIENTS -->
    <div class="panel">
      <h3 class="panel-title">Recent Patients</h3>
      <p class="panel-subtitle">Latest patients registered in the Smart-Connect system.</p>

      <div class="table-responsive">
        <table class="table table-smart">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>National ID</th>
              <th>Phone</th>
              <th>Gender</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentPatients as $p): ?>
              <tr>
                <td>#<?php echo $p['patient_id']; ?></td>
                <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                <td><?php echo htmlspecialchars($p['national_id']); ?></td>
                <td><?php echo htmlspecialchars($p['phone']); ?></td>
                <td><?php echo htmlspecialchars($p['gender'] ?? 'N/A'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- RECENT USERS -->
    <div class="panel">
      <h3 class="panel-title">Recent Users</h3>

      <?php foreach ($recentUsers as $u): ?>
        <div class="user-card mb-2">
          <h4><?php echo htmlspecialchars($u['full_name']); ?></h4>
          <p><?php echo htmlspecialchars($u['role_name']); ?> - <?php echo htmlspecialchars($u['email']); ?></p>
          <span class="mini-badge">
            <?php echo $u['is_active'] ? "Active" : "Inactive"; ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>

  </main>

</div>

</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart-Connect | Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>

<style>
    

    :root {
      --primary-blue: #0d47a1;
      --secondary-blue: #1565c0;
      --accent-green: #2ecc71;
      --light-bg: #f8f9fa;
      --text-dark: #2c3e50;
    }

    body {
      font-family: 'Inter', sans-serif;
      color: var(--text-dark);
      overflow-x: hidden;
      scroll-behavior: smooth; /* Makes clicking nav links smooth */
    }

    h1, h2, h3, .navbar-brand {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 700;
    }

    /* Modern Navbar */
    .navbar {
      padding: 1.2rem 0;
      transition: all 0.3s ease;
      background: rgba(13, 71, 161, 0.95);
      backdrop-filter: blur(10px);
    }
    .navbar-brand { font-size: 1.5rem; letter-spacing: -1px; }

    /* Hero Section */
    .hero {
      background: linear-gradient(135deg, var(--primary-blue), #002171);
      padding: 160px 0 100px;
      color: white;
      clip-path: ellipse(150% 100% at 50% 0%);
    }

    .btn-main {
      background: var(--accent-green);
      color: white;
      padding: 12px 30px;
      border-radius: 50px;
      font-weight: 600;
      transition: 0.3s;
      border: none;
    }
    .btn-main:hover {
      background: #27ae60;
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(46, 204, 113, 0.3);
    }

    /* Feature/Step Cards */
    .feature-card, .step-card {
      padding: 40px;
      border-radius: 20px;
      background: white;
      border: 1px solid #eee;
      transition: 0.4s;
      height: 100%;
    }
    .feature-card:hover, .step-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.05);
    }
    .feature-icon, .step-icon {
      font-size: 2.5rem;
      color: var(--primary-blue);
      margin-bottom: 20px;
    }

    /* Pricing/Why Cards */
    .pricing-card {
      background: white;
      border-radius: 24px;
      padding: 40px;
      border: 2px solid transparent;
      transition: 0.3s;
    }
    .pricing-card.active {
      border-color: var(--primary-blue);
      box-shadow: 0 15px 30px rgba(13, 71, 161, 0.1);
    }

    /* Animations */
    .reveal {
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s ease-out;
    }
    .reveal.active {
      opacity: 1;
      transform: translateY(0);
    }

    footer {
      background: #0a192f;
      color: #8892b0;
      padding: 60px 0;
    }

    .nav-logo {
  width: 60px;
  height: 60px;
  border-radius: 100%;
  object-fit: cover;
  background-color: white; /* Temp to isolate */
  padding: 2px;
  box-shadow: 0 0 6px rgba(0,0,0,0.15);
  
}

    

        :root {
            --primary-color: #0d6efd;
            --bg-light: #f8f9fa;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Left Side Image */
       .login-image-container {
    background: url('<?= BASE_URL ?>/assets/images/login.png') no-repeat center center;
    background-size: cover;
    height: 100vh;
    position: relative;
}

        .image-overlay {
            position: absolute;
            bottom: 10%;
            left: 5%;
            color: white;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.5);
        }

        /* Right Side Form */
        .form-container {
            height: 100vh;
            overflow-y: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            
        }

        .form-wrapper {
            width: 100%;
            max-width: 450px;
        }

        /* Role Cards Styling */
        .role-card {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .role-card:hover {
            border-color: #ccc;
            background-color: #fdfdfd;
        }

        .role-card.active {
            border-color: var(--primary-color);
            background-color: #f0f7ff;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.08);
        }

        .role-icon {
            font-size: 1.4rem;
            margin-right: 15px;
            color: #6c757d;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f3f5;
            border-radius: 8px;
        }

        .role-card.active .role-icon {
            color: var(--primary-color);
            background: #e7f0ff;
        }

        .role-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 2px;
        }

        .role-desc {
            font-size: 0.8rem;
            color: #777;
            line-height: 1.2;
        }

        .border-danger {
            border: 1px solid #dc3545 !important;
        }


        .submit-btn {
    margin-top: 0rem !important;
    font-size: 0.95rem;
    border-radius: 8px;
}

        /* Container for the logo */
.logo-placeholder {
    width: 125px;           /* Size of the circle */
    height: 125px;
    background: #ffffff;   /* White background looks best for logos */
    border-radius: 50%;    /* Makes it a perfect circle */
    display: flex;
    align-items: center;   /* Vertical centering */
    justify-content: center; /* Horizontal centering */
    margin: 0 auto 15px;   /* Centers the circle itself and adds bottom space */
    overflow: hidden;      /* Clips the image if it's too large */
    border: 2px solid #f0f7ff; /* Soft border to define the shape */
    box-shadow: 0 4px 10px rgba(0,0,0,0.05); /* Subtle shadow for depth */
    padding: 5px;         /* Prevents the logo from touching the edges */
}

/* The actual logo image */
.logo-placeholder img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;    /* Keeps the logo's original proportions */
}

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
  

</style>
<div class="container-fluid px-0">
    <div class="row g-0">

        <div class="col-lg-6 d-none d-lg-block">
            <div class="login-image-container"></div>
        </div>

        <div class="col-lg-6 col-12 form-container">
            <div class="form-wrapper">

                <div class="text-center mb-4">
                    <div class="logo-placeholder">
                        <img src="<?= BASE_URL ?>/assets/images/logo-landing.jpg" alt="Smart-Connect Logo">
                    </div>
                </div>

                <form onsubmit="event.preventDefault(); handleAction();">

                    <label class="form-label fw-bold small text-uppercase text-muted mb-2">Select Your Role</label>
                    <div id="role-selector" class="mb-4">

                        <div class="role-card active" id="card-patient" onclick="selectRole('patient')">
                            <div class="role-icon"><i class="bi bi-person"></i></div>
                            <div class="role-text">
                                <div class="role-title">Patient</div>
                                <div class="role-desc">Access personal health records and appointments</div>
                            </div>
                        </div>

                        <div class="role-card" id="card-hospital" onclick="selectRole('hospital')">
                            <div class="role-icon"><i class="bi bi-hospital"></i></div>
                            <div class="role-text">
                                <div class="role-title">Hospital</div>
                                <div class="role-desc">Manage patient admissions and records</div>
                            </div>
                        </div>

                        <div class="role-card" id="card-insurance" onclick="selectRole('insurance')">
                            <div class="role-icon"><i class="bi bi-shield-check"></i></div>
                            <div class="role-text">
                                <div class="role-title">Insurance</div>
                                <div class="role-desc">Review and process insurance claims</div>
                            </div>
                        </div>

                        <div class="role-card" id="card-admin" onclick="selectRole('admin')">
                            <div class="role-icon"><i class="bi bi-person-gear"></i></div>
                            <div class="role-text">
                                <div class="role-title">Admin</div>
                                <div class="role-desc">Manage platform users, settings, and system activity</div>
                            </div>
                        </div>

                        <input type="hidden" id="role" value="patient">
                    </div>

                    <div id="form-fields">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" id="fullName" class="form-control form-control-lg" placeholder="Ahmed Hassan">
                            <small id="nameError" class="text-danger d-none">Please enter a valid name (letters only)</small>
                        </div>

                        <div id="patient-only">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">National ID</label>
                                <input type="text" id="nationalId" class="form-control form-control-lg" placeholder="2990101XXXXXXXX">
                                <small id="nidError" class="text-danger d-none">National ID must be 14 digits</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Phone Number</label>
                                <input type="text" id="phone" class="form-control form-control-lg" placeholder="010XXXXXXXX">
                                <small id="phoneError" class="text-danger d-none">Enter a valid Egyptian phone number</small>
                            </div>
                        </div>

                        <div id="org-only" class="d-none">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Organization Email</label>
                                <input type="email" id="email" class="form-control form-control-lg" placeholder="name@organization.com">
                                <small id="emailError" class="text-danger d-none">Invalid email format</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Password</label>
                                <input type="password" id="password" class="form-control form-control-lg" placeholder="••••••••">
                                <small id="passwordError" class="text-danger d-none">Password must be at least 6 characters</small>
                            </div>
                        </div>

                    </div>

                    <button id="submitBtn" type="submit" class="btn btn-primary btn-lg w-100 mt-3 shadow-sm">
                        Continue
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    function selectRole(roleValue) {
        document.getElementById("role").value = roleValue;

        document.querySelectorAll('.role-card').forEach(card => card.classList.remove('active'));
        document.getElementById('card-' + roleValue).classList.add('active');

        clearErrors();

        if (roleValue === 'patient') {
            document.getElementById("patient-only").classList.remove("d-none");
            document.getElementById("org-only").classList.add("d-none");
        } else {
            document.getElementById("patient-only").classList.add("d-none");
            document.getElementById("org-only").classList.remove("d-none");
        }
    }

    function clearErrors() {
        document.querySelectorAll(".text-danger").forEach(e => e.classList.add("d-none"));
        document.querySelectorAll("input").forEach(i => i.classList.remove("border-danger"));
    }

    function showError(inputId, errorId) {
        document.getElementById(inputId).classList.add("border-danger");
        document.getElementById(errorId).classList.remove("d-none");
    }

    function setLoading(isLoading) {
        const btn = document.getElementById("submitBtn");
        if (isLoading) {
            btn.disabled = true;
            btn.innerText = "Please wait...";
        } else {
            btn.disabled = false;
            btn.innerText = "Continue";
        }
    }

    async function handleAction() {
        clearErrors();
        let valid = true;
        const role = document.getElementById("role").value;

        // UI-only name validation (not used in DB check now)
        const nameVal = document.getElementById("fullName").value.trim();
        if (!/^[A-Za-z\s]{3,}$/.test(nameVal)) {
            showError("fullName", "nameError");
            valid = false;
        }

        if (role === "patient") {
            const nid = document.getElementById("nationalId").value.trim();
            const ph = document.getElementById("phone").value.trim();

            if (!/^[0-9]{14}$/.test(nid)) { showError("nationalId", "nidError"); valid = false; }
            if (!/^(010|011|012|015)[0-9]{8}$/.test(ph)) { showError("phone", "phoneError"); valid = false; }

            if (!valid) return;

            try {
                setLoading(true);

                const res = await fetch("<?= BASE_URL ?>/auth/patientLogin", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        national_id: nid,
                        phone: ph
                    })
                });

                const data = await res.json();

                if (!res.ok || !data.ok) {
                    alert(data.message || "Patient login failed");
                    return;
                }

                window.location.href = data.redirect || "PatientDashboard.html";

            } catch (e) {
                alert("Network error: " + e.message);
            } finally {
                setLoading(false);
            }

        } else {
            const mail = document.getElementById("email").value.trim();
            const pass = document.getElementById("password").value;

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail)) { showError("email", "emailError"); valid = false; }
            if (pass.length < 6) { showError("password", "passwordError"); valid = false; }

            if (!valid) return;

            try {
                setLoading(true);
      
                const res = await fetch("<?= BASE_URL ?>/auth/staffLogin", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ email: mail, password: pass, portal: role })
                });

                const data = await res.json();

                if (!res.ok || !data.ok) {
                    alert(data.message || "Login failed");
                    return;
                }

                // Insurance first-time setup
 if (role === "insurance") {
    if (!data.policy_completed) {
        window.location.href = "<?= BASE_URL ?>/insurance/policy";
        return;
    }
}


                window.location.href = data.redirect || "landing_page.html";

            } catch (e) {
                alert("Network error: " + e.message);
            } finally {
                setLoading(false);
            }
        }
    }
</script>

</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart-Connect | Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); max-width: 420px; width: 100%; }
        .icon-wrap { width: 72px; height: 72px; border-radius: 50%; background: #fff3cd; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
    </style>
</head>
<body>
<div class="card p-5 text-center">
    <div class="icon-wrap">
        <i class="bi bi-shield-exclamation" style="font-size:2.2rem; color:#f0ad4e;"></i>
    </div>
    <h4 class="fw-bold mb-2">Access Denied</h4>
    <p class="text-muted mb-4">You do not have permission to view this page.<br>Please log in with the correct account.</p>
    <a href="<?= BASE_URL ?>/auth/login" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
    </a>
</div>
</body>
</html>
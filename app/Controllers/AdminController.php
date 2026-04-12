<?php

require_once ROOT . '/app/models/Admin.php';
require_once ROOT . '/app/models/Database.php';

class AdminController {

    public function dashboard() {

        // Auth check
        if (
            empty($_SESSION['auth_type']) ||
            $_SESSION['auth_type'] !== 'staff' ||
            ($_SESSION['role'] ?? '') !== 'ADMIN'
        ) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $db = new Database();
        $conn = $db->getConnection(); // ✅ IMPORTANT (NOT connect())

        $admin = new Admin($conn);

        // ✅ SAME DATA AS YOUR OLD PAGE
        $counts = $admin->getCounts();
        $recentPatients = $admin->getRecentPatients();
        $recentUsers = $admin->getRecentUsers();
        $recentRecords = $admin->getRecentRecords();

        require_once ROOT . '/app/views/admin/dashboard.php';
    }
}
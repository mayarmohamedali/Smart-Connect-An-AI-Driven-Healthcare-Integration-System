<?php

/**
 * Guard.php — Simple Auth Helper
 *
 * Call one of these three functions at the top of every protected controller method.
 * Session is already started in public/index.php so we just read $_SESSION here.
 */
class Guard
{
    /** Returns true if anyone is logged in */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['auth_type']);
    }

    /** Returns true only if a patient is logged in */
    public static function isPatient(): bool
    {
        return ($_SESSION['auth_type'] ?? '') === 'patient';
    }

    /** Returns true only if a staff member with the given role is logged in */
    public static function isStaff(string $role): bool
    {
        return ($_SESSION['auth_type'] ?? '') === 'staff'
            && ($_SESSION['role']      ?? '') === $role;
    }

    /** Redirect to login page */
    private static function redirectToLogin(): void
    {
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }

    /** Redirect to the correct dashboard for whoever is currently logged in */
    private static function redirectToDashboard(): void
    {
        $type = $_SESSION['auth_type'] ?? '';
        $role = $_SESSION['role']      ?? '';

        if ($type === 'patient') {
            header('Location: ' . BASE_URL . '/patient/dashboard');
        } elseif ($type === 'staff') {
            if ($role === 'HOSPITAL_STAFF')      header('Location: ' . BASE_URL . '/hospital/dashboard');
            elseif ($role === 'INSURANCE_STAFF') header('Location: ' . BASE_URL . '/insurance/dashboard');
            elseif ($role === 'ADMIN')           header('Location: ' . BASE_URL . '/admin/dashboard');
            else                                 header('Location: ' . BASE_URL . '/auth/login');
        } else {
            header('Location: ' . BASE_URL . '/auth/login');
        }
        exit;
    }

    /**
     * Use on the LOGIN PAGE only.
     * If the user is already logged in, send them to their dashboard.
     * Usage: Guard::guest();
     */
    public static function guest(): void
    {
        if (self::isLoggedIn()) {
            self::redirectToDashboard();
        }
    }

    /**
     * Use on PATIENT pages.
     * If not a logged-in patient → redirect to login.
     * Usage: Guard::patient();
     */
    public static function patient(): void
    {
        if (!self::isPatient()) {
            self::redirectToLogin();
        }
    }

    /**
     * Use on STAFF pages.
     * If not logged in → redirect to login.
     * If wrong role (e.g. patient opens hospital page) → redirect to their own dashboard.
     * Usage: Guard::staff('HOSPITAL_STAFF');
     *        Guard::staff('INSURANCE_STAFF');
     *        Guard::staff('ADMIN');
     */
    public static function staff(string $requiredRole): void
    {
        if (!self::isLoggedIn()) {
            self::redirectToLogin();
        }

        if (!self::isStaff($requiredRole)) {
            self::redirectToDashboard();
        }
    }
}
<?php
class Validator
{
    /* =========================
       Basic validators
    ========================= */

    public static function validateNationalId(string $national_id): bool
    {
        return (bool)preg_match('/^\d{14}$/', trim($national_id));
    }

    public static function validatePhone(string $phone): bool
    {
        return (bool)preg_match('/^(010|011|012|015)\d{8}$/', trim($phone));
    }

    public static function validateEmail(string $email): bool
    {
        return (bool)filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    // If you want Arabic names too, tell me and I’ll update regex.
    public static function validateName(string $name): bool
    {
        $name = trim($name);
        return (bool)preg_match('/^[A-Za-z\s]{3,}$/', $name);
    }

    public static function validatePassword(string $pass): bool
    {
        return strlen((string)$pass) >= 6;
    }

    public static function validatePortal(string $portal): bool
    {
        $portal = trim($portal);
        if ($portal === "") return true;
        return in_array($portal, ["hospital", "insurance", "admin"], true);
    }

    /* =========================
       Helpers / Sanitizers
    ========================= */

    public static function sanitizeInput($input): string
    {
        return htmlspecialchars(trim((string)$input), ENT_QUOTES, "UTF-8");
    }

    public static function nullIfEmpty($value): ?string
    {
        $value = trim((string)($value ?? ""));
        return $value === "" ? null : $value;
    }

    public static function boolToInt($value): int
    {
        // Accepts 1, "1", true, "true", "on"
        if (is_bool($value)) return $value ? 1 : 0;

        $v = strtolower(trim((string)$value));
        return in_array($v, ["1", "true", "on", "yes"], true) ? 1 : 0;
    }
}

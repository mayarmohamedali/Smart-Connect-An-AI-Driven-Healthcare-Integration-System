<?php
/**
 * EpidemicForecast.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Calls the Flask ML API to retrieve epidemic predictions.
 *
 * Usage in HospitalDashboard.php:
 *   require_once __DIR__ . '/EpidemicForecast.php';
 *   $ef = new EpidemicForecast();
 *   $calendar   = $ef->getHistoricalCalendar();          // full year
 *   $next_alert = $ef->getNextMonthForecast($patients);  // live forecast
 */

class EpidemicForecast
{
    // ── Config ────────────────────────────────────────────────────────────────
    private string $api_base;
    private int    $timeout;

    /**
     * @param string $api_base  Base URL of the Flask API (no trailing slash)
     * @param int    $timeout   cURL timeout in seconds
     */
    public function __construct(
        string $api_base = "http://localhost:5000",
        int    $timeout  = 10
    ) {
        $this->api_base = rtrim($api_base, "/");
        $this->timeout  = $timeout;
    }

    // ── Public methods ────────────────────────────────────────────────────────

    /**
     * Check whether the Flask API is alive.
     * Returns true if reachable, false otherwise.
     */
    public function isApiAlive(): bool
    {
        $result = $this->get("/api/hospital/health");
        return isset($result["status"]) && $result["status"] === "ok";
    }

    /**
     * Fetch the full 12-month historical dominant-disease calendar.
     * Returns array of monthly entries, or [] on failure.
     *
     * Each entry:
     *   month, month_name, dominant_disease, dominant_display,
     *   severity (critical|high|medium|low), recommendations[]
     */
    public function getHistoricalCalendar(): array
    {
        $result = $this->get("/api/hospital/historical");
        return $result["calendar"] ?? [];
    }

    /**
     * Fetch the forecast for a specific month (1-12).
     * Returns a single entry array, or [] on failure.
     */
    public function getMonthForecast(int $month): array
    {
        $result = $this->get("/api/hospital/historical?month=" . $month);
        return is_array($result) && !isset($result["error"]) ? $result : [];
    }

    /**
     * Post a batch of patients and get a live dominant-disease forecast.
     *
     * @param array $patients  Array of patient dicts (see field list below)
     * @return array           Forecast result from Flask, or error array
     *
     * Required patient dict fields:
     *   Age, Gender (Male|Female), BMI, Blood_Pressure, Cholesterol_Level,
     *   Glucose_Level, Smoking_Status (0|1), Physical_Activity (Low|Medium|High),
     *   Diet_Quality (Poor|Average|Good), Alcohol_Consumption (0|1),
     *   Sleep_Hours, Stress_Level, Family_History (0.0|1.0), Medications_Count,
     *   Fever (0|1), Cough (0|1), Fatigue (0|1), Chest_Pain (0|1),
     *   Shortness_of_Breath (0|1), Headache (0|1), Month (1-12)
     */
    public function getNextMonthForecast(array $patients): array
    {
        if (empty($patients)) {
            return [
                "status"           => "insufficient_data",
                "message"          => "No patient records provided.",
                "dominant_disease" => null,
                "distribution"     => [],
                "distribution_pct" => [],
            ];
        }

        return $this->post("/api/hospital/forecast", [
            "patients"     => $patients,
            "min_patients" => 20,
        ]);
    }

    // ── Private HTTP helpers ──────────────────────────────────────────────────

    private function get(string $path): array
    {
        $ch = curl_init($this->api_base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => ["Accept: application/json"],
        ]);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || $raw === false) {
            return ["error" => "API unreachable: " . $err];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ["error" => "Invalid JSON response"];
    }

    private function post(string $path, array $body): array
    {
        $payload = json_encode($body);
        $ch      = curl_init($this->api_base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/json",
                "Content-Length: " . strlen($payload),
                "Accept: application/json",
            ],
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || $raw === false) {
            return ["error" => "API unreachable: " . $err];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ["error" => "Invalid JSON response"];
    }
}
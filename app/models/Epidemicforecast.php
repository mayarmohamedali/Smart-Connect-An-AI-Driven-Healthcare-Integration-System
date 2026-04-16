<?php
/**
 * EpidemicForecast — Service class that calls the Flask ML API.
 * Place in: app/models/EpidemicForecast.php
 *
 * Mirrors InsuranceForecast.php in structure so the pattern is identical
 * across all three dashboards (Patient / Insurance / Hospital).
 *
 * Usage in HospitalController::dashboard():
 *   require_once ROOT . '/app/models/EpidemicForecast.php';
 *   $ef          = new EpidemicForecast('http://127.0.0.1:5000');
 *   $api_alive   = $ef->isApiAlive();
 *   $calendar    = $api_alive ? $ef->getHistoricalCalendar() : [];
 *   $live_forecast = ($api_alive && !empty($patients_for_forecast))
 *                    ? $ef->getNextMonthForecast($patients_for_forecast) : [];
 */
class EpidemicForecast
{
    // ── Config ────────────────────────────────────────────────────────────────
    private string $base_url;
    private int    $timeout;

    /**
     * @param string $base_url  Base URL of the Flask API (no trailing slash)
     * @param int    $timeout   cURL timeout in seconds
     */
    public function __construct(
        string $base_url = 'http://127.0.0.1:5000',
        int    $timeout  = 10
    ) {
        $this->base_url = rtrim($base_url, '/');
        $this->timeout  = $timeout;
    }

    // ── Public methods ────────────────────────────────────────────────────────

    /**
     * Check whether the Flask API is alive.
     * Returns true if /api/hospital/health responds with status=ok.
     */
    public function isApiAlive(): bool
    {
        $result = $this->get('/api/hospital/health');
        return isset($result['status']) && $result['status'] === 'ok';
    }

    /**
     * Fetch the full 12-month historical dominant-disease calendar.
     * Returns an array of monthly entries keyed from Flask, or [] on failure.
     *
     * Each entry contains:
     *   month, month_name, dominant_disease, dominant_display,
     *   severity (critical|high|medium|low), recommendations[]
     */
    public function getHistoricalCalendar(): array
    {
        $result = $this->get('/api/hospital/historical');
        return $result['calendar'] ?? [];
    }

    /**
     * Fetch the historical forecast for a specific month (1-12).
     * Returns a single entry array, or [] on failure / error.
     */
    public function getMonthForecast(int $month): array
    {
        $result = $this->get('/api/hospital/historical?month=' . $month);
        return (is_array($result) && !isset($result['error'])) ? $result : [];
    }

    /**
     * Post a batch of patient records and get a live dominant-disease forecast.
     *
     * @param array $patients  Array of patient dicts (see field list below)
     * @return array           Forecast result from Flask, or error array
     *
     * Required patient dict fields (all produced by HospitalController's SQL):
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
                'status'           => 'insufficient_data',
                'message'          => 'No patient records provided.',
                'dominant_disease' => null,
                'distribution'     => [],
                'distribution_pct' => [],
            ];
        }

        return $this->post('/api/hospital/forecast', [
            'patients'     => $patients,
            'min_patients' => 5,
        ]);
    }

    // ── Private HTTP helpers ──────────────────────────────────────────────────

    private function get(string $path): array
    {
        $ch = curl_init($this->base_url . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || $raw === false) {
            return ['error' => 'API unreachable: ' . $err];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['error' => 'Invalid JSON response'];
    }

    private function post(string $path, array $body): array
    {
        $payload = json_encode($body);
        $ch      = curl_init($this->base_url . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
                'Accept: application/json',
            ],
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || $raw === false) {
            return ['error' => 'API unreachable: ' . $err];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['error' => 'Invalid JSON response'];
    }
}
<?php

class InsuranceForecast {
    private string $base_url;

    public function __construct(string $base_url) {
        $this->base_url = rtrim($base_url, '/');
    }

    public function isApiAlive(): bool {
        $ch = curl_init($this->base_url . "/api/health");
        if (!$ch) return false;
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response !== false && $httpCode === 200;
    }

    public function getSavedForecast(string $company): array {
        $url = $this->base_url . "/api/insurance/forecast?company=" . urlencode($company);
        $ch  = curl_init($url);
        if ($ch === false) return ["status" => "error", "error" => "curl_init failed"];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ["Accept: application/json"],
        ]);

        $body  = curl_exec($ch);
        $errNo = curl_errno($ch);
        $errMsg= curl_error($ch);
        curl_close($ch);

        if ($body === false || $errNo !== 0)
            return ["status" => "error", "error" => "cURL error $errNo: $errMsg"];

        $body = $this->fixNaN($body);
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE)
            return ["status" => "error", "error" => "Invalid JSON: " . json_last_error_msg(), "raw" => substr($body, 0, 300)];

        return $data ?? [];
    }

    public function getLiveForecast(string $company, array $patients): array {
        $url = $this->base_url . "/api/insurance/forecast/live";
        $ch  = curl_init($url);
        if ($ch === false) return ["status" => "error", "error" => "curl_init failed"];

        $payload = json_encode(["company" => $company, "patients" => $patients]);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ["Content-Type: application/json", "Accept: application/json"],
        ]);

        $body  = curl_exec($ch);
        $errNo = curl_errno($ch);
        $errMsg= curl_error($ch);
        curl_close($ch);

        if ($body === false || $errNo !== 0)
            return ["status" => "error", "error" => "cURL error $errNo: $errMsg"];

        $body = $this->fixNaN($body);
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE)
            return ["status" => "error", "error" => "Invalid JSON: " . json_last_error_msg(), "raw" => substr($body, 0, 300)];

        return $data ?? [];
    }

    /** Replace Python's NaN (invalid JSON) with null before decoding */
    private function fixNaN(string $body): string {
        $body = preg_replace('/:\s*NaN\b/',  ': null', $body);
        $body = preg_replace('/,\s*NaN\b/',  ', null', $body);
        $body = preg_replace('/\[\s*NaN\b/', '[null',  $body);
        return $body;
    }
}
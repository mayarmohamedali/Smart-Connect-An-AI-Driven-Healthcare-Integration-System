<?php

class Database {

    private mysqli $conn;

    public function __construct() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->conn = new mysqli(
            '127.0.0.1',
            'root',
            '',
            'smart_connect',
            3307
        );

        $this->conn->set_charset("utf8mb4");
    }

    public function getConnection(): mysqli {
        return $this->conn;
    }

    public function close(): void {
        if (isset($this->conn) && $this->conn instanceof mysqli) {
            try {
                $this->conn->close();
            } catch (Throwable $e) {
                // Already closed
            }
        }
    }
}
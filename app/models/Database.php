<?php

class Database {

    private mysqli $conn;

    public function __construct() {
        $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        $name = defined('DB_NAME') ? DB_NAME : 'smart_connect';
        $port = defined('DB_PORT') ? (int)DB_PORT : 3307;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->conn = new mysqli(
            $host,
            $user,
            $pass,
            $name,
            $port
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
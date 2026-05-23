<?php

class Database {

    private mysqli $conn;

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

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
                // Already closed — nothing to do
            }
        }
    }
}
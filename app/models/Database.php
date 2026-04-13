<?php

class Database {

    private mysqli $conn;

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
    }

    public function getConnection(): mysqli {
        return $this->conn;
    }

    // Optional: keep it safe if ever used
    public function close(): void {
        if (isset($this->conn) && $this->conn instanceof mysqli) {
            if (@$this->conn->ping()) {
                $this->conn->close();
            }
        }
    }
}
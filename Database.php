<?php
class Database {
    private $host = "127.0.0.1";
    private $port = 3307;
    private $user = "root";
    private $pass = "";
    private $dbname = "smart_connect";
    private $conn;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->conn = new mysqli(
                $this->host,
                $this->user,
                $this->pass,
                $this->dbname,
                $this->port
            );

            $this->conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
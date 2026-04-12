<?php
/**
 * Database Connection File - OOP Version
 * This file maintains backward compatibility with old code
 * while supporting the new OOP structure
 */

require_once __DIR__ . 'Database.php';

// Create database instance
$database = new Database();
$conn = $database->getConnection();

// For backward compatibility with existing code
// Old code can still use $conn directly
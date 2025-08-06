<?php
// Set error reporting based on environment
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // Change to 'production' for live site
}

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
define('SITE_URL', 'http://localhost/carrentalphp-main');

function Connect() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "carrentalp";
    
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        if (!$conn->set_charset("utf8mb4")) {
            throw new Exception("Error setting charset: " . $conn->error);
        }
        
        return $conn;
    } catch (Exception $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        
        if (ENVIRONMENT === 'development') {
            die("Connection Error: " . $e->getMessage());
        } else {
            die("Database connection failed. Please try again later.");
        }
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sanitize_input($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validate_db_connection($conn) {
    if (!$conn instanceof mysqli) {
        return false;
    }
    
    if ($conn->connect_error) {
        return false;
    }
    
    try {
        $test = $conn->query("SELECT 1");
        return (bool) $test;
    } catch (Exception $e) {
        return false;
    }
}

function handle_db_error($conn, $error_message = "") {
    error_log("Database Error: " . $conn->error . " - " . $error_message);
    if (ENVIRONMENT === 'development') {
        die("Database Error: " . $conn->error . " - " . $error_message);
    } else {
        die("An error occurred. Please try again later.");
    }
}
?>

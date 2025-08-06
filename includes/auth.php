<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'functions.php';

// Unified login function for both admin and users
function login($identifier, $password)
{
    $conn = Connect();

    $stmt = $conn->prepare("SELECT user_id, username, name, email, phone, password, role 
                            FROM users 
                            WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            return true;
        }
    }

    // Debugging
    error_log("Login attempt failed for identifier: " . $identifier);
    return false;
}

// Register function (only for regular users)
function register($username, $name, $phone, $email, $password)
{
    $conn = Connect();
    
    // Check if email or username already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return false;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user (role = 'user' by default)
    $role = 'user';
    $stmt = $conn->prepare("INSERT INTO users (username, name, email, phone, password, role) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $name, $email, $phone, $hashed_password, $role);
    
    return $stmt->execute();
}

// Logout function
function logout()
{
    $_SESSION = array();
    session_destroy();
}

// Check if email exists
function email_exists($email)
{
    $conn = Connect();
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Get user details by ID
function get_user_details($user_id)
{
    $conn = Connect();
    $stmt = $conn->prepare("SELECT user_id, username, name, email, phone, role 
                            FROM users 
                            WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Check if logged in (any role)
function is_logged_in()
{
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Role-based check
function is_admin_logged_in()
{
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_user_logged_in()
{
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}
?>

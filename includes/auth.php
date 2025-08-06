<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'functions.php';

// Admin login function
function admin_login($username, $password)
{
    $conn = Connect();

    $stmt = $conn->prepare("SELECT admin_id, username, name, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            // Set admin session variables
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['name'] = $admin['name'];
            $_SESSION['admin_logged_in'] = true;
            return true;
        }
    }
    return false;
}

// User login function
function user_login($email, $password)
{
    $conn = Connect();

    // Try login with email first
    $stmt = $conn->prepare("SELECT user_id, email, username, name, password FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $email); // Try both email and username fields
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set user session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['user_logged_in'] = true;
            return true;
        }
    }
    
    // For debugging
    error_log("Login attempt failed for user: " . $email);
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
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $name, $email, $phone, $hashed_password);
    
    if ($stmt->execute()) {
        return true;
    }
    return false;
}

// Logout function
function logout()
{
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
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

// Get user details
function get_user_details($user_id)
{
    $conn = Connect();
    $stmt = $conn->prepare("SELECT user_id, name, email, phone FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get admin details
function get_admin_details($admin_id)
{
    $conn = Connect();
    $stmt = $conn->prepare("SELECT admin_id, username, name, email, phone FROM admins WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Check if user is logged in
function is_user_logged_in()
{
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

// Check if admin is logged in
function is_admin_logged_in()
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}
?>
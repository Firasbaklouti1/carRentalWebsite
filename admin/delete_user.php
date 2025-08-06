<?php
require_once '../includes/auth.php';
require_once '../includes/init.php';
// Check if admin is logged in
if (!is_admin_logged_in()) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if (!isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'User ID not provided']));
}

$user_id = (int)$_GET['user_id'];
$conn = Connect();

// Start transaction
$conn->begin_transaction();

try {
    // First check if user exists and is not an admin
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    // Delete user's bookings first (due to foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Then delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to delete user');
    }
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

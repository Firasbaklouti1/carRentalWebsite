<?php
require_once '../includes/auth.php';
require_once '../includes/init.php';
header('Content-Type: application/json');

// Check if admin is logged in
if (!is_admin_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login as admin to access this page.']);
    exit();
}

$conn = Connect();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    switch ($_GET['action']) {
        case 'delete':
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // First delete all bookings associated with this user
                $stmt = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                // Then delete the user
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                } else {
                    throw new Exception('Error deleting user');
                }
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
}

exit();

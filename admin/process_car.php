<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/init.php';
// Check if admin is logged in
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = Connect();
        
        // Validate database connection
        if (!validate_db_connection($conn)) {
            throw new Exception('Database connection failed');
        }
    
    // Get form data
    $car_name = $_POST['car_name'];
    $car_type = $_POST['car_type'];
    $price = $_POST['price'];
    
    // Handle file upload
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['car_image']['tmp_name'];
        $file_name = $_FILES['car_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check if file is an image
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($file_ext, $allowed)) {
            $_SESSION['error'] = 'Only JPG, JPEG, PNG & GIF files are allowed.';
            header('Location: cars.php');
            exit();
        }
        
        // Generate unique filename
        $new_file_name = uniqid('car_', true) . '.' . $file_ext;
        $upload_path = '../assets/img/cars/' . $new_file_name;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            $car_image = 'assets/img/cars/' . $new_file_name;
            
            // Insert into database
            $sql = "INSERT INTO cars (car_name, car_type, car_image, price, car_availability) VALUES (?, ?, ?, ?, 'yes')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssd", $car_name, $car_type, $car_image, $price);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Car added successfully!";
            } else {
                $_SESSION['error'] = "Error adding car to database.";
                // Delete uploaded file if database insert fails
                unlink($upload_path);
            }
        } else {
            $_SESSION['error'] = "Error uploading file.";
        }
    } else {
        $_SESSION['error'] = "Please select an image file.";
    }
    
    } catch (Exception $e) {
        error_log('Car Processing Error: ' . $e->getMessage());
        $_SESSION['error'] = ENVIRONMENT === 'development' ? $e->getMessage() : 'An error occurred while processing your request.';
    }
    
    header('Location: cars.php');
    exit();
}
?>

<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_user_logged_in()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug user authentication
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('You are not logged in. Please log in to make a booking.');
        }
        
        // Debug session data
        error_log('Session data: ' . print_r($_SESSION, true));
        error_log('POST data: ' . print_r($_POST, true));
        // Validate required fields
        if (empty($_POST['car_id']) || empty($_POST['pickup_date']) || 
            empty($_POST['return_date']) || empty($_POST['pickup_location'])) {
            throw new Exception('All fields are required.');
        }

        $conn = Connect();
        
        // Get and sanitize form data
        $car_id = intval($_POST['car_id']);
        $user_id = $_SESSION['user_id'];
        $pickup_date = date('Y-m-d', strtotime($_POST['pickup_date']));
        $return_date = date('Y-m-d', strtotime($_POST['return_date']));
        $pickup_location = trim($_POST['pickup_location']);
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Calculate total days and amount
    $start = new DateTime($pickup_date);
    $end = new DateTime($return_date);
    $days = $end->diff($start)->days;
    
    // Get car price
    $sql = "SELECT price FROM cars WHERE car_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    
    $total_amount = $days * $car['price'];
    
    // Check if car is available for the selected dates
    $sql = "SELECT booking_id FROM bookings 
            WHERE car_id = ? AND 
            ((pickup_date BETWEEN ? AND ?) OR 
             (return_date BETWEEN ? AND ?) OR 
             (pickup_date <= ? AND return_date >= ?))";
             
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $car_id, $pickup_date, $return_date, $pickup_date, $return_date, $pickup_date, $return_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Sorry, this car is not available for the selected dates.";
        header("Location: book.php?car_id=" . $car_id);
        exit();
    }
    
    // Insert booking
    $sql = "INSERT INTO bookings (user_id, car_id, pickup_date, return_date, pickup_location, notes, total_amount, booking_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssd", $user_id, $car_id, $pickup_date, $return_date, $pickup_location, $notes, $total_amount);
    
    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        $_SESSION['success'] = "Booking successful! Your booking ID is: " . $booking_id;
        header("Location: my_bookings.php");
        exit();
    } else {
        throw new Exception("Error processing your booking.");
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    if (isset($_POST['car_id'])) {
        header("Location: book.php?car_id=" . $_POST['car_id']);
    } else {
        header("Location: cars.php");
    }
    exit();
}
} else {
    header('Location: cars.php');
    exit();
}

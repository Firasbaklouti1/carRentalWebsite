<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only admin can run this
if (!is_admin_logged_in()) {
    die('Unauthorized access');
}

$conn = Connect();

// Read and execute the SQL file
$sql = file_get_contents('../DATABASE FILE/car_types.sql');

// Split SQL commands by semicolon
$commands = array_filter(array_map('trim', explode(';', $sql)), 'strlen');

$success = true;
$error_message = '';

foreach ($commands as $command) {
    if (!$conn->query($command)) {
        $success = false;
        $error_message .= "Error executing: " . $command . "\nError: " . $conn->error . "\n\n";
    }
}

// Add some default car types
if ($success) {
    $default_types = ['Sedan', 'SUV', 'Luxury', 'Sports', 'Van', 'Hatchback'];
    $insert_sql = "INSERT IGNORE INTO car_types (type_name) VALUES (?)";
    $stmt = $conn->prepare($insert_sql);
    
    foreach ($default_types as $type) {
        $stmt->bind_param('s', $type);
        $stmt->execute();
    }
    $stmt->close();
}

// Update existing cars to use the first car type
$update_sql = "UPDATE cars SET car_type = (SELECT type_id FROM car_types LIMIT 1) WHERE car_type IS NOT NULL";
$conn->query($update_sql);

if ($success) {
    echo "Car types table created successfully and default types added!<br>";
    echo "<a href='car_types.php' class='btn btn-primary'>Go to Car Types Management</a>";
} else {
    echo "Error creating car types table:<br>";
    echo "<pre>" . htmlspecialchars($error_message) . "</pre>";
}

$conn->close();

<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';

// Ensure only admin can run this
if (!is_admin_logged_in()) {
    die('Unauthorized access');
}



$conn = Connect();

// Read and execute the SQL file from DATABASE FILE folder
$sql = file_get_contents('../DATABASE FILE/add_fuel_type.sql');

if ($conn->multi_query($sql)) {
    do {
        // Free result
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Added fuel_type column successfully! You can now go back and edit cars.";
} else {
    echo "Error updating database: " . $conn->error;
}

$conn->close();

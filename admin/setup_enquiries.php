<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure only admin can run this
if (!is_admin_logged_in()) {
    die('Unauthorized access');
}

$conn = Connect();

// Read and execute the SQL file
$sql = file_get_contents('../DATABASE FILE/add_enquiries.sql');

if ($conn->multi_query($sql)) {
    echo "Enquiries table created successfully!<br>";
    echo "<a href='dashboard.php' class='btn btn-primary'>Go back to Dashboard</a>";
} else {
    echo "Error creating enquiries table: " . $conn->error;
}

$conn->close();

<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';
// Ensure only admin can run this
if (!is_admin_logged_in()) {
    die('Unauthorized access');
}

$conn = Connect();

// Get table structure
$sql = "SHOW COLUMNS FROM cars";
$result = $conn->query($sql);

echo "<h2>Cars Table Structure:</h2>";
echo "<pre>";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
} else {
    echo "Error getting table structure: " . $conn->error;
}
echo "</pre>";

$conn->close();

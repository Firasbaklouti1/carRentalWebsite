<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// Get database connection
$conn = Connect();

// Set current page for nav highlighting
$current_page = 'dashboard';
$page_title = 'Dashboard';

// Initialize counts
$cars_count = 0;
$bookings_count = 0;
$users_count = 0;
$enquiries_count = 0;

// Get counts using try-catch to handle potential errors
try {
    // Check if tables exist before querying
    $tables_result = $conn->query("SHOW TABLES");
    $tables = array();
    while ($table = $tables_result->fetch_row()) {
        $tables[] = $table[0];
    }

    // Cars count
    if (in_array('cars', $tables)) {
        $cars_result = $conn->query("SELECT COUNT(*) as total FROM cars");
        if ($cars_result) {
            $cars_count = $cars_result->fetch_assoc()['total'];
        }
    }

    // Bookings count
    if (in_array('bookings', $tables)) {
        $bookings_result = $conn->query("SELECT COUNT(*) as total FROM bookings");
        if ($bookings_result) {
            $bookings_count = $bookings_result->fetch_assoc()['total'];
        }
    }

    // Users count
    if (in_array('users', $tables)) {
        $users_result = $conn->query("SELECT COUNT(*) as total FROM users");
        if ($users_result) {
            $users_count = $users_result->fetch_assoc()['total'];
        }
    }

    // Recent enquiries count
    if (in_array('enquiries', $tables)) {
        // Check if created_at column exists
        $columns_result = $conn->query("SHOW COLUMNS FROM enquiries LIKE 'created_at'");
        if ($columns_result->num_rows > 0) {
            $enquiries_result = $conn->query("SELECT COUNT(*) as total FROM enquiries WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        } else {
            $enquiries_result = $conn->query("SELECT COUNT(*) as total FROM enquiries");
        }
        if ($enquiries_result) {
            $enquiries_count = $enquiries_result->fetch_assoc()['total'];
        }
    }
} catch (Exception $e) {
    error_log("Error in dashboard counts: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Dashboard Overview</h1>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <!-- Total Cars -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <span class="stat-icon">
                            <i class="fas fa-car fa-2x text-primary"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="h6 mb-2">Total Cars</h3>
                        <div class="count"><?php echo $cars_count; ?></div>
                        <p class="text-muted small mb-0">Available for Rent</p>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="cars.php" class="btn btn-sm btn-light">View Details <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <!-- Total Bookings -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <span class="stat-icon">
                            <i class="fas fa-calendar-check fa-2x text-success"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="h6 mb-2">Total Bookings</h3>
                        <div class="count"><?php echo $bookings_count; ?></div>
                        <p class="text-muted small mb-0">All Time Bookings</p>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="bookings.php" class="btn btn-sm btn-light">View Details <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <!-- Total Users -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <span class="stat-icon">
                            <i class="fas fa-users fa-2x text-info"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="h6 mb-2">Total Users</h3>
                        <div class="count"><?php echo $users_count; ?></div>
                        <p class="text-muted small mb-0">Registered Users</p>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="users.php" class="btn btn-sm btn-light">View Details <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <!-- Recent Enquiries -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <span class="stat-icon">
                            <i class="fas fa-envelope fa-2x text-warning"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="h6 mb-2">Recent Enquiries</h3>
                        <div class="count"><?php echo $enquiries_count; ?></div>
                        <p class="text-muted small mb-0">Last 30 Days</p>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="enquiries.php" class="btn btn-sm btn-light">View Details <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Data -->
    <div class="row g-4">
        <!-- Recent Bookings -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Bookings</h5>
                        <a href="bookings.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php
                    try {
                        if (in_array('bookings', $tables)) {
                            // Check required columns
                            $bookings_cols = $conn->query("SHOW COLUMNS FROM bookings");
                            $booking_columns = array();
                            while ($col = $bookings_cols->fetch_assoc()) {
                                $booking_columns[] = $col['Field'];
                            }

                            $order_by = in_array('created_at', $booking_columns) ? 'created_at' : 'booking_id';
                            $amount_field = in_array('total_amount', $booking_columns) ? 'total_amount' : 'amount';

                            $recent_bookings = $conn->query("
                                SELECT b.*, u.name as customer_name, c.car_name 
                                FROM bookings b 
                                JOIN users u ON b.user_id = u.user_id 
                                JOIN cars c ON b.car_id = c.car_id 
                                ORDER BY b.$order_by DESC 
                                LIMIT 5
                            ");
                            
                            if ($recent_bookings && $recent_bookings->num_rows > 0) {
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-hover mb-0">';
                                echo '<thead class="table-light">';
                                echo '<tr>';
                                echo '<th class="border-0">Customer</th>';
                                echo '<th class="border-0">Car</th>';
                                echo '<th class="border-0">Amount</th>';
                                echo '<th class="border-0">Status</th>';
                                echo '</tr>';
                                echo '</thead>';
                                echo '<tbody>';
                                while ($booking = $recent_bookings->fetch_assoc()) {
                                    // Default to 'pending' if status is not set
                                    $status = $booking['status'] ?? 'pending';
                                    $status_class = match(strtolower($status)) {
                                        'confirmed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'warning'
                                    };
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($booking['customer_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($booking['car_name']) . '</td>';
                                    echo '<td>Rs. ' . number_format($booking['total_amount'] ?? 0, 2) . '</td>';
                                    echo '<td><span class="badge bg-' . $status_class . '">' . ucfirst($status) . '</span></td>';
                                    echo '</tr>';
                                }
                                echo '</tbody>';
                                echo '</table>';
                                echo '</div>';
                            } else {
                                echo '<div class="text-center py-4">';
                                echo '<p class="text-muted mb-0">No recent bookings</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="text-center py-4">';
                            echo '<p class="text-muted mb-0">Bookings table not found</p>';
                            echo '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="text-center py-4">';
                        echo '<p class="text-danger mb-0">Error loading recent bookings</p>';
                        echo '</div>';
                        error_log("Error loading recent bookings: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Recently Added Cars -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recently Added Cars</h5>
                        <a href="cars.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php
                    try {
                        if (in_array('cars', $tables)) {
                            // Check required columns
                            $cars_cols = $conn->query("SHOW COLUMNS FROM cars");
                            $car_columns = array();
                            while ($col = $cars_cols->fetch_assoc()) {
                                $car_columns[] = $col['Field'];
                            }

                            $order_by = in_array('created_at', $car_columns) ? 'created_at' : 'car_id';
                            $price_field = in_array('price_per_day', $car_columns) ? 'price_per_day' : 'price';

                            $recent_cars = $conn->query("
                                SELECT * FROM cars 
                                ORDER BY $order_by DESC 
                                LIMIT 5
                            ");
                            
                            if ($recent_cars && $recent_cars->num_rows > 0) {
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-hover mb-0">';
                                echo '<thead class="table-light">';
                                echo '<tr>';
                                echo '<th class="border-0">Car</th>';
                                echo '<th class="border-0">Type</th>';
                                echo '<th class="border-0">Price/Day</th>';
                                echo '<th class="border-0">Status</th>';
                                echo '</tr>';
                                echo '</thead>';
                                echo '<tbody>';
                                while ($car = $recent_cars->fetch_assoc()) {
                                    // Default to 'unavailable' if status is not set
                                    $status = $car['status'] ?? 'unavailable';
                                    $status_class = $status === 'available' ? 'success' : 'warning';
                                    
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($car['car_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($car['car_type']) . '</td>';
                                    echo '<td>Rs. ' . number_format($car['price_per_day'] ?? 0, 2) . '</td>';
                                    echo '<td><span class="badge bg-' . $status_class . '">' . ucfirst($status) . '</span></td>';
                                    echo '</tr>';
                                }
                                echo '</tbody>';
                                echo '</table>';
                                echo '</div>';
                            } else {
                                echo '<div class="text-center py-4">';
                                echo '<p class="text-muted mb-0">No cars added yet</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="text-center py-4">';
                            echo '<p class="text-muted mb-0">Cars table not found</p>';
                            echo '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="text-center py-4">';
                        echo '<p class="text-danger mb-0">Error loading recent cars</p>';
                        echo '</div>';
                        error_log("Error loading recent cars: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>

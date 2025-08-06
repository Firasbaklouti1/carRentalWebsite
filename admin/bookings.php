<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/init.php';
// Check if user is logged in and is admin
if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// Get database connection
$conn = Connect();

// Set current page for nav highlighting
$current_page = 'bookings';
$page_title = 'Manage Bookings';

// Handle booking status updates and deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['booking_id'])) {
        if (isset($_POST['status'])) {
            // Update booking status
            $booking_id = $_POST['booking_id'];
            $status = $_POST['status'];
            
            $sql = "UPDATE bookings SET status = ? WHERE booking_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $status, $booking_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Booking status updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to update booking status.";
            }
            
            $stmt->close();
        } elseif (isset($_POST['delete_booking'])) {
            // Delete booking
            $booking_id = $_POST['booking_id'];
            
            $sql = "DELETE FROM bookings WHERE booking_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $booking_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Booking deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete booking.";
            }
            
            $stmt->close();
        }
        header('Location: bookings.php');
        exit;
    }
}

// Get all bookings with user and car details
$sql = "SELECT b.*, u.name as user_name, u.email, c.car_name, c.car_type, c.price 
        FROM bookings b 
        JOIN users u ON b.user_id = u.user_id 
        JOIN cars c ON b.car_id = c.car_id 
        ORDER BY b.booking_date DESC";
$result = $conn->query($sql);

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><?= __('Manage Bookings'); ?></h1>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i><?= __('Print Report'); ?>
                </button>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header py-3 bg-white">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="m-0 fw-bold text-primary"><?= __('All Bookings'); ?></h6>
                    </div>
                    <div class="col-auto">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchBookings" placeholder="Search bookings...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4"><?= __('Booking ID'); ?></th>
                                <th><?= __('User'); ?></th>
                                <th><?= __('Car'); ?></th>
                                <th><?= __('From Date'); ?></th>
                                <th><?= __('To Date'); ?></th>
                                <th><?= __('Total Cost'); ?></th>
                                <th><?= __('Status'); ?></th>
                                <th class="text-end pe-4"><?= __('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($booking = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">#<?php echo $booking['booking_id']; ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold"><?php echo htmlspecialchars($booking['user_name']); ?></span>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold"><?php echo htmlspecialchars($booking['car_name']); ?></span>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['car_type']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['return_date'])); ?></td>
                                        <td>Rs. <?php 
                                            $total = calculate_rental_cost(
                                                $booking['price'], 
                                                $booking['pickup_date'], 
                                                $booking['return_date']
                                            );
                                            echo number_format($total, 2); 
                                        ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo match($booking['status']) {
                                                    'pending' => 'bg-warning',
                                                    'confirmed' => 'bg-success',
                                                    'cancelled' => 'bg-danger',
                                                    'completed' => 'bg-info',
                                                    default => 'bg-secondary'
                                                };
                                            ?>"><?php echo ucfirst($booking['status']); ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-edit me-1"></i><?= __('Status'); ?>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <form method="POST" class="dropdown-item d-flex align-items-center">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                                <input type="hidden" name="status" value="pending">
                                                                <button type="submit" class="btn btn-link text-warning p-0 text-decoration-none">
                                                                    <i class="fas fa-clock me-2"></i><?= __('Pending'); ?>
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" class="dropdown-item d-flex align-items-center">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                                <input type="hidden" name="status" value="confirmed">
                                                                <button type="submit" class="btn btn-link text-success p-0 text-decoration-none">
                                                                    <i class="fas fa-check me-2"></i><?= __('Confirm'); ?>
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" class="dropdown-item d-flex align-items-center">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                                <input type="hidden" name="status" value="cancelled">
                                                                <button type="submit" class="btn btn-link text-danger p-0 text-decoration-none">
                                                                    <i class="fas fa-ban me-2"></i><?= __('Cancel'); ?>
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" class="dropdown-item d-flex align-items-center">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" class="btn btn-link text-info p-0 text-decoration-none">
                                                                    <i class="fas fa-flag-checkered me-2"></i><?= __('Complete'); ?>
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteBookingModal<?php echo $booking['booking_id']; ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>

                                            <!-- Delete Booking Modal -->
                                            <div class="modal fade" id="deleteBookingModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><?= __('Delete Booking'); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <p><?= __('Are you sure you want to delete this booking?'); ?></p>
                                                            <p class="mb-0"><strong><?= __('Booking ID:'); ?></strong> #<?php echo $booking['booking_id']; ?></p>
                                                            <p class="mb-0"><strong><?= __('User:'); ?></strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                                                            <p class="mb-0"><strong><?= __('Car:'); ?></strong> <?php echo htmlspecialchars($booking['car_name']); ?></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('Cancel'); ?></button>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                                <input type="hidden" name="delete_booking" value="1">
                                                                <button type="submit" class="btn btn-danger"><?= __('Delete'); ?></button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4"><?= __('No bookings found.'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchBookings');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    }
});
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>

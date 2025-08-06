<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_user_logged_in()) {
    header('Location: login.php');
    exit();
}

$conn = Connect();
$user_id = $_SESSION['user_id'];

// Get user's bookings
$sql = "SELECT b.*, c.car_name, c.car_image, c.car_type, c.price 
        FROM bookings b 
        JOIN cars c ON b.car_id = c.car_id 
        WHERE b.user_id = ? 
        ORDER BY b.booking_date DESC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Car Rental System</title>
    <?php include 'includes/header.php'; ?>
</head>
<body class="bg-light">
    <?php include 'includes/navigation.php'; ?>

    <div class="container py-5">
        <h2 class="mb-4">My Bookings</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($bookings->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php while($booking = $bookings->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="<?php echo htmlspecialchars($booking['car_image']); ?>" 
                                         class="img-fluid rounded-start h-100" 
                                         alt="<?php echo htmlspecialchars($booking['car_name']); ?>"
                                         style="object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($booking['car_name']); ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                Type: <?php echo htmlspecialchars($booking['car_type']); ?> | 
                                                Price/Day: Rs. <?php echo number_format($booking['price'], 2); ?>
                                            </small>
                                        </p>
                                        
                                        <div class="mb-2">
                                            <strong>Pickup:</strong> <?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?><br>
                                            <strong>Return:</strong> <?php echo date('M d, Y', strtotime($booking['return_date'])); ?><br>
                                            <strong>Location:</strong> <?php echo htmlspecialchars($booking['pickup_location']); ?>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Total:</strong> 
                                                <span class="text-primary">
                                                    $<?php echo number_format($booking['total_amount'], 2); ?>
                                                </span>
                                            </div>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] === 'confirmed' ? 'success' : 
                                                    ($booking['status'] === 'pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <div class="mt-3">
                                                <form action="cancel_booking.php" method="POST" 
                                                      onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times me-1"></i>Cancel Booking
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4>No Bookings Found</h4>
                <p class="text-muted">You haven't made any car bookings yet.</p>
                <a href="cars.php" class="btn btn-primary">
                    <i class="fas fa-car me-2"></i>Browse Cars
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/init.php';

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
    <title><?= __('My Bookings - Car Rental System'); ?></title>
    <?php include 'includes/header.php'; ?>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <?php include 'includes/navigation.php'; ?>

    <main class="container section">
        <div class="section-header text-start">
            <h1 class="section-title mb-2">
                <i class="fas fa-clipboard-list text-primary me-2" aria-hidden="true"></i><?= __('My Bookings'); ?>
            </h1>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= __('Close'); ?>"></button>
            </div>
        <?php endif; ?>

        <?php if ($bookings->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php while($booking = $bookings->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card modern-card h-100">
                            <div class="row g-0 h-100">
                                <div class="col-md-4">
                                    <img src="<?php echo htmlspecialchars($booking['car_image']); ?>" 
                                         class="img-fluid rounded-start h-100 w-100" 
                                         alt="<?php echo htmlspecialchars($booking['car_name']); ?>"
                                         style="object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body h-100 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($booking['car_name']); ?></h5>
                                                <p class="text-muted small mb-2">
                                                    <?= __('Type'); ?>: <?php echo htmlspecialchars($booking['car_type']); ?> • 
                                                    <?= __('Price/Day: Rs.'); ?> <?php echo number_format($booking['price'], 0,0); ?>
                                                </p>
                                            </div>
                                            <span class="badge <?php 
                                                echo $booking['status'] === 'confirmed' ? 'bg-success' : 
                                                    ($booking['status'] === 'pending' ? 'bg-warning' : 'bg-danger'); 
                                                ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </div>

                                        <div class="mb-2 small">
                                            <strong><?= __('Pickup:'); ?></strong> <?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?><br>
                                            <strong><?= __('Return:'); ?></strong> <?php echo date('M d, Y', strtotime($booking['return_date'])); ?><br>
                                            <strong><?= __('Location:'); ?></strong> <?php echo htmlspecialchars($booking['pickup_location']); ?>
                                        </div>
                                        
                                        <div class="mt-auto d-flex justify-content-between align-items-center">
                                            <div class="fw-semibold">
                                                <?= __('Total:'); ?> 
                                                <span class="text-primary">
                                                    DT<?php echo number_format($booking['total_amount'], 0,0); ?>
                                                </span>
                                            </div>
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <form action="cancel_booking.php" method="POST" 
                                                      onsubmit="return confirm('<?= __('Are you sure you want to cancel this booking?'); ?>');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times me-1" aria-hidden="true"></i><?= __('Cancel'); ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3" aria-hidden="true"></i>
                <h4 class="mb-2"><?= __('No Bookings Found'); ?></h4>
                <p class="text-muted"><?= __('You haven t made any car bookings yet.'); ?></p>
                <a href="cars.php" class="btn btn-primary btn-elevated">
                    <i class="fas fa-car me-2" aria-hidden="true"></i><?= __('Browse Cars'); ?>
                </a>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

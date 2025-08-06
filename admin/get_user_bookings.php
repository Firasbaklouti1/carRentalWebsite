<?php
require_once '../includes/auth.php';
require_once '../includes/init.php';
// Check if admin is logged in
if (!is_admin_logged_in()) {
    die('Unauthorized access');
}

if (!isset($_GET['user_id'])) {
    die('User ID not provided');
}

$user_id = (int)$_GET['user_id'];
$conn = Connect();

// Get user details
$stmt = $conn->prepare("SELECT name, username FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die('User not found');
}

// Get user's bookings
$stmt = $conn->prepare("
    SELECT b.*, c.car_name 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.car_id 
    WHERE b.user_id = ? 
    ORDER BY b.from_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
?>

<div class="d-flex align-items-center mb-3">
    <div>
        <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th><?= __('Car'); ?></th>
                <th><?= __('From Date'); ?></th>
                <th><?= __('To Date'); ?></th>
                <th><?= __('Status'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($bookings->num_rows > 0): ?>
                <?php while($booking = $bookings->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $booking['booking_id']; ?></td>
                        <td><?php echo htmlspecialchars($booking['car_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($booking['from_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($booking['to_date'])); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $booking['status'] === 'confirmed' ? 'success' : 
                                    ($booking['status'] === 'cancelled' ? 'danger' : 'warning');
                            ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center py-3"><?= __('No bookings found'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

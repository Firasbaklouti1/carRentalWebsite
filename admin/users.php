<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/init.php';
// Check if admin is logged in
if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// Get database connection
$conn = Connect();

// Set current page for nav highlighting
$current_page = 'users';
$page_title = 'Manage Users';

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'delete') {
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete user.";
        }
        
        $stmt->close();
        header('Location: users.php');
        exit;
    }
}

// Get all users
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id) as booking_count 
        FROM users u 
        ORDER BY u.user_id DESC";
$result = $conn->query($sql);

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid p-4">
        <h1 class="h3 mb-4"><?= __('Manage Users'); ?></h1>

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

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?= __('User ID'); ?></th>
                                <th><?= __('Name'); ?></th>
                                <th><?= __('Email'); ?></th>
                                <th><?= __('Phone'); ?></th>
                                <th><?= __('Bookings'); ?></th>
                                <th><?= __('Joined Date'); ?></th>
                                <th><?= __('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($user = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $user['user_id']; ?></td>
                                        <td><?= htmlspecialchars($user['name']); ?></td>
                                        <td><?= htmlspecialchars($user['email']); ?></td>
                                        <td><?= htmlspecialchars($user['phone']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= $user['booking_count']; ?> <?= __('bookings'); ?>
                                            </span>
                                        </td>
                                        <td><?= format_date($user['created_at']); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?= $user['user_id']; ?>">
                                                <i class="fas fa-trash-alt"></i> <?= __('Delete'); ?>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?= $user['user_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?= __('Delete User'); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><?= __('Are you sure you want to delete user'); ?> 
                                                       <strong><?= htmlspecialchars($user['name']); ?></strong>?</p>
                                                    <p class="text-danger">
                                                        <small><?= __('This action cannot be undone.'); ?></small>
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <?= __('Cancel'); ?>
                                                    </button>
                                                    <form action="users.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="btn btn-danger"><?= __('Delete User'); ?></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center"><?= __('No users found.'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<?php 
$conn->close();
include 'includes/footer.php'; 
?>

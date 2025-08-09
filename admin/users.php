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

// Handle guest user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_guest') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || $email === '' || $phone === '') {
        $_SESSION['error'] = "Please fill in all required fields.";
    } else {
        // Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param('s', $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $_SESSION['error'] = "A user with this email already exists.";
        } else {
            // Create guest user with role 'guest'
            // Password can be NULL or random hashed string, since guest won't login
            $password_hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

            $insert_sql = "INSERT INTO users (username, password, name, email, phone, role) VALUES (?, ?, ?, ?, ?, 'guest')";
            $stmt_insert = $conn->prepare($insert_sql);
            // username: generate from name or email to ensure unique (simple version: email prefix)
            $username = strtolower(preg_replace('/[^a-z0-9]/', '', strstr($email, '@', true) ?: $name));

            $stmt_insert->bind_param('sssss', $username, $password_hash, $name, $email, $phone);

            if ($stmt_insert->execute()) {
                $_SESSION['success'] = "Guest user created successfully.";
            } else {
                $_SESSION['error'] = "Failed to create guest user.";
            }
            $stmt_insert->close();
        }

        $stmt_check->close();
    }

    header('Location: users.php');
    exit;
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
        <!-- Create Guest User Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><?= __('Create Guest User'); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="users.php">
                    <input type="hidden" name="action" value="create_guest">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="guest_name" class="form-label"><?= __('Name'); ?></label>
                            <input type="text" class="form-control" id="guest_name" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="guest_email" class="form-label"><?= __('Email'); ?></label>
                            <input type="email" class="form-control" id="guest_email" name="email" required>
                        </div>
                        <div class="col-md-4">
                            <label for="guest_phone" class="form-label"><?= __('Phone'); ?></label>
                            <input type="text" class="form-control" id="guest_phone" name="phone" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary"><?= __('Create Guest User'); ?></button>
                    </div>
                </form>
            </div>
        </div>


        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?= __('User ID'); ?></th>
                                <th><?= __('Name'); ?></th>
                                <th><?= __('Role'); ?></th> 
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
                                        <td><?= htmlspecialchars(ucfirst($user['role'])); ?></td>
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
                                            <a href="user_documents.php?user_id=<?= $user['user_id']; ?>" class="btn btn-sm btn-light mt-2">
                                                <?= __('View Documents'); ?> <i class="fas fa-arrow-right ms-1"></i>
                                            </a>
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

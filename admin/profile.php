<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/init.php';
// Ensure user is logged in as admin
if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$conn = Connect();
$admin_id = $_SESSION['user_id'];

// Set current page for nav highlighting
$current_page = 'profile';
$page_title = 'Profile';

// Get admin details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // If changing password
    if (!empty($current_password)) {
        if (empty($new_password)) {
            $errors[] = "New password is required when changing password";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        // Verify current password
        if (!password_verify($current_password, $admin['password'])) {
            $errors[] = "Current password is incorrect";
        }
    }
    
    if (empty($errors)) {
        // Update profile
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $name, $email, $phone, $hashed_password, $admin_id);
        } else {
            // Update without password
            $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $email, $phone, $admin_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully";
            // Refresh admin data
            header('Location: profile.php');
            exit;
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title h5 mb-0"><?= __('Edit Profile'); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label"><?= __('Full Name'); ?></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($admin['name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label"><?= __('Email Address'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($admin['email']); ?>" required>
                            </div>

                            <div class="col-12">
                                <label for="phone" class="form-label"><?= __('Phone Number'); ?></label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($admin['phone']); ?>" required>
                            </div>

                            <div class="col-12">
                                <hr>
                                <h5><?= __('Change Password'); ?></h5>
                                <p class="text-muted small"><?= __('Leave blank if you don\'t want to change your password'); ?></p>
                            </div>

                            <div class="col-12">
                                <label for="current_password" class="form-label"><?= __('Current Password'); ?></label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>

                            <div class="col-md-6">
                                <label for="new_password" class="form-label"><?= __('New Password'); ?></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                            </div>

                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label"><?= __('Confirm New Password'); ?></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?= __('Save Changes'); ?>
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i><?= __('Back to Dashboard'); ?>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<?php 
$conn->close();
include 'includes/footer.php'; 
?>

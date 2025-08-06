<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/init.php';


// Check if user is logged in
if (!is_user_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get current user data
$conn = Connect();
$stmt = $conn->prepare("SELECT username, name, email, phone FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $current_password = sanitize_input($_POST['current_password']);
    $new_password = sanitize_input($_POST['new_password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);

    // Validate inputs
    if (empty($name)) {
        $error_message = "Name cannot be empty";
    } elseif (!empty($new_password)) {
        // If user wants to change password
        if (empty($current_password)) {
            $error_message = "Current password is required to set a new password";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();

            if (!password_verify($current_password, $user_data['password'])) {
                $error_message = "Current password is incorrect";
            } else {
                // Update user data with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, password = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $name, $phone, $hashed_password, $user_id);
            }
        }
    } else {
        // Update user data without changing password
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $name, $phone, $user_id);
    }

    // If no errors, execute the update
    if (empty($error_message) && $stmt->execute()) {
        $_SESSION['name'] = $name; // Update session name
        $success_message = "Profile updated successfully";
        // Refresh user data
        $user['name'] = $name;
        $user['phone'] = $phone;
    } elseif (empty($error_message)) {
        $error_message = "Failed to update profile";
    }
}

$page_title = "My Profile";
include 'includes/header.php';
?>

<div class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4"><?= __('My Profile'); ?></h2>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label"><?= __('Username'); ?></label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            <small class="text-muted"><?= __('Username cannot be changed'); ?></small>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label"><?= __('Email'); ?></label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            <small class="text-muted"><?= __('Email cannot be changed'); ?></small>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label"><?= __('Full Name'); ?></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label"><?= __('Phone Number'); ?></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>

                        <hr class="my-4">
                        <h4 class="mb-3"><?= __('Change Password'); ?></h4>
                        <p class="text-muted small"><?= __('Leave password fields empty if you don t want to change it'); ?></p>

                        <div class="mb-3">
                            <label for="current_password" class="form-label"><?= __('Current Password'); ?></label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label"><?= __('New Password'); ?></label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><?= __('Confirm New Password'); ?></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><?= __('Update Profile'); ?></button>
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

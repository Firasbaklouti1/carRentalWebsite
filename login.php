<?php
require_once 'includes/auth.php';
require_once  'includes/init.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    redirect('index.php');
}

// If trying to access user login while logged in as admin, redirect to admin dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    redirect('admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $password = isset($_POST['password']) ? sanitize_input($_POST['password']) : '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email/username and password";
    } else {
        if (login($email, $password)) {
            redirect('index.php', 'Welcome back!', 'success');
        } else {
            $error = "Invalid email/username or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Login - Car Rental System'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4"><?= __('Login'); ?></h2>
                        <?php echo display_message(); ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label"><?= __('Email or Username'); ?></label>
                                <input type="text" class="form-control" id="email" name="email" required 
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?= __('Password'); ?></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary"><?= __('Login'); ?></button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p><?= __('Don t have an account?'); ?> <a href="register.php"><?= __('Register here'); ?></a></p>
                            <p><?= __('Are you an admin?'); ?> <a href="admin/login.php"><?= __('Admin Login'); ?></a></p>
                            <p><a href="index.php"><?= __('Back to Home'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

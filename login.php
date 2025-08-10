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
    <?php include 'includes/header.php'; ?>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<?php include 'includes/navigation.php'; ?>
    <main class="container section">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="text-center mb-3">
                    <i class="fas fa-steering-wheel fa-2x text-primary" aria-hidden="true"></i>
                </div>
                <div class="card modern-card">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h4 text-center mb-4"><?= __('Login'); ?></h1>
                        <?php echo display_message(); ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="row g-3" novalidate>
                            <div class="col-12">
                                <label for="email" class="form-label"><?= __('Email or Username'); ?></label>
                                <input type="text" class="form-control" id="email" name="email" required 
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" autocomplete="username">
                            </div>
                            <div class="col-12">
                                <label for="password" class="form-label"><?= __('Password'); ?></label>
                                <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary btn-elevated"><?= __('Login'); ?></button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-1"><?= __('Don t have an account?'); ?> <a href="register.php"><?= __('Register here'); ?></a></p>
                            <p class="mb-1"><?= __('Are you an admin?'); ?> <a href="admin/login.php"><?= __('Admin Login'); ?></a></p>
                            <p class="mb-0"><a href="index.php"><?= __('Back to Home'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

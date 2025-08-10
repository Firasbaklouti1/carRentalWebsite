<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once 'includes/init.php';
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = sanitize_input($_POST['username']);
        $name = sanitize_input($_POST['name']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $password = sanitize_input($_POST['password']);
        $confirm_password = sanitize_input($_POST['confirm_password']);
        
        // Validate inputs
        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long";
        }
        if (empty($name)) {
            $errors[] = "Name is required";
        }
        if (empty($phone)) {
            $errors[] = "Phone number is required";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        if (empty($password) || strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        
        if (empty($errors)) {
            if (register($username, $name, $phone, $email, $password)) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header('Location: login.php');
                exit;
            } else {
                $errors[] = "Registration failed. Username or email may already exist.";
            }
        }
    } catch (Exception $e) {
        error_log("Registration Error: " . $e->getMessage());
        $errors[] = "An error occurred during registration. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Register - Car Rental System'); ?></title>
    <?php include 'includes/header.php'; ?>
</head>
<?php include 'includes/navigation.php'; ?>
<body class="bg-light d-flex flex-column min-vh-100">
    <main class="container section">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="text-center mb-3">
                    <i class="fas fa-id-card fa-2x text-primary" aria-hidden="true"></i>
                </div>
                <div class="card modern-card">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h4 text-center mb-4"><?= __('Register'); ?></h1>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="row g-3" novalidate>
                            <div class="col-12">
                                <label for="username" class="form-label"><?= __('Username'); ?></label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autocomplete="username">
                            </div>
                            
                            <div class="col-12">
                                <label for="name" class="form-label"><?= __('Full Name'); ?></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required autocomplete="name">
                            </div>
                            
                            <div class="col-12">
                                <label for="email" class="form-label"><?= __('Email'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required autocomplete="email">
                            </div>
                            
                            <div class="col-12">
                                <label for="phone" class="form-label"><?= __('Phone'); ?></label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required autocomplete="tel">
                            </div>
                            
                            <div class="col-12">
                                <label for="password" class="form-label"><?= __('Password'); ?></label>
                                <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                            </div>
                            
                            <div class="col-12">
                                <label for="confirm_password" class="form-label"><?= __('Confirm Password'); ?></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                            </div>
                            
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary btn-elevated"><?= __('Register'); ?></button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-1"><?= __('Already have an account?'); ?> <a href="login.php"><?= __('Login here'); ?></a></p>
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

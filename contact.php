<?php 
require_once 'includes/auth.php'; 
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/init.php';

// ensure session is started (auth.php may already do this)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$conn = Connect();

// Create enquiries table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Pre-fill variables (will be used in form values)
$name = '';
$email = '';
$phone = '';
$subject = '';
$message_text = '';

// If user is logged in, fetch their info from DB and prefill the form
if (function_exists('is_user_logged_in') && is_user_logged_in()) {
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        $user_id = (int) $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT username, name, email, phone FROM users WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $user = $result->fetch_assoc()) {
                // prefer 'name' column; fall back to username if name empty
                $name = $user['name'] ?? $user['username'] ?? '';
                $email = $user['email'] ?? '';
                $phone = $user['phone'] ?? '';
            }
            $stmt->close();
        } else {
            error_log("Failed to prepare user fetch statement: " . $conn->error);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Pull posted fields (these will override prefilled values)
        $name = sanitize_input($_POST['name'] ?? $name);
        $email = sanitize_input($_POST['email'] ?? $email);
        $subject = sanitize_input($_POST['subject'] ?? '');
        $message_text = sanitize_input($_POST['message'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? $phone);
        
        // Insert into enquiries table
        $sql = "INSERT INTO enquiries (name, email, subject, message, phone) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssss", $name, $email, $subject, $message_text, $phone);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Your message has been sent successfully. We'll get back to you soon!";
            $_SESSION['message_type'] = 'success';
            // clear form variables after success (optional)
            $name = $email = $phone = $subject = $message_text = '';
        } else {
            throw new Exception("Failed to insert message: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Contact form error: " . $e->getMessage());
        $_SESSION['message'] = "Failed to send message. Please try again.";
        $_SESSION['message_type'] = 'danger';
    }
    
    $conn->close();
    header('Location: contact.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Contact Us - Car Rental System'); ?></title>
    <?php include 'includes/header.php'; ?>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <?php include 'includes/navigation.php'; ?>

    <main class="container section">
        <div class="section-header">
            <h1 class="section-title mb-2">
                <i class="fas fa-envelope-open text-primary me-2" aria-hidden="true"></i><?= __('Contact Us'); ?>
            </h1>
            <p class="section-subtitle"><?= __('We usually respond within 24 hours.'); ?></p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-8">
                <div class="card modern-card">
                    <div class="card-body p-4 p-md-5">
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert" aria-live="polite">
                                <?php 
                                    echo htmlspecialchars($_SESSION['message']);
                                    unset($_SESSION['message']);
                                    unset($_SESSION['message_type']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= __('Close'); ?>"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="row g-3" novalidate>
                            <div class="col-md-6">
                                <label for="name" class="form-label"><?= __('Name'); ?></label>
                                <input type="text" class="form-control" id="name" name="name" required aria-required="true" autocomplete="name" value="<?php echo htmlspecialchars($name); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label"><?= __('Email'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" required aria-required="true" autocomplete="email" value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label"><?= __('Phone'); ?></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required aria-required="true" autocomplete="tel" pattern="^\+?[0-9\s\-]{7,15}$" title="<?= __('Enter a valid phone number'); ?>" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="subject" class="form-label"><?= __('Subject'); ?></label>
                                <input type="text" class="form-control" id="subject" name="subject" required aria-required="true" value="<?php echo htmlspecialchars($subject); ?>">
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label"><?= __('Message'); ?></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required aria-required="true"><?php echo htmlspecialchars($message_text); ?></textarea>
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary btn-elevated">
                                    <i class="fas fa-paper-plane me-2" aria-hidden="true"></i><?= __('Send Message'); ?>
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt text-primary me-3" aria-hidden="true"></i>
                                    <div>
                                        <div class="fw-semibold"><?= __('Address'); ?></div>
                                        <div class="text-muted small"><?= __('Sorakhutte, Kathmandu'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-phone text-primary me-3" aria-hidden="true"></i>
                                    <div>
                                        <div class="fw-semibold"><?= __('Phone'); ?></div>
                                        <div class="text-muted small">+977 9870564820</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-envelope text-primary me-3" aria-hidden="true"></i>
                                    <div>
                                        <div class="fw-semibold"><?= __('Email'); ?></div>
                                        <div class="text-muted small">Kusum@carrentalsystem.com</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>                    
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

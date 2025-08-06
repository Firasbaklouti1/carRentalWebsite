<?php 
require_once 'includes/auth.php'; 
require_once 'includes/config.php';
require_once 'includes/functions.php';

$conn = Connect();

// Create enquiries table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $subject = sanitize_input($_POST['subject']);
        $message = sanitize_input($_POST['message']);
        
        // Insert into enquiries table
        $sql = "INSERT INTO enquiries (name, email, subject, message) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Your message has been sent successfully. We'll get back to you soon!";
            $_SESSION['message_type'] = 'success';
        } else {
            throw new Exception("Failed to insert message");
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
    <title>Contact Us - Car Rental System</title>
    <?php include 'includes/header.php'; ?>
</head>
<body class="bg-light">
    <?php include 'includes/navigation.php'; ?>

    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Contact Us</h2>
                        
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                                <?php 
                                    echo $_SESSION['message'];
                                    unset($_SESSION['message']);
                                    unset($_SESSION['message_type']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/init.php';

// Ensure admin is logged in
if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    $_SESSION['error'] = "Invalid booking ID.";
    header('Location: bookings.php');
    exit;
}

$booking_id = (int)$_GET['booking_id'];
$conn = Connect();

// Get booking details (with user and car)
$stmt = $conn->prepare("
    SELECT b.*, u.name AS user_name, u.email, c.car_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id 
    JOIN cars c ON b.car_id = c.car_id 
    WHERE b.booking_id = ?
");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    $_SESSION['error'] = "Booking not found.";
    header('Location: bookings.php');
    exit;
}

// Handle file uploads and deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_document') {
        if (!empty($_FILES['document']['name'])) {
            $upload_dir = "../uploads/booking_documents/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg','jpeg','png','pdf','doc','docx'];
            if (!in_array($file_ext, $allowed_ext)) {
                $_SESSION['error'] = "Invalid file type. Allowed: JPG, PNG, PDF, DOC, DOCX.";
                header("Location: booking_documents.php?booking_id=$booking_id");
                exit;
            }

            $new_file_name = uniqid("booking_" . $booking_id . "_") . "." . $file_ext;
            $target_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($_FILES['document']['tmp_name'], $target_path)) {
                $file_db_path = "uploads/booking_documents/" . $new_file_name;
                $doc_name = sanitize_input($_POST['document_name']);

                $stmt = $conn->prepare("INSERT INTO booking_documents (booking_id, document_name, document_path) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $booking_id, $doc_name, $file_db_path);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Document added successfully.";
                } else {
                    $_SESSION['error'] = "Failed to save document to database.";
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Failed to upload file.";
            }
        } else {
            $_SESSION['error'] = "No file selected.";
        }
        header("Location: booking_documents.php?booking_id=$booking_id");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_document' && isset($_POST['document_id'])) {
        $document_id = (int)$_POST['document_id'];

        // Get file path
        $stmt = $conn->prepare("SELECT document_path FROM booking_documents WHERE document_id = ? AND booking_id = ?");
        $stmt->bind_param('ii', $document_id, $booking_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res && file_exists("../" . $res['document_path'])) {
            unlink("../" . $res['document_path']);
        }

        $stmt = $conn->prepare("DELETE FROM booking_documents WHERE document_id = ? AND booking_id = ?");
        $stmt->bind_param('ii', $document_id, $booking_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Document deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete document.";
        }
        $stmt->close();
        header("Location: booking_documents.php?booking_id=$booking_id");
        exit;
    }
}

// Get all documents for this booking
$stmt = $conn->prepare("SELECT * FROM booking_documents WHERE booking_id = ?");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$documents = $stmt->get_result();
$stmt->close();

$page_title = "Booking Documents";
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><?= __('Booking Documents'); ?></h1>
            <a href="bookings.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?= __('Back to Bookings'); ?></a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0"><?= __('Booking Information'); ?></h6>
            </div>
            <div class="card-body">
                <p><strong><?= __('Booking ID:'); ?></strong> #<?= $booking_id; ?></p>
                <p><strong><?= __('User:'); ?></strong> <?= htmlspecialchars($booking['user_name']); ?> (<?= htmlspecialchars($booking['email']); ?>)</p>
                <p><strong><?= __('Car:'); ?></strong> <?= htmlspecialchars($booking['car_name']); ?></p>
                <p><strong><?= __('Status:'); ?></strong> <?= ucfirst($booking['status']); ?></p>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add Document Form -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0"><?= __('Add Document'); ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_document">
                    <div class="mb-3">
                        <label class="form-label"><?= __('Document Name'); ?></label>
                        <input type="text" name="document_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('Select File'); ?></label>
                        <input type="file" name="document" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= __('Upload Document'); ?></button>
                </form>
            </div>
        </div>

        <!-- Documents List -->
        <div class="card shadow">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0"><?= __('Documents List'); ?></h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th><?= __('Document Name'); ?></th>
                                <th><?= __('File'); ?></th>
                                <th class="text-end pe-4"><?= __('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($documents && $documents->num_rows > 0): ?>
                                <?php while ($doc = $documents->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $doc['document_id']; ?></td>
                                        <td><?= htmlspecialchars($doc['document_name']); ?></td>
                                        <td><a href="../<?= $doc['document_path']; ?>" target="_blank"><?= basename($doc['document_path']); ?></a></td>
                                        <td class="text-end pe-4">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete_document">
                                                <input type="hidden" name="document_id" value="<?= $doc['document_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('<?= __('Are you sure you want to delete this document?'); ?>')">
                                                    <i class="fas fa-trash-alt"></i> <?= __('Delete'); ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4"><?= __('No documents found.'); ?></td></tr>
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

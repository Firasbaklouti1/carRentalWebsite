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

$conn = Connect();

// Get current user data
$stmt = $conn->prepare("SELECT username, name, email, phone FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_action']) && $_POST['profile_action'] === 'update_profile') {
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $current_password = sanitize_input($_POST['current_password']);
    $new_password = sanitize_input($_POST['new_password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);

    if (empty($name)) {
        $error_message = "Name cannot be empty";
    } elseif (!empty($new_password)) {
        if (empty($current_password)) {
            $error_message = "Current password is required to set a new password";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long";
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();

            if (!password_verify($current_password, $user_data['password'])) {
                $error_message = "Current password is incorrect";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, password = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $name, $phone, $hashed_password, $user_id);
            }
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $name, $phone, $user_id);
    }

    if (empty($error_message) && isset($stmt) && $stmt->execute()) {
        $_SESSION['name'] = $name;
        $success_message = "Profile updated successfully";
        $user['name'] = $name;
        $user['phone'] = $phone;
    } elseif (empty($error_message)) {
        $error_message = "Failed to update profile";
    }
}

// ==== USER DOCUMENTS ACTIONS ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_action'])) {
    if ($_POST['doc_action'] === 'add_document') {
        $document_name = trim($_POST['document_name'] ?? '');

        if (empty($document_name) || !isset($_FILES['document_file'])) {
            $error_message = "All fields are required for document upload.";
        } else {
            $allowed_extensions = ['jpg','jpeg','png','pdf'];
            $file_name = $_FILES['document_file']['name'];
            $file_tmp  = $_FILES['document_file']['tmp_name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_extensions)) {
                $error_message = "Invalid file type. Only JPG, JPEG, PNG, PDF allowed.";
            } else {
                $target_dir = "uploads/user_documents/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

                $new_name = uniqid() . "." . $ext;
                $target_file = $target_dir . $new_name;
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $sql = "INSERT INTO user_documents (user_id, document_name, file_path) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $file_path_db = "uploads/user_documents/" . $new_name;
                    $stmt->bind_param('iss', $user_id, $document_name, $file_path_db);
                    if ($stmt->execute()) {
                        $success_message = "Document uploaded successfully.";
                    } else {
                        $error_message = "Failed to save document.";
                        unlink($target_file);
                    }
                    $stmt->close();
                } else {
                    $error_message = "Error moving uploaded file.";
                }
            }
        }
    }

    if ($_POST['doc_action'] === 'delete_document' && isset($_POST['document_id'])) {
        $document_id = (int)$_POST['document_id'];
        $stmt = $conn->prepare("SELECT file_path FROM user_documents WHERE document_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $document_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $file_path = ($row = $result->fetch_assoc()) ? $row['file_path'] : null;
        $stmt->close();

        if ($file_path && file_exists($file_path)) unlink($file_path);

        $stmt = $conn->prepare("DELETE FROM user_documents WHERE document_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $document_id, $user_id);
        if ($stmt->execute()) $success_message = "Document deleted successfully.";
        else $error_message = "Failed to delete document.";
        $stmt->close();
    }
}

// ==== GET USER DOCUMENTS ====
$stmt = $conn->prepare("SELECT * FROM user_documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_docs = $stmt->get_result();
$stmt->close();

$page_title = "My Profile";
include 'includes/header.php';
?>
<?php
        
        include 'includes/navigation.php';
      ?>
<div class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message; ?></div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4"><?= __('My Profile'); ?></h2>
                    <form method="POST">
                        <input type="hidden" name="profile_action" value="update_profile">
                        <div class="mb-3">
                            <label class="form-label"><?= __('Username'); ?></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= __('Email'); ?></label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= __('Full Name'); ?></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= __('Phone Number'); ?></label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']); ?>">
                        </div>
                        <hr>
                        <h4><?= __('Change Password'); ?></h4>
                        <p class="text-muted small"><?= __('Leave password fields empty if you do not want to change it'); ?></p>
                        <div class="mb-3">
                            <label class="form-label"><?= __('Current Password'); ?></label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= __('New Password'); ?></label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= __('Confirm New Password'); ?></label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary"><?= __('Update Profile'); ?></button>
                    </form>
                </div>
            </div>

            <!-- User Documents Section -->
            <div class="card shadow">
                <div class="card-header"><h5><?= __('My Documents'); ?></h5></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="mb-4">
                        <input type="hidden" name="doc_action" value="add_document">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="document_name" class="form-control" placeholder="<?= __('Document Name'); ?>" required>
                            </div>
                            <div class="col-md-5">
                                <input type="file" name="document_file" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success w-100"><?= __('Upload'); ?></button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?= __('Name'); ?></th>
                                <th><?= __('File'); ?></th>
                                <th><?= __('Uploaded'); ?></th>
                                <th><?= __('Action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($user_docs->num_rows > 0): ?>
                            <?php while ($doc = $user_docs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= ucfirst(htmlspecialchars($doc['document_name'])); ?></td>
                                    <td><a href="<?= htmlspecialchars($doc['file_path']); ?>" target="_blank"><?= __('View'); ?></a></td>
                                    <td><?= format_date($doc['uploaded_at']); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('<?= __('Delete this document?'); ?>');">
                                            <input type="hidden" name="doc_action" value="delete_document">
                                            <input type="hidden" name="document_id" value="<?= $doc['document_id']; ?>">
                                            <button class="btn btn-sm btn-danger"><?= __('Delete'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center"><?= __('No documents found'); ?></td></tr>
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

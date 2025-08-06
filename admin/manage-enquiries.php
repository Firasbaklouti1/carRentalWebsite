<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';
// Ensure user is logged in as admin
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit;
}

$conn = Connect();

// Get all enquiries, ordered by most recent first
$sql = "SELECT * FROM enquiries ORDER BY created_at DESC";
$result = $conn->query($sql);

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?= __('Manage Enquiries'); ?></h1>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?= __('Date'); ?></th>
                            <th><?= __('Name'); ?></th>
                            <th><?= __('Email'); ?></th>
                            <th><?= __('Subject'); ?></th>
                            <th><?= __('Message'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($enquiry = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M Y H:i', strtotime($enquiry['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($enquiry['name']); ?></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($enquiry['email']); ?>">
                                            <?php echo htmlspecialchars($enquiry['email']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($enquiry['subject']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($enquiry['message'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-envelope fa-2x mb-3 text-muted d-block"></i>
                                    <p class="mb-0 text-muted"><?= __('No enquiries found'); ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

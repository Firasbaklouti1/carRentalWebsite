<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';
// Ensure user is logged in as admin
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit;
}

$conn = Connect();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['enquiry_id'])) {
        $enquiry_id = $_POST['enquiry_id'];
        
        if ($_POST['action'] === 'mark_read') {
            $sql = "UPDATE enquiries SET status = 'read' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $enquiry_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Enquiry marked as read.";
            } else {
                $_SESSION['error'] = "Failed to update enquiry status.";
            }
            $stmt->close();
        }
        
        if ($_POST['action'] === 'delete') {
            $sql = "DELETE FROM enquiries WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $enquiry_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Enquiry deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete enquiry.";
            }
            $stmt->close();
        }
        
        header('Location: enquiries.php');
        exit;
    }
}

// Get all enquiries
$sql = "SELECT * FROM enquiries ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Manage Enquiries</h1>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th> <!-- Added phone header -->
                            <th>Subject</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($enquiry = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($enquiry['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($enquiry['name']); ?></td>
                                    <td><?php echo htmlspecialchars($enquiry['email']); ?></td>
                                    <td><?php echo htmlspecialchars($enquiry['phone']); ?></td> <!-- Show phone -->
                                    <td><?php echo htmlspecialchars($enquiry['subject']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($enquiry['status'] ?? 'new') === 'read' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($enquiry['status'] ?? 'New'); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info view-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal<?php echo $enquiry['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (($enquiry['status'] ?? '') !== 'read'): ?>
                                                <form action="" method="POST" class="d-inline">
                                                    <input type="hidden" name="enquiry_id" value="<?php echo $enquiry['id']; ?>">
                                                    <input type="hidden" name="action" value="mark_read">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="" method="POST" class="d-inline delete-form">
                                                <input type="hidden" name="enquiry_id" value="<?php echo $enquiry['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal<?php echo $enquiry['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>

                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade delete-modal" id="deleteModal<?php echo $enquiry['id']; ?>" 
                                                 tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title">Confirm Delete</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body p-4">
                                                            <p class="mb-0">Are you sure you want to delete this enquiry? This action cannot be undone.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <form action="" method="POST" class="d-inline">
                                                                <input type="hidden" name="enquiry_id" value="<?php echo $enquiry['id']; ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <button type="submit" class="btn btn-danger">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- View Modal -->
                                        <div class="modal fade enquiry-modal" id="viewModal<?php echo $enquiry['id']; ?>" 
                                             tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">View Enquiry Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="mb-4">
                                                            <strong class="d-block mb-2">From:</strong>
                                                            <p class="mb-0 fs-5"><?php echo htmlspecialchars($enquiry['name']); ?></p>
                                                            <p class="mb-0 text-muted"><?php echo htmlspecialchars($enquiry['email']); ?></p>
                                                            <p class="mb-0 text-muted"><strong>Phone:</strong> <?php echo htmlspecialchars($enquiry['phone']); ?></p> <!-- Show phone -->
                                                        </div>
                                                        <div class="mb-4">
                                                            <strong class="d-block mb-2">Subject:</strong>
                                                            <p class="mb-0 fs-5"><?php echo htmlspecialchars($enquiry['subject']); ?></p>
                                                        </div>
                                                        <div class="mb-4">
                                                            <strong class="d-block mb-2">Message:</strong>
                                                            <div class="p-3 bg-light rounded">
                                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($enquiry['message'])); ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="mb-0">
                                                            <strong class="d-block mb-2">Sent on:</strong>
                                                            <p class="mb-0 text-muted"><?php echo date('F j, Y g:i A', strtotime($enquiry['created_at'])); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <?php if (($enquiry['status'] ?? '') !== 'read'): ?>
                                                            <form action="" method="POST">
                                                                <input type="hidden" name="enquiry_id" value="<?php echo $enquiry['id']; ?>">
                                                                <input type="hidden" name="action" value="mark_read">
                                                                <button type="submit" class="btn btn-success">
                                                                    <i class="fas fa-check me-1"></i>Mark as Read
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x mb-3 text-muted d-block"></i>
                                    <p class="mb-0 text-muted">No enquiries found</p>
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

<style>
/* Fix modal flickering */
.modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
}

.modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    outline: 0 !important;
}

.modal-dialog {
    position: relative !important;
    pointer-events: none !important;
    transform: none !important;
}

.modal-dialog.modal-lg {
    max-width: 800px !important;
}

.modal-dialog-centered {
    display: flex !important;
    align-items: center !important;
    min-height: calc(100% - 3.5rem) !important;
}

.modal-content {
    position: relative !important;
    pointer-events: auto !important;
    border: none !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Fix button group */
.btn-group {
    display: inline-flex !important;
    gap: 2px !important;
    position: relative !important;
    vertical-align: middle !important;
}

.btn-group .btn {
    position: relative !important;
    flex: 0 1 auto !important;
    border-radius: 0.25rem !important;
}

/* Prevent body shift */
body.modal-open {
    overflow: hidden !important;
    padding-right: 0 !important;
}

/* Fix button hover states */
.btn:hover {
    z-index: 2 !important;
}

/* Additional modal fixes */
.modal.fade .modal-dialog {
    transition: none !important;
}

.modal.show .modal-dialog {
    transform: none !important;
}

.delete-modal .modal-dialog {
    max-width: 400px !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fix modal stacking issues
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            // Remove any existing backdrop
            const existingBackdrop = document.querySelector('.modal-backdrop');
            if (existingBackdrop) {
                existingBackdrop.remove();
            }
            
            // Ensure proper z-index
            const zIndex = 1050 + (document.querySelectorAll('.modal.show').length * 10);
            this.style.zIndex = zIndex;
            
            // Create new backdrop with proper z-index
            setTimeout(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.style.zIndex = zIndex - 5;
                }
            }, 0);
        });

        // Prevent modal from closing when clicking inside content
        modal.querySelector('.modal-content')?.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // Clean up modals on close
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        });
    });
});
</script>

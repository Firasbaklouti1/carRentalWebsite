<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Ensure user is logged in as admin
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit;
}

$conn = Connect();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_type') {
            $type_name = trim($_POST['type_name']);
            
            // Check if type already exists
            $check_sql = "SELECT type_id FROM car_types WHERE type_name = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param('s', $type_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['error'] = "Car type already exists.";
            } else {
                // Add new type
                $sql = "INSERT INTO car_types (type_name) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $type_name);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Car type added successfully.";
                } else {
                    $_SESSION['error'] = "Failed to add car type.";
                }
            }
            $stmt->close();
            header('Location: car_types.php');
            exit;
        }
        
        if ($_POST['action'] === 'edit_type' && isset($_POST['type_id'])) {
            $type_id = $_POST['type_id'];
            $type_name = trim($_POST['type_name']);
            
            // Check if type already exists
            $check_sql = "SELECT type_id FROM car_types WHERE type_name = ? AND type_id != ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param('si', $type_name, $type_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['error'] = "Car type already exists.";
            } else {
                // Update type
                $sql = "UPDATE car_types SET type_name = ? WHERE type_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $type_name, $type_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Car type updated successfully.";
                } else {
                    $_SESSION['error'] = "Failed to update car type.";
                }
            }
            $stmt->close();
            header('Location: car_types.php');
            exit;
        }
        
        if ($_POST['action'] === 'delete_type' && isset($_POST['type_id'])) {
            $type_id = $_POST['type_id'];
            
            // Check if type is being used
            $check_sql = "SELECT car_id FROM cars WHERE car_type = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param('i', $type_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['error'] = "Cannot delete car type as it is being used by one or more cars.";
            } else {
                // Delete type
                $sql = "DELETE FROM car_types WHERE type_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $type_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Car type deleted successfully.";
                } else {
                    $_SESSION['error'] = "Failed to delete car type.";
                }
            }
            $stmt->close();
            header('Location: car_types.php');
            exit;
        }
    }
}

// Get all car types
$sql = "SELECT * FROM car_types ORDER BY type_name";
$result = $conn->query($sql);
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Manage Car Types</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
            <i class="fas fa-plus me-2"></i>Add Car Type
        </button>
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
                            <th>Type Name</th>
                            <th>Cars Using This Type</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($type = $result->fetch_assoc()): ?>
                                <?php
                                    // Get count of cars using this type
                                    $count_sql = "SELECT COUNT(*) as count FROM cars WHERE car_type = ?";
                                    $stmt = $conn->prepare($count_sql);
                                    $stmt->bind_param('i', $type['type_id']);
                                    $stmt->execute();
                                    $count_result = $stmt->get_result();
                                    $count = $count_result->fetch_assoc()['count'];
                                    $stmt->close();
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($type['type_name']); ?></td>
                                    <td><?php echo $count; ?> cars</td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editTypeModal<?php echo $type['type_id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="type_id" value="<?php echo $type['type_id']; ?>">
                                                <input type="hidden" name="action" value="delete_type">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this car type?');"
                                                        <?php echo $count > 0 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Edit Type Modal -->
                                        <div class="modal fade" id="editTypeModal<?php echo $type['type_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Car Type</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="action" value="edit_type">
                                                        <input type="hidden" name="type_id" value="<?php echo $type['type_id']; ?>">
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Type Name</label>
                                                                <input type="text" name="type_name" class="form-control" 
                                                                       value="<?php echo htmlspecialchars($type['type_name']); ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="fas fa-list fa-2x mb-3 text-muted d-block"></i>
                                    <p class="mb-0 text-muted">No car types found. Add your first car type to get started!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Type Modal -->
<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Car Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add_type">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="type_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

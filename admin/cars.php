<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';
// Ensure user is logged in as admin
if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// Get database connection
$conn = Connect();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_car') {
            // Handle file upload
            $target_dir = "../assets/img/cars/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $car_image = "";
            
            if (isset($_FILES["car_image"]) && $_FILES["car_image"]["error"] === 0) {
                $file_extension = strtolower(pathinfo($_FILES["car_image"]["name"], PATHINFO_EXTENSION));
                $allowed_extensions = array("jpg", "jpeg", "png", "webp");
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_file_name = uniqid() . "." . $file_extension;
                    $car_image = "assets/img/cars/" . $new_file_name;
                    $target_file = $target_dir . $new_file_name;
                    
                    if (!move_uploaded_file($_FILES["car_image"]["tmp_name"], $target_file)) {
                        $_SESSION['error'] = "Failed to upload image. Please try again.";
                        header('Location: cars.php');
                        exit;
                    }
                } else {
                    $_SESSION['error'] = "Invalid file type. Please upload a JPG, JPEG, PNG, or WEBP file.";
                    header('Location: cars.php');
                    exit;
                }
            } else {
                $_SESSION['error'] = "Please select an image file.";
                header('Location: cars.php');
                exit;
            }
            
            // Add new car
            $sql = "INSERT INTO cars (car_name, car_type, fuel_type, car_image, price, car_availability) VALUES (?, ?, ?, ?, ?, 'yes')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssd', 
                $_POST['car_name'],
                $_POST['car_type'],
                $_POST['fuel_type'],
                $car_image,
                $_POST['car_price']
            );
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Car added successfully.";
            } else {
                $_SESSION['error'] = "Failed to add car.";
            }
            
            $stmt->close();
            header('Location: cars.php');
            exit;
        }
        
        if (isset($_POST['car_id'])) {
            $car_id = $_POST['car_id'];
            
            if ($_POST['action'] === 'toggle_availability') {
                // Toggle car availability
                $sql = "UPDATE cars SET car_availability = IF(car_availability = 'yes', 'no', 'yes') WHERE car_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $car_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Car availability updated successfully.";
                } else {
                    $_SESSION['error'] = "Failed to update car availability.";
                }
                
                $stmt->close();
                header('Location: cars.php');
                exit;
            }
            
            if ($_POST['action'] === 'delete') {
                // Delete car image first
                $sql = "SELECT car_image FROM cars WHERE car_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $car_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if ($row['car_image']) {
                        $image_path = "../" . $row['car_image'];
                        if (file_exists($image_path)) {
                            @unlink($image_path);
                        }
                    }
                }
                $stmt->close();
                
                // Delete any existing bookings first
                $delete_bookings = "DELETE FROM bookings WHERE car_id = ?";
                $stmt = $conn->prepare($delete_bookings);
                $stmt->bind_param('i', $car_id);
                $stmt->execute();
                $stmt->close();
                
                // Delete car
                $sql = "DELETE FROM cars WHERE car_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $car_id);
                
                try {
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Car deleted successfully.";
                    } else {
                        $_SESSION['error'] = "Failed to delete car.";
                    }
                } catch (mysqli_sql_exception $e) {
                    $_SESSION['error'] = "Cannot delete car due to existing bookings. Please handle the bookings first.";
                }
                
                $stmt->close();
                header('Location: cars.php');
                exit;
            }
            
            if ($_POST['action'] === 'edit_car') {
                $car_id = $_POST['car_id'];
                $car_name = $_POST['car_name'];
                $car_type = $_POST['car_type'];
                $fuel_type = $_POST['fuel_type'];
                $price = $_POST['car_price'];

                // Start with basic car details
                $sql = "UPDATE cars SET car_name = ?, car_type = ?, fuel_type = ?, price = ? WHERE car_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssdi', $car_name, $car_type, $fuel_type, $price, $car_id);
                
                if ($stmt->execute()) {
                    // Handle image upload only if a new image is provided
                    if (!empty($_FILES['car_image']['name'])) {
                        $target_dir = "../assets/img/cars/";
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }

                        $file_extension = strtolower(pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION));
                        $new_filename = uniqid() . '.' . $file_extension;
                        $target_file = $target_dir . $new_filename;
                        
                        // Get old image path
                        $old_image_sql = "SELECT car_image FROM cars WHERE car_id = ?";
                        $stmt = $conn->prepare($old_image_sql);
                        $stmt->bind_param('i', $car_id);
                        $stmt->execute();
                        $old_image_result = $stmt->get_result();
                        $old_image = $old_image_result->fetch_assoc()['car_image'];

                        // Check file type
                        if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                            $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                        } else {
                            // Upload new image
                            if (move_uploaded_file($_FILES['car_image']['tmp_name'], $target_file)) {
                                // Delete old image if exists
                                if ($old_image && file_exists("../" . $old_image)) {
                                    unlink("../" . $old_image);
                                }
                                
                                $image_path = "assets/img/cars/" . $new_filename;
                                $sql = "UPDATE cars SET car_image = ? WHERE car_id = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param('si', $image_path, $car_id);
                                
                                if ($stmt->execute()) {
                                    $_SESSION['success'] = "Car updated successfully with new image.";
                                } else {
                                    $_SESSION['error'] = "Car details updated but failed to update image.";
                                }
                            } else {
                                $_SESSION['error'] = "Car details updated but failed to upload new image.";
                            }
                        }
                    } else {
                        $_SESSION['success'] = "Car updated successfully.";
                    }
                } else {
                    $_SESSION['error'] = "Failed to update car.";
                }
                
                header('Location: cars.php');
                exit;
            }
        }
    }
}

// Get all cars
$sql = "SELECT * FROM cars ORDER BY car_name";
$result = $conn->query($sql);

if (!$result) {
    echo "Error: " . $conn->error;
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?= __('Manage Cars'); ?></h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCarModal">
            <i class="fas fa-plus me-2"></i><?= __('Add New Car'); ?>
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

    <div class="row">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($car = $result->fetch_assoc()): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-img-top position-relative" style="height: 200px; background: #f8f9fa;">
                            <?php if ($car['car_image']): ?>
                                <img src="../<?php echo htmlspecialchars($car['car_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($car['car_name']); ?>"
                                     class="w-100 h-100"
                                     style="object-fit: contain; object-position: center;">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <i class="fas fa-car fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            <div class="position-absolute top-0 end-0 p-2">
                                <span class="badge bg-<?php echo $car['car_availability'] === 'yes' ? 'success' : 'danger'; ?>">
                                    <?php echo $car['car_availability'] === 'yes' ? 'Available' : 'Not Available'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($car['car_name']); ?></h5>
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($car['car_type'] ?? ''); ?></p>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <div class="d-flex align-items-center text-muted">
                                        <i class="fas fa-tag me-2"></i>
                                        <span>Rs. <?php echo number_format($car['price'] ?? 0, 2); ?>/day</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center text-muted">
                                        <i class="fas fa-gas-pump me-2"></i>
                                        <span><?php echo htmlspecialchars($car['fuel_type'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <p class="card-text text-muted"><?php echo substr(htmlspecialchars($car['car_description'] ?? ''), 0, 100); ?>...</p>
                            <div class="card-footer bg-white">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCarModal<?php echo $car['car_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="car_id" value="<?php echo $car['car_id']; ?>">
                                        <input type="hidden" name="action" value="toggle_availability">
                                        <button type="submit" class="btn btn-sm <?php echo $car['car_availability'] === 'yes' ? 'btn-outline-success' : 'btn-outline-warning'; ?>">
                                            <i class="fas <?php echo $car['car_availability'] === 'yes' ? 'fa-check' : 'fa-clock'; ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="car_id" value="<?php echo $car['car_id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this car? This action cannot be undone.');">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Car Modal -->
                <div class="modal fade" id="editCarModal<?php echo $car['car_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= __('Edit Car'); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="cars.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="edit_car">
                                <input type="hidden" name="car_id" value="<?php echo $car['car_id']; ?>">
                                
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Car Name'); ?></label>
                                                <input type="text" name="car_name" class="form-control" value="<?php echo htmlspecialchars($car['car_name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Car Type'); ?></label>
                                                <input type="text" name="car_type" class="form-control" 
                                                       value="<?php echo htmlspecialchars($car['car_type'] ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Fuel Type'); ?></label>
                                                <select name="fuel_type" class="form-select" required>
                                                    <option value=""><?= __('Select Fuel Type'); ?></option>
                                                    <option value="Petrol" <?php echo ($car['fuel_type'] ?? '') === 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                                                    <option value="Diesel" <?php echo ($car['fuel_type'] ?? '') === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                                    <option value="Hybrid" <?php echo ($car['fuel_type'] ?? '') === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                                                    <option value="Electric" <?php echo ($car['fuel_type'] ?? '') === 'Electric' ? 'selected' : ''; ?>>Electric</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Price per Day'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><?= __('Rs.'); ?></span>
                                                    <input type="number" name="car_price" class="form-control" value="<?php echo $car['price']; ?>" required min="0" step="0.01">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Current Image'); ?></label>
                                                <div class="border rounded p-2 text-center" style="height: 200px; background: #f8f9fa;">
                                                    <?php if ($car['car_image']): ?>
                                                        <img src="../<?php echo htmlspecialchars($car['car_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($car['car_name']); ?>"
                                                             class="h-100"
                                                             style="object-fit: contain; object-position: center;">
                                                    <?php else: ?>
                                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                                            <i class="fas fa-car fa-3x"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Update Image'); ?></label>
                                                <input type="file" name="car_image" class="form-control" accept="image/*">
                                                <div class="form-text"><?= __('Leave empty to keep current image'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('Cancel'); ?></button>
                                    <button type="submit" class="btn btn-primary"><?= __('Save Changes'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i><?= __('No cars found. Add your first car to get started!'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Car Modal -->
<div class="modal fade" id="addCarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('Add New Car'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="cars.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_car">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= __('Car Name'); ?></label>
                            <input type="text" name="car_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('Car Type'); ?></label>
                            <input type="text" name="car_type" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('Fuel Type'); ?></label>
                            <select name="fuel_type" class="form-select" required>
                                <option value=""><?= __('Select Fuel Type'); ?></option>
                                <option value="Petrol"><?= __('Petrol'); ?></option>
                                <option value="Diesel"><?= __('Diesel'); ?></option>
                                <option value="Hybrid"><?= __('Hybrid'); ?></option>
                                <option value="Electric"><?= __('Electric'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('Price per Day'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><?= __('Rs.'); ?></span>
                                <input type="number" name="car_price" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= __('Car Image'); ?></label>
                            <input type="file" name="car_image" class="form-control" accept="image/*" required>
                            <small class="text-muted"><?= __('Upload a clear image of the car. Maximum size: 5MB'); ?></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= __('Add Car'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>

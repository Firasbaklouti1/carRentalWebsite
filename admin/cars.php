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

// Function to check for upcoming deadlines
function checkUpcomingDeadlines($conn) {
    $notifications = [];
    $today = date('Y-m-d');
    $warning_days = 30; // Notify 30 days before deadline
    $critical_days = 7; // Critical warning 7 days before
    $warning_date = date('Y-m-d', strtotime("+$warning_days days"));
    $critical_date = date('Y-m-d', strtotime("+$critical_days days"));
    
    $sql = "SELECT c.car_name, cd.* FROM car_documents cd 
            JOIN cars c ON cd.car_id = c.car_id 
            WHERE (cd.vignette_date BETWEEN ? AND ?) 
               OR (cd.technical_check_date BETWEEN ? AND ?) 
               OR (cd.insurance_date BETWEEN ? AND ?) 
               OR (cd.oil_change_date BETWEEN ? AND ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssss', $today, $warning_date, $today, $warning_date, 
                      $today, $warning_date, $today, $warning_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $car_name = $row['car_name'];
        
        // Check each document type with priority levels
        $documents = [
            'vignette_date' => 'Vignette',
            'technical_check_date' => 'Technical Check',
            'insurance_date' => 'Insurance',
            'oil_change_date' => 'Oil Change'
        ];
        
        foreach ($documents as $date_field => $doc_name) {
            if ($row[$date_field] && $row[$date_field] <= $warning_date) {
                $days_left = (strtotime($row[$date_field]) - strtotime($today)) / (60 * 60 * 24);
                $priority = $days_left <= 7 ? 'critical' : ($days_left <= 14 ? 'warning' : 'info');
                
                $notifications[] = [
                    'message' => "$doc_name for $car_name expires on {$row[$date_field]} ($days_left days left)",
                    'priority' => $priority,
                    'days_left' => $days_left
                ];
            }
        }
    }
    
    // Sort by priority and days left
    usort($notifications, function($a, $b) {
        $priority_order = ['critical' => 0, 'warning' => 1, 'info' => 2];
        if ($priority_order[$a['priority']] != $priority_order[$b['priority']]) {
            return $priority_order[$a['priority']] - $priority_order[$b['priority']];
        }
        return $a['days_left'] - $b['days_left'];
    });
    
    return $notifications;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_car') {
            // Handle multiple file uploads
            $target_dir = "../assets/img/cars/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $main_image = "";
            $uploaded_images = [];
            
            // Handle main image (required)
            if (isset($_FILES["car_image"]) && $_FILES["car_image"]["error"] === 0) {
                $file_extension = strtolower(pathinfo($_FILES["car_image"]["name"], PATHINFO_EXTENSION));
                $allowed_extensions = array("jpg", "jpeg", "png", "webp");
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_file_name = uniqid() . "." . $file_extension;
                    $main_image = "assets/img/cars/" . $new_file_name;
                    $target_file = $target_dir . $new_file_name;
                    
                    if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $target_file)) {
                        $uploaded_images[] = $main_image;
                    }
                }
            }
            
            // Handle additional images
            if (isset($_FILES["additional_images"])) {
                for ($i = 0; $i < count($_FILES["additional_images"]["name"]); $i++) {
                    if ($_FILES["additional_images"]["error"][$i] === 0) {
                        $file_extension = strtolower(pathinfo($_FILES["additional_images"]["name"][$i], PATHINFO_EXTENSION));
                        if (in_array($file_extension, array("jpg", "jpeg", "png", "webp"))) {
                            $new_file_name = uniqid() . "." . $file_extension;
                            $image_path = "assets/img/cars/" . $new_file_name;
                            $target_file = $target_dir . $new_file_name;
                            
                            if (move_uploaded_file($_FILES["additional_images"]["tmp_name"][$i], $target_file)) {
                                $uploaded_images[] = $image_path;
                            }
                        }
                    }
                }
            }
            
            if (empty($uploaded_images)) {
                $_SESSION['error'] = "Please upload at least one image.";
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
                $uploaded_images[0], // Use first image as main image
                $_POST['car_price']
            );
            
            if ($stmt->execute()) {
                $car_id = $conn->insert_id;
                
                // Insert all images into car_images table
                foreach ($uploaded_images as $image_path) {
                    $img_sql = "INSERT INTO car_images (car_id, image_path) VALUES (?, ?)";
                    $img_stmt = $conn->prepare($img_sql);
                    $img_stmt->bind_param('is', $car_id, $image_path);
                    $img_stmt->execute();
                    $img_stmt->close();
                }
                
                // Insert document tracking record
                $doc_sql = "INSERT INTO car_documents (car_id) VALUES (?)";
                $doc_stmt = $conn->prepare($doc_sql);
                $doc_stmt->bind_param('i', $car_id);
                $doc_stmt->execute();
                $doc_stmt->close();
                
                $_SESSION['success'] = "Car added successfully.";
            } else {
                $_SESSION['error'] = "Failed to add car.";
            }
            
            $stmt->close();
            header('Location: cars.php');
            exit;
        }
        
        if ($_POST['action'] === 'update_documents') {
            $car_id = $_POST['car_id'];
        
            // Assignation aux variables (éviter les expressions dans bind_param)
            $vignette_date = $_POST['vignette_date'] ?: null;
            $technical_check_date = $_POST['technical_check_date'] ?: null;
            $insurance_date = $_POST['insurance_date'] ?: null;
            $oil_change_date = $_POST['oil_change_date'] ?: null;
        
            $sql = "UPDATE car_documents SET vignette_date = ?, technical_check_date = ?, insurance_date = ?, oil_change_date = ? WHERE car_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssi', 
                $vignette_date,
                $technical_check_date,
                $insurance_date,
                $oil_change_date,
                $car_id
            );
        
            if ($stmt->execute()) {
                $_SESSION['success'] = "Document dates updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to update document dates.";
            }
        
            $stmt->close();
            header('Location: cars.php');
            exit;
        }
        
        
        if ($_POST['action'] === 'add_maintenance') {
            $car_id = $_POST['car_id'];
            $maintenance_date = $_POST['maintenance_date'];
            $description = $_POST['description'];
            $cost = $_POST['cost'];
            
            $invoice_path = null;
            if (isset($_FILES['invoice']) && $_FILES['invoice']['error'] === 0) {
                $target_dir = "../assets/invoices/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['invoice']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = array("jpg", "jpeg", "png", "pdf", "doc", "docx");
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_file_name = "invoice_" . $car_id . "_" . time() . "." . $file_extension;
                    $invoice_path = "assets/invoices/" . $new_file_name;
                    $target_file = $target_dir . $new_file_name;
                    move_uploaded_file($_FILES['invoice']['tmp_name'], $target_file);
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO car_maintenance (car_id, maintenance_date, description, cost, invoice_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('issds', $car_id, $maintenance_date, $description, $cost, $invoice_path);
            $stmt->execute();
            
            header('Location: cars.php?success=maintenance_added');
            exit;
        }
        
        if ($_POST['action'] === 'remove_image') {
            header('Content-Type: application/json');
            
            $image_id = $_POST['image_id'];
            $car_id = $_POST['car_id'];
            
            // Get image path before deletion
            $stmt = $conn->prepare("SELECT image_path FROM car_images WHERE image_id = ? AND car_id = ?");
            $stmt->bind_param('ii', $image_id, $car_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $image_path = $row['image_path'];
                
                // Delete from database first
                $delete_stmt = $conn->prepare("DELETE FROM car_images WHERE image_id = ? AND car_id = ?");
                $delete_stmt->bind_param('ii', $image_id, $car_id);
                
                if ($delete_stmt->execute()) {
                    // Delete physical file - try multiple path constructions
                    $file_paths_to_try = [
                        "../" . $image_path,                    // "../assets/img/cars/filename.jpg"
                        $image_path,                            // "assets/img/cars/filename.jpg" 
                        "../" . str_replace("assets/", "", $image_path), // "../img/cars/filename.jpg"
                        __DIR__ . "/../" . $image_path          // Full absolute path
                    ];
                    
                    $file_deleted = false;
                    $attempted_path = "";
                    
                    foreach ($file_paths_to_try as $file_path) {
                        $attempted_path = $file_path;
                        if (file_exists($file_path)) {
                            if (unlink($file_path)) {
                                $file_deleted = true;
                                break;
                            }
                        }
                    }
                    
                    // Return success even if file deletion fails (database is cleaned)
                    echo json_encode([
                        'success' => true, 
                        'file_deleted' => $file_deleted,
                        'attempted_path' => $attempted_path,
                        'image_path' => $image_path
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Database error']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Image not found']);
            }
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
                // Delete main car image first
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
                
                // Delete all additional images from car_images table
                $images_sql = "SELECT image_path FROM car_images WHERE car_id = ?";
                $images_stmt = $conn->prepare($images_sql);
                $images_stmt->bind_param('i', $car_id);
                $images_stmt->execute();
                $images_result = $images_stmt->get_result();
                
                while ($image_row = $images_result->fetch_assoc()) {
                    $image_file_path = "../" . $image_row['image_path'];
                    if (file_exists($image_file_path)) {
                        @unlink($image_file_path);
                    }
                }
                $images_stmt->close();
                
                // Delete all maintenance invoices from car_maintenance table
                $maintenance_sql = "SELECT invoice_path FROM car_maintenance WHERE car_id = ? AND invoice_path IS NOT NULL";
                $maintenance_stmt = $conn->prepare($maintenance_sql);
                $maintenance_stmt->bind_param('i', $car_id);
                $maintenance_stmt->execute();
                $maintenance_result = $maintenance_stmt->get_result();
                
                while ($maintenance_row = $maintenance_result->fetch_assoc()) {
                    $invoice_file_path = "../" . $maintenance_row['invoice_path'];
                    if (file_exists($invoice_file_path)) {
                        @unlink($invoice_file_path);
                    }
                }
                $maintenance_stmt->close();
                
                // Delete database records in correct order (foreign key constraints)
                // 1. Delete car images
                $delete_images = "DELETE FROM car_images WHERE car_id = ?";
                $stmt = $conn->prepare($delete_images);
                $stmt->bind_param('i', $car_id);
                $stmt->execute();
                $stmt->close();
                
                // 2. Delete car maintenance records
                $delete_maintenance = "DELETE FROM car_maintenance WHERE car_id = ?";
                $stmt = $conn->prepare($delete_maintenance);
                $stmt->bind_param('i', $car_id);
                $stmt->execute();
                $stmt->close();
                
                // 3. Delete car documents
                $delete_documents = "DELETE FROM car_documents WHERE car_id = ?";
                $stmt = $conn->prepare($delete_documents);
                $stmt->bind_param('i', $car_id);
                $stmt->execute();
                $stmt->close();
                
                // 4. Delete any existing bookings
                $delete_bookings = "DELETE FROM bookings WHERE car_id = ?";
                $stmt = $conn->prepare($delete_bookings);
                $stmt->bind_param('i', $car_id);
                $stmt->execute();
                $stmt->close();

                // 5. Finally delete the car itself
                $sql = "DELETE FROM cars WHERE car_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $car_id);
                
                try {
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Car and all related files deleted successfully.";
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
                    }
                    
                    // Handle additional images upload
                    if (isset($_FILES["additional_images"])) {
                        $target_dir = "../assets/img/cars/";
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        
                        for ($i = 0; $i < count($_FILES["additional_images"]["name"]); $i++) {
                            if ($_FILES["additional_images"]["error"][$i] === 0) {
                                $file_extension = strtolower(pathinfo($_FILES["additional_images"]["name"][$i], PATHINFO_EXTENSION));
                                if (in_array($file_extension, array("jpg", "jpeg", "png", "webp"))) {
                                    $new_file_name = uniqid() . "." . $file_extension;
                                    $image_path = "assets/img/cars/" . $new_file_name;
                                    $target_file = $target_dir . $new_file_name;
                                    
                                    if (move_uploaded_file($_FILES["additional_images"]["tmp_name"][$i], $target_file)) {
                                        // Insert into car_images table
                                        $image_stmt = $conn->prepare("INSERT INTO car_images (car_id, image_path) VALUES (?, ?)");
                                        $image_stmt->bind_param('is', $car_id, $image_path);
                                        $image_stmt->execute();
                                    }
                                }
                            }
                        }
                    }
                    
                    $_SESSION['success'] = "Car updated successfully.";
                } else {
                    $_SESSION['error'] = "Failed to update car.";
                }
                
                header('Location: cars.php');
                exit;
            }
        }
    }
}

// Get all cars with document information
$sql = "SELECT c.*, cd.vignette_date, cd.technical_check_date, cd.insurance_date, cd.oil_change_date 
        FROM cars c 
        LEFT JOIN car_documents cd ON c.car_id = cd.car_id 
        ORDER BY c.car_name";
$result = $conn->query($sql);

// Get notifications
$notifications = checkUpcomingDeadlines($conn);

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Notifications -->
    <?php if (!empty($notifications)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong><?= __('Upcoming Deadlines:'); ?></strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($notifications as $notification): ?>
                    <li class="mb-1">
                        <span class="badge <?= $notification['priority'] === 'critical' ? 'bg-danger' : ($notification['priority'] === 'warning' ? 'bg-warning text-dark' : 'bg-info'); ?>">
                            <?= $notification['priority'] === 'critical' ? '🚨' : ($notification['priority'] === 'warning' ? '⚠️' : 'ℹ️'); ?>
                            <?= htmlspecialchars($notification['message']); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?= __('Manage Cars'); ?></h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCarModal">
            <i class="fas fa-plus me-2"></i><?= __('Add New Car'); ?>
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($car = $result->fetch_assoc()): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-img-top position-relative" style="height: 200px; background: #f8f9fa;">
                            <?php if ($car['car_image']): ?>
                                <img src="../<?= $car['car_image']; ?>" alt="<?= $car['car_name']; ?>" 
                                     class="img-fluid w-100 h-100" style="object-fit: cover;">
                                
                                <!-- Image Gallery Button -->
                                <?php
                                $img_sql = "SELECT COUNT(*) as img_count FROM car_images WHERE car_id = ?";
                                $img_stmt = $conn->prepare($img_sql);
                                $img_stmt->bind_param('i', $car['car_id']);
                                $img_stmt->execute();
                                $img_result = $img_stmt->get_result();
                                $img_count = $img_result->fetch_assoc()['img_count'];
                                ?>
                                
                                <?php if ($img_count > 1): ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#imageGalleryModal<?= $car['car_id']; ?>">
                                            <i class="fas fa-images"></i> <?= $img_count; ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100">
                                    <i class="fas fa-car fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status badges -->
                            <div class="position-absolute top-0 start-0 m-2">
                                <?php if ($car['car_availability'] === 'yes'): ?>
                                    <span class="badge bg-success"><?= __('Available'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?= __('Not Available'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Document status indicators -->
                            <div class="position-absolute bottom-0 start-0 m-2">
                                <?php
                                $today = date('Y-m-d');
                                $warning_date = date('Y-m-d', strtotime('+30 days'));
                                $has_warnings = false;
                                
                                if ($car['vignette_date'] && $car['vignette_date'] <= $warning_date) $has_warnings = true;
                                if ($car['technical_check_date'] && $car['technical_check_date'] <= $warning_date) $has_warnings = true;
                                if ($car['insurance_date'] && $car['insurance_date'] <= $warning_date) $has_warnings = true;
                                if ($car['oil_change_date'] && $car['oil_change_date'] <= $warning_date) $has_warnings = true;
                                ?>
                                
                                <?php if ($has_warnings): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-exclamation-triangle"></i> <?= __('Deadline Soon'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($car['car_name']); ?></h5>
                            <p class="card-text">
                                <small class="text-muted"><?= htmlspecialchars($car['car_type']); ?> • <?= htmlspecialchars($car['fuel_type']); ?></small><br>
                                <strong class="text-primary"><?= __('Rs.'); ?> <?= number_format($car['price'], 0,0); ?>/<?= __('day'); ?></strong>
                            </p>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCarModal<?= $car['car_id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#documentTrackingModal<?= $car['car_id']; ?>">
                                    <i class="fas fa-file-alt"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#maintenanceHistoryModal<?= $car['car_id']; ?>">
                                    <i class="fas fa-wrench"></i>
                                </button>
                                <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal<?= $car['car_id']; ?>">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="btn-group w-100 mt-2" role="group">
                                <form action="cars.php" method="POST" class="flex-fill">
                                    <input type="hidden" name="action" value="toggle_availability">
                                    <input type="hidden" name="car_id" value="<?= $car['car_id']; ?>">
                                    <button type="submit" class="btn btn-outline-<?= $car['car_availability'] === 'yes' ? 'warning' : 'success'; ?> btn-sm w-100">
                                        <?= $car['car_availability'] === 'yes' ? __('Mark Unavailable') : __('Mark Available'); ?>
                                    </button>
                                </form>
                                <form action="cars.php" method="POST" class="flex-fill ms-1" onsubmit="return confirm('<?= __('Are you sure you want to delete this car?'); ?>')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="car_id" value="<?= $car['car_id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Gallery Modal -->
                <div class="modal fade" id="imageGalleryModal<?= $car['car_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= __('Car Images'); ?> - <?= htmlspecialchars($car['car_name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="carouselImages<?= $car['car_id']; ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php
                                        $gallery_sql = "SELECT * FROM car_images WHERE car_id = ? ORDER BY image_id";
                                        $gallery_stmt = $conn->prepare($gallery_sql);
                                        $gallery_stmt->bind_param('i', $car['car_id']);
                                        $gallery_stmt->execute();
                                        $gallery_result = $gallery_stmt->get_result();
                                        $first = true;
                                        
                                        while ($image = $gallery_result->fetch_assoc()):
                                        ?>
                                        <div class="carousel-item <?= $first ? 'active' : ''; ?>">
                                            <img src="../<?= $image['image_path']; ?>" class="d-block w-100" style="height: 400px; object-fit: cover;">
                                        </div>
                                        <?php $first = false; endwhile; ?>
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselImages<?= $car['car_id']; ?>" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon"></span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carouselImages<?= $car['car_id']; ?>" data-bs-slide="next">
                                        <span class="carousel-control-next-icon"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Car Modal -->
                <div class="modal fade" id="editCarModal<?= $car['car_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= __('Edit Car'); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="cars.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="edit_car">
                                <input type="hidden" name="car_id" value="<?= $car['car_id']; ?>">
                                
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Car Name'); ?></label>
                                                <input type="text" name="car_name" class="form-control" value="<?= htmlspecialchars($car['car_name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Car Type'); ?></label>
                                                <input type="text" name="car_type" class="form-control" 
                                                       value="<?= htmlspecialchars($car['car_type'] ?? ''); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Fuel Type'); ?></label>
                                                <select name="fuel_type" class="form-select" required>
                                                    <option value=""><?= __('Select Fuel Type'); ?></option>
                                                    <option value="Petrol" <?= ($car['fuel_type'] ?? '') === 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                                                    <option value="Diesel" <?= ($car['fuel_type'] ?? '') === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                                    <option value="Hybrid" <?= ($car['fuel_type'] ?? '') === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                                                    <option value="Electric" <?= ($car['fuel_type'] ?? '') === 'Electric' ? 'selected' : ''; ?>>Electric</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Price per Day'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><?= __('Rs.'); ?></span>
                                                    <input type="number" name="car_price" class="form-control" value="<?= $car['price']; ?>" required min="0" step="0.01">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Current Images'); ?></label>
                                                <div class="row" id="currentImages<?= $car['car_id']; ?>">
                                                    <?php
                                                    $current_images_sql = "SELECT * FROM car_images WHERE car_id = ? ORDER BY image_id";
                                                    $current_images_stmt = $conn->prepare($current_images_sql);
                                                    $current_images_stmt->bind_param('i', $car['car_id']);
                                                    $current_images_stmt->execute();
                                                    $current_images_result = $current_images_stmt->get_result();
                                                    
                                                    if ($current_images_result->num_rows > 0):
                                                        while ($img = $current_images_result->fetch_assoc()):
                                                    ?>
                                                    <div class="col-4 mb-2" id="image-<?= $img['image_id']; ?>">
                                                        <div class="position-relative">
                                                            <img src="../<?= htmlspecialchars($img['image_path']); ?>" 
                                                                 class="img-thumbnail w-100" style="height: 80px; object-fit: cover;">
                                                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" 
                                                                    onclick="removeImage(<?= $img['image_id']; ?>, <?= $car['car_id']; ?>)"
                                                                    style="transform: translate(25%, -25%);">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <?php 
                                                        endwhile;
                                                    else:
                                                    ?>
                                                    <div class="col-12">
                                                        <div class="text-center text-muted p-3 border rounded">
                                                            <i class="fas fa-images fa-2x mb-2"></i>
                                                            <p class="mb-0"><?= __('No images uploaded yet'); ?></p>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Update Main Image'); ?></label>
                                                <input type="file" name="car_image" class="form-control" accept="image/*">
                                                <div class="form-text"><?= __('Leave empty to keep current main image'); ?></div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Add New Images'); ?></label>
                                                <input type="file" name="additional_images[]" class="form-control" accept="image/*" multiple>
                                                <div class="form-text"><?= __('You can select multiple images at once'); ?></div>
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
                
                <!-- Document Tracking Modal -->
                <div class="modal fade" id="documentTrackingModal<?= $car['car_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= __('Document Tracking'); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="cars.php" method="POST">
                                <input type="hidden" name="action" value="update_documents">
                                <input type="hidden" name="car_id" value="<?= $car['car_id']; ?>">
                                
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Vignette Date'); ?></label>
                                                <input type="date" name="vignette_date" class="form-control" value="<?= $car['vignette_date'] ?? ''; ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Technical Check Date'); ?></label>
                                                <input type="date" name="technical_check_date" class="form-control" value="<?= $car['technical_check_date'] ?? ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Insurance Date'); ?></label>
                                                <input type="date" name="insurance_date" class="form-control" value="<?= $car['insurance_date'] ?? ''; ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Oil Change Date'); ?></label>
                                                <input type="date" name="oil_change_date" class="form-control" value="<?= $car['oil_change_date'] ?? ''; ?>">
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
                
                <!-- Maintenance History Modal -->
                <div class="modal fade" id="maintenanceHistoryModal<?= $car['car_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= __('Maintenance History'); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?= __('Date'); ?></th>
                                            <th><?= __('Description'); ?></th>
                                            <th><?= __('Cost'); ?></th>
                                            <th><?= __('Invoice'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $maintenance_sql = "SELECT * FROM car_maintenance WHERE car_id = ?";
                                        $maintenance_stmt = $conn->prepare($maintenance_sql);
                                        $maintenance_stmt->bind_param('i', $car['car_id']);
                                        $maintenance_stmt->execute();
                                        $maintenance_result = $maintenance_stmt->get_result();
                                        
                                        while ($maintenance = $maintenance_result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?= $maintenance['maintenance_date']; ?></td>
                                            <td><?= $maintenance['description']; ?></td>
                                            <td>DT. <?= number_format($maintenance['cost'], 0,0); ?></td>
                                            <td>
                                                <?php if ($maintenance['invoice_path']): ?>
                                                    <a href="../<?= $maintenance['invoice_path']; ?>" target="_blank"><?= __('View Invoice'); ?></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('Close'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Add Maintenance Modal -->
                <div class="modal fade" id="addMaintenanceModal<?= $car['car_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= __('Add Maintenance'); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="cars.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add_maintenance">
                                <input type="hidden" name="car_id" value="<?= $car['car_id']; ?>">
                                
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Maintenance Date'); ?></label>
                                                <input type="date" name="maintenance_date" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Description'); ?></label>
                                                <textarea name="description" class="form-control" required></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Cost'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><?= __('Rs.'); ?></span>
                                                    <input type="number" name="cost" class="form-control" required min="0" step="0.01">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?= __('Invoice'); ?></label>
                                                <input type="file" name="invoice" class="form-control" accept="application/pdf, image/*">
                                                <div class="form-text"><?= __('Upload invoice for maintenance'); ?></div>
                                                <div id="invoicePreview"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('Cancel'); ?></button>
                                    <button type="submit" class="btn btn-primary"><?= __('Add Maintenance'); ?></button>
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
                        <div class="col-12">
                            <label class="form-label"><?= __('Additional Images'); ?></label>
                            <input type="file" name="additional_images[]" class="form-control" accept="image/*" multiple>
                            <small class="text-muted"><?= __('Upload additional images of the car. Maximum size: 5MB each'); ?></small>
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

<script>
    // Function to remove image
    function removeImage(imageId, carId) {
        if (confirm('<?= __("Are you sure you want to remove this image?"); ?>')) {
            fetch('cars.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_image&image_id=${imageId}&car_id=${carId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`image-${imageId}`).remove();
                    
                    // Check if no images left
                    const currentImagesDiv = document.getElementById(`currentImages${carId}`);
                    if (currentImagesDiv.children.length === 0) {
                        currentImagesDiv.innerHTML = `
                            <div class="col-12">
                                <div class="text-center text-muted p-3 border rounded">
                                    <i class="fas fa-images fa-2x mb-2"></i>
                                    <p class="mb-0"><?= __('No images uploaded yet'); ?></p>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    alert('<?= __("Error removing image. Please try again."); ?>');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?= __("Error removing image. Please try again."); ?>');
            });
        }
    }

    // Preview uploaded maintenance invoice
    function previewInvoice(input, previewId) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px;">`;
                } else {
                    preview.innerHTML = `<i class="fas fa-file-alt fa-2x text-primary"></i><br><small>${file.name}</small>`;
                }
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '';
        }
    }

    $(document).ready(function() {
        // File preview for invoice
        $('input[name="invoice"]').on('change', function() {
            var file = this.files[0];
            var reader = new FileReader();
            reader.onload = function(e) {
                var invoicePreview = $('#invoicePreview');
                invoicePreview.html('<img src="' + e.target.result + '" class="img-fluid w-100" style="height: 200px; object-fit: cover;">');
            };
            reader.readAsDataURL(file);
        });
    });
</script>

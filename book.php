<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/init.php';

// Check if user is logged in
if (!is_user_logged_in()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Get car details
if (isset($_GET['car_id'])) {
    $car_id = $_GET['car_id'];
    $conn = Connect();
    $sql = "SELECT * FROM cars WHERE car_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    
    if (!$car) {
        header('Location: cars.php');
        exit();
    }

    // Fetch secondary images for this car
    $images_sql = "SELECT image_path FROM car_images WHERE car_id = ? ORDER BY image_id";
    $images_stmt = $conn->prepare($images_sql);
    $images_stmt->bind_param("i", $car_id);
    $images_stmt->execute();
    $images_result = $images_stmt->get_result();
    $secondary_images = [];
    while ($img_row = $images_result->fetch_assoc()) {
        $secondary_images[] = $img_row['image_path'];
    }

    // Fetch booked date ranges for this car
    $sql2 = "SELECT pickup_date, return_date FROM bookings WHERE car_id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $car_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $bookedRanges = [];
    while ($row = $result2->fetch_assoc()) {
        $bookedRanges[] = [
            'start' => $row['pickup_date'],
            'end'   => $row['return_date']
        ];
    }
} else {
    header('Location: cars.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo htmlspecialchars($car['car_name']); ?> <?= __('- Car Rental System'); ?></title>
    <?php include 'includes/header.php'; ?>
    <style>
       :root {
    --accent: #0d6efd; /* Bootstrap blue, change to your brand color */
    --accent-dark: #084298;
    --light-bg: #f8f9fa;
    --card-bg: #ffffff;
    --border-radius: 12px;
    --shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* General Layout */
body {
    background: var(--light-bg);
    font-family: 'Inter', sans-serif;
    color: #333;
}

.booking-container {
    padding: 40px 0;
}

/* Car Gallery */
.car-gallery {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.main-image {
    width: 100%;
    height: auto;
    max-height: 450px;
    object-fit: cover;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    cursor: pointer;
}
.thumbnail-container {
    margin-top: 12px;
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 5px;
    scrollbar-width: none;
}
.thumbnail-container::-webkit-scrollbar { display: none; }
.thumbnail {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: 0.2s ease;
}
.thumbnail:hover,
.thumbnail.active {
    border-color: var(--accent);
}

/* Car Details Card */
.car-details-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-top: 20px;
}
.car-header {
    background: var(--accent);
    color: white;
    padding: 20px;
    text-align: center;
}
.feature-badge {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    margin: 4px;
}

/* Specs */
.car-specs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    padding: 20px;
    background: var(--light-bg);
}
.spec-item {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    text-align: center;
    padding: 15px;
}
.spec-icon {
    font-size: 1.8rem;
    color: var(--accent);
    margin-bottom: 8px;
}

/* Rental Summary */
.rental-summary {
    background: #f9fafb;
    border: 1px solid #eee;
    border-radius: var(--border-radius);
    padding: 20px;
    margin-top: 15px;
}
.total-amount {
    color: var(--accent);
    font-size: 1.6rem;
    font-weight: bold;
}

/* Booking Form */
.booking-form-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-top: 30px;
    overflow: hidden;
}
.form-header {
    background: var(--accent);
    color: white;
    padding: 20px;
    text-align: center;
}
.form-body {
    padding: 25px;
}
.form-control {
    border-radius: 8px;
    padding: 12px;
    border: 1px solid #ddd;
}
.form-control:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
.btn-book {
    background: var(--accent);
    color: white;
    border-radius: 8px;
    padding: 14px;
    font-weight: 600;
    border: none;
    transition: 0.2s ease;
}
.btn-book:hover {
    background: var(--accent-dark);
}

/* Flatpickr Dates */
.flatpickr-day.booked-date {
    background: #dc3545 !important;
    color: white !important;
    border-radius: 50%;
}
.flatpickr-day.booked-date:hover {
    background: #b02a37 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .booking-container { padding: 20px; }
    .main-image { max-height: 300px; }
}

    </style>
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="booking-container">
        <div class="container">
            <div class="booking-form">
                <!-- Car Gallery -->
                <div class="car-gallery mb-4">
                    <?php 
                    $main_image = !empty($car['car_image']) ? $car['car_image'] : 'assets/img/cars/default.jpg';
                    $all_images = array_merge([$main_image], $secondary_images);
                    ?>
                    <img src="<?php echo $all_images[0]; ?>" 
                         class="main-image" 
                         id="mainImage"
                         alt="<?php echo htmlspecialchars($car['car_name']); ?>">
                    
                    <?php if (count($all_images) > 1): ?>
                    <div class="thumbnail-container">
                        <?php foreach ($all_images as $index => $image): ?>
                        <img src="<?php echo $image; ?>" 
                             class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                             onclick="changeMainImage('<?php echo $image; ?>', this)"
                             alt="<?php echo htmlspecialchars($car['car_name']); ?> - Image <?php echo $index + 1; ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Car Details -->
                <div class="car-details-card">
                    <div class="car-header">
                        <h2 class="mb-2"><?php echo htmlspecialchars($car['car_name']); ?></h2>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <span class="feature-badge"><?php echo htmlspecialchars($car['car_type']); ?></span>
                            <span class="feature-badge"><?= __('Available'); ?></span>
                        </div>
                    </div>
                    
                    <div class="car-specs">
                        <div class="spec-item">
                            <div class="spec-icon">
                                <i class="fas fa-car"></i>
                            </div>
                            <h6><?= __('Vehicle Type'); ?></h6>
                            <p class="mb-0"><?php echo htmlspecialchars($car['car_type']); ?></p>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                            <h6><?= __('Daily Rate'); ?></h6>
                            <p class="mb-0">Rs. <?php echo number_format($car['price'], 2); ?></p>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h6><?= __('Capacity'); ?></h6>
                            <p class="mb-0"><?php echo isset($car['capacity']) ? $car['capacity'] . ' ' . __('Passengers') : __('Standard'); ?></p>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">
                                <i class="fas fa-gas-pump"></i>
                            </div>
                            <h6><?= __('Fuel Type'); ?></h6>
                            <p class="mb-0"><?php echo isset($car['fuel_type']) ? htmlspecialchars($car['fuel_type']) : __('Petrol'); ?></p>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="rental-summary">
                            <h5 class="mb-3 text-center">
                                <i class="fas fa-calculator me-2"></i><?= __('Rental Summary'); ?>
                            </h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-calendar-day me-2"></i><?= __('Daily Rate'); ?>:</span>
                                <span class="fw-bold">Rs. <?php echo number_format($car['price'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-clock me-2"></i><?= __('Number of Days'); ?>:</span>
                                <span id="totalDays" class="fw-bold">0</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold fs-5">
                                    <i class="fas fa-money-bill-wave me-2"></i><?= __('Total Amount'); ?>:
                                </span>
                                <span id="totalAmount" class="total-amount">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="booking-form-card">
                    <div class="form-header">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i><?= __('Book Your Ride'); ?>
                        </h4>
                        <p class="mb-0 mt-2 opacity-75"><?= __('Fill in the details below to complete your booking'); ?></p>
                    </div>
                    <div class="form-body">
                        <form action="process_booking.php" method="POST" id="bookingForm">
                            <input type="hidden" name="car_id" value="<?php echo htmlspecialchars($car_id); ?>">
                            <input type="hidden" name="total_days" id="hidden_total_days" value="0">
                            <input type="hidden" name="total_amount" id="hidden_total_amount" value="0">
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="pickup_date" class="form-label fw-bold">
                                        <i class="fas fa-calendar-alt me-2 text-primary"></i><?= __('Pickup Date'); ?>
                                    </label>
                                    <input type="text" class="form-control" id="pickup_date" name="pickup_date" 
                                           placeholder="<?= __('Select pickup date'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="return_date" class="form-label fw-bold">
                                        <i class="fas fa-calendar-alt me-2 text-primary"></i><?= __('Return Date'); ?>
                                    </label>
                                    <input type="text" class="form-control" id="return_date" name="return_date" 
                                           placeholder="<?= __('Select return date'); ?>" required>
                                </div>
                                
                                <div class="col-12">
                                    <label for="pickup_location" class="form-label fw-bold">
                                        <i class="fas fa-map-marker-alt me-2 text-primary"></i><?= __('Pickup Location'); ?>
                                    </label>
                                    <input type="text" class="form-control" id="pickup_location" name="pickup_location" 
                                           placeholder="<?= __('Enter your preferred pickup location'); ?>" required>
                                </div>

                                <div class="col-12">
                                    <label for="notes" class="form-label fw-bold">
                                        <i class="fas fa-sticky-note me-2 text-primary"></i><?= __('Special Requests'); ?> 
                                        <small class="text-muted">(<?= __('Optional'); ?>)</small>
                                    </label>
                                    <textarea class="form-control" id="notes" name="notes" rows="4" 
                                              placeholder="<?= __('Any special requirements or notes for your booking'); ?>"></textarea>
                                </div>

                                <div class="col-12 mt-5">
                                    <button type="submit" class="btn btn-book w-100">
                                        <i class="fas fa-credit-card me-2"></i><?= __('Confirm Booking'); ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

<script>
    // Image gallery functionality
    function changeMainImage(imageSrc, thumbnail) {
        document.getElementById('mainImage').src = imageSrc;
        
        // Remove active class from all thumbnails
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        
        // Add active class to clicked thumbnail
        thumbnail.classList.add('active');
    }

    // Booking calculation functionality
    const bookedRanges = <?php echo json_encode($bookedRanges); ?>;
    const bookedDates = [];

    // Convert date ranges to individual dates
    bookedRanges.forEach(range => {
        let current = new Date(range.start);
        const end = new Date(range.end);
        while (current <= end) {
            bookedDates.push(current.toISOString().split('T')[0]);
            current.setDate(current.getDate() + 1);
        }
    });

    const pricePerDay = <?php echo $car['price']; ?>;
    const totalDays = document.getElementById('totalDays');
    const totalAmount = document.getElementById('totalAmount');
    const hiddenTotalDays = document.getElementById('hidden_total_days');
    const hiddenTotalAmount = document.getElementById('hidden_total_amount');

    function updatePrice() {
        const start = pickupCalendar.selectedDates[0];
        const end = returnCalendar.selectedDates[0];
        if (start && end) {
            const diffTime = end - start;
            const diffDays = diffTime / (1000 * 60 * 60 * 24);
            if (diffDays > 0) {
                totalDays.textContent = diffDays + (diffDays === 1 ? ' day' : ' days');
                const total = diffDays * pricePerDay;
                totalAmount.textContent = 'Rs. ' + total.toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                hiddenTotalDays.value = diffDays;
                hiddenTotalAmount.value = total;
            }
        }
    }

    function highlightBookedDates(selectedDates, dateStr, instance, dayElem) {
        const date = dayElem.dateObj.toISOString().split('T')[0];
        if (bookedDates.includes(date)) {
            dayElem.classList.add("booked-date");
        }
    }

    const pickupCalendar = flatpickr("#pickup_date", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: bookedDates,
        onDayCreate: highlightBookedDates,
        onChange: updatePrice
    });

    const returnCalendar = flatpickr("#return_date", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: bookedDates,
        onDayCreate: highlightBookedDates,
        onChange: updatePrice
    });

    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        if (!pickupCalendar.selectedDates.length || !returnCalendar.selectedDates.length) {
            e.preventDefault();
            alert('<?= __('Please select both pickup and return dates'); ?>');
            return;
        }
        
        const start = pickupCalendar.selectedDates[0];
        const end = returnCalendar.selectedDates[0];
        if (start >= end) {
            e.preventDefault();
            alert('<?= __('Return date must be after pickup date'); ?>');
            return;
        }
    });

    // Add smooth scrolling animation when form is submitted
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        if (this.checkValidity()) {
            // Add loading state to button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?= __('Processing...'); ?>';
            submitBtn.disabled = true;
            
            // Re-enable after 3 seconds in case of issues
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        }
    });
</script>
</body>
</html>

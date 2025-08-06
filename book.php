<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

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
    <title>Book <?php echo htmlspecialchars($car['car_name']); ?> - Car Rental System</title>
    <?php include 'includes/header.php'; ?>
    <style>
        .booking-form { max-width: 800px; margin: 0 auto; }
        .car-image { max-height: 300px; object-fit: contain; width: 100%; }
        .rental-summary { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .rental-summary hr { margin: 15px 0; }
        .total-amount { font-size: 1.25rem; color: #0d6efd; }
        /* Highlight booked dates in red */
        .flatpickr-day.booked-date {
            background: #dc3545 !important;
            color: white !important;
            border-radius: 50%;
            opacity: 1 !important;
        }
        .flatpickr-day.booked-date:hover {
            background: #b02a37 !important;
        }
    </style>
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="bg-light">
    <?php include 'includes/navigation.php'; ?>

    <div class="container py-5">
        <div class="booking-form">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="card-title h5 mb-0">Book <?php echo htmlspecialchars($car['car_name']); ?></h3>
                </div>
                <div class="card-body">
                    <!-- Car Details -->
                    <div class="row align-items-center mb-4">
                        <div class="col-md-6 text-center mb-3 mb-md-0">
                            <img src="<?php echo !empty($car['car_image']) ? $car['car_image'] : 'assets/img/cars/default.jpg'; ?>" 
                                 class="car-image rounded" 
                                 alt="<?php echo htmlspecialchars($car['car_name']); ?>">
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-3"><?php echo htmlspecialchars($car['car_name']); ?></h4>
                            <div class="mb-3">
                                <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($car['car_type']); ?></span>
                                <span class="badge bg-success">Available</span>
                            </div>
                            <div class="rental-summary">
                                <h5 class="mb-3">Rental Summary</h5>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Daily Rate:</span>
                                    <span>Rs. <?php echo number_format($car['price'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Number of Days:</span>
                                    <span id="totalDays">0</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Total Amount:</span>
                                    <span id="totalAmount" class="total-amount fw-bold">Rs. 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Form -->
                    <form action="process_booking.php" method="POST" id="bookingForm">
                        <input type="hidden" name="car_id" value="<?php echo htmlspecialchars($car_id); ?>">
                        <input type="hidden" name="total_days" id="hidden_total_days" value="0">
                        <input type="hidden" name="total_amount" id="hidden_total_amount" value="0">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pickup_date" class="form-label">Pickup Date</label>
                                <input type="text" class="form-control" id="pickup_date" name="pickup_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="return_date" class="form-label">Return Date</label>
                                <input type="text" class="form-control" id="return_date" name="return_date" required>
                            </div>
                            
                            <div class="col-12">
                                <label for="pickup_location" class="form-label">Pickup Location</label>
                                <input type="text" class="form-control" id="pickup_location" name="pickup_location" 
                                       placeholder="Enter your preferred pickup location" required>
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label">Special Requests (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Any special requirements or notes for your booking"></textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-calendar-check me-2"></i>Confirm Booking
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

<script>
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
                totalDays.textContent = diffDays;
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
            alert('Please select both pickup and return dates');
        }
    });
</script>
</body>
</html>

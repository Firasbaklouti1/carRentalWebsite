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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <?php include 'includes/navigation.php'; ?>

    <header class="section">
        <div class="container">
            <div class="section-header text-start">
                <h1 class="section-title mb-1">
                    <i class="fas fa-calendar-check text-primary me-2" aria-hidden="true"></i>
                    <?= __('Book Your Ride'); ?>
                </h1>
                <p class="text-muted mb-0"><?= __('Fill in the details below to complete your booking'); ?></p>
            </div>
        </div>
    </header>

    <main class="booking-container">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card modern-card">
                        <div class="card-body">
                            <div class="car-gallery">
                                <?php 
                                $main_image = !empty($car['car_image']) ? $car['car_image'] : 'assets/img/cars/default.jpg';
                                $all_images = array_merge([$main_image], $secondary_images);
                                ?>
                                <img src="<?php echo $all_images[0]; ?>" 
                                    class="main-image" 
                                    id="mainImage"
                                    alt="<?php echo htmlspecialchars($car['car_name']); ?>">

                                <?php if (count($all_images) > 1): ?>
                                <div class="thumbnail-container" aria-label="<?= __('Car images thumbnails'); ?>">
                                    <?php foreach ($all_images as $index => $image): ?>
                                    <img src="<?php echo $image; ?>" 
                                        class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                        onclick="changeMainImage('<?php echo $image; ?>', this)"
                                        alt="<?php echo htmlspecialchars($car['car_name']); ?> - Image <?php echo $index + 1; ?>">
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card modern-card car-details-card mt-4">
                        <div class="car-header p-4 text-center">
                            <h2 class="h4 mb-2"><?php echo htmlspecialchars($car['car_name']); ?></h2>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <span class="feature-badge"><?php echo htmlspecialchars($car['car_type']); ?></span>
                                <span class="feature-badge"><?= __('Available'); ?></span>
                            </div>
                        </div>
                        <div class="car-specs">
                            <div class="spec-item">
                                <div class="spec-icon"><i class="fas fa-car" aria-hidden="true"></i></div>
                                <h6 class="mb-1"><?= __('Vehicle Type'); ?></h6>
                                <p class="mb-0"><?php echo htmlspecialchars($car['car_type']); ?></p>
                            </div>
                            <div class="spec-item">
                                <div class="spec-icon"><i class="fas fa-rupee-sign" aria-hidden="true"></i></div>
                                <h6 class="mb-1"><?= __('Daily Rate'); ?></h6>
                                <p class="mb-0">DT. <?php echo number_format($car['price'], 0,0); ?></p>
                            </div>
                            <div class="spec-item">
                                <div class="spec-icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                                <h6 class="mb-1"><?= __('Capacity'); ?></h6>
                                <p class="mb-0"><?php echo isset($car['capacity']) ? $car['capacity'] . ' ' . __('Passengers') : __('Standard'); ?></p>
                            </div>
                            <div class="spec-item">
                                <div class="spec-icon"><i class="fas fa-gas-pump" aria-hidden="true"></i></div>
                                <h6 class="mb-1"><?= __('Fuel Type'); ?></h6>
                                <p class="mb-0"><?php echo isset($car['fuel_type']) ? htmlspecialchars($car['fuel_type']) : __('Petrol'); ?></p>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="rental-summary">
                                <h5 class="mb-3 text-center">
                                    <i class="fas fa-calculator me-2" aria-hidden="true"></i><?= __('Rental Summary'); ?>
                                </h5>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-calendar-day me-2" aria-hidden="true"></i><?= __('Daily Rate'); ?>:</span>
                                    <span class="fw-bold">DT. <?php echo number_format($car['price'], 0,0); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-clock me-2" aria-hidden="true"></i><?= __('Number of Days'); ?>:</span>
                                    <span id="totalDays" class="fw-bold">0</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-5">
                                        <i class="fas fa-money-bill-wave me-2" aria-hidden="true"></i><?= __('Total Amount'); ?>:
                                    </span>
                                    <span id="totalAmount" class="total-amount">DT. 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card modern-card booking-form-card">
                        <div class="form-header p-4 text-center">
                            <h4 class="mb-0">
                                <i class="fas fa-calendar-alt me-2" aria-hidden="true"></i><?= __('Reservation Details'); ?>
                            </h4>
                        </div>
                        <div class="form-body p-4">
                            <form action="process_booking.php" method="POST" id="bookingForm">
                                <input type="hidden" name="car_id" value="<?php echo htmlspecialchars($car_id); ?>">
                                <input type="hidden" name="total_days" id="hidden_total_days" value="0">
                                <input type="hidden" name="total_amount" id="hidden_total_amount" value="0">
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="pickup_date" class="form-label">
                                            <i class="fas fa-calendar-day text-primary me-2" aria-hidden="true"></i><?= __('Pickup Date'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="pickup_date" name="pickup_date" 
                                            placeholder="<?= __('Select pickup date'); ?>" required aria-required="true">
                                    </div>
                                    <div class="col-12">
                                        <label for="return_date" class="form-label">
                                            <i class="fas fa-calendar-day text-primary me-2" aria-hidden="true"></i><?= __('Return Date'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="return_date" name="return_date" 
                                            placeholder="<?= __('Select return date'); ?>" required aria-required="true">
                                    </div>
                                    <div class="col-12">
                                        <label for="pickup_location" class="form-label">
                                            <i class="fas fa-map-marker-alt text-primary me-2" aria-hidden="true"></i><?= __('Pickup Location'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="pickup_location" name="pickup_location" 
                                            placeholder="<?= __('Enter your preferred pickup location'); ?>" required aria-required="true">
                                    </div>
                                    <div class="col-12">
                                        <label for="notes" class="form-label">
                                            <i class="fas fa-sticky-note text-primary me-2" aria-hidden="true"></i><?= __('Special Requests'); ?>
                                            <small class="text-muted">(<?= __('Optional'); ?>)</small>
                                        </label>
                                        <textarea class="form-control" id="notes" name="notes" rows="4" 
                                            placeholder="<?= __('Any special requirements or notes for your booking'); ?>"></textarea>
                                    </div>
                                    <div class="col-12 pt-2">
                                        <button type="submit" class="btn btn-book w-100 btn-elevated">
                                            <i class="fas fa-credit-card me-2" aria-hidden="true"></i><?= __('Confirm Booking'); ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <p class="text-muted small mt-3 mb-0"><?= __('Booked dates are disabled in the date picker.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        /* Image gallery functionality */
        function changeMainImage(imageSrc, thumbnail) {
            document.getElementById('mainImage').src = imageSrc;
            document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        /* --- Booking calculation & validation --- */
        /* bookedRanges comes from PHP */
        const bookedRanges = <?php echo json_encode($bookedRanges); ?>;

        /* Build array of disabled single dates for flatpickr (keeps existing behavior) */
        const bookedDates = [];
        bookedRanges.forEach(range => {
            let current = new Date(range.start);
            const end = new Date(range.end);
            while (current <= end) {
                bookedDates.push(current.toISOString().split('T')[0]);
                current.setDate(current.getDate() + 1);
            }
        });

        /* Price / UI elements */
        const pricePerDay = <?php echo $car['price']; ?>;
        const totalDays = document.getElementById('totalDays');
        const totalAmount = document.getElementById('totalAmount');
        const hiddenTotalDays = document.getElementById('hidden_total_days');
        const hiddenTotalAmount = document.getElementById('hidden_total_amount');

        /* Utility: return new Date at local midnight for given date */
        function dateOnly(d) {
            return new Date(d.getFullYear(), d.getMonth(), d.getDate());
        }

        /* Check if selected [start, end] overlaps any bookedRanges.
        Returns { overlaps: bool, bookedStart, bookedEnd } */
        function rangeOverlapsBookedRanges(start, end) {
            const s = dateOnly(start).getTime();
            const e = dateOnly(end).getTime();
            for (const r of bookedRanges) {
                const rs = dateOnly(new Date(r.start)).getTime();
                const re = dateOnly(new Date(r.end)).getTime();
                // inclusive overlap: selected interval intersects existing booking
                if (s <= re && e >= rs) {
                    return {
                        overlaps: true,
                        bookedStart: new Date(rs).toISOString().split('T')[0],
                        bookedEnd: new Date(re).toISOString().split('T')[0]
                    };
                }
            }
            return { overlaps: false };
        }

        /* Validate selected dates.
        showAlert = true -> will call alert(...) on failure.
        returns true when valid, false otherwise. */
        function validateDates(showAlert = true) {
            if (!pickupCalendar.selectedDates.length || !returnCalendar.selectedDates.length) {
                if (showAlert) alert('<?= __('Please select both pickup and return dates'); ?>');
                return false;
            }

            const start = dateOnly(pickupCalendar.selectedDates[0]);
            const end = dateOnly(returnCalendar.selectedDates[0]);

            // 1) start must be strictly before end
            if (start >= end) {
                if (showAlert) alert('<?= __('Return date must be after pickup date'); ?>');
                return false;
            }

            // 2) selected range must not overlap any booked ranges (inclusive)
            const overlap = rangeOverlapsBookedRanges(start, end);
            if (overlap.overlaps) {
                if (showAlert) {
                    alert(
                        '<?= __('Selected dates conflict with an existing booking'); ?> ' +
                        `(${overlap.bookedStart} → ${overlap.bookedEnd}).`
                    );
                }
                return false;
            }

            return true;
        }

        /* Update price and totals (called on date changes) */
        function updatePrice() {
            const start = pickupCalendar.selectedDates[0];
            const end = returnCalendar.selectedDates[0];

            if (start && end) {
                // validate first (shows alerts if invalid)
                // We call validateDates(false) here to avoid double alerts on every change;
                // keep live validation minimal — final submit will show alerts if still invalid.
                const isValid = validateDates(false);

                // compute diff in full days using dateOnly to avoid timezone issues
                const msPerDay = 1000 * 60 * 60 * 24;
                const diffMs = dateOnly(end).getTime() - dateOnly(start).getTime();
                const diffDays = Math.round(diffMs / msPerDay);

                if (isValid && diffDays > 0) {
                    totalDays.textContent = diffDays + (diffDays === 1 ? ' day' : ' days');
                    const total = diffDays * pricePerDay;
                    totalAmount.textContent = 'DT. ' + total.toLocaleString('en-IN', {
                        minimumFractionDigits: 2, maximumFractionDigits: 2
                    });
                    hiddenTotalDays.value = diffDays;
                    hiddenTotalAmount.value = total;
                } else {
                    totalDays.textContent = '0';
                    totalAmount.textContent = 'DT. 0.00';
                    hiddenTotalDays.value = 0;
                    hiddenTotalAmount.value = 0;
                }
            } else {
                // one or both dates not selected yet -> reset totals
                totalDays.textContent = '0';
                totalAmount.textContent = 'DT. 0.00';
                hiddenTotalDays.value = 0;
                hiddenTotalAmount.value = 0;
            }
        }

        /* Highlight booked dates on calendar day create (keeps your class) */
        function highlightBookedDates(selectedDates, dateStr, instance, dayElem) {
            const date = dayElem.dateObj.toISOString().split('T')[0];
            if (bookedDates.includes(date)) {
                dayElem.classList.add("booked-date");
            }
        }

        /* Initialize flatpickr calendars */
        const pickupCalendar = flatpickr("#pickup_date", {
            dateFormat: "Y-m-d",
            minDate: "today",
            disable: bookedDates,
            onDayCreate: highlightBookedDates,
            onChange: function(selectedDates, dateStr, instance) {
                // set return minDate to the next day after pickup for better UX
                if (selectedDates.length) {
                    const nextDay = new Date(selectedDates[0]);
                    nextDay.setDate(nextDay.getDate() + 1);
                    returnCalendar.set('minDate', nextDay);

                    // if return is before new minDate, clear it
                    if (returnCalendar.selectedDates.length) {
                        const ret = dateOnly(returnCalendar.selectedDates[0]).getTime();
                        if (ret <= dateOnly(selectedDates[0]).getTime()) {
                            returnCalendar.clear();
                        }
                    }
                } else {
                    returnCalendar.set('minDate', 'today');
                }
                updatePrice();
            }
        });

        const returnCalendar = flatpickr("#return_date", {
            dateFormat: "Y-m-d",
            minDate: "today",
            disable: bookedDates,
            onDayCreate: highlightBookedDates,
            onChange: function(selectedDates, dateStr, instance) {
                updatePrice();
            }
        });

        /* Final form submission validation */
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            // ensure both dates are present and valid (this shows alerts)
            if (!validateDates(true)) {
                e.preventDefault();
                return;
            }

            // proceed with submit UI
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?= __('Processing...'); ?>';
            submitBtn.disabled = true;
            // keep your previous behavior of re-enabling button after a short time
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
    </script>

</body>
</html>

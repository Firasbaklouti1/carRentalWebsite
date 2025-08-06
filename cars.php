<?php
require_once 'includes/auth.php';
$conn = Connect();

// Fetch all cars with filters
$sql = "SELECT * FROM cars ORDER BY car_name";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Cars - Car Rental System</title>
    <?php include 'includes/header.php'; ?>
    <style>
        .car-img-top {
            width: 100%;
            height: 200px;
            object-fit: cover;
            object-position: center;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navigation.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="mb-4">Our Fleet</h2>
                <p class="text-muted">Choose from our wide range of vehicles for any occasion.</p>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchCar" placeholder="Search cars...">
                    <button class="btn btn-primary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <select class="form-select" id="carType">
                                    <option value="">Car Type</option>
                                    <option value="Sedan">Sedan</option>
                                    <option value="SUV">SUV</option>
                                    <option value="Luxury">Luxury</option>
                                    <option value="Sports">Sports</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">Price Range</span>
                                    <input type="number" class="form-control" id="minPrice" placeholder="Min">
                                    <input type="number" class="form-control" id="maxPrice" placeholder="Max">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100" id="applyFilters">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cars Grid -->
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($car = $result->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <?php if (!empty($car['car_image'])): ?>
                                <img src="<?php echo htmlspecialchars($car['car_image']); ?>" 
                                     class="car-img-top"
                                     alt="<?php echo htmlspecialchars($car['car_name']); ?>">
                            <?php else: ?>
                                <img src="assets/img/cars/default.jpg" 
                                     class="car-img-top"
                                     alt="Default car image">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($car['car_name']); ?></h5>
                                <div class="car-features mb-3">
                                    <span class="badge bg-primary me-2">
                                        <i class="fas fa-car me-1"></i><?php echo htmlspecialchars($car['car_type']); ?>
                                    </span>
                                    <span class="badge bg-info me-2">
                                        <i class="fas fa-gas-pump me-1"></i><?php echo htmlspecialchars($car['fuel_type'] ?? 'Petrol'); ?>
                                    </span>
                                    <span class="badge bg-<?php echo $car['car_availability'] === 'yes' ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-<?php echo $car['car_availability'] === 'yes' ? 'check' : 'times'; ?> me-1"></i>
                                        <?php echo $car['car_availability'] === 'yes' ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </div>
                                <div class="car-price mb-3">
                                    Rs. <?php echo number_format($car['price'], 2); ?> <small class="text-muted">/ day</small>
                                </div>
                                <?php if (is_user_logged_in()): ?>
                                    <a href="book.php?car_id=<?php echo $car['car_id']; ?>" class="btn btn-primary w-100">
                                        <i class="fas fa-calendar-check me-2"></i>Book Now
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login to Book
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No cars available at the moment.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchCar');
            const carTypeSelect = document.getElementById('carType');
            const minPriceInput = document.getElementById('minPrice');
            const maxPriceInput = document.getElementById('maxPrice');
            const applyFiltersBtn = document.getElementById('applyFilters');
            const carCards = document.querySelectorAll('.car-card');

            function filterCars() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedType = carTypeSelect.value;
                const minPrice = parseFloat(minPriceInput.value) || 0;
                const maxPrice = parseFloat(maxPriceInput.value) || Infinity;

                carCards.forEach(card => {
                    const title = card.querySelector('.card-title').textContent.toLowerCase();
                    const type = card.querySelector('.badge.bg-primary').textContent.toLowerCase();
                    const priceText = card.querySelector('.car-price').textContent;
                    const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));

                    const matchesSearch = title.includes(searchTerm);
                    const matchesType = !selectedType || type.includes(selectedType.toLowerCase());
                    const matchesPrice = price >= minPrice && price <= maxPrice;

                    card.closest('.col').style.display = 
                        matchesSearch && matchesType && matchesPrice ? '' : 'none';
                });
            }

            // Event listeners
            searchInput.addEventListener('input', filterCars);
            carTypeSelect.addEventListener('change', filterCars);
            minPriceInput.addEventListener('input', filterCars);
            maxPriceInput.addEventListener('input', filterCars);
            applyFiltersBtn.addEventListener('click', filterCars);
        });
    </script>
</body>
</html>

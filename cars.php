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
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <?php include 'includes/navigation.php'; ?>

    <header class="section">
        <div class="container">
            <div class="section-header text-start">
                <h1 class="section-title mb-2">
                    <i class="fas fa-car text-primary me-2" aria-hidden="true"></i><?= __('Our Fleet'); ?>
                </h1>
                <p class="text-muted"><?= __('Choose from our wide range of vehicles for any occasion.'); ?></p>
            </div>

            <div class="filter-bar">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="searchCar" class="form-label"><?= __('Search'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span>
                            <input type="text" class="form-control" id="searchCar" placeholder="<?= __('Search cars...'); ?>" aria-label="<?= __('Search cars'); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="carType" class="form-label"><?= __('Car Type'); ?></label>
                        <select class="form-select" id="carType" aria-label="<?= __('Filter by car type'); ?>">
                            <option value=""><?= __('All Types'); ?></option>
                            <option value="Sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                            <option value="Luxury">Luxury</option>
                            <option value="Sports">Sports</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?= __('Price Range'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text">DT</span>
                            <input type="number" class="form-control" id="minPrice" placeholder="<?= __('Min'); ?>" aria-label="<?= __('Minimum price'); ?>">
                            <input type="number" class="form-control" id="maxPrice" placeholder="<?= __('Max'); ?>" aria-label="<?= __('Maximum price'); ?>">
                        </div>
                    </div>
                    <div class="col-md-1">
                        
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container section pt-4">
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($car = $result->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card car-card modern-card h-100">
                            <?php if (!empty($car['car_image'])): ?>
                                <img src="<?php echo htmlspecialchars($car['car_image']); ?>" 
                                     class="card-img-top"
                                     alt="<?php echo htmlspecialchars($car['car_name']); ?>">
                            <?php else: ?>
                                <img src="assets/img/cars/default.jpg" 
                                     class="card-img-top"
                                     alt="Default car image">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title mb-2"><?php echo htmlspecialchars($car['car_name']); ?></h5>
                                <div class="car-meta mb-3">
                                    <span class="badge bg-primary">
                                        <i class="fas fa-car me-1" aria-hidden="true"></i><?php echo htmlspecialchars($car['car_type']); ?>
                                    </span>
                                    <span class="badge bg-info">
                                        <i class="fas fa-gas-pump me-1" aria-hidden="true"></i><?php echo htmlspecialchars($car['fuel_type'] ?? 'Petrol'); ?>
                                    </span>
                                    <span class="badge bg-<?php echo $car['car_availability'] === 'yes' ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-<?php echo $car['car_availability'] === 'yes' ? 'check' : 'times'; ?> me-1" aria-hidden="true"></i>
                                        <?php echo $car['car_availability'] === 'yes' ? __('Available') : __('Not Available'); ?>
                                    </span>
                                </div>
                                <div class="car-price h5 mb-3">
                                    DT . <?php echo number_format($car['price'], 0,0); ?> <small class="text-muted"><?= __('/ day'); ?></small>
                                </div>
                                <?php if (is_user_logged_in()): ?>
                                    <a href="book.php?car_id=<?php echo $car['car_id']; ?>" class="btn btn-primary w-100 btn-elevated">
                                        <i class="fas fa-calendar-check me-2" aria-hidden="true"></i><?= __('Book Now'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i><?= __('Login to Book'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info modern-card">
                        <i class="fas fa-info-circle me-2" aria-hidden="true"></i><?= __('No cars available at the moment.'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

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
                const minPrice = parseFloat(minPriceInput.value)/100 || 0;
                const maxPrice = parseFloat(maxPriceInput.value)/100 || Infinity;
                

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

            searchInput.addEventListener('input', filterCars);
            carTypeSelect.addEventListener('change', filterCars);
            minPriceInput.addEventListener('input', filterCars);
            maxPriceInput.addEventListener('input', filterCars);
            applyFiltersBtn.addEventListener('click', filterCars);
        });
    </script>
</body>
</html>

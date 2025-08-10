<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/init.php';

try {
    $conn = Connect();

    // Get car types for filter
    $types_sql = "SELECT DISTINCT car_type FROM cars ORDER BY car_type";
    $car_types = $conn->query($types_sql);
    $types_array = [];
    if ($car_types) {
        while ($type = $car_types->fetch_assoc()) {
            $types_array[] = $type['car_type'];
        }
    }

    // Get price range
    $price_sql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM cars";
    $price_result = $conn->query($price_sql);
    $price_range = $price_result->fetch_assoc();

    // Pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 6;
    $offset = ($page - 1) * $per_page;

    // Build WHERE clauses
    $where_clauses = ["car_availability = 'yes'"];
    $params = [];
    $types = "";

    if (!empty($_GET['search'])) {
        $where_clauses[] = "(car_name LIKE ? OR car_type LIKE ?)";
        $search_term = "%" . $_GET['search'] . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }

    if (!empty($_GET['type'])) {
        $where_clauses[] = "car_type = ?";
        $params[] = $_GET['type'];
        $types .= "s";
    }

    if (!empty($_GET['min_price'])) {
        $where_clauses[] = "price >= ?";
        $params[] = $_GET['min_price'];
        $types .= "d";
    }

    if (!empty($_GET['max_price'])) {
        $where_clauses[] = "price <= ?";
        $params[] = $_GET['max_price'];
        $types .= "d";
    }

    // Count total for pagination
    $count_sql = "SELECT COUNT(*) as total FROM cars WHERE " . implode(" AND ", $where_clauses);
    $stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total_result = $stmt->get_result();
    $total_cars = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_cars / $per_page);

    // Fetch page of cars
    $sql = "SELECT * FROM cars 
            WHERE " . implode(" AND ", $where_clauses) . " 
            ORDER BY car_name
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $types_for_query = $types . "ii";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->bind_param($types_for_query, ...$params);
    $stmt->execute();
    $available_cars = $stmt->get_result();

} catch (Exception $e) {
    error_log("Error in index.php: " . $e->getMessage());
    $error_message = "An error occurred while fetching cars. Please try again later.";
}

// AJAX-only response (grid + pagination)
if (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    if (isset($error_message)) {
        echo '<div class="alert alert-danger text-center">' . htmlspecialchars($error_message) . '</div>';
    } else {
        // Cars grid
        echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
        if ($available_cars && $available_cars->num_rows > 0) {
            while ($car = $available_cars->fetch_assoc()) {
                ?>
                <div class="col">
                    <div class="card car-card modern-card h-100">
                        <img src="<?php echo !empty($car['car_image']) ? $car['car_image'] : 'assets/img/cars/default.jpg'; ?>" 
                             class="card-img-top"
                             alt="<?php echo htmlspecialchars($car['car_name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($car['car_name']); ?></h5>
                            <div class="car-meta mb-3">
                                <span class="badge bg-primary"><i class="fas fa-car me-1"></i> <?php echo htmlspecialchars($car['car_type']); ?></span>
                                <span class="badge-soft success"><?= __('Available'); ?></span>
                            </div>
                            <div class="car-price h5 mb-3">
                                DT. <?php echo number_format($car['price'], 0,0); ?> <small class="text-muted"><?= __('/day'); ?></small>
                            </div>
                            <?php if (is_user_logged_in()): ?>
                                <a href="book.php?car_id=<?php echo $car['car_id']; ?>" class="btn btn-primary w-100 btn-elevated">
                                    <i class="fas fa-calendar-check me-2"></i><?= __('Book Now'); ?>
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i><?= __('Login to Book'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-info text-center modern-card">
                    <i class="fas fa-car-side fa-3x mb-3"></i>
                    <p class="mb-0">'.__('No cars available matching your criteria').'.</p>
                  </div></div>';
        }
        echo '</div>';

        // Centered pagination
        if ($total_pages > 1) {
            echo '<nav aria-label="Page navigation" class="mt-4 d-flex justify-content-center"><ul class="pagination">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $active = $page === $i ? 'active' : '';
                $url = "?page=$i";
                if (!empty($_GET['search']))   $url .= '&search=' . urlencode($_GET['search']);
                if (!empty($_GET['type']))     $url .= '&type='   . urlencode($_GET['type']);
                if (!empty($_GET['min_price']))$url .= '&min_price=' . urlencode($_GET['min_price']);
                if (!empty($_GET['max_price']))$url .= '&max_price=' . urlencode($_GET['max_price']);
                echo "<li class='page-item $active'>
                        <a class='page-link' href='$url' data-page='$i'>$i</a>
                      </li>";
            }
            echo '</ul></nav>';
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Welcome - Car Rental System'); ?></title>
    <?php include 'includes/header.php'; ?>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <?php include 'includes/navigation.php'; ?>

    <section class="hero-modern">
        <div class="hero-content container">
            <span class="hero-kicker"><?= __('Premium car rentals, made simple'); ?></span>
            <h1 class="display-5 fw-bold mb-3"><?= __('Find Your Perfect Ride'); ?></h1>
            <p class="lead mb-4"><?= __('Choose from our wide selection of cars for any occasion. Easy booking, great rates!'); ?></p>
            <div class="hero-actions d-grid gap-2 d-sm-flex justify-content-center">
                <a href="#available-cars" class="btn btn-primary btn-lg btn-elevated">
                    <i class="fas fa-car me-2" aria-hidden="true"></i><?= __('Browse Cars'); ?>
                </a>
                <?php if (!is_user_logged_in()): ?>
                    <a href="register.php" class="btn btn-soft btn-lg">
                        <i class="fas fa-user-plus me-2" aria-hidden="true"></i><?= __('Sign Up'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div id="available-cars" class="container section">
        <div class="section-header">
            <h2 class="section-title"><?= __('Available Cars'); ?></h2>
        </div>

        <div class="filter-bar mb-4">
            <form id="filterForm" class="row g-3">
                <div class="col-md-4">
                    <label for="searchInput" class="form-label"><?= __('Search'); ?></label>
                    <input type="text" name="search" id="searchInput" class="form-control" placeholder="<?= __('Search cars...'); ?>"
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" autocomplete="off" aria-label="<?= __('Search cars'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="typeSelect" class="form-label"><?= __('Type'); ?></label>
                    <select name="type" id="typeSelect" class="form-select" aria-label="<?= __('Filter by type'); ?>">
                        <option value=""><?= __('All Types'); ?></option>
                        <?php foreach ($types_array as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>"
                                <?php echo (isset($_GET['type']) && $_GET['type'] === $type) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= __('Price Range'); ?></label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" name="min_price" id="minPriceInput" class="form-control"
                                   placeholder="<?= __('Min Price'); ?>"
                                   min="<?php echo floor($price_range['min_price']); ?>"
                                   max="<?php echo ceil($price_range['max_price']); ?>"
                                   value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>" autocomplete="off" aria-label="<?= __('Minimum price'); ?>">
                        </div>
                        <div class="col-6">
                            <input type="number" name="max_price" id="maxPriceInput" class="form-control"
                                   placeholder="<?= __('Max Price'); ?>"
                                   min="<?php echo floor($price_range['min_price']); ?>"
                                   max="<?php echo ceil($price_range['max_price']); ?>"
                                   value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>" autocomplete="off" aria-label="<?= __('Maximum price'); ?>">
                        </div>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100" title="<?= __('Reset Filters'); ?>" aria-label="<?= __('Reset Filters'); ?>">
                        <i class="fas fa-redo" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>

        <div id="results">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger text-center modern-card">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php if ($available_cars && $available_cars->num_rows > 0): ?>
                        <?php while ($car = $available_cars->fetch_assoc()): ?>
                            <div class="col">
                                <div class="card car-card modern-card h-100">
                                    <img src="<?php echo !empty($car['car_image']) ? $car['car_image'] : 'assets/img/cars/default.jpg'; ?>"
                                         class="card-img-top"
                                         alt="<?php echo htmlspecialchars($car['car_name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($car['car_name']); ?></h5>
                                        <div class="car-meta mb-3">
                                            <span class="badge bg-primary"><i class="fas fa-car me-1" aria-hidden="true"></i> <?php echo htmlspecialchars($car['car_type']); ?></span>
                                            <span class="badge-soft success"><?= __('Available'); ?></span>
                                        </div>
                                        <div class="car-price h5 mb-3">
                                            DT. <?php echo number_format($car['price'], 0,0); ?> <small class="text-muted"><?= __('/day'); ?></small>
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
                        <div class="col-12">
                            <div class="alert alert-info text-center modern-card">
                                <i class="fas fa-car-side fa-3x mb-3" aria-hidden="true"></i>
                                <p class="mb-0"><?= __('No cars available matching your criteria'); ?>.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4 d-flex justify-content-center">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++):
                                $active = $page === $i ? 'active' : '';
                                $url = "?page=$i"
                                    . (!empty($_GET['search'])    ? '&search='    . urlencode($_GET['search'])    : '')
                                    . (!empty($_GET['type'])      ? '&type='      . urlencode($_GET['type'])      : '')
                                    . (!empty($_GET['min_price']) ? '&min_price=' . urlencode($_GET['min_price']) : '')
                                    . (!empty($_GET['max_price']) ? '&max_price=' . urlencode($_GET['max_price']) : '');
                            ?>
                                <li class="page-item <?php echo $active; ?>">
                                    <a 
                                    class="page-link" 
                                    href="<?php echo $url; ?>" 
                                    data-page="<?php echo $i; ?>"
                                    >
                                    <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('filterForm');
        const results = document.getElementById('results');
        const resetBtn = document.getElementById('resetFilters');

        function debounce(func, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        function fetchResults(page = 1) {
            const data = new FormData(form);
            data.set('page', page);
            const params = new URLSearchParams(data).toString();

            fetch('index?' + params, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                results.innerHTML = html;
                history.replaceState(null, '', 'index?' + params);

                results.querySelectorAll('.pagination a.page-link').forEach(link => {
                    link.addEventListener('click', e => {
                        e.preventDefault();
                        const newPage = parseInt(link.dataset.page);
                        if (newPage) fetchResults(newPage);
                    });
                });
            })
            .catch(console.error);
        }

        const debouncedFetch = debounce(() => fetchResults(), 300);
        form.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', debouncedFetch);
            el.addEventListener('change', debouncedFetch);
        });

        resetBtn.addEventListener('click', () => {
            form.reset();
            fetchResults(1);
        });

        results.querySelectorAll('.pagination a.page-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (newPage) fetchResults(newPage);
            });
        });
    });
    </script>
</body>
</html>

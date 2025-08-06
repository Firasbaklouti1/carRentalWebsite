<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php"><?= __('Car Rental System'); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php"><?= __('Home'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'cars.php' ? 'active' : ''; ?>" href="cars.php"><?= __('Cars'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'about.php' ? 'active' : ''; ?>" href="about.php"><?= __('About'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'contact.php' ? 'active' : ''; ?>" href="contact.php"><?= __('Contact'); ?></a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'my_bookings.php' ? 'active' : ''; ?>" href="my_bookings.php"><?= __('My Bookings'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><?= __('Logout'); ?></a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'login.php' ? 'active' : ''; ?>" href="login.php"><?= __('Login'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'register.php' ? 'active' : ''; ?>" href="register.php"><?= __('Register'); ?></a>
                    </li>
                <?php endif; ?>

                <!-- Language Switcher -->
                <li class="nav-item">
                    <a class="nav-link <?= ($_SESSION['lang'] ?? 'fr') === 'en' ? 'active' : ''; ?>" href="?lang=en">English</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'active' : ''; ?>" href="?lang=fr">Français</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
include_once 'init.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-car-side me-2"></i><?= __('Car Rental'); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i><?= __('Home'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'cars.php' ? 'active' : ''; ?>" href="cars.php">
                        <i class="fas fa-car me-1"></i><?= __('Cars'); ?>
                    </a>
                </li>
                <?php if (is_user_logged_in()): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'my_bookings.php' ? 'active' : ''; ?>" href="my_bookings.php">
                        <i class="fas fa-calendar-alt me-1"></i><?= __('My Bookings'); ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>" href="about.php">
                        <i class="fas fa-info-circle me-1"></i><?= __('About'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                        <i class="fas fa-envelope me-1"></i><?= __('Contact'); ?>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (!is_user_logged_in() && !is_admin_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i><?= __('Login'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>" href="register.php">
                            <i class="fas fa-user-plus me-1"></i><?= __('Register'); ?>
                        </a>
                    </li>
                <?php else: ?>
                    <?php if (is_user_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['name'] ?? __('User')); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-id-card me-2"></i><?= __('Profile'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="my_bookings.php">
                                        <i class="fas fa-calendar-alt me-2"></i><?= __('My Bookings'); ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i><?= __('Logout'); ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (is_admin_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i><?= __('Admin Dashboard'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
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

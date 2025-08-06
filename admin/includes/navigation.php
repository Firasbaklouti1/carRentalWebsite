<?php require_once '../includes/init.php'; ?>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-car"></i> <?= __('CarRental Admin'); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i><?= __(' Dashboard'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'cars.php' ? 'active' : ''; ?>" href="cars.php">
                        <i class="fas fa-car"></i><?= __(' Cars'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>" href="bookings.php">
                        <i class="fas fa-calendar-check"></i> <?= __('Bookings'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'enquiries.php' ? 'active' : ''; ?>" href="enquiries.php">
                        <i class="fas fa-envelope"></i><?= __(' Enquiries'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" href="users.php">
                        <i class="fas fa-users"></i> <?= __('Users'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '';
                     ?>" href=<?php echo SITE_URL . "/index.php"?>>
                        <i class="fas fa-users"></i><?= __(' Back to Website'); ?>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i> <?= __('Profile'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> <?= __('Logout'); ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

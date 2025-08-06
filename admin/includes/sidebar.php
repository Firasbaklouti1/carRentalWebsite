<?php

$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php require_once '../includes/init.php'; ?>
<nav id="sidebar">
    <div>
        <h5><?= __('Car Rental Admin'); ?></h5>
        <p><?= __('Welcome,'); ?></p>
        <p><?= isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : __('Administrator'); ?></p>
    </div>
    
    <ul>
        <li>
            <a href="dashboard.php"><?= __('Dashboard'); ?></a>
        </li>
        <li>
            <a href="cars.php"><?= __('Manage Cars'); ?></a>
        </li>
        <li>
            <a href="bookings.php"><?= __('Manage Bookings'); ?></a>
        </li>
        <li>
            <a href="users.php"><?= __('Manage Users'); ?></a>
        </li>
        <li>
            <a href="../logout.php"><?= __('Logout'); ?></a>
        </li>
    </ul>
</nav>

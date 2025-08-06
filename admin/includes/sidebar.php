<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar">
    <div>
        <h5>Car Rental Admin</h5>
        <p>Welcome,</p>
        <p><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Administrator'; ?></p>
    </div>
    
    <ul>
        <li>
            <a href="dashboard.php">Dashboard</a>
        </li>
        <li>
            <a href="cars.php">Manage Cars</a>
        </li>
        <li>
            <a href="bookings.php">Manage Bookings</a>
        </li>
        <li>
            <a href="users.php">Manage Users</a>
        </li>
        <li>
            <a href="../logout.php">Logout</a>
        </li>
    </ul>
</nav>

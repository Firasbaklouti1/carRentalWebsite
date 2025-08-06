<?php
require_once 'config.php';
require_once 'auth.php';

// Function to redirect with message
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $url");
    exit;
}

// Function to display message
function display_message() {
    $output = '';
    if (isset($_SESSION['message'])) {
        $type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
        $output = '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        $output .= $_SESSION['message'];
        $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $output .= '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
    return $output;
}

// Function to generate random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

// Function to format date
function format_date($date) {
    if (empty($date)) {
        return '';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format('M d, Y');
    } catch (Exception $e) {
        error_log("Date formatting error: " . $e->getMessage());
        return $date;
    }
}

// Function to calculate rental duration
function calculate_rental_days($pickup_date, $return_date) {
    try {
        $pickup = new DateTime($pickup_date);
        $return = new DateTime($return_date);
        $interval = $pickup->diff($return);
        return $interval->days + 1; // Including both pickup and return days
    } catch (Exception $e) {
        error_log("Rental days calculation error: " . $e->getMessage());
        return 0;
    }
}

// Function to calculate total rental cost
function calculate_rental_cost($price_per_day, $pickup_date, $return_date) {
    $days = calculate_rental_days($pickup_date, $return_date);
    return $price_per_day * $days;
}

// Function to get status badge class
function get_status_badge_class($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'warning';
        case 'confirmed':
            return 'success';
        case 'cancelled':
            return 'danger';
        case 'completed':
            return 'info';
        case 'active':
            return 'success';
        case 'inactive':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<?php
require_once '../includes/auth.php';
logout('admin');
header('Location: ' . SITE_URL . '/login.php');
exit;
?>

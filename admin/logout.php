<?php
require_once '../includes/auth.php';
logout('admin');
header('Location: login.php');
exit;
?>

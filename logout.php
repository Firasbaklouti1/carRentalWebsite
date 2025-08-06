<?php
require_once 'includes/auth.php';
logout('user');
header('Location: index.php');
exit;
?>
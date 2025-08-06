<?php

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'fr';

// Get absolute project root (assuming init.php is in /includes)
$basePath = dirname(__DIR__);
$translations = include $basePath . "/lang/$lang.php";

function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

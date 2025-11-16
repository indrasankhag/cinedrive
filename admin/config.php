<?php
// ===================
// DATABASE CONFIGURATION
// ===================

// admin/config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // XAMPP වල default username එක
define('DB_PASS', '');     // XAMPP වල default password එක හිස්
define('DB_NAME', 'cinedrive'); // ඔයා phpMyAdmin වල හදපු නම

// --- No need to edit below this line ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // If you see this message, your credentials are wrong.
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function check_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

?>
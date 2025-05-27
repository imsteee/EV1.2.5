<?php
/**
 * Configuration file
 *
 * This file contains global configuration settings for the application.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Application configuration
define('APP_NAME', 'ElectroCharge');
define('APP_URL', 'http://localhost/ev-charging-system');
define('ADMIN_EMAIL', 'admin@example.com');

// Set default timezone
date_default_timezone_set('UTC');

// Error reporting settings
// In production, set error_reporting to E_ALL and display_errors to 0
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes/');
define('PAGES_PATH', ROOT_PATH . '/pages/');
define('ASSETS_PATH', ROOT_PATH . '/assets/');

// Include database connection
require_once ROOT_PATH . '/config/database.php';

// Include utility functions
require_once INCLUDES_PATH . 'functions.php';

// Initialize application settings
$settings = [
    'booking_duration_minutes' => 60, // Default booking duration in minutes
    'booking_expiry_minutes' => 10,   // Minutes after which a booking expires if user doesn't show up
    'price_per_kwh' => 0.35,          // Price per kWh in currency
    'min_booking_interval' => 30,     // Minimum booking interval in minutes
];

// User session handling
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
        setFlashMessage('error', 'You do not have permission to access that page.');
        redirect('index.php');
    }
}

function logout() {
    session_unset();
    session_destroy();
    redirect('login.php');
}

// Redirect function
function redirect($page) {
    header("Location: " . APP_URL . "/" . $page);
    exit;
}

// Flash messaging system
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// CSRF protection
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
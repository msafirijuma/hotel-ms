<?php
// Database Connection - Hotel Management System

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL & ~E_NOTICE);

// Prevent direct access to this file
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: ../index.php");
    exit();
}

// Make this file more secure
// if (!defined('SECURE_ACCESS')) {
//     die('You are using unsecure connection.');
// } else {
//     define('SECURE_ACCESS', true);
// }

// Set error reporting level
ini_set('display_errors', 1);

// Autoload dependencies using Composer
// require_once __DIR__ . '/../vendor/autoload.php';

// Import necessary classes
use PHPMailer\PHPMailer\PHPMailer;

// Start session to track user activities
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Set default timezone
date_default_timezone_set('Africa/Nairobi');

// Function to get the base URL dynamically
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($protocol . $host . $script, '/') . '/';
}

// Define base URL constant
define('BASE_URL', get_base_url());

// MySQL Database Configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Badilisha kama una password
$database = 'hotel_db'; // Jina la database yako

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4 for Swahili support
mysqli_set_charset($conn, "utf8mb4");

// Function to log user activity
function log_activity($conn, $action, $details = '', $reference = '') {
    if (!isset($_SESSION['user_id'])) return;
    
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, reference, ip_address) VALUES (?, ?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->bind_param("issss", $_SESSION['user_id'], $action, $details, $reference, $ip);
    $stmt->execute();
    $stmt->close();
}

// Function to log booking-specific activity
function log_booking_activity($conn, $action, $booking_code, $details = '') {
    if (!isset($_SESSION['user_id'])) return;
    
    $reference = $booking_code;
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, reference, ip_address) VALUES (?, ?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->bind_param("issss", $_SESSION['user_id'], $action, $details, $reference, $ip);
    $stmt->execute();
    $stmt->close();
}

// Helper Functions - Wrapped to prevent redeclaration
if (!function_exists('run_query')) {
    function run_query($query) {
        global $conn;
        $result = mysqli_query($conn, $query);
        if (!$result) {
            error_log("Query Error: " . mysqli_error($conn) . " | Query: " . $query);
        }
        return $result;
    }
}

// Additional helper functions
if (!function_exists('escape_string')) {
    function escape_string($string) {
        global $conn;
        return mysqli_real_escape_string($conn, trim($string));
    }
}

// Fetch single value
if (!function_exists('get_single_value')) {
    function get_single_value($query) {
        $result = run_query($query);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return reset($row); // Return first value
        }
        return null;
    }
}

// Fetch single row
if (!function_exists('get_row')) {
    function get_row($query) {
        $result = run_query($query);
        return $result ? mysqli_fetch_assoc($result) : null;
    }
}

// Fetch all rows
if (!function_exists('get_all_rows')) {
    function get_all_rows($query) {
        $result = run_query($query);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
}

// Optional: Close connection at script end (PHP does it automatically)
// register_shutdown_function(function() {
//     global $conn;
//     mysqli_close($conn);
// });
?>
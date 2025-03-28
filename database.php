<?php
// Database configuration
$host = 'localhost';
$username = 'root';  // Default XAMPP username
$password = '';      // Default XAMPP password
$database = 'flixnate';

// Create connection with error reporting
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?> 
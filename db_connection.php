<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'flixnate');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Function to safely escape strings
function db_escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}

// Function to execute a query and return result
function db_query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if ($result === false) {
        error_log("Query failed: " . mysqli_error($conn));
        return false;
    }
    return $result;
}

// Function to get a single row
function db_fetch($result) {
    return mysqli_fetch_assoc($result);
}

// Function to get number of rows
function db_num_rows($result) {
    return mysqli_num_rows($result);
}

// Function to get last inserted ID
function db_last_id() {
    global $conn;
    return mysqli_insert_id($conn);
}

// Function to begin transaction
function db_begin_transaction() {
    global $conn;
    return mysqli_begin_transaction($conn);
}

// Function to commit transaction
function db_commit() {
    global $conn;
    return mysqli_commit($conn);
}

// Function to rollback transaction
function db_rollback() {
    global $conn;
    return mysqli_rollback($conn);
}

// Function to close database connection
function db_close() {
    global $conn;
    if ($conn) {
        mysqli_close($conn);
    }
}

// Register shutdown function to close database connection
register_shutdown_function('db_close'); 
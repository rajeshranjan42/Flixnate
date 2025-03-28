<?php
require_once '../includes/db_connection.php';

// Create database if it doesn't exist
$create_db_query = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $create_db_query)) {
    echo "Database created successfully or already exists.<br>";
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
mysqli_select_db($conn, DB_NAME);

// Read and execute the SQL file
$sql_file = file_get_contents('../database/setup_tables.sql');
$queries = explode(';', $sql_file);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (mysqli_query($conn, $query)) {
            echo "Query executed successfully.<br>";
        } else {
            echo "Error executing query: " . mysqli_error($conn) . "<br>";
        }
    }
}

echo "Database setup completed!";
?> 
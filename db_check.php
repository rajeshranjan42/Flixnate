<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking database connection...\n";

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'flixnate';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to MySQL server successfully!\n";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$db_name'");
    if ($result->num_rows == 0) {
        echo "Database '$db_name' does not exist!\n";
        echo "Creating database...\n";
        
        if ($conn->query("CREATE DATABASE IF NOT EXISTS $db_name")) {
            echo "Database created successfully!\n";
            
            // Select the database
            $conn->select_db($db_name);
            
            // Create tables
            $tables = [
                "CREATE TABLE IF NOT EXISTS content (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    release_year INT,
                    duration INT,
                    thumbnail VARCHAR(255),
                    video_path VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                "CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(50) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                "CREATE TABLE IF NOT EXISTS content_categories (
                    content_id INT,
                    category_id INT,
                    PRIMARY KEY (content_id, category_id),
                    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                )"
            ];
            
            foreach ($tables as $sql) {
                if ($conn->query($sql)) {
                    echo "Table created successfully!\n";
                } else {
                    echo "Error creating table: " . $conn->error . "\n";
                }
            }
        } else {
            echo "Error creating database: " . $conn->error . "\n";
        }
    } else {
        echo "Database '$db_name' exists!\n";
        
        // Select the database
        $conn->select_db($db_name);
        
        // Check tables
        $required_tables = ['content', 'categories', 'content_categories'];
        foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "Table '$table' exists!\n";
                
                // Show table structure
                $result = $conn->query("DESCRIBE $table");
                echo "\nStructure of table '$table':\n";
                while ($row = $result->fetch_assoc()) {
                    print_r($row);
                }
                echo "\n";
            } else {
                echo "Table '$table' does not exist!\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 
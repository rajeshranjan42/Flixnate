<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting database check...\n";

require_once 'includes/db_connection.php';

if (!isset($conn)) {
    die("Database connection not established!\n");
}

echo "Database connection successful!\n";

function checkTable($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    if (!$result) {
        echo "Error checking table $tableName: " . mysqli_error($conn) . "\n";
        return false;
    }
    return mysqli_num_rows($result) > 0;
}

function checkTableStructure($conn, $tableName) {
    $result = mysqli_query($conn, "DESCRIBE $tableName");
    if (!$result) {
        return "Error getting structure for $tableName: " . mysqli_error($conn);
    }
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row;
    }
    return $columns;
}

$requiredTables = ['content', 'categories', 'content_categories'];
$missingTables = [];
$tableStructures = [];

foreach ($requiredTables as $table) {
    echo "\nChecking table: $table\n";
    if (!checkTable($conn, $table)) {
        $missingTables[] = $table;
        echo "Table $table is missing!\n";
    } else {
        echo "Table $table exists, checking structure...\n";
        $tableStructures[$table] = checkTableStructure($conn, $table);
    }
}

echo "\nMissing Tables:\n";
print_r($missingTables);

echo "\nTable Structures:\n";
print_r($tableStructures);
?> 
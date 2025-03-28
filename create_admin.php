<?php
require_once '../config/database.php';

try {
    // Create admins table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (mysqli_query($conn, $create_table_sql)) {
        echo "<div style='background: #1E1E1E; color: #fff; padding: 20px; margin: 20px; border-radius: 8px;'>";
        echo "<h2 style='color: #8B5CF6; margin-bottom: 15px;'>Setup Status</h2>";
        echo "<p style='color: #4BB543; margin-bottom: 10px;'>✓ Admins table created successfully.</p>";

        // Check if default admin exists
        $check_admin = "SELECT id FROM admins WHERE username = 'admin'";
        $result = mysqli_query($conn, $check_admin);

        if (mysqli_num_rows($result) == 0) {
            // Create default admin user
            $username = 'admin';
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $email = 'admin@flixnate.com';

            $insert_admin = "INSERT INTO admins (username, password, email) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_admin);
            mysqli_stmt_bind_param($stmt, "sss", $username, $password, $email);

            if (mysqli_stmt_execute($stmt)) {
                echo "<p style='color: #4BB543; margin-bottom: 10px;'>✓ Default admin user created successfully.</p>";
                echo "<div style='background: #2a2a2a; padding: 15px; border-radius: 6px; margin-top: 20px;'>";
                echo "<h3 style='color: #EC4899; margin-bottom: 10px;'>Default Admin Credentials</h3>";
                echo "<p style='margin-bottom: 5px;'><strong>Username:</strong> admin</p>";
                echo "<p style='margin-bottom: 5px;'><strong>Password:</strong> admin123</p>";
                echo "<p style='color: #ff4757; margin-top: 15px;'>⚠️ Please change these credentials immediately after logging in!</p>";
                echo "</div>";
                echo "<a href='login.php' style='display: inline-block; margin-top: 20px; background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%); color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;'>Go to Login</a>";
            } else {
                throw new Exception("Error creating admin user: " . mysqli_error($conn));
            }
        } else {
            echo "<p style='color: #3498db;'>ℹ️ Admin user already exists.</p>";
            echo "<a href='login.php' style='display: inline-block; margin-top: 20px; background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%); color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;'>Go to Login</a>";
        }
        echo "</div>";
    } else {
        throw new Exception("Error creating table: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    echo "<div style='background: #1E1E1E; color: #fff; padding: 20px; margin: 20px; border-radius: 8px;'>";
    echo "<h2 style='color: #ff4757; margin-bottom: 15px;'>Setup Error</h2>";
    echo "<p style='color: #ff4757;'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - Flixnate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #121212;
            margin: 0;
            padding: 20px;
            color: #fff;
        }
    </style>
</head>
<body>
</body>
</html> 
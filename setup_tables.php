<?php
require_once '../config/database.php';

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// Function to create a table
function createTable($conn, $sql) {
    if (!mysqli_query($conn, $sql)) {
        return "Error creating table: " . mysqli_error($conn);
    }
    return true;
}

// Check and create required tables
$required_tables = [
    'users' => "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT '1',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    'content' => "CREATE TABLE IF NOT EXISTS `content` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `description` text,
        `thumbnail` varchar(255),
        `video_path` varchar(255),
        `duration` int(11) DEFAULT NULL,
        `release_year` int(4) DEFAULT NULL,
        `is_featured` tinyint(1) NOT NULL DEFAULT '0',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    'site_settings' => "CREATE TABLE IF NOT EXISTS `site_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `site_name` varchar(100) NOT NULL DEFAULT 'Flixnate',
        `site_description` text,
        `contact_email` varchar(100),
        `items_per_page` int(11) NOT NULL DEFAULT 10,
        `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    'user_ratings' => "CREATE TABLE IF NOT EXISTS `user_ratings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `content_id` int(11) NOT NULL,
        `rating` int(11) NOT NULL CHECK (rating >= 1 AND rating <= 5),
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_user_content_rating` (`user_id`, `content_id`),
        KEY `content_id` (`content_id`),
        CONSTRAINT `user_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `user_ratings_ibfk_2` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    'user_comments' => "CREATE TABLE IF NOT EXISTS `user_comments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `content_id` int(11) NOT NULL,
        `comment` text NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `content_id` (`content_id`),
        CONSTRAINT `user_comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `user_comments_ibfk_2` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    'watchlist' => "CREATE TABLE IF NOT EXISTS `watchlist` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `content_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_user_content_watchlist` (`user_id`, `content_id`),
        KEY `content_id` (`content_id`),
        CONSTRAINT `watchlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `watchlist_ibfk_2` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

$success = true;
$errors = [];

// Disable foreign key checks temporarily
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Drop and recreate content table to ensure all columns exist
mysqli_query($conn, "DROP TABLE IF EXISTS content");
$result = createTable($conn, $required_tables['content']);
if ($result !== true) {
    $success = false;
    $errors[] = "Error recreating content table: " . $result;
}

// Create required tables if they don't exist
foreach ($required_tables as $table => $sql) {
    if ($table !== 'content' && !tableExists($conn, $table)) {
        $result = createTable($conn, $sql);
        if ($result !== true) {
            $success = false;
            $errors[] = "Error creating $table table: " . $result;
        }
    }
}

// Check if is_active column exists in users table
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_active'");
if (mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE users ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1 AFTER password";
    if (!mysqli_query($conn, $alter_query)) {
        $success = false;
        $errors[] = "Error adding is_active column to users table: " . mysqli_error($conn);
    }
}

// Update existing users to have is_active = 1
$update_query = "UPDATE users SET is_active = 1 WHERE is_active IS NULL";
if (!mysqli_query($conn, $update_query)) {
    $success = false;
    $errors[] = "Error updating existing users: " . mysqli_error($conn);
}

// Insert default site settings if the table is empty
if (tableExists($conn, 'site_settings')) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM site_settings");
    $row = mysqli_fetch_assoc($result);
    if ($row['count'] == 0) {
        $default_settings = "INSERT INTO site_settings (site_name, site_description, contact_email, items_per_page, maintenance_mode) 
                           VALUES ('Flixnate', 'Your Ultimate Streaming Platform', 'admin@flixnate.com', 10, 0)";
        if (!mysqli_query($conn, $default_settings)) {
            $success = false;
            $errors[] = "Error inserting default site settings: " . mysqli_error($conn);
        }
    }
}

// Check if thumbnail column exists in content table
$result = mysqli_query($conn, "SHOW COLUMNS FROM content LIKE 'thumbnail'");
if (mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE content ADD COLUMN thumbnail varchar(255) AFTER description";
    if (!mysqli_query($conn, $alter_query)) {
        $success = false;
        $errors[] = "Error adding thumbnail column to content table: " . mysqli_error($conn);
    }
}

// Check if video_path column exists in content table
$result = mysqli_query($conn, "SHOW COLUMNS FROM content LIKE 'video_path'");
if (mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE content ADD COLUMN video_path varchar(255) AFTER thumbnail";
    if (!mysqli_query($conn, $alter_query)) {
        $success = false;
        $errors[] = "Error adding video_path column to content table: " . mysqli_error($conn);
    }
}

// Check if duration column exists in content table
$result = mysqli_query($conn, "SHOW COLUMNS FROM content LIKE 'duration'");
if (mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE content ADD COLUMN duration int(11) DEFAULT NULL AFTER video_path";
    if (!mysqli_query($conn, $alter_query)) {
        $success = false;
        $errors[] = "Error adding duration column to content table: " . mysqli_error($conn);
    }
}

// Check if release_year column exists in content table
$result = mysqli_query($conn, "SHOW COLUMNS FROM content LIKE 'release_year'");
if (mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE content ADD COLUMN release_year int(4) DEFAULT NULL AFTER duration";
    if (!mysqli_query($conn, $alter_query)) {
        $success = false;
        $errors[] = "Error adding release_year column to content table: " . mysqli_error($conn);
    }
}

// Check if is_featured column exists in content table
$result = mysqli_query($conn, "SHOW COLUMNS FROM content LIKE 'is_featured'");
if (mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE content ADD COLUMN is_featured tinyint(1) NOT NULL DEFAULT '0' AFTER video_path";
    if (!mysqli_query($conn, $alter_query)) {
        $success = false;
        $errors[] = "Error adding is_featured column to content table: " . mysqli_error($conn);
    }
}

// Re-enable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

// Output results with better formatting
echo "<html><head><style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; }
    .error { color: red; }
    .error-details { background: #f8f8f8; padding: 15px; border-radius: 5px; margin-top: 10px; }
</style></head><body>";

if ($success) {
    echo "<div class='success'>All tables created successfully!</div>";
    
    // Verify all required tables exist
    echo "<div style='margin-top: 20px;'>Verifying tables:</div>";
    foreach ($required_tables as $table => $sql) {
        if (tableExists($conn, $table)) {
            echo "<div class='success'>✓ Table '$table' exists</div>";
        } else {
            echo "<div class='error'>✗ Table '$table' was not created</div>";
        }
    }
    
    // Add links to go back to admin pages
    echo "<div style='margin-top: 20px;'>
        <a href='users.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>
            Go to Users Page
        </a>
        <a href='settings.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>
            Go to Settings Page
        </a>
    </div>";
} else {
    echo "<div class='error'>Error creating tables:</div>";
    echo "<div class='error-details'>";
    foreach ($errors as $error) {
        echo "- " . $error . "<br>";
    }
    echo "</div>";
}

echo "</body></html>";
?> 
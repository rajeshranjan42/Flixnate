<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if site_settings table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'site_settings'");
if (mysqli_num_rows($result) == 0) {
    // Create site_settings table
    $create_table = "CREATE TABLE IF NOT EXISTS `site_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `site_name` varchar(100) NOT NULL DEFAULT 'Flixnate',
        `site_description` text,
        `contact_email` varchar(100),
        `items_per_page` int(11) NOT NULL DEFAULT 10,
        `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!mysqli_query($conn, $create_table)) {
        die("Error creating site_settings table: " . mysqli_error($conn));
    }
    
    // Insert default settings
    $default_settings = "INSERT INTO site_settings (site_name, site_description, contact_email, items_per_page, maintenance_mode) 
                        VALUES ('Flixnate', 'Your Ultimate Streaming Platform', 'admin@flixnate.com', 10, 0)";
    if (!mysqli_query($conn, $default_settings)) {
        die("Error inserting default settings: " . mysqli_error($conn));
    }
}

$error = '';
$success = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_site_settings'])) {
        $site_name = trim(mysqli_real_escape_string($conn, $_POST['site_name']));
        $site_description = trim(mysqli_real_escape_string($conn, $_POST['site_description']));
        $contact_email = trim(mysqli_real_escape_string($conn, $_POST['contact_email']));
        $items_per_page = (int)$_POST['items_per_page'];
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        $update_query = "UPDATE site_settings SET 
                        site_name = ?,
                        site_description = ?,
                        contact_email = ?,
                        items_per_page = ?,
                        maintenance_mode = ?
                        WHERE id = 1";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssiii", $site_name, $site_description, $contact_email, $items_per_page, $maintenance_mode);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Site settings updated successfully!";
        } else {
            $error = "Error updating site settings: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['update_admin_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password === $confirm_password) {
            // Verify current password
            $admin_id = $_SESSION['admin_id'];
            $check_query = "SELECT password FROM admins WHERE id = ?";
            $stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($stmt, "i", $admin_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);
            
            if (password_verify($current_password, $admin['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE admins SET password = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $admin_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Password updated successfully!";
                } else {
                    $error = "Error updating password: " . mysqli_error($conn);
                }
            } else {
                $error = "Current password is incorrect!";
            }
        } else {
            $error = "New passwords do not match!";
        }
    }
}

// Get current settings
$settings_query = "SELECT * FROM site_settings WHERE id = 1";
$settings = mysqli_fetch_assoc(mysqli_query($conn, $settings_query));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Flixnate Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .settings-section {
            background: #1E1E1E;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .settings-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #fff;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #fff;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group input[type="password"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #333;
            background: #2a2a2a;
            color: #fff;
            font-size: 16px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }

        .btn-container {
            margin-top: 20px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-primary {
            background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%);
            color: white;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #4BB543;
            color: white;
        }

        .alert-error {
            background: #ff4757;
            color: white;
        }

        .settings-info {
            color: #9B9B9B;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body class="dark-theme">
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <h1>Settings</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="settings-section">
            <h2>Site Settings</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" 
                           value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="site_description">Site Description</label>
                    <textarea id="site_description" name="site_description" required><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" 
                           value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="items_per_page">Items Per Page</label>
                    <input type="number" id="items_per_page" name="items_per_page" 
                           value="<?php echo htmlspecialchars($settings['items_per_page']); ?>" 
                           min="10" max="50" required>
                    <div class="settings-info">Number of items to display per page in listings (10-50)</div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                               <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                        <label for="maintenance_mode">Maintenance Mode</label>
                    </div>
                    <div class="settings-info">When enabled, only administrators can access the site</div>
                </div>

                <div class="btn-container">
                    <button type="submit" name="update_site_settings" class="btn btn-primary">
                        Update Site Settings
                    </button>
                </div>
            </form>
        </div>

        <div class="settings-section">
            <h2>Change Admin Password</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="btn-container">
                    <button type="submit" name="update_admin_password" class="btn btn-primary">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 
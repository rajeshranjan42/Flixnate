<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Get statistics
$stats = [
    'total_content' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM content"))['count'],
    'total_categories' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM categories"))['count'],
    'total_users' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count']
];

// Get recent content
$recent_content = mysqli_query($conn, "SELECT * FROM content ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Flixnate</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #1E1E1E;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card h3 {
            color: #9B9B9B;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }

        .content-section {
            background: #1E1E1E;
            padding: 20px;
            border-radius: 8px;
        }

        .content-section h2 {
            margin-bottom: 20px;
            color: #fff;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
        }

        .content-table th,
        .content-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        .content-table th {
            color: #9B9B9B;
        }

        .content-table td {
            color: #fff;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: #fff;
            font-size: 14px;
        }

        .edit-btn {
            background: #3498db;
        }

        .delete-btn {
            background: #e74c3c;
        }

        .add-btn {
            background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%);
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            color: #fff;
            font-weight: 500;
        }
    </style>
</head>
<body class="dark-theme">
<?php include 'includes/header.php'; ?>
    <div class="admin-container">

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Content</h3>
                <div class="number"><?php echo $stats['total_content']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Categories</h3>
                <div class="number"><?php echo $stats['total_categories']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Users</h3>
                <div class="number"><?php echo $stats['total_users']; ?></div>
            </div>
        </div>

        <div class="content-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Recent Content</h2>
                <a href="add-content.php" class="add-btn">Add New Content</a>
            </div>
            <table class="content-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Release Year</th>
                        <th>Duration</th>
                        <th>Added Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($content = mysqli_fetch_assoc($recent_content)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($content['title']); ?></td>
                            <td><?php echo htmlspecialchars($content['release_year']); ?></td>
                            <td><?php echo htmlspecialchars($content['duration']); ?> min</td>
                            <td><?php echo date('Y-m-d', strtotime($content['created_at'])); ?></td>
                            <td>
                                <a href="edit-content.php?id=<?php echo $content['id']; ?>" class="action-btn edit-btn">Edit</a>
                                <a href="delete-content.php?id=<?php echo $content['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this content?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 
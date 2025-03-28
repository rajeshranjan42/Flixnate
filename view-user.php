<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get user ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user_id = (int)$_GET['id'];

// Get user details with activity counts
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM watchlist WHERE user_id = u.id) as watchlist_count,
          (SELECT COUNT(*) FROM user_ratings WHERE user_id = u.id) as ratings_count,
          (SELECT COUNT(*) FROM user_comments WHERE user_id = u.id) as comments_count
          FROM users u 
          WHERE u.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    header('Location: users.php');
    exit();
}

// Get user's recent activity (watchlist, ratings, comments)
$activity_query = "
    (SELECT 'watchlist' as type, c.title, c.thumbnail, w.created_at, NULL as rating, NULL as comment
     FROM watchlist w 
     JOIN content c ON w.content_id = c.id 
     WHERE w.user_id = ?)
    UNION ALL
    (SELECT 'rating' as type, c.title, c.thumbnail, r.created_at, r.rating, NULL as comment
     FROM user_ratings r 
     JOIN content c ON r.content_id = c.id 
     WHERE r.user_id = ?)
    UNION ALL
    (SELECT 'comment' as type, c.title, c.thumbnail, cm.created_at, NULL as rating, cm.comment
     FROM user_comments cm 
     JOIN content c ON cm.content_id = c.id 
     WHERE cm.user_id = ?)
    ORDER BY created_at DESC 
    LIMIT 10";

$stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$recent_activity = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - Flixnate Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .back-link {
            color: #9B9B9B;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .back-link:hover {
            color: #fff;
        }

        .user-info {
            background: #1E1E1E;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .user-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            background: #2a2a2a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #9B9B9B;
        }

        .user-details h2 {
            margin: 0;
            color: #fff;
        }

        .user-email {
            color: #9B9B9B;
            margin-top: 5px;
        }

        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #9B9B9B;
            font-size: 14px;
        }

        .activity-section {
            background: #1E1E1E;
            padding: 30px;
            border-radius: 8px;
        }

        .activity-list {
            margin-top: 20px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            border-bottom: 1px solid #333;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .content-thumbnail {
            width: 80px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
        }

        .activity-details {
            flex-grow: 1;
        }

        .activity-title {
            color: #fff;
            margin: 0 0 5px 0;
        }

        .activity-meta {
            color: #9B9B9B;
            font-size: 14px;
        }

        .activity-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .type-watchlist {
            background: #3498db;
            color: white;
        }

        .type-rating {
            background: #f1c40f;
            color: black;
        }

        .type-comment {
            background: #2ecc71;
            color: white;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: #4BB543;
            color: white;
        }

        .status-inactive {
            background: #ff4757;
            color: white;
        }
    </style>
</head>
<body class="dark-theme">
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1>User Details</h1>
            <a href="users.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>

        <div class="user-info">
            <div class="user-header">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    <div style="margin-top: 10px;">
                        <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="user-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user['watchlist_count']; ?></div>
                    <div class="stat-label">Watchlist Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user['ratings_count']; ?></div>
                    <div class="stat-label">Ratings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user['comments_count']; ?></div>
                    <div class="stat-label">Comments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></div>
                    <div class="stat-label">Join Date</div>
                </div>
            </div>
        </div>

        <div class="activity-section">
            <h2>Recent Activity</h2>
            <div class="activity-list">
                <?php while ($activity = mysqli_fetch_assoc($recent_activity)): ?>
                    <div class="activity-item">
                        <img src="<?php echo htmlspecialchars($activity['thumbnail'] ?? '../assets/images/default-thumbnail.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($activity['title']); ?>" 
                             class="content-thumbnail">
                        <div class="activity-details">
                            <h3 class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></h3>
                            <div class="activity-meta">
                                <span class="activity-type type-<?php echo $activity['type']; ?>">
                                    <?php echo ucfirst($activity['type']); ?>
                                </span>
                                <?php if ($activity['rating']): ?>
                                    - Rated <?php echo $activity['rating']; ?>/5
                                <?php endif; ?>
                                <?php if ($activity['comment']): ?>
                                    - "<?php echo htmlspecialchars(substr($activity['comment'], 0, 100)); ?>..."
                                <?php endif; ?>
                                <div style="margin-top: 5px;">
                                    <?php echo date('Y-m-d H:i', strtotime($activity['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 
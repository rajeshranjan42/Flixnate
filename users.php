<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if required tables exist and create them if they don't
$required_tables = ['users', 'user_ratings', 'watchlist'];
$missing_tables = [];

foreach ($required_tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) == 0) {
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    // Redirect to setup script if tables are missing
    header('Location: setup_tables.php');
    exit();
}

$error = '';
$success = '';

// Handle user status toggle (active/inactive)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];
    $status_query = "UPDATE users SET is_active = NOT is_active WHERE id = ?";
    $stmt = mysqli_prepare($conn, $status_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "User status updated successfully!";
    } else {
        $error = "Error updating user status: " . mysqli_error($conn);
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = $search ? "WHERE username LIKE '%$search%' OR email LIKE '%$search%'" : "";

// Get total users count
$count_query = "SELECT COUNT(*) as count FROM users $where_clause";
$total_count = mysqli_fetch_assoc(mysqli_query($conn, $count_query))['count'];
$total_pages = ceil($total_count / $itemsPerPage);

// Get users with pagination
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM watchlist WHERE user_id = u.id) as watchlist_count,
          (SELECT COUNT(*) FROM user_ratings WHERE user_id = u.id) as ratings_count,
          1 as is_active
          FROM users u 
          $where_clause
          ORDER BY u.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $itemsPerPage, $offset);
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Flixnate Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #333;
            background: #2a2a2a;
            color: #fff;
            width: 300px;
        }

        .users-table {
            width: 100%;
            background: #1E1E1E;
            border-radius: 8px;
            overflow: hidden;
        }

        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        .users-table th {
            background: #2a2a2a;
            color: #9B9B9B;
            font-weight: 500;
        }

        .users-table td {
            color: #fff;
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

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: #fff;
            font-size: 14px;
            margin-right: 5px;
            display: inline-block;
        }

        .toggle-btn {
            background: #3498db;
        }

        .view-btn {
            background: #8B5CF6;
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

        .stats-badge {
            background: #2a2a2a;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
        }
    </style>
</head>
<body class="dark-theme">
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <div class="users-header">
            <h1>Users Management</h1>
            <form class="search-form" method="GET">
                <input type="text" name="search" class="search-input" 
                       placeholder="Search by username or email" 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="users.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Activity</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="status-badge <?php echo isset($user['is_active']) && $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo isset($user['is_active']) && $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="stats-badge">
                                    <i class="fas fa-list"></i> <?php echo $user['watchlist_count']; ?> watchlist
                                </span>
                                <span class="stats-badge">
                                    <i class="fas fa-star"></i> <?php echo $user['ratings_count']; ?> ratings
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="?toggle_status=<?php echo $user['id']; ?>" 
                                   class="action-btn toggle-btn"
                                   onclick="return confirm('Are you sure you want to <?php echo isset($user['is_active']) && $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                                    <?php echo isset($user['is_active']) && $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                <a href="view-user.php?id=<?php echo $user['id']; ?>" class="action-btn view-btn">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">First</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);

                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       <?php echo $i === $page ? 'class="active"' : ''; ?>>
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Last</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
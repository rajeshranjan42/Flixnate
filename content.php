<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Get total content count
$total_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM content"))['count'];
$total_pages = ceil($total_count / $itemsPerPage);

// Get content with pagination
$query = "SELECT c.*, GROUP_CONCAT(cat.name) as categories 
          FROM content c 
          LEFT JOIN content_categories cc ON c.id = cc.content_id 
          LEFT JOIN categories cat ON cc.category_id = cat.id 
          GROUP BY c.id 
          ORDER BY c.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $itemsPerPage, $offset);
mysqli_stmt_execute($stmt);
$content = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - Flixnate Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .content-table {
            width: 100%;
            background: #1E1E1E;
            border-radius: 8px;
            overflow: hidden;
        }

        .content-table th,
        .content-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        .content-table th {
            background: #2a2a2a;
            color: #9B9B9B;
            font-weight: 500;
        }

        .content-table td {
            color: #fff;
        }

        .thumbnail-preview {
            width: 80px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
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

        .pagination {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .pagination a {
            padding: 8px 12px;
            background: #2a2a2a;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .pagination a:hover {
            background: #333;
        }

        .pagination a.active {
            background: #3498db;
        }

        .category-tags {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .category-tag {
            background: #2a2a2a;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: #2ecc71;
            color: #fff;
        }

        .alert.error {
            background: #e74c3c;
            color: #fff;
        }
    </style>
</head>
<body class="dark-theme">
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <div class="content-header">
            <h1>Content Management</h1>
            <a href="add-content.php" class="add-btn">Add New Content</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="content-table">
            <table>
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Title</th>
                        <th>Categories</th>
                        <th>Release Year</th>
                        <th>Duration</th>
                        <th>Added Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($content)): ?>
                        <tr data-content-id="<?php echo $item['id']; ?>">
                            <td>
                                <img src="<?php echo htmlspecialchars($item['thumbnail'] ?? '../assets/images/default-thumbnail.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="thumbnail-preview">
                            </td>
                            <td><?php echo htmlspecialchars($item['title']); ?></td>
                            <td>
                                <div class="category-tags">
                                    <?php if ($item['categories']): 
                                        $categories = explode(',', $item['categories']);
                                        foreach ($categories as $category): ?>
                                            <span class="category-tag"><?php echo htmlspecialchars(trim($category)); ?></span>
                                        <?php endforeach;
                                    else: ?>
                                        <span class="category-tag">Uncategorized</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($item['release_year'] ?? 'N/A'); ?></td>
                            <td><?php echo $item['duration'] ? htmlspecialchars($item['duration']) . ' min' : 'N/A'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($item['created_at'])); ?></td>
                            <td>
                                <a href="edit-content.php?id=<?php echo $item['id']; ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="handleDelete(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['title'])); ?>')" 
                                        class="action-btn delete-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1">First</a>
                    <a href="?page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);

                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" <?php echo $i === $page ? 'class="active"' : ''; ?>>
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next</a>
                    <a href="?page=<?php echo $total_pages; ?>">Last</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/admin.js"></script>
</body>
</html>
<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle category addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    
    if (!empty($name)) {
        $check_query = "SELECT id FROM categories WHERE name = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 0) {
            $insert_query = "INSERT INTO categories (name) VALUES (?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "s", $name);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Category added successfully!";
            } else {
                $error = "Error adding category: " . mysqli_error($conn);
            }
        } else {
            $error = "Category already exists!";
        }
    } else {
        $error = "Category name cannot be empty!";
    }
}

// Handle category deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if category is in use
    $check_query = "SELECT COUNT(*) as count FROM content_categories WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($result['count'] > 0) {
        $error = "Cannot delete category as it is being used by content!";
    } else {
        $delete_query = "DELETE FROM categories WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Category deleted successfully!";
        } else {
            $error = "Error deleting category: " . mysqli_error($conn);
        }
    }
}

// Get all categories with content count
$query = "SELECT c.*, COUNT(cc.content_id) as content_count 
          FROM categories c 
          LEFT JOIN content_categories cc ON c.id = cc.category_id 
          GROUP BY c.id 
          ORDER BY c.name ASC";
$categories = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Flixnate Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .category-card {
            background: #1E1E1E;
            border-radius: 8px;
            padding: 20px;
            position: relative;
        }

        .category-card h3 {
            color: #fff;
            margin: 0 0 10px 0;
        }

        .category-stats {
            color: #9B9B9B;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .category-actions {
            display: flex;
            gap: 10px;
        }

        .add-category-form {
            background: #1E1E1E;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #fff;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #333;
            background: #2a2a2a;
            color: #fff;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: opacity 0.3s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-primary {
            background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%);
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
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
    </style>
</head>
<body class="dark-theme">
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <h1>Categories Management</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="add-category-form">
            <h2>Add New Category</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
            </form>
        </div>

        <div class="categories-grid">
            <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                <div class="category-card">
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    <div class="category-stats">
                        <i class="fas fa-film"></i> <?php echo $category['content_count']; ?> content items
                    </div>
                    <div class="category-actions">
                        <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php if ($category['content_count'] == 0): ?>
                            <a href="?delete=<?php echo $category['id']; ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this category?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 
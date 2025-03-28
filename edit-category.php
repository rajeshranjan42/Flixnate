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
$category = null;

// Get category ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: categories.php');
    exit();
}

$category_id = (int)$_GET['id'];

// Handle category update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    
    if (!empty($name)) {
        // Check if name already exists for other categories
        $check_query = "SELECT id FROM categories WHERE name = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "si", $name, $category_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 0) {
            $update_query = "UPDATE categories SET name = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $name, $category_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Category updated successfully!";
            } else {
                $error = "Error updating category: " . mysqli_error($conn);
            }
        } else {
            $error = "Category name already exists!";
        }
    } else {
        $error = "Category name cannot be empty!";
    }
}

// Get category details
$query = "SELECT c.*, COUNT(cc.content_id) as content_count 
          FROM categories c 
          LEFT JOIN content_categories cc ON c.id = cc.category_id 
          WHERE c.id = ? 
          GROUP BY c.id";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$category = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$category) {
    header('Location: categories.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - Flixnate Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .edit-category-form {
            background: #1E1E1E;
            padding: 30px;
            border-radius: 8px;
            margin-top: 20px;
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

        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #333;
            background: #2a2a2a;
            color: #fff;
            font-size: 16px;
        }

        .category-stats {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #9B9B9B;
        }

        .btn-container {
            display: flex;
            gap: 15px;
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

        .btn-secondary {
            background: #4a4a4a;
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
    </style>
</head>
<body class="dark-theme">
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1>Edit Category</h1>
            <a href="categories.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="edit-category-form">
            <div class="category-stats">
                <i class="fas fa-film"></i> This category is used in <?php echo $category['content_count']; ?> content items
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                </div>
                <div class="btn-container">
                    <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                    <a href="categories.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 
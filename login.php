<?php
session_start();

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM admins WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Flixnate</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: #1E1E1E;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .admin-login-container h1 {
            text-align: center;
            color: #fff;
            margin-bottom: 30px;
        }

        .admin-login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            color: #fff;
            font-size: 16px;
        }

        .form-group input {
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #333;
            background: #2a2a2a;
            color: #fff;
            font-size: 16px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .error-message {
            color: #ff4757;
            text-align: center;
            margin-bottom: 20px;
        }

        .admin-login-btn {
            background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .admin-login-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body class="dark-theme">
    <div class="admin-login-container">
        <h1>Admin Login</h1>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form class="admin-login-form" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="admin-login-btn">Login</button>
        </form>
    </div>
</body>
</html> 
<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flixnate - Your Premium Streaming Experience</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/navigation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">
    <!-- Navigation -->
    <nav class="main-nav">
        <div class="nav-brand">
            <h1>Flixnate</h1>
        </div>
        <button class="mobile-menu-toggle" aria-label="Toggle menu">
            <span class="toggle-bar"></span>
            <span class="toggle-bar"></span>
            <span class="toggle-bar"></span>
        </button>
        <div class="nav-menu">
            <ul class="nav-list">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li class="has-dropdown">
                    <a href="movies.php"><i class="fas fa-film"></i> Movies</a>
                    <ul class="dropdown">
                        <li><a href="movies.php?genre=action">Action</a></li>
                        <li><a href="movies.php?genre=comedy">Comedy</a></li>
                        <li><a href="movies.php?genre=drama">Drama</a></li>
                        <li><a href="movies.php?genre=horror">Horror</a></li>
                        <li><a href="movies.php">View All</a></li>
                    </ul>
                </li>
                <li class="has-dropdown">
                    <a href="tv-shows.php"><i class="fas fa-tv"></i> TV Shows</a>
                    <ul class="dropdown">
                        <li><a href="tv-shows.php?genre=drama">Drama Series</a></li>
                        <li><a href="tv-shows.php?genre=comedy">Comedy Series</a></li>
                        <li><a href="tv-shows.php?genre=documentary">Documentary</a></li>
                        <li><a href="tv-shows.php">View All</a></li>
                    </ul>
                </li>
                <li><a href="categories.php"><i class="fas fa-list"></i> Categories</a></li>
                <li><a href="search.php"><i class="fas fa-search"></i> Search</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="has-dropdown user-menu">
                    <a href="#"><i class="fas fa-user"></i> Account</a>
                    <ul class="dropdown">
                        <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                        <li><a href="watchlist.php"><i class="fas fa-bookmark"></i> Watchlist</a></li>
                        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="menu-overlay"></div>

    <style>
    :root {
        --primary-color: #8B5CF6;
        --secondary-color: #EC4899;
        --dark-bg: #1E1E1E;
        --darker-bg: #2a2a2a;
        --text-color: #fff;
        --muted-text: #9B9B9B;
        --header-height: 70px;
    }

    .main-header {
        background: var(--dark-bg);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        height: var(--header-height);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .header-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .logo a {
        color: var(--primary-color);
        text-decoration: none;
        font-size: 1.5rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: color 0.3s;
    }

    .logo a:hover {
        color: var(--secondary-color);
    }

    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: var(--text-color);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 8px;
    }

    .main-nav {
        display: flex;
        align-items: center;
        gap: 30px;
    }

    .nav-links {
        display: flex;
        gap: 20px;
    }

    .nav-links a {
        color: var(--muted-text);
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-links a:hover,
    .nav-links a.active {
        color: var(--text-color);
        background: var(--darker-bg);
    }

    .search-box {
        position: relative;
    }

    .search-box form {
        display: flex;
        align-items: center;
    }

    .search-box input {
        background: var(--darker-bg);
        border: none;
        padding: 8px 35px 8px 15px;
        border-radius: 20px;
        color: var(--text-color);
        width: 200px;
        transition: width 0.3s ease;
    }

    .search-box input:focus {
        width: 250px;
        outline: none;
    }

    .search-box button {
        background: none;
        border: none;
        color: var(--muted-text);
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        padding: 5px;
    }

    .user-menu {
        position: relative;
    }

    .user-dropdown {
        position: relative;
    }

    .dropdown-toggle {
        background: none;
        border: none;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        transition: background-color 0.3s;
    }

    .dropdown-toggle:hover {
        background: var(--darker-bg);
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: var(--dark-bg);
        border-radius: 8px;
        padding: 8px;
        min-width: 180px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: none;
    }

    .dropdown-menu.active {
        display: block;
    }

    .dropdown-menu a {
        color: var(--muted-text);
        text-decoration: none;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-radius: 4px;
        transition: all 0.3s;
    }

    .dropdown-menu a:hover {
        color: var(--text-color);
        background: var(--darker-bg);
    }

    .auth-buttons {
        display: flex;
        gap: 10px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-login {
        color: var(--text-color);
        background: var(--darker-bg);
    }

    .btn-register {
        color: var(--text-color);
        background: var(--primary-color);
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Responsive Styles */
    @media (max-width: 1024px) {
        .nav-links {
            gap: 10px;
        }

        .search-box input {
            width: 150px;
        }

        .search-box input:focus {
            width: 200px;
        }
    }

    @media (max-width: 768px) {
        .mobile-menu-toggle {
            display: block;
            z-index: 1001;
        }

        .main-nav {
            position: fixed;
            top: var(--header-height);
            right: -250px;
            width: 250px;
            height: calc(100vh - var(--header-height));
            background: var(--dark-bg);
            flex-direction: column;
            padding: 20px;
            gap: 20px;
            transition: right 0.3s ease;
            overflow-y: auto;
            z-index: 1000;
        }

        .main-nav.active {
            right: 0;
            box-shadow: -2px 0 4px rgba(0, 0, 0, 0.1);
        }

        .nav-links {
            flex-direction: column;
            width: 100%;
        }

        .nav-links a {
            padding: 12px 16px;
            width: 100%;
            font-size: 1.1rem;
        }

        .search-box {
            width: 100%;
            margin: 10px 0;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 16px;
        }

        .search-box input:focus {
            width: 100%;
        }

        .user-menu {
            width: 100%;
            margin-top: 10px;
        }

        .auth-buttons {
            flex-direction: column;
            width: 100%;
            gap: 10px;
        }

        .btn {
            width: 100%;
            text-align: center;
            padding: 12px 16px;
        }

        .dropdown-menu {
            position: static;
            box-shadow: none;
            margin-top: 10px;
            width: 100%;
            background: var(--darker-bg);
        }

        .dropdown-menu a {
            padding: 12px 16px;
        }

        /* Animation for menu items */
        .nav-links a {
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s ease;
            transition-delay: calc(var(--item-index) * 0.1s);
        }

        .main-nav.active .nav-links a {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Add margin to main content to account for fixed header */
    body {
        padding-top: var(--header-height);
    }

    .menu-overlay {
        display: none;
        position: fixed;
        top: var(--header-height);
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }

    .menu-overlay.active {
        display: block;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const nav = document.querySelector('.main-nav');
        const overlay = document.querySelector('.menu-overlay');
        
        // Add animation delay to menu items
        const menuItems = document.querySelectorAll('.nav-links a');
        menuItems.forEach((item, index) => {
            item.style.setProperty('--item-index', index);
        });

        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            overlay.classList.toggle('active');
            menuToggle.setAttribute('aria-expanded', nav.classList.contains('active'));
            
            // Toggle menu icon
            const icon = menuToggle.querySelector('i');
            if (nav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close menu when clicking overlay
        overlay.addEventListener('click', function() {
            nav.classList.remove('active');
            overlay.classList.remove('active');
            menuToggle.setAttribute('aria-expanded', 'false');
            const icon = menuToggle.querySelector('i');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        });

        // User dropdown toggle
        const dropdownToggle = document.querySelector('.dropdown-toggle');
        const dropdownMenu = document.querySelector('.dropdown-menu');

        if (dropdownToggle && dropdownMenu) {
            dropdownToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('active');
            });

            document.addEventListener('click', function() {
                dropdownMenu.classList.remove('active');
            });

            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
    </script>
</body>
</html> 
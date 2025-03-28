<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/image_handler.php';

// Initialize variables for filtering
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$selectedYear = isset($_GET['year']) ? $_GET['year'] : '';

// Get unique categories with error handling
$categoryQuery = "SELECT DISTINCT cat.name 
                 FROM categories cat 
                 LEFT JOIN content_categories cc ON cat.id = cc.category_id 
                 LEFT JOIN content c ON cc.content_id = c.id 
                 WHERE c.duration > 180
                 ORDER BY cat.name ASC";

$categoryStmt = mysqli_prepare($conn, $categoryQuery);
if ($categoryStmt === false) {
    die("Error preparing category query: " . mysqli_error($conn));
}

if (!mysqli_stmt_execute($categoryStmt)) {
    die("Error executing category query: " . mysqli_stmt_error($categoryStmt));
}

$categoryResult = mysqli_stmt_get_result($categoryStmt);
$categories = mysqli_fetch_all($categoryResult, MYSQLI_ASSOC);
mysqli_stmt_close($categoryStmt);

// Get unique years with error handling
$yearQuery = "SELECT DISTINCT release_year 
              FROM content 
              WHERE duration > 180
              AND release_year IS NOT NULL 
              ORDER BY release_year DESC";

$yearStmt = mysqli_prepare($conn, $yearQuery);
if ($yearStmt === false) {
    die("Error preparing year query: " . mysqli_error($conn));
}

if (!mysqli_stmt_execute($yearStmt)) {
    die("Error executing year query: " . mysqli_stmt_error($yearStmt));
}

$yearResult = mysqli_stmt_get_result($yearStmt);
$years = mysqli_fetch_all($yearResult, MYSQLI_ASSOC);
mysqli_stmt_close($yearStmt);

// Build the main query with filters
$query = "SELECT DISTINCT c.*, GROUP_CONCAT(cat.name) as categories 
          FROM content c 
          LEFT JOIN content_categories cc ON c.id = cc.content_id 
          LEFT JOIN categories cat ON cc.category_id = cat.id 
          WHERE c.duration > 180";

$whereConditions = [];
$params = [];
$types = "";

if (!empty($selectedCategory)) {
    $whereConditions[] = "cat.name = ?";
    $params[] = $selectedCategory;
    $types .= "s";
}

if (!empty($selectedYear)) {
    $whereConditions[] = "c.release_year = ?";
    $params[] = $selectedYear;
    $types .= "i";
}

if (!empty($whereConditions)) {
    $query .= " AND " . implode(" AND ", $whereConditions);
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";

$stmt = mysqli_prepare($conn, $query);
if ($stmt === false) {
    die("Error preparing main query: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

if (!mysqli_stmt_execute($stmt)) {
    die("Error executing main query: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Shows - Flixnate</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
    /* Navigation Styles */
    .main-nav {
        background: var(--dark-bg);
        padding: 0.8rem 1rem;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .nav-brand h1 {
        margin: 0;
        font-size: 1.8rem;
        background: linear-gradient(90deg, #8B5CF6, #EC4899);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        width: 30px;
        height: 24px;
        position: relative;
        z-index: 1001;
    }

    .toggle-bar {
        display: block;
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, #8B5CF6, #EC4899);
        position: absolute;
        left: 0;
        transition: all 0.3s ease;
        border-radius: 2px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .toggle-bar:nth-child(1) { top: 0; }
    .toggle-bar:nth-child(2) { 
        top: 50%; 
        transform: translateY(-50%);
        background: linear-gradient(90deg, #9333EA, #DB2777);
    }
    .toggle-bar:nth-child(3) { 
        bottom: 0;
        background: linear-gradient(90deg, #7C3AED, #BE185D);
    }

    .mobile-menu-toggle:hover .toggle-bar {
        background: linear-gradient(90deg, #A855F7, #F472B6);
    }

    .mobile-menu-toggle.active .toggle-bar {
        background: #8B5CF6;
    }

    .mobile-menu-toggle.active .toggle-bar:nth-child(1) {
        transform: translateY(11px) rotate(45deg);
    }

    .mobile-menu-toggle.active .toggle-bar:nth-child(2) {
        opacity: 0;
        transform: translateX(-20px);
    }

    .mobile-menu-toggle.active .toggle-bar:nth-child(3) {
        transform: translateY(-11px) rotate(-45deg);
    }

    .nav-list {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .nav-list li {
        position: relative;
    }

    .nav-list a {
        color: var(--muted-text);
        text-decoration: none;
        font-size: 1rem;
        padding: 0.6rem 1rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .nav-list a i {
        font-size: 1.1rem;
    }

    .nav-list a:hover,
    .nav-list a.active {
        color: var(--text-color);
        background: rgba(255, 255, 255, 0.05);
    }

    /* Dropdown Styles */
    .has-dropdown {
        position: relative;
    }

    .dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        background: var(--darker-bg);
        min-width: 200px;
        padding: 0.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }

    .has-dropdown:hover .dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown li {
        margin: 0;
    }

    .dropdown a {
        padding: 0.8rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .user-menu .dropdown {
        right: 0;
        left: auto;
    }

    /* Responsive Navigation */
    @media (max-width: 768px) {
        .mobile-menu-toggle {
            display: block;
        }

        .nav-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 80px 20px 20px;
            transition: right 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .nav-menu.active {
            right: 0;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3);
        }

        .nav-list {
            flex-direction: column;
            gap: 0.5rem;
            align-items: stretch;
        }

        .nav-list li {
            width: 100%;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-list a {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: #a9b1d6;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .nav-list a:hover,
        .nav-list a.active {
            color: #fff;
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.3);
            transform: translateX(5px);
        }

        .nav-list a i {
            color: #8B5CF6;
        }

        .dropdown {
            position: static;
            background: rgba(0, 0, 0, 0.2);
            min-width: 100%;
            padding: 0.5rem;
            margin-top: 0.5rem;
            border-radius: 8px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .has-dropdown.active .dropdown {
            display: block;
            animation: slideDown 0.3s ease forwards;
        }

        .dropdown a {
            padding: 0.8rem 1.5rem 0.8rem 3rem;
            margin: 0.3rem 0;
            background: rgba(255, 255, 255, 0.02);
        }

        .dropdown a:hover {
            background: rgba(139, 92, 246, 0.1);
        }

        .menu-overlay {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
        }

        .menu-overlay.active {
            display: block;
            opacity: 1;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        const overlay = document.querySelector('.menu-overlay');
        const dropdownItems = document.querySelectorAll('.has-dropdown');
        
        function toggleMenu() {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        menuToggle.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);

        // Handle dropdowns on mobile
        dropdownItems.forEach(item => {
            const link = item.querySelector('a');
            link.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    item.classList.toggle('active');
                }
            });
        });

        // Close menu on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && navMenu.classList.contains('active')) {
                toggleMenu();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                toggleMenu();
            }
        });
    });
    </script>


    <div class="content-container">
        <div class="filters">
            <h2>Filters</h2>
            <form class="filter-form" method="GET">
                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>"
                                    <?php echo $selectedCategory === $category['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year">Release Year</label>
                    <select name="year" id="year">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year['release_year']; ?>"
                                    <?php echo $selectedYear == $year['release_year'] ? 'selected' : ''; ?>>
                                <?php echo $year['release_year']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="filter-button">Apply Filters</button>
            </form>
        </div>

        <div class="content-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="content-card">
                        <a href="watch.php?id=<?php echo $row['id']; ?>">
                            <?php 
                            $thumbnail = isset($row['thumbnail']) ? $row['thumbnail'] : '';
                            $imagePath = getImagePath($thumbnail);
                            ?>
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                 alt="<?php echo htmlspecialchars($row['title']); ?>"
                                 loading="lazy"
                                 onerror="this.src='assets/images/default-thumbnail.jpg'">
                            <div class="play-button">
                                <i class="fas fa-play"></i>
                            </div>
                        </a>
                        <div class="content-info">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="year"><?php echo isset($row['release_year']) ? $row['release_year'] : 'N/A'; ?></div>
                            <div class="categories">
                                <?php echo isset($row['categories']) ? htmlspecialchars($row['categories']) : 'Uncategorized'; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No TV shows found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="footer-wrapper">
            <div class="footer-section">
                <h3 class="footer-title">About Flixnate</h3>
                <p>Your premium streaming experience with unique content and features.</p>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Connect With Us</h3>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> Flixnate. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html> 
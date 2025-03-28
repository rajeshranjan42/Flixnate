<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/image_handler.php';

// Initialize variables
$error = null;
$movies = [];
$categories = [];
$years = [];

// Pagination settings
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Get filter values
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$selectedYear = isset($_GET['year']) ? $_GET['year'] : '';

try {
    // Verify database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Base query conditions
    $conditions = [];
    $params = [];
    $types = "";

    // Add category filter
    if (!empty($selectedCategory)) {
        $conditions[] = "cat.name = ?";
        $params[] = $selectedCategory;
        $types .= "s";
    }

    // Add year filter
    if (!empty($selectedYear)) {
        $conditions[] = "c.release_year = ?";
        $params[] = $selectedYear;
        $types .= "i";
    }

    // Build WHERE clause
    $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

    // Get total count for pagination
    $countQuery = "SELECT COUNT(DISTINCT c.id) as total 
                   FROM content c 
                   LEFT JOIN content_categories cc ON c.id = cc.content_id 
                   LEFT JOIN categories cat ON cc.category_id = cat.id" . $whereClause;

    $stmt = mysqli_prepare($conn, $countQuery);
    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $countResult = mysqli_stmt_get_result($stmt);
        $totalMovies = mysqli_fetch_assoc($countResult)['total'];
        $totalPages = ceil($totalMovies / $itemsPerPage);
        mysqli_stmt_close($stmt);
    } else {
        throw new Exception("Error preparing count query: " . mysqli_error($conn));
    }

    // Get all categories for filter
    $categoryQuery = "SELECT DISTINCT cat.name 
                     FROM categories cat 
                     INNER JOIN content_categories cc ON cat.id = cc.category_id 
                     ORDER BY cat.name ASC";
    $categoryResult = mysqli_query($conn, $categoryQuery);
    if ($categoryResult) {
        $categories = mysqli_fetch_all($categoryResult, MYSQLI_ASSOC);
    } else {
        throw new Exception("Error fetching categories: " . mysqli_error($conn));
    }

    // Get all years for filter
    $yearQuery = "SELECT DISTINCT release_year 
                  FROM content 
                  WHERE release_year IS NOT NULL 
                  ORDER BY release_year DESC";
    $yearResult = mysqli_query($conn, $yearQuery);
    if ($yearResult) {
        $years = mysqli_fetch_all($yearResult, MYSQLI_ASSOC);
    } else {
        throw new Exception("Error fetching years: " . mysqli_error($conn));
    }

    // Main query for movies
    $query = "SELECT DISTINCT c.*, GROUP_CONCAT(cat.name) as categories 
              FROM content c 
              LEFT JOIN content_categories cc ON c.id = cc.content_id 
              LEFT JOIN categories cat ON cc.category_id = cat.id" . 
              $whereClause . 
              " GROUP BY c.id 
                ORDER BY c.created_at DESC 
                LIMIT ? OFFSET ?";

    // Add pagination parameters
    $params[] = $itemsPerPage;
    $params[] = $offset;
    $types .= "ii";

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $movies = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    } else {
        throw new Exception("Error preparing main query: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    error_log("Movies page error: " . $e->getMessage());
    $error = "An error occurred while fetching the movies. Please try again later.";
    $movies = [];
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies - Flixnate</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            gap: 10px;
        }
        .pagination a, .pagination span {
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            background: #333;
            color: white;
            transition: background-color 0.3s;
        }
        .pagination a:hover {
            background: #444;
        }
        .pagination .active {
            background: #666;
        }
        .pagination .disabled {
            background: #222;
            cursor: not-allowed;
        }
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .loading.active {
            display: flex;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-message {
            background: #ff5555;
            color: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        .content-info {
            padding: 10px;
            background: rgba(0, 0, 0, 0.7);
        }
        .content-card {
            position: relative;
            transition: transform 0.3s;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .content-card:hover {
            transform: translateY(-5px);
        }
        .content-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .content-card:hover img {
            transform: scale(1.05);
        }
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: opacity 0.3s;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .content-card:hover .play-button {
            opacity: 1;
        }
        .play-button i {
            color: white;
            font-size: 24px;
        }
        .filters {
            background: #1E1E1E;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            width: 300px;
            margin-top: 100px;
        }

        .filters h2 {
            color: #fff;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #9B9B9B;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            background: #000000;
            border: none;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .form-group select:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .btn-primary {
            background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            transition: opacity 0.3s ease;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        @media (min-width: 768px) {
            #filterForm {
                display: block;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .btn-primary {
                width: 100%;
                margin-top: 5px;
            }
        }
    </style>
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


    <div class="loading">
        <div class="spinner"></div>
    </div>

    <main class="container">
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <div class="filters">
                <h2>Filters</h2>
                <form id="filterForm" method="GET" action="movies.php">
                    <div class="form-group">
                        <label for="category">Category:</label>
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
                        <label for="year">Release Year:</label>
                        <select name="year" id="year">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo htmlspecialchars($year['release_year']); ?>"
                                        <?php echo $selectedYear == $year['release_year'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year['release_year']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </form>
            </div>

            <div class="content-grid">
                <?php if ($movies && mysqli_num_rows($movies) > 0): ?>
                    <?php while ($movie = mysqli_fetch_assoc($movies)): ?>
                        <div class="content-card">
                            <div class="thumbnail">
                                <?php
                                $thumbnail = isset($movie['thumbnail']) ? $movie['thumbnail'] : '';
                                $imagePath = getImagePath($thumbnail);
                                ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                <div class="play-button">
                                    <a href="watch.php?id=<?php echo $movie['id']; ?>">▶</a>
                                </div>
                            </div>
                            <div class="content-info">
                                <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                                <p class="year"><?php echo $movie['release_year'] ? htmlspecialchars($movie['release_year']) : 'N/A'; ?></p>
                                <p class="categories">
                                    <?php
                                    $categories = $movie['categories'] ? explode(',', $movie['categories']) : ['Uncategorized'];
                                    echo htmlspecialchars(implode(', ', $categories));
                                    ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-content">No movies found matching your criteria.</p>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $selectedCategory ? '&category='.urlencode($selectedCategory) : ''; ?><?php echo $selectedYear ? '&year='.urlencode($selectedYear) : ''; ?>" class="btn">First</a>
                        <a href="?page=<?php echo $page-1; ?><?php echo $selectedCategory ? '&category='.urlencode($selectedCategory) : ''; ?><?php echo $selectedYear ? '&year='.urlencode($selectedYear) : ''; ?>" class="btn">Previous</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo $selectedCategory ? '&category='.urlencode($selectedCategory) : ''; ?><?php echo $selectedYear ? '&year='.urlencode($selectedYear) : ''; ?>" 
                           class="btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page+1; ?><?php echo $selectedCategory ? '&category='.urlencode($selectedCategory) : ''; ?><?php echo $selectedYear ? '&year='.urlencode($selectedYear) : ''; ?>" class="btn">Next</a>
                        <a href="?page=<?php echo $totalPages; ?><?php echo $selectedCategory ? '&category='.urlencode($selectedCategory) : ''; ?><?php echo $selectedYear ? '&year='.urlencode($selectedYear) : ''; ?>" class="btn">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.getElementById('filterForm');
        const loading = document.querySelector('.loading');

        if (filterForm) {
            filterForm.addEventListener('submit', function() {
                loading.classList.add('active');
            });
        }

        // Show loading on pagination clicks
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function() {
                loading.classList.add('active');
            });
        });
    });
    </script>
</body>
</html> 
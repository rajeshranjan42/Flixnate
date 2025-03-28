<?php
session_start();
require_once 'config/database.php';
require_once 'includes/image_handler.php';

// Get search query if exists
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Function to get search results
function getSearchResults($conn, $search_query, $category = '') {
    $query = "SELECT DISTINCT c.* FROM content c 
              LEFT JOIN content_categories cc ON c.id = cc.content_id 
              LEFT JOIN categories cat ON cc.category_id = cat.id 
              WHERE 1=1";
    
    $params = array();
    $types = "";
    
    if (!empty($search_query)) {
        $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
        $search_param = "%{$search_query}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    if (!empty($category)) {
        $query .= " AND cat.name = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    $query .= " ORDER BY c.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $content = array();
    while ($row = mysqli_fetch_assoc($result)) {
        // Set default thumbnail if none exists
        if (!isset($row['thumbnail']) || empty($row['thumbnail'])) {
            $row['thumbnail'] = 'assets/images/default-thumbnail.jpg';
        } else {
            $row['thumbnail'] = getImagePath($row['thumbnail']);
        }
        $content[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $content;
}

// Function to get categories
function getCategories($conn) {
    $categories_query = "SELECT DISTINCT name FROM categories ORDER BY name ASC";
    $categories_result = mysqli_query($conn, $categories_query);
    $categories = array();
    
    if ($categories_result) {
        while ($category = mysqli_fetch_assoc($categories_result)) {
            $categories[] = $category;
        }
        mysqli_free_result($categories_result);
    }
    
    return $categories;
}

$search_results = !empty($search_query) || !empty($selected_category) ? getSearchResults($conn, $search_query, $selected_category) : array();
$categories = getCategories($conn);

// Define icon mapping
$category_icons = [
    'Action' => 'fa-fire',
    'Drama' => 'fa-theater-masks',
    'Comedy' => 'fa-laugh',
    'Sci-Fi' => 'fa-robot',
    'Horror' => 'fa-ghost',
    'Romance' => 'fa-heart',
    'Documentary' => 'fa-film',
    'Animation' => 'fa-child',
    'Crime' => 'fa-user-secret'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Flixnate</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/navigation.css">
    <link rel="stylesheet" href="assets/css/search.css">
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
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
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
                <li><a href="search.php" class="active"><i class="fas fa-search"></i> Search</a></li>
                
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

    <main>
        <section class="search-section">
            <h2>Search Content</h2>
            <form class="search-form" action="search.php" method="GET">
                <div class="search-input-group">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search for movies, TV shows..." autofocus>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <?php $selected = ($selected_category === $category['name']) ? 'selected' : ''; ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>

            <?php if (!empty($search_query) || !empty($selected_category)): ?>
                <div class="search-results">
                    <h3>Search Results <?php if (!empty($search_results)): ?>(<?php echo count($search_results); ?>)<?php endif; ?></h3>
                    <?php if (empty($search_results)): ?>
                        <p class="no-results">
                            <i class="fas fa-search"></i>
                            No results found for "<?php echo htmlspecialchars($search_query); ?>"
                            <?php if (!empty($selected_category)): ?>
                                in <?php echo htmlspecialchars($selected_category); ?>
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <div class="content-grid">
                            <?php foreach ($search_results as $content): ?>
                                <div class="content-card">
                                    <img src="<?php echo htmlspecialchars($content['thumbnail']); ?>" 
                                         alt="<?php echo htmlspecialchars($content['title']); ?>" 
                                         loading="lazy"
                                         onerror="this.onerror=null; this.src='assets/images/default-thumbnail.jpg';">
                                    <div class="content-info">
                                        <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                                        <p><?php echo htmlspecialchars(substr($content['description'], 0, 100)) . '...'; ?></p>
                                        <a href="watch.php?id=<?php echo (int)$content['id']; ?>" class="play-button">
                                            <i class="fas fa-play"></i> Watch Now
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Categories -->
        <section class="categories">
            <h2>Browse Categories</h2>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <?php
                    $category_name = htmlspecialchars($category['name']);
                    $category_icon = isset($category_icons[$category['name']]) ? $category_icons[$category['name']] : 'fa-film';
                    ?>
                    <a href="search.php?category=<?php echo urlencode($category['name']); ?>" 
                       class="category-card<?php echo ($selected_category === $category['name']) ? ' active' : ''; ?>">
                        <i class="fas <?php echo htmlspecialchars($category_icon); ?>"></i>
                        <h3><?php echo $category_name; ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            const menuOverlay = document.querySelector('.menu-overlay');
            const dropdownItems = document.querySelectorAll('.has-dropdown');
            
            if (mobileMenuToggle && navMenu && menuOverlay) {
                mobileMenuToggle.addEventListener('click', function() {
                    mobileMenuToggle.classList.toggle('active');
                    navMenu.classList.toggle('active');
                    menuOverlay.style.display = navMenu.classList.contains('active') ? 'block' : 'none';
                    setTimeout(() => {
                        menuOverlay.classList.toggle('active');
                    }, 10);
                });

                menuOverlay.addEventListener('click', function() {
                    mobileMenuToggle.classList.remove('active');
                    navMenu.classList.remove('active');
                    menuOverlay.classList.remove('active');
                    setTimeout(() => {
                        menuOverlay.style.display = 'none';
                    }, 300);
                });
            }

            // Handle dropdowns on mobile
            dropdownItems.forEach(item => {
                const link = item.querySelector('a');
                const dropdown = item.querySelector('.dropdown');

                if (window.innerWidth <= 768) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                    });
                }
            });
        });
    </script>
</body>
</html>
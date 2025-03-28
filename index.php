<?php
// Start output buffering
ob_start();
session_start();

// Include database connection and image handler
require_once 'config/database.php';
require_once 'includes/image_handler.php';

// Function to get featured content
function getFeaturedContent($conn) {
    $query = "SELECT * FROM content WHERE is_featured = 1 ORDER BY id ASC LIMIT 6";
    $result = mysqli_query($conn, $query);
    $content = array();
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Set default thumbnail if none exists
            if (!isset($row['thumbnail']) || empty($row['thumbnail'])) {
                $row['thumbnail'] = 'assets/images/default-thumbnail.jpg';
            } else {
                $row['thumbnail'] = getImagePath($row['thumbnail']);
            }
            $content[] = $row;
        }
    }
    return $content;
}

// Function to get latest content
function getLatestContent($conn) {
    $query = "SELECT * FROM content ORDER BY created_at DESC LIMIT 6";
    $result = mysqli_query($conn, $query);
    $content = array();
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Set default thumbnail if none exists
            if (!isset($row['thumbnail']) || empty($row['thumbnail'])) {
                $row['thumbnail'] = 'assets/images/default-thumbnail.jpg';
            } else {
                $row['thumbnail'] = getImagePath($row['thumbnail']);
            }
            $content[] = $row;
        }
    }
    return $content;
}

// Get content before any HTML output
$featured_content = getFeaturedContent($conn);
$latest_content = getLatestContent($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flixnate - Stream Movies & TV Shows</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/hero.css">
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-slider">
            <div class="hero-slide active" style="background-image: url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1600&auto=format')">
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h1 class="hero-title">Welcome to Flixnate</h1>
                        <p class="hero-subtitle">Your Ultimate Streaming Experience</p>
                        <a href="#featured" class="hero-cta">
                            <i class="fas fa-play"></i> Start Watching
                        </a>
                    </div>
                </div>
            </div>
            <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1600&auto=format')">
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h1 class="hero-title">Endless Entertainment</h1>
                        <p class="hero-subtitle">Movies, TV Shows, and More</p>
                        <a href="movies.php" class="hero-cta">
                            <i class="fas fa-film"></i> Browse Movies
                        </a>
                    </div>
                </div>
            </div>
            <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=1600&auto=format')">
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h1 class="hero-title">Watch Anywhere</h1>
                        <p class="hero-subtitle">Stream on Any Device</p>
                        <a href="tv-shows.php" class="hero-cta">
                            <i class="fas fa-tv"></i> Explore Shows
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="slider-nav">
            <div class="slider-dot active"></div>
            <div class="slider-dot"></div>
            <div class="slider-dot"></div>
        </div>
    </section>

    <main class="main-content">
        <!-- Featured Content Section -->
        <section id="featured" class="featured-section">
            <div class="container">
                <h2 class="section-title">Featured</h2>
                <div class="content-grid">
                    <?php
                    // Get featured content
                    $featured_query = "SELECT * FROM content WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 6";
                    $featured_result = mysqli_query($conn, $featured_query);
                    
                    while ($content = mysqli_fetch_assoc($featured_result)) {
                        ?>
                        <div class="content-card">
                            <div class="content-thumbnail">
                                <img src="<?php echo htmlspecialchars($content['thumbnail']); ?>" alt="<?php echo htmlspecialchars($content['title']); ?>">
                                <div class="content-overlay">
                                    <a href="watch.php?id=<?php echo $content['id']; ?>" class="play-button">
                                        <i class="fas fa-play"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="content-info">
                                <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                                <p class="release-year"><?php echo $content['release_year']; ?></p>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Latest Content Section -->
        <section class="latest-section">
            <div class="container">
                <h2 class="section-title">Latest</h2>
                <div class="content-grid">
                    <?php
                    // Get latest content
                    $latest_query = "SELECT * FROM content ORDER BY created_at DESC LIMIT 12";
                    $latest_result = mysqli_query($conn, $latest_query);
                    
                    while ($content = mysqli_fetch_assoc($latest_result)) {
                        ?>
                        <div class="content-card">
                            <div class="content-thumbnail">
                                <img src="<?php echo htmlspecialchars($content['thumbnail']); ?>" alt="<?php echo htmlspecialchars($content['title']); ?>">
                                <div class="content-overlay">
                                    <a href="watch.php?id=<?php echo $content['id']; ?>" class="play-button">
                                        <i class="fas fa-play"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="content-info">
                                <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                                <p class="release-year"><?php echo $content['release_year']; ?></p>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Flixnate</h3>
                <p>Your premium streaming experience with unique content and features.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="about.php">About Us</a>
                <a href="contact.php">Contact</a>
                <a href="faq.php">FAQ</a>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Flixnate. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/hero-slider.js"></script>
</body>
</html>
<?php
// Flush the output buffer and send to browser
ob_end_flush();
?>

<style>
/* Main Content Styles */
.main-content {
    padding: 20px;
    min-height: calc(100vh - var(--header-height));
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Section Styles */
.featured-section,
.latest-section {
    margin-bottom: 40px;
}

.section-title {
    color: var(--text-color);
    font-size: 1.8rem;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--primary-color);
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

/* Content Card */
.content-card {
    background: var(--darker-bg);
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.content-card:hover {
    transform: translateY(-5px);
}

.content-thumbnail {
    position: relative;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.content-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.content-card:hover .content-thumbnail img {
    transform: scale(1.05);
}

.content-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.content-card:hover .content-overlay {
    opacity: 1;
}

.play-button {
    width: 50px;
    height: 50px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    transform: scale(0.8);
    transition: all 0.3s ease;
}

.content-card:hover .play-button {
    transform: scale(1);
}

.content-info {
    padding: 15px;
}

.content-info h3 {
    color: var(--text-color);
    font-size: 1rem;
    margin: 0 0 5px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.release-year {
    color: var(--muted-text);
    font-size: 0.9rem;
    margin: 0;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .main-content {
        padding: 15px;
    }

    .content-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }

    .section-title {
        font-size: 1.5rem;
    }

    .content-info h3 {
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .content-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
    }

    .play-button {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>
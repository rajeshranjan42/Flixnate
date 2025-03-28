<?php
require_once 'includes/init.php';
require_once 'includes/db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log errors
function logError($message) {
    error_log("Watch Page Error: " . $message);
}

// Get content ID from URL
$content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
logError("Accessing content ID: " . $content_id);

if (!$content_id) {
    logError("No content ID provided");
    header("Location: index.php");
    exit;
}

// Get content details
$stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE id = ?");
if (!$stmt) {
    logError("Failed to prepare content query: " . mysqli_error($conn));
    die("Database error occurred");
}

mysqli_stmt_bind_param($stmt, "i", $content_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    logError("Content not found for ID: " . $content_id);
    header("Location: index.php");
    exit;
}

$content = mysqli_fetch_assoc($result);
logError("Content found: " . $content['title']);

// Get video source (handle both video_path and video_url)
$video_source = '';
$video_type = 'video/mp4'; // default video type

if (!empty($content['video_url'])) {
    $video_source = $content['video_url'];
    logError("Using video URL: " . $video_source);
} elseif (!empty($content['video_path'])) {
    // Handle both full paths and relative paths
    if (strpos($content['video_path'], 'http') === 0) {
        $video_source = $content['video_path'];
    } else {
        $video_source = strpos($content['video_path'], 'uploads/') === 0 
            ? $content['video_path'] 
            : 'uploads/videos/' . basename($content['video_path']);
    }
    logError("Using video path: " . $video_source);
}

// Verify video exists if it's a local file
$video_exists = false;
$error_message = '';

if (!empty($video_source)) {
    if (strpos($video_source, 'http') === 0) {
        $video_exists = true;
        logError("External URL detected: " . $video_source);
    } else {
        $absolute_path = realpath($video_source);
        logError("Checking local file:");
        logError("Relative path: " . $video_source);
        logError("Absolute path: " . ($absolute_path ?: 'Not found'));
        
        if (!file_exists($video_source)) {
            $error_message = "Video file does not exist";
            logError("Error: " . $error_message);
        } elseif (!is_readable($video_source)) {
            $error_message = "Video file is not readable";
            logError("Error: " . $error_message);
        } else {
            $video_exists = true;
            logError("File exists and is readable");
            logError("File size: " . filesize($video_source) . " bytes");
            logError("File permissions: " . decoct(fileperms($video_source)));
            
            // Determine video type based on file extension
            $extension = strtolower(pathinfo($video_source, PATHINFO_EXTENSION));
            switch ($extension) {
                case 'webm':
                    $video_type = 'video/webm';
                    break;
                case 'mp4':
                    $video_type = 'video/mp4';
                    break;
                case 'ogg':
                case 'ogv':
                    $video_type = 'video/ogg';
                    break;
            }
        }
    }
} else {
    $error_message = "No video source specified";
    logError("Error: " . $error_message);
}

// Get content categories
$categories = [];
$stmt = mysqli_prepare($conn, "SELECT c.name FROM categories c 
                              JOIN content_categories cc ON c.id = cc.category_id 
                              WHERE cc.content_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row['name'];
    }
}

// Get user rating if logged in
$user_rating = null;
if (isset($_SESSION['user_id'])) {
    $stmt = mysqli_prepare($conn, "SELECT rating FROM user_ratings WHERE user_id = ? AND content_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $content_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $user_rating = $row['rating'];
        }
    }
}

// Get average rating
$avg_rating = 0;
$stmt = mysqli_prepare($conn, "SELECT AVG(rating) as avg_rating FROM user_ratings WHERE content_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $avg_rating = round($row['avg_rating'], 1);
    }
}

// Get comments
$comments = [];
$stmt = mysqli_prepare($conn, "SELECT uc.*, u.username 
                              FROM user_comments uc 
                              JOIN users u ON uc.user_id = u.id 
                              WHERE uc.content_id = ? 
                              ORDER BY uc.created_at DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating']) && isset($_SESSION['user_id'])) {
    $rating = (int)$_POST['rating'];
    if ($rating >= 1 && $rating <= 5) {
        $stmt = mysqli_prepare($conn, "INSERT INTO user_ratings (user_id, content_id, rating) 
                                     VALUES (?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE rating = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iiii", $_SESSION['user_id'], $content_id, $rating, $rating);
            mysqli_stmt_execute($stmt);
            header("Location: watch.php?id=" . $content_id);
            exit;
        }
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && isset($_SESSION['user_id'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO user_comments (user_id, content_id, comment) VALUES (?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iis", $_SESSION['user_id'], $content_id, $comment);
            mysqli_stmt_execute($stmt);
            header("Location: watch.php?id=" . $content_id);
            exit;
        }
    }
}

// Get site settings
$site_settings = [];
$result = mysqli_query($conn, "SELECT * FROM site_settings");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (isset($row['setting_key']) && isset($row['setting_value'])) {
            $site_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// Get page title with fallback
$page_title = $content['title'] . ' - ' . (isset($site_settings['site_name']) ? $site_settings['site_name'] : 'Flixnate');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flixnate - Watch</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/navigation.css">
    <link rel="stylesheet" href="assets/css/watch.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
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
    
    <main class="watch-page">
        <div class="content-container">
            <div class="video-section">
                <?php if ($video_exists): ?>
                    <div class="video-player">
                        <video id="player" playsinline controls data-plyr-config='{"controls": ["play-large", "play", "progress", "current-time", "mute", "volume", "fullscreen"]}'>
                            <source src="stream.php?id=<?php echo $content_id; ?>" type="<?php echo $video_type; ?>">
                            <?php if (strpos($video_source, 'http') !== 0 && file_exists($video_source)): ?>
                                <!-- Fallback to direct file access if stream.php fails -->
                                <source src="<?php echo htmlspecialchars($video_source); ?>" type="<?php echo $video_type; ?>">
                            <?php endif; ?>
                            Your browser does not support the video tag.
                        </video>
                        <div id="video-error-message" style="display:none; color:red; padding:10px; background:#f8f8f8; margin-top:10px; border-radius:4px;">
                            Video playback error. Please try refreshing the page or contact support.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="error-message">
                        <h3><i class="fas fa-exclamation-circle"></i> Video Not Available</h3>
                        <p>Sorry, this video is currently unavailable. <?php echo htmlspecialchars($error_message); ?></p>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <div style="margin-top: 20px; font-size: 0.9em; color: #ff6b6b; text-align: left; max-width: 600px; margin-left: auto; margin-right: auto;">
                                <h4 style="margin-bottom: 10px;">Admin Debug Info:</h4>
                                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px;">
                                    Content ID: <?php echo htmlspecialchars($content_id); ?><br>
                                    Title: <?php echo htmlspecialchars($content['title']); ?><br>
                                    Video Path: <?php echo htmlspecialchars($content['video_path'] ?: 'Not set'); ?><br>
                                    Video URL: <?php echo htmlspecialchars($content['video_url'] ?: 'Not set'); ?><br>
                                    Resolved Source: <?php echo htmlspecialchars($video_source); ?><br>
                                    Error: <?php echo htmlspecialchars($error_message); ?><br>
                                    <?php if (!empty($video_source) && strpos($video_source, 'http') !== 0): ?>
                                        Absolute Path: <?php echo htmlspecialchars($absolute_path ?: 'Not found'); ?><br>
                                        File Exists: <?php echo file_exists($video_source) ? 'Yes' : 'No'; ?><br>
                                        Is Readable: <?php echo is_readable($video_source) ? 'Yes' : 'No'; ?><br>
                                        <?php if (file_exists($video_source)): ?>
                                            File Size: <?php echo filesize($video_source); ?> bytes<br>
                                            File Permissions: <?php echo decoct(fileperms($video_source)); ?><br>
                                            File Type: <?php echo $video_type; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="content-info">
                    <h1><?php echo htmlspecialchars($content['title']); ?></h1>
                    <div class="meta-info">
                        <span class="year"><?php echo htmlspecialchars($content['release_year']); ?></span>
                        <span class="duration"><?php echo htmlspecialchars($content['duration']); ?></span>
                        <?php if (!empty($categories)): ?>
                            <span class="categories">
                                <?php echo htmlspecialchars(implode(', ', $categories)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="rating-section">
                        <div class="average-rating">
                            <i class="fas fa-star"></i>
                            <span><?php echo number_format($avg_rating, 1); ?></span>
                        </div>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" class="rating-form">
                                <div class="rating-stars">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                               id="star<?php echo $i; ?>" 
                                               <?php echo $user_rating === $i ? 'checked' : ''; ?>>
                                        <label for="star<?php echo $i; ?>">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <button type="submit" class="rate-button">Rate</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <p class="description"><?php echo nl2br(htmlspecialchars($content['description'])); ?></p>
                </div>
            </div>

            <div class="comments-section">
                <h2>Comments</h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" class="comment-form">
                        <textarea name="comment" placeholder="Write a comment..." required></textarea>
                        <button type="submit">Post Comment</button>
                    </form>
                <?php else: ?>
                    <p class="login-prompt">Please <a href="login.php">login</a> to leave a comment.</p>
                <?php endif; ?>

                <div class="comments-list">
                    <?php if (empty($comments)): ?>
                        <p class="no-comments">No comments yet. Be the first to comment!</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <span class="username"><?php echo htmlspecialchars($comment['username']); ?></span>
                                    <span class="date"><?php echo date('M j, Y', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu functionality
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

            // Video player initialization
            const videoElement = document.getElementById('player');
            
            if (!videoElement) {
                console.error('Video element not found');
                return;
            }

            // Add event listeners for debugging
            videoElement.addEventListener('error', function(e) {
                console.error('Video error:', e);
                if (videoElement.error) {
                    console.error('Error code:', videoElement.error.code);
                    console.error('Error message:', videoElement.error.message);
                    
                    // Show error message to user
                    const errorMessageElement = document.getElementById('video-error-message');
                    if (errorMessageElement) {
                        let errorText = 'Video playback error';
                        
                        // Provide more specific error messages based on error code
                        switch(videoElement.error.code) {
                            case 1: // MEDIA_ERR_ABORTED
                                errorText = 'Video playback aborted';
                                break;
                            case 2: // MEDIA_ERR_NETWORK
                                errorText = 'Network error occurred while loading the video';
                                break;
                            case 3: // MEDIA_ERR_DECODE
                                errorText = 'Video decoding error. The file may be corrupted or in an unsupported format';
                                break;
                            case 4: // MEDIA_ERR_SRC_NOT_SUPPORTED
                                errorText = 'Video format not supported by your browser';
                                break;
                        }
                        
                        errorMessageElement.textContent = errorText;
                        errorMessageElement.style.display = 'block';
                    }
                    
                    // Try to use the fallback source if available
                    const sources = videoElement.getElementsByTagName('source');
                    if (sources.length > 1) {
                        console.log('Trying fallback video source');
                        videoElement.src = sources[1].src;
                        videoElement.load();
                        videoElement.play().catch(e => console.error('Failed to play fallback source:', e));
                    }
                }
            });

            videoElement.addEventListener('loadstart', function() {
                console.log('Video loading started');
            });

            videoElement.addEventListener('loadedmetadata', function() {
                console.log('Video metadata loaded');
                console.log('Duration:', videoElement.duration);
                console.log('Size:', videoElement.videoWidth, 'x', videoElement.videoHeight);
            });

            videoElement.addEventListener('canplay', function() {
                console.log('Video can start playing');
            });

            // Initialize Plyr with more options
            const player = new Plyr(videoElement, {
                debug: true,
                controls: [
                    'play-large',
                    'play',
                    'progress',
                    'current-time',
                    'mute',
                    'volume',
                    'fullscreen'
                ],
                volume: 1,
                muted: false,
                loadSprite: true,
                iconUrl: 'https://cdn.plyr.io/3.7.8/plyr.svg',
                blankVideo: 'https://cdn.plyr.io/static/blank.mp4',
                previewThumbnails: { enabled: false },
                storage: { enabled: true },
                autoplay: false // Ensure autoplay is disabled as it can cause issues
            });
            
            // Force video to load
            videoElement.load();

            // Add Plyr event listeners
            player.on('ready', () => {
                console.log('Player is ready');
            });

            player.on('error', (error) => {
                console.error('Plyr error:', error);
            });

            player.on('loadeddata', () => {
                console.log('Video data loaded');
            });
        });
    </script>
</body>
</html>

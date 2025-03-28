<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Create categories table if it doesn't exist
$create_categories = "CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

mysqli_query($conn, $create_categories);

// Create content_categories table if it doesn't exist
$create_content_categories = "CREATE TABLE IF NOT EXISTS `content_categories` (
    `content_id` int(11) NOT NULL,
    `category_id` int(11) NOT NULL,
    PRIMARY KEY (`content_id`, `category_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `content_categories_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
    CONSTRAINT `content_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

mysqli_query($conn, $create_content_categories);

// Insert default categories if they don't exist
$default_categories = ['Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi', 'Documentary', 'Romance', 'Thriller', 'Animation'];
foreach ($default_categories as $category) {
    $cat = mysqli_real_escape_string($conn, $category);
    mysqli_query($conn, "INSERT IGNORE INTO categories (name) VALUES ('$cat')");
}

// Get all categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Debug: Check table structure
$table_check = mysqli_query($conn, "SHOW COLUMNS FROM content");
if (!$table_check) {
    die("Error checking table structure: " . mysqli_error($conn));
}

$columns = [];
while ($row = mysqli_fetch_assoc($table_check)) {
    $columns[] = $row['Field'];
}

if (!in_array('thumbnail', $columns)) {
    die("Thumbnail column is missing from content table. Please run setup_tables.php");
}

if (!in_array('video_path', $columns)) {
    die("Video_path column is missing from content table. Please run setup_tables.php");
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim(mysqli_real_escape_string($conn, $_POST['title']));
    $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $release_year = !empty($_POST['release_year']) ? intval($_POST['release_year']) : null;
    $duration = !empty($_POST['duration']) ? intval($_POST['duration']) : null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    
    // Handle file upload
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['thumbnail']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/thumbnails/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_path)) {
                $thumbnail = 'uploads/thumbnails/' . $file_name;
            } else {
                $error = "Error uploading thumbnail file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
        }
    }
    
    // Handle video file upload
    $video_path = '';
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['video/mp4', 'video/webm', 'video/ogg'];
        $file_type = $_FILES['video']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/videos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['video']['tmp_name'], $target_path)) {
                $video_path = 'uploads/videos/' . $file_name;
            } else {
                $error = "Error uploading video file.";
            }
        } else {
            $error = "Invalid file type. Only MP4, WebM, and OGG files are allowed.";
        }
    }
    
    if (empty($error)) {
        mysqli_begin_transaction($conn);
        try {
            // Insert content
            $query = "INSERT INTO content (title, description, thumbnail, video_path, release_year, duration, is_featured) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssiis", $title, $description, $thumbnail, $video_path, $release_year, $duration, $is_featured);
            
            if (mysqli_stmt_execute($stmt)) {
                $content_id = mysqli_insert_id($conn);
                
                // Insert categories
                if (!empty($selected_categories)) {
                    $cat_query = "INSERT INTO content_categories (content_id, category_id) VALUES (?, ?)";
                    $cat_stmt = mysqli_prepare($conn, $cat_query);
                    
                    foreach ($selected_categories as $category_id) {
                        mysqli_stmt_bind_param($cat_stmt, "ii", $content_id, $category_id);
                        mysqli_stmt_execute($cat_stmt);
                    }
                }
                
                mysqli_commit($conn);
                $success = "Content added successfully!";
                // Clear form data
                $_POST = array();
            } else {
                throw new Exception("Error adding content: " . mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Content - Flixnate Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Base styles */
        :root {
            --primary-gradient: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%);
            --dark-bg: #1E1E1E;
            --darker-bg: #2a2a2a;
            --border-color: #333;
            --text-color: #fff;
            --muted-text: #9B9B9B;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: var(--dark-bg);
        }

        /* Container */
        .admin-container {
            padding: 20px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 15px;
            }
        }

        /* Form */
        .content-form {
            background: var(--dark-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 576px) {
            .content-form {
                padding: 20px;
            }
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: var(--text-color);
            margin-bottom: 10px;
            font-weight: 500;
            font-size: 1rem;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--darker-bg);
            color: var(--text-color);
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            border-color: #8B5CF6;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
            outline: none;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
            min-height: 100px;
        }

        /* File Upload */
        .file-upload {
            border: 2px dashed var(--border-color);
            padding: 25px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--darker-bg);
        }

        .file-upload:hover {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.1);
        }

        .file-upload i {
            font-size: 32px;
            color: var(--muted-text);
            margin-bottom: 15px;
        }

        .file-upload p {
            color: var(--muted-text);
            margin: 0;
        }

        /* Preview Image */
        .preview-image {
            max-width: 100%;
            height: auto;
            margin-top: 15px;
            border-radius: 8px;
            display: none;
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 10px;
            background: var(--darker-bg);
            border-radius: 5px;
            margin-top: 15px;
            overflow: hidden;
        }

        #progress {
            height: 100%;
            background: var(--primary-gradient);
            transition: width 0.3s ease;
        }

        #progress-text {
            text-align: center;
            margin-top: 8px;
            color: var(--muted-text);
            font-size: 14px;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            min-width: 150px;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(75, 181, 67, 0.2);
            border: 1px solid #4BB543;
            color: #4BB543;
        }

        .alert-error {
            background: rgba(255, 71, 87, 0.2);
            border: 1px solid #ff4757;
            color: #ff4757;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted-text);
            text-decoration: none;
            margin-bottom: 25px;
            font-size: 16px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--text-color);
        }

        /* Responsive Typography */
        h1 {
            font-size: 2rem;
            margin-bottom: 30px;
            color: var(--text-color);
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 1.75rem;
                margin-bottom: 20px;
            }

            .btn {
                width: 100%;
            }

            .file-upload {
                padding: 15px;
            }

            .file-upload i {
                font-size: 24px;
            }
        }

        /* Dark Theme Enhancements */
        .dark-theme {
            background: #121212;
        }

        .dark-theme .content-form {
            background: var(--dark-bg);
        }

        /* Loading State */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* File Upload Info */
        .file-info {
            margin-top: 10px;
            font-size: 14px;
            color: var(--muted-text);
        }

        .file-info span {
            color: var(--text-color);
        }

        .category-select {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Category Select Styles */
        .select2-container {
            width: 100% !important;
        }
        
        .select2-container--default .select2-selection--multiple {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            min-height: 45px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #8B5CF6;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            margin: 5px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff;
            margin-right: 5px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #ff4757;
        }
        
        .select2-dropdown {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
        }
        
        .select2-container--default .select2-results__option {
            padding: 8px 12px;
        }
        
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #8B5CF6;
        }
        
        .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: var(--dark-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            padding: 8px;
        }
    </style>
</head>
<body class="dark-theme">
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <a href="content.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Content
        </a>

        <h1>Add New Content</h1>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="content-form">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" 
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                           placeholder="Enter content title"
                           required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Enter content description"
                              required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="categories">Categories</label>
                    <select id="categories" name="categories[]" multiple="multiple" class="category-select">
                        <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="release_year">Release Year</label>
                        <input type="number" id="release_year" name="release_year" min="1900" max="<?php echo date('Y'); ?>" 
                               value="<?php echo isset($_POST['release_year']) ? htmlspecialchars($_POST['release_year']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" min="1" 
                               value="<?php echo isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Thumbnail</label>
                    <div class="file-upload" onclick="document.getElementById('thumbnail').click()">
                        <i class="fas fa-image"></i>
                        <p>Click to upload thumbnail</p>
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div id="thumbnail-info" class="file-info"></div>
                    <img id="thumbnail-preview" class="preview-image">
                </div>

                <div class="form-group">
                    <label>Video File</label>
                    <div class="file-upload" onclick="document.getElementById('video').click()">
                        <i class="fas fa-video"></i>
                        <p>Click to upload video</p>
                        <input type="file" id="video" name="video" accept="video/*" onchange="handleVideoSelect(this)">
                    </div>
                    <div id="video-info" class="file-info"></div>
                    <div id="upload-progress" style="display: none;">
                        <div class="progress-bar">
                            <div id="progress" style="width: 0%"></div>
                        </div>
                        <p id="progress-text">0%</p>
                    </div>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_featured" value="1" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                        Feature this content
                    </label>
                </div>

                <div class="form-group">
                    <button type="submit" id="submit-btn" class="btn btn-primary">
                        <i class="fas fa-upload"></i>
                        Add Content
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.category-select').select2({
                placeholder: "Select categories",
                allowClear: true,
                theme: "default"
            });
        });

        function previewImage(input) {
            const preview = document.getElementById('thumbnail-preview');
            const info = document.getElementById('thumbnail-info');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                
                info.innerHTML = `Selected: <span>${file.name}</span> (${sizeMB} MB)`;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }

        function handleVideoSelect(input) {
            const info = document.getElementById('video-info');
            const file = input.files[0];
            if (file) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                info.innerHTML = `Selected: <span>${file.name}</span> (${sizeMB} MB)`;
                console.log(`Selected video: ${file.name} (${sizeMB} MB, ${file.type})`);
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const videoFile = document.getElementById('video').files[0];
            if (videoFile) {
                const sizeMB = videoFile.size / (1024 * 1024);
                if (sizeMB > 500) {
                    e.preventDefault();
                    alert('Video file size must be less than 500MB');
                    return;
                }
                
                const submitBtn = document.getElementById('submit-btn');
                const progress = document.getElementById('upload-progress');
                
                progress.style.display = 'block';
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                submitBtn.classList.add('loading');
                
                // Simulate upload progress
                let progressValue = 0;
                const interval = setInterval(() => {
                    progressValue += 5;
                    if (progressValue <= 100) {
                        document.getElementById('progress').style.width = progressValue + '%';
                        document.getElementById('progress-text').textContent = progressValue + '%';
                    } else {
                        clearInterval(interval);
                    }
                }, 500);
            }
        });
    </script>
</body>
</html>
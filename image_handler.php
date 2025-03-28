<?php
/**
 * Get the proper path for an image, with fallback to default thumbnail
 */
function getImagePath($path) {
    // If path is empty or file doesn't exist, return default thumbnail
    if (empty($path) || !file_exists(__DIR__ . '/../' . $path)) {
        return createOrGetDefaultThumbnail();
    }
    return $path;
}

/**
 * Get or create default thumbnail
 */
function createOrGetDefaultThumbnail() {
    $defaultPath = 'assets/images/default-thumbnail.jpg';
    $fullPath = __DIR__ . '/../' . $defaultPath;
    
    // Create assets/images directory if it doesn't exist
    if (!file_exists(dirname($fullPath))) {
        mkdir(dirname($fullPath), 0777, true);
    }
    
    // Create default thumbnail if it doesn't exist
    if (!file_exists($fullPath)) {
        // Create image
        $width = 300;
        $height = 450;
        $image = imagecreatetruecolor($width, $height);
        
        // Set colors
        $bgColor = imagecolorallocate($image, 40, 40, 40);
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // Fill background
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // Add text
        $text = "No Image";
        imagestring($image, 5, 100, 200, $text, $textColor);
        
        // Save image
        imagejpeg($image, $fullPath, 90);
        imagedestroy($image);
        
        // Set permissions
        chmod($fullPath, 0644);
    }
    
    return $defaultPath;
}

/**
 * Ensure required directories exist
 */
function ensureDirectoriesExist() {
    $dirs = [
        __DIR__ . '/../assets/images',
        __DIR__ . '/../thumbnails',
        __DIR__ . '/../videos'
    ];

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

// Initialize: ensure directories exist and create default thumbnail
ensureDirectoriesExist();
createOrGetDefaultThumbnail();
?> 
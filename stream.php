<?php
require_once 'includes/init.php';
require_once 'includes/db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers to allow video playback
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, X-CSRF-Token');
header('Access-Control-Expose-Headers: Accept-Ranges, Content-Encoding, Content-Length, Content-Range');

// Function to log errors
function logError($message) {
    error_log("Stream Error: " . $message);
}

// Get video ID from URL
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
logError("Streaming video ID: " . $video_id);

if (!$video_id) {
    logError("No video ID provided");
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Get video details from database
$stmt = mysqli_prepare($conn, "SELECT video_path, video_url FROM content WHERE id = ?");
if (!$stmt) {
    logError("Failed to prepare video query: " . mysqli_error($conn));
    header("HTTP/1.0 500 Internal Server Error");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $video_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    logError("Video not found for ID: " . $video_id);
    header("HTTP/1.0 404 Not Found");
    exit;
}

$video = mysqli_fetch_assoc($result);

// Determine the video path
$video_path = '';
if (!empty($video['video_url'])) {
    $video_path = $video['video_url'];
    logError("Using video URL: " . $video_path);
} elseif (!empty($video['video_path'])) {
    if (strpos($video['video_path'], 'http') === 0) {
        $video_path = $video['video_path'];
    } else {
        $video_path = strpos($video['video_path'], 'uploads/') === 0 
            ? $video['video_path'] 
            : 'uploads/videos/' . basename($video['video_path']);
    }
    logError("Using video path: " . $video_path);
}

if (empty($video_path)) {
    logError("No video source found");
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Handle external URLs
if (strpos($video_path, 'http') === 0) {
    logError("Redirecting to external URL: " . $video_path);
    header("Location: " . $video_path);
    exit;
}

// Check if file exists and is readable
if (!file_exists($video_path)) {
    logError("Video file does not exist: " . $video_path);
    header("HTTP/1.0 404 Not Found");
    exit;
}

if (!is_readable($video_path)) {
    logError("Video file is not readable: " . $video_path);
    header("HTTP/1.0 403 Forbidden");
    exit;
}

// Get file size
$file_size = filesize($video_path);
logError("File size: " . $file_size . " bytes");

// Handle range requests
$range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;
if ($range) {
    $ranges = array_map('trim', explode('=', $range));
    $positions = array_map('trim', explode('-', $ranges[1]));
    
    $start = $positions[0];
    $end = isset($positions[1]) && $positions[1] !== '' ? $positions[1] : $file_size - 1;
    
    if ($end >= $file_size) {
        $end = $file_size - 1;
    }
    
    $length = $end - $start + 1;
    
    header("HTTP/1.1 206 Partial Content");
    header("Content-Range: bytes $start-$end/$file_size");
    header("Content-Length: $length");
} else {
    header("Content-Length: $file_size");
}

// Set content type based on file extension
$extension = strtolower(pathinfo($video_path, PATHINFO_EXTENSION));
logError("File extension detected: " . $extension);

switch ($extension) {
    case 'webm':
        $content_type = "video/webm";
        break;
    case 'mp4':
        $content_type = "video/mp4";
        break;
    case 'ogg':
    case 'ogv':
        $content_type = "video/ogg";
        break;
    case 'mov':
        $content_type = "video/quicktime";
        break;
    case 'avi':
        $content_type = "video/x-msvideo";
        break;
    case 'wmv':
        $content_type = "video/x-ms-wmv";
        break;
    case 'flv':
        $content_type = "video/x-flv";
        break;
    case 'mkv':
        $content_type = "video/x-matroska";
        break;
    default:
        $content_type = "application/octet-stream";
}

logError("Setting Content-Type: " . $content_type);
header("Content-Type: " . $content_type);

// Enable cross-origin resource sharing
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Range");
header("Accept-Ranges: bytes");

// Prevent caching for dynamic content
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Set Content-Disposition to inline to ensure browser plays the video instead of downloading
header("Content-Disposition: inline; filename=\"" . basename($video_path) . "\"");

// Open file for reading
$fp = fopen($video_path, 'rb');
if (!$fp) {
    logError("Failed to open video file: " . $video_path);
    header("HTTP/1.0 500 Internal Server Error");
    exit;
}

// Seek to start position if range request
if ($range) {
    fseek($fp, $start);
}

// Stream the video
$buffer = 8192; // 8KB chunks
$sent = 0;
while (!feof($fp) && $sent < $file_size) {
    $chunk = fread($fp, $buffer);
    $sent += strlen($chunk);
    echo $chunk;
    flush();
}

fclose($fp);
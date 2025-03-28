<?php
session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if content ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid content ID.";
    header('Location: content.php');
    exit();
}

$content_id = intval($_GET['id']);

// Debug log
error_log("Attempting to delete content ID: " . $content_id);

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // First, check if content exists
    $check_stmt = mysqli_prepare($conn, "SELECT id, video_path, thumbnail FROM content WHERE id = ?");
    if (!$check_stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "i", $content_id);
    if (!mysqli_stmt_execute($check_stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($check_stmt));
    }
    
    $result = mysqli_stmt_get_result($check_stmt);
    $content = mysqli_fetch_assoc($result);
    
    if (!$content) {
        throw new Exception("Content with ID $content_id not found.");
    }

    // Debug log
    error_log("Found content to delete: " . print_r($content, true));

    // Delete from user_ratings
    $stmt = mysqli_prepare($conn, "DELETE FROM user_ratings WHERE content_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    mysqli_stmt_execute($stmt);
    error_log("Deleted user ratings for content ID: " . $content_id);

    // Delete from user_comments
    $stmt = mysqli_prepare($conn, "DELETE FROM user_comments WHERE content_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    mysqli_stmt_execute($stmt);
    error_log("Deleted user comments for content ID: " . $content_id);

    // Delete from watchlist
    $stmt = mysqli_prepare($conn, "DELETE FROM watchlist WHERE content_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    mysqli_stmt_execute($stmt);
    error_log("Deleted watchlist entries for content ID: " . $content_id);

    // Delete from content_categories
    $stmt = mysqli_prepare($conn, "DELETE FROM content_categories WHERE content_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    mysqli_stmt_execute($stmt);
    error_log("Deleted categories for content ID: " . $content_id);

    // Finally, delete the content itself
    $stmt = mysqli_prepare($conn, "DELETE FROM content WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_affected_rows($conn) === 0) {
        throw new Exception("Failed to delete content. No rows affected.");
    }

    error_log("Successfully deleted content ID: " . $content_id);

    // Try to delete files if they exist
    if (!empty($content['video_path'])) {
        $video_path = "../" . $content['video_path'];
        if (file_exists($video_path)) {
            if (!unlink($video_path)) {
                error_log("Warning: Could not delete video file: " . $video_path);
            }
        }
    }

    if (!empty($content['thumbnail'])) {
        $thumbnail_path = "../" . $content['thumbnail'];
        if (file_exists($thumbnail_path)) {
            if (!unlink($thumbnail_path)) {
                error_log("Warning: Could not delete thumbnail file: " . $thumbnail_path);
            }
        }
    }

    // Commit the transaction
    mysqli_commit($conn);
    $_SESSION['success'] = "Content deleted successfully.";
    error_log("Delete operation completed successfully for content ID: " . $content_id);

} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($conn);
    error_log("Error deleting content: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting content: " . $e->getMessage();
}

// Redirect back to content page
header('Location: content.php');
exit();
?>

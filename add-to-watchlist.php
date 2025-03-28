<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$content_id = (int)$data['content_id'];
$user_id = $_SESSION['user_id'];

// Check if already in watchlist
$check_query = "SELECT * FROM watchlist WHERE user_id = $user_id AND content_id = $content_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['success' => false, 'message' => 'Already in watchlist']);
    exit();
}

// Add to watchlist
$query = "INSERT INTO watchlist (user_id, content_id) VALUES ($user_id, $content_id)";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
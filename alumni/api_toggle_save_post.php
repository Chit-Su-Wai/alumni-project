<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Login required']);
    exit;
}

require_once "../config/db.php";
require_once "../include/request_guard.php";

$userId = (int) $_SESSION['user_id'];
$postId = (int) ($_POST['post_id'] ?? 0);

if ($postId <= 0) {
    echo json_encode(['error' => 'Invalid post']);
    exit;
}

if (request_guard_is_duplicate('api_toggle_save_post', [
    'user_id' => $userId,
    'post_id' => $postId,
], 2)) {
    $exists = $conn->prepare('SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ? LIMIT 1');
    $exists->bind_param('ii', $userId, $postId);
    $exists->execute();
    echo json_encode(['success' => true, 'saved' => $exists->get_result()->num_rows > 0]);
    exit;
}

$checkPost = $conn->prepare('SELECT id FROM posts WHERE id = ? LIMIT 1');
$checkPost->bind_param('i', $postId);
$checkPost->execute();
if ($checkPost->get_result()->num_rows === 0) {
    echo json_encode(['error' => 'Post not found']);
    exit;
}

$exists = $conn->prepare('SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ? LIMIT 1');
$exists->bind_param('ii', $userId, $postId);
$exists->execute();

if ($exists->get_result()->num_rows > 0) {
    $stmt = $conn->prepare('DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?');
    $stmt->bind_param('ii', $userId, $postId);
    $stmt->execute();
    echo json_encode(['success' => true, 'saved' => false]);
    exit;
}

$stmt = $conn->prepare('INSERT IGNORE INTO saved_posts (user_id, post_id) VALUES (?, ?)');
$stmt->bind_param('ii', $userId, $postId);
$stmt->execute();

echo json_encode(['success' => true, 'saved' => true]);

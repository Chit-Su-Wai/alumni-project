<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Login required"]);
    exit;
}

require_once "../config/db.php";
require_once "../include/request_guard.php";

$user_id = $_SESSION['user_id'];
$post_id = (int)($_POST['post_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($post_id <= 0 || empty($comment)) {
    echo json_encode(["error" => "Invalid data"]);
    exit;
}

if (request_guard_is_duplicate('api_comment', [
    'user_id' => $user_id,
    'post_id' => $post_id,
    'comment' => $comment,
])) {
    echo json_encode(["error" => "Duplicate comment request"]);
    exit;
}

/* Insert Comment */
$stmt = $conn->prepare("INSERT INTO comments(post_id, user_id, comment) VALUES(?,?,?)");
$stmt->bind_param("iis", $post_id, $user_id, $comment);
$stmt->execute();

$comment_id = $stmt->insert_id;

/* Get User Name */
$userStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userName = $userStmt->get_result()->fetch_assoc()['name'];

/* Get Comment Count */
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM comments WHERE post_id = ?");
$countStmt->bind_param("i", $post_id);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];

echo json_encode([
    "success" => true,
    "comment_id" => (int)$comment_id,
    "name" => htmlspecialchars($userName),
    "comment" => htmlspecialchars($comment),
    "count" => (int)$total,
    "time" => date("M d, Y h:i A")
]);

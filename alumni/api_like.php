<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Login required"]);
    exit;
}

require_once "../config/db.php";
require_once "../include/request_guard.php";

$user_id = $_SESSION["user_id"];
$post_id = (int)($_POST["post_id"] ?? 0);

if ($post_id <= 0) {
    echo json_encode(["error" => "Invalid post"]);
    exit;
}

if (request_guard_is_duplicate('api_like', [
    'user_id' => $user_id,
    'post_id' => $post_id,
], 2)) {
    $check = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
    $check->bind_param("ii", $post_id, $user_id);
    $check->execute();
    $liked = $check->get_result()->num_rows > 0;

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM post_likes WHERE post_id = ?");
    $countStmt->bind_param("i", $post_id);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];

    echo json_encode([
        "liked" => $liked,
        "count" => (int)$total
    ]);
    exit;
}

/* Check Like Exists */
$check = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
$check->bind_param("ii", $post_id, $user_id);
$check->execute();
$result = $check->get_result();

/* Unlike */
if ($result->num_rows > 0) {
    $delete = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
    $delete->bind_param("ii", $post_id, $user_id);
    $delete->execute();
    $liked = false;
}
/* Like */
else {
    $insert = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    $insert->bind_param("ii", $post_id, $user_id);
    $insert->execute();
    $liked = true;
}

/* Get Updated Count */
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM post_likes WHERE post_id = ?");
$countStmt->bind_param("i", $post_id);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];

echo json_encode([
    "liked" => $liked,
    "count" => (int)$total
]);

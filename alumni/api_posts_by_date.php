<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once "../config/db.php";

header('Content-Type: application/json');

$date = $_GET['date'] ?? null;

if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    /* Specific date selected */
    $stmt = $conn->prepare("
        SELECT p.id, p.content, p.created_at, u.name, u.profile_image
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        WHERE u.role = 'user'
          AND DATE(p.created_at) = ?
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("s", $date);
} else {
    /* Default: last 3 days */
    $stmt = $conn->prepare("
        SELECT p.id, p.content, p.created_at, u.name, u.profile_image
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        WHERE u.role = 'user'
          AND p.created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
}

$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = [
        'id'            => $row['id'],
        'content'       => substr($row['content'], 0, 40),
        'created_at'    => $row['created_at'],
        'date_label'    => date('M d', strtotime($row['created_at'])),
        'name'          => $row['name'],
        'profile_image' => !empty($row['profile_image']) ? $row['profile_image'] : '../images/default-avatar.svg',
    ];
}

echo json_encode(['posts' => $posts]);

<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Login required"]);
    exit;
}

require_once "../config/db.php";

$current_user_id = (int)$_SESSION["user_id"];
$receiver_id = (int)($_GET["user_id"] ?? 0);
$last_id = (int)($_GET["last_id"] ?? 0);

if ($receiver_id <= 0) {
    echo json_encode(["messages" => []]);
    exit;
}

/* Fetch New Messages */
$stmt = $conn->prepare("
    SELECT id, sender_id, message, created_at
    FROM messages
    WHERE
        ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND id > ?
    ORDER BY created_at ASC
");

$stmt->bind_param("iiiii", $current_user_id, $receiver_id, $receiver_id, $current_user_id, $last_id);
$stmt->execute();

$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        "id" => (int)$row['id'],
        "sender_id" => (int)$row['sender_id'],
        "message" => htmlspecialchars($row['message']),
        "time" => date("M d, h:i A", strtotime($row['created_at'])),
        "is_mine" => $row['sender_id'] == $current_user_id
    ];
}

echo json_encode(["messages" => $messages]);

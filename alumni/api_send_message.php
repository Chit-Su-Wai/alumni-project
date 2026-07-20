<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Login required"]);
    exit;
}

require_once "../config/db.php";
require_once "../include/request_guard.php";

$sender_id = (int)$_SESSION["user_id"];
$receiver_id = (int)($_POST["receiver_id"] ?? 0);
$message = trim($_POST["message"] ?? "");

if ($receiver_id <= 0 || empty($message)) {
    echo json_encode(["error" => "Invalid data"]);
    exit;
}

if (request_guard_is_duplicate('api_send_message', [
    'sender_id' => $sender_id,
    'receiver_id' => $receiver_id,
    'message' => $message,
])) {
    echo json_encode(["error" => "Duplicate message request"]);
    exit;
}

/* Check receiver is not admin */
$checkStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$checkStmt->bind_param("i", $receiver_id);
$checkStmt->execute();
$receiverRole = $checkStmt->get_result()->fetch_assoc();

if ($receiverRole && $receiverRole['role'] === 'admin') {
    echo json_encode(["error" => "Cannot message admin"]);
    exit;
}

/* Insert Message */
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $sender_id, $receiver_id, $message);
$stmt->execute();

$message_id = $stmt->insert_id;

$senderNameStmt = $conn->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
$senderNameStmt->bind_param('i', $sender_id);
$senderNameStmt->execute();
$senderName = $senderNameStmt->get_result()->fetch_assoc()['name'] ?? 'Someone';

push_notification(
    $conn,
    $receiver_id,
    'message',
    'New Message',
    $senderName . ' sent you a message.',
    'message.php?user_id=' . $sender_id
);

echo json_encode([
    "success" => true,
    "message_id" => (int)$message_id,
    "message" => htmlspecialchars($message),
    "time" => date("M d, h:i A")
]);

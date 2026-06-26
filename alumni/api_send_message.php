<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Login required"]);
    exit;
}

require_once "../config/db.php";

$sender_id = (int)$_SESSION["user_id"];
$receiver_id = (int)($_POST["receiver_id"] ?? 0);
$message = trim($_POST["message"] ?? "");

if ($receiver_id <= 0 || empty($message)) {
    echo json_encode(["error" => "Invalid data"]);
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

echo json_encode([
    "success" => true,
    "message_id" => (int)$message_id,
    "message" => htmlspecialchars($message),
    "time" => date("M d, h:i A")
]);


<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";
require_once "../include/request_guard.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $sender_id = (int)$_SESSION["user_id"];
    $receiver_id = (int)$_POST["receiver_id"];
    $message = trim($_POST["message"]);

    if (!empty($message)) {

        if (request_guard_is_duplicate('send_message', [
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'message' => $message,
        ])) {
            header("Location: message.php?user_id=" . $receiver_id);
            exit;
        }

        $checkStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $checkStmt->bind_param("i", $receiver_id);
        $checkStmt->execute();
        $receiverRole = $checkStmt->get_result()->fetch_assoc();

        if ($receiverRole && $receiverRole['role'] === 'admin') {
            header("Location: message.php");
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO messages
            (
                sender_id,
                receiver_id,
                message
            )
            VALUES
            (
                ?,
                ?,
                ?
            )
        ");

        $stmt->bind_param(
            "iis",
            $sender_id,
            $receiver_id,
            $message
        );

        $stmt->execute();

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
    }

    header(
        "Location: message.php?user_id=" .
        $receiver_id
    );

    exit;
}
?>

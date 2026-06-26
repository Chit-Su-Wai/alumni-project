
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $sender_id = (int)$_SESSION["user_id"];
    $receiver_id = (int)$_POST["receiver_id"];
    $message = trim($_POST["message"]);

    if (!empty($message)) {

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
    }

    header(
        "Location: message.php?user_id=" .
        $receiver_id
    );

    exit;
}
?>


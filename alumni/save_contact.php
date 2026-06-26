
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: contact.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$message = trim($_POST["message"] ?? "");

if (
    empty($name) ||
    empty($email) ||
    empty($subject) ||
    empty($message)
) {
    header("Location: contact.php");
    exit;
}

$stmt = $conn->prepare("
INSERT INTO contact_messages
(
    user_id,
    name,
    email,
    subject,
    message
)
VALUES
(
    ?,
    ?,
    ?,
    ?,
    ?
)
");

$stmt->bind_param(
    "issss",
    $user_id,
    $name,
    $email,
    $subject,
    $message
);

$stmt->execute();

header("Location: contact.php?success=1");
exit;
?>

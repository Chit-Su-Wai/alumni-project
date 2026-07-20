<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";
require_once "../include/request_guard.php";

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

if (request_guard_is_duplicate('save_contact', [
    'user_id' => $user_id,
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'message' => $message,
])) {
    header("Location: contact.php?success=1");
    exit;
}

/* Get user's profile image */
$getUser = $conn->prepare("
    SELECT profile_image
    FROM users
    WHERE id = ?
");

$getUser->bind_param("i", $user_id);
$getUser->execute();

$user = $getUser->get_result()->fetch_assoc();

$profile_image = $user['profile_image'] ?? "";
$stmt = $conn->prepare("
INSERT INTO contact_messages
(
    user_id,
    name,
    email,
    profile_image,
    subject,
    message
)
VALUES
(
    ?, ?, ?, ?, ?, ?
)
");

$stmt->bind_param(
    "isssss",
    $user_id,
    $name,
    $email,
    $profile_image,
    $subject,
    $message
);

$stmt->execute();

$stmt->close();
$getUser->close();
$conn->close();

header("Location: contact.php?success=1");
exit;

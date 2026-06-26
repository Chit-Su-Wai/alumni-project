<?php
/**
 * Auth Check — include at top of all protected alumni pages
 * Validates session and updates online status
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';

// Update online status and last_seen
$_auth_stmt = $conn->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?");
$_auth_stmt->bind_param("i", $_SESSION['user_id']);
$_auth_stmt->execute();
$_auth_stmt->close();

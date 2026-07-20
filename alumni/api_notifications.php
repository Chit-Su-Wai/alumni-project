<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Login required']);
    exit;
}

require_once "../config/db.php";

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $limit = min((int)($_GET['limit'] ?? 20), 50);

    $stmt = $conn->prepare("
        SELECT id, type, title, body, link, is_read, created_at
        FROM notifications
        WHERE user_id = ? AND is_read = 0 AND type = 'message'
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $row['time_ago'] = timeAgo($row['created_at']);
        $row['icon'] = notifIcon($row['type']);
        $row['type_label'] = typeLabel($row['type']);
        $notifications[] = $row;
    }

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0 AND type = 'message'");
    $countStmt->bind_param('i', $userId);
    $countStmt->execute();
    $unreadCount = (int) $countStmt->get_result()->fetch_assoc()['total'];

    $totalStmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ?");
    $totalStmt->bind_param('i', $userId);
    $totalStmt->execute();
    $totalCount = (int) $totalStmt->get_result()->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'total_count' => $totalCount
    ]);
    exit;
}

if ($action === 'mark_read') {
    $notifId = (int) ($_POST['id'] ?? 0);
    if ($notifId > 0) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $notifId, $userId);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'mark_all_read') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0 AND type = 'message'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete') {
    $notifId = (int) ($_POST['id'] ?? 0);
    if ($notifId > 0) {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $notifId, $userId);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit;
}

function timeAgo($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'min ago';
    return 'just now';
}

function notifIcon($type)
{
    switch ($type) {
        case 'message':
            return 'fa-regular fa-comment';
        case 'announcement':
            return 'fa-solid fa-bullhorn';
        case 'job':
            return 'fa-solid fa-briefcase';
        default:
            return 'fa-regular fa-bell';
    }
}

function typeLabel($type)
{
    switch ($type) {
        case 'message':
            return 'Message';
        case 'announcement':
            return 'Announcement';
        case 'job':
            return 'Job';
        default:
            return 'Notification';
    }
}

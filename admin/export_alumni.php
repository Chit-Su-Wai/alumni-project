<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'admin'
) {
    header("Location: ../alumni/login.php");
    exit;
}

require_once "../config/db.php";

$search = trim($_GET['search'] ?? '');

if ($search != '') {
    $stmt = $conn->prepare("
        SELECT
            id,
            approved_id,
            name,
            email,
            phone,
            address,
            bio,
            facebook,
            linkedin,
            github,
            telegram,
            instagram,
            youtube,
            created_at
        FROM users
        WHERE role = 'user'
        AND (
            name LIKE ?
            OR email LIKE ?
            OR approved_id LIKE ?
        )
        ORDER BY id DESC
    ");

    $keyword = "%{$search}%";
    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
} else {
    $stmt = $conn->prepare("
        SELECT
            id,
            approved_id,
            name,
            email,
            phone,
            address,
            bio,
            facebook,
            linkedin,
            github,
            telegram,
            instagram,
            youtube,
            created_at
        FROM users
        WHERE role = 'user'
        ORDER BY id DESC
    ");
}

$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="alumni_list_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, ['ID', 'Approved ID', 'Name', 'Email', 'Phone', 'Address', 'Bio', 'Facebook', 'LinkedIn', 'GitHub', 'Telegram', 'Instagram', 'YouTube', 'Joined']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['approved_id'],
        $row['name'],
        $row['email'],
        $row['phone'] ?? '',
        $row['address'] ?? '',
        $row['bio'] ?? '',
        $row['facebook'] ?? '',
        $row['linkedin'] ?? '',
        $row['github'] ?? '',
        $row['telegram'] ?? '',
        $row['instagram'] ?? '',
        $row['youtube'] ?? '',
        date('M d, Y', strtotime($row['created_at']))
    ]);
}

fclose($output);
exit;

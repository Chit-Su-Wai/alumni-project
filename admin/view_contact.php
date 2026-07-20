
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'admin'
) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/db.php";

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $replyMessage = trim($_POST['reply_message'] ?? '');
    if ($replyMessage !== '') {
        $replyStmt = $conn->prepare("
            UPDATE contact_messages
            SET is_read = 1, replied_at = NOW(), reply_message = ?, reply_admin_id = ?
            WHERE id = ?
        ");
        $replyAdminId = (int) $_SESSION['user_id'];
        $replyStmt->bind_param('sii', $replyMessage, $replyAdminId, $id);
        $replyStmt->execute();
    }

    header("Location: view_contact.php?id=" . $id);
    exit;
}

/* Mark As Read */

$update = $conn->prepare("
UPDATE contact_messages
SET is_read = 1
WHERE id = ?
");

$update->bind_param("i", $id);
$update->execute();

/* Get Message */

$stmt = $conn->prepare("
SELECT *
FROM contact_messages
WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$message = $stmt->get_result()->fetch_assoc();

if (!$message) {

    die("Message not found.");

}
?>



<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>View Contact Message</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<div class="min-h-screen flex">

<?php include "../include/admin_header.php"; ?>

<div class="flex-1 p-6">

    <div class="max-w-5xl mx-auto">

        <!-- Header -->

        <div class="admin-page-head">

            <div class="title-wrap">
                <div class="admin-title-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                <div>
                    <h1 class="admin-page-title">Contact Message</h1>
                    <p class="admin-page-sub">View full contact message</p>
                </div>
            </div>

            <a href="contacts.php"
               class="btn btn-ghost">

                <i class="fa-solid fa-arrow-left mr-1"></i> Back

            </a>

        </div>

        <!-- Card -->

<div class="admin-card">

            <div class="p-6 border-b">

                <div class="flex items-center gap-3">

                    <h2 class="text-2xl font-bold">

                        <?= htmlspecialchars($message['subject']) ?>

                    </h2>

                    <span class="badge badge-green">

                        Read

                    </span>

                </div>

            </div>

            <div class="p-6 space-y-6">

                <div>

                    <label
                    class="block text-sm text-slate-500">

                        Name

                    </label>

                    <p
                    class="text-lg font-semibold mt-1">

                        <?= htmlspecialchars($message['name']) ?>

                    </p>

                </div>

                <div>

                    <label class="block text-sm text-slate-500">

                        Reply Note

                    </label>

                    <form method="POST" class="mt-2 space-y-3">

                        <textarea name="reply_message" rows="4" class="input-base" placeholder="Add a short reply note..."><?= htmlspecialchars($message['reply_message'] ?? '') ?></textarea>

                        <div class="flex items-center gap-3">

                            <button type="submit" class="btn btn-primary btn-sm">

                                <i class="fa-solid fa-reply mr-1"></i> Mark Replied

                            </button>

                            <?php if (!empty($message['replied_at'])): ?>

                                <span class="badge badge-green">Replied</span>

                            <?php else: ?>

                                <span class="badge badge-slate">Not Replied</span>

                            <?php endif; ?>

                        </div>

                    </form>

                </div>

                <div>

                    <label
                    class="block text-sm text-slate-500">

                        Email

                    </label>

                    <p
                    class="font-semibold mt-1">

                        <?= htmlspecialchars($message['email']) ?>

                    </p>

                </div>

                <div>

                    <label
                    class="block text-sm text-slate-500">

                        Message

                    </label>

                    <div
                    class="mt-2 bg-slate-50 rounded-2xl p-5 leading-7">

                        <?= nl2br(
                            htmlspecialchars(
                                $message['message']
                            )
                        ) ?>

                    </div>

                </div>

                <div>

                    <label
                    class="block text-sm text-slate-500">

                        Sent Date

                    </label>

                    <p class="font-semibold mt-1">

                        <?= date(
                            "M d, Y h:i A",
                            strtotime(
                                $message['created_at']
                            )
                        ) ?>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>

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

/* Delete Message */

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    $stmt = $conn->prepare("
    DELETE FROM contact_messages
    WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    admin_log_activity(
        $conn,
        'Delete Contact',
        'Deleted contact message #' . $id,
        'contact',
        $id
    );

    header("Location: contacts.php");
    exit;
}

/* Pagination */

$limit = 5;

$page = max(
    1,
    (int) ($_GET['page'] ?? 1)
);

$offset = ($page - 1) * $limit;

/* Total Messages */

$totalMessages = $conn->query("
SELECT COUNT(*) total
FROM contact_messages
")->fetch_assoc()['total'];

$readMessages = $conn->query("
SELECT COUNT(*) total
FROM contact_messages
WHERE is_read = 1
")->fetch_assoc()['total'];

$unreadMessages = $conn->query("
SELECT COUNT(*) total
FROM contact_messages
WHERE is_read = 0
")->fetch_assoc()['total'];

$repliedMessages = $conn->query("
SELECT COUNT(*) total
FROM contact_messages
WHERE replied_at IS NOT NULL
")->fetch_assoc()['total'];

$totalPages = ceil(
    $totalMessages / $limit
);

/* Messages */

$stmt = $conn->prepare("
SELECT *
FROM contact_messages
ORDER BY is_read ASC,
created_at DESC
LIMIT ?, ?
");

$stmt->bind_param(
    "ii",
    $offset,
    $limit
);

$stmt->execute();

$messages = $stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Contact Messages</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

    <div class="min-h-screen flex">

        <?php include "../include/admin_header.php"; ?>

        <div class="flex-1 flex flex-col min-h-screen p-4">

            <!-- Header -->

            <div class="admin-page-head">

                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <h1 class="admin-page-title">Contact Messages</h1>
                        <p class="admin-page-sub">Read and manage contact messages</p>
                    </div>
                </div>

            </div>

            <!-- Compact Stats -->
            <div class="admin-stagger grid grid-cols-2 xl:grid-cols-4 gap-2.5 mb-4">
                <div class="admin-stat">
                    <span class="stat-spark"></span>
                    <div class="stat-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="stat-value"><?= $totalMessages ?></div>
                    <div class="stat-label">Total Messages</div>
                </div>
                <div class="admin-stat">
                    <span class="stat-spark"></span>
                    <div class="stat-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                    <div class="stat-value"><?= $readMessages ?></div>
                    <div class="stat-label">Read</div>
                </div>
                <div class="admin-stat">
                    <span class="stat-spark"></span>
                    <div class="stat-icon"><i class="fa-solid fa-envelope-open"></i></div>
                    <div class="stat-value"><?= $unreadMessages ?></div>
                    <div class="stat-label">Unread</div>
                </div>
                <div class="admin-stat">
                    <span class="stat-spark"></span>
                    <div class="stat-icon"><i class="fa-solid fa-reply"></i></div>
                    <div class="stat-value"><?= $repliedMessages ?></div>
                    <div class="stat-label">Replied</div>
                </div>
            </div>

            <!-- Messages -->

            <div class="admin-stagger grid md:grid-cols-2 xl:grid-cols-3 gap-3 flex-1 content-start">

                <?php while ($msg = $messages->fetch_assoc()): ?>

                    <div class="admin-card p-4">

                        <div class="flex items-start gap-3">

                            <?php
                            $image = !empty($msg['profile_image'])
                                ? $msg['profile_image']
                                : "../images/default-avatar.svg";
                            ?>

                            <img src="<?= htmlspecialchars($image) ?>"
                                class="admin-avatar shrink-0 cursor-zoom-in" alt="Profile"
                                onclick="openLightbox(this.src, '<?= htmlspecialchars($msg['name']) ?>')">
                            <div class="min-w-0 flex-1">

                                <h2 class="font-semibold text-slate-800 truncate">

                                    <?= htmlspecialchars($msg['name']) ?>

                                </h2>

                                <p class="text-sm text-slate-500 truncate">

                                    <?= htmlspecialchars($msg['email']) ?>

                                </p>

                            </div>

                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">

                            <span class="badge badge-cyan">

                                <?= htmlspecialchars($msg['subject']) ?>

                            </span>

                            <?php if ($msg['is_read'] == 0): ?>

                                <span class="badge badge-red">

                                    Unread

                                </span>

                            <?php else: ?>

                                <span class="badge badge-green">

                                    Read

                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="mt-4 space-y-3 text-sm">

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Message
                                </p>

                                <p class="mt-1 text-slate-700 leading-6">

                                    <?= htmlspecialchars(
                                        substr($msg['message'], 0, 120)
                                    ) ?>

                                    ...

                                </p>

                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Date
                                </p>
                                <p class="mt-1 text-slate-500">

                                    <?= date(
                                        "M d, Y h:i A",
                                        strtotime($msg['created_at'])
                                    ) ?>

                                </p>
                            </div>

                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">

                            <a href="view_contact.php?id=<?= $msg['id'] ?>" class="btn btn-ghost btn-sm">

                                View

                            </a>

                            <a href="?delete=<?= $msg['id'] ?>" onclick="event.preventDefault(); confirmDialog('Delete this message?', function(){ window.location.href='?delete=<?= $msg['id'] ?>'; }, {title:'Delete Message', confirmText:'Delete'})" class="btn btn-danger btn-sm">

                                Delete

                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

            <!-- Pagination -->
            <?php if ($totalMessages > 0): ?>
            <div class="mt-4 flex flex-col items-center gap-2 pb-4">
                <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span> Showing <strong
                            class="text-slate-700"><?= $offset + 1 ?>–<?= min($offset + $limit, $totalMessages) ?></strong>
                        of <strong class="text-slate-700"><?= $totalMessages ?></strong> </span>
                </div>
                <!-- Controls Row -->
                <div class="pagination">
                    <?php if ($page > 1): ?> <a href="?page=<?= $page - 1 ?>">
                            <i class="fa-solid fa-chevron-left text-xs"></i> Previous </a> <?php else: ?> <span
                            class="disabled"><i class="fa-solid fa-chevron-left text-xs"></i> Previous </span> <?php endif; ?>
                    <!-- Page Numbers -->
                    <?php $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2); ?>
                    <?php if ($startPage > 1): ?> <a href="?page=1">1</a>
                        <?php if ($startPage > 2): ?> <span class="px-1 text-slate-400">…</span> <?php endif; ?>
                    <?php endif; ?>     <?php for ($i = $startPage; $i <= $endPage; $i++): ?> <a href="?page=<?= $i ?>"
                            class="<?= $page == $i ? 'active' : '' ?>">
                            <?= $i ?> </a> <?php endfor; ?>     <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?> <span class="px-1 text-slate-400">…</span>
                        <?php endif; ?> <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                    <?php endif; ?> <!-- Next --> <?php if ($page < $totalPages): ?> <a
                            href="?page=<?= $page + 1 ?>">
                            Next <i class="fa-solid fa-chevron-right text-xs"></i> </a> <?php else: ?> <span
                            class="disabled">Next <i class="fa-solid fa-chevron-right text-xs"></i> </span> <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <?php
            $es_icon    = 'fa-regular fa-envelope';
            $es_title   = 'No messages found';
            $es_message = 'There are no contact messages to display yet.';
            $es_action  = '';
            include '../include/empty_state.php';
            ?>
            <?php endif; ?>
        </div>

    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>

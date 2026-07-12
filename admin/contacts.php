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

        <div class="flex-1 flex flex-col min-h-screen p-6">

            <!-- Header -->

            <div class="flex items-center justify-between mb-6">

                <h1 class="text-3xl font-bold text-teal-700">
                    Contact Messages
                </h1>

            </div>

            <!-- Total Messages -->

            <!-- <div class="bg-white rounded-2xl p-3 shadow mb-6">

            <p class="text-sm text-slate-500">
                Total Messages
            </p>

            <h2 class="text-2xl font-black text-teal-700 mt-1">

               

            </h2>

        </div> -->
            <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 rounded-xl shadow-sm p-3 mb-6 w-[300px]">

                <p class="text-sm text-slate-500">
                    Total Messages
                </p>

                <h2 class="text-2xl font-bold text-teal-700 mt-1">
                    <?= $totalMessages ?>
                </h2>

            </div>

            <!-- Messages -->

            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 flex-1 content-start">

                <?php while ($msg = $messages->fetch_assoc()): ?>

                    <div class="bg-white rounded-2xl p-4 shadow">

                        <div class="flex items-start gap-3">

                            <?php
                            $image = !empty($msg['profile_image'])
                                ? $msg['profile_image']
                                : "../images/default-avatar.svg";
                            ?>

                            <img src="<?= htmlspecialchars($image) ?>"
                                class="h-10 w-10 rounded-full object-cover border border-cyan-100 shrink-0" alt="Profile">
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

                            <span class="rounded-full
                        bg-cyan-100
                        text-cyan-700
                        px-3 py-1.5
                        text-xs font-semibold">

                                <?= htmlspecialchars($msg['subject']) ?>

                            </span>

                            <?php if ($msg['is_read'] == 0): ?>

                                <span class="bg-red-100
                        text-red-600
                        px-3 py-1.5
                        rounded-full
                        text-xs font-bold">

                                    Unread

                                </span>

                            <?php else: ?>

                                <span class="bg-green-100
                        text-green-600
                        px-3 py-1.5
                        rounded-full
                        text-xs font-bold">

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

                            <a href="view_contact.php?id=<?= $msg['id'] ?>" class="rounded-xl
                       bg-blue-500
                       px-3 py-2
                       text-sm
                       text-white">

                                View

                            </a>

                            <a href="?delete=<?= $msg['id'] ?>" onclick="return confirm('Delete this message?')" class="rounded-xl
                       bg-red-500
                       px-3 py-2
                       text-sm
                       text-white">

                                Delete

                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-center gap-4"> <?php if ($totalMessages > 0): ?>
                    <div class="mt-8 flex flex-col items-center gap-3"> <!-- Info Row -->
                        <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                            <span> Showing <strong
                                    class="text-slate-700"><?= $offset + 1 ?>–<?= min($offset + $limit, $totalMessages) ?></strong>
                                of <strong class="text-slate-700"><?= $totalMessages ?></strong> </span> </div>
                        <!-- Controls Row -->
                        <div class=""> <!-- Previous --> <?php if ($page > 1): ?> <a href="?page=<?= $page - 1 ?>"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white shadow text-sm font-medium text-slate-600 hover:bg-cyan-50 hover:text-cyan-700 transition">
                                    <i class="fa-solid fa-chevron-left text-xs"></i> Previous </a> <?php else: ?> <span
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white shadow text-sm font-medium text-slate-300 cursor-not-allowed">
                                    <i class="fa-solid fa-chevron-left text-xs"></i> Previous </span> <?php endif; ?>
                            <!-- Page Numbers -->
                            <?php $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2); ?>
                            <?php if ($startPage > 1): ?> <a href="?page=1"
                                    class="px-3 py-2 rounded-xl bg-white shadow text-sm text-slate-600 hover:bg-cyan-50 hover:text-cyan-700 transition">1</a>
                                <?php if ($startPage > 2): ?> <span class="px-1 text-slate-400">…</span> <?php endif; ?>
                            <?php endif; ?>     <?php for ($i = $startPage; $i <= $endPage; $i++): ?> <a href="?page=<?= $i ?>"
                                    class="px-3 py-2 rounded-xl text-sm font-medium transition <?= $page == $i ? 'bg-cyan-500 text-white shadow-md shadow-cyan-200' : 'bg-white shadow text-slate-600 hover:bg-cyan-50 hover:text-cyan-700' ?>">
                                    <?= $i ?> </a> <?php endfor; ?>     <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?> <span class="px-1 text-slate-400">…</span>
                                <?php endif; ?> <a href="?page=<?= $totalPages ?>"
                                    class="px-3 py-2 rounded-xl bg-white shadow text-sm text-slate-600 hover:bg-cyan-50 hover:text-cyan-700 transition"><?= $totalPages ?></a>
                            <?php endif; ?> <!-- Next --> <?php if ($page < $totalPages): ?> <a
                                    href="?page=<?= $page + 1 ?>"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white shadow text-sm font-medium text-slate-600 hover:bg-cyan-50 hover:text-cyan-700 transition">
                                    Next <i class="fa-solid fa-chevron-right text-xs"></i> </a> <?php else: ?> <span
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white shadow text-sm font-medium text-slate-300 cursor-not-allowed">
                                    Next <i class="fa-solid fa-chevron-right text-xs"></i> </span> <?php endif; ?> </div>
                    </div> <?php endif; ?>
            </div>
        </div>

    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>
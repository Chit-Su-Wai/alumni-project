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

    $id = (int)$_GET['delete'];

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
    (int)($_GET['page'] ?? 1)
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

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>Contact Messages</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<div class="min-h-screen flex">

    <?php include "../include/admin_header.php"; ?>

    <div class="flex-1 p-6">

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

        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">

            <?php while ($msg = $messages->fetch_assoc()): ?>

            <div class="bg-white rounded-2xl p-4 shadow">

                <div class="flex items-start gap-3">

                    <img src="../images/default-avatar.svg" class="h-10 w-10 rounded-full object-cover border border-cyan-100 shrink-0">

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

                    <?php if($msg['is_read'] == 0): ?>

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

                    <a href="view_contact.php?id=<?= $msg['id'] ?>"
                       class="rounded-xl
                       bg-blue-500
                       px-3 py-2
                       text-sm
                       text-white">

                        View

                    </a>

                    <a href="?delete=<?= $msg['id'] ?>"
                       onclick="return confirm('Delete this message?')"
                       class="rounded-xl
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

        <div class="flex justify-center gap-2 mt-8">

            <?php for($i=1;$i<=$totalPages;$i++): ?>

            <a href="?page=<?= $i ?>"
               class="px-4 py-2 rounded-xl
               <?= $page == $i
               ? 'bg-cyan-500 text-white'
               : 'bg-white shadow' ?>">

                <?= $i ?>

            </a>

            <?php endfor; ?>

        </div>

    </div>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>

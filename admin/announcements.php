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

/* Delete Announcement */

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $imgStmt = $conn->prepare("SELECT image FROM announcements WHERE id = ?");
    $imgStmt->bind_param("i", $id);
    $imgStmt->execute();
    $row = $imgStmt->get_result()->fetch_assoc();

    if (!empty($row['image']) && file_exists($row['image'])) {
        unlink($row['image']);
    }

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: announcements.php");
    exit;
}

/* Pagination */

$limit = 9;

$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* Total */
$total = $conn->query("SELECT COUNT(*) AS total FROM announcements")->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

/* List */
$stmt = $conn->prepare("
    SELECT *
    FROM announcements
    ORDER BY
        CASE WHEN event_date IS NULL THEN 1 ELSE 0 END,
        event_date DESC,
        created_at DESC
    LIMIT ?, ?
");
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Announcements & Events</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

    <div class="min-h-screen flex">

        <?php include "../include/admin_header.php"; ?>

        <div class="flex-1 p-6 flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-teal-700">
                    Announcements &amp; Events
                </h1>

                <a href="announcement_form.php"
                    class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-bold text-white shadow hover:bg-teal-700 transition">
                    <i class="fa-solid fa-plus"></i>
                    New
                </a>
            </div>

            <!-- Total -->
            <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 rounded-xl shadow-sm p-3 mb-6 w-[300px]">
                <p class="text-sm text-slate-500">Total Items</p>
                <h2 class="text-2xl font-bold text-teal-700 mt-1"><?= $total ?></h2>
            </div>

            <?php if ($total === 0): ?>
                <div class="rounded-2xl bg-white p-10 text-center text-slate-500 shadow">
                    No announcements or events yet.
                </div>
            <?php else: ?>

            <!-- List -->
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 flex-1 content-start">

                <?php while ($item = $items->fetch_assoc()): ?>
                    <div class="bg-white rounded-2xl shadow overflow-hidden flex flex-col">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                                class="h-40 w-full object-cover">
                        <?php else: ?>
                            <div class="h-40 w-full bg-gradient-to-r from-cyan-100 to-teal-100 flex items-center justify-center text-teal-500">
                                <i class="fa-solid fa-bullhorn text-4xl"></i>
                            </div>
                        <?php endif; ?>

                        <div class="p-4 flex-1 flex flex-col">
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="font-semibold text-slate-800 leading-5">
                                    <?= htmlspecialchars($item['title']) ?>
                                </h2>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold
                                    <?= $item['type'] === 'event'
                                        ? 'bg-teal-100 text-teal-700'
                                        : 'bg-cyan-100 text-cyan-700' ?>">
                                    <?= $item['type'] === 'event' ? 'Event' : 'Announcement' ?>
                                </span>
                            </div>

                            <div class="mt-3 space-y-1 text-xs text-slate-500">
                                <?php if (!empty($item['event_date'])): ?>
                                    <p><i class="fa-regular fa-calendar mr-1"></i>
                                        <?= date("M d, Y", strtotime($item['event_date'])) ?>
                                        <?php if (!empty($item['event_time'])): ?>
                                            at <?= date("h:i A", strtotime($item['event_time'])) ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($item['location'])): ?>
                                    <p><i class="fa-solid fa-location-dot mr-1"></i>
                                        <?= htmlspecialchars($item['location']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($item['description'])): ?>
                                <p class="mt-3 text-sm text-slate-600 line-clamp-3">
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($item['description'], 0, 160, "..."))) ?>
                                </p>
                            <?php endif; ?>

                            <div class="mt-4 flex flex-wrap gap-2 pt-2">
                                <a href="announcement_form.php?id=<?= $item['id'] ?>"
                                    class="rounded-xl bg-blue-500 hover:bg-blue-600 px-3 py-2 text-sm text-white transition">
                                    <i class="fa-solid fa-pen mr-1"></i> Edit
                                </a>
                                <a href="?delete=<?= $item['id'] ?>" onclick="return confirm('Delete this item?')"
                                    class="rounded-xl bg-red-500 hover:bg-red-600 px-3 py-2 text-sm text-white transition">
                                    <i class="fa-solid fa-trash mr-1"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-8 flex flex-col items-center gap-3 pb-4">
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white shadow text-sm font-medium text-slate-600 hover:bg-cyan-50 hover:text-cyan-700 transition">
                            <i class="fa-solid fa-chevron-left text-xs"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white shadow text-sm font-medium text-slate-300 cursor-not-allowed">
                            <i class="fa-solid fa-chevron-left text-xs"></i> Previous
                        </span>
                    <?php endif; ?>

                    <?php
                        $startPage = max(1, $page - 2);
                        $endPage   = min($totalPages, $page + 2);
                    ?>
                    <?php if ($startPage > 1): ?>
                        <a href="?page=1" class="px-3 py-2 rounded-xl bg-white shadow text-sm text-slate-600 hover:bg-cyan-50 hover:text-cyan-700 transition">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?>"
                           class="px-3 py-2 rounded-xl text-sm font-medium transition
                           <?= $page == $i
                               ? 'bg-cyan-500 text-white shadow-md shadow-cyan-200'
                               : 'bg-white shadow text-slate-600 hover:bg-cyan-50 hover:text-cyan-700' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                        <a href="?page=<?= $totalPages ?>" class="px-3 py-2 rounded-xl bg-white shadow text-sm text-slate-600 hover:bg-cyan-50 hover:text-cyan-700 transition"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white shadow text-sm font-medium text-slate-600 hover:bg-cyan-50 hover:text-cyan-700 transition">
                            Next <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white shadow text-sm font-medium text-slate-300 cursor-not-allowed">
                            Next <i class="fa-solid fa-chevron-right text-xs"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>

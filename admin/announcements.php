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

    $imgStmt = $conn->prepare("SELECT image, title FROM announcements WHERE id = ?");
    $imgStmt->bind_param("i", $id);
    $imgStmt->execute();
    $row = $imgStmt->get_result()->fetch_assoc();

    if (!empty($row['image']) && file_exists($row['image'])) {
        unlink($row['image']);
    }

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    admin_log_activity(
        $conn,
        'Delete Announcement',
        'Deleted announcement #' . $id . (!empty($row['title']) ? ' - ' . $row['title'] : ''),
        'announcement',
        $id
    );

    $returnPage = max(1, (int) ($_GET['page'] ?? 1));
    header("Location: announcements.php?page=" . $returnPage);
    exit;
}

/* Pagination */

$limit = 6;

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

        <div class="flex-1 p-4 flex flex-col">

            <!-- Header -->
            <div class="admin-page-head">
                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    <div>
                        <h1 class="admin-page-title">Announcements &amp; Events</h1>
                        <p class="admin-page-sub">Create and manage announcements and events</p>
                    </div>
                </div>

                <a href="announcement_form.php"
                    class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    New
                </a>
            </div>

            <!-- Total -->
            <div class="admin-stat w-[220px] mb-4">
                <span class="stat-spark"></span>
                <div class="stat-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total Items</div>
            </div>

            <?php if ($total === 0): ?>
            <?php
            $es_icon    = 'fa-solid fa-bullhorn';
            $es_title   = 'No announcements yet';
            $es_message = 'Create your first announcement or event to show here.';
            $es_action  = '<a href="announcement_form.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Announcement</a>';
            include '../include/empty_state.php';
            ?>
            <?php else: ?>

            <!-- List -->
            <div class="admin-stagger grid md:grid-cols-2 xl:grid-cols-3 gap-3 flex-1 content-start">

                <?php while ($item = $items->fetch_assoc()): ?>
                    <div class="admin-card flex flex-col">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                                class="h-28 w-full object-cover cursor-zoom-in"
                                onclick="openLightbox(this.src, '<?= htmlspecialchars($item['title']) ?>')">
                        <?php else: ?>
                            <div class="h-28 w-full bg-gradient-to-r from-cyan-100 to-teal-100 flex items-center justify-center text-teal-500">
                                <i class="fa-solid fa-bullhorn text-4xl"></i>
                            </div>
                        <?php endif; ?>

                        <div class="p-4 flex-1 flex flex-col">
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="font-semibold text-slate-800 leading-5">
                                    <?= htmlspecialchars($item['title']) ?>
                                </h2>
                                <span class="badge shrink-0
                                    <?= $item['type'] === 'event'
                                        ? 'badge-green'
                                        : 'badge-cyan' ?>">
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
                                    class="btn btn-ghost btn-sm">
                                    <i class="fa-solid fa-pen mr-1"></i> Edit
                                </a>
                                <a href="?delete=<?= $item['id'] ?>&page=<?= $page ?>" onclick="event.preventDefault(); confirmDialog('Delete this item?', function(){ window.location.href='?delete=<?= $item['id'] ?>&page=<?= $page ?>'; }, {title:'Delete Item', confirmText:'Delete'})"
                                    class="btn btn-danger btn-sm">
                                    <i class="fa-solid fa-trash mr-1"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

            </div>

            <!-- Pagination -->
            <?php if ($total > 0): ?>
            <div class="mt-4 flex flex-col items-center gap-2 pb-4">

                <!-- Info Row -->
                <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span>
                        Showing
                        <strong class="text-slate-700"><?= $offset + 1 ?>&ndash;<?= min($offset + $limit, $total) ?></strong>
                        of
                        <strong class="text-slate-700"><?= $total ?></strong>
                        announcements
                    </span>
                </div>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>">
                            <i class="fa-solid fa-chevron-left text-xs"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="disabled"><i class="fa-solid fa-chevron-left text-xs"></i> Previous</span>
                    <?php endif; ?>

                    <?php
                        $startPage = max(1, $page - 2);
                        $endPage   = min($totalPages, $page + 2);
                    ?>
                    <?php if ($startPage > 1): ?>
                        <a href="?page=1">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?>"
                            class="<?= $page == $i ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                        <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>">
                            Next <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled">Next <i class="fa-solid fa-chevron-right text-xs"></i></span>
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

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

/* Delete Alumni */

if (isset($_GET['delete'])) {

    $delete_id = (int) $_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM users
        WHERE id = ?
        AND role = 'user'
    ");

    $stmt->bind_param("i", $delete_id);
    $stmt->execute();

    $msgStmt = $conn->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
    $msgStmt->bind_param("ii", $delete_id, $delete_id);
    $msgStmt->execute();

    admin_log_activity(
        $conn,
        'Delete Alumni',
        'Deleted alumni account #' . $delete_id,
        'user',
        $delete_id
    );

    header("Location: users.php");
    exit;
}

/* Pagination */

$limit = 8;

$page = max(
    1,
    (int) ($_GET['page'] ?? 1)
);

$offset = ($page - 1) * $limit;

/* Search */

$search = trim($_GET['search'] ?? '');

/* Sort */

$sort = trim($_GET['sort'] ?? '');
$dir = (trim($_GET['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';
$orderBy = $sort === 'views' ? "views {$dir}, u.id DESC" : "u.id DESC";

$sortQs = $sort !== '' ? "&sort=" . urlencode($sort) . "&dir=" . ($dir === 'DESC' ? 'desc' : 'asc') : "";

/* Total Alumni */

if ($search != '') {

    $countStmt = $conn->prepare("
        SELECT COUNT(*) total
        FROM users
        WHERE role = 'user'
        AND (
            name LIKE ?
            OR email LIKE ?
            OR approved_id LIKE ?
        )
    ");

    $keyword = "%{$search}%";

    $countStmt->bind_param(
        "sss",
        $keyword,
        $keyword,
        $keyword
    );

    $countStmt->execute();

    $totalAlumni = $countStmt->get_result()->fetch_assoc()['total'];

} else {

    $totalAlumni = $conn->query("
        SELECT COUNT(*) total
        FROM users
        WHERE role='user'
    ")->fetch_assoc()['total'];
}

$totalPages = ceil($totalAlumni / $limit);

/* Alumni Query */

if ($search != '') {

    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.approved_id,
            u.name,
            u.email,
            u.profile_image,
            u.role,
            COUNT(pv.id) AS views
        FROM users u
        LEFT JOIN profile_views pv
            ON pv.profile_id = u.id
        WHERE u.role = 'user'
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR u.approved_id LIKE ?
        )
        GROUP BY u.id
        ORDER BY {$orderBy}
        LIMIT ?, ?
    ");

    $keyword = "%{$search}%";

    $stmt->bind_param(
        "sssii",
        $keyword,
        $keyword,
        $keyword,
        $offset,
        $limit
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.approved_id,
            u.name,
            u.email,
            u.profile_image,
            u.role,
            COUNT(pv.id) AS views
        FROM users u
        LEFT JOIN profile_views pv
            ON pv.profile_id = u.id
        WHERE u.role = 'user'
        GROUP BY u.id
        ORDER BY {$orderBy}
        LIMIT ?, ?
    ");

    $stmt->bind_param(
        "ii",
        $offset,
        $limit
    );
}

$stmt->execute();

$users = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Manage Alumni</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        @media print {
            body { background: white !important; }
            .no-print, aside, header, footer, nav, .print-hide { display: none !important; }
            .min-h-screen { min-height: auto !important; }
            .flex-1 { padding: 20px !important; overflow: visible !important; }
            .print-report { display: block !important; }
            .print-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
            .print-table { width: 100% !important; }
            .print-table th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .print-report { display: none; }
    </style>

</head>

<body class="bg-slate-50">

    <div class="min-h-screen flex">
        <?php include "../include/admin_header.php"; ?>

        <div class="flex-1 flex flex-col min-h-screen p-4">

            <!-- Header -->
            <div class="admin-page-head print-hide">
                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <h1 class="admin-page-title">Alumni Management</h1>
                        <p class="admin-page-sub">View, search and manage all alumni accounts</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <form method="GET" class="max-w-[420px] w-full">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search Alumni..." class="input-base">
                        </div>
                    </form>
                    <a href="export_alumni.php?search=<?= urlencode($search) ?>"
                        class="btn btn-success">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </a>
                    <button onclick="window.print()" type="button"
                        class="btn btn-secondary">
                        <i class="fa-solid fa-print"></i> Print Report
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="print-card admin-card flex-1">

                <div class="admin-card-head">
                    <div class="admin-card-title"><i class="fa-solid fa-users fa-icon-chip"></i> <span>Alumni Directory</span></div>
                </div>

                <div class="overflow-x-auto">

                    <table class="admin-table data-table print-table min-w-[1100px] table-auto text-sm whitespace-nowrap">

                                <thead>

                            <tr>

                                <th class="w-16 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                    ID
                                </th>

                                <th class="w-1/4 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                    Approved ID
                                </th>

                                <th class="w-1/4 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                    Name
                                </th>

                                <th class="w-1/4 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                    Email
                                </th>

                                <th class="w-28 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                    <a href="?sort=views&dir=<?= ($sort === 'views' && $dir === 'DESC') ? 'asc' : 'desc' ?>&search=<?= urlencode($search) ?>"
                                        class="inline-flex items-center gap-1 hover:text-teal-700 <?= $sort === 'views' ? 'text-teal-700' : '' ?>">
                                        Views
                                        <?php if ($sort === 'views'): ?>
                                            <i class="fa-solid fa-arrow-<?= $dir === 'DESC' ? 'down' : 'up' ?>"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-sort text-slate-400"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>

                                <th class="print-hide w-36 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php $users->data_seek(0); ?>
                            <?php while ($user = $users->fetch_assoc()): ?>

                                <tr class="border-t">

                                    <td class="px-3 py-2.5 align-middle">
                                        <?= $user['id'] ?>
                                    </td>

                                    <td class="px-3 py-2.5 align-middle truncate">
                                        <?= htmlspecialchars($user['approved_id']) ?>
                                    </td>

                                    <td class="px-3 py-2.5 align-middle">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../images/default-avatar.svg' ?>"
                                                class="admin-avatar print-hide shrink-0 cursor-zoom-in"
                                                onclick="openLightbox(this.src, '<?= htmlspecialchars($user['name']) ?>')">
                                            <span class="truncate font-medium"><?= htmlspecialchars($user['name']) ?></span>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2.5 align-middle truncate">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </td>

                                    <td class="px-3 py-2.5 align-middle font-semibold text-slate-700">
                                        <i class="fa-solid fa-eye mr-1 text-slate-400"></i>
                                        <?= (int) ($user['views'] ?? 0) ?>
                                    </td>

                                    <td class="print-hide px-3 py-2.5 align-middle">

                                        <div class="flex gap-2 flex-wrap">

                                            <a href="view_user.php?id=<?= $user['id'] ?>"
                                                class="btn btn-ghost btn-sm">

                                                View

                                            </a>

                                            <a href="?delete=<?= $user['id'] ?>"
                                                onclick="event.preventDefault(); confirmDialog('Delete this alumni?', function(){ window.location.href='?delete=<?= $user['id'] ?>'; }, {title:'Delete Alumni', confirmText:'Delete'})"
                                                class="btn btn-danger btn-sm">

                                                Delete

                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            </div>

<!-- Pagination -->
            <?php if ($totalAlumni > 0): ?>
            <div class="print-hide mt-4 flex flex-col items-center gap-2 pb-4">
                <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span>
                        Showing
                        <strong class="text-slate-700"><?= $offset + 1 ?>&ndash;<?= min($offset + $limit, $totalAlumni) ?></strong>
                        of
                        <strong class="text-slate-700"><?= $totalAlumni ?></strong>
                    </span>
                </div>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $sortQs ?>&search=<?= urlencode($search) ?>">
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
                        <a href="?page=1<?= $sortQs ?>&search=<?= urlencode($search) ?>">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?><?= $sortQs ?>&search=<?= urlencode($search) ?>"
                            class="<?= $page == $i ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                        <a href="?page=<?= $totalPages ?><?= $sortQs ?>&search=<?= urlencode($search) ?>"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $sortQs ?>&search=<?= urlencode($search) ?>">
                            Next <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled">Next <i class="fa-solid fa-chevron-right text-xs"></i></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <?php
            $es_icon    = 'fa-solid fa-users';
            $es_title   = 'No alumni found';
            $es_message = 'There are no alumni records to display yet.';
            $es_action  = '';
            include '../include/empty_state.php';
            ?>
            <?php endif; ?>

        </div>

    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>

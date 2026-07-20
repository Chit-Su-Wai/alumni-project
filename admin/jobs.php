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

/* Delete Job */

if (isset($_GET['delete'])) {

    $job_id = (int) $_GET['delete'];

    $metaStmt = $conn->prepare("SELECT position, company FROM jobs WHERE id = ? LIMIT 1");
    $metaStmt->bind_param("i", $job_id);
    $metaStmt->execute();
    $metaRow = $metaStmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("
        DELETE FROM jobs
        WHERE id = ?
    ");

    $stmt->bind_param("i", $job_id);
    $stmt->execute();

    admin_log_activity(
        $conn,
        'Delete Job',
        'Deleted job #' . $job_id . (!empty($metaRow['position']) ? ' - ' . $metaRow['position'] : '') . (!empty($metaRow['company']) ? ' at ' . $metaRow['company'] : ''),
        'job',
        $job_id
    );

    header("Location: jobs.php");
    exit;
}

/* Pagination */

$limit = 6; // 2 Rows × 3 Columns

$page = max(
    1,
    (int) ($_GET['page'] ?? 1)
);

$offset = ($page - 1) * $limit;

/* Total Jobs */

$totalJobs = $conn->query("
    SELECT COUNT(*) AS total
    FROM jobs
")->fetch_assoc()['total'];

$totalPages = ceil($totalJobs / $limit);

/* Jobs */

$stmt = $conn->prepare("
    SELECT
        j.*,
        u.name,
        u.profile_image
    FROM jobs j
    INNER JOIN users u
        ON j.user_id = u.id
    ORDER BY j.created_at DESC
    LIMIT ?, ?
");

$stmt->bind_param(
    "ii",
    $offset,
    $limit
);

$stmt->execute();

$jobs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Jobs Management</title>

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
                    <div class="admin-title-icon"><i class="fa-solid fa-briefcase"></i></div>
                    <div>
                        <h1 class="admin-page-title">Jobs Management</h1>
                        <p class="admin-page-sub">Browse and manage job listings</p>
                    </div>
                </div>

            </div>

            <!-- Total Jobs -->

            <div class="admin-stat w-[220px] mb-4">
                <span class="stat-spark"></span>
                <div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div>
                <div class="stat-value"><?= $totalJobs ?></div>
                <div class="stat-label">Total Jobs</div>
            </div>

            <!-- Jobs List -->

            <?php if ($totalJobs > 0): ?>
            <div class="admin-stagger grid md:grid-cols-2 xl:grid-cols-3 gap-3 flex-1 content-start">

                <?php while ($job = $jobs->fetch_assoc()): ?>

                    <div class="admin-card p-4">
                        <div class="flex items-start gap-3">

                            <img src="<?= !empty($job['profile_image']) ? htmlspecialchars($job['profile_image']) : '../images/default-avatar.svg' ?>"
                                class="admin-avatar shrink-0 cursor-zoom-in"
                                onclick="openLightbox(this.src, '<?= htmlspecialchars($job['name']) ?>')">

                            <div class="min-w-0 flex-1">

                                <h2 class="font-semibold text-slate-800 truncate">
                                    <?= htmlspecialchars($job['name']) ?>
                                </h2>

                                <p class="text-xs text-slate-500 mt-0.5 whitespace-nowrap">
                                    <?= date("M d, Y h:i A", strtotime($job['created_at'])) ?>
                                </p>

                            </div>

                            <span class="badge badge-cyan shrink-0">
                                <?= htmlspecialchars($job['job_type']) ?>
                            </span>

                        </div>

                        <div class="mt-4 space-y-3 text-sm">

                            <div>

                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Position
                                </p>

                                <p class="mt-1 font-semibold text-slate-800 leading-5">
                                    <?= htmlspecialchars($job['position']) ?>
                                </p>

                            </div>

                            <div>

                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Company
                                </p>

                                <p class="mt-1 text-slate-700 truncate">
                                    <?= htmlspecialchars($job['company']) ?>
                                </p>

                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>

                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Location
                                    </p>

                                    <p class="mt-1 text-slate-700 truncate">
                                        <?= htmlspecialchars($job['location']) ?>
                                    </p>

                                </div>

                                <div>

                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Salary
                                    </p>

                                    <p class="mt-1 text-slate-700 truncate">

                                        <?php if (!empty($job['salary'])): ?>

                                            <?= number_format((float) $job['salary']) ?> MMK

                                        <?php else: ?>

                                            Negotiable

                                        <?php endif; ?>

                                    </p>

                                </div>

                            </div>

                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">

                            <a href="view_job.php?id=<?= $job['id'] ?>"
                                class="btn btn-ghost btn-sm">

                                <i class="fa-solid fa-eye mr-1"></i>
                                View

                            </a>

                            <a href="?delete=<?= $job['id'] ?>" onclick="event.preventDefault(); confirmDialog('Delete this job?', function(){ window.location.href='?delete=<?= $job['id'] ?>'; }, {title:'Delete Job', confirmText:'Delete'})"
                                class="btn btn-danger btn-sm">

                                <i class="fa-solid fa-trash mr-1"></i>
                                Delete

                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>
            <?php else: ?>
            <?php
            $es_icon    = 'fa-solid fa-briefcase';
            $es_title   = 'No jobs found';
            $es_message = 'There are no job listings to display yet.';
            $es_action  = '';
            include '../include/empty_state.php';
            ?>
            <?php endif; ?>
            <!-- Pagination -->
            <?php if ($totalJobs > 0): ?>
            <div class="mt-4 flex flex-col items-center gap-2 pb-4">

                <!-- Info Row -->
                <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span>
                        Showing
                        <strong class="text-slate-700"><?= $offset + 1 ?>&ndash;<?= min($offset + $limit, $totalJobs) ?></strong>
                        of
                        <strong class="text-slate-700"><?= $totalJobs ?></strong>
                    </span>

                </div>

                <!-- Controls Row -->
                <div class="pagination">

                    <!-- Previous -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>">
                            <i class="fa-solid fa-chevron-left text-xs"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="disabled"><i class="fa-solid fa-chevron-left text-xs"></i> Previous</span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
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

                    <!-- Next -->
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
        </div>

    </div>
    

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>

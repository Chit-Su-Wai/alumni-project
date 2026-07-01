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

    $stmt = $conn->prepare("
        DELETE FROM jobs
        WHERE id = ?
    ");

    $stmt->bind_param("i", $job_id);
    $stmt->execute();

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

        <div class="flex-1 p-6 flex flex-col">

            <!-- Header -->

            <div class="flex items-center justify-between mb-6">

                <h1 class="text-3xl font-bold text-teal-700">
                    Jobs Management
                </h1>

            </div>

            <!-- Total Jobs -->

            <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 rounded-xl shadow-sm p-3 mb-6 w-[300px]">

                <p class="text-sm text-slate-500">
                    Total Jobs
                </p>

                <h2 class="text-2xl font-bold text-teal-700 mt-1">
                    <?= $totalJobs ?>
                </h2>

            </div>

            <!-- Jobs List -->

            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 flex-1 content-start">

                <?php while ($job = $jobs->fetch_assoc()): ?>

                    <div class="bg-white rounded-2xl p-4 shadow">
                        <div class="flex items-start gap-3">

                            <img src="<?= !empty($job['profile_image']) ? htmlspecialchars($job['profile_image']) : '../images/default-avatar.svg' ?>"
                                class="h-10 w-10 rounded-full object-cover border border-cyan-100 shrink-0">

                            <div class="min-w-0 flex-1">

                                <h2 class="font-semibold text-slate-800 truncate">
                                    <?= htmlspecialchars($job['name']) ?>
                                </h2>

                                <p class="text-xs text-slate-500 mt-0.5 whitespace-nowrap">
                                    <?= date("M d, Y h:i A", strtotime($job['created_at'])) ?>
                                </p>

                            </div>

                            <span class="rounded-full bg-cyan-100 text-cyan-700 px-3 py-1.5 text-xs font-semibold shrink-0">
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
                                class="rounded-xl bg-blue-500 hover:bg-blue-600 px-3 py-2 text-sm text-white transition">

                                <i class="fa-solid fa-eye mr-1"></i>
                                View

                            </a>

                            <a href="?delete=<?= $job['id'] ?>" onclick="return confirm('Delete this job?')"
                                class="rounded-xl bg-red-500 hover:bg-red-600 px-3 py-2 text-sm text-white transition">

                                <i class="fa-solid fa-trash mr-1"></i>
                                Delete

                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>
            <!-- Pagination -->
            <div class="mt-auto pt-7 pb-4">
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

            <!-- <div class="flex justify-center items-center gap-2 mt-8 mb-6">

                <?php if ($totalPages > 1): ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                        <a href="?page=<?= $i ?>" class="px-4 py-2 rounded-xl transition
               <?= $page == $i
                   ? 'bg-cyan-500 text-white'
                   : 'bg-white text-slate-700 shadow hover:bg-cyan-50' ?>">

                            <?= $i ?>

                        </a>

                    <?php endfor; ?>

                <?php endif; ?>

            </div> -->
        </div>

    </div>
    

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>
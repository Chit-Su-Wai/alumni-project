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

$limit = 10;

$page = max(
    1,
    (int) ($_GET['page'] ?? 1)
);

$offset = ($page - 1) * $limit;

/* Total Jobs */

$totalJobs = $conn->query("
    SELECT COUNT(*) total
    FROM jobs
")->fetch_assoc()['total'];

$totalPages = ceil(
    $totalJobs / $limit
);

/* Jobs */

$stmt = $conn->prepare("
SELECT
    j.*,
    u.name
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
<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Jobs Management</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">
<div class="min-h-screen flex">
    <?php include "../include/admin_header.php"; ?>
    <div class="flex-1 p-6">

        <div class="flex items-center justify-between mb-6">

            <h1 class="text-3xl font-bold text-teal-700">
                Jobs Management
            </h1>

            
        </div>

        <!-- Total Jobs -->

        <div class="bg-white rounded-3xl p-6 shadow mb-6">

            <p class="text-slate-500">
                Total Jobs
            </p>

            <h2 class="text-4xl font-black text-teal-700 mt-2">

                <?= $totalJobs ?>

            </h2>

        </div>

        <!-- Jobs List -->

        <div class="space-y-5">

            <?php while ($job = $jobs->fetch_assoc()): ?>

                <div class="bg-white rounded-3xl p-6 shadow">

                    <div class="flex flex-col lg:flex-row
                        lg:items-center
                        lg:justify-between
                        gap-4">

                        <div>

                            <h2 class="text-xl font-bold">

                                <?= htmlspecialchars($job['position']) ?>

                            </h2>

                            <p class="text-slate-500 mt-1">

                                <?= htmlspecialchars($job['company']) ?>

                            </p>

                        </div>

                        <span class="rounded-full
                             bg-cyan-100
                             text-cyan-700
                             px-4 py-2
                             text-sm font-semibold">

                            <?= htmlspecialchars($job['job_type']) ?>

                        </span>

                    </div>

                    <div class="grid md:grid-cols-3 gap-4 mt-5">

                        <div>

                            <p class="text-slate-500 text-sm">
                                Location
                            </p>

                            <p class="font-semibold">
                                <?= htmlspecialchars($job['location']) ?>
                            </p>

                        </div>

                        <div>

                            <p class="text-slate-500 text-sm">
                                Salary
                            </p>

                            <p class="font-semibold">

                                <?php if (!empty($job['salary'])): ?>

                                    <?= number_format((float) $job['salary']) ?> MMK

                                <?php else: ?>

                                    Negotiable

                                <?php endif; ?>

                            </p>

                        </div>

                        <div>

                            <p class="text-slate-500 text-sm">
                                Posted By
                            </p>

                            <p class="font-semibold">

                                <?= htmlspecialchars($job['name']) ?>

                            </p>

                        </div>

                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">

                        <a href="view_job.php?id=<?= $job['id'] ?>" class="rounded-xl bg-blue-500
                          px-4 py-2 text-white">

                            View

                        </a>

                        <a href="?delete=<?= $job['id'] ?>" onclick="return confirm('Delete this job?')" class="rounded-xl bg-red-500
                          px-4 py-2 text-white">

                            Delete

                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

        <!-- Pagination -->

        <div class="flex justify-center gap-2 mt-8">

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                <a href="?page=<?= $i ?>" class="px-4 py-2 rounded-xl
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
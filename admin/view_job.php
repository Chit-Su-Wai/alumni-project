
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

$job_id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
SELECT
    j.*,
    u.name,
    u.email,
    u.profile_image
FROM jobs j
INNER JOIN users u
ON j.user_id = u.id
WHERE j.id = ?
");

$stmt->bind_param("i", $job_id);
$stmt->execute();

$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    die("Job not found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>View Job</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<div class="flex min-h-screen">

    <?php include "../include/admin_header.php"; ?>

    <div class="flex-1 p-6">

        <div class="max-w-4xl">

            <div class="admin-page-head">
                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-briefcase"></i></div>
                    <div>
                        <h1 class="admin-page-title">Job Details</h1>
                        <p class="admin-page-sub">Full job posting information</p>
                    </div>
                </div>

                <a href="jobs.php"
                   class="btn btn-ghost">

                    <i class="fa-solid fa-arrow-left mr-1"></i> Back

                </a>

            </div>

            <div class="admin-card">

                <div class="p-6 border-b border-slate-100">

                    <div class="flex items-center gap-4">

                        <img
                        src="<?= !empty($job['profile_image'])
                            ? htmlspecialchars($job['profile_image'])
                            : '../images/default-avatar.svg' ?>"
                        class="admin-avatar w-12 h-12 cursor-zoom-in"
                        onclick="openLightbox(this.src, '<?= htmlspecialchars($job['name']) ?>')">

                        <div>

                            <h2 class="font-bold text-slate-800">

                                <?= htmlspecialchars($job['name']) ?>

                            </h2>

                            <p class="text-xs text-slate-400">

                                <?= htmlspecialchars($job['email']) ?>

                            </p>

                        </div>

                    </div>

                </div>

                <div class="p-6">

                    <div class="grid md:grid-cols-2 gap-6">

                        <div>

                            <p class="text-xs text-slate-400">
                                Company
                            </p>

                            <h3 class="font-bold text-slate-800">
                                <?= htmlspecialchars($job['company']) ?>
                            </h3>

                        </div>

                        <div>

                            <p class="text-xs text-slate-400">
                                Position
                            </p>

                            <h3 class="font-bold text-slate-800">
                                <?= htmlspecialchars($job['position']) ?>
                            </h3>

                        </div>

                        <div>

                            <p class="text-xs text-slate-400">
                                Job Type
                            </p>

                            <span class="badge badge-cyan">
                                <?= htmlspecialchars($job['job_type']) ?>
                            </span>

                        </div>

                        <div>

                            <p class="text-xs text-slate-400">
                                Location
                            </p>

                            <h3 class="font-semibold text-sm text-slate-700">

                                <i class="fa-solid fa-location-dot mr-1 text-teal-600"></i>
                                <?= htmlspecialchars($job['location']) ?>

                            </h3>

                        </div>

                        <div>

                            <p class="text-xs text-slate-400">
                                Salary
                            </p>

                            <h3 class="font-semibold text-sm text-slate-700">

                                <?= !empty($job['salary'])
                                ? number_format((float)$job['salary']) . ' MMK'
                                : 'Negotiable' ?>

                            </h3>

                        </div>

                        <div>

                            <p class="text-xs text-slate-400">
                                Experience
                            </p>

                            <h3 class="font-semibold text-sm text-slate-700">

                                <?= $job['experience_year'] ?>

                                Year(s)

                            </h3>

                        </div>

                        <div>

                            <p class="text-xs text-slate-400">
                                Phone
                            </p>

                            <h3 class="font-semibold text-sm text-slate-700">

                                <?= !empty($job['phone'])
                                ? htmlspecialchars($job['phone'])
                                : '-' ?>

                            </h3>

                        </div>

                        <div>

                            <p class="text-xs text-slate-400">
                                Website
                            </p>

                            <h3 class="font-semibold text-sm text-slate-700">

                                <?= !empty($job['website'])
                                ? htmlspecialchars($job['website'])
                                : '-' ?>

                            </h3>

                        </div>

                    </div>

                    <div class="mt-8">

                        <p class="text-xs text-slate-400 mb-2">

                            Description

                        </p>

                        <div class="bg-slate-50 rounded-2xl p-4 text-sm text-slate-700 leading-7">

                            <?= !empty($job['description'])
                            ? nl2br(htmlspecialchars($job['description']))
                            : 'No description available.' ?>

                        </div>

                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-400">

                        <i class="fa-regular fa-clock mr-1"></i>
                        Posted Date: <?= $job['created_at'] ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>

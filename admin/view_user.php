
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

$user_id = (int)($_GET['id'] ?? 0);

if ($user_id <= 0) {
    header("Location: users.php");
    exit;
}

/* User Info */

$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE id = ?
    AND role = 'user'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: users.php");
    exit;
}

/* User Jobs */

$jobsStmt = $conn->prepare("
    SELECT *
    FROM jobs
    WHERE user_id = ?
    ORDER BY id DESC
");

$jobsStmt->bind_param("i", $user_id);
$jobsStmt->execute();

$jobs = $jobsStmt->get_result();

/* User Posts */

$postStmt = $conn->prepare("
    SELECT *
    FROM posts
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$postStmt->bind_param("i", $user_id);
$postStmt->execute();

$posts = $postStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>View Alumni</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<div class="flex min-h-screen">

    <?php include "../include/admin_header.php"; ?>

    <div class="flex-1 p-6">

        <div class="max-w-4xl">

            <div class="admin-page-head">
                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-user-graduate"></i></div>
                    <div>
                        <h1 class="admin-page-title">Alumni Profile</h1>
                        <p class="admin-page-sub">Full alumni details, experience &amp; posts</p>
                    </div>
                </div>

                <a href="users.php"
                   class="btn btn-ghost">

                    <i class="fa-solid fa-arrow-left mr-1"></i> Back

                </a>

            </div>

            <!-- Profile -->

            <div class="admin-card p-6">

                <div class="flex flex-col md:flex-row gap-6">

                        <img
                            src="<?= !empty($user['profile_image'])
                                ? htmlspecialchars($user['profile_image'])
                                : '../images/default-avatar.svg' ?>"
                            class="w-28 h-28 rounded-full object-cover border-4 border-cyan-100 cursor-zoom-in"
                            onclick="openLightbox(this.src, '<?= htmlspecialchars($user['name']) ?>')">

                    <div>

                        <h1 class="text-2xl font-bold text-slate-800">
                            <?= htmlspecialchars($user['name']) ?>
                        </h1>

                        <p class="text-slate-500 mt-1 text-sm">
                            <?= htmlspecialchars($user['email']) ?>
                        </p>

                        <p class="mt-2 text-sm">
                            <span class="text-slate-400">Approved ID:</span>
                            <span class="font-semibold"><?= htmlspecialchars($user['approved_id']) ?></span>
                        </p>

                        <?php if(!empty($user['address'])): ?>
                        <p class="mt-1 text-sm">
                            <span class="text-slate-400">Address:</span>
                            <span class="font-semibold"><?= htmlspecialchars($user['address']) ?></span>
                        </p>
                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <!-- Experience -->

            <div class="admin-card mt-6">

                <div class="admin-card-head">
                    <div class="admin-card-title">
                        <i class="fa-solid fa-briefcase fa-icon-chip"></i>Experience
                    </div>
                </div>

                <div class="p-6">

                <?php if ($jobs->num_rows > 0): ?>
                    <?php while($job = $jobs->fetch_assoc()): ?>

                        <div class="border-b border-slate-100 py-3 last:border-0">

                            <h3 class="font-bold text-slate-700">
                                <?= htmlspecialchars($job['position']) ?>
                            </h3>

                            <p class="text-sm text-teal-600">
                                <?= htmlspecialchars($job['company']) ?>
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                <i class="fa-solid fa-location-dot mr-1"></i>
                                <?= htmlspecialchars($job['location']) ?>
                            </p>

                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                        <?php
                        $es_icon    = 'fa-solid fa-briefcase';
                        $es_title   = 'No experience yet';
                        $es_message = 'This alumni has not added any work experience.';
                        $es_action  = '';
                        include '../include/empty_state.php';
                        ?>
                <?php endif; ?>

                </div>

            </div>

            <!-- Posts -->

            <div class="admin-card mt-6">

                <div class="admin-card-head">
                    <div class="admin-card-title">
                        <i class="fa-regular fa-newspaper fa-icon-chip"></i>Posts
                    </div>
                </div>

                <div class="p-6">

                <?php if ($posts->num_rows > 0): ?>
                    <?php while($post = $posts->fetch_assoc()): ?>

                        <div class="border border-slate-100 rounded-2xl p-4 mb-3 last:mb-0">

                            <p class="text-sm text-slate-700">
                                <?= nl2br(htmlspecialchars($post['content'])) ?>
                            </p>

                            <p class="text-xs text-slate-400 mt-2">
                                <i class="fa-regular fa-clock mr-1"></i>
                                <?= $post['created_at'] ?>
                            </p>

                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                        <?php
                        $es_icon    = 'fa-regular fa-newspaper';
                        $es_title   = 'No posts yet';
                        $es_message = 'This alumni has not published any posts.';
                        $es_action  = '';
                        include '../include/empty_state.php';
                        ?>
                <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>

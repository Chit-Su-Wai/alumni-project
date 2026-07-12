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



/* Statistics */

// $totalUsers = $conn->query("
//     SELECT COUNT(*) total
//     FROM users
// ")->fetch_assoc()['total'];

$totalUsers = $conn->query("
    SELECT COUNT(*) total
    FROM users
    WHERE role = 'user'
")->fetch_assoc()['total'];

$totalPosts = $conn->query("
    SELECT COUNT(*) total
    FROM posts
")->fetch_assoc()['total'];

$totalJobs = $conn->query("
    SELECT COUNT(*) total
    FROM jobs
")->fetch_assoc()['total'];

$totalMessages = $conn->query("
    SELECT COUNT(*) total
    FROM contact_messages
")->fetch_assoc()['total'];

$latestUsers = $conn->query("
    SELECT id,name,email,profile_image,created_at
    FROM users
    WHERE role='user'
    ORDER BY id DESC
    LIMIT 5
");

$latestPosts = $conn->query("
    SELECT
        p.id,
        p.content,
        p.created_at,
        u.name,
        u.profile_image
    FROM posts p
    INNER JOIN users u
    ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
");

$latestJobs = $conn->query("
    SELECT
        j.id,
        j.position,
        j.company,
        j.created_at,
        u.name,
        u.profile_image
    FROM jobs j
    INNER JOIN users u
    ON j.user_id = u.id
    ORDER BY j.created_at DESC
    LIMIT 5
");

$latestMessages = $conn->query("
    SELECT
        id,
        name,
        email,
        profile_image,
        subject,
        message,
        created_at
    FROM contact_messages
    ORDER BY created_at DESC
    LIMIT 5
");
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Admin Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

    <div class="flex min-h-screen">

        <?php include "../include/admin_header.php"; ?>



        <!-- Main -->

        <main class="flex-1 p-6">

            <h1 class="text-3xl font-bold mb-6 text-teal-700" data-t="dashboard">
                Dashboard
            </h1>

            <!-- Cards -->

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">

                <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 rounded-2xl p-4 shadow">
                    <p class="text-sm text-slate-500" data-t="total_alumni">Total Alumni</p>
                    <h2 class="text-2xl font-bold mt-1.5">
                        <?= $totalUsers ?>
                    </h2>
                </div>

                <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 rounded-2xl p-4 shadow">
                    <p class="text-sm text-slate-500" data-t="total_posts">Total Posts</p>
                    <h2 class="text-2xl font-bold mt-1.5">
                        <?= $totalPosts ?>
                    </h2>
                </div>

                <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 rounded-2xl p-4 shadow">
                    <p class="text-sm text-slate-500" data-t="total_jobs_label">Total Jobs</p>
                    <h2 class="text-2xl font-bold mt-1.5">
                        <?= $totalJobs ?>
                    </h2>
                </div>

                <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 rounded-2xl p-4 shadow">
                    <p class="text-sm text-slate-500" data-t="contact_messages_label">Contact Messages</p>
                    <h2 class="text-2xl font-bold mt-1.5">
                        <?= $totalMessages ?>
                    </h2>
                </div>

            </div>

            <div class="grid xl:grid-cols-2 gap-6">

                <div class="bg-white rounded-3xl shadow overflow-hidden">

                    <div class="p-5 border-b flex items-center justify-between gap-4">

                        <h2 class="font-bold text-lg" data-t="latest_alumni">
                            Latest Alumni
                        </h2>

                        <a href="users.php" class="text-sm font-semibold text-teal-700 hover:text-teal-800">
                            View All
                        </a>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="w-full table-fixed">

                            <thead class="bg-cyan-50">

                                <tr>

                                    <th class="w-16 p-3 text-left">ID</th>
                                    <th class="w-2/5 p-3 text-left">Name</th>
                                    <th class="w-2/5 p-3 text-left">Email</th>
                                    <th class="w-32 p-3 text-left">Joined</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($user = $latestUsers->fetch_assoc()): ?>

                                    <tr class="border-t">

                                        <td class="p-3 align-middle">
                                            <?= $user['id'] ?>
                                        </td>

                                        <td class="p-3 align-middle">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../images/default-avatar.svg' ?>"
                                                    class="h-9 w-9 rounded-full object-cover border border-cyan-100 shrink-0">
                                                <span
                                                    class="truncate font-medium"><?= htmlspecialchars($user['name']) ?></span>
                                            </div>
                                        </td>

                                        <td class="p-3 align-middle truncate">
                                            <?= htmlspecialchars($user['email']) ?>
                                        </td>

                                        <td class="p-3 align-middle whitespace-nowrap">
                                            <?= date("M d, Y", strtotime($user['created_at'])) ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <div class="bg-white rounded-3xl shadow overflow-hidden">

                    <div class="p-5 border-b flex items-center justify-between gap-4">

                        <h2 class="font-bold text-lg">
                            Latest Posts
                        </h2>

                        <a href="posts.php" class="text-sm font-semibold text-teal-700 hover:text-teal-800">
                            View All
                        </a>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="w-full table-fixed">

                            <thead class="bg-cyan-50">

                                <tr>

                                    <th class="w-1/3 p-3 text-left">User</th>
                                    <th class="w-1/2 p-3 text-left">Content</th>
                                    <th class="w-32 p-3 text-left">Date</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($post = $latestPosts->fetch_assoc()): ?>

                                    <tr class="border-t">

                                        <td class="p-3 align-middle">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <img src="<?= !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : '../images/default-avatar.svg' ?>"
                                                    class="h-9 w-9 rounded-full object-cover border border-cyan-100 shrink-0">
                                                <span
                                                    class="truncate font-semibold"><?= htmlspecialchars($post['name']) ?></span>
                                            </div>
                                        </td>

                                        <td class="p-3 align-middle truncate">
                                            <?= htmlspecialchars(substr($post['content'], 0, 80)) ?>
                                            <?= strlen($post['content']) > 80 ? '...' : '' ?>
                                        </td>

                                        <td class="p-3 align-middle whitespace-nowrap">
                                            <?= date("M d, Y", strtotime($post['created_at'])) ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <div class="bg-white rounded-3xl shadow overflow-hidden">

                    <div class="p-5 border-b flex items-center justify-between gap-4">

                        <h2 class="font-bold text-lg">
                            Latest Jobs
                        </h2>

                        <a href="jobs.php" class="text-sm font-semibold text-teal-700 hover:text-teal-800">
                            View All
                        </a>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="w-full table-fixed">

                            <thead class="bg-cyan-50">

                                <tr>
                                    <th class="w-1/3 p-3 text-left">Posted By</th>
                                    <th class="w-1/4 p-3 text-left">Position</th>
                                    <th class="w-1/4 p-3 text-left">Company</th>
                                    <!-- <th class="w-1/3 p-3 text-left">Posted By</th> -->
                                    <th class="w-32 p-3 text-left">Date</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($job = $latestJobs->fetch_assoc()): ?>

                                    <tr class="border-t">
                                        <td class="p-3 align-middle">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <img src="<?= !empty($job['profile_image']) ? htmlspecialchars($job['profile_image']) : '../images/default-avatar.svg' ?>"
                                                    class="h-9 w-9 rounded-full object-cover border border-cyan-100 shrink-0">
                                                <span class="truncate"><?= htmlspecialchars($job['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="p-3 align-middle font-semibold whitespace-nowrap truncate">
                                            <?= htmlspecialchars($job['position']) ?>
                                        </td>

                                        <td class="p-3 align-middle truncate">
                                            <?= htmlspecialchars($job['company']) ?>
                                        </td>

                                        <!-- <td class="p-3 align-middle">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <img src="<?= !empty($job['profile_image']) ? htmlspecialchars($job['profile_image']) : '../images/default-avatar.svg' ?>" class="h-9 w-9 rounded-full object-cover border border-cyan-100 shrink-0">
                                        <span class="truncate"><?= htmlspecialchars($job['name']) ?></span>
                                    </div>
                                </td> -->

                                        <td class="p-3 align-middle whitespace-nowrap">
                                            <?= date("M d, Y", strtotime($job['created_at'])) ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <div class="bg-white rounded-3xl shadow overflow-hidden">

                    <div class="p-5 border-b flex items-center justify-between gap-4">

                        <h2 class="font-bold text-lg">
                            Latest Contact Messages
                        </h2>

                        <a href="contacts.php" class="text-sm font-semibold text-teal-700 hover:text-teal-800">
                            View All
                        </a>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="w-full table-fixed">

                            <thead class="bg-cyan-50">

                                <tr>

                                    <th class="w-1/4 p-3 text-left">Name</th>
                                    <th class="w-1/4 p-3 text-left">Subject</th>
                                    <th class="w-1/3 p-3 text-left">Message</th>
                                    <th class="w-32 p-3 text-left">Date</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($message = $latestMessages->fetch_assoc()): ?>

                                    <tr class="border-t">

                                        <td class="p-3 align-middle">
                                            <div class="flex items-center gap-3 min-w-0">

                                                <?php
                                                $image = !empty($message['profile_image'])
                                                    ? htmlspecialchars($message['profile_image'])
                                                    : "../images/default-avatar.svg";
                                                ?>

                                                <img src="<?= $image ?>" alt="Profile"
                                                    class="w-10 h-10 rounded-full object-cover border border-cyan-200 shrink-0">

                                                <span class="font-semibold truncate">
                                                    <?= htmlspecialchars($message['name']) ?>
                                                </span>

                                            </div>
                                        </td>
                                        <td class="p-3 align-middle whitespace-nowrap truncate">
                                            <?= htmlspecialchars($message['subject']) ?>
                                        </td>

                                        <td class="p-3 align-middle truncate">
                                            <?= htmlspecialchars(substr($message['message'], 0, 80)) ?>
                                            <?= strlen($message['message']) > 80 ? '...' : '' ?>
                                        </td>

                                        <td class="p-3 align-middle whitespace-nowrap">
                                            <?= date("M d, Y", strtotime($message['created_at'])) ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </main>

    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>
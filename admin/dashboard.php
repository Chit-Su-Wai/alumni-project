
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
    SELECT id,name,email,created_at
    FROM users
    WHERE role='user'
    ORDER BY id DESC
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

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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

        <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">

            <div class="bg-white rounded-3xl p-6 shadow">
                <p class="text-slate-500" data-t="total_alumni">Total Alumni</p>
                <h2 class="text-3xl font-bold mt-2">
                    <?= $totalUsers ?>
                </h2>
            </div>

            <div class="bg-white rounded-3xl p-6 shadow">
                <p class="text-slate-500" data-t="total_posts">Total Posts</p>
                <h2 class="text-3xl font-bold mt-2">
                    <?= $totalPosts ?>
                </h2>
            </div>

            <div class="bg-white rounded-3xl p-6 shadow">
                <p class="text-slate-500" data-t="total_jobs_label">Total Jobs</p>
                <h2 class="text-3xl font-bold mt-2">
                    <?= $totalJobs ?>
                </h2>
            </div>

            <div class="bg-white rounded-3xl p-6 shadow">
                <p class="text-slate-500" data-t="contact_messages_label">Contact Messages</p>
                <h2 class="text-3xl font-bold mt-2">
                    <?= $totalMessages ?>
                </h2>
            </div>

        </div>

        <!-- Latest Users -->

        <div class="bg-white rounded-3xl shadow overflow-hidden">

            <div class="p-5 border-b">

                <h2 class="font-bold text-lg" data-t="latest_alumni">
                    Latest Alumni
                </h2>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-cyan-50">

                        <tr>

                            <th class="p-4 text-left">ID</th>
                            <th class="p-4 text-left">Name</th>
                            <th class="p-4 text-left">Email</th>
                            <th class="p-4 text-left">Joined</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while($user = $latestUsers->fetch_assoc()): ?>

                        <tr class="border-t">

                            <td class="p-4">
                                <?= $user['id'] ?>
                            </td>

                            <td class="p-4">
                                <?= htmlspecialchars($user['name']) ?>
                            </td>

                            <td class="p-4">
                                <?= htmlspecialchars($user['email']) ?>
                            </td>

                            <td class="p-4">
                                <?= $user['created_at'] ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>


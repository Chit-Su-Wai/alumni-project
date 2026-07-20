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

$dashboardSearch = trim($_GET['search'] ?? '');



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

$dashboardSearchResults = [
    'users' => [],
    'posts' => [],
    'jobs' => [],
    'contacts' => [],
    'announcements' => [],
];

if ($dashboardSearch !== '') {
    $keyword = '%' . $dashboardSearch . '%';

    $searchStmt = $conn->prepare("
        SELECT id, name, email, approved_id, profile_image
        FROM users
        WHERE role = 'user' AND (name LIKE ? OR email LIKE ? OR approved_id LIKE ?)
        ORDER BY id DESC
        LIMIT 5
    ");
    $searchStmt->bind_param('sss', $keyword, $keyword, $keyword);
    $searchStmt->execute();
    $dashboardSearchResults['users'] = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $searchStmt = $conn->prepare("
        SELECT p.id, p.content, p.created_at, u.name, u.profile_image
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        WHERE p.content LIKE ? OR u.name LIKE ?
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $searchStmt->bind_param('ss', $keyword, $keyword);
    $searchStmt->execute();
    $dashboardSearchResults['posts'] = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $searchStmt = $conn->prepare("
        SELECT j.id, j.position, j.company, j.location, j.created_at, u.name, u.profile_image
        FROM jobs j
        INNER JOIN users u ON j.user_id = u.id
        WHERE j.position LIKE ? OR j.company LIKE ? OR j.location LIKE ? OR u.name LIKE ?
        ORDER BY j.created_at DESC
        LIMIT 5
    ");
    $searchStmt->bind_param('ssss', $keyword, $keyword, $keyword, $keyword);
    $searchStmt->execute();
    $dashboardSearchResults['jobs'] = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $searchStmt = $conn->prepare("
        SELECT id, name, email, subject, message, created_at
        FROM contact_messages
        WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $searchStmt->bind_param('ssss', $keyword, $keyword, $keyword, $keyword);
    $searchStmt->execute();
    $dashboardSearchResults['contacts'] = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $searchStmt = $conn->prepare("
        SELECT id, title, type, location, description, created_at
        FROM announcements
        WHERE title LIKE ? OR description LIKE ? OR location LIKE ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $searchStmt->bind_param('sss', $keyword, $keyword, $keyword);
    $searchStmt->execute();
    $dashboardSearchResults['announcements'] = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
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

        <main class="flex-1 p-4">

            <div class="admin-page-head">
                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-gauge-high"></i></div>
                    <div>
                        <h1 class="admin-page-title" data-t="dashboard">Dashboard</h1>
                        <p class="admin-page-sub">Live overview of your alumni community</p>
                    </div>
                </div>

                <form method="GET" class="max-w-[380px] w-full">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($dashboardSearch) ?>"
                            placeholder="Search users, posts, jobs, contacts, announcements..." class="input-base">
                    </div>
                </form>
            </div>

            <?php if ($dashboardSearch !== ''): ?>
                <div class="admin-card admin-fade mb-3">
                    <div class="admin-card-head">
                        <div class="admin-card-title"><i class="fa-solid fa-magnifying-glass fa-icon-chip"></i> Search Results</div>
                        <a href="dashboard.php" class="admin-card-link">Clear</a>
                    </div>
                    <div class="p-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($dashboardSearchResults as $type => $items): ?>
                            <div class="rounded-2xl border border-cyan-100 bg-slate-50 p-3">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <h3 class="text-xs font-black uppercase tracking-wide text-teal-700"><?= htmlspecialchars(ucfirst($type)) ?></h3>
                                    <span class="badge badge-cyan"><?= count($items) ?></span>
                                </div>
                                <?php if (empty($items)): ?>
                                    <p class="text-xs text-slate-500">No matches</p>
                                <?php else: ?>
                                    <div class="space-y-2">
                                        <?php foreach ($items as $item): ?>
                                            <?php
                                            $link = '#';
                                            $title = '';
                                            $subtitle = '';
                                            if ($type === 'users') {
                                                $link = 'view_user.php?id=' . $item['id'];
                                                $title = $item['name'];
                                                $subtitle = $item['email'];
                                            } elseif ($type === 'posts') {
                                                $link = 'view_post.php?id=' . $item['id'];
                                                $title = $item['name'];
                                                $subtitle = substr($item['content'], 0, 46) . (strlen($item['content']) > 46 ? '...' : '');
                                            } elseif ($type === 'jobs') {
                                                $link = 'view_job.php?id=' . $item['id'];
                                                $title = $item['position'];
                                                $subtitle = $item['company'] . (!empty($item['location']) ? ' - ' . $item['location'] : '');
                                            } elseif ($type === 'contacts') {
                                                $link = 'view_contact.php?id=' . $item['id'];
                                                $title = $item['name'];
                                                $subtitle = $item['subject'];
                                            } else {
                                                $link = 'announcement_form.php?id=' . $item['id'];
                                                $title = $item['title'];
                                                $subtitle = !empty($item['location']) ? $item['location'] : ucfirst($item['type']);
                                            }
                                            ?>
                                            <a href="<?= htmlspecialchars($link) ?>" class="block rounded-xl border border-white bg-white p-2.5 hover:bg-cyan-50">
                                                <div class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($title) ?></div>
                                                <div class="text-xs text-slate-500 truncate"><?= htmlspecialchars($subtitle) ?></div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Cards -->

            <div class="admin-stagger grid md:grid-cols-2 xl:grid-cols-4 gap-2.5 mb-3">

                <div class="admin-stat">
                    <span class="stat-spark"></span>
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div class="stat-label" data-t="total_alumni">Total Alumni</div>
                </div>

                <div class="admin-stat">
                    <span class="stat-spark"></span>
                    <div class="stat-icon"><i class="fa-regular fa-newspaper"></i></div>
                    <div class="stat-value"><?= $totalPosts ?></div>
                    <div class="stat-label" data-t="total_posts">Total Posts</div>
                </div>

                <div class="admin-stat">
                    <span class="stat-spark"></span>
                    <div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div>
                    <div class="stat-value"><?= $totalJobs ?></div>
                    <div class="stat-label" data-t="total_jobs_label">Total Jobs</div>
                </div>

                <div class="admin-stat">
                    <span class="stat-spark"></span>
                    <div class="stat-icon"><i class="fa-regular fa-envelope"></i></div>
                    <div class="stat-value"><?= $totalMessages ?></div>
                    <div class="stat-label" data-t="contact_messages_label">Contact Messages</div>
                </div>

            </div>

            <div class="admin-stagger grid xl:grid-cols-2 gap-3">

                <div class="admin-card admin-fade">

                    <div class="admin-card-head">

                        <div class="admin-card-title"><i class="fa-solid fa-users fa-icon-chip"></i> <span data-t="latest_alumni">Latest Alumni</span></div>

                        <a href="users.php" class="admin-card-link">View All <i class="fa-solid fa-arrow-right"></i></a>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="admin-table data-table w-full table-fixed">

                                <thead>

                                <tr>

                                    <th class="w-16 p-2 text-left">ID</th>
                                    <th class="w-2/5 p-2 text-left">Name</th>
                                    <th class="w-2/5 p-2 text-left">Email</th>
                                    <th class="w-32 p-2 text-left">Joined</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($user = $latestUsers->fetch_assoc()): ?>

                                    <tr class="border-t">

                                        <td class="p-2 align-middle">
                                            <?= $user['id'] ?>
                                        </td>

                                        <td class="p-2 align-middle">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../images/default-avatar.svg' ?>"
                                                    class="h-7 w-7 rounded-full object-cover border border-cyan-100 shrink-0 cursor-zoom-in"
                                                    onclick="openLightbox(this.src, '<?= htmlspecialchars($user['name']) ?>')">
                                                <span
                                                    class="truncate font-medium"><?= htmlspecialchars($user['name']) ?></span>
                                            </div>
                                        </td>

                                        <td class="p-2 align-middle truncate">
                                            <?= htmlspecialchars($user['email']) ?>
                                        </td>

                                        <td class="p-2 align-middle whitespace-nowrap">
                                            <?= date("M d, Y", strtotime($user['created_at'])) ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <div class="admin-card admin-fade">

                    <div class="admin-card-head">

                        <div class="admin-card-title"><i class="fa-regular fa-newspaper fa-icon-chip"></i> <span>Latest Posts</span></div>

                        <a href="posts.php" class="admin-card-link">View All <i class="fa-solid fa-arrow-right"></i></a>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="admin-table data-table w-full table-fixed">

                                <thead>

                                <tr>

                                    <th class="w-1/3 p-2 text-left">User</th>
                                    <th class="w-1/2 p-2 text-left">Content</th>
                                    <th class="w-32 p-2 text-left">Date</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($post = $latestPosts->fetch_assoc()): ?>

                                    <tr class="border-t">

                                        <td class="p-2 align-middle">
                                            <div class="flex items-center gap-2 min-w-0">
                                            <img src="<?= !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : '../images/default-avatar.svg' ?>"
                                                class="h-7 w-7 rounded-full object-cover border border-cyan-100 shrink-0 cursor-zoom-in"
                                                onclick="openLightbox(this.src, '<?= htmlspecialchars($post['name']) ?>')">
                                                <span
                                                    class="truncate font-semibold"><?= htmlspecialchars($post['name']) ?></span>
                                            </div>
                                        </td>

                                        <td class="p-2 align-middle truncate">
                                            <?= htmlspecialchars(substr($post['content'], 0, 80)) ?>
                                            <?= strlen($post['content']) > 80 ? '...' : '' ?>
                                        </td>

                                        <td class="p-2 align-middle whitespace-nowrap">
                                            <?= date("M d, Y", strtotime($post['created_at'])) ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <div class="admin-card admin-fade">

                    <div class="admin-card-head">

                        <div class="admin-card-title"><i class="fa-solid fa-briefcase fa-icon-chip"></i> <span>Latest Jobs</span></div>

                        <a href="jobs.php" class="admin-card-link">View All <i class="fa-solid fa-arrow-right"></i></a>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="admin-table data-table w-full table-fixed">

                                <thead>

                                <tr>
                                    <th class="w-1/3 p-2 text-left">Posted By</th>
                                    <th class="w-1/4 p-2 text-left">Position</th>
                                    <th class="w-1/4 p-2 text-left">Company</th>
                                    <!-- <th class="w-1/3 p-3 text-left">Posted By</th> -->
                                    <th class="w-32 p-2 text-left">Date</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($job = $latestJobs->fetch_assoc()): ?>

                                    <tr class="border-t">
                                        <td class="p-2 align-middle">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <img src="<?= !empty($job['profile_image']) ? htmlspecialchars($job['profile_image']) : '../images/default-avatar.svg' ?>"
                                                    class="h-7 w-7 rounded-full object-cover border border-cyan-100 shrink-0 cursor-zoom-in"
                                                    onclick="openLightbox(this.src, '<?= htmlspecialchars($job['name']) ?>')">
                                                <span class="truncate"><?= htmlspecialchars($job['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="p-2 align-middle font-semibold whitespace-nowrap truncate">
                                            <?= htmlspecialchars($job['position']) ?>
                                        </td>

                                        <td class="p-2 align-middle truncate">
                                            <?= htmlspecialchars($job['company']) ?>
                                        </td>

                                        <!-- <td class="p-3 align-middle">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <img src="<?= !empty($job['profile_image']) ? htmlspecialchars($job['profile_image']) : '../images/default-avatar.svg' ?>" class="h-9 w-9 rounded-full object-cover border border-cyan-100 shrink-0">
                                        <span class="truncate"><?= htmlspecialchars($job['name']) ?></span>
                                    </div>
                                </td> -->

                                        <td class="p-2 align-middle whitespace-nowrap">
                                            <?= date("M d, Y", strtotime($job['created_at'])) ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <div class="admin-card admin-fade">

                    <div class="admin-card-head">

                        <div class="admin-card-title"><i class="fa-regular fa-envelope fa-icon-chip"></i> <span>Latest Contact Messages</span></div>

                        <a href="contacts.php" class="admin-card-link">View All <i class="fa-solid fa-arrow-right"></i></a>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="admin-table data-table w-full table-fixed">

                                <thead>

                                <tr>

                                    <th class="w-1/4 p-2 text-left">Name</th>
                                    <th class="w-1/4 p-2 text-left">Subject</th>
                                    <th class="w-1/3 p-2 text-left">Message</th>
                                    <th class="w-32 p-2 text-left">Date</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($message = $latestMessages->fetch_assoc()): ?>

                                    <tr class="border-t">

                                        <td class="p-2 align-middle">
                                            <div class="flex items-center gap-2 min-w-0">

                                                <?php
                                                $image = !empty($message['profile_image'])
                                                    ? htmlspecialchars($message['profile_image'])
                                                    : "../images/default-avatar.svg";
                                                ?>

                                                <img src="<?= $image ?>" alt="Profile"
                                                    class="w-7 h-7 rounded-full object-cover border border-cyan-200 shrink-0 cursor-zoom-in"
                                                    onclick="openLightbox(this.src, '<?= htmlspecialchars($message['name']) ?>')">

                                                <span class="font-semibold truncate">
                                                    <?= htmlspecialchars($message['name']) ?>
                                                </span>

                                            </div>
                                        </td>
                                        <td class="p-2 align-middle whitespace-nowrap truncate">
                                            <?= htmlspecialchars($message['subject']) ?>
                                        </td>

                                        <td class="p-2 align-middle truncate">
                                            <?= htmlspecialchars(substr($message['message'], 0, 80)) ?>
                                            <?= strlen($message['message']) > 80 ? '...' : '' ?>
                                        </td>

                                        <td class="p-2 align-middle whitespace-nowrap">
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

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

/* Delete Post */

if (isset($_GET['delete'])) {

    $post_id = (int) $_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM posts
        WHERE id = ?
    ");

    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    header("Location: posts.php");
    exit;
}

/* Pagination */

$limit = 5;

$page = max(
    1,
    (int) ($_GET['page'] ?? 1)
);

$offset = ($page - 1) * $limit;

/* Search */

$search = trim($_GET['search'] ?? '');

/* Total Posts */

if ($search != '') {

    $countStmt = $conn->prepare("
        SELECT COUNT(*) total
        FROM posts p
        INNER JOIN users u
        ON p.user_id = u.id
        WHERE
            p.content LIKE ?
            OR
            u.name LIKE ?
    ");

    $keyword = "%{$search}%";

    $countStmt->bind_param(
        "ss",
        $keyword,
        $keyword
    );

    $countStmt->execute();

    $totalPosts = $countStmt->get_result()->fetch_assoc()['total'];

} else {

    $totalPosts = $conn->query("
        SELECT COUNT(*) total
        FROM posts
    ")->fetch_assoc()['total'];
}

$totalPages = ceil($totalPosts / $limit);

/* Posts Query */

if ($search != '') {

    $stmt = $conn->prepare("
        SELECT
            p.*,
            u.name,
            u.profile_image

        FROM posts p

        INNER JOIN users u
        ON p.user_id = u.id

        WHERE
            p.content LIKE ?
            OR
            u.name LIKE ?

        ORDER BY p.created_at DESC
        LIMIT ?, ?
    ");

    $keyword = "%{$search}%";

    $stmt->bind_param(
        "ssii",
        $keyword,
        $keyword,
        $offset,
        $limit
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            p.*,
            u.name,
            u.profile_image

        FROM posts p

        INNER JOIN users u
        ON p.user_id = u.id

        ORDER BY p.created_at DESC
        LIMIT ?, ?
    ");

    $stmt->bind_param(
        "ii",
        $offset,
        $limit
    );
}

$stmt->execute();

$posts = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Manage Posts</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

    <div class="min-h-screen flex">
        <?php include "../include/admin_header.php"; ?>

        <div class="flex-1 flex flex-col min-h-screen p-6">

            <!-- Header -->

            <div class="flex items-center justify-between mb-6">

                <h1 class="text-3xl font-bold text-teal-700">
                    Posts Management
                </h1>


            </div>

            <!-- Card -->

            <!-- Total Posts -->
            <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 rounded-xl shadow-sm p-2 mb-3 w-[300px]">

                <p class="text-xl text-slate-500">
                    Total Posts
                </p>

                <h2 class="text-lg font-bold leading-tight">
                    <?= $totalPosts ?>
                </h2>

            </div>

            <!-- Search -->
            <div class="mb-4 w-[300px]">

                <form method="GET">

                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search post or user..." class="w-full border rounded-lg px-3 py-2 text-sm">

                </form>

            </div>

            <!-- Posts Grid -->

            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 flex-1 content-start">

                <?php while ($post = $posts->fetch_assoc()): ?>

                    <article class="bg-white rounded-2xl shadow p-4">

                        <div class="flex items-start gap-3 mb-3">

                            <img src="<?= !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : '../images/default-avatar.svg' ?>"
                                class="h-10 w-10 rounded-full object-cover border border-cyan-100 shrink-0">

                            <div class="min-w-0">
                                <h2 class="font-semibold text-slate-800 truncate">
                                    <?= htmlspecialchars($post['name']) ?>
                                </h2>
                                <p class="text-xs text-slate-500 whitespace-nowrap">
                                    <?= date("M d, Y h:i A", strtotime($post['created_at'])) ?>
                                </p>
                            </div>

                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Post
                            </p>
                            <p class="mt-1 text-sm text-slate-700 leading-6">
                                <?= htmlspecialchars(substr($post['content'], 0, 140)) ?>
                                <?= strlen($post['content']) > 140 ? '...' : '' ?>
                            </p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">

                            <a href="view_post.php?id=<?= $post['id'] ?>"
                                class="bg-green-500 text-white px-3 py-1.5 text-sm rounded-lg">

                                View

                            </a>

                            <a href="?delete=<?= $post['id'] ?>" onclick="return confirm('Delete this post?')"
                                class="bg-red-500 text-white px-3 py-1.5 text-sm rounded-lg">

                                Delete

                            </a>

                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

            <!-- Pagination -->
            <div class="mt-auto pt-7 pb-4">
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


            <!-- <?php if ($totalPages > 1): ?>

                <div class="flex justify-center gap-2 mt-8">

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                        <a href="?<?= $search ? 'search=' . urlencode($search) . '&' : '' ?>page=<?= $i ?>" class="px-4 py-2 rounded-xl
               <?= $page == $i
                   ? 'bg-cyan-500 text-white'
                   : 'bg-white shadow' ?>">

                            <?= $i ?>

                        </a>

                    <?php endfor; ?>

                </div>

            <?php endif; ?> -->

        </div>

    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>
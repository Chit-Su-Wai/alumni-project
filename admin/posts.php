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

    $metaStmt = $conn->prepare("SELECT content FROM posts WHERE id = ? LIMIT 1");
    $metaStmt->bind_param("i", $post_id);
    $metaStmt->execute();
    $metaRow = $metaStmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("
        DELETE FROM posts
        WHERE id = ?
    ");

    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    admin_log_activity(
        $conn,
        'Delete Post',
        'Deleted post #' . $post_id . (!empty($metaRow['content']) ? ' - ' . substr($metaRow['content'], 0, 60) . (strlen($metaRow['content']) > 60 ? '...' : '') : ''),
        'post',
        $post_id
    );

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

        <div class="flex-1 flex flex-col min-h-screen p-4">

            <!-- Header -->

            <div class="admin-page-head">

                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-newspaper"></i></div>
                    <div>
                        <h1 class="admin-page-title">Posts Management</h1>
                        <p class="admin-page-sub">Review and moderate alumni posts</p>
                    </div>
                </div>

                <form method="GET" class="max-w-[420px] w-full">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Search post or user..." class="input-base">
                    </div>
                </form>

            </div>

            <!-- Card -->

            <!-- Total Posts -->
            <div class="admin-stat w-[220px] mb-3">
                <span class="stat-spark"></span>
                <div class="stat-icon"><i class="fa-solid fa-newspaper"></i></div>
                <div class="stat-value"><?= $totalPosts ?></div>
                <div class="stat-label">Total Posts</div>
            </div>

            <!-- Posts Grid -->

            <?php if ($totalPosts > 0): ?>
            <div class="admin-stagger grid md:grid-cols-2 xl:grid-cols-3 gap-3 flex-1 content-start">

                <?php while ($post = $posts->fetch_assoc()): ?>

                    <article class="admin-card p-4">

                        <div class="flex items-start gap-3 mb-3">

                            <img src="<?= !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : '../images/default-avatar.svg' ?>"
                                class="admin-avatar shrink-0 cursor-zoom-in"
                                onclick="openLightbox(this.src, '<?= htmlspecialchars($post['name']) ?>')">

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
                                class="btn btn-ghost btn-sm">

                                View

                            </a>

                            <a href="?delete=<?= $post['id'] ?>" onclick="event.preventDefault(); confirmDialog('Delete this post?', function(){ window.location.href='?delete=<?= $post['id'] ?>'; }, {title:'Delete Post', confirmText:'Delete'})"
                                class="btn btn-danger btn-sm">

                                Delete

                            </a>

                        </div>

                    </article>

                <?php endwhile; ?>

            </div>
            <?php else: ?>
            <?php
            $es_icon    = 'fa-regular fa-newspaper';
            $es_title   = 'No posts yet';
            $es_message = 'There are no posts to display yet.';
            $es_action  = '';
            include '../include/empty_state.php';
            ?>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPosts > 0): ?>
            <div class="mt-4 flex flex-col items-center gap-2 pb-4">

                <!-- Info Row -->
                <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span>
                        Showing
                        <strong class="text-slate-700"><?= $offset + 1 ?>&ndash;<?= min($offset + $limit, $totalPosts) ?></strong>
                        of
                        <strong class="text-slate-700"><?= $totalPosts ?></strong>
                    </span>

                </div>

                <!-- Controls Row -->
                <div class="pagination">

                    <?php $searchParam = $search ? 'search=' . urlencode($search) . '&' : ''; ?>

                    <!-- Previous -->
                    <?php if ($page > 1): ?>
                        <a href="?<?= $searchParam ?>page=<?= $page - 1 ?>">
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
                        <a href="?<?= $searchParam ?>page=1">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?<?= $searchParam ?>page=<?= $i ?>"
                            class="<?= $page == $i ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                        <a href="?<?= $searchParam ?>page=<?= $totalPages ?>"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <!-- Next -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= $searchParam ?>page=<?= $page + 1 ?>">
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

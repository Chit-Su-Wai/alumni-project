
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

    $post_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM posts
        WHERE id = ?
    ");

    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    header("Location: posts.php");
    exit;
}

/* Search */

$search = trim($_GET['search'] ?? '');

/* Posts Query */

if ($search != '') {

    $stmt = $conn->prepare("
        SELECT
            p.*,
            u.name

        FROM posts p

        INNER JOIN users u
        ON p.user_id = u.id

        WHERE
            p.content LIKE ?
            OR
            u.name LIKE ?

        ORDER BY p.created_at DESC
    ");

    $keyword = "%{$search}%";

    $stmt->bind_param(
        "ss",
        $keyword,
        $keyword
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            p.*,
            u.name

        FROM posts p

        INNER JOIN users u
        ON p.user_id = u.id

        ORDER BY p.created_at DESC
    ");
}

$stmt->execute();

$posts = $stmt->get_result();

/* Total Posts */

$totalPosts = $conn->query("
    SELECT COUNT(*) total
    FROM posts
")->fetch_assoc()['total'];
?>


<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Manage Posts</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<div class="min-h-screen flex">
<?php include "../include/admin_header.php"; ?>

    <div class="flex-1 p-6">

        <!-- Header -->

        <div class="flex items-center justify-between mb-6">

            <h1 class="text-3xl font-bold text-teal-700">
                Posts Management
            </h1>

           
        </div>

        <!-- Card -->

        <div class="bg-white rounded-3xl shadow p-6 mb-6">

            <p class="text-slate-500">
                Total Posts
            </p>

            <h2 class="text-4xl font-bold mt-2">
                <?= $totalPosts ?>
            </h2>

        </div>

        <!-- Search -->

        <div class="bg-white rounded-3xl shadow p-5 mb-6">

            <form method="GET">

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search post or user..."
                    class="w-full border rounded-xl px-4 py-3">

            </form>

        </div>

        <!-- Posts Table -->

        <div class="bg-white rounded-3xl shadow overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-cyan-50">

                        <tr>

                            <th class="p-4 text-left">
                                User
                            </th>

                            <th class="p-4 text-left">
                                Content
                            </th>

                            <th class="p-4 text-left">
                                Date
                            </th>

                            <th class="p-4 text-left">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while($post = $posts->fetch_assoc()): ?>

                        <tr class="border-t">

                            <td class="p-4 font-semibold">

                                <?= htmlspecialchars($post['name']) ?>

                            </td>

                            <td class="p-4">

                                <?= htmlspecialchars(
                                    substr($post['content'],0,80)
                                ) ?>

                            </td>

                            <td class="p-4 text-slate-500">

                                <?= $post['created_at'] ?>

                            </td>

                            <td class="p-4">

                                <div class="flex gap-2 flex-wrap">

                                    <!-- <a
                                        href="../user/user_profile.php?id=<?= $post['user_id'] ?>"
                                        class="bg-blue-500 text-white px-3 py-2 rounded-lg">

                                        User

                                    </a> -->

                                    <a
                                        href="view_post.php?id=<?= $post['id'] ?>"
                                        class="bg-green-500 text-white px-3 py-2 rounded-lg">

                                        View

                                    </a>

                                    <a
                                        href="?delete=<?= $post['id'] ?>"
                                        onclick="return confirm('Delete this post?')"
                                        class="bg-red-500 text-white px-3 py-2 rounded-lg">

                                        Delete

                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>


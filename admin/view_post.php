
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

$post_id = (int)($_GET['id'] ?? 0);

/* Post */

$stmt = $conn->prepare("
SELECT
    p.*,
    u.name,
    u.email,
    u.profile_image
FROM posts p
INNER JOIN users u
ON p.user_id = u.id
WHERE p.id = ?
");

$stmt->bind_param("i",$post_id);
$stmt->execute();

$post = $stmt->get_result()->fetch_assoc();

if(!$post){
    die("Post not found");
}

/* Images */

$imageStmt = $conn->prepare("
SELECT image
FROM post_images
WHERE post_id = ?
");

$imageStmt->bind_param("i",$post_id);
$imageStmt->execute();

$images = $imageStmt->get_result();

/* Likes Count */

$likeStmt = $conn->prepare("
SELECT COUNT(*) total
FROM post_likes
WHERE post_id = ?
");

$likeStmt->bind_param("i",$post_id);
$likeStmt->execute();

$totalLikes =
$likeStmt->get_result()->fetch_assoc()['total'];

/* Comments Count */

$commentCountStmt = $conn->prepare("
SELECT COUNT(*) total
FROM comments
WHERE post_id = ?
");

$commentCountStmt->bind_param("i",$post_id);
$commentCountStmt->execute();

$totalComments =
$commentCountStmt->get_result()->fetch_assoc()['total'];

/* Comments */

$commentStmt = $conn->prepare("
SELECT
    c.*,
    u.name
FROM comments c
INNER JOIN users u
ON c.user_id = u.id
WHERE c.post_id = ?
ORDER BY c.created_at DESC
");

$commentStmt->bind_param("i",$post_id);
$commentStmt->execute();

$comments =
$commentStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>View Post</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<div class="flex min-h-screen">

    <?php include "../include/admin_header.php"; ?>

    <div class="flex-1 p-6">

        <div class="max-w-4xl">

            <div class="flex justify-between mb-6">

                <h1 class="text-3xl font-bold text-teal-700">
                    View Post
                </h1>

                <a href="posts.php"
                   class="rounded-xl bg-slate-200 hover:bg-slate-300 px-4 py-2 text-sm font-semibold transition">

                    <i class="fa-solid fa-arrow-left mr-1"></i> Back

                </a>

            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-cyan-50 overflow-hidden">

                <div class="p-6 border-b border-slate-100">

                    <div class="flex items-center gap-4">

                        <img
                        src="<?= !empty($post['profile_image'])
                            ? htmlspecialchars($post['profile_image'])
                            : '../images/default-avatar.svg' ?>"
                        class="w-12 h-12 rounded-full object-cover border-2 border-cyan-100">

                        <div>

                            <h2 class="font-bold text-slate-800">
                                <?= htmlspecialchars($post['name']) ?>
                            </h2>

                            <p class="text-xs text-slate-400">
                                <?= htmlspecialchars($post['email']) ?>
                            </p>

                        </div>

                    </div>

                </div>

                <div class="p-6">

                    <p class="text-slate-700 leading-7">
                        <?= nl2br(htmlspecialchars($post['content'])) ?>
                    </p>

                    <div class="grid md:grid-cols-2 gap-4 mt-4">

                        <?php while($img = $images->fetch_assoc()): ?>

                            <img
                            src="<?= htmlspecialchars($img['image']) ?>"
                            class="rounded-2xl w-full object-cover">

                        <?php endwhile; ?>

                    </div>

                    <div class="flex gap-6 mt-6 text-sm text-slate-600">

                        <span class="flex items-center gap-1">
                            <i class="fa-regular fa-thumbs-up text-teal-600"></i>
                            <strong><?= $totalLikes ?></strong> Likes
                        </span>

                        <span class="flex items-center gap-1">
                            <i class="fa-regular fa-comment text-teal-600"></i>
                            <strong><?= $totalComments ?></strong> Comments
                        </span>

                    </div>

                    <div class="mt-4 flex items-center gap-4 text-xs text-slate-400">

                        <span class="rounded-full bg-cyan-100 px-3 py-1 text-cyan-700 font-semibold">
                            <?= htmlspecialchars($post['category']) ?>
                        </span>

                        <span>
                            <i class="fa-regular fa-clock mr-1"></i>
                            <?= $post['created_at'] ?>
                        </span>

                    </div>

                </div>

            </div>

            <!-- Comments -->

            <div class="bg-white rounded-3xl shadow-sm border border-cyan-50 mt-6 p-6">

                <h2 class="font-bold text-lg mb-4 text-slate-800">
                    <i class="fa-regular fa-comment mr-2 text-teal-600"></i>Comments
                </h2>

                <?php while($comment = $comments->fetch_assoc()): ?>

                    <div class="border-b border-slate-100 py-3 last:border-0">

                        <div class="font-semibold text-sm text-slate-700">

                            <?= htmlspecialchars($comment['name']) ?>

                        </div>

                        <div class="mt-1 text-sm text-slate-600">

                            <?= htmlspecialchars($comment['comment']) ?>

                        </div>

                        <div class="text-xs text-slate-400 mt-1">

                            <i class="fa-regular fa-clock mr-1"></i>
                            <?= $comment['created_at'] ?>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        </div>

    </div>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>

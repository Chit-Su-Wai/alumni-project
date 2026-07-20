<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";
if (
    isset($_SESSION['role']) &&
    $_SESSION['role'] == 'admin'
) {
    header("Location: ../admin/dashboard.php");
    exit;
}
$user_id = (int) $_SESSION["user_id"];

$stmt = $conn->prepare("
SELECT u.*, a.graduated_year
FROM users u
LEFT JOIN approved_students a
ON u.approved_id = a.approved_id
WHERE u.id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

function e($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

$profileImage = !empty($user["profile_image"])
    ? $user["profile_image"]
    : "../images/default-avatar.svg";

$profileMessage = $_SESSION['profile_message'] ?? '';
unset($_SESSION['profile_message']);

$savedPostIds = [];
$savedIdsStmt = $conn->prepare("SELECT post_id FROM saved_posts WHERE user_id = ?");
$savedIdsStmt->bind_param('i', $user_id);
$savedIdsStmt->execute();
$savedIdsResult = $savedIdsStmt->get_result();
while ($savedRow = $savedIdsResult->fetch_assoc()) {
    $savedPostIds[(int) $savedRow['post_id']] = true;
}

$savedLimit = 5;
$savedPage = max(1, (int) ($_GET['saved_page'] ?? 1));
$savedOffset = ($savedPage - 1) * $savedLimit;
$savedCountStmt = $conn->prepare("
    SELECT COUNT(*) total
    FROM saved_posts sp
    INNER JOIN posts p ON p.id = sp.post_id
    INNER JOIN users u ON u.id = p.user_id
    WHERE sp.user_id = ? AND u.role = 'user'
");
$savedCountStmt->bind_param('i', $user_id);
$savedCountStmt->execute();
$savedTotal = (int) ($savedCountStmt->get_result()->fetch_assoc()['total'] ?? 0);
$savedTotalPages = max(1, (int) ceil($savedTotal / $savedLimit));

$savedPostsStmt = $conn->prepare("
    SELECT p.id, p.content, p.category, p.image, p.created_at, u.name, u.profile_image
    FROM saved_posts sp
    INNER JOIN posts p ON p.id = sp.post_id
    INNER JOIN users u ON u.id = p.user_id
    WHERE sp.user_id = ? AND u.role = 'user'
    ORDER BY sp.created_at DESC
    LIMIT ?, ?
");
$savedPostsStmt->bind_param('iii', $user_id, $savedOffset, $savedLimit);
$savedPostsStmt->execute();
$savedPosts = $savedPostsStmt->get_result();

/* Jobs */

$jobStmt = $conn->prepare(
    "SELECT *
FROM jobs
WHERE user_id = ?
ORDER BY created_at DESC"
);

$jobStmt->bind_param("i", $user_id);
$jobStmt->execute();

$jobs = $jobStmt->get_result();

/* Posts */

$postStmt = $conn->prepare(
    "SELECT *
FROM posts
WHERE user_id = ?
ORDER BY created_at DESC"
);

$postStmt->bind_param("i", $user_id);
$postStmt->execute();

$posts = $postStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

    <?php include "../include/user_header.php"; ?>

    <main class="max-w-6xl mx-auto px-4 py-6">

        <?php if (!empty($profileMessage)): ?>
            <div class="mb-4 rounded-2xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-bold text-teal-700">
                <?= e($profileMessage) ?>
            </div>
        <?php endif; ?>

        <section class="overflow-hidden rounded-3xl bg-white shadow">

            <div class="h-48 bg-gradient-to-r from-cyan-400 via-teal-400 to-teal-600">
            </div>

            <div class="relative px-6 pb-6">

                <div class="-mt-16 flex flex-col md:flex-row md:items-end md:justify-between">

                    <div class="flex flex-col md:flex-row items-center gap-4">

                        <img src="<?= e($profileImage) ?>"
                            class="h-32 w-32 rounded-full border-4 border-white object-cover shadow-lg cursor-zoom-in"
                            onclick="openLightbox(this.src, '<?= e($user['name']) ?>')">

                        <div>

                            <h1 class="text-3xl font-black">

                                <?= e($user["name"]) ?>

                            </h1>

                            <p class="text-slate-500">

                                <?= e($user["email"]) ?>

                            </p>

                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-slate-500">

                                <span>
                                    <i class="fa-solid fa-phone"></i>
                                    <?= e($user["phone"]) ?>
                                </span>

                                <span>
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?= e($user["address"]) ?>
                                </span>

                                <span>
                                    <i class="fa-solid fa-graduation-cap"></i>
                                    <?= e($user["graduated_year"]) ?>
                                </span>

                            </div>

                        </div>

                    </div>

                    <a href="edit_profile.php"
                        class="btn btn-primary mt-4 md:mt-0">

                        <i class="fa-solid fa-pen"></i> Edit Profile

                    </a>

                </div>

            </div>

        </section>

        <?php
        $jobsCount = $jobs->num_rows;
        include "../include/profile_progress.php";
        ?>

        <!-- Tabs -->

        <div class="mt-6 rounded-3xl bg-white p-4 shadow">

            <div class="flex flex-wrap gap-2">

                <button onclick="showTab('personal')" id="btn-personal"
                    class="tab-btn rounded-full bg-cyan-100 px-4 py-2 font-semibold text-cyan-700">

                    Personal

                </button>

                <button onclick="showTab('experience')" id="btn-experience" class="tab-btn rounded-full px-4 py-2">

                    Experience

                </button>

                <button onclick="showTab('social')" id="btn-social" class="tab-btn rounded-full px-4 py-2">

                    Social

                </button>

                <button onclick="showTab('posts')" id="btn-posts" class="tab-btn rounded-full px-4 py-2">

                    Posts

                </button>

                <button onclick="showTab('saved')" id="btn-saved" class="tab-btn rounded-full px-4 py-2">

                    Saved Posts

                </button>

            </div>


            <!-- PERSONAL TAB -->

            <div id="personal" class="tab-content mt-6">

                <div class="rounded-3xl border border-cyan-100  p-6 shadow-sm">

                    <div class="mb-6 flex items-center gap-3 border-b border-cyan-100 pb-4">

                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-teal-500 text-white shadow-sm">

                            <i class="fa-solid fa-user text-sm"></i>

                        </div>

                        <h3 class="text-xl font-black text-teal-700">

                            Personal Information

                        </h3>

                    </div>

                    <div class="grid md:grid-cols-2 gap-6">

                        <div class="rounded-2xl bg-cyan-50/60 p-4 transition hover:bg-cyan-50">

                            <label class="flex items-center gap-2 text-xs font-bold tracking-wide text-teal-600 uppercase">

                                <i class="fa-solid fa-user text-teal-400 text-[10px]"></i>
                                Full Name

                            </label>

                            <p class="mt-1.5 text-base font-bold text-slate-800">

                                <?= e($user['name']) ?>

                            </p>

                        </div>

                        <div class="rounded-2xl bg-cyan-50/60 p-4 transition hover:bg-cyan-50">

                            <label class="flex items-center gap-2 text-xs font-bold tracking-wide text-teal-600 uppercase">

                                <i class="fa-solid fa-envelope text-teal-400 text-[10px]"></i>
                                Email

                            </label>

                            <p class="mt-1.5 text-base font-bold text-slate-800">

                                <?= e($user['email']) ?>

                            </p>

                        </div>

                        <div class="rounded-2xl bg-cyan-50/60 p-4 transition hover:bg-cyan-50">

                            <label class="flex items-center gap-2 text-xs font-bold tracking-wide text-teal-600 uppercase">

                                <i class="fa-solid fa-phone text-teal-400 text-[10px]"></i>
                                Phone

                            </label>

                            <p class="mt-1.5 text-base font-bold text-slate-800">

                                <?= e($user['phone']) ?>

                            </p>

                        </div>

                        <div class="rounded-2xl bg-cyan-50/60 p-4 transition hover:bg-cyan-50">

                            <label class="flex items-center gap-2 text-xs font-bold tracking-wide text-teal-600 uppercase">

                                <i class="fa-solid fa-location-dot text-teal-400 text-[10px]"></i>
                                Address

                            </label>

                            <p class="mt-1.5 text-base font-bold text-slate-800">

                                <?= e($user['address']) ?>

                            </p>

                        </div>

                        <div class="rounded-2xl bg-cyan-50/60 p-4 transition hover:bg-cyan-50">

                            <label class="flex items-center gap-2 text-xs font-bold tracking-wide text-teal-600 uppercase">

                                <i class="fa-solid fa-graduation-cap text-teal-400 text-[10px]"></i>
                                Graduated Year

                            </label>

                            <p class="mt-1.5 text-base font-bold text-slate-800">

                                <?= e($user['graduated_year']) ?>

                            </p>

                        </div>

                    </div>

                    <?php if (!empty($user['bio'])): ?>

                        <div class="mt-6 rounded-2xl border border-cyan-100 bg-cyan-50/60 p-4">

                            <label class="flex items-center gap-2 text-xs font-bold tracking-wide text-teal-600 uppercase">

                                <i class="fa-solid fa-quote-left text-teal-400 text-[10px]"></i>
                                Bio

                            </label>

                            <p class="mt-1.5 text-base leading-7 text-slate-700">

                                <?= nl2br(e($user['bio'])) ?>

                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

            <!-- EXPERIENCE TAB -->

            <div id="experience" class="tab-content hidden mt-6">

                <?php if ($jobs->num_rows == 0): ?>
                    <?php
                    $es_icon    = 'fa-solid fa-briefcase';
                    $es_title   = 'No work experience yet';
                    $es_message = 'Add your work experience from your profile settings.';
                    include '../include/empty_state.php';
                    ?>
                <?php endif; ?>

                <?php while ($job = $jobs->fetch_assoc()): ?>

                    <div class="mb-5 rounded-3xl bg-slate-50 p-6">

                        <div class="flex items-start justify-between">

                            <div>

                                <h3 class="text-xl font-bold">

                                    <?= e($job['position']) ?>

                                </h3>

                                <p class="text-cyan-600 font-semibold">

                                    <?= e($job['company']) ?>

                                </p>

                            </div>

                            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700">

                                <?= e($job['job_type']) ?>

                            </span>

                        </div>

                        <div class="mt-4 grid md:grid-cols-2 gap-4 text-sm">

                            <div>

                                <i class="fa-solid fa-location-dot"></i>

                                <?= e($job['location']) ?>

                            </div>

                            <div>

                                <i class="fa-solid fa-money-bill"></i>

                                <?= e($job['salary']) ?>

                            </div>

                        </div>

                        <?php if (!empty($job['description'])): ?>

                            <div class="mt-4 text-slate-600 leading-7">

                                <?= nl2br(e($job['description'])) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php endwhile; ?>

            </div>


            <!-- SOCIAL TAB -->

            <div id="social" class="tab-content hidden mt-6">

                <div class="rounded-3xl bg-slate-50 p-6">

                    <h3 class="mb-4 text-xl font-bold">

                        Social Media

                    </h3>

                    <div class="grid md:grid-cols-2 gap-4">

                        <?php
                        $socials = [
                            'facebook' => 'Facebook',
                            'linkedin' => 'LinkedIn',
                            'github' => 'GitHub',
                            'telegram' => 'Telegram',
                            'instagram' => 'Instagram',
                            'youtube' => 'YouTube',
                            'tiktok' => 'TikTok',
                            'line_id' => 'Line',
                            'viber' => 'Viber',
                            'whatsapp' => 'WhatsApp'
                        ];

                        foreach ($socials as $key => $label):
                            ?>

                            <div class="rounded-2xl bg-white p-4 shadow-sm">

                                <label class="text-sm text-slate-500">

                                    <?= $label ?>

                                </label>

                                <p class="font-semibold">

                                    <?= !empty($user[$key])
                                        ? e($user[$key])
                                        : 'Not Added' ?>

                                </p>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

            <!-- POSTS TAB -->

            <div id="posts" class="tab-content hidden mt-6">

                <?php if ($posts->num_rows == 0): ?>
                    <?php
                    $es_icon    = 'fa-regular fa-newspaper';
                    $es_title   = 'No posts yet';
                    $es_message = 'Share something with your alumni network.';
                    include '../include/empty_state.php';
                    ?>
                <?php endif; ?>

                <?php while ($post = $posts->fetch_assoc()): ?>

<?php

/* LIKE COUNT */

$likeStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM post_likes
    WHERE post_id = ?
");

$likeStmt->bind_param("i", $post['id']);
$likeStmt->execute();

$likeCount =
$likeStmt->get_result()
->fetch_assoc()['total'];


/* COMMENT COUNT */

$commentCount = 0;

$checkTable = $conn->query(
    "SHOW TABLES LIKE 'comments'"
);

if($checkTable->num_rows > 0){

    $commentStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM comments
        WHERE post_id = ?
    ");

    $commentStmt->bind_param(
        "i",
        $post['id']
    );

    $commentStmt->execute();

    $commentCount =
    $commentStmt
    ->get_result()
    ->fetch_assoc()['total'];
}
?>


                    <div class="mb-6 rounded-3xl bg-slate-50 p-5">

                        <!-- Post Header -->

                        <div class="flex items-center justify-between">

                            <div class="flex items-center gap-3">

                                <img src="<?= e($profileImage) ?>" class="h-12 w-12 rounded-full object-cover">

                                <div>

                                    <h3 class="font-bold">

                                        <?= e($user['name']) ?>

                                    </h3>

                                    <p class="text-xs text-slate-500">

                                        <?= date(
                                            "M d, Y h:i A",
                                            strtotime($post['created_at'])
                                        ) ?>

                                    </p>

                                </div>

                            </div>

                            <div>

                                <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700">

                                    <?= e($post['category']) ?>

                                </span>

                            </div>

                        </div>

                        <!-- Content -->

                        <div class="mt-4 text-slate-700 leading-7">

                            <?= nl2br(e($post['content'])) ?>

                        </div>

                        <!-- Images -->

                        <?php

                        $imgStmt = $conn->prepare(
                            "SELECT image
 FROM post_images
 WHERE post_id = ?"
                        );

                        $imgStmt->bind_param(
                            "i",
                            $post['id']
                        );

                        $imgStmt->execute();

                        $images = $imgStmt->get_result();

                        if ($images->num_rows > 0):

                            ?>

                            <div class="mt-4 grid grid-cols-2 gap-2">

                                <?php while ($img = $images->fetch_assoc()): ?>

                                    <img src="<?= e($img['image']) ?>" class="h-56 w-full rounded-2xl object-cover cursor-zoom-in" onclick="openLightbox(this.src, 'Post image')">

                                    

                                <?php endwhile; ?>

                            </div>

                        <?php endif; ?>

                        <!-- Actions -->

                        
<!-- Actions -->

<div
class="mt-5 flex items-center justify-between border-t pt-4">

    <div class="flex items-center gap-6 text-sm">

        <span class="text-slate-600">

            <i class="fa-regular fa-thumbs-up"></i>

            <?= $likeCount ?>

        </span>

        <span class="text-slate-600">

            <i class="fa-regular fa-comment"></i>

            <?= $commentCount ?>

        </span>

        <a
        href="feed.php#post-<?= $post['id'] ?>"
        class="text-cyan-600">

            <i class="fa-regular fa-eye"></i>

            View Post

        </a>

    </div>

    <div class="flex items-center gap-3">

        <button type="button"
            onclick="toggleSavePost(<?= $post['id'] ?>, this)"
            data-saved="<?= !empty($savedPostIds[$post['id']]) ? '1' : '0' ?>"
            aria-pressed="<?= !empty($savedPostIds[$post['id']]) ? 'true' : 'false' ?>"
            class="h-8 w-8 flex items-center justify-center rounded-full border <?= !empty($savedPostIds[$post['id']]) ? 'border-cyan-100 bg-cyan-50 text-teal-700' : 'border-slate-100 bg-white text-slate-500' ?> hover:bg-cyan-50 transition"
            title="<?= !empty($savedPostIds[$post['id']]) ? 'Unsave Post' : 'Save Post' ?>">

            <i class="<?= !empty($savedPostIds[$post['id']]) ? 'fa-solid' : 'fa-regular' ?> fa-bookmark text-xs"></i>

        </button>

        <a
        href="edit_post.php?id=<?= $post['id'] ?>"
        class="text-slate-500 hover:text-cyan-600">

            <i class="fa-solid fa-pen"></i>

            Edit

        </a>

        <a
        href="delete_post.php?id=<?= $post['id'] ?>"
        onclick="event.preventDefault(); confirmDialog('Delete this post?', function(){ window.location.href='delete_post.php?id=<?= $post['id'] ?>'; }, {title:'Delete Post', confirmText:'Delete'})"
        class="text-slate-500 hover:text-red-500">

            <i class="fa-solid fa-trash"></i>

            Delete

        </a>

    </div>

</div>


                    </div>

                <?php endwhile; ?>

            </div>

        </div>

        <section id="saved" class="tab-content hidden mt-6">
            <div class="rounded-[2rem] border border-cyan-100 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-slate-800">Saved Posts</h2>
                    <span class="badge badge-cyan"><?= $savedTotal ?></span>
                </div>

                <?php if ($savedPosts->num_rows === 0): ?>
                    <?php
                    $es_icon    = 'fa-regular fa-bookmark';
                    $es_title   = 'No saved posts';
                    $es_message = 'Saved posts will appear here after you bookmark them.';
                    include '../include/empty_state.php';
                    ?>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php while ($saved = $savedPosts->fetch_assoc()): ?>
                            <div class="relative rounded-3xl bg-slate-50 p-5">
                                <div class="absolute right-4 top-4">
                                    <button type="button"
                                        onclick="toggleSavePost(<?= $saved['id'] ?>, this)"
                                        data-saved="1"
                                        aria-pressed="true"
                                        class="h-8 w-8 flex items-center justify-center rounded-full border border-cyan-100 bg-cyan-50 text-teal-700 hover:bg-cyan-100 transition"
                                        title="Unsave Post">
                                        <i class="fa-solid fa-bookmark text-xs"></i>
                                    </button>
                                </div>

                                <div class="flex items-center gap-3">
                                    <img src="<?= e(!empty($saved['profile_image']) ? $saved['profile_image'] : '../images/default-avatar.svg') ?>"
                                        class="h-12 w-12 rounded-full object-cover border cursor-zoom-in"
                                        onclick="openLightbox(this.src, '<?= e($saved['name']) ?>')">
                                    <div>
                                        <h3 class="font-bold text-slate-800"><?= e($saved['name']) ?></h3>
                                        <p class="text-xs text-slate-500"><?= date('M d, Y h:i A', strtotime($saved['created_at'])) ?></p>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700"><?= e($saved['category']) ?></span>
                                </div>

                                <div class="mt-3 text-slate-700 leading-7">
                                    <?= nl2br(e($saved['content'])) ?>
                                </div>

                                <?php if (!empty($saved['image'])): ?>
                                    <img src="<?= e($saved['image']) ?>" class="mt-3 h-56 w-full rounded-2xl object-cover cursor-zoom-in" onclick="openLightbox(this.src, 'Post image')">
                                <?php endif; ?>

                                <?php
                                $savedImgStmt = $conn->prepare("SELECT image FROM post_images WHERE post_id = ?");
                                $savedImgStmt->bind_param("i", $saved['id']);
                                $savedImgStmt->execute();
                                $savedImages = $savedImgStmt->get_result();
                                ?>
                                <?php if ($savedImages->num_rows > 0): ?>
                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        <?php while ($savedImg = $savedImages->fetch_assoc()): ?>
                                            <img src="<?= e($savedImg['image']) ?>" class="h-48 w-full rounded-2xl object-cover cursor-zoom-in" onclick="openLightbox(this.src, 'Post image')">
                                        <?php endwhile; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-4 flex items-center gap-4 text-sm text-slate-500">
                                    <a href="feed.php#post-<?= $saved['id'] ?>" class="text-cyan-600 font-semibold">View Post</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if ($savedTotal > 0): ?>
                        <div class="mt-4 flex flex-col items-center gap-2">
                            <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                <span>Showing <strong class="text-slate-700"><?= $savedOffset + 1 ?>&ndash;<?= min($savedOffset + $savedLimit, $savedTotal) ?></strong> of <strong class="text-slate-700"><?= $savedTotal ?></strong></span>
                            </div>
                            <div class="pagination">
                                <?php $savedQs = 'saved_page='; ?>
                                <?php if ($savedPage > 1): ?>
                                    <a href="?<?= $savedQs . ($savedPage - 1) ?>#saved"><i class="fa-solid fa-chevron-left text-xs"></i> Previous</a>
                                <?php else: ?>
                                    <span class="disabled"><i class="fa-solid fa-chevron-left text-xs"></i> Previous</span>
                                <?php endif; ?>
                                <?php $savedStart = max(1, $savedPage - 2); $savedEnd = min($savedTotalPages, $savedPage + 2); ?>
                                <?php for ($i = $savedStart; $i <= $savedEnd; $i++): ?>
                                    <a href="?saved_page=<?= $i ?>#saved" class="<?= $savedPage == $i ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                                <?php if ($savedPage < $savedTotalPages): ?>
                                    <a href="?saved_page=<?= $savedPage + 1 ?>#saved">Next <i class="fa-solid fa-chevron-right text-xs"></i></a>
                                <?php else: ?>
                                    <span class="disabled">Next <i class="fa-solid fa-chevron-right text-xs"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <script>

        function showTab(tabName) {

            document
                .querySelectorAll(".tab-content")
                .forEach(tab => {

                    tab.classList.add("hidden");

                });

            document
                .querySelectorAll(".tab-btn")
                .forEach(btn => {

                    btn.classList.remove(
                        "bg-cyan-100",
                        "text-cyan-700"
                    );

                });

            document
                .getElementById(tabName)
                .classList.remove("hidden");

            document
                .getElementById("btn-" + tabName)
                .classList.add(
                    "bg-cyan-100",
                    "text-cyan-700"
                );

            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '#' + tabName);
            }

        }

        window.addEventListener('DOMContentLoaded', function () {
            var initialTab = (window.location.hash || '#personal').replace('#', '');
            if (!document.getElementById(initialTab)) {
                initialTab = 'personal';
            }
            showTab(initialTab);
        });

    </script>

<?php include "../include/footer.php"; ?>

</body>

</html>

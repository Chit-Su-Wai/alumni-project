<?php
session_start();

require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

function e($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

$current_user_id = (int) $_SESSION["user_id"];
$profile_id = (int) ($_GET["id"] ?? 0);

if ($profile_id <= 0) {
    header("Location: directory.php");
    exit;
}

/* USER POSTS */

$postStmt = $conn->prepare("
    SELECT *
    FROM posts
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$postStmt->bind_param("i", $profile_id);
$postStmt->execute();

$userPosts = $postStmt->get_result();

/* Current login user for navbar */
// $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt = $conn->prepare("
    SELECT u.*, a.graduated_year
    FROM users u
    LEFT JOIN approved_students a
        ON u.approved_id = a.approved_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();

$currentProfileImage = !empty($currentUser["profile_image"])
    ? $currentUser["profile_image"]
    : "../images/default-avatar.svg";

/* Viewed profile user */
// $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt = $conn->prepare("
    SELECT u.*, a.graduated_year
    FROM users u
    LEFT JOIN approved_students a
        ON u.approved_id = a.approved_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user["role"] !== "user") {
    header("Location: directory.php");
    exit;
}

$profileImage = !empty($user["profile_image"])
    ? $user["profile_image"]
    : "../images/default-avatar.svg";

$phone = $user["phone"] ?? "";
$address = $user["address"] ?? "";
$bio = $user["bio"] ?? "";
$graduatedYear = $user["graduated_year"] ?? "";

/* Jobs */
$jobs = [];
$jobStmt = $conn->prepare("SELECT * FROM jobs WHERE user_id = ? ORDER BY start_date DESC, created_at DESC");
$jobStmt->bind_param("i", $profile_id);
$jobStmt->execute();
$jobs = $jobStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* Social count */
$socialFields = [
    "facebook",
    "linkedin",
    "github",
    "telegram",
    "instagram",
    "youtube",
    "tiktok",
    "line_id",
    "viber",
    "whatsapp"
];

$socialCount = 0;
foreach ($socialFields as $field) {
    if (!empty($user[$field])) {
        $socialCount++;
    }
}

/* Profile views */
if (!isset($_SESSION["viewed_profiles"]) || !is_array($_SESSION["viewed_profiles"])) {
    $_SESSION["viewed_profiles"] = [];
}

if ($current_user_id !== $profile_id) {
    if (!in_array($profile_id, $_SESSION["viewed_profiles"], true)) {
        $viewStmt = $conn->prepare("
            INSERT IGNORE INTO profile_views (profile_id, viewer_id, session_id)
            VALUES (?, ?, ?)
        ");
        $viewerSession = session_id();
        $viewStmt->bind_param("iis", $profile_id, $current_user_id, $viewerSession);
        $viewStmt->execute();
        $_SESSION["viewed_profiles"][] = $profile_id;
    }
}

$viewStmt = $conn->prepare("SELECT COUNT(*) AS total FROM profile_views WHERE profile_id = ?");
$viewStmt->bind_param("i", $profile_id);
$viewStmt->execute();
$profileViews = (int) $viewStmt->get_result()->fetch_assoc()["total"];

$socialItems = [
    "facebook" => ["Facebook", "fa-brands fa-facebook"],
    "linkedin" => ["LinkedIn", "fa-brands fa-linkedin"],
    "github" => ["GitHub", "fa-brands fa-github"],
    "telegram" => ["Telegram", "fa-brands fa-telegram"],
    "instagram" => ["Instagram", "fa-brands fa-instagram"],
    "youtube" => ["YouTube", "fa-brands fa-youtube"],
    "tiktok" => ["TikTok", "fa-brands fa-tiktok"],
    "line_id" => ["Line", "fa-brands fa-line"],
    "viber" => ["Viber", "fa-brands fa-viber"],
    "whatsapp" => ["WhatsApp", "fa-brands fa-whatsapp"]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($user["name"]) ?> | Alumni Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-white via-cyan-50 to-teal-50 text-slate-800">

    <?php include "../include/user_header.php"; ?>

    <main class="mx-auto max-w-5xl px-4 py-6">

        <section class="overflow-hidden rounded-[2rem] border border-cyan-100 bg-white shadow-lg">
            <div class="h-44 bg-gradient-to-r from-cyan-400 via-teal-400 to-teal-600"></div>

            <div class="relative px-6 pb-6">
                <div class="-mt-16 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
                        <img src="<?= e($profileImage) ?>" alt="<?= e($user["name"]) ?>"
                            class="h-32 w-32 rounded-full border-4 border-white bg-white object-cover shadow-lg">

                        <div>
                            <h1 class="text-3xl font-black text-slate-900">
                                <?= e($user["name"]) ?>
                            </h1>

                            <p class="text-sm font-semibold text-slate-500">
                                <?= e($user["email"]) ?>
                            </p>

                            <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                                <span class="rounded-full bg-cyan-50 px-4 py-2 text-xs font-bold text-teal-700">
                                    <i class="fa-solid fa-user-graduate mr-1"></i>
                                    <?= $graduatedYear ? "Graduated Year " . e($graduatedYear) : "Graduated Year Not Added" ?>
                                </span>

                                <span class="rounded-full bg-cyan-50 px-4 py-2 text-xs font-bold text-teal-700">
                                    <i class="fa-solid fa-briefcase mr-1"></i>
                                    <?= count($jobs) ?> Experience
                                </span>

                                <span class="rounded-full bg-cyan-50 px-4 py-2 text-xs font-bold text-teal-700">
                                    <i class="fa-solid fa-share-nodes mr-1"></i>
                                    <?= $socialCount ?> Social
                                </span>

                                <span class="rounded-full bg-cyan-50 px-4 py-2 text-xs font-bold text-teal-700">
                                    <i class="fa-solid fa-eye mr-1"></i>
                                    <?= $profileViews ?> Views
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-center gap-2">
                        <?php if (!empty($user["email"])): ?>
                            <a href="mailto:<?= e($user["email"]) ?>"
                                class="rounded-full bg-blue-500 px-5 py-3 text-sm font-black text-white shadow hover:bg-blue-600">
                                <i class="fa-solid fa-envelope mr-1"></i> Mail
                            </a>
                        <?php endif; ?>

                        <?php if ($phone): ?>
                            <a href="tel:<?= e($phone) ?>"
                                class="rounded-full bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200">
                                <i class="fa-solid fa-phone mr-1"></i> Call
                            </a>
                        <?php endif; ?>


                    </div>
                </div>

                <?php if ($current_user_id === $profile_id): ?>
                <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                    <a href="edit_profile.php"
                        class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-3 text-sm font-black text-white shadow hover:opacity-90">
                        <i class="fa-solid fa-pen mr-1"></i> Edit Profile
                    </a>
                </div>
                <?php endif; ?>

                <div class="mt-6 border-t border-slate-100 pt-4">
                    <div class="flex flex-wrap gap-3 text-sm font-bold">
                        <button onclick="showTab('about')" id="btn-about"
                            class="tab-btn rounded-full bg-cyan-100 px-4 py-2 text-teal-700">
                            About & Contact
                        </button>
                        <button onclick="showTab('experience')" id="btn-experience"
                            class="tab-btn rounded-full px-4 py-2 text-slate-600 hover:bg-cyan-50">
                            Experience
                        </button>
                        <button onclick="showTab('social')" id="btn-social"
                            class="tab-btn rounded-full px-4 py-2 text-slate-600 hover:bg-cyan-50">
                            Social Media
                        </button>
                        <button onclick="showTab('posts')" id="btn-posts"
                            class="tab-btn rounded-full px-4 py-2 text-slate-600 hover:bg-cyan-50">

                            Posts

                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="tab-content mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-[2rem] border border-cyan-100 bg-white p-6 shadow-sm">
                <h2 class="mb-5 text-lg font-black text-slate-800">About</h2>

                <div class="space-y-4">
                    <div>
                        <p class="mb-1 text-xs font-black uppercase text-slate-400">Name</p>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 font-semibold">
                            <?= e($user["name"]) ?>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1 text-xs font-black uppercase text-slate-400">Email</p>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 font-semibold">
                            <a href="mailto:<?= e($user["email"]) ?>" class="text-teal-700 hover:underline">
                                <?= e($user["email"]) ?>
                            </a>
                        </div>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-black uppercase text-slate-400">Graduated Year</p>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 font-semibold">
                            <?= $graduatedYear ? e($graduatedYear) : "Not added yet" ?>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1 text-xs font-black uppercase text-slate-400">Bio</p>
                        <div class="min-h-24 rounded-2xl bg-slate-50 px-4 py-3 font-semibold">
                            <?= $bio ? nl2br(e($bio)) : "No bio added yet" ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-cyan-100 bg-white p-6 shadow-sm">
                <h2 class="mb-5 text-lg font-black text-slate-800">Contact</h2>

                <div class="space-y-4">
                    <div>
                        <p class="mb-1 text-xs font-black uppercase text-slate-400">Phone</p>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 font-semibold">
                            <?= $phone ? e($phone) : "Not added yet" ?>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1 text-xs font-black uppercase text-slate-400">Address</p>
                        <div class="min-h-24 rounded-2xl bg-slate-50 px-4 py-3 font-semibold">
                            <?= $address ? e($address) : "Not added yet" ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <?php if (!empty($user["email"])): ?>
                            <a href="mailto:<?= e($user["email"]) ?>"
                                class="rounded-2xl bg-blue-500 px-4 py-3 text-center text-sm font-black text-white">
                                <i class="fa-solid fa-envelope mr-1"></i> Mail
                            </a>
                        <?php endif; ?>

                        <?php if ($phone): ?>
                            <a href="tel:<?= e($phone) ?>"
                                class="rounded-2xl bg-slate-100 px-4 py-3 text-center text-sm font-black text-slate-700">
                                <i class="fa-solid fa-phone mr-1"></i> Call
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section id="experience" class="tab-content mt-6 hidden">
            <div class="rounded-[2rem] border border-cyan-100 bg-white p-6 shadow-sm">
                <h2 class="mb-5 text-lg font-black text-slate-800">Experience</h2>

                <?php if (count($jobs) === 0): ?>
                    <div class="rounded-2xl bg-slate-50 p-6 text-center text-sm font-semibold text-slate-500">
                        No experience added yet.
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($jobs as $job): ?>
                            <div class="rounded-3xl border border-slate-100 bg-slate-50 p-5">
                                <h3 class="text-lg font-black text-teal-800">
                                    <?= e($job["position"] ?? "") ?>
                                </h3>

                                <p class="mt-1 font-semibold text-slate-700">
                                    <?= e($job["company"] ?? "") ?>
                                </p>

                                <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                                    <p><b>Type:</b> <?= e($job["job_type"] ?? "-") ?></p>
                                    <p><b>Location:</b> <?= e($job["location"] ?? "-") ?></p>
                                    <p><b>Salary:</b> <?= e($job["salary"] ?? "-") ?></p>
                                    <p><b>Experience Year:</b> <?= e($job["experience_year"] ?? "-") ?></p>
                                    <p class="sm:col-span-2">
                                        <b>Date:</b>
                                        <?= e($job["start_date"] ?? "-") ?>
                                        -
                                        <?= !empty($job["end_date"]) ? e($job["end_date"]) : "Current" ?>
                                    </p>

                                    <?php if (!empty($job["phone"])): ?>
                                        <p><b>Phone:</b> <?= e($job["phone"]) ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($job["email"])): ?>
                                        <p><b>Email:</b> <?= e($job["email"]) ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($job["website"])): ?>
                                        <p class="sm:col-span-2">
                                            <b>Website:</b>
                                            <a href="<?= e($job["website"]) ?>" target="_blank" class="text-teal-700 underline">
                                                <?= e($job["website"]) ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($job["description"])): ?>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        <?= nl2br(e($job["description"])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="social" class="tab-content mt-6 hidden">
            <div class="rounded-[2rem] border border-cyan-100 bg-white p-6 shadow-sm">
                <h2 class="mb-5 text-lg font-black text-slate-800">Social Media</h2>

                <?php if ($socialCount === 0): ?>
                    <div class="rounded-2xl bg-slate-50 p-6 text-center text-sm font-semibold text-slate-500">
                        No social links added yet.
                    </div>
                <?php else: ?>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <?php foreach ($socialItems as $key => $item): ?>
                            <?php if (!empty($user[$key])): ?>
                                <a href="<?= e($user[$key]) ?>" target="_blank"
                                    class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 font-bold text-slate-700 hover:bg-cyan-50 hover:text-teal-700">
                                    <i class="<?= e($item[1]) ?> text-lg"></i>
                                    <span><?= e($item[0]) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <section id="posts" class="tab-content hidden mt-6">

            <?php if ($userPosts->num_rows == 0): ?>

                <div class="rounded-3xl bg-slate-50 p-10 text-center">

                    <i class="fa-regular fa-file-lines text-5xl text-slate-300"></i>

                    <p class="mt-4 text-slate-500">

                        No Posts Yet

                    </p>

                </div>

            <?php endif; ?>

            <?php while ($post = $userPosts->fetch_assoc()): ?>


                <div class="bg-white rounded-3xl p-5 shadow-sm mb-5">

                    <div class="flex items-center gap-3">

                        <img src="<?= e($profileImage) ?>" class="h-12 w-12 rounded-full object-cover border">

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

                    <div class="mt-4">

                        <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs text-cyan-700">

                            <?= e($post['category']) ?>

                        </span>

                    </div>

                    <div class="mt-4 text-slate-700 leading-7">

                        <?= nl2br(e($post['content'])) ?>

                    </div>

                    <?php

                    $imgStmt = $conn->prepare("
        SELECT image
        FROM post_images
        WHERE post_id = ?
    ");

                    $imgStmt->bind_param(
                        "i",
                        $post['id']
                    );

                    $imgStmt->execute();

                    $images = $imgStmt->get_result();

                    ?>

                    <?php if ($images->num_rows > 0): ?>

                        <div class="grid grid-cols-2 gap-2 mt-4">

                            <?php while ($img = $images->fetch_assoc()): ?>

                                <img src="<?= e($img['image']) ?>" class="h-56 w-full rounded-2xl object-cover">

                            <?php endwhile; ?>

                        </div>

                    <?php endif; ?>

                </div>

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

                $commentStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM comments
    WHERE post_id = ?
");

                $commentStmt->bind_param("i", $post['id']);
                $commentStmt->execute();

                $commentCount =
                    $commentStmt->get_result()
                        ->fetch_assoc()['total'];

                ?>

                <div class="mt-4 border-t pt-4 flex items-center gap-6 text-sm">

                    <span class="text-slate-600">
                        👍 <?= $likeCount ?> Likes
                    </span>

                    <span class="text-slate-600">
                        💬 <?= $commentCount ?> Comments
                    </span>

                    <a href="feed.php?post_id=<?= $post['id'] ?>" class="text-cyan-600 font-semibold">

                        View Post

                    </a>

                </div>

            <?php endwhile; ?>

        </section>

    </main>

    <script>
        const menuBtn = document.getElementById("menuBtn");
        const mobileMenu = document.getElementById("mobileMenu");

        menuBtn?.addEventListener("click", () => {
            mobileMenu.classList.toggle("hidden");
        });

        function showTab(tabName) {
            document.querySelectorAll(".tab-content").forEach(tab => {
                tab.classList.add("hidden");
            });

            document.querySelectorAll(".tab-btn").forEach(btn => {
                btn.classList.remove("bg-cyan-100", "text-teal-700");
                btn.classList.add("text-slate-600", "hover:bg-cyan-50");
            });

            document.getElementById(tabName).classList.remove("hidden");

            const activeBtn = document.getElementById(`btn-${tabName}`);
            if (activeBtn) {
                activeBtn.classList.add("bg-cyan-100", "text-teal-700");
                activeBtn.classList.remove("text-slate-600", "hover:bg-cyan-50");
            }
        }
    </script>

<?php include "../include/footer.php"; ?>

</body>

</html>
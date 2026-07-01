<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/db.php";

$currentUserId = $_SESSION['user_id'] ?? 0;

$currentProfile = "images/default-avatar.svg";
$unread_count = 0;

if ($currentUserId) {

    $stmt = $conn->prepare("
        SELECT profile_image
        FROM users
        WHERE id = ?
    ");

    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        if (!empty($row['profile_image'])) {

            $currentProfile = str_replace(
                "../",
                "",
                $row['profile_image']
            );

        }

    }

    $msgStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM messages
        WHERE receiver_id = ?
        AND is_read = 0
    ");

    $msgStmt->bind_param("i", $currentUserId);
    $msgStmt->execute();

    $msgResult = $msgStmt->get_result();
    $msgRow = $msgResult->fetch_assoc();

    $unread_count = $msgRow['total'] ?? 0;
}
?>

<header class="sticky top-0 z-50 px-3 py-4">

    <nav
        class="mx-auto flex max-w-7xl items-center justify-between rounded-full border border-cyan-100 bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 px-6 py-3 shadow-md">

        <!-- Logo -->

        <a href="profile.php" class="flex shrink-0 items-center gap-3">

            <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white">

                AN

            </div>

            <span class="hidden font-bold text-teal-700 sm:inline" data-t="alumni_network">
                Alumni Network
            </span>

        </a>

        <!-- Desktop Menu -->

        <div class="hidden items-center gap-1 lg:flex xl:gap-2">

            <a href="feed.php" class="rounded-full px-3 py-2 text-sm font-bold text-teal-700 hover:bg-white/70">
                <i class="fa-regular fa-newspaper mr-1"></i>
                <span data-t="feed">Posts</span>
            </a>

            <a href="job.php" class="rounded-full px-3 py-2 text-sm font-bold text-teal-700 hover:bg-white/70">
                <i class="fa-solid fa-briefcase mr-1"></i>
                <span data-t="jobs">Jobs</span>
            </a>

            <a href="directory.php" class="rounded-full px-3 py-2 text-sm font-bold text-teal-700 hover:bg-white/70">
                <i class="fa-solid fa-users mr-1"></i>
                <span data-t="directory">Directory</span>
            </a>

            <a href="message.php" class="rounded-full px-3 py-2 text-sm font-bold text-teal-700 hover:bg-white/70">
                <i class="fa-regular fa-comment mr-1"></i>
                <span data-t="messages">Messages</span>
            </a>

            <a href="contact.php" class="rounded-full px-3 py-2 text-sm font-bold text-teal-700 hover:bg-white/70">
                <i class="fa-regular fa-envelope mr-1"></i>
                <span data-t="contact">Contact</span>
            </a>

        </div>

        <!-- Right Side -->

        <div class="hidden items-center gap-3 lg:flex">

            <!-- Notification -->

            <div class="relative">

                <a href="message.php"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-teal-700 shadow-sm">

                    <i class="fa-regular fa-bell"></i>

                </a>

                <?php if ($unread_count > 0): ?>

                    <span class="absolute -top-1 -right-1 rounded-full bg-red-500 px-2 text-xs text-white">

                        <?= $unread_count ?>

                    </span>

                <?php endif; ?>

            </div>



            <!-- Theme Toggle -->

            <button onclick="toggleTheme()"
                class="theme-toggle flex h-9 w-9 items-center justify-center rounded-full bg-white text-sm shadow-sm hover:bg-teal-50 transition">

                <i class="fa-solid fa-moon"></i>

            </button>

            <!-- Profile -->

            <a href="profile.php" class="h-9 w-9 overflow-hidden rounded-full border-2 border-teal-500 shadow-sm">

                <img src="/alumni/<?= htmlspecialchars($currentProfile) ?>" alt="Profile"
                    class="h-full w-full object-cover">
            </a>

            <!-- Logout -->

            <a href="logout.php"
                class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-4 py-2 text-sm font-bold text-white shadow-md">

                <span data-t="logout">Logout</span>

            </a>

        </div>

        <!-- Mobile Menu Button -->

        <button id="menuBtn"
            class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 text-xl font-black text-white lg:hidden">

            ☰

        </button>

    </nav>

    <!-- Mobile Menu -->

    <div id="mobileMenu"
        class="mx-auto mt-3 hidden max-w-7xl rounded-3xl border border-cyan-100 bg-white p-3 shadow-xl lg:hidden">

        <a href="feed.php" class="block rounded-xl px-4 py-2.5 font-semibold text-slate-700 hover:bg-cyan-50">
            <span data-t="feed">Posts</span>
        </a>

        <a href="job.php" class="block rounded-xl px-4 py-2.5 font-semibold text-slate-700 hover:bg-cyan-50">
            <span data-t="jobs">Jobs</span>
        </a>

        <a href="directory.php" class="block rounded-xl px-4 py-2.5 font-semibold text-slate-700 hover:bg-cyan-50">
            <span data-t="directory">Directory</span>
        </a>

        <a href="message.php" class="block rounded-xl px-4 py-2.5 font-semibold text-slate-700 hover:bg-cyan-50">
            <span data-t="messages">Messages</span>
        </a>

        <a href="contact.php" class="block rounded-xl px-4 py-2.5 font-semibold text-slate-700 hover:bg-cyan-50">
            <span data-t="contact">Contact</span>
        </a>

        <hr class="my-2 border-slate-100">

        <!-- Mobile Theme Toggle -->

        <div class="px-4 py-2.5">

            <button onclick="toggleTheme()" class="theme-toggle flex w-full items-center justify-center gap-2 rounded-lg border bg-white px-3 py-2 text-sm font-semibold">

                <i class="fa-solid fa-moon"></i> <span>Theme</span>

            </button>

        </div>

        <hr class="my-2 border-slate-100">

        <a href="profile.php" class="block rounded-xl px-4 py-2.5 font-semibold text-slate-700 hover:bg-cyan-50">
            <span data-t="my_profile">My Profile</span>
        </a>

        <a href="logout.php"
            class="mt-2 block rounded-xl bg-gradient-to-r from-cyan-400 to-teal-500 px-4 py-2.5 text-center font-semibold text-white">
            <span data-t="logout">Logout</span>
        </a>

    </div>

</header>

<script>
document.addEventListener("DOMContentLoaded", function () {

    var menuBtn = document.getElementById("menuBtn");
    var mobileMenu = document.getElementById("mobileMenu");

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener("click", function () {
            mobileMenu.classList.toggle("hidden");
        });
    }

});
</script>

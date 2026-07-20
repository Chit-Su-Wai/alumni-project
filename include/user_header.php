<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/db.php";

$currentUserId = $_SESSION['user_id'] ?? 0;
$currentUserName = $_SESSION['user_name'] ?? 'User';

$currentProfile = "../images/default-avatar.svg";
$unread_count = 0;
$notifCount = 0;

if ($currentUserId) {

    $stmt = $conn->prepare("
        SELECT name, profile_image
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (!empty($row['name'])) {
                $currentUserName = $row['name'];
            }

            if (!empty($row['profile_image'])) {
                $currentProfile = $row['profile_image'];
            }
        }
    }

    $msgStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM messages m
        INNER JOIN users u ON u.id = m.sender_id
        WHERE m.receiver_id = ?
        AND m.is_read = 0
    ");

    $msgStmt->bind_param("i", $currentUserId);
    $msgStmt->execute();

    $msgResult = $msgStmt->get_result();
    $msgRow = $msgResult->fetch_assoc();

    $unread_count = $msgRow['total'] ?? 0;

    $notifStmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0 AND type = 'message'");
    if ($notifStmt) {
        $notifStmt->bind_param("i", $currentUserId);
        $notifStmt->execute();
        $notifCount = (int) $notifStmt->get_result()->fetch_assoc()['total'];
    }
}
?>

<!--
    Self-contained mobile overlay menu styles.
    These were previously only provided by include/theme.css. When that
    stylesheet was not applied, the menu rendered as a plain block in normal
    flow and was visible on page load (pushing content down). Inlining the
    rules here guarantees the menu starts hidden and animates as an overlay
    regardless of whether theme.css is loaded.
-->
<style>
    .mobile-menu-backdrop {
        display: none;
        position: fixed;
        top: 5rem;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 55;
        background: rgba(15, 23, 42, 0.38);
        -webkit-backdrop-filter: blur(2px);
        backdrop-filter: blur(2px);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .mobile-menu-backdrop.active {
        display: block;
        opacity: 1;
    }

    .mobile-menu-overlay {
        position: fixed;
        left: 0;
        right: 0;
        top: 5rem;
        z-index: 60;
        width: 100%;
        max-height: calc(100vh - 5rem);
        overflow-y: auto;
        padding-top: 0;
        margin-top: 0;
        border-top: none;
        transform: translateY(-100%);
        opacity: 0;
        visibility: hidden;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                    opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                    visibility 0.3s;
        pointer-events: none;
    }
    .mobile-menu-overlay.active {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    body.mobile-menu-open {
        overflow: hidden !important;
    }

    .menu-btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        line-height: 1;
        transition: transform 0.2s ease;
    }
    .menu-btn-icon.is-open {
        transform: rotate(90deg);
    }

    @media (min-width: 1024px) {
        .mobile-menu-overlay.lg-hide,
        .mobile-menu-backdrop.lg-hide {
            display: none !important;
        }
    }
    @media (min-width: 768px) {
        .mobile-menu-overlay.md-hide,
        .mobile-menu-backdrop.md-hide {
            display: none !important;
        }
    }
</style>

<header class="sticky top-0 z-50 px-3 py-4">

    <nav
        class="mx-auto flex max-w-7xl items-center justify-between rounded-full border border-cyan-100 bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 px-6 py-3 shadow-md">

        <!-- Logo -->

        <a href="profile.php" class="flex shrink-0 items-center gap-3">

            <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white">

                AN

            </div>

            <span class="font-bold text-teal-700" data-t="alumni_network">
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

            <a href="announcements.php" class="rounded-full px-3 py-2 text-sm font-bold text-teal-700 hover:bg-white/70">
                <i class="fa-solid fa-bullhorn mr-1"></i>
                <span data-t="announcements">Announcements</span>
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

            <!-- Notification Bell with Dropdown -->

            <div class="relative" id="notifBellContainer">

                <button onclick="toggleNotifDropdown()"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-teal-700 shadow-sm hover:bg-teal-50 transition">

                    <i class="fa-regular fa-bell"></i>

                </button>

                <span id="notifBadge"
                    class="absolute -top-1 -right-1 rounded-full bg-red-500 px-[6px] text-[10px] leading-4 text-white font-bold <?= $notifCount > 0 ? '' : 'hidden' ?>">

                    <?= min($notifCount, 9) ?><?= $notifCount > 9 ? '+' : '' ?>

                </span>

                <!-- Dropdown -->
                <div id="notifDropdown"
                    class="hidden absolute right-0 top-12 w-80 rounded-2xl bg-white border border-cyan-100 shadow-xl z-50 overflow-hidden">

                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-bold text-sm text-slate-800">Notifications</h3>
                        <button onclick="markAllNotifRead()" class="text-xs font-semibold text-cyan-600 hover:text-cyan-700">Mark all read</button>
                    </div>

                    <div id="notifList" class="max-h-80 overflow-y-auto">
                        <div class="flex items-center justify-center py-8 text-sm text-slate-400">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
                        </div>
                    </div>

                </div>

            </div>



            <!-- Theme Toggle -->

            <button onclick="toggleTheme()"
                class="theme-toggle flex h-9 w-9 items-center justify-center rounded-full bg-white text-sm shadow-sm hover:bg-teal-50 transition">

                <i class="fa-solid fa-moon"></i>

            </button>

            <!-- Profile -->

            <a href="profile.php" class="h-9 w-9 overflow-hidden rounded-full border-2 border-teal-500 shadow-sm transition hover:scale-105">

                <img src="<?= htmlspecialchars($currentProfile) ?>" alt="<?= htmlspecialchars($currentUserName) ?>"
                    class="h-full w-full object-cover">
            </a>

            <!-- Logout -->

            <a href="logout.php"
                class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-4 py-2 text-sm font-bold text-white shadow-md">

                <span data-t="logout">Logout</span>

            </a>

        </div>

        <!-- Mobile Menu Button -->

        <button id="menuBtn" type="button" onclick="toggleMobileMenu()" aria-label="Open menu" aria-expanded="false"
            class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 text-xl font-black text-white lg:hidden">

            <span id="menuBtnIcon" class="menu-btn-icon">☰</span>

        </button>

    </nav>

</header>

<!-- Mobile Menu Backdrop -->
<div id="mobileBackdrop" class="mobile-menu-backdrop lg-hide" onclick="closeMobileMenu()"></div>

<!-- Mobile Menu Overlay -->
<div id="mobileMenu"
    class="mobile-menu-overlay lg-hide rounded-b-3xl border-b border-x border-cyan-100 bg-gradient-to-b from-cyan-50 via-cyan-100 to-teal-100 p-3 shadow-xl lg:hidden">

    <div class="space-y-1">

        <a href="feed.php" class="flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-white/70">
            <i class="fa-regular fa-newspaper"></i>
            <span data-t="feed">Posts</span>
        </a>

        <a href="job.php" class="flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-white/70">
            <i class="fa-solid fa-briefcase"></i>
            <span data-t="jobs">Jobs</span>
        </a>

        <a href="directory.php" class="flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-white/70">
            <i class="fa-solid fa-users"></i>
            <span data-t="directory">Directory</span>
        </a>

        <a href="announcements.php" class="flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-white/70">
            <i class="fa-solid fa-bullhorn"></i>
            <span data-t="announcements">Announcements</span>
        </a>

        <a href="message.php" class="flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-white/70">
            <i class="fa-regular fa-comment"></i>
            <span data-t="messages">Messages</span>
        </a>

        <a href="contact.php" class="flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-white/70">
            <i class="fa-regular fa-envelope"></i>
            <span data-t="contact">Contact</span>
        </a>

    </div>

    <hr class="my-2 border-slate-100">

    <!-- Mobile Theme Toggle -->

    <div class="px-4 py-2.5">

        <button onclick="toggleTheme()" class="theme-toggle flex w-full items-center justify-center gap-2 rounded-2xl border border-cyan-100 bg-white/90 px-4 py-3 text-sm font-semibold text-teal-700 shadow-sm transition hover:bg-white">

            <i class="fa-solid fa-moon"></i> <span>Theme</span>

        </button>

    </div>

    <hr class="my-2 border-slate-100">

    <a href="profile.php" class="flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-white/70">
        <i class="fa-regular fa-user"></i>
        <span data-t="my_profile">My Profile</span>
    </a>

    <a href="logout.php"
        class="mt-2 flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-4 py-2.5 text-sm font-bold text-white shadow-md">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span data-t="logout">Logout</span>
    </a>

</div>

<script>
function toggleMobileMenu() {
    var menu = document.getElementById("mobileMenu");
    var backdrop = document.getElementById("mobileBackdrop");
    var btnIcon = document.getElementById("menuBtnIcon");
    var menuBtn = document.getElementById("menuBtn");

    if (!menu) return;

    var isOpen = menu.classList.contains("active");

    if (isOpen) {
        closeMobileMenu();
    } else {
        menu.classList.add("active");
        if (backdrop) backdrop.classList.add("active");
        if (btnIcon) {
            btnIcon.textContent = "✕";
            btnIcon.classList.add("is-open");
        }
        if (menuBtn) {
            menuBtn.setAttribute("aria-expanded", "true");
            menuBtn.setAttribute("aria-label", "Close menu");
        }
        document.body.classList.add("mobile-menu-open");
    }
}

function closeMobileMenu() {
    var menu = document.getElementById("mobileMenu");
    var backdrop = document.getElementById("mobileBackdrop");
    var btnIcon = document.getElementById("menuBtnIcon");

    if (menu) menu.classList.remove("active");
    if (backdrop) backdrop.classList.remove("active");
    if (btnIcon) {
        btnIcon.textContent = "☰";
        btnIcon.classList.remove("is-open");
    }
    var menuBtn = document.getElementById("menuBtn");
    if (menuBtn) {
        menuBtn.setAttribute("aria-expanded", "false");
        menuBtn.setAttribute("aria-label", "Open menu");
    }
    document.body.classList.remove("mobile-menu-open");
}

document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        closeMobileMenu();
    }
});

// Close menu on window resize to desktop
window.addEventListener("resize", function () {
    if (window.innerWidth >= 1024) {
        closeMobileMenu();
    }
});
document.addEventListener("DOMContentLoaded", function () {
    closeMobileMenu();
});
</script>

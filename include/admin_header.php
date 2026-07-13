<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adminHeaderUser = null;
$adminHeaderImage = '../images/default-avatar.svg';

if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
    $adminHeaderStmt = $conn->prepare("SELECT name, profile_image FROM users WHERE id = ? LIMIT 1");
    $adminHeaderStmt->bind_param('i', $_SESSION['user_id']);
    $adminHeaderStmt->execute();
    $adminHeaderUser = $adminHeaderStmt->get_result()->fetch_assoc();

    if (!empty($adminHeaderUser['profile_image'])) {
        $adminHeaderImage = $adminHeaderUser['profile_image'];
    }
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
/* 
  Since the parent dashboard wraps this in <div class="flex min-h-screen">
  we must force flex-col on mobile so the topbar sits above the main content,
  and flex-row on desktop so the sidebar sits beside it.
*/
@media (max-width: 767px) {
    body > .flex {
        flex-direction: column !important;
    }
}
</style>

<!-- Mobile Header (Hidden on Desktop) -->
<!-- Mobile Header -->
<div class="flex md:hidden items-center justify-between bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 border-b border-cyan-200 p-4 w-full sticky top-0 z-10">

    <div class="flex items-center gap-3">

        <!-- Menu -->
        <button id="mobileMenuBtn"
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-50 text-teal-700 hover:bg-cyan-100 transition shadow-sm">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Logo -->
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white">
            AN
        </div>

        <!-- Title -->
        <div>
            <h2 class="text-lg font-bold text-teal-700" data-t="admin_panel">
                Admin Panel
            </h2>
        </div>

    </div>

    <!-- Theme -->
    

</div>
<div id="sidebarOverlay"
     class="fixed inset-0 hidden bg-black/40 z-40 md:hidden"></div>
<!-- Sidebar -->
<aside id="adminSidebar"
class="fixed top-0 left-0 z-50
h-screen w-64
-transform -translate-x-full
transition-transform duration-300 ease-in-out
md:static md:translate-x-0
bg-gradient-to-b from-cyan-50 via-cyan-100 to-teal-100
md:border-r border-b md:border-b-0 border-cyan-200
text-slate-800
md:sticky md:top-0
md:h-screen
md:flex md:flex-col">
    <div class="flex md:hidden items-center justify-between px-4 pt-4">
        <h2 class="text-lg font-bold text-teal-700" data-t="admin_panel">Admin Panel</h2>
        <button id="adminSidebarCloseBtn"
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-50 text-teal-700 hover:bg-cyan-100 transition shadow-sm"
            aria-label="Close sidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="hidden md:flex p-6 border-b border-cyan-200 items-center justify-between">
        <a href="dashboard.php" class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white">
                AN
            </div>
            <div>
                <h2 class="text-lg font-bold text-teal-700" data-t="admin_panel">Admin Panel</h2>
                <p class="text-xs text-slate-500" data-t="alumni_network">Alumni Network</p>
            </div>
        </a>

    </div>

    <nav class="flex flex-col flex-1 p-4">
    <div class="space-y-1">

        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-gauge-high w-5 text-center text-teal-600"></i>
            <span data-t="dashboard">Dashboard</span>
        </a>

        <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-user-gear w-5 text-center text-teal-600"></i>
            <span>Profile</span>
        </a>

        <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-users w-5 text-center text-teal-600"></i>
            <span data-t="alumni">Alumni</span>
        </a>

        <a href="posts.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-regular fa-newspaper w-5 text-center text-teal-600"></i>
            <span data-t="posts">Posts</span>
        </a>

        <a href="jobs.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-briefcase w-5 text-center text-teal-600"></i>
            <span data-t="jobs">Jobs</span>
        </a>

        <a href="contacts.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-regular fa-envelope w-5 text-center text-teal-600"></i>
            <span data-t="contacts">Contacts</span>
        </a>

        <a href="approved_ids.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-id-card w-5 text-center text-teal-600"></i>
            <span data-t="approved_ids">Approved IDs</span>
        </a>

        <a href="announcements.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-bullhorn w-5 text-center text-teal-600"></i>
            <span data-t="announcements">Announcements</span>
        </a>

        <a href="report.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
    <i class="fa-solid fa-chart-bar w-5 text-center text-teal-600"></i>
    <span data-t="reports">Reports</span>
</a>

<button
    type="button"
    onclick="toggleTheme()"
    class="theme-toggle flex items-center gap-3 w-full px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
    <i class="fa-solid fa-moon w-5 text-center text-teal-600"></i>
    <span>Dark / Light Mode</span>
</button>
    </div>

    <!-- Logout Bottom -->
    <div class="mt-auto pt-4 border-t border-cyan-200">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-red-600 transition">
            <i class="fa-solid fa-right-from-bracket w-5 text-center text-red-500"></i>
            <span data-t="logout">Logout</span>
        </a>
    </div>
</nav>
    <!-- <nav class="p-4 space-y-1 md:flex-1 md:flex md:flex-col">
        <div class="flex-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-gauge-high w-5 text-center text-teal-600"></i>
            <span data-t="dashboard">Dashboard</span>
        </a>
        <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-users w-5 text-center text-teal-600"></i>
            <span data-t="alumni">Alumni</span>
        </a>
        <a href="posts.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-regular fa-newspaper w-5 text-center text-teal-600"></i>
            <span data-t="posts">Posts</span>
        </a>
        <a href="jobs.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-briefcase w-5 text-center text-teal-600"></i>
            <span data-t="jobs">Jobs</span>
        </a>
        <a href="contacts.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-regular fa-envelope w-5 text-center text-teal-600"></i>
            <span data-t="contacts">Contacts</span>
        </a>
        <a href="approved_ids.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-id-card w-5 text-center text-teal-600"></i>
            <span data-t="approved_ids">Approved IDs</span>
        </a>
        <a href="report.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700 transition">
            <i class="fa-solid fa-chart-bar w-5 text-center text-teal-600"></i>
            <span data-t="reports">Reports</span>
        </a>
        </div>

        <div class="pt-4 mt-4 border-t border-cyan-200 md:mt-auto">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-red-600 transition">
                <i class="fa-solid fa-right-from-bracket w-5 text-center text-red-500"></i>
                <span data-t="logout">Logout</span>
            </a>
        </div>
    </nav> -->
</aside>

<script>
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const adminSidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
const adminSidebarCloseBtn = document.getElementById('adminSidebarCloseBtn');

function closeSidebar() {
    adminSidebar.classList.add('-translate-x-full');
    adminSidebar.classList.remove('translate-x-0');
    overlay.classList.add('hidden');
}

mobileMenuBtn.addEventListener('click', () => {
    adminSidebar.classList.toggle('-translate-x-full');
    adminSidebar.classList.toggle('translate-x-0');
    overlay.classList.toggle('hidden');
});

if (adminSidebarCloseBtn) {
    adminSidebarCloseBtn.addEventListener('click', closeSidebar);
}

overlay.addEventListener('click', closeSidebar);
</script>

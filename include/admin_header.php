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
<div class="flex md:hidden items-center justify-between bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 border-b border-cyan-200 p-4 w-full">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white">
            AN
        </div>
        <div>
            <h2 class="text-lg font-bold text-teal-700" data-t="admin_panel">Admin Panel</h2>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <!-- Theme Toggle Mobile -->
        <button onclick="toggleTheme()" class="theme-toggle flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-200 bg-white text-teal-700 shadow-sm hover:bg-cyan-50 transition">
            <i class="fa-solid fa-moon"></i>
        </button>
        <!-- Mobile Menu Toggle -->
        <button id="mobileMenuBtn" class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-50 text-teal-700 font-bold hover:bg-cyan-100 transition shadow-sm">
            ☰
        </button>
    </div>
</div>

<!-- Sidebar -->
<aside id="adminSidebar" class="max-h-0 md:max-h-none overflow-hidden md:overflow-visible transition-[max-height] duration-300 ease-in-out w-full md:w-64 bg-gradient-to-b from-cyan-50 via-cyan-100 to-teal-100 md:border-r border-b md:border-b-0 border-cyan-200 text-slate-800">
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
        <!-- Theme Toggle Desktop (Top Right) -->
        <button onclick="toggleTheme()" class="theme-toggle hidden md:flex h-9 w-9 items-center justify-center rounded-xl border border-cyan-200 bg-white text-teal-700 shadow-sm hover:bg-cyan-50 transition">
            <i class="fa-solid fa-moon text-sm"></i>
        </button>
    </div>
    <nav class="p-4 space-y-1">
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

        <div class="pt-4 mt-4 border-t border-cyan-200">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-slate-700 hover:bg-white/70 hover:text-red-600 transition">
                <i class="fa-solid fa-right-from-bracket w-5 text-center text-red-500"></i>
                <span data-t="logout">Logout</span>
            </a>
        </div>
    </nav>
</aside>

<script>
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const adminSidebar = document.getElementById('adminSidebar');
    if (mobileMenuBtn && adminSidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            adminSidebar.classList.toggle('max-h-0');
            adminSidebar.classList.toggle('max-h-[1000px]');
        });
    }
</script>
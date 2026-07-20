<?php
/**
 * Shared public navbar for homepage and auth pages.
 *
 * Expected variables:
 *   $navVariant — 'home' | 'auth' (default: 'auth')
 *   $navShowRegister — bool (default: true on home/login, false otherwise)
 *   $navShowLogin — bool (default: true)
 *   $navShowHome — bool (default: true on auth pages, false on home)
 */
$navVariant = $navVariant ?? 'auth';
$navShowHome = $navShowHome ?? ($navVariant !== 'home');
$navShowRegister = $navShowRegister ?? ($navVariant === 'home' || basename($_SERVER['PHP_SELF']) === 'login.php');
$navShowLogin = $navShowLogin ?? true;
?>
<header class="sticky top-0 z-50 px-3 py-4 sm:px-4">
    <nav class="mx-auto flex min-h-16 max-w-6xl items-center justify-between rounded-full border border-cyan-100 bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 px-4 py-3 shadow-md backdrop-blur-md">
        <a href="homepage.php" class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white shadow-sm">AN</div>
            <span class="font-bold text-teal-700" data-t="alumni_network">Alumni Network</span>
        </a>

        <div class="hidden items-center gap-2 md:flex">
            <?php if ($navShowHome): ?>
                <a href="homepage.php" class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-2 text-sm font-bold text-white shadow-md" data-t="home">Home</a>
            <?php endif; ?>

            <button type="button" onclick="toggleTheme()" class="theme-toggle flex h-9 w-9 items-center justify-center rounded-full border border-cyan-100 bg-white text-sm shadow-sm hover:bg-cyan-50 transition" aria-label="Toggle theme">
                <i class="fa-solid fa-moon"></i>
            </button>

            <?php if ($navShowRegister): ?>
                <a href="register.php" class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-2 text-sm font-bold text-white shadow-md" data-t="register">Register</a>
            <?php endif; ?>

            <?php if ($navShowLogin): ?>
                <a href="login.php" class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-2 text-sm font-bold text-white shadow-md" data-t="login">Login</a>
            <?php endif; ?>
        </div>

        <button type="button" id="menuBtn" class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white md:hidden" aria-label="Open menu" aria-expanded="false" onclick="toggleMobileMenu()">
            <span id="menuBtnIcon" class="menu-btn-icon">☰</span>
        </button>
    </nav>
</header>

<div id="mobileBackdrop" class="mobile-menu-backdrop md-hide" onclick="closeMobileMenu()"></div>
<div id="mobileMenu" class="mobile-menu-overlay md-hide rounded-b-3xl border-b border-x border-cyan-100 bg-gradient-to-b from-cyan-50 via-cyan-100 to-teal-100 p-3 shadow-xl md:hidden">
    <div class="grid gap-1 text-sm font-semibold">
        <?php if ($navShowHome): ?>
            <a href="homepage.php" class="block rounded-xl px-4 py-3 font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700" data-t="home">Home</a>
        <?php endif; ?>
        <?php if ($navShowRegister): ?>
            <a href="register.php" class="block rounded-xl px-4 py-3 font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700" data-t="register">Register</a>
        <?php endif; ?>
        <?php if ($navShowLogin): ?>
            <a href="login.php" class="block rounded-xl px-4 py-3 font-semibold text-slate-700 hover:bg-white/70 hover:text-teal-700" data-t="login">Login</a>
        <?php endif; ?>
        <button type="button" onclick="toggleTheme()" class="theme-toggle mt-1 flex w-full items-center justify-center gap-2 rounded-xl border bg-white px-3 py-2 text-sm font-semibold text-slate-700">
            <i class="fa-solid fa-moon"></i> <span>Theme</span>
        </button>
    </div>
</div>

<div class="auth-bg-decor">
    <div class="absolute -top-40 -right-40 h-96 w-96 rounded-full bg-teal-200/20 blur-3xl"></div>
    <div class="absolute -bottom-40 -left-40 h-96 w-96 rounded-full bg-cyan-200/20 blur-3xl"></div>
</div>

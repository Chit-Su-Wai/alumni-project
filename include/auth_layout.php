<?php
/**
 * Auth page layout helpers.
 *
 * Set these before including auth_layout_start.php:
 *   $pageTitle, $heroBadge, $heroTitle, $heroDesc, $formTitle, $formSubtitle
 */

function auth_layout_head(string $pageTitle): void
{
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($pageTitle) . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../include/theme.css">
    <link rel="stylesheet" href="../include/auth.css">
</head>
<body class="min-h-screen overflow-x-hidden bg-gradient-to-br from-white via-cyan-50 to-teal-50 text-slate-800 antialiased">';
}

function auth_layout_start(
    string $heroBadge,
    string $heroTitle,
    string $heroDesc,
    string $formTitle,
    string $formSubtitle
): void {
    echo '
<main class="auth-main">
    <section class="auth-card">
        <div class="auth-card-hero">
            <img src="../images/home.jpg" alt="Alumni Network" loading="eager" decoding="async">
            <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 to-black/20"></div>
            <div class="absolute inset-0 flex items-center p-10">
                <div>
                    <span class="inline-flex rounded-full bg-cyan-400/20 px-4 py-2 text-sm font-bold text-cyan-100 backdrop-blur">' . htmlspecialchars($heroBadge) . '</span>
                    <h1 class="mt-5 text-5xl font-black leading-tight text-white">' . htmlspecialchars($heroTitle) . '</h1>
                    <p class="mt-4 max-w-md text-sm leading-7 text-cyan-100">' . htmlspecialchars($heroDesc) . '</p>
                </div>
            </div>
        </div>
        <div class="auth-card-body">
            <div class="auth-form-wrap">
                <div class="auth-form-header">
                    <h2 class="auth-form-title">' . htmlspecialchars($formTitle) . '</h2>
                    <p class="auth-form-subtitle">' . $formSubtitle . '</p>
                </div>
                <div class="auth-alert-slot">';
}

function auth_layout_end(): void
{
    echo '
                </div>
            </div>
        </div>
    </section>
</main>';
}

function auth_nav_scripts(): void
{
    echo '
<script>
function toggleMobileMenu() {
    var menu = document.getElementById("mobileMenu");
    var backdrop = document.getElementById("mobileBackdrop");
    var btnIcon = document.getElementById("menuBtnIcon");
    var menuBtn = document.getElementById("menuBtn");
    if (!menu) return;
    if (menu.classList.contains("active")) {
        closeMobileMenu();
        return;
    }
    menu.classList.add("active");
    if (backdrop) backdrop.classList.add("active");
    if (btnIcon) { btnIcon.textContent = "✕"; btnIcon.classList.add("is-open"); }
    if (menuBtn) { menuBtn.setAttribute("aria-expanded", "true"); menuBtn.setAttribute("aria-label", "Close menu"); }
    document.body.classList.add("mobile-menu-open");
}
function closeMobileMenu() {
    var menu = document.getElementById("mobileMenu");
    var backdrop = document.getElementById("mobileBackdrop");
    var btnIcon = document.getElementById("menuBtnIcon");
    if (menu) menu.classList.remove("active");
    if (backdrop) backdrop.classList.remove("active");
    if (btnIcon) { btnIcon.textContent = "☰"; btnIcon.classList.remove("is-open"); }
    var menuBtn = document.getElementById("menuBtn");
    if (menuBtn) { menuBtn.setAttribute("aria-expanded", "false"); menuBtn.setAttribute("aria-label", "Open menu"); }
    document.body.classList.remove("mobile-menu-open");
}
document.addEventListener("keydown", function (e) { if (e.key === "Escape") closeMobileMenu(); });
window.addEventListener("resize", function () { if (window.innerWidth >= 768) closeMobileMenu(); });
document.addEventListener("DOMContentLoaded", function () { closeMobileMenu(); });

function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input || !btn) return;
    var icon = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        if (icon) icon.className = "fa-regular fa-eye-slash";
    } else {
        input.type = "password";
        if (icon) icon.className = "fa-regular fa-eye";
    }
}
</script>';
}

function auth_layout_scripts(): void
{
    auth_nav_scripts();
    echo '
<script src="../include/theme.js"></script>
<script src="../lang/lang.php"></script>
<script src="../include/translations.js"></script>
</body>
</html>';
}

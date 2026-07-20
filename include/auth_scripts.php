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
        if (btnIcon) { btnIcon.textContent = "✕"; btnIcon.classList.add("is-open"); }
        if (menuBtn) { menuBtn.setAttribute("aria-expanded", "true"); menuBtn.setAttribute("aria-label", "Close menu"); }
        document.body.classList.add("mobile-menu-open");
    }
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
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.className = 'fa-regular fa-eye-slash';
    } else {
        input.type = 'password';
        if (icon) icon.className = 'fa-regular fa-eye';
    }
}
</script>

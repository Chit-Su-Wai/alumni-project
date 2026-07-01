function toggleTheme() {
    var body = document.body;
    var current = body.getAttribute('data-theme') || 'light';
    var next = current === 'dark' ? 'light' : 'dark';
    body.setAttribute('data-theme', next);
    localStorage.setItem('site_theme', next);
    updateThemeIcon(next);
}

function applyTheme() {
    var saved = localStorage.getItem('site_theme') || 'light';
    document.body.setAttribute('data-theme', saved);
    updateThemeIcon(saved);
}

function updateThemeIcon(theme) {
    document.querySelectorAll('.theme-toggle i').forEach(function(icon) {
        if (theme === 'dark') {
            icon.className = 'fa-solid fa-sun';
        } else {
            icon.className = 'fa-solid fa-moon';
        }
    });
}

// Apply theme immediately (for body background) then re-apply icons after DOM ready
applyTheme();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyTheme);
} else {
    applyTheme();
}

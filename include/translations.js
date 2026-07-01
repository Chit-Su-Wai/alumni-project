/**
 * translations.js
 * Custom language engine — replaces Google Translate.
 *
 * How it works:
 *  - window.LANG is injected by lang/lang.php (loaded before this script)
 *  - Elements with data-t="key" get their textContent replaced
 *  - Elements with data-t-placeholder="key" get their placeholder replaced
 *  - switchLang(lang) updates the session via fetch + re-applies translations instantly
 *  - Language is also stored in localStorage for instant apply on page load
 */

(function () {

    /* ── Apply translations to all data-t elements ── */
    function applyTranslations(strings) {
        if (!strings) return;

        // Text content elements
        document.querySelectorAll('[data-t]').forEach(function (el) {
            var key = el.getAttribute('data-t');
            if (strings[key] !== undefined) {
                el.textContent = strings[key];
            }
        });

        // Placeholder elements (inputs/links)
        document.querySelectorAll('[data-t-placeholder]').forEach(function (el) {
            var key = el.getAttribute('data-t-placeholder');
            if (strings[key] !== undefined) {
                el.setAttribute('placeholder', strings[key]);
            }
        });
    }

    /* ── Update the language switcher button UI ── */
    function updateLangUI(lang) {
        // Update label text (EN / MM)
        var label = document.getElementById('currentLangLabel');
        if (label) {
            label.textContent = lang === 'mm' ? 'MM' : 'EN';
        }

        // Highlight the active language option button
        document.querySelectorAll('.lang-option').forEach(function (btn) {
            if (btn.getAttribute('data-lang') === lang) {
                btn.classList.add('bg-cyan-50', 'text-teal-700');
            } else {
                btn.classList.remove('bg-cyan-50', 'text-teal-700');
            }
        });

        // Highlight mobile lang buttons
        document.querySelectorAll('.lang-mobile').forEach(function (btn) {
            if (btn.getAttribute('data-lang') === lang) {
                btn.classList.add('bg-cyan-50', 'border-cyan-300');
            } else {
                btn.classList.remove('bg-cyan-50', 'border-cyan-300');
            }
        });
    }

    /* ── Switch language (called by EN/MM buttons) ── */
    window.switchLang = function (lang) {
        if (!['en', 'mm'].includes(lang)) return;

        // 1. Store in localStorage for instant apply on next page load
        localStorage.setItem('site_lang', lang);

        // 2. Update session on the server (fire-and-forget)
        fetch('../lang/lang.php?lang=' + lang, { method: 'GET' }).catch(function () {
            // also try from root path (admin pages)
            fetch('lang/lang.php?lang=' + lang, { method: 'GET' }).catch(function () {});
        });

        // 3. Fetch the new strings and apply immediately (no page reload)
        var base = getLangBase();
        fetch(base + '?lang=' + lang)
            .then(function (res) { return res.text(); })
            .then(function (js) {
                // Execute the returned JS to update window.LANG
                // eslint-disable-next-line no-new-func
                (new Function(js))();
                applyTranslations(window.LANG);
                updateLangUI(lang);
            })
            .catch(function () {
                // Fallback: reload the page with ?lang= param
                window.location.href = window.location.pathname + '?lang=' + lang;
            });

        // Close dropdown if open
        var dropdown = document.getElementById('langDropdown');
        if (dropdown) dropdown.classList.add('hidden');
    };

    /* ── Detect the correct path to lang.php ── */
    function getLangBase() {
        // Try to detect depth by checking known path patterns
        var path = window.location.pathname;
        if (path.indexOf('/admin/') !== -1 || path.indexOf('/alumni/') !== -1) {
            return '../lang/lang.php';
        }
        return 'lang/lang.php';
    }

    /* ── On page load: apply translations from already-injected window.LANG ── */
    function init() {
        var lang = (window.LANG_CODE) || localStorage.getItem('site_lang') || 'en';

        // If LANG isn't loaded yet (e.g., lang.php script not included), skip gracefully
        if (window.LANG && Object.keys(window.LANG).length > 0) {
            applyTranslations(window.LANG);
            updateLangUI(lang);
        } else {
            // LANG not ready yet — sync with localStorage preference via fetch
            var savedLang = localStorage.getItem('site_lang') || 'en';
            if (savedLang !== 'en') {
                var base = getLangBase();
                fetch(base + '?lang=' + savedLang)
                    .then(function (res) { return res.text(); })
                    .then(function (js) {
                        (new Function(js))();
                        applyTranslations(window.LANG);
                        updateLangUI(savedLang);
                    })
                    .catch(function () {});
            }
        }
    }

    // Run after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

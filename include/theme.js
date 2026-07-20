/* ============================================================
   Alumni Network — Theme + UI helpers
   ============================================================ */

/* ---------- Theme ---------- */
function toggleTheme() {
    var body = document.body;
    var current = body.getAttribute('data-theme') || 'light';
    var next = current === 'dark' ? 'light' : 'dark';

    /* Smooth transition: temporarily enable transitions on all elements */
    var root = document.documentElement;
    root.classList.add('theme-anim');
    window.clearTimeout(window.__themeAnimTimer);
    window.__themeAnimTimer = window.setTimeout(function () {
        root.classList.remove('theme-anim');
    }, 400);

    body.setAttribute('data-theme', next);
    localStorage.setItem('site_theme', next);
    updateThemeIcon(next);

    /* Spin the toggle icon for a playful effect */
    document.querySelectorAll('.theme-toggle').forEach(function (btn) {
        btn.classList.remove('spin');
        /* force reflow so the animation can replay */
        void btn.offsetWidth;
        btn.classList.add('spin');
    });

    showToast('info', (next === 'dark' ? 'Dark mode enabled' : 'Light mode enabled'), { duration: 1800, silent: true });
}

function applyTheme() {
    var saved = localStorage.getItem('site_theme');
    if (!saved) {
        /* respect OS preference on first visit */
        saved = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }
    document.body.setAttribute('data-theme', saved);
    updateThemeIcon(saved);
}

function updateThemeIcon(theme) {
    document.querySelectorAll('.theme-toggle i').forEach(function (icon) {
        icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    });
}

window.__requestLocks = window.__requestLocks || {};

function lockRequest(key) {
    if (!key) return true;
    if (window.__requestLocks[key]) return false;
    window.__requestLocks[key] = true;
    return true;
}

function unlockRequest(key) {
    if (!key) return;
    delete window.__requestLocks[key];
}

function setSubmitButtonsState(form, disabled) {
    if (!form) return;

    var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    buttons.forEach(function (btn) {
        if (disabled) {
            if (!btn.dataset.originalText && btn.tagName === 'BUTTON') {
                btn.dataset.originalText = btn.innerHTML;
            }
            btn.disabled = true;
            btn.classList.add('is-disabled');
            if (btn.tagName === 'BUTTON' && btn.dataset.loadingText) {
                btn.innerHTML = btn.dataset.loadingText;
            }
        } else {
            btn.disabled = false;
            btn.classList.remove('is-disabled');
            if (btn.tagName === 'BUTTON' && btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
                delete btn.dataset.originalText;
            }
        }
    });
}

function bindSingleSubmitForms() {
    document.querySelectorAll('form').forEach(function (form) {
        if (form.dataset.asyncForm === 'true' || form.dataset.noAutoLock === 'true' || form.dataset.lockBound === 'true') return;

        form.dataset.lockBound = 'true';
        form.addEventListener('submit', function (e) {
            if (form.dataset.submitting === '1') {
                e.preventDefault();
                return;
            }

            form.dataset.submitting = '1';
            setSubmitButtonsState(form, true);
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    applyTheme();
    bindSingleSubmitForms();
});
applyTheme();


/* ============================================================
   Toast Notifications
   Usage: showToast('success'|'error'|'warning'|'info', message, {title, duration})
   ============================================================ */
function showToast(type, message, opts) {
    opts = opts || {};
    var container = document.getElementById('toastContainer');
    if (!container) {
        /* fall back to a console warning if container missing */
        console.warn('toastContainer missing', type, message);
        return;
    }

    var icons = {
        success: 'fa-solid fa-circle-check',
        error:   'fa-solid fa-circle-exclamation',
        warning: 'fa-solid fa-triangle-exclamation',
        info:    'fa-solid fa-circle-info'
    };

    var titles = {
        success: 'Success',
        error:   'Error',
        warning: 'Warning',
        info:    'Notice'
    };

    var t = document.createElement('div');
    t.className = 'toast toast-' + (icons[type] ? type : 'info');

    var icon = document.createElement('div');
    icon.className = 'toast-icon';
    icon.innerHTML = '<i class="' + (icons[type] || icons.info) + '"></i>';

    var body = document.createElement('div');
    body.className = 'toast-body';
    var titleHtml = opts.title ? opts.title : titles[type] || titles.info;
    body.innerHTML = '<div class="toast-title">' + escapeHtml(titleHtml) + '</div>' +
                     '<div class="toast-msg">' + escapeHtml(message) + '</div>';

    var close = document.createElement('button');
    close.className = 'toast-close';
    close.setAttribute('aria-label', 'Dismiss');
    close.innerHTML = '<i class="fa-solid fa-xmark"></i>';

    var progress = document.createElement('div');
    progress.className = 'toast-progress';

    t.appendChild(icon);
    t.appendChild(body);
    t.appendChild(close);
    t.appendChild(progress);
    container.appendChild(t);

    var duration = opts.duration != null ? opts.duration : 3800;
    if (duration > 0) {
        progress.style.animationDuration = duration + 'ms';
        var timer = window.setTimeout(function () { dismissToast(t); }, duration);
        t._timer = timer;
        close.addEventListener('click', function () { window.clearTimeout(timer); dismissToast(t); });
        /* pause on hover */
        t.addEventListener('mouseenter', function () { window.clearTimeout(timer); progress.style.animationPlayState = 'paused'; });
        t.addEventListener('mouseleave', function () {
            timer = window.setTimeout(function () { dismissToast(t); }, 1500);
            progress.style.animationPlayState = 'running';
        });
    } else {
        close.addEventListener('click', function () { dismissToast(t); });
    }

    return t;
}

function dismissToast(t) {
    if (!t || t._dismissing) return;
    t._dismissing = true;
    t.classList.add('hide');
    window.setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 260);
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}


/* ============================================================
   Image Lightbox with zoom
   Usage:  <img src="..." onclick="openLightbox(this.src, 'Caption')">
           or  openLightbox(url, caption)
   ============================================================ */
function openLightbox(src, caption) {
    var lb = document.getElementById('imageLightbox');
    var img = document.getElementById('lightboxImg');
    var cap = document.getElementById('lightboxCaption');
    if (!lb || !img) return;

    img.src = src;
    img.classList.remove('zoomed');
    if (cap) cap.textContent = caption || '';
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function toggleSavePost(postId, btn) {
    if (!btn) return;

    var lockKey = 'save-post-' + postId;
    if (!lockRequest(lockKey)) return;
    btn.disabled = true;

    var formData = new FormData();
    formData.append('post_id', postId);

    fetch('api_toggle_save_post.php', {
        method: 'POST',
        body: formData
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.error) {
                showToast('error', data.error, { title: 'Save Post' });
                return;
            }

            var icon = btn.querySelector('i');
            var saved = !!data.saved;
            btn.setAttribute('data-saved', saved ? '1' : '0');
            btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
            if (icon) {
                icon.className = saved ? 'fa-solid fa-bookmark' : 'fa-regular fa-bookmark';
            }
            btn.classList.toggle('text-teal-700', saved);
            btn.classList.toggle('bg-cyan-50', saved);
            btn.classList.toggle('border-cyan-100', saved);
            btn.classList.toggle('text-slate-500', !saved);
            showToast('success', saved ? 'Post saved' : 'Post removed from saved posts', { title: saved ? 'Saved' : 'Unsaved', duration: 1500, silent: true });
        })
        .catch(function () {
            showToast('error', 'Could not update saved post', { title: 'Save Post' });
        })
        .finally(function () {
            unlockRequest(lockKey);
            btn.disabled = false;
        });
}

function closeLightbox() {
    var lb = document.getElementById('imageLightbox');
    if (!lb) return;
    lb.classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function () {
    var img = document.getElementById('lightboxImg');
    if (img) {
        img.addEventListener('click', function (e) {
            e.stopPropagation();
            img.classList.toggle('zoomed');
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeLightbox();
    });
});


/* ============================================================
   Confirm Dialog (modern replacement for native confirm())
   Usage:
     confirmDialog('Delete this post?', function(){ window.location.href='...'; },
        { title:'Delete Post', confirmText:'Delete', danger:true });
   ============================================================ */
window._confirmCallback = null;

function confirmDialog(message, onConfirm, opts) {
    opts = opts || {};
    var modal = document.getElementById('confirmModal');
    if (!modal) { /* native fallback */
        if (window.confirm(message) && typeof onConfirm === 'function') onConfirm();
        return;
    }
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmTitle').textContent = opts.title || 'Are you sure?';
    var ok = document.getElementById('confirmOkBtn');
    ok.textContent = opts.confirmText || 'Confirm';
    ok.className = 'btn ' + (opts.danger === false ? 'btn-primary' : 'btn-danger');
    window._confirmCallback = onConfirm;
    modal.classList.add('open');
}

function closeConfirm() {
    var modal = document.getElementById('confirmModal');
    if (modal) modal.classList.remove('open');
    window._confirmCallback = null;
}

function runConfirm() {
    var cb = window._confirmCallback;
    closeConfirm();
    if (typeof cb === 'function') cb();
}


/* ============================================================
   Notification Bell Dropdown
   ============================================================ */
var notifDropdownLoaded = false;
var notifDropdownOpen = false;

function toggleNotifDropdown() {
    var dd = document.getElementById('notifDropdown');
    if (!dd) return;

    if (notifDropdownOpen) {
        closeNotifDropdown();
        return;
    }

    notifDropdownOpen = true;
    dd.classList.remove('hidden');

    if (!notifDropdownLoaded) {
        loadNotifDropdown();
        notifDropdownLoaded = true;
    }
}

function closeNotifDropdown() {
    var dd = document.getElementById('notifDropdown');
    if (dd) dd.classList.add('hidden');
    notifDropdownOpen = false;
}

function loadNotifDropdown() {
    var list = document.getElementById('notifList');
    if (!list) return;

    list.innerHTML = '<div class="flex items-center justify-center py-8 text-sm text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...</div>';

    fetch('api_notifications.php?action=list&limit=20')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success || !data.notifications || data.notifications.length === 0) {
                list.innerHTML = '<div class="notif-empty"><i class="fa-regular fa-bell"></i> No new notifications</div>';
                return;
            }

            var html = '';
            data.notifications.forEach(function (n) {
                var iconClass = n.icon || 'fa-regular fa-bell';
                var typeClass = n.type || 'default';
                var typeLabel = n.type_label || 'Notification';

                html += '<div class="notif-item unread" onclick="clickNotif(this, ' + n.id + ', \'' + escapeJsStr(n.link) + '\')">';
                html += '<div class="notif-icon type-' + typeClass + '"><i class="' + iconClass + '"></i></div>';
                html += '<div class="notif-body">';
                html += '<div class="notif-header">';
                html += '<span class="notif-type-badge type-' + typeClass + '">' + escapeHtml(typeLabel) + '</span>';
                html += '<button class="notif-delete" onclick="event.stopPropagation(); deleteNotif(' + n.id + ', this)" title="Delete notification">&times;</button>';
                html += '</div>';
                html += '<div class="notif-title">' + escapeHtml(n.title) + '</div>';
                if (n.body) html += '<div class="notif-body-text">' + escapeHtml(n.body) + '</div>';
                html += '<div class="notif-footer">';
                html += '<span class="notif-time">' + escapeHtml(n.time_ago) + '</span>';
                html += '<span class="notif-unread-dot"></span>';
                html += '</div>';
                html += '</div></div>';
            });
            list.innerHTML = html;

            var badge = document.getElementById('notifBadge');
            if (badge) {
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        })
        .catch(function () {
            list.innerHTML = '<div class="notif-empty"><i class="fa-solid fa-circle-exclamation"></i> Failed to load notifications</div>';
        });
}

function clickNotif(el, id, link) {
    var formData = new FormData();
    formData.append('id', id);
    fetch('api_notifications.php?action=mark_read', {
        method: 'POST',
        body: formData
    });

    el.remove();

    var badge = document.getElementById('notifBadge');
    if (badge) {
        var count = parseInt(badge.textContent) || 0;
        if (count > 1) {
            badge.textContent = count > 10 ? '9+' : (count - 1);
        } else {
            badge.classList.add('hidden');
        }
    }

    var list = document.getElementById('notifList');
    if (list && list.children.length === 0) {
        list.innerHTML = '<div class="notif-empty"><i class="fa-regular fa-bell"></i> No new notifications</div>';
    }

    if (link) {
        window.location.href = link;
    }
}

function markAllNotifRead() {
    fetch('api_notifications.php?action=mark_all_read', {
        method: 'POST'
    });

    var badge = document.getElementById('notifBadge');
    if (badge) badge.classList.add('hidden');

    var list = document.getElementById('notifList');
    if (list) {
        list.innerHTML = '<div class="notif-empty"><i class="fa-regular fa-bell"></i> No new notifications</div>';
    }
}

function deleteNotif(id, btn) {
    var formData = new FormData();
    formData.append('id', id);
    fetch('api_notifications.php?action=delete', {
        method: 'POST',
        body: formData
    });

    var item = btn.closest('.notif-item');
    if (item) {
        var wasUnread = item.classList.contains('unread');
        item.remove();

        var badge = document.getElementById('notifBadge');
        if (wasUnread && badge) {
            var count = parseInt(badge.textContent) || 0;
            if (count > 1) {
                badge.textContent = count > 10 ? '9+' : (count - 1);
            } else {
                badge.classList.add('hidden');
            }
        }

        var list = document.getElementById('notifList');
        if (list && list.children.length === 0) {
            list.innerHTML = '<div class="notif-empty"><i class="fa-regular fa-bell"></i> No new notifications</div>';
        }
    }
}

function escapeJsStr(str) {
    if (!str) return '';
    return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

document.addEventListener('click', function (e) {
    var container = document.getElementById('notifBellContainer');
    if (container && !container.contains(e.target)) {
        closeNotifDropdown();
    }
});

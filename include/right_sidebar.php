<?php
/**
 * Right Sidebar Widget
 * Shows: Calendar, Recent Posts, Recent Alumni, Upcoming Events, Popular Posts
 */
require_once __DIR__ . '/../config/db.php';

$currentUserId = $_SESSION['user_id'] ?? 0;

/* Recent Posts */
$recentPosts = $conn->query("
    SELECT p.id, p.content, p.created_at, u.name
    FROM posts p
    INNER JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
");

/* Recent Alumni */
$recentAlumni = $conn->query("
    SELECT u.id, u.name, u.profile_image, a.graduation_year
    FROM users u
    LEFT JOIN approved_students a ON u.approved_id = a.approved_id
    WHERE u.id != $currentUserId
    AND u.role = 'user'
    ORDER BY u.created_at DESC
    LIMIT 5
");

/* Upcoming Events */
$upcomingEvents = $conn->query("
    SELECT id, title, event_date, location
    FROM events
    WHERE event_date >= CURDATE()
    ORDER BY event_date ASC
    LIMIT 5
");

/* Popular Posts (most liked) */
$popularPosts = $conn->query("
    SELECT p.id, p.content, p.created_at, u.name,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count
    FROM posts p
    INNER JOIN users u ON p.user_id = u.id
    ORDER BY like_count DESC
    LIMIT 5
");
?>

<div class="space-y-5 sticky top-24 h-fit">

    <!-- Calendar Widget -->
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-cyan-50">
        <h3 class="font-bold text-lg mb-3 text-slate-800">
            <i class="fa-regular fa-calendar mr-2 text-teal-600"></i>Calendar
        </h3>
        <div id="calendarWidget" class="text-sm"></div>
    </div>

    <!-- Recent Posts -->
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-cyan-50">
        <h3 class="font-bold text-lg mb-3 text-slate-800">
            <i class="fa-regular fa-newspaper mr-2 text-teal-600"></i>Recent Posts
        </h3>

        <?php if ($recentPosts && $recentPosts->num_rows > 0): ?>
            <?php while ($r = $recentPosts->fetch_assoc()): ?>
                <a href="feed.php#post-<?= $r['id'] ?>" class="block mb-3 pb-3 border-b border-slate-100 last:border-0 last:mb-0 last:pb-0 hover:bg-cyan-50/50 rounded-lg px-2 py-1 -mx-2 transition">
                    <div class="text-sm text-slate-700 font-medium line-clamp-2">
                        <?= htmlspecialchars(substr($r['content'], 0, 60)) ?>...
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-teal-600 font-semibold"><?= htmlspecialchars($r['name']) ?></span>
                        <span class="text-xs text-slate-400"><?= date("M d", strtotime($r['created_at'])) ?></span>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-sm text-slate-400">No posts yet</p>
        <?php endif; ?>
    </div>

    <!-- Recent Alumni -->
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-cyan-50">
        <h3 class="font-bold text-lg mb-3 text-slate-800">
            <i class="fa-solid fa-user-plus mr-2 text-teal-600"></i>New Alumni
        </h3>

        <?php if ($recentAlumni && $recentAlumni->num_rows > 0): ?>
            <?php while ($a = $recentAlumni->fetch_assoc()): ?>
                <a href="user_profile.php?id=<?= $a['id'] ?>" class="flex items-center gap-3 mb-3 pb-3 border-b border-slate-100 last:border-0 last:mb-0 last:pb-0 hover:bg-cyan-50/50 rounded-lg px-2 py-1 -mx-2 transition">
                    <img src="<?= !empty($a['profile_image']) ? htmlspecialchars($a['profile_image']) : '../images/default-avatar.svg' ?>"
                         class="h-9 w-9 rounded-full object-cover border border-cyan-100">
                    <div>
                        <div class="text-sm font-bold text-slate-700"><?= htmlspecialchars($a['name']) ?></div>
                        <?php if ($a['graduation_year']): ?>
                            <div class="text-xs text-slate-400">Class of <?= $a['graduation_year'] ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-sm text-slate-400">No alumni yet</p>
        <?php endif; ?>
    </div>

    <!-- Upcoming Events -->
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-cyan-50">
        <h3 class="font-bold text-lg mb-3 text-slate-800">
            <i class="fa-regular fa-calendar-check mr-2 text-teal-600"></i>Upcoming Events
        </h3>

        <?php if ($upcomingEvents && $upcomingEvents->num_rows > 0): ?>
            <?php while ($ev = $upcomingEvents->fetch_assoc()): ?>
                <div class="mb-3 pb-3 border-b border-slate-100 last:border-0 last:mb-0 last:pb-0">
                    <div class="text-sm font-bold text-slate-700"><?= htmlspecialchars($ev['title']) ?></div>
                    <div class="flex items-center gap-2 mt-1 text-xs text-slate-400">
                        <span><i class="fa-regular fa-calendar mr-1"></i><?= date("M d, Y", strtotime($ev['event_date'])) ?></span>
                        <?php if ($ev['location']): ?>
                            <span><i class="fa-solid fa-location-dot mr-1"></i><?= htmlspecialchars($ev['location']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-sm text-slate-400">No upcoming events</p>
        <?php endif; ?>
    </div>

    <!-- Popular Posts -->
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-cyan-50">
        <h3 class="font-bold text-lg mb-3 text-slate-800">
            <i class="fa-solid fa-fire mr-2 text-orange-500"></i>Popular Posts
        </h3>

        <?php if ($popularPosts && $popularPosts->num_rows > 0): ?>
            <?php while ($pp = $popularPosts->fetch_assoc()): ?>
                <a href="feed.php#post-<?= $pp['id'] ?>" class="block mb-3 pb-3 border-b border-slate-100 last:border-0 last:mb-0 last:pb-0 hover:bg-cyan-50/50 rounded-lg px-2 py-1 -mx-2 transition">
                    <div class="text-sm text-slate-700 font-medium line-clamp-2">
                        <?= htmlspecialchars(substr($pp['content'], 0, 50)) ?>...
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-teal-600 font-semibold"><?= htmlspecialchars($pp['name']) ?></span>
                        <span class="text-xs text-slate-400">
                            <i class="fa-regular fa-thumbs-up mr-1"></i><?= $pp['like_count'] ?>
                        </span>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-sm text-slate-400">No popular posts yet</p>
        <?php endif; ?>
    </div>

</div>

<!-- Calendar Widget Script -->
<script>
(function() {
    const cal = document.getElementById('calendarWidget');
    if (!cal) return;

    const now = new Date();
    let currentMonth = now.getMonth();
    let currentYear = now.getFullYear();

    function renderCalendar() {
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const days = ['Su','Mo','Tu','We','Th','Fr','Sa'];
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const today = new Date();

        let html = '<div class="flex items-center justify-between mb-3">';
        html += '<button onclick="calPrev()" class="h-7 w-7 rounded-full hover:bg-cyan-50 text-slate-500 text-xs">&lt;</button>';
        html += '<span class="font-bold text-sm text-teal-700">' + months[currentMonth] + ' ' + currentYear + '</span>';
        html += '<button onclick="calNext()" class="h-7 w-7 rounded-full hover:bg-cyan-50 text-slate-500 text-xs">&gt;</button>';
        html += '</div>';

        html += '<div class="grid grid-cols-7 gap-1 text-center text-xs">';
        days.forEach(d => { html += '<div class="font-bold text-slate-400 py-1">' + d + '</div>'; });

        for (let i = 0; i < firstDay; i++) { html += '<div></div>'; }

        for (let d = 1; d <= daysInMonth; d++) {
            const isToday = d === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear();
            html += '<div class="py-1 rounded-lg cursor-default ' + (isToday ? 'bg-teal-500 text-white font-bold' : 'text-slate-600 hover:bg-cyan-50') + '">' + d + '</div>';
        }
        html += '</div>';
        cal.innerHTML = html;
    }

    window.calPrev = function() { currentMonth--; if(currentMonth < 0){ currentMonth = 11; currentYear--; } renderCalendar(); };
    window.calNext = function() { currentMonth++; if(currentMonth > 11){ currentMonth = 0; currentYear++; } renderCalendar(); };

    renderCalendar();
})();
</script>

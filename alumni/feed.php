<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";
if (
    isset($_SESSION['role']) &&
    $_SESSION['role'] == 'admin'
) {
    header("Location: ../admin/dashboard.php");
    exit;
}

/* COMMENT UPDATE */
if(isset($_POST['update_comment'])){
    $comment_id = (int)$_POST['comment_id'];
    $comment = trim($_POST['comment']);
    if(!empty($comment)){
        $stmt = $conn->prepare("UPDATE comments SET comment = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $comment, $comment_id, $_SESSION['user_id']);
        $stmt->execute();
    }
    header("Location: feed.php");
    exit;
}

if (isset($_GET['delete_comment'])) {
    $comment_id = (int) $_GET['delete_comment'];
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $_SESSION['user_id']);
    $stmt->execute();
    header("Location: feed.php");
    exit;
}

$user_id = $_SESSION["user_id"];

/* Current User */
$userQuery = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$currentUser = $userQuery->get_result()->fetch_assoc();
$currentProfileImage = !empty($currentUser['profile_image']) ? $currentUser['profile_image'] : '../images/default-avatar.svg';

/* Filters */
$category = $_GET['category'] ?? 'All';
$search = trim($_GET['search'] ?? '');

/* Feed Query */
$sql = "SELECT posts.*, users.name, users.profile_image FROM posts INNER JOIN users ON posts.user_id = users.id WHERE users.role = 'user'";
$params = [];
$types = "";

if ($category != "All") {
    $sql .= " AND posts.category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND posts.content LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

$sql .= " ORDER BY posts.created_at DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);
$totalPosts = count($posts);

/* Recent Posts for Sidebar */
$recent = $conn->query("SELECT p.id, p.content, p.created_at, u.name, u.profile_image FROM posts p INNER JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-slate-50">

    <?php include "../include/user_header.php"; ?>

    <div class="max-w-7xl mx-auto px-4 py-4">

        <div class="grid lg:grid-cols-4 gap-6">

            <!-- LEFT SIDEBAR -->
            <div class="space-y-5 lg:sticky lg:top-24 lg:left-0 lg:h-fit hidden lg:block">

                <div class="bg-white rounded-3xl p-6 shadow-sm border border-cyan-50">
                    <h3 class="font-bold text-lg text-slate-800">
                        <span data-t="showing_posts">Showing</span> <?= $totalPosts ?> <span data-t="posts_label">Posts</span>
                    </h3>
                    <form method="GET" class="mt-4">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search posts..." data-t-placeholder="search_posts" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        <button class="w-full mt-3 rounded-xl bg-gradient-to-r from-cyan-400 to-teal-500 py-2.5 text-sm font-bold text-white shadow hover:shadow-md transition" data-t="search">Search</button>
                    </form>
                </div>

                <div class="bg-white rounded-3xl p-6 shadow-sm border border-cyan-50">
                    <h3 class="font-bold text-lg mb-4 text-slate-800" data-t="categories">Categories</h3>
                    <?php $categories = ["All", "General", "Job", "Event", "News"]; foreach ($categories as $cat): ?>
                        <a href="feed.php?category=<?= $cat ?>" class="block rounded-xl px-4 py-2.5 mb-1.5 text-sm font-semibold <?= $category == $cat ? 'bg-gradient-to-r from-cyan-400 to-teal-500 text-white' : 'text-slate-600 hover:bg-cyan-50' ?>"><?= $cat ?></a>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- CENTER -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Create Post Box -->
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-cyan-50">
                    <div class="flex items-center gap-3">
                        <img src="<?= htmlspecialchars($currentProfileImage) ?>" class="h-12 w-12 rounded-full object-cover border-2 border-cyan-100">
                        <a href="create_post.php" class="flex-1 rounded-full border border-slate-200 px-5 py-3 text-sm text-slate-400 hover:bg-slate-50 transition" data-t-placeholder="whats_on_mind">What's on your mind?</a>
                    </div>
                </div>

                <?php if (count($posts) == 0): ?>
                    <div class="bg-white rounded-3xl p-10 text-center shadow-sm border border-cyan-50">
                        <i class="fa-regular fa-face-smile text-5xl text-slate-300"></i>
                        <h3 class="mt-4 text-xl font-bold text-slate-700" data-t="no_posts_found">No posts found</h3>
                    </div>
                <?php endif; ?>

                <?php foreach ($posts as $post): ?>

                    <?php
                    $likeStmt = $conn->prepare("SELECT COUNT(*) AS total FROM post_likes WHERE post_id = ?");
                    $likeStmt->bind_param("i", $post['id']);
                    $likeStmt->execute();
                    $likeCount = $likeStmt->get_result()->fetch_assoc()['total'];

                    $cmtStmt = $conn->prepare("SELECT COUNT(*) AS total FROM comments WHERE post_id = ?");
                    $cmtStmt->bind_param("i", $post['id']);
                    $cmtStmt->execute();
                    $commentCount = $cmtStmt->get_result()->fetch_assoc()['total'];

                    $likedStmt = $conn->prepare("SELECT COUNT(*) AS total FROM post_likes WHERE post_id = ? AND user_id = ?");
                    $likedStmt->bind_param("ii", $post['id'], $_SESSION['user_id']);
                    $likedStmt->execute();
                    $isLiked = $likedStmt->get_result()->fetch_assoc()['total'] > 0;
                    ?>

                    <div id="post-<?= $post['id'] ?>" class="bg-white rounded-3xl p-5 shadow-sm border border-cyan-50">

                        <!-- User Info -->
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <img src="<?= !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : '../images/default-avatar.svg' ?>" class="h-11 w-11 rounded-full object-cover border-2 border-cyan-100">
                                <div>
                                    <h3 class="font-bold text-sm text-slate-800">
                                        <?= htmlspecialchars($post['name']) ?>
                                        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                            <span class="text-cyan-600 text-xs font-normal">(You)</span>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="text-xs text-slate-400"><?= date("M d, Y h:i A", strtotime($post['created_at'])) ?></p>
                                </div>
                            </div>
                            <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                <div class="flex gap-2">
                                    <a href="edit_post.php?id=<?= $post['id'] ?>" class="h-8 w-8 flex items-center justify-center rounded-full text-cyan-500 hover:bg-cyan-50 transition"><i class="fa-solid fa-pen text-xs"></i></a>
                                    <a href="delete_post.php?id=<?= $post['id'] ?>" onclick="return confirm('Delete this post?')" class="h-8 w-8 flex items-center justify-center rounded-full text-red-400 hover:bg-red-50 transition"><i class="fa-solid fa-trash text-xs"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Category -->
                        <div class="mt-3">
                            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700"><?= htmlspecialchars($post['category']) ?></span>
                        </div>

                        <!-- Content -->
                        <div class="mt-3 text-sm text-slate-700 leading-7"><?= nl2br(htmlspecialchars($post['content'])) ?></div>

                        <!-- Single Image -->
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?= htmlspecialchars($post['image']) ?>" class="mt-3 w-full max-h-[400px] object-cover rounded-2xl">
                        <?php endif; ?>

                        <!-- Multiple Images -->
                        <?php
                        $imgStmt = $conn->prepare("SELECT image FROM post_images WHERE post_id = ?");
                        $imgStmt->bind_param("i", $post['id']);
                        $imgStmt->execute();
                        $images = $imgStmt->get_result();
                        ?>
                        <?php if ($images->num_rows > 0): ?>
                            <div class="grid grid-cols-2 gap-2 mt-3">
                                <?php while ($img = $images->fetch_assoc()): ?>
                                    <img src="<?= htmlspecialchars($img['image']) ?>" class="h-48 w-full object-cover rounded-xl">
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Like & Comment Counts -->
                        <div class="mt-3 flex items-center gap-4 text-xs text-slate-400">
                            <?php if ($likeCount > 0): ?>
                                <span id="like-info-<?= $post['id'] ?>" class="flex items-center gap-1">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500 text-[10px] text-white"><i class="fa-solid fa-thumbs-up"></i></span>
                                    <span id="like-count-<?= $post['id'] ?>"><?= $likeCount ?></span>
                                </span>
                            <?php else: ?>
                                <span id="like-info-<?= $post['id'] ?>" class="flex items-center gap-1 hidden">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500 text-[10px] text-white"><i class="fa-solid fa-thumbs-up"></i></span>
                                    <span id="like-count-<?= $post['id'] ?>">0</span>
                                </span>
                            <?php endif; ?>

                            <?php if ($commentCount > 0): ?>
                                <span id="comment-info-<?= $post['id'] ?>" class="flex items-center gap-1">
                                    <span id="comment-count-<?= $post['id'] ?>"><?= $commentCount ?></span> comment<?= $commentCount > 1 ? 's' : '' ?>
                                </span>
                            <?php else: ?>
                                <span id="comment-info-<?= $post['id'] ?>" class="flex items-center gap-1 hidden">
                                    <span id="comment-count-<?= $post['id'] ?>">0</span> comments
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-3 border-t border-slate-100 pt-3 grid grid-cols-3 gap-1">
                            <button onclick="toggleLike(<?= $post['id'] ?>)" id="like-btn-<?= $post['id'] ?>" class="flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold transition <?= $isLiked ? 'text-cyan-600 bg-cyan-50' : 'text-slate-500 hover:bg-slate-50' ?>">
                                <i id="like-icon-<?= $post['id'] ?>" class="<?= $isLiked ? 'fa-solid' : 'fa-regular' ?> fa-thumbs-up"></i>
                                <span data-t="like">Like</span>
                            </button>
                            <button onclick="document.getElementById('comment-section-<?= $post['id'] ?>').classList.toggle('hidden')" class="flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50 transition">
                                <i class="fa-regular fa-comment"></i>
                                <span data-t="comment">Comment</span>
                            </button>
                            <button onclick="openShareMenu(<?= $post['id'] ?>)" class="flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50 transition">
                                <i class="fa-solid fa-share-nodes"></i>
                                <span data-t="share">Share</span>
                            </button>
                        </div>

                        <!-- Comment Section -->
                        <div id="comment-section-<?= $post['id'] ?>" class="hidden mt-3 border-t border-slate-100 pt-3">
                            <form onsubmit="submitComment(event, <?= $post['id'] ?>)" class="flex gap-2">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <input type="text" id="comment-input-<?= $post['id'] ?>" name="comment" placeholder="Write a comment..." required data-t-placeholder="write_comment" class="flex-1 rounded-full border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-400">
                                <button type="submit" class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:shadow transition">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </button>
                            </form>

                            <!-- Existing Comments -->
                            <?php
                            $cmt = $conn->prepare("SELECT comments.*, users.name FROM comments INNER JOIN users ON comments.user_id = users.id WHERE post_id = ? AND users.role = 'user' ORDER BY comments.created_at DESC");
                            $cmt->bind_param("i", $post['id']);
                            $cmt->execute();
                            $comments = $cmt->get_result();
                            ?>
                            <div id="comments-list-<?= $post['id'] ?>">
                                <?php while($com = $comments->fetch_assoc()): ?>
                                    <div class="mt-3 flex gap-2">
                                        <div class="flex-1 bg-slate-50 rounded-2xl px-4 py-3">
                                            <div class="flex items-center justify-between">
                                                <b class="text-sm text-slate-700"><?= htmlspecialchars($com['name']) ?></b>
                                                <?php if($com['user_id'] == $_SESSION['user_id']): ?>
                                                    <div class="flex gap-2 text-xs">
                                                        <a href="feed.php?edit_comment=<?= $com['id'] ?>" class="text-cyan-600 hover:underline">Edit</a>
                                                        <a href="feed.php?delete_comment=<?= $com['id'] ?>" onclick="return confirm('Delete comment?')" class="text-red-500 hover:underline">Delete</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if(isset($_GET['edit_comment']) && $_GET['edit_comment'] == $com['id']): ?>
                                                <form method="POST" class="mt-2 flex gap-2">
                                                    <input type="hidden" name="comment_id" value="<?= $com['id'] ?>">
                                                    <input type="text" name="comment" value="<?= htmlspecialchars($com['comment']) ?>" class="flex-1 border rounded-xl px-3 py-2 text-sm" required>
                                                    <button type="submit" name="update_comment" class="bg-cyan-500 text-white px-3 py-2 rounded-xl text-sm font-bold">Save</button>
                                                </form>
                                            <?php else: ?>
                                                <p class="mt-1 text-sm text-slate-600"><?= htmlspecialchars($com['comment']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <!-- RIGHT SIDEBAR -->
            <div class="space-y-5 lg:sticky lg:top-24 lg:h-fit hidden lg:block">

                <!-- Calendar -->
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-cyan-50">
                    <h3 class="font-bold text-lg mb-3 text-slate-800">
                        <i class="fa-regular fa-calendar mr-2 text-teal-600"></i><span data-t="calendar">Calendar</span>
                    </h3>
                    <div id="calendarWidget" class="text-sm"></div>
                </div>

                <!-- Recent Posts -->
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-cyan-50">
                    <h3 class="font-bold text-lg mb-3 text-slate-800">
                        <i class="fa-regular fa-newspaper mr-2 text-teal-600"></i><span data-t="recent_posts">Recent Posts</span>
                    </h3>
                    <?php if ($recent && $recent->num_rows > 0): ?>
                        <?php while ($r = $recent->fetch_assoc()): ?>
                            <a href="feed.php#post-<?= $r['id'] ?>" class="flex items-center gap-3 mb-3 pb-3 border-b border-slate-100 last:border-0 last:mb-0 last:pb-0 hover:bg-cyan-50/50 rounded-lg px-2 py-1 -mx-2 transition">
                                <img src="<?= !empty($r['profile_image']) ? htmlspecialchars($r['profile_image']) : '../images/default-avatar.svg' ?>" class="h-8 w-8 rounded-full object-cover border border-cyan-100">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-slate-700 font-medium line-clamp-1"><?= htmlspecialchars(substr($r['content'], 0, 40)) ?>...</div>
                                    <div class="text-xs text-slate-400 mt-0.5"><?= date("M d", strtotime($r['created_at'])) ?></div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-sm text-slate-400">No posts yet</p>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>

<!-- SHARE MODAL -->
<div id="shareModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-3xl p-6 w-80 shadow-2xl">
        <h3 class="font-bold text-lg mb-4 text-slate-800" data-t="share_post">Share Post</h3>
        <div class="space-y-3">
            <button onclick="copyLink()" class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-cyan-400 to-teal-500 text-white py-3 rounded-xl font-bold shadow hover:shadow-md transition" data-t="copy_link"><i class="fa-solid fa-link"></i> Copy Link</button>
            <button onclick="shareFacebook()" class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition" data-t="facebook"><i class="fa-brands fa-facebook-f"></i> Facebook</button>
            <button onclick="shareTelegram()" class="w-full flex items-center justify-center gap-2 bg-sky-500 text-white py-3 rounded-xl font-bold hover:bg-sky-600 transition" data-t="telegram"><i class="fa-brands fa-telegram"></i> Telegram</button>
            <button onclick="closeShareMenu()" class="w-full bg-slate-100 py-3 rounded-xl font-bold text-slate-600 hover:bg-slate-200 transition" data-t="cancel">Cancel</button>
        </div>
    </div>
</div>

<script>
let currentShareLink = '';

function openShareMenu(postId) {
    currentShareLink = window.location.origin + window.location.pathname + '#post-' + postId;
    document.getElementById('shareModal').classList.remove('hidden');
    document.getElementById('shareModal').classList.add('flex');
}

function closeShareMenu() {
    document.getElementById('shareModal').classList.add('hidden');
    document.getElementById('shareModal').classList.remove('flex');
}

function copyLink() {
    navigator.clipboard.writeText(currentShareLink);
    alert('Link Copied Successfully!');
}

function shareFacebook() {
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(currentShareLink), '_blank');
}

function shareTelegram() {
    window.open('https://t.me/share/url?url=' + encodeURIComponent(currentShareLink), '_blank');
}

window.onclick = function(event) {
    if (event.target === document.getElementById('shareModal')) {
        closeShareMenu();
    }
}

/* AJAX Like */
function toggleLike(postId) {
    var formData = new FormData();
    formData.append('post_id', postId);

    fetch('api_like.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.error) return;

        var btn = document.getElementById('like-btn-' + postId);
        var icon = document.getElementById('like-icon-' + postId);
        var info = document.getElementById('like-info-' + postId);
        var count = document.getElementById('like-count-' + postId);

        if (data.liked) {
            btn.classList.add('text-cyan-600', 'bg-cyan-50');
            btn.classList.remove('text-slate-500');
            icon.classList.remove('fa-regular');
            icon.classList.add('fa-solid');
        } else {
            btn.classList.remove('text-cyan-600', 'bg-cyan-50');
            btn.classList.add('text-slate-500');
            icon.classList.remove('fa-solid');
            icon.classList.add('fa-regular');
        }

        count.textContent = data.count;

        if (data.count > 0) {
            info.classList.remove('hidden');
        } else {
            info.classList.add('hidden');
        }
    });
}

/* AJAX Comment */
function submitComment(e, postId) {
    e.preventDefault();

    var input = document.getElementById('comment-input-' + postId);
    var text = input.value.trim();

    if (!text) return;

    var formData = new FormData();
    formData.append('post_id', postId);
    formData.append('comment', text);

    fetch('api_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.error) return;

        var list = document.getElementById('comments-list-' + postId);
        var info = document.getElementById('comment-info-' + postId);
        var countEl = document.getElementById('comment-count-' + postId);

        var html = '<div class="mt-3 flex gap-2">' +
            '<div class="flex-1 bg-slate-50 rounded-2xl px-4 py-3">' +
            '<div class="flex items-center justify-between">' +
            '<b class="text-sm text-slate-700">' + data.name + '</b>' +
            '<span class="text-xs text-slate-400">' + data.time + '</span>' +
            '</div>' +
            '<p class="mt-1 text-sm text-slate-600">' + data.comment + '</p>' +
            '</div></div>';

        list.insertAdjacentHTML('afterbegin', html);

        input.value = '';

        countEl.textContent = data.count;
        info.classList.remove('hidden');
    });
}

/* Calendar Widget */
(function() {
    var cal = document.getElementById('calendarWidget');
    if (!cal) return;

    var now = new Date();
    var currentMonth = now.getMonth();
    var currentYear = now.getFullYear();

    function renderCalendar() {
        var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var days = ['Su','Mo','Tu','We','Th','Fr','Sa'];
        var firstDay = new Date(currentYear, currentMonth, 1).getDay();
        var daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        var today = new Date();

        var html = '<div class="flex items-center justify-between mb-3">';
        html += '<button onclick="calPrev()" class="h-7 w-7 rounded-full hover:bg-cyan-50 text-slate-500 text-xs">&lt;</button>';
        html += '<span class="font-bold text-sm text-teal-700">' + months[currentMonth] + ' ' + currentYear + '</span>';
        html += '<button onclick="calNext()" class="h-7 w-7 rounded-full hover:bg-cyan-50 text-slate-500 text-xs">&gt;</button>';
        html += '</div>';

        html += '<div class="grid grid-cols-7 gap-1 text-center text-xs">';
        days.forEach(function(d) { html += '<div class="font-bold text-slate-400 py-1">' + d + '</div>'; });

        for (var i = 0; i < firstDay; i++) { html += '<div></div>'; }

        for (var d = 1; d <= daysInMonth; d++) {
            var isToday = d === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear();
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

<?php include "../include/footer.php"; ?>

</body>
</html>


<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

$currentUserId = (int) $_SESSION['user_id'];
$myMessagesSearch = trim($_GET['search'] ?? '');

$limit = 5;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$baseWhere = "user_id = ?";
$searchKeyword = null;

if ($myMessagesSearch !== '') {
    $baseWhere .= " AND (subject LIKE ? OR message LIKE ? OR reply_message LIKE ?)";
    $searchKeyword = '%' . $myMessagesSearch . '%';
}

$countStmtSql = "SELECT COUNT(*) total FROM contact_messages WHERE {$baseWhere}";
$countStmt = $conn->prepare($countStmtSql);
if ($myMessagesSearch !== '') {
    $countStmt->bind_param('isss', $currentUserId, $searchKeyword, $searchKeyword, $searchKeyword);
} else {
    $countStmt->bind_param('i', $currentUserId);
}
$countStmt->execute();
$totalMessages = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalMessages / $limit));

$listSql = "
    SELECT subject, message, reply_message, replied_at, is_read, created_at
    FROM contact_messages
    WHERE {$baseWhere}
    ORDER BY created_at DESC
    LIMIT ?, ?
";
$listStmt = $conn->prepare($listSql);
if ($myMessagesSearch !== '') {
    $listStmt->bind_param('isssii', $currentUserId, $searchKeyword, $searchKeyword, $searchKeyword, $offset, $limit);
} else {
    $listStmt->bind_param('iii', $currentUserId, $offset, $limit);
}
$listStmt->execute();
$myMessages = $listStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-slate-50">

<?php include "../include/user_header.php"; ?>

<div class="max-w-7xl mx-auto px-4 py-8">

<?php if(isset($_GET['success'])): ?>

<div class="rounded-xl border border-green-200 bg-green-50 p-4 mb-6 text-green-700 flex items-center gap-3">
    <i class="fa-solid fa-circle-check text-lg"></i>
    <span class="font-semibold">Message sent successfully.</span>
</div>

<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-8">


<!-- LEFT -->

<div class="space-y-6">

    <div class="bg-white rounded-3xl p-6 shadow">

        <h2 class="text-2xl font-bold mb-6">
            Contact Information
        </h2>

        <div class="space-y-5">

            <div class="flex items-center gap-4">
                <i class="fa-solid fa-envelope text-cyan-500 text-xl"></i>
                <span>ucs.htd@gmail.com</span>
            </div>

            <div class="flex items-center gap-4">
                <i class="fa-solid fa-phone text-cyan-500 text-xl"></i>
                <span>09783453901</span>
            </div>

            <div class="flex items-center gap-4">
                <i class="fa-solid fa-location-dot text-cyan-500 text-xl"></i>
                <span>University of Computer Studies, Hinthada</span>
            </div>

        </div>

    </div>

    <div class="bg-white rounded-3xl p-6 shadow">

        <h2 class="text-2xl font-bold mb-5">
            Send Message
        </h2>

        <form action="save_contact.php" method="POST">

            <label class="ui-label">Your Name</label>
            <input
            type="text"
            name="name"
            placeholder="Your Name"
            required
            class="input-base mb-4">

            <label class="ui-label">Your Email</label>
            <input
            type="email"
            name="email"
            placeholder="Your Email"
            required
            class="input-base mb-4">

            <label class="ui-label">Subject</label>
            <input
            type="text"
            name="subject"
            placeholder="Subject"
            required
            class="input-base mb-4">

            <label class="ui-label">Message</label>
            <textarea
            name="message"
            rows="5"
            placeholder="Write your message..."
            required
            class="input-base mb-4"></textarea>

            <button
            type="submit"
            class="btn btn-primary btn-block">

                <i class="fa-solid fa-paper-plane"></i> Send Message

            </button>

        </form>

    </div>

</div>


<!-- RIGHT -->

<div>

    <div class="bg-white rounded-3xl p-4 shadow">

        <h2 class="text-2xl font-bold mb-4">
            Location
        </h2>

        <iframe
        src="https://maps.google.com/maps?q=University%20of%20Computer%20Studies%20Hinthada&t=&z=15&ie=UTF8&iwloc=&output=embed"
        class="w-full h-[500px] rounded-2xl border"
        loading="lazy">
        </iframe>

    </div>

</div>

</div>

<div class="mt-8 bg-white rounded-3xl p-6 shadow">

    <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
        <h2 class="text-2xl font-bold">My Messages</h2>
        <form method="GET" class="max-w-[320px] w-full">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($myMessagesSearch) ?>" placeholder="Search your messages..." class="input-base">
            </div>
        </form>
    </div>

    <?php if ($myMessages->num_rows > 0): ?>
        <div class="space-y-3">
            <?php while ($msg = $myMessages->fetch_assoc()): ?>
                <div class="rounded-2xl border border-cyan-100 bg-slate-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="badge badge-cyan"><?= htmlspecialchars($msg['subject']) ?></span>
                                <?php if (!empty($msg['replied_at'])): ?>
                                    <span class="badge badge-green">Replied</span>
                                <?php else: ?>
                                    <span class="badge badge-slate">Pending</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-slate-700 leading-6">
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            </p>
                        </div>
                        <div class="text-xs text-slate-400 whitespace-nowrap">
                            <?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?>
                        </div>
                    </div>

                    <div class="mt-3 border-t border-white pt-3 text-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Admin Reply</p>
                        <p class="mt-1 text-slate-700 leading-6">
                            <?php if (!empty($msg['reply_message'])): ?>
                                <?= nl2br(htmlspecialchars($msg['reply_message'])) ?>
                            <?php else: ?>
                                <span class="text-slate-400">No reply yet</span>
                            <?php endif; ?>
                        </p>
                        <p class="mt-2 text-xs text-slate-500">
                            Reply Date &amp; Time:
                            <span class="font-semibold text-slate-700">
                                <?= !empty($msg['replied_at']) ? date('M d, Y h:i A', strtotime($msg['replied_at'])) : '-' ?>
                            </span>
                        </p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($totalMessages > 0): ?>
            <div class="mt-4 flex flex-col items-center gap-2">
                <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span>Showing <strong class="text-slate-700"><?= $offset + 1 ?>&ndash;<?= min($offset + $limit, $totalMessages) ?></strong> of <strong class="text-slate-700"><?= $totalMessages ?></strong></span>
                </div>
                <div class="pagination">
                    <?php $qs = $myMessagesSearch !== '' ? 'search=' . urlencode($myMessagesSearch) . '&' : ''; ?>
                    <?php if ($page > 1): ?>
                        <a href="?<?= $qs ?>page=<?= $page - 1 ?>"><i class="fa-solid fa-chevron-left text-xs"></i> Previous</a>
                    <?php else: ?>
                        <span class="disabled"><i class="fa-solid fa-chevron-left text-xs"></i> Previous</span>
                    <?php endif; ?>
                    <?php $startPage = max(1, $page - 2); $endPage = min($totalPages, $page + 2); ?>
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?<?= $qs ?>page=<?= $i ?>" class="<?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= $qs ?>page=<?= $page + 1 ?>">Next <i class="fa-solid fa-chevron-right text-xs"></i></a>
                    <?php else: ?>
                        <span class="disabled">Next <i class="fa-solid fa-chevron-right text-xs"></i></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php
        $es_icon = 'fa-regular fa-envelope';
        $es_title = 'No messages yet';
        $es_message = 'Your contact messages and admin replies will appear here.';
        $es_action = '';
        include '../include/empty_state.php';
        ?>
    <?php endif; ?>

</div>

</div>

<?php include "../include/footer.php"; ?>

</body>
</html>

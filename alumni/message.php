<?php
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
$current_user_id = (int) $_SESSION["user_id"];
$receiver_id = (int) ($_GET['user_id'] ?? 0);

/* Mark Messages As Read */

if ($receiver_id > 0) {

    $update = $conn->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE receiver_id = ?
        AND sender_id = ?
    ");

    $update->bind_param(
        "ii",
        $current_user_id,
        $receiver_id
    );

    $update->execute();
}

/* Users List */

$stmt = $conn->prepare("
SELECT
    u.id,
    u.name,
    u.profile_image,

    (
        SELECT COUNT(*)
        FROM messages m
        WHERE m.sender_id = u.id
        AND m.receiver_id = ?
        AND m.is_read = 0
    ) AS unread_count,

    (
        SELECT message
        FROM messages m2
        WHERE
            (m2.sender_id = u.id AND m2.receiver_id = ?)
            OR
            (m2.sender_id = ? AND m2.receiver_id = u.id)
        ORDER BY m2.created_at DESC
        LIMIT 1
    ) AS last_message

FROM users u

WHERE u.id != ?
AND u.role = 'user'

ORDER BY u.name ASC
");

$stmt->bind_param(
    "iiii",
    $current_user_id,
    $current_user_id,
    $current_user_id,
    $current_user_id
);

$stmt->execute();

$users = $stmt->get_result();
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Messages</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-slate-100">

    <?php include "../include/user_header.php"; ?>

    <div class="max-w-7xl mx-auto p-2">

        <div class="bg-white rounded-3xl shadow overflow-hidden">

            <div class="grid lg:grid-cols-4 h-auto">

                <div class="border-r overflow-y-auto">

                    <div class="p-2 border-b">

                        <h2 class="font-bold text-xl">
                            Messages
                        </h2>

                        <!-- <input type="text" placeholder="Search alumni..."
                            class="mt-3 w-full border rounded-xl px-4 py-2"> -->

                    </div>

                    <?php while ($user = $users->fetch_assoc()): ?>

                        <a href="?user_id=<?= $user['id'] ?>" class="flex items-center gap-3 p-4 border-b hover:bg-slate-50
<?= $receiver_id == $user['id']
            ? 'bg-cyan-50'
            : '' ?>">

                            <img src="<?= !empty($user['profile_image'])
                                ? $user['profile_image']
                                : '../images/default-avatar.svg' ?>" class="w-12 h-12 rounded-full object-cover cursor-zoom-in"
                                onclick="openLightbox(this.src, '<?= htmlspecialchars(addslashes($user['name'])) ?>')">

                            <div class="flex-1 min-w-0">

                                <div class="flex items-center justify-between">

                                    <h3 class="font-semibold truncate">
                                        <?= htmlspecialchars($user['name']) ?>
                                    </h3>

                                    <?php if ($user['unread_count'] > 0): ?>

                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">

                                            <?= $user['unread_count'] ?>

                                        </span>

                                    <?php endif; ?>

                                </div>

                                <p class="text-xs text-slate-400 truncate">

                                    <?= !empty($user['last_message'])
                                        ? htmlspecialchars(substr($user['last_message'], 0, 30))
                                        : 'No messages yet' ?>

                                </p>

                            </div>

                        </a>

                    <?php endwhile; ?>

                </div>

                <div class="lg:col-span-3 flex flex-col h-[700px]">


                    <?php if ($receiver_id == 0): ?>

                        <div class="flex-1 flex items-center justify-center">

                            <?php
                            $es_icon    = 'fa-regular fa-comment';
                            $es_title   = 'No conversation selected';
                            $es_message = 'Choose an alumni from the list to start chatting.';
                            include '../include/empty_state.php';
                            ?>

                        </div>

                    <?php else:

                        $userStmt = $conn->prepare("
SELECT name
FROM users
WHERE id = ?
");

                        $userStmt->bind_param("i", $receiver_id);
                        $userStmt->execute();

                        $receiver =
                            $userStmt->get_result()->fetch_assoc();

                        $msgStmt = $conn->prepare("
SELECT *
FROM messages
WHERE
(sender_id=? AND receiver_id=?)
OR
(sender_id=? AND receiver_id=?)
ORDER BY created_at ASC
");

                        $msgStmt->bind_param(
                            "iiii",
                            $current_user_id,
                            $receiver_id,
                            $receiver_id,
                            $current_user_id
                        );

                        $msgStmt->execute();

                        $messages =
                            $msgStmt->get_result();
                        ?>


                        <div class="border-b p-4 flex items-center gap-3">

                            <a href="message.php" class="text-cyan-600 text-xl">

                                <i class="fa-solid fa-arrow-left"></i>

                            </a>

                            <h3 class="font-bold">

                                <?= htmlspecialchars($receiver['name']) ?>

                            </h3>

                        </div>



                        <div id="chatBox" class="flex-1 overflow-y-auto p-4 space-y-3">

                            <?php while ($msg = $messages->fetch_assoc()): ?>

                                <div class="<?= $msg['sender_id'] == $current_user_id
                                    ? 'text-right'
                                    : 'text-left' ?>">

                                    <div class="inline-block max-w-[70%]">

                                        <div class="px-4 py-2 rounded-2xl
<?= $msg['sender_id'] == $current_user_id
                    ? 'bg-cyan-500 text-white'
                    : 'bg-slate-200 text-slate-800' ?>">

                                            <?= htmlspecialchars($msg['message']) ?>

                                        </div>

                                        <div class="text-xs text-slate-400 mt-1">

                                            <?= date(
                                                'Y-m-d H:i:s'
                                            ) ?>

                                        </div>

                                    </div>

                                </div>

                            <?php endwhile; ?>

                        </div>

                        <form onsubmit="sendMessage(event)" data-async-form="true" class="border-t bg-white p-4 flex gap-3 shrink-0">

                            <input type="hidden" name="receiver_id" id="receiver_id" value="<?= $receiver_id ?>">

                            <input type="text" name="message" id="messageInput" required placeholder="Type message..."
                                class="input-base">

                            <button type="submit" class="btn btn-primary">

                                <i class="fa-solid fa-paper-plane"></i> Send

                            </button>

                        </form>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <script>
        var lastMessageId = <?= $receiver_id > 0 ? 'getLastMsgId()' : '0' ?>;
        var chatReceiverId = <?= $receiver_id ?>;
        var currentUserId = <?= $current_user_id ?>;

        function getLastMsgId() {
            var chatBox = document.getElementById('chatBox');
            if (!chatBox) return 0;
            var msgs = chatBox.querySelectorAll('[data-msg-id]');
            if (msgs.length === 0) return 0;
            return parseInt(msgs[msgs.length - 1].getAttribute('data-msg-id'));
        }

        function scrollToBottom() {
            var chatBox = document.getElementById('chatBox');
            if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
        }

        /* Send Message via AJAX */
        function sendMessage(e) {
            e.preventDefault();

            var form = e.target;
            var input = document.getElementById('messageInput');
            var text = input.value.trim();
            var receiverId = document.getElementById('receiver_id').value;

            if (!text || !receiverId) return;

            var lockKey = 'message-' + receiverId;
            if (!lockRequest(lockKey)) return;

            setSubmitButtonsState(form, true);

            var formData = new FormData();
            formData.append('receiver_id', receiverId);
            formData.append('message', text);

            fetch('api_send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.error) return;

                var chatBox = document.getElementById('chatBox');
                var html = '<div class="text-right" data-msg-id="' + data.message_id + '">' +
                    '<div class="inline-block max-w-[70%]">' +
                    '<div class="px-4 py-2 rounded-2xl bg-cyan-500 text-white">' + data.message + '</div>' +
                    '<div class="text-xs text-slate-400 mt-1">' + data.time + '</div>' +
                    '</div></div>';

                chatBox.insertAdjacentHTML('beforeend', html);
                input.value = '';
                lastMessageId = data.message_id;
                scrollToBottom();
            })
            .catch(function () {
            })
            .finally(function () {
                unlockRequest(lockKey);
                setSubmitButtonsState(form, false);
            });
        }

        /* Poll for new messages */
        <?php if ($receiver_id > 0): ?>
        setInterval(function() {
            fetch('api_get_messages.php?user_id=' + chatReceiverId + '&last_id=' + lastMessageId)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data.messages || data.messages.length === 0) return;

                var chatBox = document.getElementById('chatBox');

                data.messages.forEach(function(msg) {
                    var isMine = msg.sender_id == currentUserId;
                    var bgClass = isMine ? 'bg-cyan-500 text-white' : 'bg-slate-200 text-slate-800';
                    var alignClass = isMine ? 'text-right' : 'text-left';

                    var html = '<div class="' + alignClass + '" data-msg-id="' + msg.id + '">' +
                        '<div class="inline-block max-w-[70%]">' +
                        '<div class="px-4 py-2 rounded-2xl ' + bgClass + '">' + msg.message + '</div>' +
                        '<div class="text-xs text-slate-400 mt-1">' + msg.time + '</div>' +
                        '</div></div>';

                    chatBox.insertAdjacentHTML('beforeend', html);
                    lastMessageId = msg.id;
                });

                scrollToBottom();
            });
        }, 3000);
        <?php endif; ?>

        scrollToBottom();
    </script>

<?php include "../include/footer.php"; ?>

</body>

</html>


<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'admin'
) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/db.php";

$id = (int)($_GET['id'] ?? 0);

/* Mark As Read */

$update = $conn->prepare("
UPDATE contact_messages
SET is_read = 1
WHERE id = ?
");

$update->bind_param("i", $id);
$update->execute();

/* Get Message */

$stmt = $conn->prepare("
SELECT *
FROM contact_messages
WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$message = $stmt->get_result()->fetch_assoc();

if (!$message) {

    die("Message not found.");

}
?>



<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>View Contact Message</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<div class="min-h-screen flex">

<?php include "../include/admin_header.php"; ?>

<div class="flex-1 p-6">

    <div class="max-w-5xl mx-auto">

        <!-- Header -->

        <div class="flex items-center justify-between mb-6">

            <div>

                <h1 class="text-3xl font-bold text-teal-700">

                    Contact Message

                </h1>

                <p class="text-slate-500 mt-1">

                    View full contact message

                </p>

            </div>

            <a href="contacts.php"
               class="rounded-xl bg-slate-200
               px-5 py-3 font-semibold">

                Back

            </a>

        </div>

        <!-- Card -->

        <div class="bg-white rounded-3xl shadow overflow-hidden">

            <div class="p-6 border-b">

                <div class="flex items-center gap-3">

                    <h2 class="text-2xl font-bold">

                        <?= htmlspecialchars($message['subject']) ?>

                    </h2>

                    <span
                    class="bg-green-100 text-green-600
                    px-3 py-1 rounded-full text-xs">

                        Read

                    </span>

                </div>

            </div>

            <div class="p-6 space-y-6">

                <div>

                    <label
                    class="block text-sm text-slate-500">

                        Name

                    </label>

                    <p
                    class="text-lg font-semibold mt-1">

                        <?= htmlspecialchars($message['name']) ?>

                    </p>

                </div>

                <div>

                    <label
                    class="block text-sm text-slate-500">

                        Email

                    </label>

                    <p
                    class="font-semibold mt-1">

                        <?= htmlspecialchars($message['email']) ?>

                    </p>

                </div>

                <div>

                    <label
                    class="block text-sm text-slate-500">

                        Message

                    </label>

                    <div
                    class="mt-2 bg-slate-50 rounded-2xl p-5 leading-7">

                        <?= nl2br(
                            htmlspecialchars(
                                $message['message']
                            )
                        ) ?>

                    </div>

                </div>

                <div>

                    <label
                    class="block text-sm text-slate-500">

                        Sent Date

                    </label>

                    <p class="font-semibold mt-1">

                        <?= date(
                            "M d, Y h:i A",
                            strtotime(
                                $message['created_at']
                            )
                        ) ?>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>


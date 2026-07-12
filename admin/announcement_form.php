<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'admin'
) {
    header("Location: ../alumni/login.php");
    exit;
}

require_once "../config/db.php";

$upload_dir = "../uploads/announcements/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$id = (int) ($_GET['id'] ?? 0);
$edit = null;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $type = $_POST["type"] === "event" ? "event" : "announcement";
    $event_date = trim($_POST["event_date"] ?? "") ?: null;
    $event_time = trim($_POST["event_time"] ?? "") ?: null;
    $location = trim($_POST["location"] ?? "") ?: null;
    $description = trim($_POST["description"] ?? "") ?: null;

    if ($title === "") {
        $error = "Title is required.";
    }

    $image_path = $edit['image'] ?? "";

    if ($error === "" && !empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES["image"]["tmp_name"];
        $file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "webp", "gif"];

        if (!in_array($file_ext, $allowed)) {
            $error = "Invalid image type. Only JPG, JPEG, PNG, WEBP, GIF allowed.";
        } else {
            $new_name = "ann_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
            $dest = $upload_dir . $new_name;
            if (move_uploaded_file($file_tmp, $dest)) {
                if (!empty($edit['image']) && file_exists($edit['image'])) {
                    unlink($edit['image']);
                }
                $image_path = $dest;
            } else {
                $error = "Failed to upload image.";
            }
        }
    }

    if ($error === "") {
        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE announcements
                SET type=?, title=?, image=?, event_date=?, event_time=?, location=?, description=?, updated_at=NOW()
                WHERE id=?
            ");
            $stmt->bind_param(
                "sssssssi",
                $type, $title, $image_path, $event_date, $event_time, $location, $description, $id
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO announcements (type, title, image, event_date, event_time, location, description)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssss",
                $type, $title, $image_path, $event_date, $event_time, $location, $description
            );
        }

        if ($stmt->execute()) {
            header("Location: announcements.php");
            exit;
        } else {
            $error = "Failed to save. Please try again.";
        }
    }
}

function v($key)
{
    global $edit;
    return $edit[$key] ?? "";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $id > 0 ? "Edit" : "New" ?> Announcement / Event</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

    <div class="min-h-screen flex">

        <?php include "../include/admin_header.php"; ?>

        <div class="flex-1 p-6 flex flex-col">

            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-teal-700">
                    <?= $id > 0 ? "Edit" : "New" ?> Announcement / Event
                </h1>
                <a href="announcements.php" class="text-sm text-slate-500 hover:text-teal-700">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Back
                </a>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data"
                class="bg-white rounded-2xl shadow p-6 max-w-2xl space-y-5">

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Type</label>
                    <select name="type"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        <option value="announcement" <?= v('type') === 'announcement' || v('type') === '' ? 'selected' : '' ?>>Announcement</option>
                        <option value="event" <?= v('type') === 'event' ? 'selected' : '' ?>>Event</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required value="<?= htmlspecialchars(v('title')) ?>"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"
                        placeholder="Enter title">
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Date</label>
                        <input type="date" name="event_date" value="<?= htmlspecialchars(v('event_date')) ?>"
                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Time</label>
                        <input type="time" name="event_time" value="<?= htmlspecialchars(v('event_time')) ?>"
                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars(v('location')) ?>"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"
                        placeholder="e.g. Main Hall, Campus">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Image</label>
                    <?php if (!empty($edit['image'])): ?>
                        <img src="<?= htmlspecialchars($edit['image']) ?>" alt="current"
                            class="h-32 w-48 object-cover rounded-xl mb-2 border">
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Description</label>
                    <textarea name="description" rows="5"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"
                        placeholder="Write details here..."><?= htmlspecialchars(v('description')) ?></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                        class="rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-700 transition">
                        <i class="fa-solid fa-save mr-1"></i> Save
                    </button>
                    <a href="announcements.php"
                        class="rounded-xl bg-slate-100 px-6 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>

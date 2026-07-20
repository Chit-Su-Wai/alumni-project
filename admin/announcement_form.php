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
            $savedId = $id > 0 ? $id : (int) $conn->insert_id;
            admin_log_activity(
                $conn,
                $id > 0 ? 'Update Announcement' : 'Add Announcement',
                ($id > 0 ? 'Updated' : 'Added') . ' announcement: ' . $title,
                'announcement',
                $savedId
            );

            if ($id === 0) {
                push_notification_to_users(
                    $conn,
                    'announcement',
                    'New Announcement',
                    $title,
                    'announcements.php'
                );
            }
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

            <div class="admin-page-head">
                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    <div>
                        <h1 class="admin-page-title">
                            <?= $id > 0 ? "Edit" : "New" ?> Announcement / Event
                        </h1>
                        <p class="admin-page-sub">Create or update announcements and events</p>
                    </div>
                </div>
                <a href="announcements.php" class="text-sm text-slate-500 hover:text-teal-700 no-print">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Back
                </a>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data"
                class="admin-form-card max-w-2xl space-y-5">

                <h3 class="form-section-title"><i class="fa-solid fa-circle-info"></i> Details</h3>

                <div>
                    <label class="ui-label">Type</label>
                    <select name="type"
                        class="input-base">
                        <option value="announcement" <?= v('type') === 'announcement' || v('type') === '' ? 'selected' : '' ?>>Announcement</option>
                        <option value="event" <?= v('type') === 'event' ? 'selected' : '' ?>>Event</option>
                    </select>
                </div>

                <div>
                    <label class="ui-label">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required value="<?= htmlspecialchars(v('title')) ?>"
                        class="input-base"
                        placeholder="Enter title">
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="ui-label">Date</label>
                        <input type="date" name="event_date" value="<?= htmlspecialchars(v('event_date')) ?>"
                            class="input-base">
                    </div>
                    <div>
                        <label class="ui-label">Time</label>
                        <input type="time" name="event_time" value="<?= htmlspecialchars(v('event_time')) ?>"
                            class="input-base">
                    </div>
                </div>

                <div>
                    <label class="ui-label">Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars(v('location')) ?>"
                        class="input-base"
                        placeholder="e.g. Main Hall, Campus">
                </div>

                <h3 class="form-section-title pt-2"><i class="fa-solid fa-image"></i> Media</h3>

                <div>
                    <label class="ui-label">Image</label>
                    <?php if (!empty($edit['image'])): ?>
                        <img src="<?= htmlspecialchars($edit['image']) ?>" alt="current"
                            class="h-32 w-48 object-cover rounded-xl mb-2 border-2 border-cyan-100 shadow-sm cursor-zoom-in"
                            onclick="openLightbox(this.src, 'Current image')">
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*"
                        class="input-base">
                </div>

                <h3 class="form-section-title pt-2"><i class="fa-solid fa-align-left"></i> Content</h3>

                <div>
                    <label class="ui-label">Description</label>
                    <textarea name="description" rows="5"
                        class="input-base"
                        placeholder="Write details here..."><?= htmlspecialchars(v('description')) ?></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                        class="btn btn-primary">
                        <i class="fa-solid fa-save mr-1"></i> Save
                    </button>
                    <a href="announcements.php"
                        class="btn btn-ghost">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>

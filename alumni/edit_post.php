
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";
require_once "../include/request_guard.php";

$user_id = $_SESSION["user_id"];
$post_id = (int)($_GET["id"] ?? 0);

/* Get Post */

$stmt = $conn->prepare(
    "SELECT *
     FROM posts
     WHERE id = ?
     AND user_id = ?"
);

$stmt->bind_param(
    "ii",
    $post_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: feed.php");
    exit;
}

$post = $result->fetch_assoc();

$error = "";

/* Update */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $content = trim($_POST["content"]);
    $category = trim($_POST["category"]);

    if (empty($content)) {

        $error = "Content is required.";

    } else {

        if (request_guard_is_duplicate('edit_post', [
            'user_id' => $user_id,
            'post_id' => $post_id,
            'content' => $content,
            'category' => $category,
            'images' => array_map('basename', $_FILES['images']['name'] ?? []),
        ])) {
            $error = "Please wait and submit the changes only once.";
        } else {

            $update = $conn->prepare(
                "UPDATE posts
                 SET content = ?,
                     category = ?
                 WHERE id = ?"
            );

        $update->bind_param(
            "ssi",
            $content,
            $category,
            $post_id
        );

            $update->execute();

        /* Upload New Images */

            if (!empty($_FILES['images']['name'][0])) {

            $uploadDir = "../uploads/posts/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach (
                $_FILES['images']['tmp_name']
                as $key => $tmpName
            ) {

                if (
                    $_FILES['images']['error'][$key] == 0
                ) {

                    $fileName =
                        time() . "_" .
                        basename(
                            $_FILES['images']['name'][$key]
                        );

                    $targetFile =
                        $uploadDir . $fileName;

                    if (
                        move_uploaded_file(
                            $tmpName,
                            $targetFile
                        )
                    ) {

                        $imgStmt =
                            $conn->prepare(
                                "INSERT INTO post_images
                                (post_id,image)
                                VALUES (?,?)"
                            );

                        $imgStmt->bind_param(
                            "is",
                            $post_id,
                            $targetFile
                        );

                        $imgStmt->execute();
                    }
                }
            }
        }

            header("Location: feed.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Edit Post</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<?php include "../include/user_header.php"; ?>

<div class="max-w-3xl mx-auto px-4 py-8">

<div class="bg-white rounded-3xl p-6 shadow-sm">

<div class="flex justify-between items-center mb-6">

<h1 class="text-2xl font-bold">
Edit Post
</h1>

<a
href="feed.php"
class="btn btn-ghost btn-sm">

<i class="fa-solid fa-arrow-left"></i> Back

</a>

</div>

<?php if($error): ?>

<div
class="mb-4 rounded-xl bg-red-50 p-4 text-red-600">

<?= $error ?>

</div>

<?php endif; ?>

<form
method="POST"
enctype="multipart/form-data"
class="space-y-5">

<div>

<label class="ui-label">
Category
</label>

<select
name="category"
class="input-base">

<option value="General"
<?= $post['category']=='General' ? 'selected' : '' ?>>
General
</option>

<option value="Job"
<?= $post['category']=='Job' ? 'selected' : '' ?>>
Job
</option>

<option value="Event"
<?= $post['category']=='Event' ? 'selected' : '' ?>>
Event
</option>

<option value="News"
<?= $post['category']=='News' ? 'selected' : '' ?>>
News
</option>

</select>

</div>

<div>

<label class="ui-label">
Content
</label>

<textarea
name="content"
rows="6"
required
class="input-base"><?= htmlspecialchars($post['content']) ?></textarea>

</div>

<!-- Current Images -->

<div>

<label class="ui-label">
Current Photos
</label>

<div class="grid grid-cols-2 gap-3">

<?php

$imgs = mysqli_query(
    $conn,
    "SELECT *
     FROM post_images
     WHERE post_id = {$post_id}"
);

while($img = mysqli_fetch_assoc($imgs)):

?>

<img
src="<?= $img['image'] ?>"
class="h-40 w-full rounded-xl object-cover cursor-zoom-in"
onclick="openLightbox('<?= htmlspecialchars($img['image'], ENT_QUOTES) ?>', 'Current photo')">

<?php endwhile; ?>

</div>

</div>

<!-- Add New Images -->

<div>

<label class="ui-label">
Add More Photos
</label>

<input
type="file"
name="images[]"
multiple
accept="image/*"
class="input-base">

</div>

<button
type="submit"
class="btn btn-primary btn-block">

<i class="fa-solid fa-floppy-disk"></i> Update Post

</button>

</form>

</div>

</div>

<?php include "../include/footer.php"; ?>

</body>
</html>

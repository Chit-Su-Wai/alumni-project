
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";
require_once "../include/request_guard.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION["user_id"];
    $content = trim($_POST["content"]);
    $category = trim($_POST["category"]);

    if (empty($content)) {

        $error = "Please enter post content.";

    } else {

        if (request_guard_is_duplicate('create_post', [
            'user_id' => $user_id,
            'content' => $content,
            'category' => $category,
            'images' => array_map('basename', $_FILES['images']['name'] ?? []),
        ])) {
            $error = "Please wait and submit the post only once.";
        } else {

            /* Create Post */

            $stmt = $conn->prepare(
                "INSERT INTO posts
                (user_id, content, category)
                VALUES (?, ?, ?)"
            );

        $stmt->bind_param(
            "iss",
            $user_id,
            $content,
            $category
        );

            if ($stmt->execute()) {

            $post_id = $conn->insert_id;

            /* Upload Multiple Images */

            if (!empty($_FILES['images']['name'][0])) {

                $uploadDir = "../uploads/posts/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {

                    if ($_FILES['images']['error'][$key] == 0) {

                        $fileName =
                            time() . "_" .
                            basename($_FILES['images']['name'][$key]);

                        $targetFile =
                            $uploadDir . $fileName;

                        if (
                            move_uploaded_file(
                                $tmpName,
                                $targetFile
                            )
                        ) {

                            $imgStmt = $conn->prepare(
                                "INSERT INTO post_images
                                (post_id, image)
                                VALUES (?, ?)"
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
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Create Post</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<?php include "../include/user_header.php"; ?>

<div class="max-w-3xl mx-auto px-4 py-8">

<div class="bg-white rounded-3xl p-6 shadow-sm">

<div class="flex items-center justify-between mb-6">

<h1 class="text-2xl font-bold">
Create Post
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

<label
class="ui-label">

Category

</label>

<select
name="category"
required
class="input-base">

<option value="General">
General
</option>

<option value="Job">
Job
</option>

<option value="Event">
Event
</option>

<option value="News">
News
</option>

</select>

</div>

<div>

<label
class="ui-label">

Content

</label>

<textarea
name="content"
rows="6"
required
placeholder="What's on your mind?"
class="input-base"></textarea>

</div>

<div>

<label
class="ui-label">

Upload Photos

</label>

<input
type="file"
name="images[]"
multiple
accept="image/*"
class="input-base">

<p class="mt-2 text-sm text-slate-500">
You can select multiple photos.
</p>

</div>

<button
type="submit"
class="btn btn-primary btn-block">

<i class="fa-solid fa-paper-plane"></i> Post

</button>

</form>

</div>

</div>

<?php include "../include/footer.php"; ?>

</body>
</html>

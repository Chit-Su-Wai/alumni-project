
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

if (!isset($_GET["id"])) {
    header("Location: feed.php");
    exit;
}

$post_id = (int) $_GET["id"];
$user_id = $_SESSION["user_id"];

/* Check Post Owner */

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

/* Delete Image */

if (
    !empty($post["image"]) &&
    file_exists($post["image"])
) {
    unlink($post["image"]);
}

/* Delete Post */

$delete = $conn->prepare(
    "DELETE FROM posts
     WHERE id = ?"
);

$delete->bind_param(
    "i",
    $post_id
);

$delete->execute();

header("Location: feed.php");
exit;
?>


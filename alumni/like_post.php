
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$post_id = (int)($_GET["post_id"] ?? 0);

if ($post_id <= 0) {
    header("Location: feed.php");
    exit;
}

/* Check Like Exists */

$check = $conn->prepare(
    "SELECT id
     FROM post_likes
     WHERE post_id = ?
     AND user_id = ?"
);

$check->bind_param(
    "ii",
    $post_id,
    $user_id
);

$check->execute();

$result = $check->get_result();

/* Unlike */

if ($result->num_rows > 0) {

    $delete = $conn->prepare(
        "DELETE FROM post_likes
         WHERE post_id = ?
         AND user_id = ?"
    );

    $delete->bind_param(
        "ii",
        $post_id,
        $user_id
    );

    $delete->execute();

}

/* Like */

else {

    $insert = $conn->prepare(
        "INSERT INTO post_likes
        (post_id, user_id)
        VALUES (?, ?)"
    );

    $insert->bind_param(
        "ii",
        $post_id,
        $user_id
    );

    $insert->execute();
}

header("Location: feed.php");
exit;
?>


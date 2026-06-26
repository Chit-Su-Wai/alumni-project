
<?php
session_start();

require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    exit;
}

$current_user_id = (int)$_SESSION["user_id"];

$position = $_GET['position'] ?? '';

$stmt = $conn->prepare("
    SELECT
        users.id,
        users.name,
        users.email,
        users.profile_image,
        jobs.company,
        jobs.location,
        jobs.position
    FROM jobs
    INNER JOIN users
        ON jobs.user_id = users.id
    WHERE jobs.position = ?
    AND users.id != ?
    AND users.role = 'user'
");

$stmt->bind_param(
    "si",
    $position,
    $current_user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '
    <div class="text-center text-slate-500 py-4">
        No alumni found
    </div>';
}

while ($row = $result->fetch_assoc()):
?>

<div class="rounded-2xl border p-4 mb-3 hover:bg-slate-50">

    <div class="flex items-center gap-4">

        <img
        src="<?= !empty($row['profile_image'])
            ? htmlspecialchars($row['profile_image'])
            : '../images/default-avatar.svg' ?>"
        class="h-12 w-12 rounded-full object-cover border">

        <div class="flex-1">

            <h4 class="font-bold text-slate-800">
                <?= htmlspecialchars($row['name']) ?>
            </h4>

            <p class="text-sm text-slate-500">
                <?= htmlspecialchars($row['company']) ?>
            </p>

            <p class="text-xs text-slate-400">
                <?= htmlspecialchars($row['location']) ?>
            </p>

        </div>

        <a href="user_profile.php?id=<?= $row['id'] ?>"
           class="rounded-xl bg-cyan-500 px-4 py-2 text-white text-sm font-semibold hover:bg-cyan-600">

            View Profile

        </a>

    </div>

</div>

<?php endwhile; ?>


<?php
session_start();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    header("Location: ../admin/dashboard.php");
    exit;
}

$current_user_id = (int) $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$position = $_GET['position'] ?? '';
$job_type = $_GET['job_type'] ?? '';
$location = $_GET['location'] ?? '';

$sql = "
SELECT
    jobs.position,
    jobs.job_type,
    jobs.location,
    jobs.company,
    users.id,
    users.name,
    users.email,
    users.profile_image
FROM jobs
INNER JOIN users ON jobs.user_id = users.id
WHERE jobs.user_id != ?
AND users.role = 'user'
";

$params = [$current_user_id];
$types = "i";

if ($position !== '') {
    $sql .= " AND jobs.position = ? ";
    $params[] = $position;
    $types .= "s";
}

if ($job_type !== '') {
    $sql .= " AND jobs.job_type = ? ";
    $params[] = $job_type;
    $types .= "s";
}

if ($location !== '') {
    $sql .= " AND jobs.location = ? ";
    $params[] = $location;
    $types .= "s";
}

if ($search !== '') {
    $sql .= "
    AND (
        jobs.position LIKE ?
        OR users.name LIKE ?
        OR users.email LIKE ?
        OR jobs.company LIKE ?
        OR jobs.location LIKE ?
    )
    ";
    $keyword = "%{$search}%";
    array_push($params, $keyword, $keyword, $keyword, $keyword, $keyword);
    $types .= "sssss";
}

$sql .= " ORDER BY jobs.position ASC, users.name ASC ";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();
$jobGroups = [];

while ($row = $result->fetch_assoc()) {
    $jobGroups[$row['position']][] = $row;
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jobs</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen flex flex-col bg-slate-50">

    <?php include "../include/user_header.php"; ?>

    <main class="flex-1 mx-auto max-w-7xl px-4 py-6 w-full">

        <div class="bg-white rounded-3xl p-5 shadow-sm mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">

                <div class="search-box">
                    <i class="fa-solid fa-search"></i>
                    <input
                        type="text"
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="Search alumni, company, or title"
                        class="input-base"
                    >
                </div>

                <select name="position" class="input-base">
                    <option value="">All Job Titles</option>
                    <?php
                    $q = mysqli_query($conn, "SELECT DISTINCT position FROM jobs ORDER BY position");
                    while ($row = mysqli_fetch_assoc($q)):
                    ?>
                        <option value="<?= e($row['position']) ?>" <?= $position == $row['position'] ? 'selected' : '' ?>>
                            <?= e($row['position']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="job_type" class="input-base">
                    <option value="">All Job Types</option>
                    <?php
                    $q = mysqli_query($conn, "SELECT DISTINCT job_type FROM jobs WHERE job_type != '' ORDER BY job_type");
                    while ($row = mysqli_fetch_assoc($q)):
                    ?>
                        <option value="<?= e($row['job_type']) ?>" <?= $job_type == $row['job_type'] ? 'selected' : '' ?>>
                            <?= e($row['job_type']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="location" class="input-base">
                    <option value="">All Locations</option>
                    <?php
                    $q = mysqli_query($conn, "SELECT DISTINCT location FROM jobs ORDER BY location");
                    while ($row = mysqli_fetch_assoc($q)):
                    ?>
                        <option value="<?= e($row['location']) ?>" <?= $location == $row['location'] ? 'selected' : '' ?>>
                            <?= e($row['location']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1">
                        <i class="fa-solid fa-magnifying-glass"></i> Search
                    </button>

                    <a href="job.php" class="btn btn-ghost flex-1">
                        Clear
                    </a>
                </div>

            </form>
        </div>

        <div class="space-y-4">

            <?php if (empty($jobGroups)): ?>
                <?php
                $es_icon    = 'fa-solid fa-briefcase';
                $es_title   = 'No jobs found';
                $es_message = 'Try adjusting your filters or search keyword.';
                $es_action  = '<a href="job.php" class="btn btn-primary"><i class="fa-solid fa-rotate-left"></i> Clear Filters</a>';
                include '../include/empty_state.php';
                ?>
            <?php endif; ?>

            <?php foreach ($jobGroups as $jobTitle => $alumni): ?>
                <div class="bg-white rounded-3xl p-6 shadow-sm border">

                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-xl bg-cyan-100 flex items-center justify-center">
                            <i class="fa-solid fa-briefcase text-cyan-600"></i>
                        </div>

                        <div>
                            <h3 class="font-bold text-lg">
                                <?= e($jobTitle) ?>
                            </h3>

                            <p class="text-sm text-slate-500">
                                <?= count($alumni) ?> Alumni
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 border-t pt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($alumni as $person): ?>
                            <?php $image = !empty($person['profile_image']) ? $person['profile_image'] : '../images/default-avatar.svg'; ?>

                            <div class="rounded-2xl border p-4 hover:bg-slate-50 transition">
                                <div class="flex items-center gap-4">
                                    <img
                                        src="<?= e($image) ?>"
                                        alt="<?= e($person['name']) ?>"
                                        class="h-14 w-14 rounded-full object-cover border"
                                    >

                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-bold text-slate-800 truncate">
                                            <?= e($person['name']) ?>
                                        </h4>

                                        <p class="text-sm text-slate-500 truncate">
                                            <?= e($person['email']) ?>
                                        </p>

                                        <p class="text-sm text-slate-600 truncate">
                                            <?= e($person['company']) ?>
                                        </p>

                                        <p class="text-xs text-slate-400 truncate">
                                            <?= e($person['location']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-2">
                                    <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700">
                                        <?= e($person['job_type'] ?: 'Job') ?>
                                    </span>

                                    <a href="user_profile.php?id=<?= (int) $person['id'] ?>"
                                       class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-user"></i> View Profile
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>

    </main>

<?php include "../include/footer.php"; ?>

</body>

</html>

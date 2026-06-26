
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
$current_user_id = (int) $_SESSION['user_id'];

$position = $_GET['position'] ?? '';
$job_type = $_GET['job_type'] ?? '';
$location = $_GET['location'] ?? '';

$sql = "
SELECT
    position,
    user_id,
    COUNT(*) as total_alumni
FROM jobs
WHERE user_id != ?
";

$params = [$current_user_id];
$types = "i";

if ($position != '') {
    $sql .= " AND position = ? ";
    $params[] = $position;
    $types .= "s";
}

if ($job_type != '') {
    $sql .= " AND job_type = ? ";
    $params[] = $job_type;
    $types .= "s";
}

if ($location != '') {
    $sql .= " AND location = ? ";
    $params[] = $location;
    $types .= "s";
}

$sql .= "
GROUP BY position, user_id
ORDER BY total_alumni DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);
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

<body class="bg-slate-50">

    <?php include "../include/user_header.php"; ?>

    <div class="max-w-7xl mx-auto px-4 py-6">

        <!-- FILTER -->
        <div class="bg-white rounded-3xl p-5 shadow-sm mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <select name="position" class="rounded-xl border px-4 py-3">
                    <option value="">All Job Titles</option>
                    <?php
                    $q = mysqli_query($conn, "SELECT DISTINCT position FROM jobs ORDER BY position");
                    while ($row = mysqli_fetch_assoc($q)):
                        ?>
                        <option value="<?= htmlspecialchars($row['position']) ?>" <?= ($position == $row['position']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['position']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="job_type" class="rounded-xl border px-4 py-3">
                    <option value="">All Job Types</option>
                    <?php
                    $q = mysqli_query($conn, "SELECT DISTINCT job_type FROM jobs WHERE job_type != ''");
                    while ($row = mysqli_fetch_assoc($q)):
                        ?>
                        <option value="<?= htmlspecialchars($row['job_type']) ?>" <?= ($job_type == $row['job_type']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['job_type']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="location" class="rounded-xl border px-4 py-3">
                    <option value="">All Locations</option>
                    <?php
                    $q = mysqli_query($conn, "SELECT DISTINCT location FROM jobs ORDER BY location");
                    while ($row = mysqli_fetch_assoc($q)):
                        ?>
                        <option value="<?= htmlspecialchars($row['location']) ?>" <?= ($location == $row['location']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['location']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="w-full bg-cyan-500 text-white rounded-xl font-bold">
                        Search
                    </button>

                     <button type="submit" class="w-full bg-cyan-500 text-white rounded-xl font-bold">
                        Clear
                    </button>
                </div>

            </form>
        </div>

        <!-- JOB LIST -->
        <div class="space-y-4">

            <?php if (empty($jobs)): ?>
                <div class="bg-white p-6 rounded-xl text-center shadow">
                    No jobs found
                </div>
            <?php endif; ?>

            <?php foreach ($jobs as $job):
                $positionId = preg_replace('/[^a-zA-Z0-9]/', '-', $job['position']);
                ?>

                <div class="bg-white rounded-3xl p-6 shadow-sm border">

                    <div class="flex items-center justify-between">

                        <div class="flex items-center gap-4">

                            <div class="h-12 w-12 rounded-xl bg-cyan-100 flex items-center justify-center">
                                <i class="fa-solid fa-briefcase text-cyan-600"></i>
                            </div>

                            <div>

                                <h3 class="font-bold text-lg">
                                    <?= htmlspecialchars($job['position']) ?>
                                </h3>

                                <p class="text-sm text-slate-500">
                                    <?= $job['total_alumni'] ?> Alumni
                                </p>

                            
                                

                            </div>

                        </div>

                        <button onclick="toggleAlumni('<?= $positionId ?>', '<?= htmlspecialchars($job['position']) ?>')">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

                    </div>

                    <div id="alumni-<?= $positionId ?>" class="hidden mt-5 border-t pt-5"></div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <script>
        function toggleAlumni(id, position) {
            let box = document.getElementById('alumni-' + id);

            if (box.classList.contains('hidden')) {
                fetch('load_alumni.php?position=' + encodeURIComponent(position))
                    .then(res => res.text())
                    .then(data => {
                        box.innerHTML = data;
                        box.classList.remove('hidden');
                    });
            } else {
                box.classList.add('hidden');
            }
        }
    </script>

<?php include "../include/footer.php"; ?>

</body>

</html>
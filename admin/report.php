
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

/* Cards */

$totalAlumni = $conn->query(" 
SELECT COUNT(*) total
FROM users
WHERE role NOT IN ('admin', 'super_admin')
")->fetch_assoc()['total'];

$totalJobs = $conn->query("
SELECT COUNT(*) total
FROM jobs
")->fetch_assoc()['total'];

$totalPosts = $conn->query("
SELECT COUNT(*) total
FROM posts
")->fetch_assoc()['total'];

$totalMessages = $conn->query("
SELECT COUNT(*) total
FROM contact_messages
")->fetch_assoc()['total'];

/* Jobs Chart */

$fullTime = $conn->query("
SELECT COUNT(*) total
FROM jobs
WHERE job_type='Full Time'
")->fetch_assoc()['total'];

$partTime = $conn->query("
SELECT COUNT(*) total
FROM jobs
WHERE job_type='Part Time'
")->fetch_assoc()['total'];

/* Messages Chart */

$readMsg = $conn->query("
SELECT COUNT(*) total
FROM contact_messages
WHERE is_read=1
")->fetch_assoc()['total'];

$unreadMsg = $conn->query("
SELECT COUNT(*) total
FROM contact_messages
WHERE is_read=0
")->fetch_assoc()['total'];

/* Alumni By Graduation Year */

$alumniData = [];

$result = $conn->query("
SELECT
graduated_year,
COUNT(*) total
FROM approved_students
GROUP BY graduated_year
ORDER BY graduated_year ASC
");

while($row = $result->fetch_assoc()) {

    $alumniData[] = $row;

}

/* Posts By Month */

$postData = [];

$result = $conn->query("
SELECT
MONTH(created_at) month,
COUNT(*) total
FROM posts
GROUP BY MONTH(created_at)
ORDER BY MONTH(created_at)
");

while($row = $result->fetch_assoc()) {

    $postData[] = $row;

}

$activityLimit = 8;
$activityPage = max(1, (int) ($_GET['activity_page'] ?? 1));
$activityOffset = ($activityPage - 1) * $activityLimit;
$activityTotal = (int) ($conn->query("SELECT COUNT(*) total FROM activity_log")->fetch_assoc()['total'] ?? 0);
$activityTotalPages = max(1, (int) ceil($activityTotal / $activityLimit));

$activityStmt = $conn->prepare("
    SELECT a.id, a.action, a.description, a.created_at, u.name AS admin_name
    FROM activity_log a
    LEFT JOIN users u ON u.id = a.admin_id
    ORDER BY a.created_at DESC, a.id DESC
    LIMIT ?, ?
");
$activityStmt->bind_param('ii', $activityOffset, $activityLimit);
$activityStmt->execute();
$activityRows = $activityStmt->get_result();

function report_sql_value(mysqli $conn, $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . $conn->real_escape_string((string) $value) . "'";
}

function report_backup_sql(mysqli $conn): string
{
    $sql = "-- Alumni Network database backup\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = [];
    $tablesResult = $conn->query("SHOW TABLES");
    while ($tableRow = $tablesResult->fetch_array()) {
        $tables[] = $tableRow[0];
    }

    foreach ($tables as $table) {
        $createRow = $conn->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc();
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $createRow['Create Table'] . ";\n\n";

        $rows = $conn->query("SELECT * FROM `{$table}`");
        if ($rows && $rows->num_rows > 0) {
            while ($row = $rows->fetch_assoc()) {
                $columns = [];
                foreach (array_keys($row) as $column) {
                    $columns[] = '`' . $column . '`';
                }
                $values = [];
                foreach ($row as $value) {
                    $values[] = report_sql_value($conn, $value);
                }

                $sql .= "INSERT INTO `{$table}` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

if (isset($_POST['restore_database'])) {
    $restoreMessage = 'No file uploaded.';
    $restoreOk = false;

    if (!empty($_FILES['restore_file']['name']) && $_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
        $sql = file_get_contents($_FILES['restore_file']['tmp_name']);
        if ($sql !== false && trim($sql) !== '') {
            $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
            if ($conn->multi_query($sql)) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
                $restoreOk = true;
                $restoreMessage = 'Database restored successfully.';
            } else {
                $restoreMessage = 'Restore failed: ' . $conn->error;
            }
        } else {
            $restoreMessage = 'Selected file is empty.';
        }
    }

    header('Location: report.php?restore=' . ($restoreOk ? 'success' : 'error') . '&message=' . urlencode($restoreMessage));
    exit;
}

if (($_GET['export'] ?? '') === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="alumni_report_' . date('Y-m-d') . '.xls"');
    echo '<table border="1">';
    echo '<tr><th>Report Item</th><th>Value</th></tr>';
    echo '<tr><td>Total Alumni</td><td>' . (int) $totalAlumni . '</td></tr>';
    echo '<tr><td>Total Jobs</td><td>' . (int) $totalJobs . '</td></tr>';
    echo '<tr><td>Total Posts</td><td>' . (int) $totalPosts . '</td></tr>';
    echo '<tr><td>Total Messages</td><td>' . (int) $totalMessages . '</td></tr>';
    echo '<tr><td>Read Messages</td><td>' . (int) $readMsg . '</td></tr>';
    echo '<tr><td>Unread Messages</td><td>' . (int) $unreadMsg . '</td></tr>';
    echo '<tr><td colspan="2">&nbsp;</td></tr>';
    echo '<tr><th>Graduation Year</th><th>Alumni</th></tr>';
    foreach ($alumniData as $item) {
        echo '<tr><td>' . htmlspecialchars((string) $item['graduated_year']) . '</td><td>' . (int) $item['total'] . '</td></tr>';
    }
    echo '<tr><td colspan="2">&nbsp;</td></tr>';
    echo '<tr><th>Month</th><th>Posts</th></tr>';
    foreach ($postData as $item) {
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthName = $monthNames[(int) $item['month'] - 1] ?? '';
        echo '<tr><td>' . htmlspecialchars($monthName) . '</td><td>' . (int) $item['total'] . '</td></tr>';
    }
    echo '</table>';
    exit;
}

if (($_GET['backup'] ?? '') === '1') {
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="alumni_backup_' . date('Y-m-d_His') . '.sql"');
    echo report_backup_sql($conn);
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js"></script>
    <style>
        @media print {
            body { background: white !important; }
            .no-print, aside, header, footer, nav, .print-hide { display: none !important; }
            .min-h-screen { min-height: auto !important; }
            main { padding: 20px !important; overflow: visible !important; }
            .print-report { display: block !important; }
            .print-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; break-inside: avoid; }
            .print-chart { break-inside: avoid; page-break-inside: avoid; }
        }
        .print-report { display: none; }
    </style>
</head>

<body class="bg-slate-50">
<div class="min-h-screen flex">
    <?php include "../include/admin_header.php"; ?>

    <main class="flex-1 min-w-0 p-6 overflow-y-auto">

        <!-- Header -->
        <div class="admin-page-head print-hide">
            <div class="title-wrap">
                <div class="admin-title-icon"><i class="fa-solid fa-chart-bar"></i></div>
                <div>
                    <h1 class="admin-page-title">Reports</h1>
                    <p class="admin-page-sub">Analytics and statistics overview</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button onclick="window.print()" type="button" class="btn btn-secondary no-print">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </button>
                <a href="?export=excel" class="btn btn-success no-print">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
                <a href="?backup=1" class="btn btn-ghost no-print">
                    <i class="fa-solid fa-database"></i> Backup Database
                </a>
                <form method="POST" enctype="multipart/form-data" class="no-print flex items-center gap-2">
                    <input type="file" name="restore_file" accept=".sql" class="input-base w-44 text-xs">
                    <button type="submit" name="restore_database" class="btn btn-primary">
                        <i class="fa-solid fa-upload"></i> Restore
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($_GET['message'])): ?>
            <div class="mb-4 rounded-xl px-4 py-3 text-sm <?= (($_GET['restore'] ?? '') === 'success') ? 'bg-teal-50 text-teal-700 border border-teal-100' : 'bg-red-50 text-red-700 border border-red-100' ?>">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Print Only Title -->
        <div class="print-report mb-6">
            <h1 class="text-2xl font-black text-slate-800 text-center">Alumni Network System - Report</h1>
        </div>

        <!-- Stats Cards -->
        <div class="admin-stagger grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">

            <div class="print-card admin-stat">
                <span class="stat-spark"></span>
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="stat-value"><?= $totalAlumni ?></div>
                <div class="stat-label">Total Alumni</div>
            </div>

            <div class="print-card admin-stat">
                <span class="stat-spark"></span>
                <div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div>
                <div class="stat-value"><?= $totalJobs ?></div>
                <div class="stat-label">Total Jobs</div>
            </div>

            <div class="print-card admin-stat">
                <span class="stat-spark"></span>
                <div class="stat-icon"><i class="fa-regular fa-newspaper"></i></div>
                <div class="stat-value"><?= $totalPosts ?></div>
                <div class="stat-label">Total Posts</div>
            </div>

            <div class="print-card admin-stat">
                <span class="stat-spark"></span>
                <div class="stat-icon"><i class="fa-regular fa-envelope"></i></div>
                <div class="stat-value"><?= $totalMessages ?></div>
                <div class="stat-label">Messages</div>
            </div>

        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- Alumni By Graduation Year -->
            <div class="print-card admin-card admin-fade print-chart">
                <div class="admin-card-head">
                    <div class="admin-card-title"><i class="fa-solid fa-graduation-cap fa-icon-chip"></i> <span>Alumni By Graduation Year</span></div>
                </div>
                <div class="p-5">
                    <div class="relative h-64 w-full">
                        <canvas id="alumniChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Jobs By Type -->
            <div class="print-card admin-card admin-fade print-chart">
                <div class="admin-card-head">
                    <div class="admin-card-title"><i class="fa-solid fa-chart-pie fa-icon-chip"></i> <span>Jobs By Type</span></div>
                </div>
                <div class="p-5">
                    <div class="relative h-64 w-full">
                        <canvas id="jobChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Posts By Month -->
            <div class="print-card admin-card admin-fade print-chart">
                <div class="admin-card-head">
                    <div class="admin-card-title"><i class="fa-solid fa-chart-column fa-icon-chip"></i> <span>Posts By Month</span></div>
                </div>
                <div class="p-5">
                    <div class="relative h-64 w-full">
                        <canvas id="postChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Messages Status -->
            <div class="print-card admin-card admin-fade print-chart">
                <div class="admin-card-head">
                    <div class="admin-card-title"><i class="fa-solid fa-envelope-open fa-icon-chip"></i> <span>Messages Status</span></div>
                </div>
                <div class="p-5">
                    <div class="relative h-64 w-full">
                        <canvas id="messageChart"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <div class="admin-card admin-fade mt-5">
            <div class="admin-card-head">
                <div class="admin-card-title"><i class="fa-solid fa-clock-rotate-left fa-icon-chip"></i> <span>Activity Log</span></div>
                <span class="admin-card-link"><?= (int) $activityTotal ?> Entries</span>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table data-table w-full table-fixed">
                    <thead>
                        <tr>
                            <th class="w-40 p-2 text-left">Action</th>
                            <th class="w-1/2 p-2 text-left">Description</th>
                            <th class="w-36 p-2 text-left">Admin</th>
                            <th class="w-32 p-2 text-left">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($activityRows && $activityRows->num_rows > 0): ?>
                            <?php while ($activity = $activityRows->fetch_assoc()): ?>
                                <tr>
                                    <td class="p-2 align-middle">
                                        <?php
                                        $activityAction = strtolower((string) $activity['action']);
                                        $actionClass = 'badge-cyan';
                                        if (strpos($activityAction, 'delete') !== false) {
                                            $actionClass = 'badge-red';
                                        } elseif (strpos($activityAction, 'approve') !== false || strpos($activityAction, 'add') !== false) {
                                            $actionClass = 'badge-green';
                                        }
                                        ?>
                                        <span class="badge <?= $actionClass ?>"><?= htmlspecialchars($activity['action']) ?></span>
                                    </td>
                                    <td class="p-2 align-middle truncate"><?= htmlspecialchars($activity['description'] ?: '-') ?></td>
                                    <td class="p-2 align-middle truncate"><?= htmlspecialchars($activity['admin_name'] ?: 'Admin') ?></td>
                                    <td class="p-2 align-middle whitespace-nowrap"><?= date('M d, Y h:i A', strtotime($activity['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-4 text-sm text-slate-500">No activity recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($activityTotal > 0): ?>
                <div class="mt-4 flex flex-col items-center gap-2 pb-4">
                    <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                        <span>Showing <strong class="text-slate-700"><?= $activityOffset + 1 ?>&ndash;<?= min($activityOffset + $activityLimit, $activityTotal) ?></strong> of <strong class="text-slate-700"><?= $activityTotal ?></strong></span>
                    </div>
                    <div class="pagination">
                        <?php if ($activityPage > 1): ?>
                            <a href="?activity_page=<?= $activityPage - 1 ?>"><i class="fa-solid fa-chevron-left text-xs"></i> Previous</a>
                        <?php else: ?>
                            <span class="disabled"><i class="fa-solid fa-chevron-left text-xs"></i> Previous</span>
                        <?php endif; ?>

                        <?php $activityStart = max(1, $activityPage - 2); $activityEnd = min($activityTotalPages, $activityPage + 2); ?>
                        <?php for ($i = $activityStart; $i <= $activityEnd; $i++): ?>
                            <a href="?activity_page=<?= $i ?>" class="<?= $activityPage == $i ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($activityPage < $activityTotalPages): ?>
                            <a href="?activity_page=<?= $activityPage + 1 ?>">Next <i class="fa-solid fa-chevron-right text-xs"></i></a>
                        <?php else: ?>
                            <span class="disabled">Next <i class="fa-solid fa-chevron-right text-xs"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php include "../include/admin_footer.php"; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    var chartColors = {
        cyan: '#06b6d4',
        cyanLight: '#22d3ee',
        teal: '#14b8a6',
        tealLight: '#2dd4bf',
        emerald: '#10b981',
        red: '#ef4444'
    };

    var tooltipStyle = {
        backgroundColor: '#0f172a',
        titleFont: { weight: 'bold', size: 13 },
        bodyFont: { size: 12 },
        padding: 12,
        cornerRadius: 8,
        displayColors: true,
        boxPadding: 4
    };

    var alumniCanvas = document.getElementById('alumniChart');
    if (alumniCanvas) {
        new Chart(alumniCanvas, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($alumniData, 'graduated_year')) ?>,
                datasets: [{
                    label: 'Alumni',
                    data: <?= json_encode(array_map('intval', array_column($alumniData, 'total'))) ?>,
                    backgroundColor: chartColors.cyan,
                    hoverBackgroundColor: chartColors.cyanLight,
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 5, bottom: 5 } },
                plugins: {
                    legend: { display: false },
                    tooltip: tooltipStyle
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 11 }, padding: 8 },
                        grid: { color: '#f1f5f9', drawBorder: false },
                        border: { display: false }
                    },
                    x: {
                        ticks: { font: { size: 11 }, padding: 8 },
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    }

    var jobCanvas = document.getElementById('jobChart');
    if (jobCanvas) {
        new Chart(jobCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Full Time', 'Part Time'],
                datasets: [{
                    data: [<?= (int)$fullTime ?>, <?= (int)$partTime ?>],
                    backgroundColor: [chartColors.cyan, chartColors.teal],
                    hoverBackgroundColor: [chartColors.cyanLight, chartColors.tealLight],
                    borderWidth: 0,
                    spacing: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: 5 },
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16, usePointStyle: true, pointStyleWidth: 10, font: { size: 12 } }
                    },
                    tooltip: tooltipStyle
                }
            }
        });
    }

    var postCanvas = document.getElementById('postChart');
    if (postCanvas) {
        new Chart(postCanvas, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(function ($post) {
                    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    return $monthNames[(int)$post['month'] - 1] ?? '';
                }, $postData)) ?>,
                datasets: [{
                    label: 'Posts',
                    data: <?= json_encode(array_map('intval', array_column($postData, 'total'))) ?>,
                    backgroundColor: chartColors.teal,
                    hoverBackgroundColor: chartColors.tealLight,
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 5, bottom: 5 } },
                plugins: {
                    legend: { display: false },
                    tooltip: tooltipStyle
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 11 }, padding: 8 },
                        grid: { color: '#f1f5f9', drawBorder: false },
                        border: { display: false }
                    },
                    x: {
                        ticks: { font: { size: 11 }, padding: 8 },
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    }

    var messageCanvas = document.getElementById('messageChart');
    if (messageCanvas) {
        new Chart(messageCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Read', 'Unread'],
                datasets: [{
                    data: [<?= (int)$readMsg ?>, <?= (int)$unreadMsg ?>],
                    backgroundColor: [chartColors.emerald, chartColors.red],
                    hoverBackgroundColor: ['#34d399', '#f87171'],
                    borderWidth: 0,
                    spacing: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: 5 },
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16, usePointStyle: true, pointStyleWidth: 10, font: { size: 12 } }
                    },
                    tooltip: tooltipStyle
                }
            }
        });
    }
});
</script>

</body>
</html>

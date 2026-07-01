
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
WHERE role != 'admin'
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
?>


<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>Reports</title>

<script src="https://cdn.tailwindcss.com"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js"></script>

<style>

@media print {

    .no-print {
        display: none;
    }

}

</style>

</head>

<body class="bg-slate-50">
<div class="min-h-screen flex">
    <?php include "../include/admin_header.php"; ?>
<div class="flex-1 p-6">

    <div class="flex justify-between items-center mb-6">

        <h1 class="text-3xl font-bold text-teal-700">
            System Reports
        </h1>

        <button
            onclick="window.print()"
            class="no-print bg-gradient-to-r
            from-cyan-400 to-teal-500
            text-white px-5 py-3 rounded-xl">

            Print Report

        </button>

    </div>

    <!-- Cards -->

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 p-4 rounded-2xl shadow">
            <p class="text-sm text-slate-500">Total Alumni</p>
            <h2 class="text-3xl font-black text-teal-700 mt-1.5">
                <?= $totalAlumni ?>
            </h2>
        </div>

        <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 p-4 rounded-2xl shadow">
            <p class="text-sm text-slate-500">Total Jobs</p>
            <h2 class="text-3xl font-black text-teal-700 mt-1.5">
                <?= $totalJobs ?>
            </h2>
        </div>

        <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 p-4 rounded-2xl shadow">
            <p class="text-sm text-slate-500">Total Posts</p>
            <h2 class="text-3xl font-black text-teal-700 mt-1.5">
                <?= $totalPosts ?>
            </h2>
        </div>

        <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 p-4 rounded-2xl shadow">
            <p class="text-sm text-slate-500">Total Messages</p>
            <h2 class="text-3xl font-black text-teal-700 mt-1.5">
                <?= $totalMessages ?>
            </h2>
        </div>

    </div>

    <!-- Charts -->

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- Alumni Chart -->

        <div class="bg-white rounded-3xl p-6 shadow">

            <h2 class="font-bold text-xl mb-4">
                Alumni By Graduation Year
            </h2>

            <div style="position:relative;height:220px;">
                <canvas id="alumniChart"></canvas>
            </div>

        </div>

        <!-- Jobs Chart -->

        <div class="bg-white rounded-3xl p-6 shadow">

            <h2 class="font-bold text-xl mb-4">
                Jobs By Type
            </h2>

            <div style="position:relative;height:220px;">
                <canvas id="jobChart"></canvas>
            </div>

        </div>

        <!-- Posts Chart -->

        <div class="bg-white rounded-3xl p-6 shadow">

            <h2 class="font-bold text-xl mb-4">
                Posts By Month
            </h2>

            <div style="position:relative;height:220px;">
                <canvas id="postChart"></canvas>
            </div>

        </div>

        <!-- Messages Chart -->

        <div class="bg-white rounded-3xl p-6 shadow">

            <h2 class="font-bold text-xl mb-4">
                Messages Status
            </h2>

            <div style="position:relative;height:220px;">
                <canvas id="messageChart"></canvas>
            </div>

        </div>

    </div>

</div>
</div>

<?php include "../include/admin_footer.php"; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    var alumniChart = document.getElementById('alumniChart');
    var jobChart = document.getElementById('jobChart');
    var postChart = document.getElementById('postChart');
    var messageChart = document.getElementById('messageChart');

    if (alumniChart) {
        new Chart(alumniChart, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($alumniData, 'graduated_year')) ?>,
                datasets: [{
                    label: 'Alumni',
                    data: <?= json_encode(array_map('intval', array_column($alumniData, 'total'))) ?>,
                    backgroundColor: '#06b6d4',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    if (jobChart) {
        new Chart(jobChart, {
            type: 'doughnut',
            data: {
                labels: ['Full Time', 'Part Time'],
                datasets: [{
                    data: [<?= (int)$fullTime ?>, <?= (int)$partTime ?>],
                    backgroundColor: ['#06b6d4', '#14b8a6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    if (postChart) {
        new Chart(postChart, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(function ($post) {
                    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    return $monthNames[(int)$post['month'] - 1] ?? '';
                }, $postData)) ?>,
                datasets: [{
                    label: 'Posts',
                    data: <?= json_encode(array_map('intval', array_column($postData, 'total'))) ?>,
                    backgroundColor: '#14b8a6',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    if (messageChart) {
        new Chart(messageChart, {
            type: 'doughnut',
            data: {
                labels: ['Read', 'Unread'],
                datasets: [{
                    data: [<?= (int)$readMsg ?>, <?= (int)$unreadMsg ?>],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

</body>
</html>

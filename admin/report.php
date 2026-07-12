
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
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 print-hide">
            <div>
                <h1 class="text-3xl font-black text-teal-700">Reports</h1>
                <p class="text-sm text-slate-500 mt-1">Analytics and statistics overview</p>
            </div>
            <button onclick="window.print()" class="no-print flex items-center gap-2 bg-gradient-to-r from-cyan-400 to-teal-500 text-white px-5 py-3 rounded-xl font-bold shadow-md hover:shadow-lg transition">
                <i class="fa-solid fa-print"></i> Print Report
            </button>
        </div>

        <!-- Print Only Title -->
        <div class="print-report mb-6">
            <h1 class="text-2xl font-black text-slate-800 text-center">Alumni Network System - Report</h1>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            <div class="print-card bg-white rounded-2xl p-5 shadow-sm border border-cyan-50 hover:shadow-md transition">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-cyan-500 text-white shadow-lg shadow-cyan-200">
                        <i class="fa-solid fa-users text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Alumni</p>
                        <h2 class="text-2xl font-black text-slate-800"><?= $totalAlumni ?></h2>
                    </div>
                </div>
            </div>

            <div class="print-card bg-white rounded-2xl p-5 shadow-sm border border-cyan-50 hover:shadow-md transition">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-400 to-teal-500 text-white shadow-lg shadow-teal-200">
                        <i class="fa-solid fa-briefcase text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Jobs</p>
                        <h2 class="text-2xl font-black text-slate-800"><?= $totalJobs ?></h2>
                    </div>
                </div>
            </div>

            <div class="print-card bg-white rounded-2xl p-5 shadow-sm border border-cyan-50 hover:shadow-md transition">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500 to-teal-400 text-white shadow-lg shadow-cyan-200">
                        <i class="fa-regular fa-newspaper text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Posts</p>
                        <h2 class="text-2xl font-black text-slate-800"><?= $totalPosts ?></h2>
                    </div>
                </div>
            </div>

            <div class="print-card bg-white rounded-2xl p-5 shadow-sm border border-cyan-50 hover:shadow-md transition">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 text-white shadow-lg shadow-emerald-200">
                        <i class="fa-regular fa-envelope text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Messages</p>
                        <h2 class="text-2xl font-black text-slate-800"><?= $totalMessages ?></h2>
                    </div>
                </div>
            </div>

        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- Alumni By Graduation Year -->
            <div class="print-card bg-white rounded-2xl p-5 shadow-sm border border-cyan-50 print-chart">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cyan-100 text-cyan-600">
                        <i class="fa-solid fa-graduation-cap text-sm"></i>
                    </div>
                    <h2 class="font-bold text-lg text-slate-800">Alumni By Graduation Year</h2>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="alumniChart"></canvas>
                </div>
            </div>

            <!-- Jobs By Type -->
            <div class="print-card bg-white rounded-2xl p-5 shadow-sm border border-cyan-50 print-chart">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-teal-600">
                        <i class="fa-solid fa-chart-pie text-sm"></i>
                    </div>
                    <h2 class="font-bold text-lg text-slate-800">Jobs By Type</h2>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="jobChart"></canvas>
                </div>
            </div>

            <!-- Posts By Month -->
            <div class="print-card bg-white rounded-2xl p-5 shadow-sm border border-cyan-50 print-chart">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cyan-100 text-cyan-600">
                        <i class="fa-solid fa-chart-column text-sm"></i>
                    </div>
                    <h2 class="font-bold text-lg text-slate-800">Posts By Month</h2>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="postChart"></canvas>
                </div>
            </div>

            <!-- Messages Status -->
            <div class="print-card bg-white rounded-2xl p-5 shadow-sm border border-cyan-50 print-chart">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                        <i class="fa-solid fa-envelope-open text-sm"></i>
                    </div>
                    <h2 class="font-bold text-lg text-slate-800">Messages Status</h2>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="messageChart"></canvas>
                </div>
            </div>

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

<?php
session_start();

require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$today = date("Y-m-d");

/* Upcoming Events */
$eventStmt = $conn->prepare("
    SELECT *
    FROM announcements
    WHERE type = 'event'
      AND (event_date IS NULL OR event_date >= ?)
    ORDER BY event_date ASC, event_time ASC, created_at DESC
");
$eventStmt->bind_param("s", $today);
$eventStmt->execute();
$events = $eventStmt->get_result();

/* Announcements */
$annStmt = $conn->prepare("
    SELECT *
    FROM announcements
    WHERE type = 'announcement'
    ORDER BY created_at DESC
");
$annStmt->execute();
$announcements = $annStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements & Events | Alumni Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-white via-cyan-50 to-teal-50 text-slate-800">

    <?php include "../include/user_header.php"; ?>

    <main class="mx-auto max-w-5xl px-4 py-6">

        <section class="overflow-hidden rounded-[2rem] border border-cyan-100 bg-white shadow-lg">
            <div class="h-32 bg-gradient-to-r from-cyan-400 via-teal-400 to-teal-600"></div>
            <div class="px-6 pb-6">
                <div class="flex items-center gap-3 -mt-10">
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl border-4 border-white bg-white text-3xl text-teal-600 shadow-lg">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <div class="pt-10">
                        <h1 class="text-2xl font-black text-slate-900">Announcements &amp; Events</h1>
                        <p class="text-sm font-semibold text-slate-500">Stay updated with the latest from your alumni network</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Upcoming Events -->
        <section class="mt-6">
            <h2 class="mb-4 text-lg font-black text-slate-800">
                <i class="fa-solid fa-calendar-days mr-2 text-teal-600"></i>Upcoming Events
            </h2>

            <?php if ($events->num_rows === 0): ?>
                <div class="rounded-3xl border border-cyan-100 bg-white p-8 text-center text-sm font-semibold text-slate-500 shadow-sm">
                    No upcoming events scheduled.
                </div>
            <?php else: ?>
                <div class="grid gap-5 sm:grid-cols-2">
                    <?php while ($item = $events->fetch_assoc()): ?>
                        <div class="overflow-hidden rounded-3xl border border-cyan-100 bg-white shadow-sm">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                                    class="h-44 w-full object-cover">
                            <?php endif; ?>
                            <div class="p-5">
                                <h3 class="text-lg font-black text-teal-800"><?= htmlspecialchars($item['title']) ?></h3>

                                <div class="mt-3 flex flex-wrap gap-3 text-sm text-slate-600">
                                    <?php if (!empty($item['event_date'])): ?>
                                        <span class="rounded-full bg-cyan-50 px-3 py-1 font-bold text-teal-700">
                                            <i class="fa-regular fa-calendar mr-1"></i>
                                            <?= date("M d, Y", strtotime($item['event_date'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['event_time'])): ?>
                                        <span class="rounded-full bg-cyan-50 px-3 py-1 font-bold text-teal-700">
                                            <i class="fa-regular fa-clock mr-1"></i>
                                            <?= date("h:i A", strtotime($item['event_time'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['location'])): ?>
                                        <span class="rounded-full bg-cyan-50 px-3 py-1 font-bold text-teal-700">
                                            <i class="fa-solid fa-location-dot mr-1"></i>
                                            <?= htmlspecialchars($item['location']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($item['description'])): ?>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        <?= nl2br(htmlspecialchars($item['description'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Announcements -->
        <section class="mt-8">
            <h2 class="mb-4 text-lg font-black text-slate-800">
                <i class="fa-solid fa-bullhorn mr-2 text-teal-600"></i>Announcements
            </h2>

            <?php if ($announcements->num_rows === 0): ?>
                <div class="rounded-3xl border border-cyan-100 bg-white p-8 text-center text-sm font-semibold text-slate-500 shadow-sm">
                    No announcements yet.
                </div>
            <?php else: ?>
                <div class="grid gap-5 sm:grid-cols-2">
                    <?php while ($item = $announcements->fetch_assoc()): ?>
                        <div class="overflow-hidden rounded-3xl border border-cyan-100 bg-white shadow-sm">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                                    class="h-44 w-full object-cover">
                            <?php endif; ?>
                            <div class="p-5">
                                <h3 class="text-lg font-black text-teal-800"><?= htmlspecialchars($item['title']) ?></h3>

                                <div class="mt-3 flex flex-wrap gap-3 text-sm text-slate-600">
                                    <?php if (!empty($item['event_date'])): ?>
                                        <span class="rounded-full bg-cyan-50 px-3 py-1 font-bold text-teal-700">
                                            <i class="fa-regular fa-calendar mr-1"></i>
                                            <?= date("M d, Y", strtotime($item['event_date'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['event_time'])): ?>
                                        <span class="rounded-full bg-cyan-50 px-3 py-1 font-bold text-teal-700">
                                            <i class="fa-regular fa-clock mr-1"></i>
                                            <?= date("h:i A", strtotime($item['event_time'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['location'])): ?>
                                        <span class="rounded-full bg-cyan-50 px-3 py-1 font-bold text-teal-700">
                                            <i class="fa-solid fa-location-dot mr-1"></i>
                                            <?= htmlspecialchars($item['location']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($item['description'])): ?>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        <?= nl2br(htmlspecialchars($item['description'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php include "../include/footer.php"; ?>

</body>

</html>

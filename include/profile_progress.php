<?php
/**
 * Profile Completion Progress Bar
 *
 * Renders a completion progress card that is meant to be displayed only on the
 * currently logged-in user's own profile page (profile.php / edit_profile.php).
 * The percentage is calculated from the filled profile fields.
 *
 * Expected variables:
 *   $user       array  The user record (from users + approved_students join)
 *   $jobsCount  int    Number of work experiences for the user
 */
if (!isset($user)) {
    return;
}

$jobsCount = $jobsCount ?? 0;

$socialKeys = [
    'facebook', 'linkedin', 'github', 'telegram', 'instagram',
    'youtube', 'tiktok', 'line_id', 'viber', 'whatsapp'
];

$hasSocial = false;
foreach ($socialKeys as $key) {
    if (!empty(trim((string) ($user[$key] ?? '')))) {
        $hasSocial = true;
        break;
    }
}

$checklist = [
    [
        'label'   => 'Profile Picture',
        'field'   => 'profile_image',
        'section' => 'personal',
        'done'    => !empty(trim((string) ($user['profile_image'] ?? '')))
    ],
    [
        'label'   => 'Full Name',
        'field'   => 'name',
        'section' => 'personal',
        'done'    => !empty(trim((string) ($user['name'] ?? '')))
    ],
    [
        'label'   => 'Bio',
        'field'   => 'bio',
        'section' => 'personal',
        'done'    => !empty(trim((string) ($user['bio'] ?? '')))
    ],
    [
        'label'   => 'Degree / Graduation Year',
        'field'   => 'graduated_year',
        'section' => 'personal',
        'done'    => !empty(trim((string) ($user['graduated_year'] ?? '')))
    ],
    [
        'label'   => 'Phone Number',
        'field'   => 'phone',
        'section' => 'personal',
        'done'    => !empty(trim((string) ($user['phone'] ?? '')))
    ],
    [
        'label'   => 'Address',
        'field'   => 'address',
        'section' => 'personal',
        'done'    => !empty(trim((string) ($user['address'] ?? '')))
    ],
    [
        'label'   => 'Work Experience',
        'field'   => 'experience',
        'section' => 'experience',
        'done'    => $jobsCount > 0
    ],
    [
        'label'   => 'Social Links',
        'field'   => 'social',
        'section' => 'social',
        'done'    => $hasSocial
    ],
    [
        'label'   => 'Skills & Other Info',
        'field'   => 'skills',
        'section' => 'personal',
        'done'    => !empty(trim((string) ($user['skills'] ?? '')))
    ],
];

$totalFields = count($checklist);
$doneFields  = 0;
foreach ($checklist as $item) {
    if ($item['done']) {
        $doneFields++;
    }
}

$percent = $totalFields > 0
    ? (int) round(($doneFields / $totalFields) * 100)
    : 0;

$missing = array_filter($checklist, fn($i) => !$i['done']);

$isComplete = $percent >= 100;
?>

<section class="overflow-hidden rounded-3xl border border-cyan-100 bg-white p-5 shadow sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-500 text-white shadow-sm">
                <i class="fa-solid fa-chart-simple text-sm"></i>
            </div>
            <div>
                <h3 class="text-lg font-black text-teal-700">Profile Completion</h3>
                <p class="text-xs font-semibold text-slate-500" data-cp-subtitle>
                    <?= $doneFields ?> of <?= $totalFields ?> sections completed
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Circular progress indicator -->
            <div class="relative h-16 w-16 shrink-0 sm:h-20 sm:w-20">
                <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90">
                    <defs>
                        <linearGradient id="cpGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#34d399"></stop>
                            <stop offset="100%" stop-color="#06b6d4"></stop>
                        </linearGradient>
                    </defs>
                    <path class="cp-track"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        pathLength="100" />
                    <path class="cp-bar"
                        data-cp-circle
                        data-percent="<?= $percent ?>"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        pathLength="100"
                        stroke-dasharray="0 100" />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-sm font-black text-teal-700 sm:text-base" data-cp-text>0%</span>
                </div>
            </div>

            <?php if ($isComplete): ?>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-4 py-2 text-sm font-bold text-emerald-700 shadow-sm ring-1 ring-emerald-200">
                    <i class="fa-solid fa-circle-check"></i> Profile Complete ✅
                </span>
            <?php else: ?>
                <a href="edit_profile.php"
                    class="rounded-full bg-gradient-to-r from-emerald-400 to-cyan-500 px-4 py-2 text-sm font-bold text-white shadow-sm hover:opacity-90">
                    <i class="fa-solid fa-pen mr-1"></i> Complete Profile
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Linear gradient progress bar -->
    <div class="mt-5">
        <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div data-cp-bar
                data-percent="<?= $percent ?>"
                class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-cyan-500 transition-[width] duration-700 ease-out"
                style="width: 0%">
            </div>
        </div>
    </div>

    <!-- Missing field chips -->
    <?php if (!empty($missing)): ?>
        <div class="mt-5 rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
            <p class="flex items-center gap-2 text-sm font-bold text-amber-700">
                <i class="fa-solid fa-circle-exclamation"></i>
                Complete the following to reach 100%:
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <?php foreach ($missing as $item): ?>
                    <a href="edit_profile.php#field-<?= $item['field'] ?>"
                        class="group inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-amber-700 shadow-sm ring-1 ring-amber-200 transition hover:bg-amber-100 hover:ring-amber-300">
                        <i class="fa-solid fa-plus text-[10px] transition group-hover:rotate-90"></i>
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="mt-5 flex items-center gap-2 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">
            <i class="fa-solid fa-circle-check"></i>
            Your profile is fully complete. Great job!
        </div>
    <?php endif; ?>
</section>

<style>
    .cp-track {
        fill: none;
        stroke: #e2e8f0;
        stroke-width: 3.5;
    }
    .cp-bar {
        fill: none;
        stroke: url(#cpGrad);
        stroke-width: 3.5;
        stroke-linecap: round;
        transition: stroke-dasharray 1s ease-out;
    }
</style>

<script>
    (function () {
        function animateCompletion() {
            var circle = document.querySelector('[data-cp-circle]');
            var bar = document.querySelector('[data-cp-bar]');
            var text = document.querySelector('[data-cp-text]');
            if (!circle) return;

            var target = parseInt(circle.getAttribute('data-percent'), 10) || 0;

            requestAnimationFrame(function () {
                circle.setAttribute('stroke-dasharray', target + ' 100');
                if (bar) bar.style.width = target + '%';
            });

            var start = null;
            var duration = 900;
            function step(ts) {
                if (!start) start = ts;
                var p = Math.min(1, (ts - start) / duration);
                var eased = 1 - Math.pow(1 - p, 3);
                var val = Math.round(eased * target);
                if (text) text.textContent = val + '%';
                if (p < 1) requestAnimationFrame(step);
                else if (text) text.textContent = target + '%';
            }
            requestAnimationFrame(step);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', animateCompletion);
        } else {
            animateCompletion();
        }
    })();
</script>

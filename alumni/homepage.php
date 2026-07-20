<?php
require_once "../config/db.php";
require_once "../include/auth_layout.php";

$currentYear = date("Y");

$recentPosts = $conn->query("
    SELECT
        p.id,
        p.content,
        p.image,
        p.created_at,
        u.name,
        u.profile_image
    FROM posts p
    INNER JOIN users u ON p.user_id = u.id
    WHERE u.role = 'user'
    ORDER BY p.created_at DESC
    LIMIT 3
");
?>

<?php auth_layout_head('Alumni Network'); ?>

<?php
$navVariant = 'home';
$navShowHome = false;
$navShowRegister = true;
$navShowLogin = true;
include "../include/public_nav.php";
?>

<main>

<!-- HERO -->
<section class="mx-auto max-w-6xl px-4">
    <div class="relative h-[320px] overflow-hidden rounded-[2rem] shadow-2xl sm:h-[380px] group">

        <img
            src="../images/home.jpg"
            alt="Alumni Network"
            class="h-full w-full object-cover object-top transition duration-700 group-hover:scale-105"
        >

        <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/40 to-transparent"></div>

        <div class="absolute inset-0 flex items-center px-8 sm:px-12">
            <div class="max-w-xl">

                <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-cyan-500/30 to-teal-500/30 px-4 py-1.5 text-xs font-bold text-cyan-100 backdrop-blur-md border border-white/10 shadow-lg" data-t="verified_alumni_community">
                    <span class="flex h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    Verified Alumni Community
                </span>

                <h1 class="mt-5 text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl" data-t="alumni_network_system">
                    Alumni Network System
                </h1>

                <p class="mt-3 text-sm font-semibold text-cyan-100 sm:text-base" data-t="connecting_alumni">
                    Connecting Alumni, Sharing Knowledge, Building Careers
                </p>

                <p class="mt-3 max-w-lg text-xs leading-6 text-slate-300 sm:text-sm" data-t="hero_desc">
                    A secure platform where verified alumni can connect, share experiences,
                    build professional networks, and communicate with fellow graduates.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="register.php"
                       class="rounded-xl bg-gradient-to-br from-cyan-400 to-teal-500 px-6 py-2.5 text-sm font-bold text-white shadow-lg hover:shadow-xl hover:scale-105 transition-all" data-t="get_started">
                        Get Started
                    </a>

                    <a href="directory.php"
                       class="rounded-xl border border-white/30 bg-white/10 px-6 py-2.5 text-sm font-bold text-white backdrop-blur-md hover:bg-white/20 hover:scale-105 transition-all" data-t="view_directory">
                        View Directory
                    </a>
                </div>

            </div>
        </div>

    </div>
</section>
<!-- TRUST BADGES -->
<section class="mx-auto -mt-8 max-w-5xl px-4 relative z-10">
    <div class="grid gap-4 rounded-2xl border border-cyan-100 bg-white p-5 shadow-xl sm:grid-cols-3">
        <div class="flex items-center gap-3 rounded-xl bg-cyan-50/60 p-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-teal-500 text-white shadow-sm">
                <i class="fa-solid fa-check text-sm"></i>
            </div>
            <div>
                <p class="font-bold text-teal-800 text-sm" data-t="verified_alumni_only">Verified Alumni Only</p>
                <p class="text-xs text-slate-500 mt-0.5" data-t="verified_alumni_desc">Approved users can register</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl bg-cyan-50/60 p-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-teal-500 text-white shadow-sm">
                <i class="fa-solid fa-id-card text-sm"></i>
            </div>
            <div>
                <p class="font-bold text-teal-800 text-sm" data-t="approved_registration">Approved Registration</p>
                <p class="text-xs text-slate-500 mt-0.5" data-t="approved_registration_desc">Checked with Approved ID</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl bg-cyan-50/60 p-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-teal-500 text-white shadow-sm">
                <i class="fa-solid fa-shield-halved text-sm"></i>
            </div>
            <div>
                <p class="font-bold text-teal-800 text-sm" data-t="secure_community">Secure Community</p>
                <p class="text-xs text-slate-500 mt-0.5" data-t="secure_community_desc">Safe alumni networking</p>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="mx-auto max-w-6xl px-4 py-16">
    <div class="text-center">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-cyan-500/20 to-teal-500/20 px-4 py-1.5 text-xs font-bold text-teal-700 border border-cyan-100">
            <i class="fa-solid fa-star text-[10px] text-teal-500"></i>
            What We Offer
        </span>
        <h2 class="mt-4 text-3xl font-black text-teal-800" data-t="platform_key_features">Platform Key Features</h2>
        <p class="mt-2 text-sm text-slate-500" data-t="features_desc">Main modules included in the Alumni Network System.</p>
    </div>

    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php
        $features = [
            ["fa-lock", "Secure Registration", "Only approved alumni can create accounts using Approved ID."],
            ["fa-user", "Alumni Profiles", "Create and manage professional alumni profiles."],
            ["fa-newspaper", "Community Posts", "Share updates, memories, discussions, and alumni activities."],
            ["fa-heart", "Like and Comment", "Interact with alumni posts through likes and comments."],
            ["fa-briefcase", "Experience Directory", "Search alumni by company, position, and professional experience."],
            ["fa-envelope", "Private Messaging", "Communicate directly with other alumni members."],
        ];

        $featureIcons = [
            "from-rose-400 to-pink-500",
            "from-cyan-400 to-teal-500",
            "from-violet-400 to-purple-500",
            "from-rose-400 to-red-500",
            "from-amber-400 to-orange-500",
            "from-teal-400 to-emerald-500",
        ];

        $featureKeys = [
            ["secure_registration", "secure_registration_desc"],
            ["alumni_profiles", "alumni_profiles_desc"],
            ["community_posts", "community_posts_desc"],
            ["like_and_comment", "like_and_comment_desc"],
            ["experience_directory", "experience_directory_desc"],
            ["private_messaging", "private_messaging_desc"],
        ];

        foreach ($features as $i => $feature):
        ?>
            <div class="group rounded-2xl border border-cyan-100 bg-white p-6 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br <?= $featureIcons[$i] ?> text-white shadow-sm transition group-hover:shadow-md group-hover:scale-110">
                    <i class="fa-solid <?= $feature[0] ?> text-sm"></i>
                </div>
                <h3 class="text-lg font-black text-teal-800" data-t="<?= $featureKeys[$i][0] ?>"><?= $feature[1] ?></h3>
                <p class="mt-2 text-sm leading-6 text-slate-500" data-t="<?= $featureKeys[$i][1] ?>"><?= $feature[2] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- RECENT POSTS -->
<section class="mx-auto max-w-6xl px-4 py-16">
    <div class="mb-8 flex items-end justify-between">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-cyan-500/20 to-teal-500/20 px-4 py-1.5 text-xs font-bold text-teal-700 border border-cyan-100">
                <i class="fa-solid fa-clock-rotate-left text-[10px] text-teal-500"></i>
                Community
            </span>
            <h2 class="mt-4 text-3xl font-black text-teal-800" data-t="recent_alumni_posts">Recent Alumni Posts</h2>
            <p class="mt-2 text-sm text-slate-500" data-t="posts_desc">Latest updates from the alumni community.</p>
        </div>
        <a href="login.php" class="hidden items-center gap-1.5 rounded-xl bg-gradient-to-br from-cyan-400 to-teal-500 px-4 py-2 text-sm font-bold text-white shadow-md hover:shadow-lg hover:scale-105 transition-all sm:inline-flex" data-t="view_all">
            View All <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
    </div>

    <?php if ($recentPosts && $recentPosts->num_rows > 0): ?>
        <div class="grid gap-6 md:grid-cols-3">
            <?php while ($post = $recentPosts->fetch_assoc()): ?>
                <?php
                    $displayImage = $post['image'];

                    if (empty($displayImage)) {
                        $imgStmt = $conn->prepare("SELECT image FROM post_images WHERE post_id = ? LIMIT 1");
                        $imgStmt->bind_param("i", $post['id']);
                        $imgStmt->execute();
                        $imgRes = $imgStmt->get_result();
                        if ($imgRes->num_rows > 0) {
                            $displayImage = $imgRes->fetch_assoc()['image'];
                        }
                    }
                ?>
                <article class="group overflow-hidden rounded-2xl border border-cyan-100 bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl">

                    <?php if (!empty($displayImage)): ?>
                        <div class="overflow-hidden">
                            <img src="<?= htmlspecialchars($displayImage) ?>" alt="Post image" class="h-44 w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                    <?php else: ?>
                        <div class="flex h-44 w-full items-center justify-center bg-gradient-to-br from-cyan-400 to-teal-500">
                            <i class="fa-regular fa-newspaper text-4xl text-white/50"></i>
                        </div>
                    <?php endif; ?>

                    <div class="p-5">
                        <div class="mb-3 flex items-center gap-3">
                            <img src="<?= !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : '../images/default-avatar.svg' ?>"
                                class="h-10 w-10 rounded-full object-cover border-2 border-cyan-100 shadow-sm">
                            <div>
                                <h3 class="font-bold text-sm text-slate-800"><?= htmlspecialchars($post['name']) ?></h3>
                                <p class="flex items-center gap-1 text-xs text-slate-400">
                                    <i class="fa-regular fa-calendar text-[10px]"></i>
                                    <?= date("M d, Y", strtotime($post['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                        <p class="text-sm leading-6 text-slate-600 line-clamp-3">
                            <?= htmlspecialchars(substr($post['content'], 0, 120)) ?><?= strlen($post['content']) > 120 ? '...' : '' ?>
                        </p>
                        <div class="mt-4">
                            <a href="login.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-600 transition hover:text-teal-700" data-t="read_more">
                                Read More <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="rounded-2xl border border-cyan-100 bg-white py-12 text-center shadow-sm">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-cyan-50">
                <i class="fa-regular fa-newspaper text-2xl text-slate-300"></i>
            </div>
            <p class="text-sm text-slate-400" data-t="no_posts_yet">No posts yet. Be the first to share!</p>
        </div>
    <?php endif; ?>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="mx-auto max-w-6xl px-4 py-16">
    <div class="text-center">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-cyan-500/20 to-teal-500/20 px-4 py-1.5 text-xs font-bold text-teal-700 border border-cyan-100">
            <i class="fa-solid fa-arrow-right-arrow-left text-[10px] text-teal-500"></i>
            Process
        </span>
        <h2 class="mt-4 text-3xl font-black text-teal-800" data-t="how_it_works">How It Works</h2>
        <p class="mt-2 text-sm text-slate-500" data-t="how_it_works_desc">Simple approved registration and alumni connection flow.</p>
    </div>

    <div class="relative mt-10 grid gap-4 md:grid-cols-6">
        <div class="absolute top-8 left-[10%] right-[10%] h-0.5 hidden bg-gradient-to-r from-cyan-200 via-teal-200 to-cyan-200 md:block"></div>
        <?php
        $steps = ["admin_adds", "register", "verify", "profile", "posts", "messages"];
        $stepIcons = ["fa-user-gear", "fa-pen-to-square", "fa-circle-check", "fa-id-card", "fa-newspaper", "fa-comment-dots"];
        foreach ($steps as $index => $step):
        ?>
            <div class="relative rounded-2xl border border-cyan-100 bg-white p-5 text-center shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-teal-500 text-white shadow-sm">
                    <i class="fa-solid <?= $stepIcons[$index] ?> text-sm"></i>
                </div>
                <div class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-white border border-cyan-100 text-xs font-black text-teal-700 shadow-sm">
                    <?= $index + 1 ?>
                </div>
                <h3 class="text-sm font-black text-teal-800" data-t="<?= $step ?>"><?= ucfirst(str_replace('_', ' ', $step)) ?></h3>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- WHY JOIN -->
<section class="mx-auto max-w-6xl px-4 py-16">
    <div class="grid items-center gap-8 rounded-2xl border border-cyan-100 bg-white p-6 shadow-sm md:grid-cols-2 md:p-8">

        <!-- Image -->
        <div class="overflow-hidden rounded-xl">
            <img
                src="../images/alumni.jpg"
                alt="Alumni"
                class="h-56 w-full cursor-zoom-in rounded-xl object-cover transition duration-500 hover:scale-105 md:h-72"
                onclick="openLightbox(this.src,'Our Alumni Community')"
            >
        </div>

        <!-- Content -->
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-cyan-500/20 to-teal-500/20 px-4 py-1.5 text-xs font-bold text-teal-700 border border-cyan-100">
                <i class="fa-solid fa-circle-question text-[10px] text-teal-500"></i>
                Why Join?
            </span>
            <h2 class="mt-4 text-2xl font-black text-teal-800 md:text-3xl" data-t="why_join">
                Why Join Our Alumni Network?
            </h2>

            <p class="mt-3 text-sm text-slate-600 leading-6" data-t="why_join_desc">
                Connect with alumni, share experiences,
                and build professional relationships.
            </p>

            <div class="mt-6 grid gap-3 text-sm">
                <div class="flex items-center gap-3 rounded-xl bg-cyan-50/60 p-3" data-t="professional_connections">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-400 to-teal-500 text-white shadow-sm">
                        <i class="fa-solid fa-briefcase text-[10px]"></i>
                    </div>
                    <span class="font-semibold text-slate-700">Professional Connections</span>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-cyan-50/60 p-3" data-t="share_experiences">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-400 to-teal-500 text-white shadow-sm">
                        <i class="fa-solid fa-comments text-[10px]"></i>
                    </div>
                    <span class="font-semibold text-slate-700">Share Experiences</span>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-cyan-50/60 p-3" data-t="alumni_community">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-400 to-teal-500 text-white shadow-sm">
                        <i class="fa-solid fa-users text-[10px]"></i>
                    </div>
                    <span class="font-semibold text-slate-700">Alumni Community</span>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-cyan-50/60 p-3" data-t="career_growth">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-400 to-teal-500 text-white shadow-sm">
                        <i class="fa-solid fa-chart-line text-[10px]"></i>
                    </div>
                    <span class="font-semibold text-slate-700">Career Growth</span>
                </div>
            </div>
        </div>

    </div>
</section>
<!-- CTA -->
<section class="mx-auto max-w-6xl px-4 py-16">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-500 to-teal-700 p-8 text-center text-white shadow-xl md:p-12">
        <div class="absolute -top-20 -right-20 h-60 w-60 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 h-60 w-60 rounded-full bg-cyan-300/20 blur-3xl"></div>
        <div class="relative z-10">
            <h2 class="text-3xl font-black md:text-4xl" data-t="ready_to_join">Ready to Join the Alumni Community?</h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm text-cyan-100" data-t="ready_desc">
                Connect with fellow graduates, share experiences, and grow your professional network.
            </p>
            <div class="mt-8 flex justify-center gap-3">
                <a href="register.php" class="rounded-xl bg-white px-6 py-3 font-bold text-teal-700 shadow-lg hover:shadow-xl hover:scale-105 transition-all" data-t="create_account">Create Account</a>
                <a href="login.php" class="rounded-xl border border-white/40 bg-white/10 px-6 py-3 font-bold text-white backdrop-blur-sm hover:bg-white/20 hover:scale-105 transition-all" data-t="login">Login</a>
            </div>
        </div>
    </div>
</section>

</main>

<!-- FOOTER -->
<footer class="mx-auto mt-10 mb-4 max-w-6xl px-4">
    <div class="rounded-2xl border border-cyan-100 bg-white px-6 py-5 shadow-sm">
        <div class="flex flex-col items-center justify-between gap-3 text-center text-sm text-slate-700 md:flex-row">

            <div class="font-semibold">
                © 2026 Alumni Network
            </div>

            <div class="flex items-center gap-1 text-teal-700 font-medium">
                <i class="fa-solid fa-link text-[10px]"></i>
                Connecting Alumni • Sharing Knowledge • Inspiring Innovation
            </div>

            <div class="flex items-center gap-3">
                <a href="#" class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-50 text-teal-600 hover:bg-gradient-to-br hover:from-cyan-400 hover:to-teal-500 hover:text-white transition-all"><i class="fa-brands fa-facebook-f text-xs"></i></a>
                <a href="#" class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-50 text-teal-600 hover:bg-gradient-to-br hover:from-cyan-400 hover:to-teal-500 hover:text-white transition-all"><i class="fa-brands fa-telegram-plane text-xs"></i></a>
                <a href="#" class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-50 text-teal-600 hover:bg-gradient-to-br hover:from-cyan-400 hover:to-teal-500 hover:text-white transition-all"><i class="fa-brands fa-linkedin-in text-xs"></i></a>
            </div>

        </div>
    </div>
</footer>

<?php include "../include/ui_components.php"; ?>
<?php auth_layout_scripts(); ?>

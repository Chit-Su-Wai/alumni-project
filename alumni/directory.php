<?php
// Session သက်တမ်းကို ရက် ၃၀ (စက္ကန့် ၈၆၄၀၀ * ၃၀) အထိ သတ်မှတ်ခြင်း
$lifetime = 30 * 24 * 60 * 60;
session_set_cookie_params($lifetime);
session_start();
require_once "../config/db.php";
if (
    isset($_SESSION['role']) &&
    $_SESSION['role'] == 'admin'
) {
    header("Location: ../admin/dashboard.php");
    exit;
}
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

function e($value) {
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

$current_user_id = (int) $_SESSION["user_id"];

/* Current User Info */
$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();

$currentProfileImage = !empty($currentUser["profile_image"])
    ? $currentUser["profile_image"]
    : "../images/default-avatar.svg";

/* Search & Filter Params */
$search = trim($_GET["search"] ?? "");
$year = trim($_GET["year"] ?? "");

$where = [];
$params = [];
$types = "";

/* Logged In User Hide (ကိုယ့်အကောင့်ကိုယ် ဖျောက်ထားမည်) */
$where[] = "u.id != ?";
$params[] = $current_user_id;
$types .= "i";

/* Hide Admin Users */
$where[] = "u.role = ?";
$params[] = "user";
$types .= "s";

/* Search Filter (အမည် သို့မဟုတ် Email ဖြင့် ရှာဖွေခြင်း) */
if ($search !== "") {
    // ဒီနေရာမှာ u.name ကော u.email ကောကို စစ်တာ သေချာစေဖို့ ကွင်းစ ကွင်းပိတ် ( ) ပါတာ မှန်ပါတယ်
    $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $searchValue = "%" . $search . "%";
    $params[] = $searchValue;
    $params[] = $searchValue;
    $types .= "ss";
}

/* Year Filter */
if ($year !== "") {
    $where[] = "a.graduated_year = ?";
    $params[] = $year;
    $types .= "s";
}

// Where Clause တည်ဆောက်ခြင်း
$whereSql = "";
if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

/* Directory Users SQL Query (LEFT JOIN ကို သေချာစစ်ဆေးခြင်း) */
/* Directory Users SQL Query (ပြင်ဆင်ပြီး) */
$sql = "
SELECT 
    u.id, 
    u.name, 
    u.email, 
    u.phone, 
    u.profile_image, 
    a.graduated_year, 
    u.address,
    (SELECT j.position FROM jobs j WHERE j.user_id = u.id ORDER BY j.start_date DESC LIMIT 1) AS position,
    (SELECT j.company FROM jobs j WHERE j.user_id = u.id ORDER BY j.start_date DESC LIMIT 1) AS company
FROM users u
LEFT JOIN approved_students a ON u.approved_id = a.approved_id
$whereSql
ORDER BY u.name ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // SQL Error တက်ခဲ့ရင် သိနိုင်အောင် debug လုပ်ရန်
    die("SQL Prepare Error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* Dropdown Filter အတွက် Years data ကို approved table ထဲက ယူခြင်း */
$years = [];
$yearResult = $conn->query("
    SELECT DISTINCT graduated_year
    FROM approved_students
    WHERE graduated_year IS NOT NULL AND graduated_year != ''
    ORDER BY graduated_year DESC
");

if ($yearResult) {
    while ($row = $yearResult->fetch_assoc()) {
        $years[] = $row["graduated_year"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directory | Alumni Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-white via-cyan-50 to-teal-50 text-slate-800">

<?php include "../include/user_header.php"; ?>
<main class="mx-auto max-w-7xl px-3 py-5">
<section class="mb-6 rounded-[2rem] border border-cyan-100  p-4 shadow-sm">
    <form method="GET" class="grid gap-3 md:grid-cols-[1fr_220px_200px]">
        <div class="relative">
            <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="Search name or email..."
                class="w-full rounded-2xl border border-cyan-100 bg-cyan-50/50 py-3 pl-9 pr-4 text-sm font-semibold outline-none focus:border-teal-400 focus:bg-white"
            >
        </div>

        <select
            name="year"
            class="rounded-2xl border border-cyan-100 bg-cyan-50/50 px-4 py-3 text-sm font-bold text-slate-600 outline-none focus:border-teal-400 focus:bg-white"
            onchange="this.form.submit()"
        >
            <option value="">All Years</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= e($y) ?>" <?= $year == $y ? "selected" : "" ?>>
                    <?= e($y) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="flex items-center gap-2 whitespace-nowrap">
            <button
                type="submit"
                class="rounded-2xl bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-3 text-sm font-black text-white shadow hover:opacity-90 transition"
            >
                Search
            </button>
            <a
                href="directory.php"
                class="rounded-2xl bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-3 text-sm font-black text-white shadow hover:opacity-90 transition text-center"
            >
                Clear
            </a>
        </div>
    </form>
</section>

    <?php if (count($users) === 0): ?>
        <div class="rounded-[2rem] border border-cyan-100 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-cyan-50 text-2xl text-teal-600">
                <i class="fa-solid fa-users"></i>
            </div>
            <h2 class="mt-4 text-xl font-black text-slate-800">No alumni found</h2>
            <p class="mt-2 text-sm font-semibold text-slate-500">Try another search keyword.</p>
        </div>
    <?php else: ?>
        <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($users as $row): ?>
                <?php
                    $image = !empty($row["profile_image"]) ? $row["profile_image"] : "../images/default-avatar.svg";
                    $phone = $row["phone"] ?? "";
                    $email = $row["email"] ?? "";
                ?>

                <div class="rounded-[2rem] border border-cyan-100 bg-white p-5 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <img
                        src="<?= e($image) ?>"
                        alt="<?= e($row["name"]) ?>"
                        class="mx-auto h-24 w-24 rounded-full border-4 border-cyan-50 object-cover shadow"
                    >

                    <h3 class="mt-4 text-lg font-black text-slate-900">
                        <?= e($row["name"]) ?>
                    </h3>

                    <p class="mt-1 truncate text-sm font-semibold text-slate-500">
                        <?= e($email) ?>
                    </p>

                    <div class="mt-3 flex flex-wrap justify-center gap-2">
                        <?php if (!empty($row["graduated_year"])): ?>
                            <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-teal-700">
                                <?= e($row["graduated_year"]) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($row["position"])): ?>
                            <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-black text-slate-600">
                                <?= e($row["position"]) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($row["company"])): ?>
                        <p class="mt-3 text-sm font-bold text-slate-600">
                            <i class="fa-solid fa-building mr-1 text-teal-600"></i>
                            <?= e($row["company"]) ?>
                        </p>
                    <?php else: ?>
                        <p class="mt-3 text-sm font-bold text-slate-400">
                            No experience added
                        </p>
                    <?php endif; ?>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <?php if ($phone): ?>
                            <a href="tel:<?= e($phone) ?>" class="rounded-2xl bg-slate-50 px-3 py-2 text-xs font-black text-slate-600 hover:bg-cyan-50">
                                <i class="fa-solid fa-phone mr-1"></i> Call
                            </a>
                        <?php else: ?>
                            <button class="rounded-2xl bg-slate-50 px-3 py-2 text-xs font-black text-slate-300" disabled>
                                <i class="fa-solid fa-phone mr-1"></i> Call
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($email): ?>
                            <a href="mailto:<?= e($email) ?>" class="rounded-2xl bg-slate-50 px-3 py-2 text-xs font-black text-slate-600 hover:bg-cyan-50">
                                <i class="fa-solid fa-envelope mr-1"></i> Email
                            </a>
                        <?php endif; ?>
                    </div>

                    <a
                        href="user_profile.php?id=<?= e($row["id"]) ?>"
                        class="mt-4 block rounded-2xl bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-3 text-sm font-black text-white shadow"
                    >
                        Profile View
                    </a>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<!-- <script>
const menuBtn = document.getElementById("menuBtn");
const mobileMenu = document.getElementById("mobileMenu");

menuBtn?.addEventListener("click", () => {
    mobileMenu.classList.toggle("hidden");
});
</script> -->

<?php include "../include/footer.php"; ?>

</body>
</html>
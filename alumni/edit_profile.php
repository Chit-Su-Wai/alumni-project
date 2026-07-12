<?php
session_start();
// session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
// require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$message = "";
$messageType = "";

$upload_dir = "../uploads/profiles/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

function e($value) {
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "save_personal") {
        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $bio = trim($_POST["bio"] ?? "");
        $skills = trim($_POST["skills"] ?? "");

        $profile_image_path = "";

        if (!empty($_FILES["profile_image"]["name"]) && $_FILES["profile_image"]["error"] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES["profile_image"]["tmp_name"];
            $file_name = $_FILES["profile_image"]["name"];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ["jpg", "jpeg", "png", "webp"];

            if (!in_array($file_ext, $allowed_exts)) {
                $message = "Invalid image type. Only JPG, JPEG, PNG, WEBP allowed.";
                $messageType = "error";
            } else {
                $new_file_name = "profile_" . $user_id . "_" . time() . "." . $file_ext;
                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $profile_image_path = $dest_path;
                }
            }
        }

        if ($messageType !== "error") {
            if ($profile_image_path !== "") {
                $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, bio=?, skills=?, profile_image=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param("ssssssi", $name, $phone, $address, $bio, $skills, $profile_image_path, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, bio=?, skills=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param("sssssi", $name, $phone, $address, $bio, $skills, $user_id);
            }

            if ($stmt->execute()) {
                $message = "Personal information updated successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to update personal information.";
                $messageType = "error";
            }
        }
    }

    if ($action === "save_social") {
        $facebook = trim($_POST["facebook"] ?? "");
        $linkedin = trim($_POST["linkedin"] ?? "");
        $github = trim($_POST["github"] ?? "");
        $telegram = trim($_POST["telegram"] ?? "");
        $instagram = trim($_POST["instagram"] ?? "");
        $youtube = trim($_POST["youtube"] ?? "");
        $tiktok = trim($_POST["tiktok"] ?? "");
        $line_id = trim($_POST["line_id"] ?? "");
        $viber = trim($_POST["viber"] ?? "");
        $whatsapp = trim($_POST["whatsapp"] ?? "");

        $stmt = $conn->prepare("UPDATE users SET facebook=?, linkedin=?, github=?, telegram=?, instagram=?, youtube=?, tiktok=?, line_id=?, viber=?, whatsapp=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssssssssssi", $facebook, $linkedin, $github, $telegram, $instagram, $youtube, $tiktok, $line_id, $viber, $whatsapp, $user_id);

        if ($stmt->execute()) {
            $message = "Social links updated successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to update social links.";
            $messageType = "error";
        }
    }

    if ($action === "save_experience") {
        $job_id = (int)($_POST["job_id"] ?? 0);
        $position = trim($_POST["position"] ?? "");
        $company = trim($_POST["company"] ?? "");
        $job_type = trim($_POST["job_type"] ?? "");
        $location = trim($_POST["location"] ?? "");
        $salary = trim($_POST["salary"] ?? "");
        $experience_year = (int)($_POST["experience_year"] ?? 0);
        $start_date = !empty($_POST["start_date"]) ? $_POST["start_date"] : null;
        $end_date = isset($_POST["current"]) ? null : (!empty($_POST["end_date"]) ? $_POST["end_date"] : null);
        $job_phone = trim($_POST["job_phone"] ?? "");
        $job_email = trim($_POST["job_email"] ?? "");
        $website = trim($_POST["website"] ?? "");
        $description = trim($_POST["description"] ?? "");

        if ($position === "" || $company === "") {
            $message = "Position and Company are required.";
            $messageType = "error";
        } else {
            if ($job_id > 0) {
                $stmt = $conn->prepare("
                    UPDATE jobs
                    SET position=?, company=?, job_type=?, location=?, salary=?, experience_year=?, start_date=?, end_date=?, phone=?, email=?, website=?, description=?
                    WHERE id=? AND user_id=?
                ");
                $stmt->bind_param(
                    "sssssissssssii",
                    $position,
                    $company,
                    $job_type,
                    $location,
                    $salary,
                    $experience_year,
                    $start_date,
                    $end_date,
                    $job_phone,
                    $job_email,
                    $website,
                    $description,
                    $job_id,
                    $user_id
                );
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO jobs
                    (user_id, position, company, job_type, location, salary, experience_year, start_date, end_date, phone, email, website, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "isssssissssss",
                    $user_id,
                    $position,
                    $company,
                    $job_type,
                    $location,
                    $salary,
                    $experience_year,
                    $start_date,
                    $end_date,
                    $job_phone,
                    $job_email,
                    $website,
                    $description
                );
            }

            if ($stmt->execute()) {
                $message = "Experience saved successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to save experience.";
                $messageType = "error";
            }
        }
    }

    if ($action === "delete_experience") {
        $job_id = (int)($_POST["job_id"] ?? 0);

        $stmt = $conn->prepare("DELETE FROM jobs WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $job_id, $user_id);

        if ($stmt->execute()) {
            $message = "Experience deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to delete experience.";
            $messageType = "error";
        }
    }
}

// $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt = $conn->prepare("
    SELECT u.*, a.graduated_year
    FROM users u
    LEFT JOIN approved_students a
        ON u.approved_id = a.approved_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$profileImage = !empty($user["profile_image"]) ? $user["profile_image"] : "../images/default-avatar.svg";

$jobs = [];
$jobStmt = $conn->prepare("SELECT * FROM jobs WHERE user_id=? ORDER BY start_date DESC, created_at DESC");
$jobStmt->bind_param("i", $user_id);
$jobStmt->execute();
$jobs = $jobStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Alumni Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-white via-cyan-50 to-teal-50 text-slate-800">

<?php include "../include/user_header.php"; ?>
<main class="mx-auto max-w-6xl px-4 py-6">

    <?php if ($message): ?>
        <div class="mb-4 rounded-2xl p-4 font-bold <?= $messageType === "success" ? "bg-teal-100 text-teal-800" : "bg-red-100 text-red-800" ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="mb-6 overflow-hidden rounded-[2rem] border border-cyan-100 bg-white shadow-lg">
        <div class="h-44 bg-gradient-to-r from-cyan-400 via-teal-400 to-teal-600"></div>

        <div class="relative px-6 pb-6">
            <div class="-mt-16 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
                    <img src="<?= e($profileImage) ?>" class="h-32 w-32 rounded-full border-4 border-white bg-white object-cover shadow-lg">

                    <div>
                        <h1 class="text-3xl font-black text-slate-900"><?= e($user["name"]) ?></h1>
                        <p class="text-sm font-semibold text-slate-500"><?= e($user["email"]) ?></p>

                        <span class="mt-2 inline-flex rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-teal-700">
                            <i class="fa-solid fa-user-graduate mr-1"></i>
                            Graduated Year <?= !empty($user["graduated_year"]) ? e($user["graduated_year"]) : "Not Added" ?>
                        </span>
                    </div>
                </div>

                <button onclick="submitActiveTabForm()" class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-6 py-3 font-black text-white shadow">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Save Changes
                </button>
            </div>

            <div class="mt-8 border-t border-slate-100 pt-4">
                <div class="flex flex-wrap gap-3 text-sm font-bold">
                    <button onclick="switchTab('personal')" id="tabBtn-personal" class="tab-btn rounded-full bg-cyan-100 px-5 py-2 text-teal-700">Personal</button>
                    <button onclick="switchTab('experience')" id="tabBtn-experience" class="tab-btn rounded-full px-5 py-2 text-slate-600 hover:bg-cyan-50">Experience</button>
                    <button onclick="switchTab('social')" id="tabBtn-social" class="tab-btn rounded-full px-5 py-2 text-slate-600 hover:bg-cyan-50">Social</button>
                </div>
            </div>
        </div>
    </section>

    <?php
    $jobsCount = count($jobs);
    include "../include/profile_progress.php";
    ?>

    <div id="tabContent-personal" class="tab-content">
        <form id="form-personal" method="POST" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-2">
            <input type="hidden" name="action" value="save_personal">

            <div class="space-y-4 rounded-[2rem] border border-cyan-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-800">Personal Information</h2>

                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Profile Image</label>
                    <input type="file" name="profile_image" id="field-profile_image" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Name</label>
                    <input type="text" name="name" id="field-name" value="<?= e($user["name"]) ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Email Readonly</label>
                    <input type="email" value="<?= e($user["email"]) ?>" readonly class="w-full cursor-not-allowed rounded-2xl border border-slate-100 bg-slate-100 px-4 py-3 font-semibold text-slate-500">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Graduated Year Readonly</label>
                    <input type="text" id="field-graduated_year" value="<?= e($user["graduated_year"] ?? "") ?>" readonly class="w-full cursor-not-allowed rounded-2xl border border-slate-100 bg-slate-100 px-4 py-3 font-semibold text-slate-500">
                </div>
            </div>

            <div class="space-y-4 rounded-[2rem] border border-cyan-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-800">Contact & Bio</h2>

                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Phone</label>
                    <input type="text" name="phone" id="field-phone" value="<?= e($user["phone"] ?? "") ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Address</label>
                    <input type="text" name="address" id="field-address" value="<?= e($user["address"] ?? "") ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Bio</label>
                    <textarea name="bio" id="field-bio" rows="5" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold"><?= e($user["bio"] ?? "") ?></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Skills & Other Info</label>
                    <textarea name="skills" id="field-skills" rows="3" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold"><?= e($user["skills"] ?? "") ?></textarea>
                </div>
            </div>
        </form>
    </div>

    <div id="tabContent-experience" class="tab-content hidden space-y-6">
        <div id="field-experience" class="rounded-[2rem] border border-cyan-100 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-black text-slate-800">Work Experience</h2>
                <button onclick="openExperienceModal()" class="rounded-full bg-cyan-50 px-5 py-2 text-sm font-black text-teal-700 hover:bg-cyan-100">
                    <i class="fa-solid fa-plus mr-1"></i> Add Experience
                </button>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="rounded-2xl bg-slate-50 p-6 text-center text-sm font-semibold text-slate-500">
                    No work experience added yet.
                </div>
            <?php else: ?>
                <div class="grid gap-4 md:grid-cols-2">
                    <?php foreach ($jobs as $job): ?>
                        <div class="relative rounded-3xl border border-slate-100 bg-slate-50 p-5">
                            <div class="absolute right-4 top-4 flex gap-2">
                                <button onclick='editExperience(<?= json_encode($job, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="h-8 w-8 rounded-full border bg-white text-cyan-600">
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <form method="POST" onsubmit="return confirm('Delete this experience?');">
                                    <input type="hidden" name="action" value="delete_experience">
                                    <input type="hidden" name="job_id" value="<?= e($job["id"]) ?>">
                                    <button type="submit" class="h-8 w-8 rounded-full border bg-white text-red-500">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>

                            <h3 class="pr-16 text-lg font-black text-teal-800"><?= e($job["position"]) ?></h3>
                            <p class="text-sm font-bold text-slate-700"><?= e($job["company"]) ?></p>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs font-semibold text-slate-500">
                                <p><b>Type:</b> <?= e($job["job_type"] ?: "-") ?></p>
                                <p><b>Location:</b> <?= e($job["location"] ?: "-") ?></p>
                                <p><b>Salary:</b> <?= e($job["salary"] ?: "-") ?></p>
                                <p><b>Year:</b> <?= e($job["experience_year"] ?: "-") ?></p>
                                <p class="col-span-2"><b>Dates:</b> <?= e($job["start_date"] ?: "-") ?> to <?= !empty($job["end_date"]) ? e($job["end_date"]) : "Current" ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="tabContent-social" class="tab-content hidden">
        <form id="form-social" method="POST" class="rounded-[2rem] border border-cyan-100 bg-white p-6 shadow-sm">
            <input type="hidden" name="action" value="save_social">

            <h2 id="field-social" class="mb-6 text-lg font-black text-slate-800">Social Links</h2>

            <div class="grid gap-4 md:grid-cols-2">
                <?php
                $socials = [
                    "facebook" => "Facebook",
                    "linkedin" => "LinkedIn",
                    "github" => "GitHub",
                    "telegram" => "Telegram",
                    "instagram" => "Instagram",
                    "youtube" => "YouTube",
                    "tiktok" => "TikTok",
                    "line_id" => "Line",
                    "viber" => "Viber",
                    "whatsapp" => "WhatsApp"
                ];
                foreach ($socials as $key => $label):
                ?>
                    <div>
                        <label class="mb-1 block text-xs font-black uppercase text-slate-400"><?= $label ?></label>
                        <input type="text" name="<?= $key ?>" value="<?= e($user[$key] ?? "") ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold">
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</main>

<div id="experienceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-2xl overflow-hidden rounded-[2rem] border border-cyan-100 bg-white shadow-2xl">
        <div class="bg-gradient-to-r from-cyan-400 to-teal-500 px-6 py-4 text-white flex items-center justify-between">
            <h3 id="modalTitle" class="text-lg font-black">Add Work Experience</h3>
            <button onclick="closeExperienceModal()" class="text-xl font-bold hover:text-slate-200">&times;</button>
        </div>
        <form id="form-experience" method="POST" class="p-6 max-h-[80vh] overflow-y-auto space-y-4">
            <input type="hidden" name="action" value="save_experience">
            <input type="hidden" name="job_id" id="exp_job_id" value="0">

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Position *</label>
                    <input type="text" name="position" id="exp_position" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Company *</label>
                    <input type="text" name="company" id="exp_company" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Job Type</label>
                    <input type="text" name="job_type" id="exp_job_type" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Location</label>
                    <input type="text" name="location" id="exp_location" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Salary</label>
                    <input type="text" name="salary" id="exp_salary" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Experience Years</label>
                    <input type="number" name="experience_year" id="exp_experience_year" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Start Date</label>
                    <input type="date" name="start_date" id="exp_start_date" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">End Date</label>
                    <input type="date" name="end_date" id="exp_end_date" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
            </div>

            <div class="flex items-center gap-2 py-2">
                <input type="checkbox" name="current" id="exp_current" onchange="toggleEndDate(this.checked)" class="h-4 w-4 rounded text-teal-600">
                <label for="exp_current" class="text-sm font-bold text-slate-600">I currently work here</label>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Phone</label>
                    <input type="text" name="job_phone" id="exp_phone" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Email</label>
                    <input type="email" name="job_email" id="exp_email" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase text-slate-400">Website</label>
                    <input type="text" name="website" id="exp_website" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-black uppercase text-slate-400">Description</label>
                <textarea name="description" id="exp_description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeExperienceModal()" class="rounded-full bg-slate-100 px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200">Cancel</button>
                <button type="submit" class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-6 py-2.5 text-sm font-black text-white shadow">Save Experience</button>
            </div>
        </form>
    </div>
</div>

<script>
const menuBtn = document.getElementById("menuBtn");
const mobileMenu = document.getElementById("mobileMenu");

menuBtn?.addEventListener("click", () => {
    mobileMenu.classList.toggle("hidden");
});

let activeTab = "personal";

function switchTab(targetTab) {
    activeTab = targetTab;

    document.querySelectorAll(".tab-content").forEach(el => {
        el.classList.add("hidden");
    });

    document.querySelectorAll(".tab-btn").forEach(el => {
        el.classList.remove("bg-cyan-100", "text-teal-700");
        el.classList.add("text-slate-600", "hover:bg-cyan-50");
    });

    // Fixed String Syntax to proper Template Literals with Backticks
    document.getElementById(`tabContent-${targetTab}`).classList.remove("hidden");

    const activeBtn = document.getElementById(`tabBtn-${targetTab}`);
    if (activeBtn) {
        activeBtn.classList.add("bg-cyan-100", "text-teal-700");
        activeBtn.classList.remove("text-slate-600", "hover:bg-cyan-50");
    }
}

function submitActiveTabForm() {
    if (activeTab === "personal") {
        document.getElementById("form-personal").submit();
    } else if (activeTab === "social") {
        document.getElementById("form-social").submit();
    } else {
        alert("Use Add/Edit/Delete buttons inside Experience tab.");
    }
}

const modal = document.getElementById("experienceModal");

function openExperienceModal() {
    document.getElementById("modalTitle").innerText = "Add Work Experience";

    document.getElementById("exp_job_id").value = "0";
    document.getElementById("exp_position").value = "";
    document.getElementById("exp_company").value = "";
    document.getElementById("exp_job_type").value = "";
    document.getElementById("exp_location").value = "";
    document.getElementById("exp_salary").value = "";
    document.getElementById("exp_experience_year").value = "";
    document.getElementById("exp_start_date").value = "";
    document.getElementById("exp_end_date").value = "";
    document.getElementById("exp_phone").value = "";
    document.getElementById("exp_email").value = "";
    document.getElementById("exp_website").value = "";
    document.getElementById("exp_description").value = "";
    document.getElementById("exp_current").checked = false;

    toggleEndDate(false);

    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function editExperience(data) {
    document.getElementById("modalTitle").innerText = "Update Work Experience";

    document.getElementById("exp_job_id").value = data.id || 0;
    document.getElementById("exp_position").value = data.position || "";
    document.getElementById("exp_company").value = data.company || "";
    document.getElementById("exp_job_type").value = data.job_type || "";
    document.getElementById("exp_location").value = data.location || "";
    document.getElementById("exp_salary").value = data.salary || "";
    document.getElementById("exp_experience_year").value = data.experience_year || "";
    document.getElementById("exp_start_date").value = data.start_date || "";
    document.getElementById("exp_end_date").value = data.end_date || "";
    document.getElementById("exp_phone").value = data.phone || "";
    document.getElementById("exp_email").value = data.email || "";
    document.getElementById("exp_website").value = data.website || "";
    document.getElementById("exp_description").value = data.description || "";

    const isCurrent = !data.end_date;
    document.getElementById("exp_current").checked = isCurrent;

    toggleEndDate(isCurrent);

    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeExperienceModal() {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}

function toggleEndDate(isCurrent) {
    const endDateInput = document.getElementById("exp_end_date");

    if (isCurrent) {
        endDateInput.value = "";
        endDateInput.disabled = true;
        endDateInput.classList.add("bg-slate-100", "cursor-not-allowed");
    } else {
        endDateInput.disabled = false;
        endDateInput.classList.remove("bg-slate-100", "cursor-not-allowed");
    }
}

window.addEventListener("DOMContentLoaded", () => {
    const raw = window.location.hash.replace("#", "");
    if (!raw) return;

    const fieldToSection = {
        profile_image: "personal",
        name: "personal",
        bio: "personal",
        graduated_year: "personal",
        phone: "personal",
        address: "personal",
        skills: "personal",
        experience: "experience",
        social: "social"
    };

    if (raw.startsWith("field-")) {
        const field = raw.substring(6);
        const section = fieldToSection[field] || "personal";
        switchTab(section);
        setTimeout(() => {
            const el = document.getElementById("field-" + field);
            if (el) el.scrollIntoView({ behavior: "smooth", block: "center" });
        }, 200);
    } else if (raw === "personal" || raw === "social" || raw === "experience") {
        switchTab(raw);
    }
});
</script>

<?php include "../include/footer.php"; ?>

</body>
</html>
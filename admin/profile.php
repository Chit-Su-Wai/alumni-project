<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: ../alumni/login.php");
    exit;
}

require_once "../config/db.php";

    $userId = (int) $_SESSION['user_id'];
    $message = "";
    $messageType = "";

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function remove_local_profile_image($path)
{
    if ($path && strpos($path, '../uploads/profiles/') === 0 && file_exists($path)) {
        @unlink($path);
    }
}

$uploadDir = '../uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $newImagePath = '';
    $currentImage = $user['profile_image'] ?? '';

    if ($name === '' || $email === '') {
        $message = 'Name and email are required.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $emailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $emailStmt->bind_param('si', $email, $userId);
        $emailStmt->execute();
        if ($emailStmt->get_result()->num_rows > 0) {
            $message = 'This email address is already in use.';
            $messageType = 'error';
        }
        $emailStmt->close();
    }

    if ($messageType !== 'error' && !empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['profile_image']['tmp_name'];
        $fileExt = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($fileExt, $allowed, true)) {
            $message = 'Invalid image type. Only JPG, JPEG, PNG, WEBP, GIF allowed.';
            $messageType = 'error';
        } else {
            $newFileName = 'admin_' . $userId . '_' . time() . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $destPath)) {
                $newImagePath = $destPath;
            } else {
                $message = 'Failed to upload profile image.';
                $messageType = 'error';
            }
        }
    }

    $passwordHash = '';
    if ($messageType !== 'error' && ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '')) {
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $message = 'Please complete all password fields.';
            $messageType = 'error';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $message = 'Current password is incorrect.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'New password and confirm password do not match.';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = 'New password must be at least 6 characters.';
            $messageType = 'error';
        } else {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }
    }

    $facebook = $user['facebook'] ?? '';
    $linkedin = $user['linkedin'] ?? '';
    $github = $user['github'] ?? '';
    $telegram = $user['telegram'] ?? '';
    $instagram = $user['instagram'] ?? '';
    $youtube = $user['youtube'] ?? '';
    $tiktok = $user['tiktok'] ?? '';
    $line_id = $user['line_id'] ?? '';
    $viber = $user['viber'] ?? '';
    $whatsapp = $user['whatsapp'] ?? '';

    if ($messageType !== 'error') {
        if ($passwordHash !== '' || $newImagePath !== '') {
            if ($passwordHash !== '' && $newImagePath !== '') {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, bio=?, phone=?, address=?, facebook=?, linkedin=?, github=?, telegram=?, instagram=?, youtube=?, tiktok=?, line_id=?, viber=?, whatsapp=?, password=?, profile_image=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param('sssssssssssssssssi', $name, $email, $bio, $phone, $address, $facebook, $linkedin, $github, $telegram, $instagram, $youtube, $tiktok, $line_id, $viber, $whatsapp, $passwordHash, $newImagePath, $userId);
            } elseif ($passwordHash !== '') {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, bio=?, phone=?, address=?, facebook=?, linkedin=?, github=?, telegram=?, instagram=?, youtube=?, tiktok=?, line_id=?, viber=?, whatsapp=?, password=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param('ssssssssssssssssi', $name, $email, $bio, $phone, $address, $facebook, $linkedin, $github, $telegram, $instagram, $youtube, $tiktok, $line_id, $viber, $whatsapp, $passwordHash, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, bio=?, phone=?, address=?, facebook=?, linkedin=?, github=?, telegram=?, instagram=?, youtube=?, tiktok=?, line_id=?, viber=?, whatsapp=?, profile_image=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param('ssssssssssssssssi', $name, $email, $bio, $phone, $address, $facebook, $linkedin, $github, $telegram, $instagram, $youtube, $tiktok, $line_id, $viber, $whatsapp, $newImagePath, $userId);
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, bio=?, phone=?, address=?, facebook=?, linkedin=?, github=?, telegram=?, instagram=?, youtube=?, tiktok=?, line_id=?, viber=?, whatsapp=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('sssssssssssssssi', $name, $email, $bio, $phone, $address, $facebook, $linkedin, $github, $telegram, $instagram, $youtube, $tiktok, $line_id, $viber, $whatsapp, $userId);
        }

        if ($stmt->execute()) {
            if ($newImagePath !== '' && !empty($currentImage) && $currentImage !== $newImagePath) {
                remove_local_profile_image($currentImage);
            }

            $_SESSION['admin_profile_flash'] = [
                'type' => 'success',
                'message' => 'Profile updated successfully.'
            ];
            header('Location: profile.php');
            exit;
        }

        $message = 'Failed to update profile.';
        $messageType = 'error';
        if ($newImagePath !== '') {
            remove_local_profile_image($newImagePath);
        }
    }
}

$flash = $_SESSION['admin_profile_flash'] ?? null;
unset($_SESSION['admin_profile_flash']);

$user = array_merge($user, [
    'profile_image' => $user['profile_image'] ?? '',
]);

$profileImage = !empty($user['profile_image']) ? $user['profile_image'] : '../images/default-avatar.svg';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-slate-50 overflow-x-hidden">
    <div class="min-h-screen flex">
        <?php include "../include/admin_header.php"; ?>

        <main class="flex-1 min-w-0 p-6">
            <div class="admin-page-head">
                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-user-gear"></i></div>
                    <div>
                        <h1 class="admin-page-title">Admin Profile</h1>
                        <p class="admin-page-sub">Update your account information</p>
                    </div>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="mb-4 rounded-2xl border px-4 py-3 text-sm font-semibold <?= $flash['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="mb-4 rounded-2xl border px-4 py-3 text-sm font-semibold <?= $messageType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' ?>">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
                    <section class="admin-card">
                        <div class="admin-card-head">
                            <div class="admin-card-title"><i class="fa-solid fa-user fa-icon-chip"></i> <span>Account</span></div>
                        </div>
                        <div class="p-5">
                            <div class="flex flex-col items-center text-center">
                                <img id="profilePreview" src="<?= e($profileImage) ?>" class="h-28 w-28 rounded-full object-cover border-4 border-cyan-100 shadow-sm cursor-zoom-in admin-avatar"
                                    onclick="openLightbox(this.src, '<?= e($user['name']) ?>')">
                                <div class="mt-4">
                                    <h2 class="text-xl font-bold text-slate-800"><?= e($user['name']) ?></h2>
                                    <p class="text-sm text-slate-500 mt-1"><?= e($user['email']) ?></p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="ui-label">Profile Image</label>
                                <input type="file" name="profile_image" id="profileImageInput" accept="image/*"
                                    class="input-base">
                            </div>

                            <div class="mt-6 rounded-2xl bg-cyan-50 p-4 text-sm text-slate-600">
                                <div class="flex items-center gap-2 font-semibold text-slate-700">
                                    <i class="fa-solid fa-circle-info text-teal-600"></i>
                                    Account
                                </div>
                                <p class="mt-2">Use this page to update your own administrator profile details.</p>
                            </div>
                        </div>
                    </section>

                    <div class="space-y-6">
                        <section class="admin-form-card">
                            <h3 class="form-section-title"><i class="fa-solid fa-id-card"></i> Personal Information</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label                                 class="ui-label">Full Name</label>
                                    <input type="text" name="name" value="<?= e($user['name']) ?>" required
                                        class="w-full input-base">
                                </div>
                                <div>
                                    <label                                 class="ui-label">Email</label>
                                    <input type="email" name="email" value="<?= e($user['email']) ?>" required
                                        class="w-full input-base">
                                </div>
                                <div class="md:col-span-2">
                                    <label                                 class="ui-label">Bio</label>
                                    <textarea name="bio" rows="4"
                                        class="w-full input-base"><?= e($user['bio'] ?? '') ?></textarea>
                                </div>
                                <div>
                                    <label                                 class="ui-label">Phone</label>
                                    <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>"
                                        class="w-full input-base">
                                </div>
                                <div>
                                    <label                                 class="ui-label">Address</label>
                                    <input type="text" name="address" value="<?= e($user['address'] ?? '') ?>"
                                        class="w-full input-base">
                                </div>
                            </div>
                        </section>

                        <section class="admin-form-card">
                            <h3 class="form-section-title"><i class="fa-solid fa-lock"></i> Change Password</h3>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label                                 class="ui-label">Current Password</label>
                                    <input type="password" name="current_password"
                                        class="w-full input-base">
                                </div>
                                <div>
                                    <label                                 class="ui-label">New Password</label>
                                    <input type="password" name="new_password"
                                        class="w-full input-base">
                                </div>
                                <div>
                                    <label                                 class="ui-label">Confirm Password</label>
                                    <input type="password" name="confirm_password"
                                        class="w-full input-base">
                                </div>
                            </div>
                        </section>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk mr-1"></i> Save Profile
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        const profileImageInput = document.getElementById('profileImageInput');
        const profilePreview = document.getElementById('profilePreview');

        if (profileImageInput && profilePreview) {
            profileImageInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function (e) {
                    profilePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }
    </script>

    <?php include "../include/admin_footer.php"; ?>
</body>

</html>

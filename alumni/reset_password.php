<?php
session_start();
require_once "../config/db.php";
require_once "../include/auth_helpers.php";
require_once "../include/auth_layout.php";

if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['reset_verified_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_verified_email'];
$error = "";
$success = "";
$showForm = true;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($password === "" || $confirm_password === "") {
        $error = "Please fill all fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND email_verified_at IS NOT NULL");
        $stmt->bind_param("ss", $hashedPassword, $email);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            delete_otp_records($conn, $email, 'reset');
            unset($_SESSION['reset_verified']);
            unset($_SESSION['reset_verified_email']);
            $success = "Password updated successfully. Redirecting to login...";
            $showForm = false;
            header("refresh:2;url=login.php");
        } else {
            $error = "Unable to update password. Your account may not be verified.";
        }
        $stmt->close();
    }
}

$navVariant = 'auth';
$navShowHome = true;
$navShowRegister = true;
$navShowLogin = true;

auth_layout_head('Reset Password | Alumni Network');
include "../include/public_nav.php";
auth_layout_start(
    'Reset Password',
    'Create New Password',
    'Your identity has been verified. Choose a strong new password for your account.',
    'Reset Password',
    'Create a new password for <strong class="text-teal-700">' . htmlspecialchars($email) . '</strong>'
);

echo render_auth_alert($error);
echo render_auth_alert($success, 'success');

if ($showForm):
?>
                <form method="POST" action="" class="auth-form auth-form-space-y-5">
                    <div>
                        <label class="ui-label">New Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="newPassword" placeholder="At least 6 characters" class="input-base pr-12" required autocomplete="new-password">
                            <button type="button" onclick="togglePassword('newPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-teal-600 transition" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
                        </div>
                        <div id="passwordStrength" class="mt-2 text-xs font-bold transition-all"></div>
                    </div>
                    <div>
                        <label class="ui-label">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm new password" class="input-base pr-12" required autocomplete="new-password">
                            <button type="button" onclick="togglePassword('confirmPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-teal-600 transition" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-key"></i> Update Password</button>
                </form>

                <div class="auth-footer-link">
                    <a href="login.php" class="btn btn-ghost btn-block"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
                </div>
<?php
endif;

auth_layout_end();
include "../include/footer.php";
include "../include/ui_components.php";
?>
<script>
document.getElementById('newPassword').addEventListener('input', function() {
    var val = this.value;
    var el = document.getElementById('passwordStrength');
    if (val.length === 0) {
        el.textContent = '';
        return;
    }
    if (val.length < 6) {
        el.textContent = 'Weak password';
        el.className = 'mt-2 text-xs font-bold transition-all text-red-500';
    } else {
        el.textContent = 'Strong password';
        el.className = 'mt-2 text-xs font-bold transition-all text-green-500';
    }
});
</script>
<?php
auth_layout_scripts();

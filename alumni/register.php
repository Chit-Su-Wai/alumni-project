<?php
session_start();
require_once "../config/db.php";
require_once "../include/mail_helper.php";
require_once "../include/auth_helpers.php";
require_once "../include/auth_layout.php";

$error = "";
$pendingEmail = $_SESSION['register_data']['email'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $approved_id = trim($_POST["approved_id"] ?? "");
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($approved_id === "" || $name === "" || $email === "" || $password === "" || $confirm_password === "") {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!is_valid_existing_email($email)) {
        $error = "Please enter a valid, existing email address. The domain could not be verified.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Password and Confirm Password do not match.";
    } else {
        $checkApproved = $conn->prepare("SELECT approved_id FROM approved_students WHERE approved_id = ?");
        $checkApproved->bind_param("s", $approved_id);
        $checkApproved->execute();
        $approvedResult = $checkApproved->get_result();

        if ($approvedResult->num_rows === 0) {
            $error = "Invalid Approved ID. Please contact admin.";
        } else {
            $checkUsed = $conn->prepare("SELECT id FROM users WHERE approved_id = ?");
            $checkUsed->bind_param("s", $approved_id);
            $checkUsed->execute();
            $usedResult = $checkUsed->get_result();

            if ($usedResult->num_rows > 0) {
                        $error = "This Approved ID has already been registered.";
            } else {
                $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $checkEmail->bind_param("s", $email);
                $checkEmail->execute();
                $emailResult = $checkEmail->get_result();

                if ($emailResult->num_rows > 0) {
                        $error = "This email is already registered. Please login or use a different email.";
                } else {
                    $_SESSION['register_data'] = [
                        'approved_id' => $approved_id,
                        'name' => $name,
                        'email' => $email,
                        'password' => $password,
                    ];

                    $otp = generate_otp();
                    store_otp($conn, $email, $otp, 'register');
                    $sent = send_otp_email($email, $otp, 'register');

                    if ($sent) {
                        header("Location: verify_otp.php?mode=register");
                        exit;
                    }

                    unset($_SESSION['register_data']);
                        $error = "Could not send verification email. Please check your email address and try again.";
                }
            }
        }
    }
}

$pendingEmail = $_SESSION['register_data']['email'] ?? '';

$navVariant = 'auth';
$navShowHome = true;
$navShowRegister = false;
$navShowLogin = true;

auth_layout_head('Register | Alumni Network');
include "../include/public_nav.php";
auth_layout_start(
    'Verified Alumni Community',
    'Join Our Alumni Network',
    'Register with your Approved ID and connect with fellow graduates, share posts, manage experience, and build your professional network.',
    'Create Account',
    '<span data-t="register_form_desc">Use your admin-approved ID to register.</span>'
);

echo render_auth_alert($error);

if ($pendingEmail !== '') {
    echo render_auth_alert('You have a pending verification for ' . $pendingEmail . '. Complete verification to finish creating your account.', 'info');
}
?>
                <form method="POST" action="" class="auth-form auth-form-space-y-5">
                    <div>
                        <label class="ui-label" data-t="approved_id">Approved ID</label>
                        <input type="text" name="approved_id" value="<?= htmlspecialchars($_POST["approved_id"] ?? "") ?>" placeholder="Example: 0001" class="input-base" required>
                    </div>
                    <div>
                        <label class="ui-label" data-t="name">Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($_POST["name"] ?? "") ?>" placeholder="Enter your name" class="input-base" required>
                    </div>
                    <div>
                        <label class="ui-label" data-t="email">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" placeholder="Enter your email" class="input-base" required autocomplete="email">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ui-label" data-t="password">Password</label>
                            <div class="relative">
                                <input type="password" name="password" id="passwordInput" placeholder="At least 6 characters" class="input-base pr-12" required autocomplete="new-password">
                                <button type="button" onclick="togglePassword('passwordInput', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-teal-600 transition" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
                            </div>
                            <div id="passwordStrength" class="mt-2 text-xs font-bold transition-all"></div>
                        </div>
                        <div>
                            <label class="ui-label" data-t="confirm_password">Confirm Password</label>
                            <div class="relative">
                                <input type="password" name="confirm_password" id="confirmPasswordInput" placeholder="Confirm password" class="input-base pr-12" required autocomplete="new-password">
                                <button type="button" onclick="togglePassword('confirmPasswordInput', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-teal-600 transition" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" data-t="register_btn"><i class="fa-solid fa-user-plus"></i> Register</button>
                </form>

                <p class="auth-footer-link">
                    <span data-t="already_have_account">Already have an account?</span>
                    <a href="login.php" class="font-black text-teal-700 hover:underline" data-t="login">Login</a>
                </p>
<?php
auth_layout_end();
include "../include/footer.php";
include "../include/ui_components.php";
?>
<script>
document.getElementById('passwordInput').addEventListener('input', function() {
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

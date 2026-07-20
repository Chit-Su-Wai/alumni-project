<?php
session_start();
require_once "../config/db.php";
require_once "../include/mail_helper.php";
require_once "../include/auth_helpers.php";
require_once "../include/auth_layout.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if ($email === "") {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!is_valid_existing_email($email)) {
        $error = "Please enter a valid, existing email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, email, email_verified_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "No account found with this email address.";
        } else {
            $user = $result->fetch_assoc();

            if (empty($user['email_verified_at'])) {
                $error = "This account has not been verified yet. Password reset is only available for verified email addresses.";
            } else {
                $_SESSION['reset_data'] = ['email' => $email];
                $otp = generate_otp();
                store_otp($conn, $email, $otp, 'reset');
                $sent = send_otp_email($email, $otp, 'reset');

                if ($sent) {
                    header("Location: verify_otp.php?mode=reset");
                    exit;
                }

                unset($_SESSION['reset_data']);
                $error = "Could not send OTP email. Please try again later.";
            }
        }
        $stmt->close();
    }
}

$navVariant = 'auth';
$navShowHome = true;
$navShowRegister = true;
$navShowLogin = true;

auth_layout_head('Forgot Password | Alumni Network');
include "../include/public_nav.php";
auth_layout_start(
    'Password Reset',
    'Forgot Password',
    "Enter your registered email and we'll send you an OTP to reset your password.",
    'Forgot Password',
    '<span data-t="forgot_desc">Enter your registered and verified email address.</span>'
);

echo render_auth_alert($error);
?>
                <form method="POST" action="" class="auth-form auth-form-space-y-5">
                    <div>
                        <label class="ui-label" data-t="email">Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" required placeholder="Enter your email" class="input-base" autocomplete="email">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-paper-plane"></i> Send OTP</button>
                </form>

                <div class="auth-footer-link">
                    <a href="login.php" class="btn btn-ghost btn-block"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
                </div>
<?php
auth_layout_end();
include "../include/footer.php";
include "../include/ui_components.php";
auth_layout_scripts();

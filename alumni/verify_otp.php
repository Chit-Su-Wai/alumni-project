<?php
session_start();
require_once "../config/db.php";
require_once "../include/mail_helper.php";
require_once "../include/auth_helpers.php";
require_once "../include/auth_layout.php";

$mode = $_GET['mode'] ?? '';
$error = '';
$success = '';
$email = '';
$showForm = true;

if ($mode === 'register') {
    $data = $_SESSION['register_data'] ?? null;
    if (!$data || empty($data['email'])) {
        header("Location: register.php");
        exit;
    }
    $email = $data['email'];
} elseif ($mode === 'reset') {
    $data = $_SESSION['reset_data'] ?? null;
    if (!$data || empty($data['email'])) {
        header("Location: forgot_password.php");
        exit;
    }
    $email = $data['email'];
} else {
    header("Location: homepage.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp = trim($_POST['otp'] ?? '');

    if ($otp === '') {
        $error = "Please enter the 6-digit OTP code.";
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $error = "OTP must be a 6-digit number.";
    } else {
        $row = fetch_otp_record($conn, $email, $otp, $mode);

        if ($row) {
            if (otp_is_expired($row['expires_at'])) {
                $error = "OTP has expired. Please request a new one.";
            } else {
                if ($mode === 'register') {
                    $regData = $_SESSION['register_data'];
                    $hashedPassword = password_hash($regData['password'], PASSWORD_DEFAULT);

                    $insert = $conn->prepare(
                        "INSERT INTO users (approved_id, name, email, password, role, email_verified_at)
                         VALUES (?, ?, ?, ?, 'user', NOW())"
                    );
                    $insert->bind_param("ssss",
                        $regData['approved_id'],
                        $regData['name'],
                        $regData['email'],
                        $hashedPassword
                    );

                    if ($insert->execute()) {
                        mark_otp_verified($conn, (int)$row['id']);
                        delete_otp_records($conn, $email, 'register');
                        unset($_SESSION['register_data']);
                        $success = "Email verified! Your account has been created successfully.";
                        $showForm = false;
                    } else {
                        $error = "Account creation failed. This email or Approved ID may already be registered.";
                    }
                } else {
                    mark_otp_verified($conn, (int)$row['id']);
                    $_SESSION['reset_verified'] = true;
                    $_SESSION['reset_verified_email'] = $email;
                    unset($_SESSION['reset_data']);
                    header("Location: reset_password.php");
                    exit;
                }
            }
        } else {
            $error = "Incorrect OTP code. Please check and try again.";
        }
    }
}

if (isset($_GET['resend'])) {
    $otp = generate_otp();
    store_otp($conn, $email, $otp, $mode);
    if (send_otp_email($email, $otp, $mode)) {
        $success = 'A new OTP has been sent to your email. It expires in ' . ALUMNI_OTP_EXPIRY_MINUTES . ' minutes.';
    } else {
        $error = "Could not resend OTP. Please try again later.";
    }
}

$navVariant = 'auth';
$navShowHome = true;
$navShowRegister = false;
$navShowLogin = false;

auth_layout_head('Verify OTP | Alumni Network');
include "../include/public_nav.php";
auth_layout_start(
    'Email Verification',
    'Verify Your Email',
    'Enter the 6-digit OTP sent to your email address to continue.',
    'Email Verification',
    'A 6-digit code was sent to <strong class="text-teal-700">' . htmlspecialchars($email) . '</strong>'
);

echo render_auth_alert($error);
echo render_auth_alert($success, 'success');

if ($showForm):
?>
                <form method="POST" action="" class="auth-form auth-form-space-y-5">
                    <div>
                        <label class="ui-label">OTP Code</label>
                    <input type="text" name="otp" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
                        placeholder="Enter 6-digit OTP" class="input-base text-center text-2xl font-bold tracking-[0.5em]"
                        required autocomplete="one-time-code">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-circle-check"></i> Verify OTP
                    </button>
                </form>

                <p class="auth-footer-link">
                    Didn't receive the code?
                    <a href="?mode=<?= htmlspecialchars($mode) ?>&resend=1" class="font-black text-teal-700 hover:underline">Resend OTP</a>
                </p>

                <div class="mt-4 text-center">
                    <a href="<?= $mode === 'register' ? 'register.php' : 'forgot_password.php' ?>" class="btn btn-ghost btn-block">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
<?php
endif;

if (!$showForm && $mode === 'register'):
?>
                <div class="auth-form mt-6 text-center">
                    <a href="login.php" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Login Now
                    </a>
                </div>
<?php
endif;

auth_layout_end();
include "../include/footer.php";
include "../include/ui_components.php";
auth_layout_scripts();

<?php
session_start();

require_once "../config/db.php";
require_once "../include/auth_helpers.php";
require_once "../include/auth_layout.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please fill all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");

        if (!$stmt) {
            $error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $userRole = $user["role"] ?? "user";
                $userStatus = $user["status"] ?? "active";

                if ($userRole === "user" && empty($user["email_verified_at"])) {
                    $error = "This account is not verified. Please complete email verification or register again.";
                } elseif (password_verify($password, $user["password"])) {
                    if (($userRole === "admin" || $userRole === "super_admin") && $userStatus !== "active") {
                        $error = "This admin account is inactive.";
                    } else {
                        session_regenerate_id(true);

                        $_SESSION["user_id"] = $user["id"];
                        $_SESSION["user_name"] = $user["name"];
                        $_SESSION["user_email"] = $user["email"];
                        $_SESSION["role"] = ($userRole === "admin" || $userRole === "super_admin") ? "admin" : $userRole;
                        $_SESSION["admin_role"] = $userRole;

                        if ($userRole === "admin" || $userRole === "super_admin") {
                            header("Location: ../admin/dashboard.php");
                            exit;
                        }

                        header("Location: profile.php");
                        exit;
                    }
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "No account found with this email address.";
            }

            $stmt->close();
        }
    }
}

$navVariant = 'auth';
$navShowHome = true;
$navShowRegister = true;
$navShowLogin = false;

auth_layout_head('Login | Alumni Network');
include "../include/public_nav.php";
auth_layout_start(
    'Alumni Community',
    'Welcome Back',
    'Login and reconnect with your alumni community.',
    'Login',
    '<span data-t="sign_in_desc">Sign in to your account</span>'
);

echo render_auth_alert($error);
?>
                <form method="POST" action="" class="auth-form auth-form-space-y-5">
                    <div>
                        <label class="ui-label" data-t="email">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" required class="input-base" autocomplete="email">
                    </div>

                    <div>
                        <label class="ui-label" data-t="password">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="loginPassword" required class="input-base pr-12" autocomplete="current-password">
                            <button type="button" onclick="togglePassword('loginPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-teal-600 transition" aria-label="Toggle password visibility">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div class="mt-3 text-right">
                            <a href="forgot_password.php" class="text-sm font-bold text-teal-700 hover:underline" data-t="forgot_password">Forgot Password?</a>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" data-t="login">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
                    </button>
                </form>

                <p class="auth-footer-link">
                    <span data-t="no_account">Don't have an account?</span>
                    <a href="register.php" class="font-black text-teal-700 hover:underline" data-t="register">Register</a>
                </p>
<?php
auth_layout_end();
include "../include/footer.php";
include "../include/ui_components.php";
auth_layout_scripts();

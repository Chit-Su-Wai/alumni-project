<?php
session_start();

require_once "../config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please fill all fields.";
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

                if (password_verify($password, $user["password"])) {

                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["name"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];

                    if ($user["role"] == "admin") {

                        header("Location: ../admin/dashboard.php");
                        exit;

                    } else {

                        header("Location: profile.php");
                        exit;

                    }

                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "Email not found.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Alumni Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-white via-cyan-50 to-teal-50 text-slate-800">

    <header class="sticky top-0 z-50 px-3 py-4 sm:px-4">
        <nav
            class="mx-auto flex max-w-6xl items-center justify-between rounded-full border border-cyan-100 bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 px-4 py-3 shadow-md">
            <a href="homepage.php" class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white">
                    AN
                </div>
                <span class="font-bold text-teal-700" data-t="alumni_network">
                    Alumni Network
                </span>
            </a>

            <div class="hidden items-center gap-2 md:flex">
                <a href="homepage.php"
                    class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-2 text-sm font-bold text-white shadow-md" data-t="home">
                    Home
                </a>

                <button onclick="toggleTheme()"
                    class="theme-toggle flex h-9 w-9 items-center justify-center rounded-full border border-cyan-100 bg-white text-sm shadow-sm hover:bg-cyan-50 transition">

                    <i class="fa-solid fa-moon"></i>

                </button>

                <a href="register.php"
                    class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-2 text-sm font-bold text-white shadow-md" data-t="register">
                    Register
                </a>
            </div>

            <button type="button" id="menuBtn"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white md:hidden"
                aria-label="Open menu">
                ☰
            </button>
        </nav>

        <div id="mobileMenu"
            class="mx-auto mt-3 hidden max-w-6xl rounded-3xl border border-cyan-100 bg-white p-3 shadow-xl md:hidden">
            <a href="homepage.php"
                class="block rounded-xl px-4 py-3 font-semibold text-slate-700 hover:bg-cyan-50 hover:text-teal-700" data-t="home">
                Home
            </a>

            <a href="register.php"
                class="mt-1 block rounded-xl px-4 py-3 font-semibold text-slate-700 hover:bg-cyan-50 hover:text-teal-700" data-t="register">
                Register
            </a>

            <button onclick="toggleTheme()" class="theme-toggle mt-2 flex w-full items-center justify-center gap-2 rounded-xl border bg-white px-3 py-2 text-sm font-semibold text-slate-700">

                <i class="fa-solid fa-moon"></i> <span>Theme</span>

            </button>
        </div>
    </header>

    <main class="flex min-h-[calc(100vh-96px)] items-center justify-center px-4 py-6">
        <section
            class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-cyan-100 bg-white/85 shadow-2xl backdrop-blur lg:grid-cols-2">

            <div class="relative hidden min-h-[560px] lg:block">
                <img src="../images/home.jpg" alt="Alumni" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 to-black/20"></div>
                <div class="absolute inset-0 flex items-center p-10">
                    <div>
                        <span
                            class="inline-flex rounded-full bg-cyan-400/20 px-4 py-2 text-sm font-bold text-cyan-100 backdrop-blur">
                            <span data-t="verified_alumni_community">Alumni Community</span>
                        </span>

                        <h1 class="mt-5 text-5xl font-black leading-tight text-white" data-t="welcome_back">
                            Welcome Back
                        </h1>

                        <p class="mt-4 max-w-md text-sm leading-7 text-cyan-100" data-t="login_and_reconnect">
                            Login and reconnect with your alumni community.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center p-6 sm:p-8 lg:p-10">
                <div class="w-full max-w-md">
                    <div class="text-center">
                        <h2 class="text-3xl font-black text-teal-700" data-t="login_title">
                            Login
                        </h2>

                        <p class="mt-2 text-sm text-slate-500" data-t="sign_in_desc">
                            Sign in to your account
                        </p>
                    </div>

                    <?php if ($error): ?>
                        <div
                            class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="mt-6 space-y-5">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700" data-t="email">
                                Email
                            </label>

                            <input type="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                                required
                                class="w-full rounded-2xl border border-cyan-100 bg-cyan-50/60 px-4 py-3 text-sm outline-none transition focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700" data-t="password">
                                Password
                            </label>

                            <div class="relative">
                                <input type="password" name="password" id="loginPassword" required
                                    class="w-full rounded-2xl border border-cyan-100 bg-cyan-50/60 px-4 py-3 pr-12 text-sm outline-none transition focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                                <button type="button" onclick="togglePassword('loginPassword', this)"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-teal-600 transition">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>

                            <div class="mt-3 text-right">
                                <a href="forgot_password.php" class="text-sm font-bold text-teal-700 hover:underline" data-t="forgot_password">
                                    Forgot Password?
                                </a>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full rounded-2xl bg-gradient-to-r from-cyan-400 to-teal-500 py-3 font-black text-white shadow-lg transition hover:scale-[1.01] hover:shadow-xl" data-t="login">
                            Login
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        <span data-t="no_account">Don't have an account?</span>
                        <a href="register.php" class="font-black text-teal-700 hover:underline" data-t="register">
                            Register
                        </a>
                    </p>
                </div>
            </div>
        </section>
    </main>

    <?php include "../include/footer.php"; ?>

    <script>
        const menuBtn = document.getElementById("menuBtn");
        const mobileMenu = document.getElementById("mobileMenu");

        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener("click", () => {
                mobileMenu.classList.toggle("hidden");
            });
        }

        function togglePassword(inputId, btn) {
            var input = document.getElementById(inputId);
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa-regular fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa-regular fa-eye';
            }
        }
    </script>

</body>

</html>

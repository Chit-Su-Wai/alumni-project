<?php
session_start();
require_once "../config/db.php";

$error = "";
$success = "";

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
                    $error = "This email is already registered.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $insertUser = $conn->prepare(
                        "INSERT INTO users (approved_id, name, email, password, role) VALUES (?, ?, ?, ?, 'user')"
                    );

                    $insertUser->bind_param(
                        "ssss",
                        $approved_id,
                        $name,
                        $email,
                        $hashedPassword
                    );

                    if ($insertUser->execute()) {
                        $success = "Registration successful. You can now login.";
                    } else {
                        $error = "Registration failed: " . $conn->error;
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Alumni Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-white via-cyan-50 to-teal-50 text-slate-800">
<header class="sticky top-0 z-50 px-3 py-4 sm:px-4">
    <nav class="mx-auto flex max-w-6xl items-center justify-between rounded-full border border-cyan-100 bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 px-4 py-3 shadow-md">

        <a href="homepage.php" class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 font-black text-white">
                AN
            </div>

            <span class="font-bold text-teal-700" data-t="alumni_network">
                Alumni Network
            </span>
        </a>

        <div class="hidden md:flex items-center gap-2">
            <a href="homepage.php"
                class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-2 text-sm font-bold text-white shadow-md" data-t="home">
                Home
            </a>

            <button onclick="toggleTheme()"
                class="theme-toggle flex h-9 w-9 items-center justify-center rounded-full border border-cyan-100 bg-white text-sm shadow-sm hover:bg-cyan-50 transition">

                <i class="fa-solid fa-moon"></i>

            </button>

            <a href="login.php"
                class="rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-2 text-sm font-bold text-white shadow-md" data-t="login">
                Login
            </a>
        </div>

        <button id="menuBtn"
            class="flex md:hidden h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-teal-500 text-white font-black">
            ☰
        </button>

    </nav>

    <div id="mobileMenu"
        class="hidden md:hidden mx-auto mt-3 max-w-6xl rounded-3xl border border-cyan-100 bg-white p-3 shadow-xl">

        <a href="homepage.php"
            class="block rounded-xl px-4 py-3 font-semibold hover:bg-cyan-50" data-t="home">
            Home
        </a>

        <a href="login.php"
            class="mt-1 block rounded-xl px-4 py-3 font-semibold hover:bg-cyan-50" data-t="login">
            Login
        </a>

        <button onclick="toggleTheme()" class="theme-toggle mt-2 flex w-full items-center justify-center gap-2 rounded-xl border bg-white px-3 py-2 text-sm font-semibold">

            <i class="fa-solid fa-moon"></i> <span>Theme</span>

        </button>

    </div>
</header>

    <main class="flex min-h-[calc(100vh-96px)] items-center justify-center px-4 py-8">
        <section
            class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-cyan-100 bg-white/80 shadow-2xl backdrop-blur lg:grid-cols-2">

            <div class="relative hidden min-h-[620px] overflow-hidden lg:block">
                <img src="../images/home.jpg" alt="Alumni Network" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-900/50 to-teal-900/40"></div>

                <div class="absolute inset-0 flex items-center p-10">
                    <div>
                        <span class="inline-flex rounded-full bg-cyan-400/20 px-4 py-2 text-sm font-bold text-cyan-100">
                            <span data-t="verified_alumni_community">Verified Alumni Community</span>
                        </span>

                        <h1 class="mt-5 text-5xl font-black leading-tight text-white" data-t="join_our_network">
                            Join Our Alumni Network
                        </h1>

                        <p class="mt-4 max-w-md text-sm leading-7 text-slate-200" data-t="register_desc">
                            Register with your Approved ID and connect with fellow graduates,
                            share posts, manage experience, and build your professional network.
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-10">
                <div class="mx-auto max-w-md">
                    <div class="text-center">
                        <h2 class="text-3xl font-black text-teal-800" data-t="create_account_title">Create Account</h2>
                        <p class="mt-2 text-sm text-slate-500" data-t="register_form_desc">
                            Use your admin-approved ID to register.
                        </p>
                    </div>

                    <?php if ($error): ?>
                        <div
                            class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div
                            class="mt-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                            <?= htmlspecialchars($success) ?>
                            <a href="login.php" class="ml-1 font-black underline" data-t="login_now">Login now</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="mt-6 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700" data-t="approved_id">Approved ID</label>
                            <input type="text" name="approved_id"
                                value="<?= htmlspecialchars($_POST["approved_id"] ?? "") ?>" placeholder="Example: 0001"
                                class="w-full rounded-2xl border border-cyan-100 bg-cyan-50/40 px-4 py-3 text-sm outline-none transition focus:border-teal-400 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                required>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700" data-t="name">Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_POST["name"] ?? "") ?>"
                                placeholder="Enter your name"
                                class="w-full rounded-2xl border border-cyan-100 bg-cyan-50/40 px-4 py-3 text-sm outline-none transition focus:border-teal-400 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                required>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700" data-t="email">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                                placeholder="Enter your email"
                                class="w-full rounded-2xl border border-cyan-100 bg-cyan-50/40 px-4 py-3 text-sm outline-none transition focus:border-teal-400 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                required>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700" data-t="password">Password</label>
                                <div class="relative">
                                    <input type="password" name="password" id="passwordInput" placeholder="At least 6 characters"
                                        class="w-full rounded-2xl border border-cyan-100 bg-cyan-50/40 px-4 py-3 pr-12 text-sm outline-none transition focus:border-teal-400 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                        required>
                                    <button type="button" onclick="togglePassword('passwordInput', this)"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-teal-600 transition">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordStrength" class="mt-2 text-xs font-bold transition-all"></div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700" data-t="confirm_password">Confirm Password</label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" id="confirmPasswordInput" placeholder="Confirm password"
                                        class="w-full rounded-2xl border border-cyan-100 bg-cyan-50/40 px-4 py-3 pr-12 text-sm outline-none transition focus:border-teal-400 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                        required>
                                    <button type="button" onclick="togglePassword('confirmPasswordInput', this)"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-teal-600 transition">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full rounded-2xl bg-gradient-to-r from-cyan-400 to-teal-500 px-5 py-3 font-black text-white shadow-lg transition hover:scale-[1.01] hover:shadow-xl" data-t="register_btn">
                            Register
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        <span data-t="already_have_account">Already have an account?</span>
                        <a href="login.php" class="font-black text-teal-700 hover:underline" data-t="login">Login</a>
                    </p>
                </div>
            </div>
        </section>
    </main>
    <?php include "../include/footer.php"; ?>

    <script>
    const menuBtn = document.getElementById("menuBtn");
    const mobileMenu = document.getElementById("mobileMenu");

    menuBtn.addEventListener("click", () => {
        mobileMenu.classList.toggle("hidden");
    });

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

    /* Password strength checker */
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
</body>

</html>

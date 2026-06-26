<?php
session_start();
require_once "../config/db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if ($email === "") {
        $error = "Please enter your email.";
    } else {

        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $_SESSION["reset_email"] = $email;

            header("Location: reset_password.php");
            exit;

        } else {
            $error = "Email not found.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Alumni Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-white via-cyan-50 to-teal-50">

<div class="flex min-h-screen items-center justify-center px-4">

    <div class="w-full max-w-md rounded-[2rem] border border-cyan-100 bg-white p-8 shadow-2xl">

        <div class="text-center">
            <h1 class="text-3xl font-black text-teal-700">
                Forgot Password
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Enter your registered email address.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-5">

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    required
                    placeholder="Enter your email"
                    class="w-full rounded-2xl border border-cyan-100 bg-cyan-50/60 px-4 py-3 outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                >
            </div>

            <button
                type="submit"
                class="w-full rounded-2xl bg-gradient-to-r from-cyan-400 to-teal-500 py-3 font-black text-white shadow-lg"
            >
                Continue
            </button>

        </form>

        <div class="mt-6 text-center">
            <a
                href="login.php"
                class="text-sm font-bold text-teal-700 hover:underline"
            >
                Back to Login
            </a>
        </div>

    </div>

</div>

</body>
</html>
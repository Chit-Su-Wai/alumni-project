<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["reset_email"])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION["reset_email"];

$error = "";
$success = "";

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

        $stmt = $conn->prepare(
            "UPDATE users SET password = ? WHERE email = ?"
        );

        $stmt->bind_param(
            "ss",
            $hashedPassword,
            $email
        );

        if ($stmt->execute()) {

            unset($_SESSION["reset_email"]);

            $success = "Password updated successfully.";

            header("refresh:2;url=login.php");

        } else {
            $error = "Something went wrong.";
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
<title>Reset Password</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-white via-cyan-50 to-teal-50">

<div class="flex min-h-screen items-center justify-center px-4">

    <div class="w-full max-w-md rounded-[2rem] border border-cyan-100 bg-white p-8 shadow-2xl">

        <h1 class="text-center text-3xl font-black text-teal-700">
            Reset Password
        </h1>

        <p class="mt-2 text-center text-sm text-slate-500">
            Create a new password for your account.
        </p>

        <?php if ($error): ?>
            <div class="mt-5 rounded-xl bg-red-50 p-3 text-red-700">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mt-5 rounded-xl bg-green-50 p-3 text-green-700">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-5">

            <div>
                <label class="mb-2 block text-sm font-bold">
                    New Password
                </label>

                <input
                    type="password"
                    name="password"
                    required
                    class="w-full rounded-2xl border border-cyan-100 bg-cyan-50 px-4 py-3 outline-none focus:border-cyan-400"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold">
                    Confirm Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    required
                    class="w-full rounded-2xl border border-cyan-100 bg-cyan-50 px-4 py-3 outline-none focus:border-cyan-400"
                >
            </div>

            <button
                type="submit"
                class="w-full rounded-2xl bg-gradient-to-r from-cyan-400 to-teal-500 py-3 font-black text-white"
            >
                Update Password
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
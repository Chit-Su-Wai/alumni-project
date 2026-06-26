
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-slate-50">

<?php include "../include/user_header.php"; ?>

<div class="max-w-7xl mx-auto px-4 py-8">

<?php if(isset($_GET['success'])): ?>

<div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6">
    Message sent successfully.
</div>

<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-8">


<!-- LEFT -->

<div class="space-y-6">

    <div class="bg-white rounded-3xl p-6 shadow">

        <h2 class="text-2xl font-bold mb-6">
            Contact Information
        </h2>

        <div class="space-y-5">

            <div class="flex items-center gap-4">
                <i class="fa-solid fa-envelope text-cyan-500 text-xl"></i>
                <span>ucs.htd@gmail.com</span>
            </div>

            <div class="flex items-center gap-4">
                <i class="fa-solid fa-phone text-cyan-500 text-xl"></i>
                <span>09783453901</span>
            </div>

            <div class="flex items-center gap-4">
                <i class="fa-solid fa-location-dot text-cyan-500 text-xl"></i>
                <span>University of Computer Studies, Hinthada</span>
            </div>

        </div>

    </div>

    <div class="bg-white rounded-3xl p-6 shadow">

        <h2 class="text-2xl font-bold mb-5">
            Send Message
        </h2>

        <form action="save_contact.php" method="POST">

            <input
            type="text"
            name="name"
            placeholder="Your Name"
            required
            class="w-full border rounded-xl px-4 py-3 mb-4">

            <input
            type="email"
            name="email"
            placeholder="Your Email"
            required
            class="w-full border rounded-xl px-4 py-3 mb-4">

            <input
            type="text"
            name="subject"
            placeholder="Subject"
            required
            class="w-full border rounded-xl px-4 py-3 mb-4">

            <textarea
            name="message"
            rows="5"
            placeholder="Write your message..."
            required
            class="w-full border rounded-xl px-4 py-3 mb-4"></textarea>

            <button
            type="submit"
            class="bg-cyan-500 text-white px-6 py-3 rounded-xl">

                Send Message

            </button>

        </form>

    </div>

</div>


<!-- RIGHT -->

<div>

    <div class="bg-white rounded-3xl p-4 shadow">

        <h2 class="text-2xl font-bold mb-4">
            Location
        </h2>

        <iframe
        src="https://maps.google.com/maps?q=University%20of%20Computer%20Studies%20Hinthada&t=&z=15&ie=UTF8&iwloc=&output=embed"
        class="w-full h-[500px] rounded-2xl border"
        loading="lazy">
        </iframe>

    </div>

</div>

</div>

</div>

<?php include "../include/footer.php"; ?>

</body>
</html>


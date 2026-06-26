
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'admin'
) {
    header("Location: ../alumni/login.php");
    exit;
}

require_once "../config/db.php";

/* Delete Alumni */

if (isset($_GET['delete'])) {

    $delete_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM users
        WHERE id = ?
        AND role = 'user'
    ");

    $stmt->bind_param("i", $delete_id);
    $stmt->execute();

    header("Location: users.php");
    exit;
}

/* Search */

$search = trim($_GET['search'] ?? '');

/* Alumni Query */

if ($search != '') {

    $stmt = $conn->prepare("
        SELECT
            id,
            approved_id,
            name,
            email,
            role
        FROM users
        WHERE role = 'user'
        AND (
            name LIKE ?
            OR email LIKE ?
            OR approved_id LIKE ?
        )
        ORDER BY id DESC
    ");

    $keyword = "%{$search}%";

    $stmt->bind_param(
        "sss",
        $keyword,
        $keyword,
        $keyword
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            id,
            approved_id,
            name,
            email,
            role
        FROM users
        WHERE role = 'user'
        ORDER BY id DESC
    ");
}

$stmt->execute();

$users = $stmt->get_result();

/* Total Alumni */

$totalAlumni = $conn->query("
    SELECT COUNT(*) total
    FROM users
    WHERE role='user'
")->fetch_assoc()['total'];
?>


<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Manage Alumni</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">

<div class="flex min-h-screen">
<?php include "../include/admin_header.php"; ?>

    <div class="flex-1 p-6">

        <!-- Header -->

        <div class="flex items-center justify-between mb-6">

            <h1 class="text-3xl font-bold text-teal-700">
                Alumni Management
            </h1>

            <!-- <a href="dashboard.php"
               class="bg-cyan-500 text-white px-5 py-3 rounded-xl">

                Back Dashboard

            </a> -->

        </div>

        <!-- Search -->

        <div class="bg-white p-5 rounded-3xl shadow mb-6">

            <form method="GET">

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search Alumni..."
                    class="w-full border rounded-xl px-4 py-3">

            </form>

        </div>

        <!-- Table -->

        <div class="bg-white rounded-3xl shadow overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-cyan-50">

                        <tr>

                            <th class="p-4 text-left">
                                ID
                            </th>

                            <th class="p-4 text-left">
                                Name
                            </th>

                            <th class="p-4 text-left">
                                Email
                            </th>

                            <th class="p-4 text-left">
                                Approved ID
                            </th>

                            <th class="p-4 text-left">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while($user = $users->fetch_assoc()): ?>

                        <tr class="border-t">

                            <td class="p-4">
                                <?= $user['id'] ?>
                            </td>

                            <td class="p-4">
                                <?= htmlspecialchars($user['name']) ?>
                            </td>

                            <td class="p-4">
                                <?= htmlspecialchars($user['email']) ?>
                            </td>

                            <td class="p-4">
                                <?= htmlspecialchars($user['approved_id']) ?>
                            </td>

                            <td class="p-4">

                                <div class="flex gap-2">

                                    <a
                                        href="view_user.php?id=<?= $user['id'] ?>"
                                        class="bg-blue-500 text-white px-3 py-2 rounded-lg">

                                        View

                                    </a>

                                    <a
                                        href="?delete=<?= $user['id'] ?>"
                                        onclick="return confirm('Delete this alumni?')"
                                        class="bg-red-500 text-white px-3 py-2 rounded-lg">

                                        Delete

                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>


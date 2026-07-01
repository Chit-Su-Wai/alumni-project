
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

/* Pagination */

$limit = 8;

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$offset = ($page - 1) * $limit;

/* Search */

$search = trim($_GET['search'] ?? '');

/* Total Alumni */

if ($search != '') {

    $countStmt = $conn->prepare("
        SELECT COUNT(*) total
        FROM users
        WHERE role = 'user'
        AND (
            name LIKE ?
            OR email LIKE ?
            OR approved_id LIKE ?
        )
    ");

    $keyword = "%{$search}%";

    $countStmt->bind_param(
        "sss",
        $keyword,
        $keyword,
        $keyword
    );

    $countStmt->execute();

    $totalAlumni = $countStmt->get_result()->fetch_assoc()['total'];

} else {

    $totalAlumni = $conn->query("
        SELECT COUNT(*) total
        FROM users
        WHERE role='user'
    ")->fetch_assoc()['total'];
}

$totalPages = ceil($totalAlumni / $limit);

/* Alumni Query */

if ($search != '') {

    $stmt = $conn->prepare("
        SELECT
            id,
            approved_id,
            name,
            email,
            profile_image,
            role
        FROM users
        WHERE role = 'user'
        AND (
            name LIKE ?
            OR email LIKE ?
            OR approved_id LIKE ?
        )
        ORDER BY id DESC
        LIMIT ?, ?
    ");

    $keyword = "%{$search}%";

    $stmt->bind_param(
        "sssii",
        $keyword,
        $keyword,
        $keyword,
        $offset,
        $limit
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            id,
            approved_id,
            name,
            email,
            profile_image,
            role
        FROM users
        WHERE role = 'user'
        ORDER BY id DESC
        LIMIT ?, ?
    ");

    $stmt->bind_param(
        "ii",
        $offset,
        $limit
    );
}

$stmt->execute();

$users = $stmt->get_result();
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

        <div class="bg-whit p-3 rounded-2xl mb-4">

            <form method="GET">

                <div class="max-w-sm">
                    <input
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search Alumni..."
                        class="w-full border rounded-lg px-3 py-1.5 text-sm">
                </div>

            </form>

        </div>

        <!-- Table -->

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full table-fixed text-sm">

                    <thead class="bg-cyan-50">

                        <tr>

                            <th class="w-16 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                ID
                            </th>

                            <th class="w-1/3 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Name
                            </th>

                            <th class="w-1/3 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Email
                            </th>

                            <th class="w-1/4 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Approved ID
                            </th>

                            <th class="w-36 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while($user = $users->fetch_assoc()): ?>

                        <tr class="border-t">

                            <td class="px-3 py-2.5 align-middle">
                                <?= $user['id'] ?>
                            </td>

                            <td class="px-3 py-2.5 align-middle">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../images/default-avatar.svg' ?>" class="h-8 w-8 rounded-full object-cover border border-cyan-100 shrink-0">
                                    <span class="truncate font-medium"><?= htmlspecialchars($user['name']) ?></span>
                                </div>
                            </td>

                            <td class="px-3 py-2.5 align-middle truncate">
                                <?= htmlspecialchars($user['email']) ?>
                            </td>

                            <td class="px-3 py-2.5 align-middle truncate">
                                <?= htmlspecialchars($user['approved_id']) ?>
                            </td>

                            <td class="px-3 py-2.5 align-middle">

                                <div class="flex gap-2 flex-wrap">

                                    <a
                                        href="view_user.php?id=<?= $user['id'] ?>"
                                        class="bg-blue-500 text-white px-2.5 py-1.5 text-xs rounded-lg">

                                        View

                                    </a>

                                    <a
                                        href="?delete=<?= $user['id'] ?>"
                                        onclick="return confirm('Delete this alumni?')"
                                        class="bg-red-500 text-white px-2.5 py-1.5 text-xs rounded-lg">

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

        <!-- Pagination -->

        <?php if ($totalPages > 1): ?>

        <div class="flex justify-center gap-2 mt-8">

            <?php for($i = 1; $i <= $totalPages; $i++): ?>

            <a href="?<?= $search ? 'search='.urlencode($search).'&' : '' ?>page=<?= $i ?>"
               class="px-4 py-2 rounded-xl
               <?= $page == $i
               ? 'bg-cyan-500 text-white'
               : 'bg-white shadow' ?>">

                <?= $i ?>

            </a>

            <?php endfor; ?>

        </div>

        <?php endif; ?>

    </div>

</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>

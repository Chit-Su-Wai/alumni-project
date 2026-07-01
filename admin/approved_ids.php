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

/* Add Student */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $approved_id = trim($_POST['approved_id']);
    $student_name = trim($_POST['student_name']);
    $roll_number = trim($_POST['roll_number']);
    $graduated_year = trim($_POST['graduated_year']);

    if (
        !empty($approved_id) &&
        !empty($student_name) &&
        !empty($roll_number) &&
        !empty($graduated_year)
    ) {

        $stmt = $conn->prepare("
        INSERT INTO approved_students
        (
            approved_id,
            student_name,
            roll_number,
            graduated_year
        )
        VALUES
        (
            ?, ?, ?, ?
        )
        ");

        $stmt->bind_param(
            "issi",
            $approved_id,
            $student_name,
            $roll_number,
            $graduated_year
        );

        $stmt->execute();
    }

    header("Location: approved_ids.php");
    exit;
}

/* Delete */

if (isset($_GET['delete'])) {

    $approved_id = (int) $_GET['delete'];

    $stmt = $conn->prepare("
    DELETE FROM approved_students
    WHERE approved_id = ?
    ");

    $stmt->bind_param(
        "i",
        $approved_id
    );

    $stmt->execute();

    header("Location: approved_ids.php");
    exit;
}

/* Pagination */

$limit = 6;

$page = max(
    1,
    (int) ($_GET['page'] ?? 1)
);

$offset = ($page - 1) * $limit;

/* Total */

$totalStudents = $conn->query("
SELECT COUNT(*) total
FROM approved_students
")->fetch_assoc()['total'];

$totalPages = ceil($totalStudents / $limit);

/* List */

$stmt = $conn->prepare("
SELECT *
FROM approved_students
ORDER BY approved_id DESC
LIMIT ?, ?
");

$stmt->bind_param(
    "ii",
    $offset,
    $limit
);

$stmt->execute();

$students = $stmt->get_result();
?>


<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Approved Students</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">
    <div class="flex min-h-screen">
        <?php include "../include/admin_header.php"; ?>
        <div class="flex-1 p-6 flex flex-col min-h-screen">

            <div class="flex items-center justify-between mb-6">

                <h1 class="text-3xl font-bold text-teal-700">
                    Approved Students
                </h1>



            </div>

            <!-- Total -->
            <div class="bg-gradient-to-r from-cyan-50 via-cyan-100 to-teal-100 rounded-xl shadow-sm p-3 mb-6 w-[300px]">

                <p class="text-sm text-slate-500">
                    Total Approved Students
                </p>

                <h2 class="text-2xl font-bold text-teal-700 mt-1">
                    <?= $totalStudents ?>
                </h2>

            </div>
            <!-- <div class="bg-white rounded-3xl p-6 shadow mb-6">

        <p class="text-slate-500">
            Total Approved Students
        </p>

        <h2 class="text-4xl font-black text-teal-700 mt-2">

           
        </h2>

    </div> -->

            <!-- Add Form -->
            <div class="bg-white rounded-xl shadow-sm p-3 mb-4 max-w-2xl">

                <form method="POST" class="grid md:grid-cols-2 gap-2">

                    <input type="number" name="approved_id" required placeholder="Approved ID"
                        class="border rounded-lg px-3 py-2 text-sm">

                    <input type="text" name="student_name" required placeholder="Student Name"
                        class="border rounded-lg px-3 py-2 text-sm">

                    <input type="text" name="roll_number" required placeholder="Roll Number"
                        class="border rounded-lg px-3 py-2 text-sm">

                    <input type="number" name="graduated_year" required placeholder="Graduated Year"
                        class="border rounded-lg px-3 py-2 text-sm">

                    <!-- <button
            type="submit"
            class="md:col-span-2 rounded-lg bg-gradient-to-r from-cyan-400 to-teal-500 px-4 py-2 text-sm font-medium text-white hover:opacity-90">

            Add Student

        </button> -->
                    <button type="submit" class="rounded-lg bg-gradient-to-r from-cyan-400 to-teal-500
           px-3 py-1.5 text-xs font-medium text-white
           w-fit hover:opacity-90">

                        Add Student

                    </button>
                </form>

            </div>
            <!-- <div class="bg-white rounded-3xl p-5 shadow mb-6">

        <form method="POST"
              class="grid md:grid-cols-2 gap-3">

            <input
                type="number"
                name="approved_id"
                required
                placeholder="Approved ID"
                class="border rounded-xl px-3 py-2.5 text-sm">

            <input
                type="text"
                name="student_name"
                required
                placeholder="Student Name"
                class="border rounded-xl px-3 py-2.5 text-sm">

            <input
                type="text"
                name="roll_number"
                required
                placeholder="Roll Number"
                class="border rounded-xl px-3 py-2.5 text-sm">

            <input
                type="number"
                name="graduated_year"
                required
                placeholder="Graduated Year"
                class="border rounded-xl px-3 py-2.5 text-sm">

            <button
                type="submit"
                class="md:col-span-2 rounded-xl
                bg-gradient-to-r
                from-cyan-400 to-teal-500
                px-4 py-2.5 text-sm font-semibold text-white">

                Add Approved Student

            </button>

        </form>

    </div> -->

            <!-- Students List -->

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 flex-1 content-start">

                <?php while ($row = $students->fetch_assoc()): ?>

                    <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm min-h-[200px] flex flex-col">

                        <div class="flex h-full flex-col justify-between gap-4">

                            <div class="space-y-1">

                                <h3 class="text-base font-bold text-slate-800">

                                    <?= htmlspecialchars($row['student_name']) ?>

                                </h3>

                                <p class="text-sm text-slate-500">

                                    Approved ID :
                                    <?= $row['approved_id'] ?>

                                </p>

                                <p class="text-sm text-slate-500">

                                    Roll No :
                                    <?= htmlspecialchars($row['roll_number']) ?>

                                </p>

                                <p class="text-sm text-slate-500">

                                    Graduated :
                                    <?= $row['graduated_year'] ?>

                                </p>

                            </div>

                            <div class="mt-auto">

                                <a href="?delete=<?= $row['approved_id'] ?>"
                                    onclick="return confirm('Delete this student?')"
                                    class="inline-block rounded-lg bg-red-500 px-3 py-1.5 text-xs font-medium text-white">

                                    Delete

                                </a>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

            <!-- Pagination -->

            <div class="mt-auto pt-7 pb-4">
                <div class="flex justify-center gap-2">

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                        <a href="?page=<?= $i ?>" class="px-4 py-2 rounded-xl
               <?= $page == $i
                   ? 'bg-cyan-500 text-white'
                   : 'bg-white shadow' ?>">

                            <?= $i ?>

                        </a>

                    <?php endfor; ?>

                </div>
            </div>

        </div>
    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>
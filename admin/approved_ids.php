
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

    $approved_id = (int)$_GET['delete'];

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

/* Total */

$totalStudents = $conn->query("
SELECT COUNT(*) total
FROM approved_students
")->fetch_assoc()['total'];

/* List */

$students = $conn->query("
SELECT *
FROM approved_students
ORDER BY approved_id DESC
");
?>


<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>Approved Students</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-50">
<div class="flex min-h-screen">
    <?php include "../include/admin_header.php"; ?>
    <div class="flex-1 p-6">

    <div class="flex items-center justify-between mb-6">

        <h1 class="text-3xl font-bold text-teal-700">
            Approved Students
        </h1>

        

    </div>

    <!-- Total -->

    <div class="bg-white rounded-3xl p-6 shadow mb-6">

        <p class="text-slate-500">
            Total Approved Students
        </p>

        <h2 class="text-4xl font-black text-teal-700 mt-2">

            <?= $totalStudents ?>

        </h2>

    </div>

    <!-- Add Form -->

    <div class="bg-white rounded-3xl p-6 shadow mb-6">

        <form method="POST"
              class="grid md:grid-cols-2 gap-4">

            <input
                type="number"
                name="approved_id"
                required
                placeholder="Approved ID"
                class="border rounded-xl px-4 py-3">

            <input
                type="text"
                name="student_name"
                required
                placeholder="Student Name"
                class="border rounded-xl px-4 py-3">

            <input
                type="text"
                name="roll_number"
                required
                placeholder="Roll Number"
                class="border rounded-xl px-4 py-3">

            <input
                type="number"
                name="graduated_year"
                required
                placeholder="Graduated Year"
                class="border rounded-xl px-4 py-3">

            <button
                type="submit"
                class="md:col-span-2 rounded-xl
                bg-gradient-to-r
                from-cyan-400 to-teal-500
                px-6 py-3 text-white font-bold">

                Add Approved Student

            </button>

        </form>

    </div>

    <!-- Students List -->

    <div class="space-y-4">

        <?php while($row = $students->fetch_assoc()): ?>

        <div class="bg-white rounded-3xl p-5 shadow">

            <div class="flex flex-col lg:flex-row
                        lg:items-center
                        lg:justify-between
                        gap-4">

                <div>

                    <h3 class="font-bold text-lg">

                        <?= htmlspecialchars($row['student_name']) ?>

                    </h3>

                    <p class="text-slate-500">

                        Approved ID :
                        <?= $row['approved_id'] ?>

                    </p>

                    <p class="text-slate-500">

                        Roll No :
                        <?= htmlspecialchars($row['roll_number']) ?>

                    </p>

                    <p class="text-slate-500">

                        Graduated :
                        <?= $row['graduated_year'] ?>

                    </p>

                </div>

                <a
                href="?delete=<?= $row['approved_id'] ?>"
                onclick="return confirm('Delete this student?')"
                class="rounded-xl bg-red-500
                px-4 py-2 text-white text-center">

                    Delete

                </a>

            </div>

        </div>

        <?php endwhile; ?>

    </div>

</div>
</div>

<?php include "../include/admin_footer.php"; ?>

</body>
</html>


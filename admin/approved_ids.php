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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $approved_id   = trim($_POST['approved_id']);
    $student_name  = trim($_POST['student_name']);
    $roll_number   = trim($_POST['roll_number']);
    $graduated_year = trim($_POST['graduated_year']);

    if (
        empty($approved_id) ||
        empty($student_name) ||
        empty($roll_number) ||
        empty($graduated_year)
    ) {

        $_SESSION['error'] = "Please fill in all fields.";

    } else {

        // Check duplicate first
        $check = $conn->prepare("
            SELECT approved_id
            FROM approved_students
            WHERE approved_id = ?
        ");

        $check->bind_param("s", $approved_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $_SESSION['error'] = "Approved ID already exists.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO approved_students
                (
                    approved_id,
                    student_name,
                    roll_number,
                    graduated_year
                )
                VALUES
                (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssi",
                $approved_id,
                $student_name,
                $roll_number,
                $graduated_year
            );

            if ($stmt->execute()) {

                admin_log_activity(
                    $conn,
                    'Approve Student',
                    'Approved student ' . $student_name . ' (' . $approved_id . ')',
                    'student',
                    $approved_id
                );

                $_SESSION['success'] = "Student added successfully.";

            } else {

                $_SESSION['error'] = "Unable to add student.";

            }

            $stmt->close();
        }

        $check->close();
    }

    header("Location: approved_ids.php");
    exit;
}

/* Delete */

if (isset($_GET['delete'])) {

    $approved_id = (string) $_GET['delete'];

    $stmt = $conn->prepare("
    DELETE FROM approved_students
    WHERE approved_id = ?
    ");

    $stmt->bind_param(
        "s",
        $approved_id
    );

    $stmt->execute();

    admin_log_activity(
        $conn,
        'Delete Approved Student',
        'Deleted approved student ID ' . $approved_id,
        'student',
        $approved_id
    );

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
        <div class="flex-1 p-4 flex flex-col min-h-screen">

            <div class="admin-page-head">

                <div class="title-wrap">
                    <div class="admin-title-icon"><i class="fa-solid fa-id-card"></i></div>
                    <div>
                        <h1 class="admin-page-title">Approved Students</h1>
                        <p class="admin-page-sub">Manage the approved student registry</p>
                    </div>
                </div>

            </div>

            <!-- Total -->
            <div class="admin-stat w-[220px] mb-4">
                <span class="stat-spark"></span>
                <div class="stat-icon"><i class="fa-solid fa-id-card"></i></div>
                <div class="stat-value"><?= $totalStudents ?></div>
                <div class="stat-label">Total Approved Students</div>
            </div>
            <!-- <div class="bg-white rounded-3xl p-6 shadow mb-6">

        <p class="text-slate-500">
            Total Approved Students
        </p>

        <h2 class="text-4xl font-black text-teal-700 mt-2">

           
        </h2>

    </div> -->
<?php if (isset($_SESSION['error'])): ?>
<div class="mb-3 max-w-sm rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600">
    <i class="fa-solid fa-circle-exclamation mr-1"></i>
    <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<div class="mb-3 max-w-sm rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs text-green-600">
    <i class="fa-solid fa-circle-check mr-1"></i>
    <?= htmlspecialchars($_SESSION['success']) ?>
</div>
<?php unset($_SESSION['success']); endif; ?>
            <!-- Add Form -->
            <div class="admin-card p-3 mb-4 max-w-lg">

                <form method="POST" class="grid md:grid-cols-2 gap-2">

                    <input type="number" name="approved_id" required placeholder="Approved ID"
                        class="input-base">

                    <input type="text" name="student_name" required placeholder="Student Name"
                        class="input-base">

                    <input type="text" name="roll_number" required placeholder="Roll Number"
                        class="input-base">

                    <input type="number" name="graduated_year" required placeholder="Graduated Year"
                        class="input-base">

                    <button type="submit" class="btn btn-primary btn-sm w-fit">

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

            <?php if ($totalStudents > 0): ?>
            <div class="admin-stagger grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3 flex-1 content-start">

                <?php while ($row = $students->fetch_assoc()): ?>

                    <div class="admin-card p-4 min-h-[140px] flex flex-col">

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
                                    onclick="event.preventDefault(); confirmDialog('Delete this student?', function(){ window.location.href='?delete=<?= $row['approved_id'] ?>'; }, {title:'Delete Student', confirmText:'Delete'})"
                                    class="btn btn-danger btn-sm">

                                    Delete

                                </a>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>
            <?php else: ?>
            <?php
            $es_icon    = 'fa-solid fa-id-card';
            $es_title   = 'No approved students';
            $es_message = 'There are no approved student records to display yet.';
            $es_action  = '';
            include '../include/empty_state.php';
            ?>
            <?php endif; ?>

            <!-- Pagination -->

            <?php if ($totalStudents > 0): ?>
            <div class="mt-4 flex flex-col items-center gap-2 pb-4">

                <!-- Info Row -->
                <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span>
                        Showing
                        <strong class="text-slate-700"><?= $offset + 1 ?>&ndash;<?= min($offset + $limit, $totalStudents) ?></strong>
                        of
                        <strong class="text-slate-700"><?= $totalStudents ?></strong>
                    </span>

                </div>

                <!-- Controls Row -->
                <div class="pagination">

                    <!-- Previous -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>">
                            <i class="fa-solid fa-chevron-left text-xs"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="disabled"><i class="fa-solid fa-chevron-left text-xs"></i> Previous</span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                        $startPage = max(1, $page - 2);
                        $endPage   = min($totalPages, $page + 2);
                    ?>
                    <?php if ($startPage > 1): ?>
                        <a href="?page=1">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?>"
                            class="<?= $page == $i ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="px-1 text-slate-400">&hellip;</span>
                        <?php endif; ?>
                        <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <!-- Next -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>">
                            Next <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled">Next <i class="fa-solid fa-chevron-right text-xs"></i></span>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include "../include/admin_footer.php"; ?>

</body>

</html>

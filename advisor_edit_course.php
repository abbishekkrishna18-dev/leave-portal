<?php
session_start();
require_once 'db.php';

/* =========================
   ACCESS CONTROL
========================= */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'advisor') {
    header("Location: index.php");
    exit;
}

$advisor_id = (int)$_SESSION['user_id'];

$error = '';

/* =========================
   GET COURSE ID
========================= */

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    $_SESSION['error'] = "Invalid course.";
    header("Location: advisor_courses.php");
    exit;
}

/* =========================
   GET ADVISOR'S CLASS
========================= */

$stmt = $conn->prepare("
    SELECT
        c.id AS class_id,
        c.department,
        c.year,
        c.section
    FROM class_advisors ca
    INNER JOIN classes c
        ON c.id = ca.class_id
    WHERE ca.advisor_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $advisor_id);
$stmt->execute();

$result = $stmt->get_result();
$class = $result->fetch_assoc();

$stmt->close();

if (!$class) {
    $_SESSION['error'] = "You are not assigned to any class.";
    header("Location: advisor_courses.php");
    exit;
}

/* =========================
   GET COURSE
========================= */

$stmt = $conn->prepare("
    SELECT
        id,
        course_code,
        course_name,
        department,
        year,
        section
    FROM courses
    WHERE id = ?
      AND department = ?
      AND year = ?
      AND section = ?
    LIMIT 1
");

$stmt->bind_param(
    "isss",
    $course_id,
    $class['department'],
    $class['year'],
    $class['section']
);

$stmt->execute();

$result = $stmt->get_result();
$course = $result->fetch_assoc();

$stmt->close();

if (!$course) {
    $_SESSION['error'] = "Course not found or you are not authorized to edit it.";
    header("Location: advisor_courses.php");
    exit;
}

/* =========================
   UPDATE COURSE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $course_code = trim($_POST['course_code'] ?? '');
    $course_name = trim($_POST['course_name'] ?? '');

    /* VALIDATION */

    if ($course_code === '' || $course_name === '') {

        $error = "Please fill in all required fields.";

    } else {

        /* =========================
           CHECK DUPLICATE COURSE
        ========================= */

        $stmt = $conn->prepare("
            SELECT id
            FROM courses
            WHERE course_code = ?
              AND department = ?
              AND year = ?
              AND section = ?
              AND id != ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "ssssi",
            $course_code,
            $class['department'],
            $class['year'],
            $class['section'],
            $course_id
        );

        $stmt->execute();

        $result = $stmt->get_result();
        $duplicate = $result->fetch_assoc();

        $stmt->close();

        if ($duplicate) {

            $error = "Another course with this course code already exists.";

        } else {

            /* =========================
               UPDATE
            ========================= */

            $stmt = $conn->prepare("
                UPDATE courses
                SET
                    course_code = ?,
                    course_name = ?
                WHERE id = ?
                  AND department = ?
                  AND year = ?
                  AND section = ?
            ");

            $stmt->bind_param(
                "ssisss",
                $course_code,
                $course_name,
                $course_id,
                $class['department'],
                $class['year'],
                $class['section']
            );

            if ($stmt->execute()) {

                $_SESSION['success'] =
                    "Course updated successfully.";

                $stmt->close();

                header("Location: advisor_courses.php");
                exit;

            } else {

                $error = "Unable to update the course.";

            }

            $stmt->close();
        }
    }

    /* Keep entered values after validation error */

    $course['course_code'] = $course_code;
    $course['course_name'] = $course_name;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Course - MKCE Leave Portal</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >

    <link rel="stylesheet" href="style.css">

    <style>

        .page-header {
            background: #fff;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .form-card {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            max-width: 750px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .class-info {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            padding: 15px;
        }

        .class-info span {
            margin-right: 20px;
        }

    </style>

</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <?php include 'topbar.php'; ?>

    <div class="container-fluid p-4">

        <!-- PAGE HEADER -->

        <div class="page-header">

            <h2 class="mb-1">

                <i class="fa-solid fa-pen-to-square me-2"></i>

                Edit Course

            </h2>

            <p class="text-muted mb-0">

                Update course information

            </p>

        </div>


        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div class="alert alert-danger">

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <div class="form-card">

            <!-- CLASS INFORMATION -->

            <div class="class-info mb-4">

                <strong class="d-block mb-2">

                    <i class="fa-solid fa-chalkboard-user me-2"></i>

                    Assigned Class

                </strong>

                <span>

                    <strong>Department:</strong>

                    <?= htmlspecialchars($class['department']) ?>

                </span>

                <span>

                    <strong>Year:</strong>

                    <?= htmlspecialchars($class['year']) ?>

                </span>

                <span>

                    <strong>Section:</strong>

                    <?= htmlspecialchars($class['section']) ?>

                </span>

            </div>


            <form method="POST">

                <!-- COURSE CODE -->

                <div class="mb-4">

                    <label class="form-label">

                        Course Code

                        <span class="text-danger">*</span>

                    </label>

                    <input
                        type="text"
                        name="course_code"
                        class="form-control"
                        maxlength="50"
                        value="<?= htmlspecialchars($course['course_code']) ?>"
                        placeholder="Example: CS301"
                        required
                    >

                </div>


                <!-- COURSE NAME -->

                <div class="mb-4">

                    <label class="form-label">

                        Course Name

                        <span class="text-danger">*</span>

                    </label>

                    <input
                        type="text"
                        name="course_name"
                        class="form-control"
                        maxlength="150"
                        value="<?= htmlspecialchars($course['course_name']) ?>"
                        placeholder="Example: Database Management Systems"
                        required
                    >

                </div>


                <!-- INFORMATION -->

                <div class="alert alert-info">

                    <i class="fa-solid fa-circle-info me-2"></i>

                    Department, year and section cannot be changed here.
                    They are automatically linked to your assigned class.

                </div>


                <!-- BUTTONS -->

                <div class="d-flex gap-2">

                    <a
                        href="advisor_courses.php"
                        class="btn btn-secondary"
                    >

                        <i class="fa-solid fa-arrow-left me-2"></i>

                        Back

                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fa-solid fa-save me-2"></i>

                        Update Course

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>
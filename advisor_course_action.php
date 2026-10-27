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


/* =========================
   DELETE COURSE
   GET REQUEST
========================= */

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'delete'
) {

    $course_id = isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;

    if ($course_id <= 0) {

        $_SESSION['error'] = "Invalid course.";

        header("Location: advisor_courses.php");
        exit;
    }


    /* =========================
       CHECK ADVISOR'S CLASS
    ========================= */

    $stmt = $conn->prepare("
        SELECT 
            c.id,
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

    $class_result = $stmt->get_result();

    $class = $class_result->fetch_assoc();

    $stmt->close();


    if (!$class) {

        $_SESSION['error'] =
            "You are not assigned to any class.";

        header("Location: advisor_courses.php");
        exit;
    }


    /* =========================
       CHECK COURSE BELONGS
       TO ADVISOR'S CLASS
    ========================= */

    $stmt = $conn->prepare("
        SELECT id, course_code, course_name
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

    $course_result = $stmt->get_result();

    $course = $course_result->fetch_assoc();

    $stmt->close();


    if (!$course) {

        $_SESSION['error'] =
            "You are not authorized to delete this course.";

        header("Location: advisor_courses.php");
        exit;
    }


    /* =========================
       DELETE COURSE
    ========================= */

    $stmt = $conn->prepare("
        DELETE FROM courses
        WHERE id = ?
    ");

    $stmt->bind_param("i", $course_id);

    if ($stmt->execute()) {

        $_SESSION['success'] =
            "Course " . $course['course_code'] .
            " deleted successfully.";

    } else {

        $_SESSION['error'] =
            "Unable to delete the course.";
    }

    $stmt->close();


    header("Location: advisor_courses.php");
    exit;
}


/* =========================
   ONLY POST FOR ADD
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: advisor_courses.php");
    exit;
}


/* =========================
   GET POST DATA
========================= */

$action = $_POST['action'] ?? '';

$course_code = trim($_POST['course_code'] ?? '');
$course_name = trim($_POST['course_name'] ?? '');
$department  = trim($_POST['department'] ?? '');
$year        = trim($_POST['year'] ?? '');
$section     = trim($_POST['section'] ?? '');


/* =========================
   VALIDATE ACTION
========================= */

if ($action !== 'add') {

    $_SESSION['error'] = "Invalid action.";

    header("Location: advisor_courses.php");
    exit;
}


/* =========================
   VALIDATE INPUT
========================= */

if (
    $course_code === '' ||
    $course_name === '' ||
    $department === '' ||
    $year === '' ||
    $section === ''
) {

    $_SESSION['error'] =
        "Please fill in all required fields.";

    header("Location: advisor_courses.php");
    exit;
}


/* =========================
   CHECK ADVISOR'S CLASS
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

    $_SESSION['error'] =
        "You are not assigned to any class.";

    header("Location: advisor_courses.php");
    exit;
}


/* =========================
   SECURITY CHECK
   DO NOT TRUST HIDDEN
   CLASS VALUES
========================= */

if (
    $department !== $class['department'] ||
    $year !== $class['year'] ||
    $section !== $class['section']
) {

    $_SESSION['error'] =
        "Invalid class information.";

    header("Location: advisor_courses.php");
    exit;
}


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
    LIMIT 1
");

$stmt->bind_param(
    "ssss",
    $course_code,
    $department,
    $year,
    $section
);

$stmt->execute();

$result = $stmt->get_result();

$existing_course = $result->fetch_assoc();

$stmt->close();


if ($existing_course) {

    $_SESSION['error'] =
        "This course code already exists for your class.";

    header("Location: advisor_courses.php");
    exit;
}


/* =========================
   ADD COURSE
========================= */

$stmt = $conn->prepare("
    INSERT INTO courses
    (
        course_code,
        course_name,
        department,
        year,
        section
    )
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssss",
    $course_code,
    $course_name,
    $department,
    $year,
    $section
);


if ($stmt->execute()) {

    $_SESSION['success'] =
        "Course added successfully.";

} else {

    $_SESSION['error'] =
        "Unable to add course.";
}

$stmt->close();


/* =========================
   REDIRECT
========================= */

header("Location: advisor_courses.php");
exit;
?>
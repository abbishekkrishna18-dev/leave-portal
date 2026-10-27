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
   ONLY POST REQUEST
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: advisor_allocate_course.php");
    exit;
}


/* =========================
   GET FORM DATA
========================= */

$course_id = isset($_POST['course_id'])
    ? (int)$_POST['course_id']
    : 0;

$faculty_id = isset($_POST['faculty_id'])
    ? (int)$_POST['faculty_id']
    : 0;


/* =========================
   BASIC VALIDATION
========================= */

if ($course_id <= 0 || $faculty_id <= 0) {

    $_SESSION['error'] =
        "Please select a valid course and faculty member.";

    header("Location: advisor_allocate_course.php");
    exit;
}


/* =========================
   GET ADVISOR'S ASSIGNED CLASS
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

    header("Location: advisor_allocate_course.php");
    exit;
}


/* =========================
   VERIFY COURSE BELONGS
   TO ADVISOR'S CLASS
========================= */

$stmt = $conn->prepare("
    SELECT
        id,
        course_code,
        course_name
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

    $_SESSION['error'] =
        "You are not authorized to allocate this course.";

    header("Location: advisor_allocate_course.php");
    exit;
}


/* =========================
   VERIFY FACULTY EXISTS
========================= */

$stmt = $conn->prepare("
    SELECT
        id,
        name
    FROM users
    WHERE id = ?
      AND role = 'faculty'
    LIMIT 1
");

$stmt->bind_param("i", $faculty_id);
$stmt->execute();

$result = $stmt->get_result();
$faculty = $result->fetch_assoc();

$stmt->close();


if (!$faculty) {

    $_SESSION['error'] =
        "Selected faculty account does not exist.";

    header("Location: advisor_allocate_course.php");
    exit;
}


/* =========================
   CHECK EXISTING ALLOCATION
========================= */

$stmt = $conn->prepare("
    SELECT
        id,
        faculty_id
    FROM course_faculty
    WHERE course_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $course_id);
$stmt->execute();

$result = $stmt->get_result();
$allocation = $result->fetch_assoc();

$stmt->close();


/* =========================
   UPDATE EXISTING ALLOCATION
========================= */

if ($allocation) {

    $allocation_id = (int)$allocation['id'];

    $stmt = $conn->prepare("
        UPDATE course_faculty
        SET
            faculty_id = ?,
            allocated_by = ?,
            allocated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $stmt->bind_param(
        "iii",
        $faculty_id,
        $advisor_id,
        $allocation_id
    );

    if ($stmt->execute()) {

        $_SESSION['success'] =
            "Faculty " . $faculty['name'] .
            " assigned to " . $course['course_code'] .
            " successfully.";

    } else {

        $_SESSION['error'] =
            "Unable to update the faculty allocation.";
    }

    $stmt->close();

}


/* =========================
   CREATE NEW ALLOCATION
========================= */

else {

    $stmt = $conn->prepare("
        INSERT INTO course_faculty
        (
            course_id,
            faculty_id,
            allocated_by
        )
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param(
        "iii",
        $course_id,
        $faculty_id,
        $advisor_id
    );

    if ($stmt->execute()) {

        $_SESSION['success'] =
            "Faculty " . $faculty['name'] .
            " assigned to " . $course['course_code'] .
            " successfully.";

    } else {

        $_SESSION['error'] =
            "Unable to allocate faculty to this course.";
    }

    $stmt->close();
}


/* =========================
   REDIRECT
========================= */

header("Location: advisor_allocate_course.php");
exit;
?>
<?php

session_start();

require_once "db.php";


/* =========================================================
   ACCESS CONTROL
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'advisor'
) {
    header("Location: index.php");
    exit;
}


$advisor_id = (int) $_SESSION['user_id'];


/* =========================================================
   ONLY POST REQUESTS
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: advisor_timetable.php");
    exit;

}


/* =========================================================
   ACTION
========================================================= */

$action = $_POST['action'] ?? '';



/* =========================================================
   ADD TIMETABLE
========================================================= */

if ($action === 'add') {


    /* =====================================================
       GET FORM VALUES
    ===================================================== */

    $course_id = isset($_POST['course_id'])
        ? (int) $_POST['course_id']
        : 0;


    $day_of_week = trim(
        $_POST['day_of_week'] ?? ''
    );


    $period_no = isset($_POST['period_no'])
        ? (int) $_POST['period_no']
        : 0;


    $start_time = trim(
        $_POST['start_time'] ?? ''
    );


    $end_time = trim(
        $_POST['end_time'] ?? ''
    );



    /* =====================================================
       VALIDATE COURSE
    ===================================================== */

    if ($course_id <= 0) {

        $_SESSION['error'] =
            "Please select a course.";

        header("Location: advisor_timetable.php");
        exit;

    }



    /* =====================================================
       VALIDATE DAY
    ===================================================== */

    $allowed_days = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    ];


    if (!in_array($day_of_week, $allowed_days, true)) {

        $_SESSION['error'] =
            "Please select a valid day.";

        header("Location: advisor_timetable.php");
        exit;

    }



    /* =====================================================
       VALIDATE PERIOD
    ===================================================== */

    if ($period_no < 1 || $period_no > 8) {

        $_SESSION['error'] =
            "Please select a valid period.";

        header("Location: advisor_timetable.php");
        exit;

    }



    /* =====================================================
       VALIDATE TIME
    ===================================================== */

    if (
        $start_time === '' ||
        $end_time === ''
    ) {

        $_SESSION['error'] =
            "Please select both start time and end time.";

        header("Location: advisor_timetable.php");
        exit;

    }


    if ($start_time >= $end_time) {

        $_SESSION['error'] =
            "End time must be later than start time.";

        header("Location: advisor_timetable.php");
        exit;

    }



    /* =====================================================
       GET ADVISOR'S ASSIGNED CLASS
    ===================================================== */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            c.id AS class_id,
            c.department,
            c.year,
            c.section

         FROM class_advisors ca

         INNER JOIN classes c
            ON c.id = ca.class_id

         WHERE ca.advisor_id = ?

         LIMIT 1"
    );


    if (!$stmt) {

        $_SESSION['error'] =
            "Database error while finding your class: "
            . mysqli_error($conn);

        header("Location: advisor_timetable.php");
        exit;

    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $advisor_id
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    $class = mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);



    if (!$class) {

        $_SESSION['error'] =
            "You are not assigned to any class.";

        header("Location: advisor_timetable.php");
        exit;

    }


    $class_id = (int) $class['class_id'];



    /* =====================================================
       VERIFY COURSE BELONGS TO THIS CLASS
    ===================================================== */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            id,
            course_code,
            course_name

         FROM courses

         WHERE id = ?

           AND department = ?

           AND year = ?

           AND section = ?

         LIMIT 1"
    );


    if (!$stmt) {

        $_SESSION['error'] =
            "Database error while checking course: "
            . mysqli_error($conn);

        header("Location: advisor_timetable.php");
        exit;

    }


    mysqli_stmt_bind_param(
        $stmt,
        "isss",
        $course_id,
        $class['department'],
        $class['year'],
        $class['section']
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    $course = mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);



    if (!$course) {

        $_SESSION['error'] =
            "The selected course does not belong to your assigned class.";

        header("Location: advisor_timetable.php");
        exit;

    }



    /* =====================================================
       GET FACULTY ALLOCATED TO COURSE
    ===================================================== */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            cf.faculty_id,
            u.name AS faculty_name

         FROM course_faculty cf

         INNER JOIN users u
            ON u.id = cf.faculty_id

         WHERE cf.course_id = ?

           AND u.role = 'faculty'

         LIMIT 1"
    );


    if (!$stmt) {

        $_SESSION['error'] =
            "Database error while finding faculty: "
            . mysqli_error($conn);

        header("Location: advisor_timetable.php");
        exit;

    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $course_id
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    $faculty = mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);



    if (!$faculty) {

        $_SESSION['error'] =
            "No faculty has been allocated to "
            . $course['course_code']
            . " - "
            . $course['course_name']
            . ". Please allocate a faculty to this course first.";

        header("Location: advisor_timetable.php");
        exit;

    }


    $faculty_id = (int) $faculty['faculty_id'];



    /* =====================================================
       CHECK CLASS CONFLICT
    ===================================================== */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id

         FROM timetable

         WHERE class_id = ?

           AND day_of_week = ?

           AND period_no = ?

         LIMIT 1"
    );


    if (!$stmt) {

        $_SESSION['error'] =
            "Database error while checking class timetable: "
            . mysqli_error($conn);

        header("Location: advisor_timetable.php");
        exit;

    }


    mysqli_stmt_bind_param(
        $stmt,
        "isi",
        $class_id,
        $day_of_week,
        $period_no
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    $class_conflict = mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);



    if ($class_conflict) {

        $_SESSION['error'] =
            "Your class already has a subject on "
            . $day_of_week
            . " - Period "
            . $period_no
            . ".";

        header("Location: advisor_timetable.php");
        exit;

    }



    /* =====================================================
       CHECK FACULTY CONFLICT
    ===================================================== */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            t.id,
            t.class_id,
            t.course_id

         FROM timetable t

         WHERE t.faculty_id = ?

           AND t.day_of_week = ?

           AND t.period_no = ?

         LIMIT 1"
    );


    if (!$stmt) {

        $_SESSION['error'] =
            "Database error while checking faculty timetable: "
            . mysqli_error($conn);

        header("Location: advisor_timetable.php");
        exit;

    }


    mysqli_stmt_bind_param(
        $stmt,
        "isi",
        $faculty_id,
        $day_of_week,
        $period_no
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    $faculty_conflict = mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);



    if ($faculty_conflict) {

        $_SESSION['error'] =
            "Faculty "
            . $faculty['faculty_name']
            . " already has a class on "
            . $day_of_week
            . " - Period "
            . $period_no
            . ".";

        header("Location: advisor_timetable.php");
        exit;

    }



    /* =====================================================
       INSERT TIMETABLE
    ===================================================== */

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO timetable
        (
            faculty_id,
            class_id,
            course_id,
            day_of_week,
            period_no,
            start_time,
            end_time
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )"
    );


    if (!$stmt) {

        $_SESSION['error'] =
            "Unable to prepare timetable insertion: "
            . mysqli_error($conn);

        header("Location: advisor_timetable.php");
        exit;

    }



    mysqli_stmt_bind_param(
        $stmt,
        "iiisiss",
        $faculty_id,
        $class_id,
        $course_id,
        $day_of_week,
        $period_no,
        $start_time,
        $end_time
    );



    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success'] =
            "Timetable added successfully: "
            . $course['course_code']
            . " - "
            . $day_of_week
            . " - Period "
            . $period_no
            . ".";

    } else {

        $_SESSION['error'] =
            "Failed to add timetable: "
            . mysqli_stmt_error($stmt);

    }


    mysqli_stmt_close($stmt);


    header("Location: advisor_timetable.php");

    exit;

}



/* =========================================================
   DELETE TIMETABLE
========================================================= */

if ($action === 'delete') {


    $timetable_id = isset($_POST['timetable_id'])
        ? (int) $_POST['timetable_id']
        : 0;



    if ($timetable_id <= 0) {

        $_SESSION['error'] =
            "Invalid timetable entry.";

        header("Location: advisor_timetable.php");
        exit;

    }



    /* =====================================================
       GET ADVISOR CLASS
    ===================================================== */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            c.id AS class_id

         FROM class_advisors ca

         INNER JOIN classes c
            ON c.id = ca.class_id

         WHERE ca.advisor_id = ?

         LIMIT 1"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $advisor_id
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    $class = mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);



    if (!$class) {

        $_SESSION['error'] =
            "You are not assigned to any class.";

        header("Location: advisor_timetable.php");
        exit;

    }



    /* =====================================================
       DELETE ONLY FROM ADVISOR'S CLASS
    ===================================================== */

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM timetable

         WHERE id = ?

           AND class_id = ?"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $timetable_id,
        $class['class_id']
    );



    if (mysqli_stmt_execute($stmt)) {

        if (mysqli_stmt_affected_rows($stmt) > 0) {

            $_SESSION['success'] =
                "Timetable entry deleted successfully.";

        } else {

            $_SESSION['error'] =
                "Timetable entry not found "
                . "or you are not authorized to delete it.";

        }

    } else {

        $_SESSION['error'] =
            "Failed to delete timetable: "
            . mysqli_stmt_error($stmt);

    }


    mysqli_stmt_close($stmt);


    header("Location: advisor_timetable.php");

    exit;

}



/* =========================================================
   INVALID ACTION
========================================================= */

$_SESSION['error'] =
    "Invalid timetable action.";

header("Location: advisor_timetable.php");

exit;

?>
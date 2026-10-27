<?php
session_start();
require_once 'db.php';


// --------------------------------------------------
// FACULTY ACCESS CHECK
// --------------------------------------------------

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: index.php");
    exit();
}

$faculty_id = (int)$_SESSION['user_id'];


// --------------------------------------------------
// POST REQUEST CHECK
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: faculty_timetable.php");
    exit();
}


// --------------------------------------------------
// GET FORM DATA
// --------------------------------------------------

$timetable_id = isset($_POST['timetable_id'])
    ? (int)$_POST['timetable_id']
    : 0;

$attendance_date = $_POST['attendance_date'] ?? '';

$status_data = $_POST['status'] ?? [];


// --------------------------------------------------
// BASIC VALIDATION
// --------------------------------------------------

if ($timetable_id <= 0 || empty($attendance_date)) {

    $_SESSION['error'] = "Invalid attendance data.";

    header("Location: faculty_timetable.php");
    exit();
}


// Validate date format

$date_object = DateTime::createFromFormat(
    'Y-m-d',
    $attendance_date
);

if (
    !$date_object ||
    $date_object->format('Y-m-d') !== $attendance_date
) {

    $_SESSION['error'] = "Invalid attendance date.";

    header("Location: faculty_timetable.php");
    exit();
}


// Do not allow future attendance

if ($attendance_date > date('Y-m-d')) {

    $_SESSION['error'] = "Attendance cannot be marked for a future date.";

    header(
        "Location: faculty_attendance.php?timetable_id="
        . $timetable_id
    );

    exit();
}


// --------------------------------------------------
// VERIFY TIMETABLE BELONGS TO FACULTY
// --------------------------------------------------

$stmt = mysqli_prepare($conn, "
    SELECT
        t.id,
        t.class_id,
        t.day_of_week,
        t.faculty_id,
        t.course_id
    FROM timetable t
    WHERE t.id = ?
      AND t.faculty_id = ?
    LIMIT 1
");

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $timetable_id,
    $faculty_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$timetable = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$timetable) {

    $_SESSION['error'] =
        "You are not authorized to mark attendance for this timetable.";

    header("Location: faculty_timetable.php");
    exit();
}


// --------------------------------------------------
// VERIFY DAY OF WEEK
// --------------------------------------------------

$actual_day = date(
    'l',
    strtotime($attendance_date)
);

if (
    strcasecmp(
        $actual_day,
        $timetable['day_of_week']
    ) !== 0
) {

    $_SESSION['error'] =
        "Attendance date does not match the timetable day.";

    header(
        "Location: faculty_attendance.php?timetable_id="
        . $timetable_id
        . "&date="
        . urlencode($attendance_date)
    );

    exit();
}


// --------------------------------------------------
// GET STUDENTS ACTUALLY BELONGING TO THIS CLASS
// --------------------------------------------------

$valid_students = [];

$stmt = mysqli_prepare($conn, "
    SELECT
        sc.student_id
    FROM student_classes sc
    INNER JOIN users u
        ON sc.student_id = u.id
    WHERE sc.class_id = ?
      AND u.role = 'student'
");

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $timetable['class_id']
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {

    $valid_students[
        (int)$row['student_id']
    ] = true;
}

mysqli_stmt_close($stmt);


// --------------------------------------------------
// IF NO STUDENTS
// --------------------------------------------------

if (empty($valid_students)) {

    $_SESSION['error'] =
        "No students are assigned to this class.";

    header(
        "Location: faculty_attendance.php?timetable_id="
        . $timetable_id
        . "&date="
        . urlencode($attendance_date)
    );

    exit();
}


// --------------------------------------------------
// GET APPROVED LEAVE / OD
// --------------------------------------------------

$approved_status = [];

$stmt = mysqli_prepare($conn, "
    SELECT
        student_id,
        leave_type
    FROM leaves
    WHERE advisor_status = 'Approved'
      AND hod_status = 'Approved'
      AND (od_status IS NULL OR od_status <> 'Blocked')
      AND date_from <= ?
      AND date_to >= ?
");

mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $attendance_date,
    $attendance_date
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {

    $student_id = (int)$row['student_id'];

    /*
     * Ignore leave records belonging to students
     * who are not part of this timetable's class.
     */

    if (!isset($valid_students[$student_id])) {
        continue;
    }

    $leave_type = strtolower(
        trim($row['leave_type'])
    );


    $is_od = in_array(
        $leave_type,
        [
            'od',
            'on duty',
            'on-duty',
            'on_duty'
        ],
        true
    );


    /*
     * If multiple approved records exist,
     * OD gets priority.
     */

    if ($is_od) {

        $approved_status[$student_id] = 'OD';

    } elseif (
        !isset($approved_status[$student_id])
    ) {

        $approved_status[$student_id] = 'Leave';
    }
}

mysqli_stmt_close($stmt);


// --------------------------------------------------
// START DATABASE TRANSACTION
// --------------------------------------------------

mysqli_begin_transaction($conn);

try {

    foreach ($valid_students as $student_id => $unused) {

        /*
         * Get submitted status.
         */

        $submitted_status =
            $status_data[$student_id]
            ?? '';


        $submitted_status =
            trim($submitted_status);


        // --------------------------------------------------
        // APPROVED LEAVE / OD
        // --------------------------------------------------

        if (isset($approved_status[$student_id])) {

            /*
             * IMPORTANT:
             *
             * Ignore whatever the browser submitted.
             *
             * The database decides the correct status.
             */

            $final_status =
                $approved_status[$student_id];

        } else {

            // --------------------------------------------------
            // NORMAL STUDENT
            // --------------------------------------------------

            if (
                $submitted_status !== 'Present' &&
                $submitted_status !== 'Absent'
            ) {

                throw new Exception(
                    "Please select Present or Absent for all students."
                );
            }

            $final_status =
                $submitted_status;
        }


        // --------------------------------------------------
        // CHECK IF ATTENDANCE ALREADY EXISTS
        // --------------------------------------------------

        $check_stmt = mysqli_prepare($conn, "
            SELECT id
            FROM attendance
            WHERE student_id = ?
              AND timetable_id = ?
              AND attendance_date = ?
            LIMIT 1
        ");

        mysqli_stmt_bind_param(
            $check_stmt,
            "iis",
            $student_id,
            $timetable_id,
            $attendance_date
        );

        mysqli_stmt_execute($check_stmt);

        $check_result =
            mysqli_stmt_get_result($check_stmt);

        $existing =
            mysqli_fetch_assoc($check_result);

        mysqli_stmt_close($check_stmt);


        // --------------------------------------------------
        // UPDATE EXISTING ATTENDANCE
        // --------------------------------------------------

        if ($existing) {

            $attendance_id =
                (int)$existing['id'];


            $update_stmt = mysqli_prepare($conn, "
                UPDATE attendance
                SET
                    status = ?,
                    marked_by = ?,
                    marked_at = NOW()
                WHERE id = ?
            ");

            mysqli_stmt_bind_param(
                $update_stmt,
                "sii",
                $final_status,
                $faculty_id,
                $attendance_id
            );

            if (!mysqli_stmt_execute($update_stmt)) {

                throw new Exception(
                    "Failed to update attendance."
                );
            }

            mysqli_stmt_close($update_stmt);


        } else {

            // --------------------------------------------------
            // INSERT NEW ATTENDANCE
            // --------------------------------------------------

            $insert_stmt = mysqli_prepare($conn, "
                INSERT INTO attendance
                (
                    student_id,
                    timetable_id,
                    attendance_date,
                    status,
                    marked_by,
                    marked_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()
                )
            ");

            mysqli_stmt_bind_param(
                $insert_stmt,
                "iissi",
                $student_id,
                $timetable_id,
                $attendance_date,
                $final_status,
                $faculty_id
            );

            if (!mysqli_stmt_execute($insert_stmt)) {

                throw new Exception(
                    "Failed to save attendance."
                );
            }

            mysqli_stmt_close($insert_stmt);
        }
    }


    // --------------------------------------------------
    // COMMIT
    // --------------------------------------------------

    mysqli_commit($conn);


    $_SESSION['success'] =
        "Attendance saved successfully.";


} catch (Exception $e) {

    // --------------------------------------------------
    // ROLLBACK
    // --------------------------------------------------

    mysqli_rollback($conn);

    $_SESSION['error'] =
        $e->getMessage();
}


// --------------------------------------------------
// REDIRECT BACK
// --------------------------------------------------

header(
    "Location: faculty_attendance.php?timetable_id="
    . $timetable_id
    . "&date="
    . urlencode($attendance_date)
);

exit();
?>
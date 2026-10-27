<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: index.php");
    exit();
}

$faculty_id = (int)$_SESSION['user_id'];

if (!isset($_GET['timetable_id']) || !is_numeric($_GET['timetable_id'])) {
    $_SESSION['error'] = "Invalid timetable.";
    header("Location: faculty_timetable.php");
    exit();
}

$timetable_id = (int)$_GET['timetable_id'];


// --------------------------------------------------
// GET TIMETABLE DETAILS
// --------------------------------------------------

$stmt = mysqli_prepare($conn, "
    SELECT 
        t.id,
        t.class_id,
        t.course_id,
        t.day_of_week,
        t.period_no,
        t.start_time,
        t.end_time,
        c.course_name,
        cl.department,
        cl.year,
        cl.section
    FROM timetable t
    INNER JOIN courses c 
        ON t.course_id = c.id
    INNER JOIN classes cl 
        ON t.class_id = cl.id
    WHERE t.id = ?
      AND t.faculty_id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "ii", $timetable_id, $faculty_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$timetable = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$timetable) {
    $_SESSION['error'] = "Timetable entry not found.";
    header("Location: faculty_timetable.php");
    exit();
}


// --------------------------------------------------
// ATTENDANCE DATE
// --------------------------------------------------

$attendance_date = $_GET['date'] ?? date('Y-m-d');

$date_object = DateTime::createFromFormat('Y-m-d', $attendance_date);

if (!$date_object || $date_object->format('Y-m-d') !== $attendance_date) {
    $attendance_date = date('Y-m-d');
}


// Do not allow future attendance
if ($attendance_date > date('Y-m-d')) {
    $attendance_date = date('Y-m-d');
}


// --------------------------------------------------
// CHECK DAY OF WEEK
// --------------------------------------------------

$actual_day = date('l', strtotime($attendance_date));

if (strcasecmp($actual_day, $timetable['day_of_week']) !== 0) {
    $wrong_day = true;
} else {
    $wrong_day = false;
}


// --------------------------------------------------
// GET STUDENTS OF THIS CLASS
// --------------------------------------------------

$students = [];

$stmt = mysqli_prepare($conn, "
    SELECT 
        u.id,
        u.name,
        u.email
    FROM student_classes sc
    INNER JOIN users u
        ON sc.student_id = u.id
    WHERE sc.class_id = ?
      AND u.role = 'student'
    ORDER BY u.name ASC
");

mysqli_stmt_bind_param($stmt, "i", $timetable['class_id']);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

mysqli_stmt_close($stmt);


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
     * If a student has multiple approved records
     * on the same date, OD takes priority.
     */

    if ($is_od) {
        $approved_status[$student_id] = 'OD';
    } elseif (!isset($approved_status[$student_id])) {
        $approved_status[$student_id] = 'Leave';
    }
}

mysqli_stmt_close($stmt);


// --------------------------------------------------
// GET EXISTING ATTENDANCE
// --------------------------------------------------

$existing_attendance = [];

$stmt = mysqli_prepare($conn, "
    SELECT 
        student_id,
        status
    FROM attendance
    WHERE timetable_id = ?
      AND attendance_date = ?
");

mysqli_stmt_bind_param(
    $stmt,
    "is",
    $timetable_id,
    $attendance_date
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {

    $existing_attendance[
        (int)$row['student_id']
    ] = $row['status'];
}

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Mark Attendance</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <?php include 'topbar.php'; ?>

    <div class="page-content">

        <div class="container-fluid">

            <!-- HEADER -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h3 class="fw-bold mb-1">
                        <i class="fa-solid fa-user-check"></i>
                        Mark Attendance
                    </h3>

                    <p class="text-muted mb-0">
                        <?= htmlspecialchars($timetable['course_name']) ?>
                    </p>

                </div>

                <a
                    href="faculty_timetable.php"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Timetable
                </a>

            </div>


            <!-- CLASS INFORMATION -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-3">

                            <strong>Class</strong>

                            <div>
                                <?= htmlspecialchars($timetable['department']) ?>
                                -
                                <?= htmlspecialchars($timetable['year']) ?>
                                -
                                <?= htmlspecialchars($timetable['section']) ?>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <strong>Day</strong>

                            <div>
                                <?= htmlspecialchars($timetable['day_of_week']) ?>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <strong>Period</strong>

                            <div>
                                Period <?= htmlspecialchars($timetable['period_no']) ?>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <strong>Time</strong>

                            <div>
                                <?= date(
                                    'h:i A',
                                    strtotime($timetable['start_time'])
                                ) ?>

                                -

                                <?= date(
                                    'h:i A',
                                    strtotime($timetable['end_time'])
                                ) ?>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- DATE -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <form method="GET">

                        <input
                            type="hidden"
                            name="timetable_id"
                            value="<?= $timetable_id ?>"
                        >

                        <div class="row align-items-end">

                            <div class="col-md-4">

                                <label class="form-label fw-bold">
                                    Attendance Date
                                </label>

                                <input
                                    type="date"
                                    name="date"
                                    class="form-control"
                                    value="<?= htmlspecialchars($attendance_date) ?>"
                                    max="<?= date('Y-m-d') ?>"
                                    required
                                >

                            </div>

                            <div class="col-md-2">

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    <i class="fa-solid fa-calendar-check"></i>
                                    Load
                                </button>

                            </div>

                        </div>

                    </form>

                </div>

            </div>


            <?php if ($wrong_day): ?>

                <div class="alert alert-warning">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                    This timetable is scheduled on
                    <strong>
                        <?= htmlspecialchars($timetable['day_of_week']) ?>
                    </strong>.

                    The selected date is
                    <strong>
                        <?= htmlspecialchars($actual_day) ?>
                    </strong>.

                    Attendance cannot be marked for this date.

                </div>

            <?php endif; ?>


            <!-- ATTENDANCE TABLE -->

            <form
                method="POST"
                action="faculty_attendance_action.php"
            >

                <input
                    type="hidden"
                    name="timetable_id"
                    value="<?= $timetable_id ?>"
                >

                <input
                    type="hidden"
                    name="attendance_date"
                    value="<?= htmlspecialchars($attendance_date) ?>"
                >


                <div class="card shadow-sm">

                    <div class="card-header">

                        <div class="d-flex justify-content-between align-items-center">

                            <strong>
                                <i class="fa-solid fa-users"></i>
                                Students
                            </strong>

                            <span class="badge bg-primary">
                                <?= count($students) ?> Students
                            </span>

                        </div>

                    </div>


                    <div class="card-body p-0">

                        <div class="table-responsive">

                            <table class="table table-hover mb-0">

                                <thead class="table-light">

                                    <tr>

                                        <th style="width:80px;">
                                            #
                                        </th>

                                        <th>
                                            Student Name
                                        </th>

                                        <th>
                                            Email
                                        </th>

                                        <th style="width:220px;">
                                            Attendance
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                <?php if (empty($students)): ?>

                                    <tr>

                                        <td
                                            colspan="5"
                                            class="text-center py-5"
                                        >

                                            <i
                                                class="fa-solid fa-user-slash fa-2x text-muted mb-2"
                                            ></i>

                                            <div class="text-muted">
                                                No students assigned to this class.
                                            </div>

                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($students as $index => $student): ?>

                                        <?php

                                        $student_id =
                                            (int)$student['id'];

                                        $locked_status =
                                            $approved_status[$student_id]
                                            ?? null;

                                        $current_status =
                                            $existing_attendance[$student_id]
                                            ?? '';

                                        ?>

                                        <tr>

                                            <td>
                                                <?= $index + 1 ?>
                                            </td>

                                            <td>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $student['name']
                                                    ) ?>
                                                </strong>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars(
                                                    $student['email']
                                                ) ?>

                                            </td>


                                            <td>

                                                <?php if ($locked_status): ?>

                                                    <!-- APPROVED LEAVE / OD -->

                                                    <select
                                                        class="form-select"
                                                        disabled
                                                    >

                                                        <option selected>

                                                            <?= $locked_status ?>

                                                        </option>

                                                    </select>


                                                    <!--
                                                        Hidden value is still
                                                        submitted to the server.
                                                    -->

                                                    <input
                                                        type="hidden"
                                                        name="status[<?= $student_id ?>]"
                                                        value="<?= $locked_status ?>"
                                                    >

                                                <?php else: ?>

                                                    <!-- NORMAL ATTENDANCE -->

                                                    <select
                                                        name="status[<?= $student_id ?>]"
                                                        class="form-select"
                                                        <?= $wrong_day ? 'disabled' : '' ?>
                                                    >

                                                        <option value="">
                                                            Select
                                                        </option>

                                                        <option
                                                            value="Present"
                                                            <?= $current_status === 'Present'
                                                                ? 'selected'
                                                                : '' ?>
                                                        >
                                                            Present
                                                        </option>

                                                        <option
                                                            value="Absent"
                                                            <?= $current_status === 'Absent'
                                                                ? 'selected'
                                                                : '' ?>
                                                        >
                                                            Absent
                                                        </option>

                                                    </select>

                                                <?php endif; ?>

                                            </td>


                                            <td>

                                                <?php if ($locked_status === 'OD'): ?>

                                                    <span class="badge bg-info">
                                                        <i class="fa-solid fa-briefcase"></i>
                                                        Approved OD
                                                    </span>

                                                <?php elseif ($locked_status === 'Leave'): ?>

                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fa-solid fa-calendar-xmark"></i>
                                                        Approved Leave
                                                    </span>

                                                <?php elseif ($current_status === 'Present'): ?>

                                                    <span class="badge bg-success">
                                                        Present
                                                    </span>

                                                <?php elseif ($current_status === 'Absent'): ?>

                                                    <span class="badge bg-danger">
                                                        Absent
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge bg-secondary">
                                                        Not Marked
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    <?php if (!empty($students) && !$wrong_day): ?>

                        <div class="card-footer text-end">

                            <button
                                type="submit"
                                class="btn btn-success"
                            >

                                <i class="fa-solid fa-save"></i>

                                Save Attendance

                            </button>

                        </div>

                    <?php endif; ?>

                </div>

            </form>


            <!-- INFORMATION -->

            <div class="alert alert-info mt-4">

                <i class="fa-solid fa-circle-info"></i>

                <strong>Attendance Rule:</strong>

                Students with leave or OD approved by
                <strong>both Advisor and HOD</strong>
                are automatically marked as
                <strong>Leave</strong> or
                <strong>OD</strong> and cannot be changed by the faculty.

                Other students can be marked as
                <strong>Present</strong> or
                <strong>Absent</strong>.

            </div>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>
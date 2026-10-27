<?php

session_start();
require_once 'db.php';

/* =========================================================
   ACCESS CHECK
========================================================= */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'advisor') {
    header("Location: index.php");
    exit();
}

$advisor_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET ADVISOR'S ASSIGNED CLASS
========================================================= */

$class_stmt = mysqli_prepare(
    $conn,
    "SELECT 
        c.id,
        c.department,
        c.year,
        c.section
     FROM classes c
     INNER JOIN class_advisors ca
        ON ca.class_id = c.id
     WHERE ca.advisor_id = ?"
);

mysqli_stmt_bind_param(
    $class_stmt,
    "i",
    $advisor_id
);

mysqli_stmt_execute($class_stmt);

$class_result = mysqli_stmt_get_result($class_stmt);

$class = mysqli_fetch_assoc($class_result);

mysqli_stmt_close($class_stmt);


/* =========================================================
   IF NO CLASS ASSIGNED
========================================================= */

if (!$class) {
    die("No class has been assigned to you.");
}

$class_id = (int) $class['id'];


/* =========================================================
   GET COURSES FOR THIS CLASS
========================================================= */

$course_stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        course_code,
        course_name
     FROM courses
     WHERE department = ?
       AND year = ?
       AND section = ?
     ORDER BY course_code"
);

mysqli_stmt_bind_param(
    $course_stmt,
    "sis",
    $class['department'],
    $class['year'],
    $class['section']
);

mysqli_stmt_execute($course_stmt);

$course_result = mysqli_stmt_get_result($course_stmt);

$courses = [];

while ($row = mysqli_fetch_assoc($course_result)) {
    $courses[] = $row;
}

mysqli_stmt_close($course_stmt);


/* =========================================================
   GET TIMETABLE FOR THIS CLASS
========================================================= */

$timetable_stmt = mysqli_prepare(
    $conn,
    "SELECT
        t.id,
        t.day_of_week,
        t.period_no,
        t.start_time,
        t.end_time,

        c.course_code,
        c.course_name,

        u.name AS faculty_name

     FROM timetable t

     INNER JOIN courses c
        ON c.id = t.course_id

     LEFT JOIN users u
        ON u.id = t.faculty_id

     WHERE t.class_id = ?

     ORDER BY
        FIELD(
            t.day_of_week,
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday'
        ),
        t.period_no"
);

mysqli_stmt_bind_param(
    $timetable_stmt,
    "i",
    $class_id
);

mysqli_stmt_execute($timetable_stmt);

$timetable_result = mysqli_stmt_get_result($timetable_stmt);

$timetable = [];

while ($row = mysqli_fetch_assoc($timetable_result)) {
    $timetable[] = $row;
}

mysqli_stmt_close($timetable_stmt);


/* =========================================================
   DAYS AND PERIODS
========================================================= */

$days = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday'
];

$periods = range(1, 8);


/* =========================================================
   TOTAL ENTRIES
========================================================= */

$total_entries = count($timetable);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Advisor Timetable</title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        body {
            background: #f5f7fb;
        }


        .page-container {
            padding: 25px;
        }


        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }


        .page-subtitle {
            color: #6c757d;
            margin-bottom: 25px;
        }


        .class-card {
            background: white;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }


        .class-title {
            font-size: 20px;
            font-weight: 700;
        }


        .entry-badge {
            background: #e7f1ff;
            color: #0d6efd;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }


        /* =====================================================
           TIMETABLE
        ===================================================== */

        .timetable-wrapper {
            background: white;
            border-radius: 14px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            overflow-x: auto;
        }


        .timetable {
            min-width: 1100px;
            border-collapse: separate;
            border-spacing: 6px;
            width: 100%;
        }


        .timetable th {
            background: #212529;
            color: white;
            text-align: center;
            padding: 14px 10px;
            border-radius: 8px;
            font-size: 14px;
        }


        .period-header {
            width: 100px;
        }


        .period-cell {
            background: #f1f3f5;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
            border-radius: 8px;
            color: #343a40;
        }


        .timetable td {
            height: 105px;
            vertical-align: middle;
            text-align: center;
            border-radius: 8px;
        }


        .free-slot {
            background: #f8f9fa;
            color: #adb5bd;
            border: 1px dashed #dee2e6;
        }


        .subject-card {
            background: #e8f3ff;
            border: 1px solid #b8dcff;
            border-radius: 10px;
            padding: 10px;
            height: 100%;
            min-height: 95px;

            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }


        .subject-code {
            font-size: 15px;
            font-weight: 800;
            color: #0d6efd;
        }


        .subject-name {
            font-size: 13px;
            font-weight: 600;
            margin-top: 4px;
            color: #343a40;
        }


        .faculty-name {
            font-size: 12px;
            margin-top: 5px;
            color: #6c757d;
        }


        .time {
            font-size: 11px;
            color: #6c757d;
            margin-top: 3px;
        }


        .delete-btn {
            margin-top: 7px;
        }


        /* =====================================================
           ADD FORM
        ===================================================== */

        .form-card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            margin-top: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }


        .form-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
        }


        label {
            font-weight: 600;
            margin-bottom: 6px;
        }


        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px;
        }


        .btn-add {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
        }

    </style>

</head>


<body>


<?php
/*
   If your project already includes sidebar.php
   and topbar.php, keep these includes.

   If they are already part of your common layout,
   they will load here.
*/
?>

<?php include 'sidebar.php'; ?>

<div
    class="main-content"
    style="
        margin-left: 342px !important;
        width: calc(100% - 342px) !important;
        min-height: 100vh;
        padding-top: 86px;
        position: relative;
        z-index: 1;
    "
>

    <?php include 'topbar.php'; ?>

    <div
        class="page-container"
        style="
            width: 100%;
            padding: 25px 30px;
        "
    >


    <!-- =====================================================
         PAGE TITLE
    ====================================================== -->

    <div class="page-title">
        <i class="fa-solid fa-calendar-days"></i>
        Weekly Timetable
    </div>

    <div class="page-subtitle">
        Manage timetable for your assigned class
    </div>


    <!-- =====================================================
         CLASS INFORMATION
    ====================================================== -->

    <div class="class-card">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <div class="class-title">

                    <?= htmlspecialchars($class['department']) ?>

                    -

                    <?= htmlspecialchars($class['year']) ?>

                    -

                    <?= htmlspecialchars($class['section']) ?>

                </div>

                <div class="text-muted mt-1">

                    Advisor Timetable

                </div>

            </div>


            <div>

                <span class="entry-badge">

                    <?= $total_entries ?>

                    Entries

                </span>

            </div>

        </div>

    </div>


    <!-- =====================================================
         WEEKLY TIMETABLE GRID
    ====================================================== -->

    <div class="timetable-wrapper">

        <table class="timetable">

            <thead>

                <tr>

                    <th class="period-header">
                        Period
                    </th>

                    <?php foreach ($days as $day): ?>

                        <th>
                            <?= htmlspecialchars($day) ?>
                        </th>

                    <?php endforeach; ?>

                </tr>

            </thead>


            <tbody>


            <?php foreach ($periods as $period): ?>

                <tr>


                    <!-- PERIOD -->

                    <td class="period-cell">

                        Period <?= $period ?>

                    </td>


                    <?php foreach ($days as $day): ?>


                        <?php

                        /*
                         * ==================================================
                         * FIND THE TIMETABLE ENTRY FOR THIS CELL
                         * ==================================================
                         *
                         * Example:
                         *
                         * Monday + Period 1
                         *
                         * We directly search the timetable array.
                         *
                         * This fixes the previous problem where
                         * existing entries were showing as FREE.
                         */

                        $slot = null;

                        foreach ($timetable as $entry) {

                            $entry_day = strtolower(
                                trim(
                                    (string)$entry['day_of_week']
                                )
                            );

                            $cell_day = strtolower(
                                trim(
                                    (string)$day
                                )
                            );

                            $entry_period = (int)$entry['period_no'];

                            $cell_period = (int)$period;


                            if (
                                $entry_day === $cell_day
                                &&
                                $entry_period === $cell_period
                            ) {

                                $slot = $entry;

                                break;

                            }

                        }

                        ?>


                        <?php if ($slot): ?>

                            <!-- =================================================
                                 OCCUPIED SLOT
                            ================================================== -->

                            <td>

                                <div class="subject-card">


                                    <div class="subject-code">

                                        <?= htmlspecialchars(
                                            $slot['course_code']
                                        ) ?>

                                    </div>


                                    <div class="subject-name">

                                        <?= htmlspecialchars(
                                            $slot['course_name']
                                        ) ?>

                                    </div>


                                    <?php if (!empty($slot['faculty_name'])): ?>

                                        <div class="faculty-name">

                                            <i class="fa-solid fa-user"></i>

                                            <?= htmlspecialchars(
                                                $slot['faculty_name']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty($slot['start_time'])
                                        &&
                                        !empty($slot['end_time'])
                                    ): ?>

                                        <div class="time">

                                            <?= date(
                                                'h:i A',
                                                strtotime(
                                                    $slot['start_time']
                                                )
                                            ) ?>

                                            -

                                            <?= date(
                                                'h:i A',
                                                strtotime(
                                                    $slot['end_time']
                                                )
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <!-- DELETE -->

                                    <form
                                        method="POST"
                                        action="advisor_timetable_action.php"
                                        class="delete-btn"
                                        onsubmit="return confirm('Delete this timetable entry?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="timetable_id"
                                            value="<?= (int)$slot['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                        >

                                            <i class="fa-solid fa-trash"></i>

                                        </button>

                                    </form>


                                </div>

                            </td>


                        <?php else: ?>

                            <!-- =================================================
                                 FREE SLOT
                            ================================================== -->

                            <td class="free-slot">

                                <i class="fa-regular fa-calendar-xmark"></i>

                                <div>
                                    Free
                                </div>

                            </td>

                        <?php endif; ?>


                    <?php endforeach; ?>


                </tr>

            <?php endforeach; ?>


            </tbody>

        </table>

    </div>


    <!-- =====================================================
         ADD TIMETABLE
    ====================================================== -->

    <div class="form-card">


        <div class="form-title">

            <i class="fa-solid fa-plus-circle"></i>

            Add Timetable Entry

        </div>


        <form
            method="POST"
            action="advisor_timetable_action.php"
        >

            <input
                type="hidden"
                name="action"
                value="add"
            >


            <div class="row g-3">


                <!-- COURSE -->

                <div class="col-md-6">

                    <label>
                        Course
                    </label>

                    <select
                        name="course_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Course
                        </option>


                        <?php foreach ($courses as $course): ?>

                            <option
                                value="<?= (int)$course['id'] ?>"
                            >

                                <?= htmlspecialchars(
                                    $course['course_code']
                                ) ?>

                                -

                                <?= htmlspecialchars(
                                    $course['course_name']
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <!-- DAY -->

                <div class="col-md-3">

                    <label>
                        Day
                    </label>

                    <select
                        name="day_of_week"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Day
                        </option>


                        <?php foreach ($days as $day): ?>

                            <option value="<?= $day ?>">

                                <?= $day ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <!-- PERIOD -->

                <div class="col-md-3">

                    <label>
                        Period
                    </label>

                    <select
                        name="period_no"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Period
                        </option>


                        <?php foreach ($periods as $period): ?>

                            <option value="<?= $period ?>">

                                Period <?= $period ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <!-- START TIME -->

                <div class="col-md-6">

                    <label>
                        Start Time
                    </label>

                    <input
                        type="time"
                        name="start_time"
                        class="form-control"
                        required
                    >

                </div>


                <!-- END TIME -->

                <div class="col-md-6">

                    <label>
                        End Time
                    </label>

                    <input
                        type="time"
                        name="end_time"
                        class="form-control"
                        required
                    >

                </div>


                <!-- SUBMIT -->

                <div class="col-12">

                    <button
                        type="submit"
                        class="btn btn-primary btn-add"
                    >

                        <i class="fa-solid fa-plus"></i>

                        Add Timetable

                    </button>

                </div>


            </div>

        </form>

    </div>


</div>

</div>


<!-- =====================================================
     BOOTSTRAP JS
====================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>
<?php

session_start();
require_once 'db.php';

/* =========================================================
   ACCESS CHECK
========================================================= */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: index.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET FACULTY NAME
========================================================= */

$name_stmt = mysqli_prepare(
    $conn,
    "SELECT name FROM users WHERE id = ?"
);

mysqli_stmt_bind_param(
    $name_stmt,
    "i",
    $faculty_id
);

mysqli_stmt_execute($name_stmt);

$name_result = mysqli_stmt_get_result($name_stmt);

$faculty = mysqli_fetch_assoc($name_result);

mysqli_stmt_close($name_stmt);

$faculty_name = $faculty['name'] ?? 'Faculty';


/* =========================================================
   GET FACULTY TIMETABLE
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

        cl.department,
        cl.year,
        cl.section

     FROM timetable t

     INNER JOIN courses c
        ON c.id = t.course_id

     INNER JOIN classes cl
        ON cl.id = t.class_id

     WHERE t.faculty_id = ?

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
    $faculty_id
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

$total_entries = count($timetable);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Faculty Timetable</title>


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

        /* =====================================================
           PAGE
        ===================================================== */

        .faculty-timetable-page {

            width: 100%;

            padding: 25px 30px;

        }


        .faculty-title {

            font-size: 28px;

            font-weight: 700;

            color: #212529;

            margin-bottom: 5px;

        }


        .faculty-subtitle {

            color: #6c757d;

            margin-bottom: 25px;

        }


        /* =====================================================
           INFO CARD
        ===================================================== */

        .faculty-info {

            background: #ffffff;

            border-radius: 14px;

            padding: 20px 25px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.06);

        }


        .faculty-name {

            font-size: 20px;

            font-weight: 700;

            color: #212529;

        }


        .faculty-role {

            color: #6c757d;

            margin-top: 3px;

        }


        .entry-badge {

            background: #e7f1ff;

            color: #0d6efd;

            padding: 8px 14px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 700;

        }


        /* =====================================================
           GRID CONTAINER
        ===================================================== */

        .weekly-grid-wrapper {

            width: 100%;

            background: #ffffff;

            border-radius: 14px;

            padding: 15px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.06);

            overflow-x: auto;

        }


        /* =====================================================
           GRID
        ===================================================== */

        .weekly-grid {

            width: 100%;

            min-width: 1100px;

            border-collapse: separate;

            border-spacing: 6px;

            table-layout: fixed;

        }


        .weekly-grid th {

            background: #212529;

            color: #ffffff;

            text-align: center;

            padding: 14px 8px;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 700;

        }


        .weekly-grid th:first-child {

            width: 100px;

        }


        .weekly-grid td {

            height: 120px;

            padding: 0;

            vertical-align: middle;

            text-align: center;

            border-radius: 9px;

        }


        /* =====================================================
           PERIOD CELL
        ===================================================== */

        .period-cell {

            background: #f1f3f5;

            color: #343a40;

            font-weight: 700;

            text-align: center;

            vertical-align: middle;

        }


        .period-number {

            font-size: 14px;

            font-weight: 700;

        }


        /* =====================================================
           FREE
        ===================================================== */

        .free-cell {

            background: #f8f9fa;

            border: 1px dashed #d9dee5;

            color: #adb5bd;

        }


        .free-cell i {

            font-size: 18px;

            margin-bottom: 5px;

        }


        .free-text {

            font-size: 13px;

        }


        /* =====================================================
           SUBJECT CARD
        ===================================================== */

        .subject-card {

            width: 100%;

            height: 100%;

            min-height: 120px;

            background: #e8f3ff;

            border: 1px solid #b8dcff;

            border-radius: 9px;

            padding: 10px;

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

            margin-top: 4px;

            font-size: 13px;

            font-weight: 600;

            color: #343a40;

        }


        .class-name {

            margin-top: 5px;

            font-size: 12px;

            color: #6c757d;

        }


        .time {

            margin-top: 4px;

            font-size: 11px;

            color: #6c757d;

        }


        /* =====================================================
           ATTENDANCE BUTTON
        ===================================================== */

        .attendance-btn {

            margin-top: 8px;

            font-size: 11px;

            padding: 5px 9px;

            border-radius: 6px;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 991px) {

            .faculty-timetable-page {

                padding: 20px;

            }

        }


        @media (max-width: 767px) {

            .faculty-timetable-page {

                padding: 15px;

            }

        }

    </style>

</head>


<body>


<?php include 'sidebar.php'; ?>


<div
    class="main-content"
    style="
        margin-left: 342px;
        width: calc(100% - 342px);
        min-height: 100vh;
        padding-top: 86px;
    "
>


    <?php include 'topbar.php'; ?>


    <div class="faculty-timetable-page">


        <!-- =================================================
             TITLE
        ================================================= -->

        <div class="faculty-title">

            <i class="fa-solid fa-calendar-days"></i>

            My Weekly Timetable

        </div>


        <div class="faculty-subtitle">

            View your assigned classes and mark attendance.

        </div>


        <!-- =================================================
             FACULTY INFO
        ================================================= -->

        <div class="faculty-info">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <div class="faculty-name">

                        <?= htmlspecialchars($faculty_name) ?>

                    </div>

                    <div class="faculty-role">

                        Faculty

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


        <!-- =================================================
             WEEKLY GRID
        ================================================= -->

        <div class="weekly-grid-wrapper">


            <table class="weekly-grid">


                <thead>

                    <tr>

                        <th>
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

                            <div class="period-number">

                                Period <?= $period ?>

                            </div>

                        </td>


                        <?php foreach ($days as $day): ?>


                            <?php

                            /*
                             * IMPORTANT:
                             *
                             * Directly search the actual
                             * timetable array.
                             *
                             * No timetable_grid is used.
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


                                $entry_period = (int)
                                    $entry['period_no'];


                                $cell_period = (int)
                                    $period;


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


                                <!-- =================================
                                     OCCUPIED
                                ================================== -->

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


                                        <div class="class-name">

                                            <i class="fa-solid fa-users"></i>

                                            <?= htmlspecialchars(
                                                $slot['department']
                                            ) ?>

                                            -

                                            <?= htmlspecialchars(
                                                $slot['year']
                                            ) ?>

                                            -

                                            <?= htmlspecialchars(
                                                $slot['section']
                                            ) ?>

                                        </div>


                                        <?php if (
                                            !empty(
                                                $slot['start_time']
                                            )
                                            &&
                                            !empty(
                                                $slot['end_time']
                                            )
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


                                        <!-- ATTENDANCE -->

                                        <a
                                            href="faculty_attendance.php?timetable_id=<?= (int)$slot['id'] ?>"
                                            class="btn btn-sm btn-primary attendance-btn"
                                        >

                                            <i class="fa-solid fa-clipboard-check"></i>

                                            Attendance

                                        </a>


                                    </div>


                                </td>


                            <?php else: ?>


                                <!-- =================================
                                     FREE
                                ================================== -->

                                <td class="free-cell">

                                    <i
                                        class="fa-regular fa-calendar-xmark"
                                    ></i>

                                    <div class="free-text">

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


    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>
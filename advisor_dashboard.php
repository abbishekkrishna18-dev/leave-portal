<?php

session_start();
include "db.php";

/* =========================================================
   ADVISOR LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "advisor"
) {
    header("Location: index.php");
    exit();
}

$advisor_id = (int)$_SESSION["user_id"];
$advisor_name = $_SESSION["name"] ?? "Advisor";


/* =========================================================
   CHECK OD DEADLINES
========================================================= */

$deadline_file = __DIR__ . "/check_od_deadlines.php";

if (file_exists($deadline_file)) {

    ob_start();

    include $deadline_file;

    ob_end_clean();
}


/* =========================================================
   GET ADVISOR NOTIFICATIONS
========================================================= */

$notifications = [];
$unread_count = 0;

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, leave_id, message, is_read, created_at
     FROM notifications
     WHERE advisor_id = ?
     ORDER BY id DESC"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $advisor_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   COUNT UNREAD
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS unread_count
     FROM notifications
     WHERE advisor_id = ?
     AND is_read = 0"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $advisor_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $unread_count = (int)$row["unread_count"];

    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   GET LEAVE APPLICATIONS
========================================================= */

$applications = [];

$sql = "
    SELECT
        l.id,
        l.student_id,
        l.student_name,
        l.department,
        l.class_name,
        l.leave_type,
        l.date_from,
        l.date_to,
        l.no_of_days,
        l.reason,
        l.proof,
        l.applied_at,
        l.approval_deadline,
        l.advisor_status,
        l.hod_status,
        l.od_status
    FROM leaves l
    ORDER BY l.id DESC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $applications[] = $row;

    }

}


/* =========================================================
   STATISTICS
========================================================= */

$total_count = count($applications);

$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$blocked_count = 0;

foreach ($applications as $application) {

    if (
        ($application["advisor_status"] ?? "") === "Pending"
        &&
        ($application["od_status"] ?? "Active") !== "Blocked"
    ) {
        $pending_count++;
    }

    if (
        ($application["advisor_status"] ?? "") === "Approved"
    ) {
        $approved_count++;
    }

    if (
        ($application["advisor_status"] ?? "") === "Rejected"
    ) {
        $rejected_count++;
    }

    if (
        ($application["od_status"] ?? "") === "Blocked"
    ) {
        $blocked_count++;
    }

}


/* =========================================================
   SESSION MESSAGES
========================================================= */

$success_message = $_SESSION["action_success"] ?? "";
$error_message = $_SESSION["action_error"] ?? "";

unset($_SESSION["action_success"]);
unset($_SESSION["action_error"]);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Advisor Dashboard | Leave Portal</title>


    <!-- FAVICON -->

    <link
        rel="icon"
        type="image/png"
        href="image/icons/mkce_s.png"
    >


    <!-- BOOTSTRAP 5 -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        rel="stylesheet"
    >


    <!-- DATATABLES -->

    <link
        href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css"
        rel="stylesheet"
    >


    <!-- COMMON STYLE -->

    <link
        rel="stylesheet"
        href="style.css"
    >


    <style>

        /* =====================================================
           ADVISOR DASHBOARD
        ===================================================== */

        .dashboard-header {

            background:
                linear-gradient(
                    135deg,
                    #4e73df,
                    #1cc88a
                );

            color: #fff;

            border-radius: 14px;

            padding: 25px 30px;

            margin-bottom: 25px;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, 0.08);

        }


        .dashboard-header h2 {

            font-weight: 700;

            margin-bottom: 5px;

        }


        .dashboard-header p {

            margin: 0;

            opacity: 0.9;

        }


        /* =====================================================
           STAT CARDS
        ===================================================== */

        .advisor-stat-card {

            border: none;

            border-radius: 12px;

            background: #fff;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

            transition:
                transform .2s ease,
                box-shadow .2s ease;

        }


        .advisor-stat-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.10);

        }


        .advisor-stat-icon {

            width: 50px;

            height: 50px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

        }


        .icon-blue {

            background: rgba(
                78,
                115,
                223,
                .12
            );

            color: #4e73df;

        }


        .icon-yellow {

            background: rgba(
                246,
                194,
                62,
                .15
            );

            color: #b88900;

        }


        .icon-green {

            background: rgba(
                28,
                200,
                138,
                .12
            );

            color: #1cc88a;

        }


        .icon-red {

            background: rgba(
                231,
                74,
                59,
                .12
            );

            color: #e74a3b;

        }


        .stat-title {

            color: #858796;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .4px;

        }


        .stat-value {

            color: #333;

            font-size: 26px;

            font-weight: 700;

        }


        /* =====================================================
           NOTIFICATIONS
        ===================================================== */

        .notification-card {

            border: none;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

            margin-bottom: 25px;

        }


        .notification-item {

            border-bottom:
                1px solid #edf0f5;

            padding: 14px 18px;

        }


        .notification-item:last-child {

            border-bottom: none;

        }


        .notification-icon {

            width: 38px;

            height: 38px;

            min-width: 38px;

            border-radius: 50%;

            background:
                rgba(
                    231,
                    74,
                    59,
                    .12
                );

            color: #e74a3b;

            display: flex;

            align-items: center;

            justify-content: center;

        }


        /* =====================================================
           APPLICATION CARD
        ===================================================== */

        .applications-card {

            border: none;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

            overflow: hidden;

        }


        .applications-card .card-header {

            background: #fff;

            border-bottom:
                1px solid #e3e6f0;

            padding: 18px 20px;

        }


        .applications-card .card-header h5 {

            font-weight: 700;

            color: #333;

        }


        /* =====================================================
           STUDENT
        ===================================================== */

        .student-name {

            font-weight: 700;

            color: #333;

        }


        .student-id {

            color: #858796;

            font-size: 11px;

        }


        /* =====================================================
           REASON
        ===================================================== */

        .reason-cell {

            max-width: 200px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        /* =====================================================
           ACTION BUTTONS
        ===================================================== */

        .action-buttons {

            display: flex;

            gap: 5px;

            flex-wrap: nowrap;

        }


        .action-buttons form {

            margin: 0;

        }


        .action-buttons .btn {

            white-space: nowrap;

        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            border-radius: 20px;

            padding: 6px 10px;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;

        }


        .status-pending {

            background:
                rgba(
                    246,
                    194,
                    62,
                    .18
                );

            color: #947000;

        }


        .status-approved {

            background:
                rgba(
                    28,
                    200,
                    138,
                    .15
                );

            color: #13855c;

        }


        .status-rejected {

            background:
                rgba(
                    231,
                    74,
                    59,
                    .14
                );

            color: #c0392b;

        }


        .status-blocked {

            background:
                rgba(
                    26,
                    28,
                    35,
                    .12
                );

            color: #1a1c23;

        }


        /* =====================================================
           TYPE BADGES
        ===================================================== */

        .type-od {

            background:
                rgba(
                    54,
                    185,
                    204,
                    .15
                );

            color: #177589;

            padding: 6px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

        }


        .type-leave {

            background:
                rgba(
                    133,
                    135,
                    150,
                    .14
                );

            color: #5f606c;

            padding: 6px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

        }


        /* =====================================================
           PROOF
        ===================================================== */

        .proof-image {

            max-width: 100%;

            max-height: 600px;

            object-fit: contain;

            border-radius: 8px;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 767px) {

            .dashboard-header {

                padding: 20px;

            }


            .dashboard-header h2 {

                font-size: 21px;

            }


            .applications-card .card-header {

                padding: 15px;

            }


            .action-buttons {

                flex-direction: column;

            }


            .action-buttons .btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<?php include "sidebar.php"; ?>


<!-- =====================================================
     TOPBAR
===================================================== -->

<?php include "topbar.php"; ?>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="content">

    <div class="page-wrapper">


        <!-- =================================================
             DASHBOARD HEADER
        ================================================== -->

        <div class="dashboard-header">

            <div
                class="
                    d-flex
                    flex-column
                    flex-md-row
                    justify-content-between
                    align-items-md-center
                    gap-3
                "
            >

                <div>

                    <h2>

                        <i
                            class="fa-solid fa-user-check me-2"
                        ></i>

                        Advisor Dashboard

                    </h2>

                    <p>

                        Review and manage student
                        OD / Leave applications.

                    </p>

                </div>


                <div>

                    <span
                        class="
                            badge
                            bg-light
                            text-dark
                            px-3
                            py-2
                        "
                    >

                        <i
                            class="fa-solid fa-user-tie me-1"
                        ></i>

                        Class Advisor

                    </span>

                </div>

            </div>

        </div>


        <!-- =================================================
             SUCCESS MESSAGE
        ================================================== -->

        <?php if ($success_message !== ""): ?>

            <div
                class="
                    alert
                    alert-success
                    alert-dismissible
                    fade
                    show
                "
                role="alert"
            >

                <i
                    class="fa-solid fa-circle-check me-2"
                ></i>

                <?php
                echo htmlspecialchars(
                    $success_message,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ERROR MESSAGE
        ================================================== -->

        <?php if ($error_message !== ""): ?>

            <div
                class="
                    alert
                    alert-danger
                    alert-dismissible
                    fade
                    show
                "
                role="alert"
            >

                <i
                    class="fa-solid fa-circle-exclamation me-2"
                ></i>

                <?php
                echo htmlspecialchars(
                    $error_message,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <!-- =================================================
             NOTIFICATIONS
        ================================================== -->

        <?php if ($unread_count > 0): ?>

            <div class="card notification-card" id="notifications">

                <div class="card-header bg-white py-3">

                    <div
                        class="
                            d-flex
                            justify-content-between
                            align-items-center
                        "
                    >

                        <h5 class="mb-0 fw-bold">

                            <i
                                class="
                                    fa-solid
                                    fa-bell
                                    text-danger
                                    me-2
                                "
                            ></i>

                            OD Notifications

                        </h5>


                        <span class="badge bg-danger">

                            <?php echo $unread_count; ?>

                            Unread

                        </span>

                    </div>

                </div>


                <div class="card-body p-0">

                    <?php foreach ($notifications as $notification): ?>

                        <?php
                        if ((int)$notification["is_read"] !== 0) {
                            continue;
                        }
                        ?>


                        <div class="notification-item">

                            <div
                                class="
                                    d-flex
                                    align-items-start
                                    gap-3
                                "
                            >

                                <div class="notification-icon">

                                    <i
                                        class="
                                            fa-solid
                                            fa-triangle-exclamation
                                        "
                                    ></i>

                                </div>


                                <div class="flex-grow-1">

                                    <div class="fw-semibold">

                                        <?php
                                        echo htmlspecialchars(
                                            $notification["message"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </div>


                                    <small class="text-muted">

                                        <i
                                            class="
                                                fa-regular
                                                fa-clock
                                                me-1
                                            "
                                        ></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $notification["created_at"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </small>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="row g-3 mb-4">


            <!-- TOTAL -->

            <div class="col-6 col-xl-3">

                <div
                    class="
                        card
                        advisor-stat-card
                        h-100
                    "
                >

                    <div class="card-body">

                        <div
                            class="
                                d-flex
                                justify-content-between
                                align-items-center
                            "
                        >

                            <div>

                                <div class="stat-title">

                                    Total Applications

                                </div>

                                <div class="stat-value">

                                    <?php
                                    echo $total_count;
                                    ?>

                                </div>

                            </div>


                            <div class="advisor-stat-icon icon-blue">

                                <i
                                    class="
                                        fa-solid
                                        fa-file-lines
                                    "
                                ></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PENDING -->

            <div class="col-6 col-xl-3">

                <div
                    class="
                        card
                        advisor-stat-card
                        h-100
                    "
                >

                    <div class="card-body">

                        <div
                            class="
                                d-flex
                                justify-content-between
                                align-items-center
                            "
                        >

                            <div>

                                <div class="stat-title">

                                    Pending Review

                                </div>

                                <div class="stat-value">

                                    <?php
                                    echo $pending_count;
                                    ?>

                                </div>

                            </div>


                            <div
                                class="
                                    advisor-stat-icon
                                    icon-yellow
                                "
                            >

                                <i
                                    class="
                                        fa-solid
                                        fa-clock
                                    "
                                ></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- APPROVED -->

            <div class="col-6 col-xl-3">

                <div
                    class="
                        card
                        advisor-stat-card
                        h-100
                    "
                >

                    <div class="card-body">

                        <div
                            class="
                                d-flex
                                justify-content-between
                                align-items-center
                            "
                        >

                            <div>

                                <div class="stat-title">

                                    Approved

                                </div>

                                <div class="stat-value">

                                    <?php
                                    echo $approved_count;
                                    ?>

                                </div>

                            </div>


                            <div
                                class="
                                    advisor-stat-icon
                                    icon-green
                                "
                            >

                                <i
                                    class="
                                        fa-solid
                                        fa-circle-check
                                    "
                                ></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- REJECTED / BLOCKED -->

            <div class="col-6 col-xl-3">

                <div
                    class="
                        card
                        advisor-stat-card
                        h-100
                    "
                >

                    <div class="card-body">

                        <div
                            class="
                                d-flex
                                justify-content-between
                                align-items-center
                            "
                        >

                            <div>

                                <div class="stat-title">

                                    Rejected / Blocked

                                </div>

                                <div class="stat-value">

                                    <?php
                                    echo $rejected_count
                                        + $blocked_count;
                                    ?>

                                </div>

                            </div>


                            <div
                                class="
                                    advisor-stat-icon
                                    icon-red
                                "
                            >

                                <i
                                    class="
                                        fa-solid
                                        fa-ban
                                    "
                                ></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             APPLICATIONS
        ================================================== -->

        <div class="card applications-card">


            <!-- HEADER -->

            <div class="card-header">

                <div
                    class="
                        d-flex
                        flex-column
                        flex-md-row
                        justify-content-between
                        align-items-md-center
                        gap-2
                    "
                >

                    <div>

                        <h5 class="mb-1">

                            <i
                                class="
                                    fa-solid
                                    fa-folder-open
                                    text-primary
                                    me-2
                                "
                            ></i>

                            Student Leave Applications

                        </h5>

                        <small class="text-muted">

                            Review applications submitted
                            by students.

                        </small>

                    </div>


                    <div>

                        <span class="badge bg-primary">

                            <?php
                            echo $pending_count;
                            ?>

                            Pending

                        </span>

                    </div>

                </div>

            </div>


            <!-- BODY -->

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table
                        id="advisorTable"
                        class="
                            table
                            table-hover
                            align-middle
                            mb-0
                        "
                    >

                        <thead>

                            <tr>

                                <th>S.No</th>

                                <th>Student</th>

                                <th>Department</th>

                                <th>Class</th>

                                <th>Type</th>

                                <th>From</th>

                                <th>To</th>

                                <th>Days</th>

                                <th>Reason</th>

                                <th>Applied</th>

                                <th>Status</th>

                                <th>Proof</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (empty($applications)): ?>

                            <tr>

                                <td
                                    colspan="13"
                                    class="text-center"
                                >

                                    <div class="empty-state">

                                        <i
                                            class="
                                                fa-solid
                                                fa-inbox
                                                d-block
                                            "
                                        ></i>

                                        <h5>
                                            No applications found
                                        </h5>

                                        <p class="mb-0">

                                            There are currently
                                            no student applications.

                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php else: ?>


                            <?php
                            $sno = 1;
                            ?>


                            <?php foreach ($applications as $row): ?>

                                <?php

                                $advisor_status =
                                    $row["advisor_status"]
                                    ?? "Pending";

                                $od_status =
                                    $row["od_status"]
                                    ?? "Active";

                                $leave_type =
                                    strtolower(
                                        trim(
                                            $row["leave_type"]
                                            ?? ""
                                        )
                                    );

                                $is_od = in_array(
                                    $leave_type,
                                    [
                                        "od",
                                        "on duty",
                                        "on-duty",
                                        "on_duty"
                                    ],
                                    true
                                );

                                ?>


                                <tr>


                                    <!-- S.NO -->

                                    <td>

                                        <?php
                                        echo $sno++;
                                        ?>

                                    </td>


                                    <!-- STUDENT -->

                                    <td>

                                        <div class="student-name">

                                            <?php
                                            echo htmlspecialchars(
                                                $row["student_name"]
                                                ?? "Unknown",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>

                                        </div>

                                        <div class="student-id">

                                            ID:

                                            <?php
                                            echo htmlspecialchars(
                                                $row["student_id"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <!-- DEPARTMENT -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["department"]
                                            ?? "-",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </td>


                                    <!-- CLASS -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["class_name"]
                                            ?? "-",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </td>


                                    <!-- TYPE -->

                                    <td>

                                        <?php if ($is_od): ?>

                                            <span class="type-od">

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-person-walking
                                                        me-1
                                                    "
                                                ></i>

                                                OD

                                            </span>

                                        <?php else: ?>

                                            <span class="type-leave">

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-calendar-day
                                                        me-1
                                                    "
                                                ></i>

                                                Leave

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- FROM -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["date_from"]
                                            ?? "-",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </td>


                                    <!-- TO -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["date_to"]
                                            ?? "-",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </td>


                                    <!-- DAYS -->

                                    <td>

                                        <span
                                            class="
                                                badge
                                                bg-light
                                                text-dark
                                            "
                                        >

                                            <?php
                                            echo (int)(
                                                $row["no_of_days"]
                                                ?? 0
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- REASON -->

                                    <td>

                                        <div
                                            class="reason-cell"
                                            title="<?php
                                                echo htmlspecialchars(
                                                    $row["reason"]
                                                    ?? "",
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $row["reason"]
                                                ?? "-",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <!-- APPLIED -->

                                    <td>

                                        <small>

                                            <?php
                                            echo htmlspecialchars(
                                                $row["applied_at"]
                                                ?? "-",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>

                                        </small>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if (
                                            $od_status === "Blocked"
                                        ): ?>

                                            <span
                                                class="
                                                    status-badge
                                                    status-blocked
                                                "
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-lock
                                                    "
                                                ></i>

                                                Blocked

                                            </span>

                                        <?php elseif (
                                            $advisor_status
                                            === "Pending"
                                        ): ?>

                                            <span
                                                class="
                                                    status-badge
                                                    status-pending
                                                "
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-clock
                                                    "
                                                ></i>

                                                Pending

                                            </span>

                                        <?php elseif (
                                            $advisor_status
                                            === "Approved"
                                        ): ?>

                                            <span
                                                class="
                                                    status-badge
                                                    status-approved
                                                "
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-check
                                                    "
                                                ></i>

                                                Approved

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="
                                                    status-badge
                                                    status-rejected
                                                "
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-xmark
                                                    "
                                                ></i>

                                                Rejected

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PROOF -->

                                    <td>

                                        <?php
                                        $proof =
                                            trim(
                                                $row["proof"]
                                                ?? ""
                                            );
                                        ?>


                                        <?php if (
                                            $proof !== ""
                                        ): ?>

                                            <button
                                                type="button"
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-primary
                                                "
                                                data-bs-toggle="modal"
                                                data-bs-target="#proofModal<?php
                                                    echo (int)$row["id"];
                                                ?>"
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-eye
                                                    "
                                                ></i>

                                                View

                                            </button>


                                            <!-- PROOF MODAL -->

                                            <div
                                                class="
                                                    modal
                                                    fade
                                                "
                                                id="proofModal<?php
                                                    echo (int)$row["id"];
                                                ?>"
                                                tabindex="-1"
                                                aria-hidden="true"
                                            >

                                                <div
                                                    class="
                                                        modal-dialog
                                                        modal-dialog-centered
                                                        modal-lg
                                                    "
                                                >

                                                    <div
                                                        class="
                                                            modal-content
                                                        "
                                                    >

                                                        <div
                                                            class="
                                                                modal-header
                                                            "
                                                        >

                                                            <h5
                                                                class="
                                                                    modal-title
                                                                "
                                                            >

                                                                <i
                                                                    class="
                                                                        fa-solid
                                                                        fa-file-image
                                                                        text-primary
                                                                        me-2
                                                                    "
                                                                ></i>

                                                                Leave Proof

                                                            </h5>


                                                            <button
                                                                type="button"
                                                                class="btn-close"
                                                                data-bs-dismiss="modal"
                                                            ></button>

                                                        </div>


                                                        <div
                                                            class="
                                                                modal-body
                                                                text-center
                                                            "
                                                        >

                                                            <img
                                                                src="uploads/<?php
                                                                    echo htmlspecialchars(
                                                                        $proof,
                                                                        ENT_QUOTES,
                                                                        "UTF-8"
                                                                    );
                                                                ?>"
                                                                class="proof-image"
                                                                alt="Leave Proof"
                                                            >

                                                        </div>


                                                        <div
                                                            class="
                                                                modal-footer
                                                            "
                                                        >

                                                            <button
                                                                type="button"
                                                                class="
                                                                    btn
                                                                    btn-secondary
                                                                "
                                                                data-bs-dismiss="modal"
                                                            >

                                                                Close

                                                            </button>

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                        <?php else: ?>

                                            <span class="text-muted small">

                                                No Proof

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <div class="action-buttons">


                                            <!-- VIEW -->

                                            <a
                                                href="leave_details.php?id=<?php
                                                    echo (int)$row["id"];
                                                ?>"
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-primary
                                                "
                                                title="View Details"
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-eye
                                                    "
                                                ></i>

                                            </a>


                                            <?php if (
                                                $advisor_status
                                                === "Pending"
                                                &&
                                                $od_status
                                                !== "Blocked"
                                            ): ?>


                                                <!-- APPROVE -->

                                                <form
                                                    method="POST"
                                                    action="advisor_action.php"
                                                    class="approve-form"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="leave_id"
                                                        value="<?php
                                                            echo (int)$row["id"];
                                                        ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="approve"
                                                    >

                                                    <button
                                                        type="button"
                                                        class="
                                                            btn
                                                            btn-sm
                                                            btn-success
                                                            approve-btn
                                                        "
                                                        title="Approve"
                                                    >

                                                        <i
                                                            class="
                                                                fa-solid
                                                                fa-check
                                                            "
                                                        ></i>

                                                    </button>

                                                </form>


                                                <!-- REJECT -->

                                                <form
                                                    method="POST"
                                                    action="advisor_action.php"
                                                    class="reject-form"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="leave_id"
                                                        value="<?php
                                                            echo (int)$row["id"];
                                                        ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="reject"
                                                    >

                                                    <button
                                                        type="button"
                                                        class="
                                                            btn
                                                            btn-sm
                                                            btn-danger
                                                            reject-btn
                                                        "
                                                        title="Reject"
                                                    >

                                                        <i
                                                            class="
                                                                fa-solid
                                                                fa-xmark
                                                            "
                                                        ></i>

                                                    </button>

                                                </form>


                                            <?php else: ?>

                                                <span
                                                    class="
                                                        text-muted
                                                        small
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            fa-solid
                                                            fa-lock
                                                            me-1
                                                        "
                                                    ></i>

                                                    Locked

                                                </span>

                                            <?php endif; ?>


                                        </div>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


    </div>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<?php include "footer.php"; ?>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


<script
    src="https://code.jquery.com/jquery-3.7.1.min.js"
></script>


<script
    src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"
></script>


<script
    src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"
></script>


<script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
></script>


<script>

/* =========================================================
   DATATABLE
========================================================= */

$(document).ready(function () {

    $('#advisorTable').DataTable({

        pageLength: 10,

        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],

        order: [
            [0, "desc"]
        ],

        language: {

            search: "Search applications:",

            lengthMenu: "Show _MENU_ applications",

            zeroRecords:
                "No matching applications found.",

            info:
                "Showing _START_ to _END_ of _TOTAL_ applications",

            infoEmpty:
                "No applications available",

            infoFiltered:
                "(filtered from _MAX_ applications)"

        }

    });


    /* =====================================================
       APPROVE
    ===================================================== */

    $('.approve-btn').on('click', function () {

        const form =
            $(this).closest('form');

        Swal.fire({

            title: "Approve Application?",

            text:
                "This application will be forwarded to the HOD.",

            icon: "question",

            showCancelButton: true,

            confirmButtonText:
                "Yes, Approve",

            cancelButtonText:
                "Cancel",

            confirmButtonColor:
                "#1cc88a",

            cancelButtonColor:
                "#858796"

        }).then(function (result) {

            if (result.isConfirmed) {

                form.submit();

            }

        });

    });


    /* =====================================================
       REJECT
    ===================================================== */

    $('.reject-btn').on('click', function () {

        const form =
            $(this).closest('form');

        Swal.fire({

            title: "Reject Application?",

            text:
                "Are you sure you want to reject this application?",

            icon: "warning",

            showCancelButton: true,

            confirmButtonText:
                "Yes, Reject",

            cancelButtonText:
                "Cancel",

            confirmButtonColor:
                "#e74a3b",

            cancelButtonColor:
                "#858796"

        }).then(function (result) {

            if (result.isConfirmed) {

                form.submit();

            }

        });

    });

});

</script>


</body>

</html>
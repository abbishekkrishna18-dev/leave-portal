<?php

session_start();
include "db.php";

/* =====================================================
   CHECK STUDENT LOGIN
===================================================== */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: index.php");
    exit();
}

$student_id = (int)$_SESSION["user_id"];
$student_name = $_SESSION["name"] ?? "Student";


/* =====================================================
   FETCH STUDENT APPLICATIONS
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM leaves
     WHERE student_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


/* =====================================================
   STATISTICS
===================================================== */

$total = 0;
$pending = 0;
$approved = 0;
$rejected = 0;
$blocked = 0;


/* =====================================================
   FETCH COUNT DATA
===================================================== */

$count_stmt = mysqli_prepare(
    $conn,
    "SELECT advisor_status, hod_status, od_status
     FROM leaves
     WHERE student_id = ?"
);

mysqli_stmt_bind_param(
    $count_stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($count_stmt);

$count_result = mysqli_stmt_get_result($count_stmt);


while ($row = mysqli_fetch_assoc($count_result)) {

    $total++;

    $advisor = $row["advisor_status"] ?? "Pending";
    $hod = $row["hod_status"] ?? "Pending";
    $od = $row["od_status"] ?? "Active";


    if (
        $advisor === "Pending" &&
        $od !== "Blocked"
    ) {

        $pending++;

    } elseif (
        $advisor === "Approved" &&
        $hod === "Approved"
    ) {

        $approved++;

    } elseif (
        $advisor === "Rejected" ||
        $hod === "Rejected"
    ) {

        $rejected++;

    } elseif (
        $advisor === "Blocked" ||
        $hod === "Blocked" ||
        $od === "Blocked"
    ) {

        $blocked++;
    }
}

mysqli_stmt_close($count_stmt);


/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function statusBadge($status)
{
    $status = trim($status);

    switch ($status) {

        case "Approved":

            return '<span class="badge bg-success status-badge">
                        <i class="fa-solid fa-circle-check me-1"></i>
                        Approved
                    </span>';

        case "Rejected":

            return '<span class="badge bg-danger status-badge">
                        <i class="fa-solid fa-circle-xmark me-1"></i>
                        Rejected
                    </span>';

        case "Blocked":

            return '<span class="badge bg-dark status-badge">
                        <i class="fa-solid fa-ban me-1"></i>
                        Blocked
                    </span>';

        case "Active":

            return '<span class="badge bg-primary status-badge">
                        Active
                    </span>';

        default:

            return '<span class="badge bg-warning text-dark status-badge">
                        <i class="fa-solid fa-clock me-1"></i>
                        Pending
                    </span>';
    }
}


function overallStatus($advisor, $hod, $od)
{
    if (
        $advisor === "Blocked" ||
        $hod === "Blocked" ||
        $od === "Blocked"
    ) {
        return statusBadge("Blocked");
    }

    if (
        $advisor === "Rejected" ||
        $hod === "Rejected"
    ) {
        return statusBadge("Rejected");
    }

    if (
        $advisor === "Approved" &&
        $hod === "Approved"
    ) {
        return statusBadge("Approved");
    }

    return statusBadge("Pending");
}


function formatDate($date)
{
    if (empty($date)) {
        return "-";
    }

    return date(
        "d M Y",
        strtotime($date)
    );
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

    <title>
        Student Dashboard | Leave Portal
    </title>


    <!-- FAVICON -->

    <link
        rel="icon"
        href="image/icons/mkce_s.png"
    >


    <!-- BOOTSTRAP -->

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
        rel="stylesheet"
        href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css"
    >


    <!-- SWEETALERT -->

    <script
        src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
    ></script>


    <style>

        :root {

            --sidebar-width: 250px;

            --sidebar-collapsed: 70px;

            --topbar-height: 60px;

            --primary: #4e73df;

            --success: #1cc88a;

            --danger: #e74a3b;

            --warning: #f6c23e;

            --light-bg: #f8f9fc;

            --shadow:
                0 4px 15px rgba(0,0,0,0.08);
        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background: var(--light-bg);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #343a40;
        }


        /* =================================================
           CONTENT
        ================================================= */

        .content {

            margin-left:
                var(--sidebar-width);

            padding-top:
                var(--topbar-height);

            min-height: 100vh;

            transition: 0.3s;
        }


        .page-wrapper {

            padding: 25px;

            padding-bottom: 90px;
        }


        /* =================================================
           PAGE HEADER
        ================================================= */

        .content-nav {

            background:
                linear-gradient(
                    135deg,
                    #4e73df,
                    #1cc88a
                );

            border-radius: 14px;

            padding: 22px 25px;

            color: white;

            box-shadow: var(--shadow);

            margin-bottom: 25px;
        }


        .content-nav h4 {

            margin: 0;

            font-weight: 700;
        }


        .content-nav p {

            margin: 5px 0 0;

            opacity: 0.9;
        }


        /* =================================================
           STAT CARDS
        ================================================= */

        .stat-card {

            background: white;

            border: none;

            border-radius: 14px;

            box-shadow: var(--shadow);

            padding: 20px;

            height: 100%;

            position: relative;

            overflow: hidden;
        }


        .stat-card::after {

            content: "";

            position: absolute;

            width: 70px;

            height: 70px;

            right: -20px;

            bottom: -20px;

            border-radius: 50%;

            background:
                rgba(78,115,223,0.08);
        }


        .stat-icon {

            width: 45px;

            height: 45px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 12px;

            font-size: 18px;
        }


        .icon-blue {

            background: rgba(78,115,223,0.12);

            color: #4e73df;
        }


        .icon-yellow {

            background: rgba(246,194,62,0.15);

            color: #d69e00;
        }


        .icon-green {

            background: rgba(28,200,138,0.12);

            color: #1cc88a;
        }


        .icon-red {

            background: rgba(231,74,59,0.12);

            color: #e74a3b;
        }


        .icon-dark {

            background: rgba(52,58,64,0.12);

            color: #343a40;
        }


        .stat-title {

            color: #858796;

            font-size: 13px;

            font-weight: 600;

            text-transform: uppercase;
        }


        .stat-number {

            font-size: 30px;

            font-weight: 700;

            margin-top: 3px;
        }


        /* =================================================
           MAIN CARD
        ================================================= */

        .main-card {

            background: white;

            border: none;

            border-radius: 14px;

            box-shadow: var(--shadow);

            overflow: hidden;
        }


        .main-card-header {

            padding: 18px 22px;

            border-bottom:
                1px solid #e9ecef;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            flex-wrap: wrap;
        }


        .main-card-header h5 {

            margin: 0;

            font-weight: 700;
        }


        .main-card-body {

            padding: 20px;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .btn-mkce {

            background:
                linear-gradient(
                    135deg,
                    #4e73df,
                    #1cc88a
                );

            color: white;

            border: none;

            border-radius: 8px;

            font-weight: 600;

            padding:
                10px 16px;
        }


        .btn-mkce:hover {

            color: white;

            opacity: 0.9;
        }


        /* =================================================
           TABLE
        ================================================= */

        .table {

            margin-bottom: 0;
        }


        .table thead th {

            background: #f1f4f9;

            color: #495057;

            font-size: 12px;

            text-transform: uppercase;

            white-space: nowrap;

            border-bottom:
                2px solid #dee2e6;

            padding: 13px 10px;
        }


        .table tbody td {

            vertical-align: middle;

            font-size: 13px;

            padding: 13px 10px;
        }


        .table tbody tr:hover {

            background: #f8f9fc;
        }


        .status-badge {

            font-size: 11px;

            padding:
                7px 10px;

            white-space: nowrap;
        }


        .reason-cell {

            max-width: 180px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        /* =================================================
           ACTION BUTTONS
        ================================================= */

        .action-btn {

            width: 34px;

            height: 34px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            margin-right: 3px;
        }


        /* =================================================
           EMPTY STATE
        ================================================= */

        .empty-state {

            text-align: center;

            padding: 60px 20px;

            color: #858796;
        }


        .empty-state i {

            font-size: 55px;

            margin-bottom: 15px;

            opacity: 0.35;
        }


        .empty-state h5 {

            color: #495057;

            font-weight: 700;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 768px) {

            .content {

                margin-left: 0;
            }


            .page-wrapper {

                padding: 15px;

                padding-bottom: 90px;
            }


            .content-nav {

                padding: 18px;
            }


            .main-card-body {

                padding: 12px;
            }


            .stat-number {

                font-size: 26px;
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
     CONTENT
===================================================== -->

<div class="content">

    <div class="page-wrapper">


        <!-- =================================================
             PAGE HEADER
        ================================================= -->

        <div class="content-nav">

            <div
                class="d-flex justify-content-between align-items-center flex-wrap gap-3"
            >

                <div>

                    <h4>

                        <i
                            class="fa-solid fa-graduation-cap me-2"
                        ></i>

                        Student Dashboard

                    </h4>

                    <p>

                        Welcome,
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $student_name
                            );
                            ?>
                        </strong>

                        — manage your OD and leave applications.

                    </p>

                </div>


                <div
                    class="d-flex gap-2 flex-wrap"
                >

                    <a
                        href="apply_leave.php"
                        class="btn btn-light"
                    >

                        <i
                            class="fa-solid fa-plus me-1"
                        ></i>

                        Apply OD / Leave

                    </a>


                    <!-- DOWNLOAD EXCEL -->

<a
    href="download_od_excel.php"
    class="btn btn-light"
>

    <i class="fa-solid fa-file-excel me-1"></i>

    Excel

</a>


<!-- DOWNLOAD PDF -->

<a
    href="download_od_pdf.php"
    class="btn btn-light"
>

    <i class="fa-solid fa-file-pdf me-1"></i>

    PDF

</a>
                </div>

            </div>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================= -->

        <div class="row g-3 mb-4">


            <!-- TOTAL -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div
                        class="stat-icon icon-blue"
                    >

                        <i
                            class="fa-solid fa-file-lines"
                        ></i>

                    </div>

                    <div class="stat-title">
                        Total Applications
                    </div>

                    <div class="stat-number">

                        <?php
                        echo $total;
                        ?>

                    </div>

                </div>

            </div>


            <!-- PENDING -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div
                        class="stat-icon icon-yellow"
                    >

                        <i
                            class="fa-solid fa-clock"
                        ></i>

                    </div>

                    <div class="stat-title">
                        Pending
                    </div>

                    <div
                        class="stat-number text-warning"
                    >

                        <?php
                        echo $pending;
                        ?>

                    </div>

                </div>

            </div>


            <!-- APPROVED -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div
                        class="stat-icon icon-green"
                    >

                        <i
                            class="fa-solid fa-circle-check"
                        ></i>

                    </div>

                    <div class="stat-title">
                        Fully Approved
                    </div>

                    <div
                        class="stat-number text-success"
                    >

                        <?php
                        echo $approved;
                        ?>

                    </div>

                </div>

            </div>


            <!-- REJECTED -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div
                        class="stat-icon icon-red"
                    >

                        <i
                            class="fa-solid fa-circle-xmark"
                        ></i>

                    </div>

                    <div class="stat-title">
                        Rejected
                    </div>

                    <div
                        class="stat-number text-danger"
                    >

                        <?php
                        echo $rejected;
                        ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             BLOCKED APPLICATION NOTICE
        ================================================= -->

        <?php if ($blocked > 0): ?>

            <div
                class="alert alert-dark d-flex align-items-center mb-4"
            >

                <i
                    class="fa-solid fa-ban me-3"
                ></i>

                <div>

                    <strong>
                        <?php echo $blocked; ?>
                        application(s) blocked.
                    </strong>

                    Some OD applications may have exceeded
                    the allowed approval deadline.

                </div>

            </div>

        <?php endif; ?>


        <!-- =================================================
             APPLICATION TABLE
        ================================================= -->

        <div class="main-card">


            <!-- CARD HEADER -->

            <div class="main-card-header">

                <div>

                    <h5>

                        <i
                            class="fa-solid fa-list-check text-primary me-2"
                        ></i>

                        My Leave Applications

                    </h5>

                    <small class="text-muted">

                        Track your OD / Leave approval status

                    </small>

                </div>


                <a
                    href="apply_leave.php"
                    class="btn btn-mkce"
                >

                    <i
                        class="fa-solid fa-plus me-1"
                    ></i>

                    New Application

                </a>

            </div>


            <!-- CARD BODY -->

            <div class="main-card-body">


                <?php if (
                    $result &&
                    mysqli_num_rows($result) > 0
                ): ?>


                    <div class="table-responsive">


                        <table
                            id="leaveTable"
                            class="table table-hover align-middle"
                        >

                            <thead>

                                <tr>

                                    <th>
                                        S.No
                                    </th>

                                    <th>
                                        Application
                                    </th>

                                    <th>
                                        Department
                                    </th>

                                    <th>
                                        Class
                                    </th>

                                    <th>
                                        Type
                                    </th>

                                    <th>
                                        From
                                    </th>

                                    <th>
                                        To
                                    </th>

                                    <th>
                                        Days
                                    </th>

                                    <th>
                                        Advisor
                                    </th>

                                    <th>
                                        HOD
                                    </th>

                                    <th>
                                        Overall
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php

                            $serial = 1;

                            while (
                                $row =
                                mysqli_fetch_assoc($result)
                            ):

                                $advisor_status =
                                    $row["advisor_status"]
                                    ?? "Pending";

                                $hod_status =
                                    $row["hod_status"]
                                    ?? "Pending";

                                $od_status =
                                    $row["od_status"]
                                    ?? "Active";

                            ?>

                                <tr>


                                    <!-- S.NO -->

                                    <td>

                                        <strong>
                                            <?php
                                            echo $serial++;
                                            ?>
                                        </strong>

                                    </td>


                                    <!-- APPLICATION -->

                                    <td>

                                        <span
                                            class="fw-bold text-primary"
                                        >

                                            #
                                            <?php
                                            echo htmlspecialchars(
                                                $row["id"]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- DEPARTMENT -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["department"]
                                            ?? "-"
                                        );
                                        ?>

                                    </td>


                                    <!-- CLASS -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["class_name"]
                                            ?? "-"
                                        );
                                        ?>

                                    </td>


                                    <!-- TYPE -->

                                    <td>

                                        <?php
                                        $type =
                                            strtoupper(
                                                $row["leave_type"]
                                                ?? "-"
                                            );
                                        ?>

                                        <?php if (
                                            $type === "OD"
                                        ): ?>

                                            <span
                                                class="badge bg-info text-dark"
                                            >

                                                <i
                                                    class="fa-solid fa-building-columns me-1"
                                                ></i>

                                                OD

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="badge bg-secondary"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $type
                                                );
                                                ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- FROM -->

                                    <td>

                                        <?php
                                        echo formatDate(
                                            $row["date_from"]
                                        );
                                        ?>

                                    </td>


                                    <!-- TO -->

                                    <td>

                                        <?php
                                        echo formatDate(
                                            $row["date_to"]
                                        );
                                        ?>

                                    </td>


                                    <!-- DAYS -->

                                    <td>

                                        <span
                                            class="fw-bold"
                                        >

                                            <?php
                                            echo (int)(
                                                $row["no_of_days"]
                                                ?? 0
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- ADVISOR -->

                                    <td>

                                        <?php
                                        echo statusBadge(
                                            $advisor_status
                                        );
                                        ?>

                                    </td>


                                    <!-- HOD -->

                                    <td>

                                        <?php if (
                                            $advisor_status !==
                                            "Approved"
                                        ): ?>

                                            <span
                                                class="badge bg-secondary status-badge"
                                            >

                                                <i
                                                    class="fa-solid fa-minus me-1"
                                                ></i>

                                                Not Sent

                                            </span>

                                        <?php else: ?>

                                            <?php
                                            echo statusBadge(
                                                $hod_status
                                            );
                                            ?>

                                        <?php endif; ?>

                                    </td>


                                    <!-- OVERALL -->

                                    <td>

                                        <?php
                                        echo overallStatus(
                                            $advisor_status,
                                            $hod_status,
                                            $od_status
                                        );
                                        ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td class="text-nowrap">


                                        <!-- VIEW -->

                                        <a
                                            href="leave_details.php?id=<?php echo (int)$row["id"]; ?>"
                                            class="btn btn-primary btn-sm action-btn"
                                            title="View Details"
                                        >

                                            <i
                                                class="fa-solid fa-eye"
                                            ></i>

                                        </a>


                                        <?php

                                        /*
                                         * EDIT AND DELETE ARE
                                         * AVAILABLE ONLY WHILE
                                         * ADVISOR STATUS IS PENDING.
                                         */

                                        if (
                                            $advisor_status ===
                                            "Pending"
                                        ):

                                        ?>


                                            <!-- EDIT -->

                                            <a
                                                href="edit_leave.php?id=<?php echo (int)$row["id"]; ?>"
                                                class="btn btn-warning btn-sm action-btn"
                                                title="Edit Application"
                                            >

                                                <i
                                                    class="fa-solid fa-pen"
                                                ></i>

                                            </a>


                                            <!-- DELETE -->

                                            <a
                                                href="delete_leave.php?id=<?php echo (int)$row["id"]; ?>"
                                                class="btn btn-danger btn-sm action-btn delete-btn"
                                                title="Delete Application"
                                            >

                                                <i
                                                    class="fa-solid fa-trash"
                                                ></i>

                                            </a>


                                        <?php else: ?>


                                            <!-- LOCKED -->

                                            <span
                                                class="badge bg-secondary"
                                                title="Application cannot be edited after advisor action"
                                            >

                                                <i
                                                    class="fa-solid fa-lock me-1"
                                                ></i>

                                                Locked

                                            </span>


                                        <?php endif; ?>


                                    </td>

                                </tr>


                            <?php endwhile; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <!-- EMPTY STATE -->

                    <div class="empty-state">

                        <i
                            class="fa-solid fa-folder-open"
                        ></i>

                        <h5>
                            No Applications Yet
                        </h5>

                        <p>
                            You haven't submitted any
                            OD or Leave applications.
                        </p>

                        <a
                            href="apply_leave.php"
                            class="btn btn-mkce"
                        >

                            <i
                                class="fa-solid fa-plus me-1"
                            ></i>

                            Apply Now

                        </a>

                    </div>


                <?php endif; ?>


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


<script>

$(document).ready(function () {

    if ($("#leaveTable").length) {

        $("#leaveTable").DataTable({

            pageLength: 10,

            lengthMenu: [
                [5, 10, 25, 50, -1],
                [5, 10, 25, 50, "All"]
            ],

            order: [
                [0, "asc"]
            ],

            language: {

                search: "Search applications:",

                emptyTable:
                    "No leave applications found.",

                zeroRecords:
                    "No matching applications found."

            }

        });

    }

});


/* =====================================================
   DELETE CONFIRMATION
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const deleteButtons =
            document.querySelectorAll(
                ".delete-btn"
            );


        deleteButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function (event) {

                        event.preventDefault();

                        const url =
                            this.getAttribute(
                                "href"
                            );


                        Swal.fire({

                            title:
                                "Delete Application?",

                            text:
                                "This application and its proof document will be deleted.",

                            icon:
                                "warning",

                            showCancelButton:
                                true,

                            confirmButtonText:
                                "Yes, Delete",

                            cancelButtonText:
                                "Cancel",

                            confirmButtonColor:
                                "#e74a3b",

                            cancelButtonColor:
                                "#858796"

                        }).then(
                            function (result) {

                                if (
                                    result.isConfirmed
                                ) {

                                    window.location.href =
                                        url;

                                }

                            }
                        );

                    }
                );

            }
        );

    }
);

</script>


</body>

</html>
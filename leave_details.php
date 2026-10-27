<?php

session_start();
include "db.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$role = $_SESSION["role"];


/*
|--------------------------------------------------------------------------
| GET LEAVE ID
|--------------------------------------------------------------------------
*/

$leave_id = $_GET["id"] ?? "";

if (!is_numeric($leave_id) || (int)$leave_id <= 0) {
    die("Invalid leave application.");
}

$leave_id = (int)$leave_id;


/*
|--------------------------------------------------------------------------
| GET LEAVE APPLICATION
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM leaves
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to load leave application.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $leave_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$leave = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$leave) {
    die("Leave application not found.");
}


/*
|--------------------------------------------------------------------------
| STUDENT ACCESS CHECK
|--------------------------------------------------------------------------
|
| Students can view only their own applications.
| Advisors and HODs can view applications.
|--------------------------------------------------------------------------
*/

if (
    $role === "student" &&
    (int)$leave["student_id"] !== $user_id
) {
    die("You are not authorized to view this application.");
}


/*
|--------------------------------------------------------------------------
| BACK PAGE
|--------------------------------------------------------------------------
*/

if ($role === "student") {

    $back_page = "student_dashboard.php";
    $role_title = "Student";

} elseif ($role === "advisor") {

    $back_page = "advisor_dashboard.php";
    $role_title = "Class Advisor";

} elseif ($role === "hod") {

    $back_page = "hod_dashboard.php";
    $role_title = "HOD";

} else {

    header("Location: index.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| STATUS HELPERS
|--------------------------------------------------------------------------
*/

$advisor_status = $leave["advisor_status"] ?? "Pending";
$hod_status = $leave["hod_status"] ?? "Pending";
$od_status = $leave["od_status"] ?? "Active";


/*
|--------------------------------------------------------------------------
| OVERALL STATUS
|--------------------------------------------------------------------------
*/

if (strcasecmp($od_status, "Blocked") === 0) {

    $overall_status = "Blocked";
    $overall_class = "bg-dark";

} elseif (
    strcasecmp($advisor_status, "Rejected") === 0 ||
    strcasecmp($hod_status, "Rejected") === 0
) {

    $overall_status = "Rejected";
    $overall_class = "bg-danger";

} elseif (
    strcasecmp($advisor_status, "Approved") === 0 &&
    strcasecmp($hod_status, "Approved") === 0
) {

    $overall_status = "Approved";
    $overall_class = "bg-success";

} else {

    $overall_status = "Pending";
    $overall_class = "bg-warning text-dark";
}


/*
|--------------------------------------------------------------------------
| ESCAPE HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
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

    <title>Leave Details | Leave Portal</title>


    <!-- Favicon -->

    <link
        rel="icon"
        href="image/icons/mkce_s.png"
    >


    <!-- Bootstrap 5.3.2 -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >


    <style>

        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --dark: #1a1c23;
            --light-bg: #f8f9fc;
        }


        body {
            background: var(--light-bg);
            font-family: Arial, sans-serif;
            min-height: 100vh;
        }


        .main-content {
            margin-left: 250px;
            padding-top: 60px;
            min-height: calc(100vh - 60px);
        }


        .page-wrapper {
            padding: 30px;
        }


        .page-header {
            background: linear-gradient(
                135deg,
                #4e73df,
                #224abe
            );
            color: white;
            border-radius: 14px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }


        .page-header h2 {
            margin: 0;
            font-weight: 700;
        }


        .page-header p {
            margin: 6px 0 0;
            opacity: 0.9;
        }


        .details-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            overflow: hidden;
            background: white;
        }


        .card-title-bar {
            background: linear-gradient(
                135deg,
                #4e73df,
                #1cc88a
            );
            color: white;
            padding: 20px 25px;
        }


        .card-title-bar h4 {
            margin: 0;
            font-weight: 700;
        }


        .application-number {
            font-size: 14px;
            opacity: 0.9;
        }


        .section-title {
            font-weight: 700;
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 22px;
        }


        .detail-box {
            background: #f8f9fc;
            border: 1px solid #eaecf4;
            border-radius: 10px;
            padding: 15px 18px;
            height: 100%;
        }


        .detail-label {
            color: #858796;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }


        .detail-value {
            color: #2e2e2e;
            font-size: 15px;
            font-weight: 600;
            word-break: break-word;
        }


        .reason-box {
            background: #f8f9fc;
            border: 1px solid #eaecf4;
            border-radius: 10px;
            padding: 18px;
            line-height: 1.7;
        }


        .proof-container {
            background: #f8f9fc;
            border: 1px solid #eaecf4;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }


        .proof-image {
            max-width: 100%;
            width: 400px;
            max-height: 450px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 6px;
            background: white;
        }


        .timeline {
            position: relative;
            margin-left: 12px;
            padding-left: 35px;
            border-left: 3px solid #dbe2f5;
        }


        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            background: #fff;
            border: 1px solid #eaecf4;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
        }


        .timeline-item::before {
            content: "";
            position: absolute;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: var(--primary);
            left: -44px;
            top: 22px;
            border: 3px solid white;
            box-shadow: 0 0 0 2px var(--primary);
        }


        .timeline-item h6 {
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
        }


        .timeline-item p {
            margin-bottom: 10px;
        }


        .status-badge {
            font-size: 12px;
            padding: 7px 12px;
            border-radius: 20px;
        }


        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }


        .btn {
            border-radius: 8px;
        }


        .top-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }


        .metadata-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }


        .metadata-item {
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 12px;
        }


        @media (max-width: 991px) {

            .main-content {
                margin-left: 0;
            }

            .page-wrapper {
                padding: 20px 15px;
            }

        }


        @media (max-width: 576px) {

            .page-header {
                padding: 20px;
            }

            .page-header h2 {
                font-size: 21px;
            }

            .card-title-bar {
                padding: 18px;
            }

            .card-title-bar h4 {
                font-size: 18px;
            }

            .timeline {
                padding-left: 25px;
            }

            .timeline-item::before {
                left: -34px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<?php include "sidebar.php"; ?>


<!-- =========================================================
     TOPBAR
========================================================= -->

<?php include "topbar.php"; ?>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="main-content">

    <div class="page-wrapper">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h2>
                        <i class="fas fa-file-lines me-2"></i>
                        Leave Application Details
                    </h2>

                    <p>
                        View complete application information and approval status.
                    </p>

                </div>


                <div class="action-buttons">

                    <a
                        href="<?php echo e($back_page); ?>"
                        class="btn btn-light"
                    >

                        <i class="fas fa-arrow-left me-2"></i>

                        Back to Dashboard

                    </a>

                </div>

            </div>

        </div>


        <!-- APPLICATION CARD -->

        <div class="details-card">


            <!-- CARD HEADER -->

            <div class="card-title-bar">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div>

                        <h4>
                            Application Information
                        </h4>

                        <div class="application-number">
                            Application #<?php echo e($leave["id"]); ?>
                        </div>

                    </div>


                    <div>

                        <span class="badge <?php echo e($overall_class); ?> status-badge">

                            <i class="fas fa-circle-check me-1"></i>

                            <?php echo e($overall_status); ?>

                        </span>

                    </div>

                </div>


                <div class="metadata-row">

                    <div class="metadata-item">

                        <i class="fas fa-user me-1"></i>

                        <?php echo e($role_title); ?>

                    </div>


                    <div class="metadata-item">

                        <i class="fas fa-calendar me-1"></i>

                        Applied:
                        <?php echo e($leave["applied_at"] ?? "N/A"); ?>

                    </div>

                </div>

            </div>


            <!-- CARD BODY -->

            <div class="card-body p-4">


                <!-- =====================================================
                     STUDENT INFORMATION
                ====================================================== -->

                <h5 class="section-title">

                    <i class="fas fa-user-graduate me-2"></i>

                    Student Information

                </h5>


                <div class="row g-3">


                    <div class="col-md-4">

                        <div class="detail-box">

                            <div class="detail-label">
                                Student ID
                            </div>

                            <div class="detail-value">
                                <?php echo e($leave["student_id"]); ?>
                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-box">

                            <div class="detail-label">
                                Student Name
                            </div>

                            <div class="detail-value">
                                <?php echo e($leave["student_name"]); ?>
                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-box">

                            <div class="detail-label">
                                Department
                            </div>

                            <div class="detail-value">
                                <?php echo e($leave["department"]); ?>
                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-box">

                            <div class="detail-label">
                                Class
                            </div>

                            <div class="detail-value">
                                <?php echo e($leave["class_name"]); ?>
                            </div>

                        </div>

                    </div>


                </div>


                <!-- =====================================================
                     LEAVE INFORMATION
                ====================================================== -->

                <h5 class="section-title mt-5">

                    <i class="fas fa-calendar-days me-2"></i>

                    Leave Information

                </h5>


                <div class="row g-3">


                    <div class="col-md-4">

                        <div class="detail-box">

                            <div class="detail-label">
                                Leave Type
                            </div>

                            <div class="detail-value">

                                <?php echo e($leave["leave_type"]); ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-box">

                            <div class="detail-label">
                                Date From
                            </div>

                            <div class="detail-value">

                                <?php echo e($leave["date_from"]); ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-box">

                            <div class="detail-label">
                                Date To
                            </div>

                            <div class="detail-value">

                                <?php echo e($leave["date_to"]); ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-box">

                            <div class="detail-label">
                                Number of Days
                            </div>

                            <div class="detail-value">

                                <span class="badge bg-secondary">

                                    <?php echo e($leave["no_of_days"]); ?>

                                    Days

                                </span>

                            </div>

                        </div>

                    </div>


                    <?php if (!empty($leave["approval_deadline"])): ?>

                        <div class="col-md-4">

                            <div class="detail-box">

                                <div class="detail-label">
                                    Approval Deadline
                                </div>

                                <div class="detail-value">

                                    <?php echo e($leave["approval_deadline"]); ?>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($leave["od_status"])): ?>

                        <div class="col-md-4">

                            <div class="detail-box">

                                <div class="detail-label">
                                    OD Status
                                </div>

                                <div class="detail-value">

                                    <?php

                                    if (
                                        strtolower($leave["od_status"]) === "blocked"
                                    ) {

                                        echo '<span class="badge bg-dark">Blocked</span>';

                                    } elseif (
                                        strtolower($leave["od_status"]) === "active"
                                    ) {

                                        echo '<span class="badge bg-success">Active</span>';

                                    } else {

                                        echo '<span class="badge bg-secondary">'
                                            . e($leave["od_status"])
                                            . '</span>';
                                    }

                                    ?>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>


                    <div class="col-12">

                        <div class="detail-label mb-2">
                            Reason
                        </div>

                        <div class="reason-box">

                            <?php

                            echo nl2br(
                                e($leave["reason"])
                            );

                            ?>

                        </div>

                    </div>


                </div>


                <!-- =====================================================
                     PROOF
                ====================================================== -->

                <h5 class="section-title mt-5">

                    <i class="fas fa-paperclip me-2"></i>

                    Supporting Proof

                </h5>


                <?php if (!empty($leave["proof"])): ?>

                    <div class="proof-container">

                        <img
                            src="uploads/<?php echo e($leave["proof"]); ?>"
                            class="proof-image"
                            alt="Leave Proof"
                        >

                        <div class="mt-3">

                            <a
                                href="uploads/<?php echo e($leave["proof"]); ?>"
                                target="_blank"
                                class="btn btn-info text-white"
                            >

                                <i class="fas fa-up-right-from-square me-2"></i>

                                Open Full Image

                            </a>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="alert alert-warning">

                        <i class="fas fa-triangle-exclamation me-2"></i>

                        Proof not available for this application.

                    </div>

                <?php endif; ?>


                <!-- =====================================================
                     APPROVAL STATUS
                ====================================================== -->

                <h5 class="section-title mt-5">

                    <i class="fas fa-route me-2"></i>

                    Approval Status

                </h5>


                <div class="timeline">


                    <!-- APPLICATION -->

                    <div class="timeline-item">

                        <h6>

                            <i class="fas fa-file-circle-check me-2 text-primary"></i>

                            Leave Application

                        </h6>

                        <p class="text-muted">

                            Application submitted by student.

                        </p>

                        <span class="badge bg-warning text-dark status-badge">

                            <i class="fas fa-paper-plane me-1"></i>

                            Submitted

                        </span>

                    </div>


                    <!-- ADVISOR -->

                    <div class="timeline-item">

                        <h6>

                            <i class="fas fa-user-check me-2 text-primary"></i>

                            Class Advisor

                        </h6>


                        <?php if ($advisor_status === "Pending"): ?>

                            <span class="badge bg-warning text-dark status-badge">

                                <i class="fas fa-clock me-1"></i>

                                Pending

                            </span>


                        <?php elseif ($advisor_status === "Approved"): ?>

                            <span class="badge bg-success status-badge">

                                <i class="fas fa-check me-1"></i>

                                Approved

                            </span>


                        <?php elseif ($advisor_status === "Blocked"): ?>

                            <span class="badge bg-dark status-badge">

                                <i class="fas fa-ban me-1"></i>

                                Blocked

                            </span>


                        <?php else: ?>

                            <span class="badge bg-danger status-badge">

                                <i class="fas fa-xmark me-1"></i>

                                Rejected

                            </span>

                        <?php endif; ?>


                        <?php if (!empty($leave["advisor_remarks"])): ?>

                            <div class="mt-3">

                                <strong>
                                    Remarks:
                                </strong>

                                <div class="text-muted mt-1">

                                    <?php echo e($leave["advisor_remarks"]); ?>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- HOD -->

                    <div class="timeline-item">

                        <h6>

                            <i class="fas fa-user-tie me-2 text-primary"></i>

                            HOD

                        </h6>


                        <?php if ($advisor_status !== "Approved"): ?>

                            <span class="badge bg-secondary status-badge">

                                <i class="fas fa-minus-circle me-1"></i>

                                Not Sent

                            </span>


                        <?php elseif ($hod_status === "Pending"): ?>

                            <span class="badge bg-info text-dark status-badge">

                                <i class="fas fa-clock me-1"></i>

                                Pending

                            </span>


                        <?php elseif ($hod_status === "Approved"): ?>

                            <span class="badge bg-success status-badge">

                                <i class="fas fa-check me-1"></i>

                                Approved

                            </span>


                        <?php else: ?>

                            <span class="badge bg-danger status-badge">

                                <i class="fas fa-xmark me-1"></i>

                                Rejected

                            </span>

                        <?php endif; ?>


                        <?php if (!empty($leave["hod_remarks"])): ?>

                            <div class="mt-3">

                                <strong>
                                    Remarks:
                                </strong>

                                <div class="text-muted mt-1">

                                    <?php echo e($leave["hod_remarks"]); ?>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>


                </div>


                <!-- =====================================================
                     FOOTER ACTIONS
                ====================================================== -->

                <div class="border-top pt-4 mt-4">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                        <div class="text-muted">

                            <i class="fas fa-circle-info me-1"></i>

                            Application #<?php echo e($leave["id"]); ?>

                        </div>


                        <div class="action-buttons">

                            <a
                                href="<?php echo e($back_page); ?>"
                                class="btn btn-outline-secondary"
                            >

                                <i class="fas fa-arrow-left me-2"></i>

                                Back

                            </a>


                            <?php if (
                                $role === "student" &&
                                $advisor_status === "Pending"
                            ): ?>

                                <a
                                    href="edit_leave.php?id=<?php echo e($leave["id"]); ?>"
                                    class="btn btn-primary"
                                >

                                    <i class="fas fa-pen-to-square me-2"></i>

                                    Edit Application

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     FOOTER
========================================================= -->

<?php include "footer.php"; ?>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>
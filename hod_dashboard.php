<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "hod") {
    header("Location: index.php");
    exit();
}

/* -------------------------------------------------------
   FETCH APPLICATIONS
------------------------------------------------------- */
$sql = "
    SELECT *
    FROM leaves
    WHERE advisor_status = 'Approved'
    ORDER BY id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database error: " . mysqli_error($conn));
}

/* -------------------------------------------------------
   STATISTICS
------------------------------------------------------- */
$total = 0;
$pending = 0;
$approved = 0;
$rejected = 0;

$data = [];

while ($row = mysqli_fetch_assoc($result)) {

    $data[] = $row;
    $total++;

    if ($row["hod_status"] === "Pending") {
        $pending++;
    } elseif ($row["hod_status"] === "Approved") {
        $approved++;
    } elseif (
        $row["hod_status"] === "Rejected" ||
        $row["advisor_status"] === "Blocked" ||
        ($row["od_status"] ?? "") === "Blocked"
    ) {
        $rejected++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>HOD Dashboard | Leave Portal</title>

    <link rel="icon"
          href="image/mkce_s.png">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <link rel="stylesheet" href="style.css">

    <style>

        .page-header {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            border-radius: 14px;
            padding: 24px;
            color: #fff;
            margin-bottom: 25px;
        }

        .page-header h2 {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,.08);
            transition: .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            background: #f1f3f9;
        }

        .application-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0,0,0,.07);
        }

        .table thead th {
            white-space: nowrap;
            font-size: 13px;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 14px;
        }

        .status-badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-rejected {
            background: #f8d7da;
            color: #842029;
        }

        .status-blocked {
            background: #212529;
            color: #fff;
        }

        .student-name {
            font-weight: 600;
        }

        .reason-box {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 15px;
        }

        .modal-header {
            background: #4e73df;
            color: white;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

    </style>

</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">

    <?php include "topbar.php"; ?>

    <div class="container-fluid py-4">

        <!-- PAGE HEADER -->
        <div class="page-header">

            <div class="d-flex justify-content-between
                        align-items-center flex-wrap gap-3">

                <div>
                    <h2>
                        <i class="fa-solid fa-user-tie me-2"></i>
                        HOD Dashboard
                    </h2>

                    <p class="mb-0 opacity-75">
                        Review applications approved by Advisors
                    </p>
                </div>

            </div>

        </div>
        <!-- STATISTICS -->
        <div class="row g-4 mb-4">

            <div class="col-xl-3 col-md-6">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <div class="d-flex
                                    justify-content-between
                                    align-items-center">

                            <div>

                                <div class="text-muted small">
                                    Total Applications
                                </div>

                                <h3 class="fw-bold mb-0">
                                    <?= $total ?>
                                </h3>

                            </div>

                            <div class="stat-icon">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <div class="d-flex
                                    justify-content-between
                                    align-items-center">

                            <div>

                                <div class="text-muted small">
                                    Pending
                                </div>

                                <h3 class="fw-bold mb-0">
                                    <?= $pending ?>
                                </h3>

                            </div>

                            <div class="stat-icon">
                                <i class="fa-solid fa-clock"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <div class="d-flex
                                    justify-content-between
                                    align-items-center">

                            <div>

                                <div class="text-muted small">
                                    Approved
                                </div>

                                <h3 class="fw-bold mb-0">
                                    <?= $approved ?>
                                </h3>

                            </div>

                            <div class="stat-icon">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <div class="d-flex
                                    justify-content-between
                                    align-items-center">

                            <div>

                                <div class="text-muted small">
                                    Rejected / Blocked
                                </div>

                                <h3 class="fw-bold mb-0">
                                    <?= $rejected ?>
                                </h3>

                            </div>

                            <div class="stat-icon">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- APPLICATION TABLE -->
        <div class="card application-card">

            <div class="card-header bg-white py-3">

                <div class="d-flex
                            justify-content-between
                            align-items-center
                            flex-wrap gap-2">

                    <div>

                        <h5 class="mb-1 fw-bold">
                            HOD Review Applications
                        </h5>

                        <small class="text-muted">
                            Applications approved by the Advisor
                        </small>

                    </div>

                </div>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table id="hodTable"
                           class="table table-hover align-middle">

                        <thead>

                            <tr>
                                <th>Student</th>
                                <th>Department</th>
                                <th>Class</th>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Days</th>
                                <th>Advisor</th>
                                <th>HOD Status</th>
                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($data as $index => $row): ?>

                            <?php
                            $hod_status = $row["hod_status"] ?? "Pending";
                            $od_status = $row["od_status"] ?? "";

                            if ($od_status === "Blocked") {
                                $badge_class = "status-blocked";
                            } elseif ($hod_status === "Approved") {
                                $badge_class = "status-approved";
                            } elseif ($hod_status === "Rejected") {
                                $badge_class = "status-rejected";
                            } else {
                                $badge_class = "status-pending";
                            }
                            ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <div class="student-name">
                                        <?= htmlspecialchars(
                                            $row["student_name"]
                                        ) ?>
                                    </div>

                                    <small class="text-muted">
                                        ID:
                                        <?= htmlspecialchars(
                                            $row["student_id"]
                                        ) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row["department"]
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row["class_name"]
                                    ) ?>
                                </td>

                                <td>
                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars(
                                            $row["leave_type"]
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row["date_from"]
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row["date_to"]
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row["no_of_days"]
                                    ) ?>
                                </td>

                                <td>

                                    <span class="status-badge
                                        status-approved">

                                        <i class="fa-solid fa-check me-1"></i>
                                        Approved

                                    </span>

                                </td>

                                <td>

                                    <span class="status-badge
                                        <?= $badge_class ?>">

                                        <?= htmlspecialchars(
                                            $hod_status
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <div class="d-flex gap-1">

                                        <!-- VIEW -->
                                        <a href="leave_details.php?id=<?= $row["id"] ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="View Details">

                                            <i class="fa-solid fa-eye"></i>

                                        </a>


                                        <?php if (
                                            $hod_status === "Pending" &&
                                            $od_status !== "Blocked"
                                        ): ?>

                                            <!-- APPROVE -->
                                            <form method="POST"
                                                  action="hod_action.php"
                                                  class="approve-form">

                                                <input
                                                    type="hidden"
                                                    name="leave_id"
                                                    value="<?= $row["id"] ?>">

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="approve">

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-success"
                                                    title="Approve">

                                                    <i class="fa-solid fa-check"></i>

                                                </button>

                                            </form>


                                            <!-- REJECT -->
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                title="Reject"
                                                onclick="openRejectModal(
                                                    <?= (int)$row["id"] ?>,
                                                    '<?= htmlspecialchars(
                                                        addslashes(
                                                            $row["student_name"]
                                                        )
                                                    ) ?>'
                                                )">

                                                <i class="fa-solid fa-xmark"></i>

                                            </button>

                                        <?php else: ?>

                                            <span class="text-muted small
                                                         align-self-center">
                                                Processed
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <?php include "footer.php"; ?>

</div>


<!-- =====================================================
     REJECTION MODAL
===================================================== -->

<div class="modal fade"
     id="rejectModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="fa-solid fa-circle-xmark me-2"></i>

                    Reject Application

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>


            <form method="POST"
                  action="hod_action.php"
                  id="rejectForm">

                <div class="modal-body">

                    <div class="alert alert-warning">

                        <i class="fa-solid fa-triangle-exclamation me-2"></i>

                        You are rejecting the application of
                        <strong id="rejectStudentName"></strong>.

                    </div>


                    <input type="hidden"
                           name="leave_id"
                           id="rejectLeaveId">

                    <input type="hidden"
                           name="action"
                           value="reject">


                    <label class="form-label fw-semibold">

                        Reason for Rejection
                        <span class="text-danger">*</span>

                    </label>

                    <textarea
                        name="remarks"
                        id="rejectRemarks"
                        class="form-control"
                        rows="5"
                        placeholder="Enter the reason for rejecting this application..."
                        required></textarea>

                    <div class="form-text">
                        This reason will be saved and sent to the student by email.
                    </div>

                </div>


                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-danger">

                        <i class="fa-solid fa-xmark me-1"></i>

                        Reject Application

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     SCRIPTS
===================================================== -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js">
</script>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js">
</script>

<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js">
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
</script>


<script>

$(document).ready(function () {

    $('#hodTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
        responsive: true
    });

});


/* -------------------------------------------------------
   APPROVE CONFIRMATION
------------------------------------------------------- */

document.querySelectorAll(".approve-form").forEach(function(form) {

    form.addEventListener("submit", function(event) {

        event.preventDefault();

        Swal.fire({
            title: "Approve Application?",
            text: "This application will be approved by the HOD.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, Approve",
            cancelButtonText: "Cancel"
        }).then(function(result) {

            if (result.isConfirmed) {
                form.submit();
            }

        });

    });

});


/* -------------------------------------------------------
   OPEN REJECTION MODAL
------------------------------------------------------- */

function openRejectModal(id, studentName) {

    document.getElementById("rejectLeaveId").value = id;

    document.getElementById("rejectStudentName").textContent =
        studentName;

    document.getElementById("rejectRemarks").value = "";

    const modalElement =
        document.getElementById("rejectModal");

    const modal =
        bootstrap.Modal.getOrCreateInstance(modalElement);

    modal.show();

}


/* -------------------------------------------------------
   REJECTION FORM VALIDATION
------------------------------------------------------- */

document.getElementById("rejectForm")
    .addEventListener("submit", function(event) {

        const remarks =
            document.getElementById("rejectRemarks")
                .value
                .trim();

        if (remarks === "") {

            event.preventDefault();

            Swal.fire({
                icon: "warning",
                title: "Reason Required",
                text: "Please enter a reason for rejection."
            });

        }

    });

</script>


<?php

/* -------------------------------------------------------
   SESSION ALERTS
------------------------------------------------------- */

if (isset($_SESSION["action_success"])):

    $message = $_SESSION["action_success"];
    unset($_SESSION["action_success"]);

?>

<script>

Swal.fire({
    icon: "success",
    title: "Success",
    text: <?= json_encode($message) ?>,
    timer: 2500,
    showConfirmButton: false
});

</script>

<?php endif; ?>


<?php

if (isset($_SESSION["action_error"])):

    $message = $_SESSION["action_error"];
    unset($_SESSION["action_error"]);

?>

<script>

Swal.fire({
    icon: "error",
    title: "Unable to Process",
    text: <?= json_encode($message) ?>
});

</script>

<?php endif; ?>


</body>
</html>
<?php

session_start();
require_once "db.php";

// --------------------------------------------------
// HOD LOGIN CHECK
// --------------------------------------------------

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: index.php");
    exit();
}


// --------------------------------------------------
// SESSION MESSAGES
// --------------------------------------------------

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);


// --------------------------------------------------
// GET EXISTING ADVISORS
// --------------------------------------------------

$advisors = [];

$sql = "
    SELECT id, name, username, email
    FROM users
    WHERE role = 'advisor'
    ORDER BY name ASC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $advisors[] = $row;
    }

}


// --------------------------------------------------
// GET EXISTING CLASS ADVISOR ASSIGNMENTS
// --------------------------------------------------

$assignments = [];

$sql = "
    SELECT
        ca.id AS assignment_id,
        c.id AS class_id,
        c.department,
        c.year,
        c.section,
        u.id AS advisor_id,
        u.name AS advisor_name,
        u.username AS advisor_username,
        u.email AS advisor_email
    FROM class_advisors ca

    INNER JOIN classes c
        ON c.id = ca.class_id

    INNER JOIN users u
        ON u.id = ca.advisor_id

    ORDER BY
        c.department ASC,
        c.year ASC,
        c.section ASC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $assignments[] = $row;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Class Advisor Management | MKCE Leave Portal</title>

    <link
        rel="icon"
        href="image/mkce.png"
    >

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >

    <!-- Common CSS -->
    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        .page-header {

            background: linear-gradient(
                135deg,
                #4e73df,
                #1cc88a
            );

            color: white;

            border-radius: 15px;

            padding: 25px;

            margin-bottom: 25px;
        }


        .page-header h2 {

            margin: 0;

            font-weight: 700;
        }


        .page-header p {

            margin: 5px 0 0;

            opacity: 0.9;
        }


        .form-card,
        .table-card {

            background: #fff;

            border-radius: 15px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.08);

            border: none;
        }


        .card-title {

            font-weight: 700;

            color: #333;
        }


        .form-label {

            font-weight: 600;
        }


        .form-control,
        .form-select {

            min-height: 45px;

            border-radius: 8px;
        }


        .assign-btn {

            background: #4e73df;

            border: none;

            color: white;

            border-radius: 8px;

            padding: 11px 22px;

            font-weight: 600;
        }


        .assign-btn:hover {

            background: #375dcc;

            color: white;
        }


        .table thead th {

            background: #4e73df;

            color: white;

            vertical-align: middle;
        }


        .badge-class {

            background: #eef2ff;

            color: #4e73df;

            padding: 7px 12px;

            border-radius: 20px;

            font-weight: 600;
        }


        .badge-advisor {

            background: #e8f8f1;

            color: #16895d;

            padding: 7px 12px;

            border-radius: 20px;

            font-weight: 600;
        }


        .empty-box {

            text-align: center;

            padding: 40px 20px;

            color: #888;
        }


        .action-btn {

            min-width: 75px;
        }


        .add-advisor-btn {

            background: white;

            color: #4e73df;

            border: none;

            font-weight: 600;

            border-radius: 8px;

            padding: 10px 18px;
        }


        .add-advisor-btn:hover {

            background: #f1f3f9;

            color: #375dcc;
        }

    </style>

</head>


<body>


<?php include "sidebar.php"; ?>

<?php include "topbar.php"; ?>


<div class="main-content">

    <div class="container-fluid py-4">


        <!-- =========================================
             PAGE HEADER
        ========================================== -->

        <div class="page-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h2>

                        <i class="fa-solid fa-user-tie me-2"></i>

                        Class Advisor Management

                    </h2>

                    <p>

                        Assign class advisors to departments, years and sections.

                    </p>

                </div>


                <!-- ADD ADVISOR BUTTON -->

                <a
                    href="hod_add_advisor.php"
                    class="btn add-advisor-btn"
                >

                    <i class="fa-solid fa-user-plus me-2"></i>

                    Add New Advisor

                </a>

            </div>

        </div>


        <!-- =========================================
             SUCCESS MESSAGE
        ========================================== -->

        <?php if ($success): ?>

            <div class="alert alert-success alert-dismissible fade show">

                <i class="fa-solid fa-circle-check me-2"></i>

                <?= htmlspecialchars($success) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <!-- =========================================
             ERROR MESSAGE
        ========================================== -->

        <?php if ($error): ?>

            <div class="alert alert-danger alert-dismissible fade show">

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?= htmlspecialchars($error) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <div class="row g-4">


            <!-- =====================================
                 ASSIGN CLASS ADVISOR
            ====================================== -->

            <div class="col-lg-5">

                <div class="card form-card">

                    <div class="card-body p-4">

                        <h4 class="card-title mb-4">

                            <i class="fa-solid fa-user-check me-2 text-primary"></i>

                            Assign Class Advisor

                        </h4>


                        <form
                            action="hod_class_advisor_action.php"
                            method="POST"
                        >


                            <!-- DEPARTMENT -->

                            <div class="mb-3">

                                <label class="form-label">

                                    Department

                                </label>


                                <select
                                    name="department"
                                    class="form-select"
                                    required
                                >

                                    <option value="">

                                        Select Department

                                    </option>


                                    <option value="CSE">

                                        CSE

                                    </option>


                                    <option value="ECE">

                                        ECE

                                    </option>


                                    <option value="EEE">

                                        EEE

                                    </option>


                                    <option value="MECH">

                                        MECH

                                    </option>


                                    <option value="CIVIL">

                                        CIVIL

                                    </option>


                                    <option value="IT">

                                        IT

                                    </option>

                                </select>

                            </div>


                            <!-- YEAR -->

                            <div class="mb-3">

                                <label class="form-label">

                                    Year

                                </label>


                                <select
                                    name="year"
                                    class="form-select"
                                    required
                                >

                                    <option value="">

                                        Select Year

                                    </option>


                                    <option value="I">

                                        I Year

                                    </option>


                                    <option value="II">

                                        II Year

                                    </option>


                                    <option value="III">

                                        III Year

                                    </option>


                                    <option value="IV">

                                        IV Year

                                    </option>

                                </select>

                            </div>


                            <!-- SECTION -->

                            <div class="mb-3">

                                <label class="form-label">

                                    Section

                                </label>


                                <select
                                    name="section"
                                    class="form-select"
                                    required
                                >

                                    <option value="">

                                        Select Section

                                    </option>


                                    <option value="A">

                                        A

                                    </option>


                                    <option value="B">

                                        B

                                    </option>


                                    <option value="C">

                                        C

                                    </option>


                                    <option value="D">

                                        D

                                    </option>

                                </select>

                            </div>


                            <!-- ADVISOR -->

                            <div class="mb-4">

                                <label class="form-label">

                                    Class Advisor

                                </label>


                                <select
                                    name="advisor_id"
                                    class="form-select"
                                    required
                                >

                                    <option value="">

                                        Select Class Advisor

                                    </option>


                                    <?php foreach ($advisors as $advisor): ?>

                                        <option
                                            value="<?= (int)$advisor['id'] ?>"
                                        >

                                            <?= htmlspecialchars($advisor['name']) ?>

                                            -

                                            <?= htmlspecialchars($advisor['username']) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>


                                <?php if (empty($advisors)): ?>

                                    <small class="text-danger d-block mt-2">

                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>

                                        No advisor accounts are available.

                                    </small>

                                <?php endif; ?>

                            </div>


                            <!-- SUBMIT -->

                            <button
                                type="submit"
                                class="btn assign-btn w-100"
                                <?= empty($advisors) ? 'disabled' : '' ?>
                            >

                                <i class="fa-solid fa-user-check me-2"></i>

                                Assign Advisor

                            </button>

                        </form>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 CURRENT ASSIGNMENTS
            ====================================== -->

            <div class="col-lg-7">

                <div class="card table-card">

                    <div class="card-body p-4">


                        <div class="d-flex justify-content-between align-items-center mb-4">

                            <h4 class="card-title mb-0">

                                <i class="fa-solid fa-list me-2 text-primary"></i>

                                Current Assignments

                            </h4>


                            <span class="badge bg-primary">

                                <?= count($assignments) ?> Assigned

                            </span>

                        </div>


                        <?php if (!empty($assignments)): ?>


                            <div class="table-responsive">

                                <table class="table table-hover align-middle">


                                    <thead>

                                        <tr>

                                            <th>#</th>

                                            <th>Class</th>

                                            <th>Advisor</th>

                                            <th>Actions</th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                        <?php $count = 1; ?>


                                        <?php foreach ($assignments as $assignment): ?>


                                            <tr>


                                                <!-- NUMBER -->

                                                <td>

                                                    <?= $count++ ?>

                                                </td>


                                                <!-- CLASS -->

                                                <td>

                                                    <span class="badge-class">

                                                        <?= htmlspecialchars($assignment['department']) ?>

                                                        -

                                                        <?= htmlspecialchars($assignment['year']) ?>

                                                        -

                                                        <?= htmlspecialchars($assignment['section']) ?>

                                                    </span>

                                                </td>


                                                <!-- ADVISOR -->

                                                <td>

                                                    <span class="badge-advisor">

                                                        <i class="fa-solid fa-user-tie me-1"></i>

                                                        <?= htmlspecialchars($assignment['advisor_name']) ?>

                                                    </span>

                                                    <br>

                                                    <small class="text-muted">

                                                        <?= htmlspecialchars($assignment['advisor_username']) ?>

                                                    </small>

                                                </td>


                                                <!-- ACTIONS -->

                                                <td>

                                                    <a
    href="hod_edit_advisor.php?id=<?php echo (int)$assignment['advisor_id']; ?>"
    class="btn btn-sm btn-primary action-btn"
>
    <i class="fa-solid fa-pen-to-square me-1"></i>
    Edit
</a>
<button
    type="button"
    class="btn btn-sm btn-danger"
    onclick="confirmDelete(<?= (int)$assignment['advisor_id'] ?>, '<?= htmlspecialchars(addslashes($assignment['advisor_name'])) ?>')"
>
    <i class="fa-solid fa-trash me-1"></i>
    Delete
</button>
                                                </td>


                                            </tr>


                                        <?php endforeach; ?>


                                    </tbody>

                                </table>

                            </div>


                        <?php else: ?>


                            <div class="empty-box">

                                <i
                                    class="fa-solid fa-user-tie fa-3x mb-3"
                                ></i>


                                <h5>

                                    No Class Advisors Assigned

                                </h5>


                                <p class="mb-0">

                                    Use the form to assign the first class advisor.

                                </p>

                            </div>


                        <?php endif; ?>


                    </div>

                </div>

            </div>


        </div>


    </div>

</div>


<?php include "footer.php"; ?>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

<script>

function confirmDelete(advisorId, advisorName) {

    Swal.fire({

        title: 'Delete Advisor?',

        html:
            'Are you sure you want to delete<br>' +
            '<strong>' + advisorName + '</strong>?' +
            '<br><br>' +
            '<small class="text-danger">' +
            'This action cannot be undone.' +
            '</small>',

        icon: 'warning',

        showCancelButton: true,

        confirmButtonColor: '#d33',

        cancelButtonColor: '#6c757d',

        confirmButtonText: 'Yes, Delete',

        cancelButtonText: 'Cancel',

        reverseButtons: true

    }).then((result) => {

        if (result.isConfirmed) {

            window.location.href =
                'hod_delete_advisor.php?id=' + advisorId;

        }

    });

}

</script>
</body>

</html>
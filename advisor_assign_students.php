<?php

session_start();
require_once "db.php";

/* =========================================================
   ACCESS CONTROL
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'advisor'
) {
    header("Location: index.php");
    exit;
}

$advisor_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET ADVISOR'S CLASS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        c.id AS class_id,
        c.department,
        c.year,
        c.section
    FROM class_advisors ca
    INNER JOIN classes c
        ON c.id = ca.class_id
    WHERE ca.advisor_id = ?
    LIMIT 1
");//finds advisor class//

$stmt->bind_param("i", $advisor_id);
$stmt->execute();

$result = $stmt->get_result();
$class = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   SESSION MESSAGES
========================================================= */

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';

unset(
    $_SESSION['success'],
    $_SESSION['error']
);


/* =========================================================
   GET STUDENTS
========================================================= */

$students = [];

if ($class) {

    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.name,
            u.username,
            u.email,

            CASE
                WHEN sc.id IS NOT NULL THEN 1
                ELSE 0
            END AS assigned

        FROM users u

        LEFT JOIN student_classes sc
            ON sc.student_id = u.id
           AND sc.class_id = ?

        WHERE u.role = 'student'

        ORDER BY u.name ASC
    ");

    $stmt->bind_param(
        "i",
        $class['class_id']
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();
}


/* =========================================================
   COUNT ASSIGNED STUDENTS
========================================================= */

$assigned_count = 0;

foreach ($students as $student) {

    if ((int)$student['assigned'] === 1) {
        $assigned_count++;
    }

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
        Assign Students | MKCE Leave Portal
    </title>


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


    <!-- SweetAlert -->

    <script
        src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
    ></script>


    <!-- DataTables -->

    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css"
    >


    <!-- Common CSS -->

    <link
        rel="stylesheet"
        href="style.css"
    >


    <style>

        .page-header {

            background:
                linear-gradient(
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

            opacity: .9;

        }


        .card-box {

            background: white;

            border-radius: 15px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, .06);

        }


        .class-badge {

            display: inline-block;

            padding: 9px 15px;

            background: #f8f9fc;

            border: 1px solid #e3e6f0;

            border-radius: 8px;

            margin-right: 8px;

            margin-bottom: 8px;

        }


        .student-checkbox {

            width: 20px;

            height: 20px;

            cursor: pointer;

        }


        .student-name {

            font-weight: 600;

        }


        .assigned-badge {

            background: #1cc88a;

            color: white;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

        }


        .not-assigned-badge {

            background: #858796;

            color: white;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

        }


        .select-all-box {

            background: #f8f9fc;

            border: 1px solid #e3e6f0;

            border-radius: 10px;

            padding: 12px 15px;

            margin-bottom: 15px;

        }


        .empty-state {

            text-align: center;

            padding: 60px 20px;

            color: #858796;

        }


        .empty-state i {

            font-size: 55px;

            margin-bottom: 20px;

        }

    </style>

</head>


<body>


<?php include "sidebar.php"; ?>

<?php include "topbar.php"; ?>


<div class="main-content">

    <div class="page-content">


        <!-- =================================================
             HEADER
        ================================================= -->

        <div class="page-header">

            <h2>

                <i class="fa-solid fa-users me-2"></i>

                Assign Students

            </h2>

            <p>

                Assign students to your class for attendance management.

            </p>

        </div>


        <?php if ($success): ?>

            <script>

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: <?= json_encode($success) ?>,
                    confirmButtonColor: '#4e73df'
                });

            </script>

        <?php endif; ?>


        <?php if ($error): ?>

            <script>

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: <?= json_encode($error) ?>,
                    confirmButtonColor: '#e74a3b'
                });

            </script>

        <?php endif; ?>


        <?php if (!$class): ?>


            <div class="card-box">

                <div class="empty-state">

                    <i class="fa-solid fa-users-slash"></i>

                    <h5>
                        No Class Assigned
                    </h5>

                    <p>
                        You are not currently assigned as a class advisor.
                    </p>

                </div>

            </div>


        <?php else: ?>


            <!-- =================================================
                 CLASS INFORMATION
            ================================================= -->

            <div class="card-box">

                <h5 class="mb-3">

                    <i class="fa-solid fa-school me-2"></i>

                    Your Class

                </h5>


                <span class="class-badge">

                    <strong>
                        Department:
                    </strong>

                    <?= htmlspecialchars(
                        $class['department']
                    ) ?>

                </span>


                <span class="class-badge">

                    <strong>
                        Year:
                    </strong>

                    <?= htmlspecialchars(
                        $class['year']
                    ) ?>

                </span>


                <span class="class-badge">

                    <strong>
                        Section:
                    </strong>

                    <?= htmlspecialchars(
                        $class['section']
                    ) ?>

                </span>


                <span class="class-badge">

                    <i class="fa-solid fa-users me-1"></i>

                    <strong>
                        Assigned:
                    </strong>

                    <?= $assigned_count ?>

                </span>

            </div>



            <!-- =================================================
                 STUDENTS
            ================================================= -->

            <div class="card-box">

                <form
                    action="advisor_assign_students_action.php"
                    method="POST"
                    id="studentAssignmentForm"
                >

                    <input
                        type="hidden"
                        name="class_id"
                        value="<?= (int)$class['class_id'] ?>"
                    >


                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h5 class="mb-0">

                            <i class="fa-solid fa-user-graduate me-2"></i>

                            Students

                        </h5>


                        <span class="badge bg-primary">

                            <?= count($students) ?>

                            Students

                        </span>

                    </div>


                    <!-- SELECT ALL -->

                    <div class="select-all-box">

                        <div class="form-check">

                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="selectAll"
                            >

                            <label
                                class="form-check-label fw-bold"
                                for="selectAll"
                            >

                                Select / Deselect All

                            </label>

                        </div>

                    </div>


                    <?php if (count($students) > 0): ?>


                        <div class="table-responsive">

                            <table
                                id="studentsTable"
                                class="table table-bordered table-hover align-middle"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            #
                                        </th>

                                        <th>
                                            Select
                                        </th>

                                        <th>
                                            Student
                                        </th>

                                        <th>
                                            Username
                                        </th>

                                        <th>
                                            Email
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php foreach (
                                        $students
                                        as $index => $student
                                    ): ?>

                                        <tr>

                                            <td>
                                                <?= $index + 1 ?>
                                            </td>


                                            <td>

                                                <input
                                                    type="checkbox"
                                                    class="form-check-input student-checkbox"
                                                    name="student_ids[]"
                                                    value="<?= (int)$student['id'] ?>"
                                                    <?= (
                                                        (int)$student['assigned'] === 1
                                                    )
                                                        ? 'checked'
                                                        : ''
                                                    ?>
                                                >

                                            </td>


                                            <td>

                                                <div class="student-name">

                                                    <?= htmlspecialchars(
                                                        $student['name']
                                                    ) ?>

                                                </div>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $student['username']
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $student['email'] ?? ''
                                                ) ?>

                                            </td>


                                            <td>

                                                <?php if (
                                                    (int)$student['assigned'] === 1
                                                ): ?>

                                                    <span
                                                        class="assigned-badge"
                                                    >

                                                        Assigned

                                                    </span>

                                                <?php else: ?>

                                                    <span
                                                        class="not-assigned-badge"
                                                    >

                                                        Not Assigned

                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                </tbody>

                            </table>

                        </div>


                        <div class="text-end mt-4">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i
                                    class="fa-solid fa-save me-2"
                                ></i>

                                Save Student Assignment

                            </button>

                        </div>


                    <?php else: ?>


                        <div class="empty-state">

                            <i
                                class="fa-solid fa-user-slash"
                            ></i>

                            <h5>
                                No Students Found
                            </h5>

                            <p>
                                There are no student accounts available.
                            </p>

                        </div>


                    <?php endif; ?>


                </form>

            </div>


        <?php endif; ?>


    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

const selectAll =
    document.getElementById('selectAll');

const studentCheckboxes =
    document.querySelectorAll(
        '.student-checkbox'
    );


if (selectAll) {

    selectAll.addEventListener(
        'change',
        function () {

            studentCheckboxes.forEach(
                function (checkbox) {

                    checkbox.checked =
                        selectAll.checked;

                }
            );

        }
    );

}


/* =========================================================
   UPDATE SELECT ALL STATE
========================================================= */

studentCheckboxes.forEach(
    function (checkbox) {

        checkbox.addEventListener(
            'change',
            function () {

                const checked =
                    document.querySelectorAll(
                        '.student-checkbox:checked'
                    ).length;

                selectAll.checked =
                    checked ===
                    studentCheckboxes.length;

            }
        );

    }
);


/* =========================================================
   FORM CONFIRMATION
========================================================= */

document
    .getElementById('studentAssignmentForm')
    ?.addEventListener(
        'submit',
        function (event) {

            event.preventDefault();

            Swal.fire({

                title: 'Save student assignment?',

                text:
                    'The selected students will be assigned to this class.',

                icon: 'question',

                showCancelButton: true,

                confirmButtonColor: '#4e73df',

                cancelButtonColor: '#858796',

                confirmButtonText: 'Yes, save'

            }).then(
                function (result) {

                    if (result.isConfirmed) {

                        event.target.submit();

                    }

                }
            );

        }
    );

</script>


</body>

</html>
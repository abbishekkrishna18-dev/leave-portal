<?php
session_start();
require_once 'db.php';

/* =========================
   ACCESS CONTROL
========================= */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'advisor') {
    header("Location: index.php");
    exit;
}

$advisor_id = (int)$_SESSION['user_id'];

/* =========================
   SESSION MESSAGES
========================= */

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

/* =========================
   GET ADVISOR'S CLASS
========================= */

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
");//advisor assigned to the class//

$stmt->bind_param("i", $advisor_id);
$stmt->execute();

$result = $stmt->get_result();
$class = $result->fetch_assoc();

$stmt->close();

/* =========================
   GET COURSES
========================= */

$courses = [];

if ($class) {

    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.course_code,
            c.course_name,
            c.department AS course_department,
            cf.faculty_id,
            u.name AS faculty_name
        FROM courses c
        LEFT JOIN course_faculty cf
            ON cf.course_id = c.id
        LEFT JOIN users u
            ON u.id = cf.faculty_id
           AND u.role = 'faculty'
        WHERE c.department = ?
          AND c.year = ?
          AND c.section = ?
        ORDER BY c.course_code ASC
    ");

    $stmt->bind_param(
        "sss",
        $class['department'],
        $class['year'],
        $class['section']
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

    $stmt->close();
}

/* =========================
   GET FACULTY
========================= */

$faculty = [];

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        department
    FROM users
    WHERE role = 'faculty'
    ORDER BY name ASC
");

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $faculty[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Allocate Faculty - MKCE Leave Portal</title>

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

    <!-- SweetAlert2 -->

    <script
        src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
    ></script>

    <!-- DataTables -->

    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css"
    >

    <link rel="stylesheet" href="style.css">


    <style>

        .page-header {

            background: #fff;

            padding: 20px 25px;

            border-radius: 10px;

            margin-bottom: 20px;

            box-shadow: 0 2px 8px rgba(0,0,0,0.05);

        }


        .page-header h2 {

            margin: 0;

            font-weight: 600;

        }


        .card-box {

            background: #fff;

            border-radius: 10px;

            padding: 25px;

            box-shadow: 0 2px 8px rgba(0,0,0,0.05);

        }


        .class-info {

            display: flex;

            gap: 15px;

            flex-wrap: wrap;

        }


        .class-badge {

            background: #f8f9fc;

            border: 1px solid #e3e6f0;

            padding: 10px 16px;

            border-radius: 8px;

        }


        .class-badge i {

            margin-right: 7px;

        }


        .course-code {

            font-weight: 600;

        }


        .faculty-badge {

            display: inline-block;

            padding: 6px 10px;

            border-radius: 6px;

            background: #e8f5e9;

            color: #198754;

            font-weight: 500;

        }


        .not-assigned {

            display: inline-block;

            padding: 6px 10px;

            border-radius: 6px;

            background: #fff3cd;

            color: #856404;

            font-weight: 500;

        }


        .empty-state {

            text-align: center;

            padding: 50px 20px;

        }


        .empty-state i {

            font-size: 50px;

            color: #adb5bd;

            margin-bottom: 15px;

        }


        .action-btns {

            white-space: nowrap;

        }

    </style>

</head>


<body>


<?php include 'sidebar.php'; ?>


<div class="main-content">


    <?php include 'topbar.php'; ?>


    <div class="container-fluid p-4">


        <!-- =========================
             PAGE HEADER
        ========================== -->

        <div class="page-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h2>

                        <i class="fa-solid fa-user-check me-2"></i>

                        Faculty Allocation

                    </h2>

                    <p class="text-muted mb-0 mt-1">

                        Allocate faculty members to your class courses

                    </p>

                </div>

            </div>

        </div>


        <!-- =========================
             SUCCESS
        ========================== -->

        <?php if (!empty($success)): ?>

            <div class="alert alert-success alert-dismissible fade show">

                <i class="fa-solid fa-circle-check me-2"></i>

                <?= htmlspecialchars($success) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?><!--success message-->


        <!-- =========================
             ERROR
        ========================== -->

        <?php if (!empty($error)): ?>

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


        <?php if (!$class): ?>


            <!-- =========================
                 NO CLASS
            ========================== -->

            <div class="card-box">

                <div class="empty-state">

                    <i class="fa-solid fa-users-slash"></i>

                    <h4>No Class Assigned</h4>

                    <p class="text-muted">

                        You have not been assigned as an advisor
                        to any class yet.

                    </p>

                </div>

            </div>


        <?php else: ?>


            <!-- =========================
                 CLASS INFORMATION
            ========================== -->

            <div class="card-box mb-4">

                <h5 class="mb-3">

                    <i class="fa-solid fa-chalkboard-user me-2"></i>

                    Assigned Class

                </h5>


                <div class="class-info">

                    <div class="class-badge">

                        <i class="fa-solid fa-building"></i>

                        <strong>Department:</strong>

                        <?= htmlspecialchars(
                            $class['department']
                        ) ?>

                    </div><!--input is from the sql query table-->


                    <div class="class-badge">

                        <i class="fa-solid fa-calendar"></i>

                        <strong>Year:</strong>

                        <?= htmlspecialchars(
                            $class['year']
                        ) ?>

                    </div>


                    <div class="class-badge">

                        <i class="fa-solid fa-layer-group"></i>

                        <strong>Section:</strong>

                        <?= htmlspecialchars(
                            $class['section']
                        ) ?>

                    </div>

                </div>

            </div>


            <!-- =========================
                 ALLOCATION TABLE
            ========================== -->

            <div class="card-box">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h5 class="mb-0">

                        <i class="fa-solid fa-book-open me-2"></i>

                        Course - Faculty Allocation

                    </h5>


                    <span class="badge bg-primary">

                        <?= count($courses) ?> Course(s)

                    </span>

                </div>


                <?php if (count($courses) > 0): ?>


                    <div class="table-responsive">

                        <table
                            id="allocationTable"
                            class="table table-bordered table-hover align-middle"
                        >

                            <thead class="table-light">

                                <tr>
                                    <th>Course Code</th>

                                    <th>Course Name</th>

                                    <th>Faculty</th>

                                    <th width="170">Action</th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $courses
                                as $index => $course
                            ): ?>


                                <tr>


                                    <td>

                                        <?= $index + 1 ?>

                                    </td>


                                    <td>

                                        <span class="course-code">

                                            <?= htmlspecialchars(
                                                $course['course_code']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $course['course_name']
                                        ) ?>

                                    </td>


                                    <td>


                                        <?php if (
                                            !empty(
                                                $course['faculty_id']
                                            )
                                        ): ?>

                                            <span class="faculty-badge">

                                                <i class="fa-solid fa-user-check me-1"></i>

                                                <?= htmlspecialchars(
                                                    $course['faculty_name']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="not-assigned">

                                                <i class="fa-solid fa-clock me-1"></i>

                                                Not Assigned

                                            </span>

                                        <?php endif; ?>


                                    </td>


                                    <td class="action-btns">


                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            onclick="openAllocationModal(
    <?= (int)$course['id'] ?>,
    '<?= htmlspecialchars(
        addslashes($course['course_code']),
        ENT_QUOTES
    ) ?>',
    '<?= htmlspecialchars(
        addslashes($course['course_department']),
        ENT_QUOTES
    ) ?>',
    <?= $course['faculty_id']
        ? (int)$course['faculty_id']
        : 0 ?>
)"
                                        >

                                            <?php if (
                                                !empty(
                                                    $course['faculty_id']
                                                )
                                            ): ?>

                                                <i class="fa-solid fa-pen-to-square me-1"></i>

                                                Change

                                            <?php else: ?>

                                                <i class="fa-solid fa-user-plus me-1"></i>

                                                Allocate

                                            <?php endif; ?>

                                        </button>


                                    </td>

                                </tr>


                            <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        <i class="fa-solid fa-book-open"></i>

                        <h5>No Courses Found</h5>

                        <p class="text-muted">

                            Add courses first from the My Courses page.

                        </p>


                        <a
                            href="advisor_courses.php"
                            class="btn btn-primary"
                        >

                            <i class="fa-solid fa-book me-2"></i>

                            Manage Courses

                        </a>

                    </div>


                <?php endif; ?>

            </div>


        <?php endif; ?>


    </div>

</div>


<!-- =========================
     ALLOCATION MODAL
========================= -->

<?php if ($class): ?>

<div
    class="modal fade"
    id="allocationModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">


            <form
                method="POST"
                action="advisor_allocate_course_action.php"
                id="allocationForm"
            >


                <input
                    type="hidden"
                    name="course_id"
                    id="allocation_course_id"
                >


                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa-solid fa-user-check me-2"></i>

                        Allocate Faculty

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">


                    <!-- COURSE -->

                    <div class="mb-3">

                        <label class="form-label">

                            Course

                        </label>


                        <input
                            type="text"
                            id="allocation_course_code"
                            class="form-control"
                            readonly
                        >

                    </div>


                    <!-- FACULTY -->

                    <div class="mb-3">

                        <label class="form-label">

                            Select Faculty

                            <span class="text-danger">*</span>

                        </label>


                        <select
                            name="faculty_id"
                            id="faculty_id"
                            class="form-select"
                            required
                        >

                            <option value="">

                                -- Select Faculty --

                            </option>


                           <?php foreach ($faculty as $member): ?>

    <option
        value="<?= (int)$member['id'] ?>"
        data-department="<?= htmlspecialchars(
            $member['department'] ?? '',
            ENT_QUOTES
        ) ?>"
    >
        <?= htmlspecialchars($member['name']) ?>
        —
        <?= htmlspecialchars(
            $member['department'] ?? 'Not Assigned'
        ) ?>
    </option>

<?php endforeach; ?>


                        </select>

                    </div>


                    <?php if (count($faculty) === 0): ?>

                        <div class="alert alert-warning">

                            <i class="fa-solid fa-triangle-exclamation me-2"></i>

                            No faculty accounts are available.

                            Please create a faculty account first.

                        </div>

                    <?php endif; ?>


                    <!-- CLASS -->

                    <div class="alert alert-info">

                        <i class="fa-solid fa-circle-info me-2"></i>

                        This allocation is for:

                        <strong>

                            <?= htmlspecialchars(
                                $class['department']
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $class['year']
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $class['section']
                            ) ?>

                        </strong>

                    </div>


                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        <?= count($faculty) === 0
                            ? 'disabled'
                            : '' ?>
                    >

                        <i class="fa-solid fa-user-check me-2"></i>

                        Save Allocation

                    </button>


                </div>


            </form>


        </div>

    </div>

</div>

<?php endif; ?>


<!-- =========================
     JAVASCRIPT
========================= -->

<script
    src="https://code.jquery.com/jquery-3.7.1.min.js"
></script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


<script
    src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"
></script>


<script
    src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"
></script>


<script>

/* =========================
   DATATABLE
========================= */

$(document).ready(function () {

    $('#allocationTable').DataTable({

        pageLength: 10,

        ordering: true,

        searching: true,

        lengthChange: false

    });

});


/* =========================
   OPEN ALLOCATION MODAL
========================= */

function openAllocationModal(
    courseId,
    courseCode,
    department,
    facultyId
) {

    document.getElementById(
        'allocation_course_id'
    ).value = courseId;

    document.getElementById(
        'allocation_course_code'
    ).value = courseCode;

    const facultySelect =
        document.getElementById('faculty_id');

    /*
     * Save all faculty options before filtering.
     * This allows the dropdown to be rebuilt
     * every time a different course is selected.
     */
    if (!window.allFacultyOptions) {

        window.allFacultyOptions =
            Array.from(
                facultySelect.options
            )
            .filter(function(option) {
                return option.value !== '';
            })
            .map(function(option) {
                return option.cloneNode(true);
            });
    }

    /*
     * Clear the dropdown.
     */
    facultySelect.innerHTML = '';

    /*
     * Add default option.
     */
    const defaultOption =
        document.createElement('option');

    defaultOption.value = '';
    defaultOption.textContent =
        '-- Select Faculty --';

    facultySelect.appendChild(
        defaultOption
    );

    /*
     * Add ONLY faculty belonging
     * to the selected course department.
     */
    window.allFacultyOptions.forEach(
        function(option) {

            const facultyDepartment =
                (
                    option.getAttribute(
                        'data-department'
                    ) || ''
                )
                .trim()
                .toUpperCase();

            const courseDepartment =
                (
                    department || ''
                )
                .trim()
                .toUpperCase();

            if (
                facultyDepartment ===
                courseDepartment
            ) {
                facultySelect.appendChild(
                    option.cloneNode(true)
                );
            }
        }
    );

    /*
     * Select the already allocated faculty,
     * if there is one.
     */
    if (facultyId > 0) {

        const selectedOption =
            facultySelect.querySelector(
                'option[value="' +
                facultyId +
                '"]'
            );

        if (selectedOption) {
            facultySelect.value =
                facultyId;
        }
    }//already selected one will be there like a placeholder in the dropdown tab//

    /*
     * Open modal.
     */
    const modalElement =
        document.getElementById(
            'allocationModal'
        );

    const modal =
        bootstrap.Modal.getOrCreateInstance(
            modalElement
        );

    modal.show();
}
</script>


</body>

</html>
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
   GET ADVISOR'S ASSIGNED CLASS
========================= */

$class = null;

$stmt = $conn->prepare("
    SELECT 
        ca.id AS assignment_id,
        c.id AS class_id,
        c.department,
        c.year,
        c.section
    FROM class_advisors ca
    INNER JOIN classes c ON c.id = ca.class_id
    WHERE ca.advisor_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $advisor_id);
$stmt->execute();

$result = $stmt->get_result();
$class = $result->fetch_assoc();

$stmt->close();

/* =========================
   GET COURSES + FACULTY
========================= */

$courses = [];

if ($class) {

    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.course_code,
            c.course_name,
            c.department,
            c.year,
            c.section,
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
   GET ALL FACULTY
========================= */

$faculty_list = [];

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
    $faculty_list[] = $row;
}

$stmt->close();

/* =========================
   SESSION MESSAGES
========================= */

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Courses - MKCE Leave Portal</title>

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        .class-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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

        .card-box {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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

        .course-code {
            font-weight: 600;
        }

        .action-btns {
            white-space: nowrap;
        }

        .faculty-name {
            font-weight: 500;
        }

        .not-assigned {
            color: #6c757d;
            font-style: italic;
        }

        .allocation-btn {
            margin-top: 4px;
        }

        .modal-course-info {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            padding: 12px 15px;
        }

    </style>

</head>

<body>

<?php
include 'sidebar.php';
?>

<div class="main-content">

<?php
include 'topbar.php';
?>

<div class="container-fluid p-4">

    <!-- =========================
         PAGE HEADER
    ========================= -->

    <div class="page-header d-flex justify-content-between align-items-center">

        <div>

            <h2>
                <i class="fa-solid fa-book-open me-2"></i>
                My Courses
            </h2>

            <p class="text-muted mb-0 mt-1">
                Manage courses and faculty allocation for your assigned class
            </p>

        </div>

        <?php if ($class): ?>

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#addCourseModal"
            >
                <i class="fa-solid fa-plus me-2"></i>
                Add Course
            </button>

        <?php endif; ?>

    </div>


    <!-- =========================
         SUCCESS MESSAGE
    ========================= -->

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

    <?php endif; ?>


    <!-- =========================
         ERROR MESSAGE
    ========================= -->

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
             NO CLASS ASSIGNED
        ========================= -->

        <div class="card-box">

            <div class="empty-state">

                <i class="fa-solid fa-users-slash"></i>

                <h4>No Class Assigned</h4>

                <p class="text-muted">

                    You have not been assigned as an advisor to any class yet.

                    Please contact the HOD.

                </p>

            </div>

        </div>

    <?php else: ?>

        <!-- =========================
             ASSIGNED CLASS
        ========================= -->

        <div class="class-card">

            <h5 class="mb-3">

                <i class="fa-solid fa-chalkboard-user me-2"></i>

                Assigned Class

            </h5>

            <div class="class-info">

                <div class="class-badge">

                    <i class="fa-solid fa-building"></i>

                    <strong>Department:</strong>

                    <?= htmlspecialchars($class['department']) ?>

                </div>

                <div class="class-badge">

                    <i class="fa-solid fa-calendar"></i>

                    <strong>Year:</strong>

                    <?= htmlspecialchars($class['year']) ?>

                </div>

                <div class="class-badge">

                    <i class="fa-solid fa-layer-group"></i>

                    <strong>Section:</strong>

                    <?= htmlspecialchars($class['section']) ?>

                </div>

            </div>

        </div>


        <!-- =========================
             COURSE TABLE
        ========================= -->

        <div class="card-box">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="mb-0">

                    <i class="fa-solid fa-book me-2"></i>

                    Course List

                </h5>

                <span class="badge bg-primary">

                    <?= count($courses) ?> Course(s)

                </span>

            </div>


            <?php if (count($courses) > 0): ?>

                <div class="table-responsive">

                    <table
                        id="coursesTable"
                        class="table table-bordered table-hover align-middle"
                    >

                        <thead class="table-light">

                            <tr>

                                <th>#</th>

                                <th>Course Code</th>

                                <th>Course Name</th>

                                <th>Department</th>

                                <th>Year</th>

                                <th>Section</th>

                                <!-- NEW -->
                                <th>Faculty</th>

                                <!-- ACTIONS -->
                                <th width="280">Actions</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($courses as $index => $course): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>

                                    <span class="course-code">

                                        <?= htmlspecialchars($course['course_code']) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= htmlspecialchars($course['course_name']) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars($course['department']) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars($course['year']) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars($course['section']) ?>

                                </td>


                                <!-- =========================
                                     FACULTY COLUMN
                                ========================= -->

                                <td>

                                    <?php if (!empty($course['faculty_id'])): ?>

                                        <span class="badge bg-success">

                                            <i class="fa-solid fa-user-check me-1"></i>

                                            <?= htmlspecialchars($course['faculty_name']) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="not-assigned">

                                            <i class="fa-solid fa-user-xmark me-1"></i>

                                            Not Assigned

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- =========================
                                     ACTIONS
                                ========================= -->

                                <td class="action-btns">

                                    <!-- EDIT -->

                                    <a
                                        href="advisor_edit_course.php?id=<?= (int)$course['id'] ?>"
                                        class="btn btn-sm btn-primary mb-1"
                                    >

                                        <i class="fa-solid fa-pen-to-square"></i>

                                        Edit

                                    </a>


                                    <!-- DELETE -->

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger mb-1"
                                        onclick="confirmDelete(
                                            <?= (int)$course['id'] ?>,
                                            <?= htmlspecialchars(
                                                json_encode($course['course_code']),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        )"
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                        Delete

                                    </button>


                                    <!-- =========================
                                         ALLOCATE / CHANGE FACULTY
                                    ========================= -->

                                    <?php if (!empty($course['faculty_id'])): ?>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-warning mb-1"
                                            onclick="openFacultyModal(
    <?= (int)$course['id'] ?>,
    <?= htmlspecialchars(json_encode($course['course_code']), ENT_QUOTES, 'UTF-8') ?>,
    <?= htmlspecialchars(json_encode($course['department']), ENT_QUOTES, 'UTF-8') ?>,
    <?= (int)$course['faculty_id'] ?>
)"
                                        >

                                            <i class="fa-solid fa-arrows-rotate"></i>

                                            Change Faculty

                                        </button>

                                    <?php else: ?>

                                       <button
    type="button"
    id="faculty-btn-<?= (int)$course['id'] ?>"
    class="btn btn-sm btn-success mb-1"
    onclick="openFacultyModal(
        <?= (int)$course['id'] ?>,
        <?= htmlspecialchars(
            json_encode($course['course_code']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($course['department']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        0
    )"
>
    <i class="fa-solid fa-user-plus"></i>
    Allocate Faculty
</button> 

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <i class="fa-solid fa-book-open"></i>

                    <h5>No Courses Added</h5>

                    <p class="text-muted">

                        You have not added any courses for this class yet.

                    </p>

                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#addCourseModal"
                    >

                        <i class="fa-solid fa-plus me-2"></i>

                        Add Your First Course

                    </button>

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

</div>


<!-- =========================================================
     ADD COURSE MODAL
========================================================= -->

<?php if ($class): ?>

<div
    class="modal fade"
    id="addCourseModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">

            <form
                method="POST"
                action="advisor_course_action.php"
            >

                <input
                    type="hidden"
                    name="action"
                    value="add"
                >

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa-solid fa-plus me-2"></i>

                        Add Course

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <!-- COURSE CODE -->

                    <div class="mb-3">

                        <label class="form-label">

                            Course Code

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="course_code"
                            class="form-control"
                            placeholder="Example: CS301"
                            maxlength="50"
                            required
                        >

                    </div>


                    <!-- COURSE NAME -->

                    <div class="mb-3">

                        <label class="form-label">

                            Course Name

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="course_name"
                            class="form-control"
                            placeholder="Example: Database Management Systems"
                            maxlength="150"
                            required
                        >

                    </div>


                    <!-- CLASS INFORMATION -->

                    <div class="alert alert-info">

                        <i class="fa-solid fa-circle-info me-2"></i>

                        This course will automatically be added to:

                        <br><br>

                        <strong>

                            <?= htmlspecialchars($class['department']) ?>

                            -

                            <?= htmlspecialchars($class['year']) ?>

                            -

                            <?= htmlspecialchars($class['section']) ?>

                        </strong>

                    </div>


                    <!-- HIDDEN CLASS VALUES -->

                    <input
                        type="hidden"
                        name="department"
                        value="<?= htmlspecialchars($class['department']) ?>"
                    >

                    <input
                        type="hidden"
                        name="year"
                        value="<?= htmlspecialchars($class['year']) ?>"
                    >

                    <input
                        type="hidden"
                        name="section"
                        value="<?= htmlspecialchars($class['section']) ?>"
                    >

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
                    >

                        <i class="fa-solid fa-plus me-2"></i>

                        Add Course

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     ALLOCATE / CHANGE FACULTY MODAL
========================================================= -->

<?php if ($class): ?>

<div
    class="modal fade"
    id="facultyModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">

            <form
                method="POST"
                action="advisor_allocate_course_action.php"
            >

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa-solid fa-chalkboard-user me-2"></i>

                        Allocate Faculty

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <!-- COURSE ID -->

                    <input
                        type="hidden"
                        name="course_id"
                        id="allocation_course_id"
                    >


                    <!-- COURSE INFO -->

                    <div class="modal-course-info mb-3">

                        <div class="mb-1">

                            <strong>Course:</strong>

                            <span id="allocation_course_code"></span>

                        </div>

                        <div>

                            <strong>Class:</strong>

                            <?= htmlspecialchars($class['department']) ?>

                            -

                            <?= htmlspecialchars($class['year']) ?>

                            -

                            <?= htmlspecialchars($class['section']) ?>

                        </div>

                    </div>


                    <!-- FACULTY -->

                    <div class="mb-3">

                        <label class="form-label">

                            Select Faculty

                            <span class="text-danger">*</span>

                        </label>


                        <?php if (count($faculty_list) > 0): ?>

                            <select
                                name="faculty_id"
                                id="allocation_faculty_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    -- Select Faculty --
                                </option>

                                <?php foreach ($faculty_list as $faculty): ?>
    <option
        value="<?= (int)$faculty['id'] ?>"
        data-department="<?= htmlspecialchars(
            $faculty['department'] ?? '',
            ENT_QUOTES
        ) ?>"
    >
        <?= htmlspecialchars($faculty['name']) ?>
        —
        <?= htmlspecialchars(
            $faculty['department'] ?? 'Not Assigned'
        ) ?>
    </option>
<?php endforeach; ?>

                            </select>

                        <?php else: ?>

                            <div class="alert alert-warning">

                                <i class="fa-solid fa-triangle-exclamation me-2"></i>

                                No faculty accounts are available.

                                <br><br>

                                Please add a faculty member first.

                                <br><br>

                            </div>

                        <?php endif; ?>

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


                    <?php if (count($faculty_list) > 0): ?>

                        <button
                            type="submit"
                            class="btn btn-success"
                        >

                            <i class="fa-solid fa-check me-2"></i>

                            Save Allocation

                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>


<script>

/* =========================
   DATATABLE
========================= */

$(document).ready(function () {

    $('#coursesTable').DataTable({
        pageLength: 10,
        ordering: true,
        searching: true,
        lengthChange: false
    });

});


/* =========================
   OPEN FACULTY MODAL
========================= */

function openFacultyModal(
    courseId,
    courseCode,
    department,
    currentFacultyId
) {

    const courseIdInput =
        document.getElementById('allocation_course_id');

    const courseCodeElement =
        document.getElementById('allocation_course_code');

    const facultySelect =
        document.getElementById('allocation_faculty_id');


    if (
        !courseIdInput ||
        !courseCodeElement ||
        !facultySelect
    ) {
        console.error(
            'Faculty allocation modal elements not found.'
        );
        return;
    }


    // Set course information
    courseIdInput.value = courseId;

    courseCodeElement.textContent = courseCode;


    // Filter faculty by department
    Array.from(facultySelect.options).forEach(
        function(option) {

            // Always show default option
            if (option.value === '') {
                option.hidden = false;
                return;
            }


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


            option.hidden =
                facultyDepartment !== courseDepartment;
        }
    );


    // Select already assigned faculty
    facultySelect.value =
        currentFacultyId > 0
            ? currentFacultyId
            : '';


    // Update title
    const modalTitle =
        document.getElementById(
            'facultyModalTitle'
        );

    if (modalTitle) {

        modalTitle.textContent =
            currentFacultyId > 0
                ? 'Change Faculty'
                : 'Allocate Faculty';
    }


    // Open modal
    const modalElement =
        document.getElementById(
            'facultyModal'
        );

    if (!modalElement) {
        console.error(
            'Faculty modal not found.'
        );
        return;
    }


    const modal =
        bootstrap.Modal.getOrCreateInstance(
            modalElement
        );

    modal.show();
}

/* =========================
   SWEETALERT DELETE
========================= */

function confirmDelete(courseId, courseCode) {

    Swal.fire({

        title: 'Delete Course?',

        html:
            'Are you sure you want to delete<br>' +
            '<strong>' +
            courseCode +
            '</strong>?' +
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
                'advisor_course_action.php?action=delete&id=' +
                courseId;

        }

    });

}

</script>

</body>

</html>
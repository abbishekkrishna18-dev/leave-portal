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
// ONLY POST REQUESTS
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: hod_class_advisor.php");
    exit();
}


// --------------------------------------------------
// GET FORM DATA
// --------------------------------------------------

$department = isset($_POST['department'])
    ? trim($_POST['department'])
    : '';

$year = isset($_POST['year'])
    ? trim($_POST['year'])
    : '';

$section = isset($_POST['section'])
    ? trim($_POST['section'])
    : '';

$advisor_id = isset($_POST['advisor_id'])
    ? (int)$_POST['advisor_id']
    : 0;


// --------------------------------------------------
// VALIDATE BASIC INPUT
// --------------------------------------------------

if (
    $department === '' ||
    $year === '' ||
    $section === '' ||
    $advisor_id <= 0
) {
    $_SESSION['error'] = "Please fill all fields.";
    header("Location: hod_class_advisor.php");
    exit();
}


// --------------------------------------------------
// ALLOWED VALUES
// --------------------------------------------------

$allowed_departments = [
    'CSE',
    'ECE',
    'EEE',
    'MECH',
    'CIVIL',
    'IT'
];

$allowed_years = [
    'I',
    'II',
    'III',
    'IV'
];

$allowed_sections = [
    'A',
    'B',
    'C',
    'D'
];


if (!in_array($department, $allowed_departments, true)) {
    $_SESSION['error'] = "Invalid department selected.";
    header("Location: hod_class_advisor.php");
    exit();
}

if (!in_array($year, $allowed_years, true)) {
    $_SESSION['error'] = "Invalid year selected.";
    header("Location: hod_class_advisor.php");
    exit();
}

if (!in_array($section, $allowed_sections, true)) {
    $_SESSION['error'] = "Invalid section selected.";
    header("Location: hod_class_advisor.php");
    exit();
}


// --------------------------------------------------
// CHECK SELECTED USER IS ACTUALLY AN ADVISOR
// --------------------------------------------------

$sql = "
    SELECT id, name
    FROM users
    WHERE id = ?
      AND role = 'advisor'
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['error'] = "Database error while checking advisor.";
    header("Location: hod_class_advisor.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $advisor_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$advisor = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$advisor) {
    $_SESSION['error'] = "Invalid advisor selected.";
    header("Location: hod_class_advisor.php");
    exit();
}


// --------------------------------------------------
// CHECK WHETHER CLASS ALREADY EXISTS
// --------------------------------------------------

$sql = "
    SELECT id
    FROM classes
    WHERE department = ?
      AND year = ?
      AND section = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['error'] = "Database error while checking class.";
    header("Location: hod_class_advisor.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "sss",
    $department,
    $year,
    $section
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$class = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// --------------------------------------------------
// CREATE CLASS IF IT DOES NOT EXIST
// --------------------------------------------------

if (!$class) {

    $sql = "
        INSERT INTO classes
        (
            department,
            year,
            section
        )
        VALUES (?, ?, ?)
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        $_SESSION['error'] = "Unable to create class.";
        header("Location: hod_class_advisor.php");
        exit();
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $department,
        $year,
        $section
    );

    if (!mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        $_SESSION['error'] = "Failed to create class.";
        header("Location: hod_class_advisor.php");
        exit();
    }

    $class_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

} else {

    $class_id = (int)$class['id'];
}


// --------------------------------------------------
// CHECK WHETHER THIS CLASS ALREADY HAS AN ADVISOR
// --------------------------------------------------

$sql = "
    SELECT
        ca.id,
        ca.advisor_id,
        u.name AS advisor_name
    FROM class_advisors ca
    INNER JOIN users u
        ON u.id = ca.advisor_id
    WHERE ca.class_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['error'] = "Database error while checking assignment.";
    header("Location: hod_class_advisor.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $class_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$existing_assignment = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// --------------------------------------------------
// IF CLASS ALREADY HAS SAME ADVISOR
// --------------------------------------------------

if (
    $existing_assignment &&
    (int)$existing_assignment['advisor_id'] === $advisor_id
) {

    $_SESSION['error'] =
        "This advisor is already assigned to this class.";

    header("Location: hod_class_advisor.php");
    exit();
}


// --------------------------------------------------
// IF CLASS ALREADY HAS DIFFERENT ADVISOR
// --------------------------------------------------

if ($existing_assignment) {

    $_SESSION['error'] =
        "This class is already assigned to advisor: " .
        $existing_assignment['advisor_name'];

    header("Location: hod_class_advisor.php");
    exit();
}


// --------------------------------------------------
// CHECK WHETHER ADVISOR IS ALREADY ASSIGNED TO ANOTHER CLASS
// --------------------------------------------------

$sql = "
    SELECT
        ca.id,
        c.department,
        c.year,
        c.section
    FROM class_advisors ca
    INNER JOIN classes c
        ON c.id = ca.class_id
    WHERE ca.advisor_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['error'] = "Database error while checking advisor assignment.";
    header("Location: hod_class_advisor.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $advisor_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$advisor_existing_class = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// --------------------------------------------------
// PREVENT ONE ADVISOR FROM HAVING MULTIPLE CLASSES
// --------------------------------------------------

if ($advisor_existing_class) {

    $_SESSION['error'] =
        "This advisor is already assigned to " .
        $advisor_existing_class['department'] . " - " .
        $advisor_existing_class['year'] . " - " .
        $advisor_existing_class['section'] . ".";

    header("Location: hod_class_advisor.php");
    exit();
}


// --------------------------------------------------
// CREATE CLASS ADVISOR ASSIGNMENT
// --------------------------------------------------

$sql = "
    INSERT INTO class_advisors
    (
        class_id,
        advisor_id
    )
    VALUES (?, ?)
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['error'] = "Unable to assign class advisor.";
    header("Location: hod_class_advisor.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $class_id,
    $advisor_id
);

if (mysqli_stmt_execute($stmt)) {

    $_SESSION['success'] =
        "Class Advisor assigned successfully to " .
        $department . " - " .
        $year . " - " .
        $section . ".";

} else {

    $_SESSION['error'] =
        "Failed to assign Class Advisor.";
}

mysqli_stmt_close($stmt);


// --------------------------------------------------
// RETURN TO HOD PAGE
// --------------------------------------------------

header("Location: hod_class_advisor.php");
exit();

?>
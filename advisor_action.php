<?php
session_start();
include "db.php";
/* =====================================================
   CHECK ADVISOR LOGIN
===================================================== */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "advisor"
) {
    header("Location: index.php");
    exit();
}

$advisor_id = (int)$_SESSION["user_id"];


/* =====================================================
   CHECK REQUEST METHOD
===================================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: advisor_dashboard.php");
    exit();
}


/* =====================================================
   GET POST DATA
===================================================== */

$leave_id = $_POST["leave_id"] ?? "";
$action = $_POST["action"] ?? "";


/* =====================================================
   VALIDATE APPLICATION ID
===================================================== */

if (
    !is_numeric($leave_id) ||
    (int)$leave_id <= 0
) {

    $_SESSION["action_error"] =
        "Invalid application ID.";

    header("Location: advisor_dashboard.php");
    exit();
}

$leave_id = (int)$leave_id;


/* =====================================================
   VALIDATE ACTION
===================================================== */

if (
    $action !== "approve" &&
    $action !== "reject"
) {

    $_SESSION["action_error"] =
        "Invalid advisor action.";

    header("Location: advisor_dashboard.php");
    exit();
}


/* =====================================================
   CHECK APPLICATION
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        student_id,
        advisor_status,
        hod_status,
        od_status
     FROM leaves
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $leave_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$leave = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =====================================================
   APPLICATION NOT FOUND
===================================================== */

if (!$leave) {

    $_SESSION["action_error"] =
        "Leave application not found.";

    header("Location: advisor_dashboard.php");
    exit();
}


/* =====================================================
   CHECK CURRENT STATUS
===================================================== */

if ($leave["advisor_status"] !== "Pending") {

    $_SESSION["action_error"] =
        "This application has already been processed.";

    header("Location: advisor_dashboard.php");
    exit();
}


/* =====================================================
   CHECK BLOCKED APPLICATION
===================================================== */

if (
    isset($leave["od_status"]) &&
    $leave["od_status"] === "Blocked"
) {

    $_SESSION["action_error"] =
        "This application is blocked and cannot be processed.";

    header("Location: advisor_dashboard.php");
    exit();
}


/* =====================================================
   DETERMINE NEW STATUS
===================================================== */

if ($action === "approve") {

    $new_status = "Approved";

} else {

    $new_status = "Rejected";
}


/* =====================================================
   UPDATE ADVISOR STATUS
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "UPDATE leaves
     SET advisor_status = ?
     WHERE id = ?
       AND advisor_status = 'Pending'"
);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $new_status,
    $leave_id
);

$success = mysqli_stmt_execute($stmt);

$affected_rows =
    mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);


/* =====================================================
   CHECK UPDATE
===================================================== */

if (
    !$success ||
    $affected_rows <= 0
) {

    $_SESSION["action_error"] =
        "Unable to process this application. It may have already been processed.";

    header("Location: advisor_dashboard.php");
    exit();
}


/* =====================================================
   SUCCESS MESSAGE
===================================================== */

if ($new_status === "Approved") {

    $_SESSION["action_success"] =
        "Leave application approved successfully. It has been forwarded to the HOD.";

} else {

    $_SESSION["action_success"] =
        "Leave application rejected successfully.";
}


/* =====================================================
   REDIRECT
===================================================== */

header(
    "Location: advisor_dashboard.php"
);

exit();

?>
<?php

session_start();

require_once "db.php";


/* =========================================================
   HOD LOGIN CHECK
========================================================= */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {

    header("Location: index.php");

    exit();

}


/* =========================================================
   GET ADVISOR ID
========================================================= */

$advisor_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($advisor_id <= 0) {

    $_SESSION['error'] = "Invalid advisor ID.";

    header("Location: hod_class_advisor.php");

    exit();

}


/* =========================================================
   GET ADVISOR
========================================================= */

$stmt = $conn->prepare("
    SELECT id, name, username
    FROM users
    WHERE id = ?
    AND role = 'advisor'
    LIMIT 1
");

$stmt->bind_param("i", $advisor_id);

$stmt->execute();

$result = $stmt->get_result();

$advisor = $result->fetch_assoc();

$stmt->close();


if (!$advisor) {

    $_SESSION['error'] = "Advisor account not found.";

    header("Location: hod_class_advisor.php");

    exit();

}


/* =========================================================
   START TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       DELETE NOTIFICATIONS

       notifications.advisor_id has a foreign key,
       so notifications must be removed first.
    ===================================================== */

    $stmt = $conn->prepare("
        DELETE FROM notifications
        WHERE advisor_id = ?
    ");

    if (!$stmt) {

        throw new Exception(
            "Unable to prepare notification deletion."
        );

    }

    $stmt->bind_param("i", $advisor_id);

    if (!$stmt->execute()) {

        throw new Exception(
            "Unable to delete advisor notifications."
        );

    }

    $stmt->close();


    /* =====================================================
       DELETE ADVISOR

       class_advisors.advisor_id uses ON DELETE CASCADE,
       so the class assignment will automatically be removed.
    ===================================================== */

    $stmt = $conn->prepare("
        DELETE FROM users
        WHERE id = ?
        AND role = 'advisor'
    ");

    if (!$stmt) {

        throw new Exception(
            "Unable to prepare advisor deletion."
        );

    }

    $stmt->bind_param("i", $advisor_id);

    if (!$stmt->execute()) {

        throw new Exception(
            "Unable to delete advisor account."
        );

    }


    if ($stmt->affected_rows <= 0) {

        $stmt->close();

        throw new Exception(
            "Advisor account could not be deleted."
        );

    }

    $stmt->close();


    /* =====================================================
       COMMIT
    ===================================================== */

    $conn->commit();


    $_SESSION['success'] =
        "Advisor '" .
        $advisor['name'] .
        "' has been deleted successfully.";


} catch (Exception $e) {


    /* =====================================================
       ROLLBACK
    ===================================================== */

    $conn->rollback();


    $_SESSION['error'] =
        "Advisor could not be deleted. " .
        $e->getMessage();

}


/* =========================================================
   REDIRECT
========================================================= */

header("Location: hod_class_advisor.php");

exit();

?>
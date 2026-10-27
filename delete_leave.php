<?php

session_start();

require_once "db.php";


/* =========================================================
   STUDENT LOGIN CHECK
   ========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: index.php");
    exit;
}


$student_id = (int) $_SESSION["user_id"];

$leave_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($leave_id <= 0) {

    header("Location: student_dashboard.php");
    exit;

}


/* =========================================================
   GET APPLICATION
   ========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, student_id, advisor_status, proof
     FROM leaves
     WHERE id = ?
     AND student_id = ?
     LIMIT 1"
);


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $leave_id,
    $student_id
);


mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$leave =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   APPLICATION NOT FOUND
   ========================================================= */

if (!$leave) {

    header(
        "Location: student_dashboard.php"
    );

    exit;

}


/* =========================================================
   EDIT/DELETE PERMISSION
   ========================================================= */

if (
    strtolower(
        trim(
            $leave["advisor_status"]
        )
    ) !== "pending"
) {

    $_SESSION["delete_error"] =
        "This application cannot be deleted because the Advisor has already acted on it.";

    header(
        "Location: student_dashboard.php"
    );

    exit;

}


/* =========================================================
   DELETE APPLICATION
   ========================================================= */

$delete_stmt = mysqli_prepare(
    $conn,
    "DELETE FROM leaves
     WHERE id = ?
     AND student_id = ?
     AND advisor_status = 'Pending'"
);


mysqli_stmt_bind_param(
    $delete_stmt,
    "ii",
    $leave_id,
    $student_id
);


if (
    mysqli_stmt_execute(
        $delete_stmt
    )
) {

    /*
     * Check whether a row was actually deleted.
     */

    if (
        mysqli_stmt_affected_rows(
            $delete_stmt
        ) > 0
    ) {

        /* =============================================
           DELETE PROOF FILE
           ============================================= */

        if (
            !empty(
                $leave["proof"]
            )
        ) {

            $proof_file =
                __DIR__
                . DIRECTORY_SEPARATOR
                . "uploads"
                . DIRECTORY_SEPARATOR
                . basename(
                    $leave["proof"]
                );


            if (
                file_exists(
                    $proof_file
                )
            ) {

                unlink(
                    $proof_file
                );

            }

        }


        $_SESSION["delete_success"] =
            "Application deleted successfully.";

    }

    else {

        $_SESSION["delete_error"] =
            "The application could not be deleted. The Advisor may have already acted on it.";

    }

}

else {

    $_SESSION["delete_error"] =
        "Database error: "
        .
        mysqli_stmt_error(
            $delete_stmt
        );

}


mysqli_stmt_close(
    $delete_stmt
);


/* =========================================================
   REDIRECT
   ========================================================= */

header(
    "Location: student_dashboard.php"
);

exit;

?>
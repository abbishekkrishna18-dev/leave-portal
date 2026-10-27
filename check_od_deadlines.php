<?php

session_start();
include "db.php";


/*
=========================================================
CHECK OD DEADLINES

Purpose:
- OD must be approved by the advisor within 2 days.
- If the approval deadline has passed:
    advisor_status = Blocked
    od_status      = Blocked
- A notification is created for advisors.
=========================================================
*/


/* =========================================================
   FIND EXPIRED OD APPLICATIONS
========================================================= */

$sql = "SELECT
            id,
            student_id,
            leave_type,
            approval_deadline,
            advisor_status,
            od_status
        FROM leaves
        WHERE LOWER(TRIM(leave_type)) IN (
            'od',
            'on duty',
            'on-duty',
            'on_duty'
        )
        AND advisor_status = 'Pending'
        AND approval_deadline IS NOT NULL
        AND approval_deadline < NOW()
        AND (od_status IS NULL OR od_status <> 'Blocked')";


$result = mysqli_query($conn, $sql);


if (!$result) {

    die(
        "Unable to check OD deadlines: "
        . mysqli_error($conn)
    );

}


/* =========================================================
   PREPARE UPDATE
========================================================= */

$update_stmt = mysqli_prepare(
    $conn,
    "UPDATE leaves
     SET od_status = 'Blocked',
         advisor_status = 'Blocked'
     WHERE id = ?
       AND advisor_status = 'Pending'"
);


if (!$update_stmt) {

    die(
        "Unable to prepare deadline update: "
        . mysqli_error($conn)
    );

}


/* =========================================================
   PREPARE ADVISOR LIST
========================================================= */

$advisor_result = mysqli_query(
    $conn,
    "SELECT id
     FROM users
     WHERE role = 'advisor'"
);


if (!$advisor_result) {

    mysqli_stmt_close($update_stmt);

    die(
        "Unable to fetch advisors: "
        . mysqli_error($conn)
    );

}


/* =========================================================
   STORE ADVISOR IDS
========================================================= */

$advisor_ids = [];

while ($advisor = mysqli_fetch_assoc($advisor_result)) {

    $advisor_ids[] = (int)$advisor["id"];

}


/* =========================================================
   PREPARE NOTIFICATION
========================================================= */

$notification_stmt = mysqli_prepare(
    $conn,
    "INSERT INTO notifications
        (advisor_id, leave_id, message)
     VALUES
        (?, ?, ?)"
);


if (!$notification_stmt) {

    mysqli_stmt_close($update_stmt);

    die(
        "Unable to prepare notification: "
        . mysqli_error($conn)
    );

}


/* =========================================================
   PROCESS EXPIRED APPLICATIONS
========================================================= */

$blocked_count = 0;
$notification_count = 0;


while ($leave = mysqli_fetch_assoc($result)) {

    $leave_id = (int)$leave["id"];

    /* -----------------------------------------------
       BLOCK APPLICATION
    ------------------------------------------------ */

    mysqli_stmt_bind_param(
        $update_stmt,
        "i",
        $leave_id
    );

    $updated = mysqli_stmt_execute($update_stmt);

    $affected = mysqli_stmt_affected_rows(
        $update_stmt
    );


    if (!$updated || $affected <= 0) {

        continue;

    }


    $blocked_count++;


    /* -----------------------------------------------
       CREATE NOTIFICATION MESSAGE
    ------------------------------------------------ */

    $message =
        "OD application #"
        . $leave_id
        . " has been automatically blocked because "
        . "the advisor approval deadline has expired.";


    /* -----------------------------------------------
       NOTIFY ALL ADVISORS
    ------------------------------------------------ */

    foreach ($advisor_ids as $advisor_id) {

        mysqli_stmt_bind_param(
            $notification_stmt,
            "iis",
            $advisor_id,
            $leave_id,
            $message
        );

        if (mysqli_stmt_execute($notification_stmt)) {

            $notification_count++;

        }

    }

}


/* =========================================================
   CLOSE STATEMENTS
========================================================= */

mysqli_stmt_close($update_stmt);

mysqli_stmt_close($notification_stmt);


/* =========================================================
   OUTPUT
========================================================= */

echo "OD deadline check completed successfully.<br>";

echo "Blocked applications: "
     . $blocked_count
     . "<br>";

echo "Advisor notifications created: "
     . $notification_count
     . "<br>";

?>
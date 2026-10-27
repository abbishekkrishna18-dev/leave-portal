<?php

session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = (int)$_SESSION["user_id"];


/* =========================================================
   EXCEL DOWNLOAD HEADERS
========================================================= */

$filename = "OD_Report_" . $student_id . "_" . date("Y-m-d") . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");


/* =========================================================
   FETCH STUDENT OD APPLICATIONS
========================================================= */

$sql = "SELECT
            l.student_id,
            u.name AS student_name,
            l.department,
            l.class_name,
            l.leave_type,
            l.date_from,
            l.date_to,
            l.no_of_days,
            l.reason,
            l.applied_at,
            l.approval_deadline,
            l.advisor_status,
            l.hod_status,
            l.od_status
        FROM leaves l
        INNER JOIN users u
            ON u.id = l.student_id
        WHERE l.student_id = ?
        AND LOWER(TRIM(l.leave_type)) IN (
            'od',
            'on duty',
            'on-duty',
            'on_duty'
        )
        ORDER BY l.id DESC";


$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    echo "Unable to generate OD report.";

    exit();

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


/* =========================================================
   REPORT TITLE
========================================================= */

echo "<html>";

echo "<head>";

echo "<meta charset='UTF-8'>";

echo "</head>";

echo "<body>";

echo "<table border='1'>";


/* =========================================================
   TITLE
========================================================= */

echo "<tr>";

echo "<th colspan='14' style='font-size:16px;'>";

echo "OD APPLICATION REPORT";

echo "</th>";

echo "</tr>";


/* =========================================================
   STUDENT INFORMATION
========================================================= */

echo "<tr>";

echo "<td><strong>Student ID</strong></td>";

echo "<td colspan='4'>"
    . htmlspecialchars((string)$student_id)
    . "</td>";

echo "<td><strong>Student Name</strong></td>";

$student_name = "";

if ($first_row = mysqli_fetch_assoc($result)) {

    $student_name = $first_row["student_name"] ?? "";

    /*
       Rewind is not supported reliably on all result
       configurations, so store the first row and process
       it below.
    */

    $rows = [$first_row];

    while ($row = mysqli_fetch_assoc($result)) {

        $rows[] = $row;

    }

} else {

    $rows = [];

}


echo "<td colspan='8'>"
    . htmlspecialchars($student_name)
    . "</td>";

echo "</tr>";


/* =========================================================
   COLUMN HEADERS
========================================================= */

echo "<tr>";

$headers = [
    "Student ID",
    "Student Name",
    "Department",
    "Class",
    "OD Type",
    "OD From",
    "OD To",
    "No. of Days",
    "Reason",
    "Applied Date",
    "Approval Deadline",
    "Advisor Status",
    "HOD Status",
    "Overall Status"
];


foreach ($headers as $header) {

    echo "<th>"
        . htmlspecialchars($header)
        . "</th>";

}


echo "</tr>";


/* =========================================================
   APPLICATION DATA
========================================================= */

if (empty($rows)) {

    echo "<tr>";

    echo "<td colspan='14'>";

    echo "No OD applications found.";

    echo "</td>";

    echo "</tr>";

} else {


    foreach ($rows as $row) {

        $advisor_status =
            $row["advisor_status"] ?? "Pending";

        $hod_status =
            $row["hod_status"] ?? "Pending";

        $od_status =
            $row["od_status"] ?? "Active";


        /* -----------------------------------------------
           OVERALL STATUS
        ------------------------------------------------ */

        if ($od_status === "Blocked") {

            $overall_status = "Blocked";

        } elseif (
            $advisor_status === "Rejected"
            ||
            $hod_status === "Rejected"
        ) {

            $overall_status = "Rejected";

        } elseif (
            $advisor_status === "Approved"
            &&
            $hod_status === "Approved"
        ) {

            $overall_status = "Approved";

        } else {

            $overall_status = "Pending";

        }


        echo "<tr>";


        /* Student ID */

        echo "<td>"
            . htmlspecialchars(
                (string)$row["student_id"]
            )
            . "</td>";


        /* Student Name */

        echo "<td>"
            . htmlspecialchars(
                $row["student_name"] ?? ""
            )
            . "</td>";


        /* Department */

        echo "<td>"
            . htmlspecialchars(
                $row["department"] ?? ""
            )
            . "</td>";


        /* Class */

        echo "<td>"
            . htmlspecialchars(
                $row["class_name"] ?? ""
            )
            . "</td>";


        /* OD Type */

        echo "<td>"
            . htmlspecialchars(
                $row["leave_type"] ?? ""
            )
            . "</td>";


        /* From */

        echo "<td>"
            . htmlspecialchars(
                $row["date_from"] ?? ""
            )
            . "</td>";


        /* To */

        echo "<td>"
            . htmlspecialchars(
                $row["date_to"] ?? ""
            )
            . "</td>";


        /* Days */

        echo "<td>"
            . htmlspecialchars(
                (string)($row["no_of_days"] ?? "")
            )
            . "</td>";


        /* Reason */

        echo "<td>"
            . htmlspecialchars(
                $row["reason"] ?? ""
            )
            . "</td>";


        /* Applied Date */

        echo "<td>"
            . htmlspecialchars(
                $row["applied_at"] ?? ""
            )
            . "</td>";


        /* Approval Deadline */

        echo "<td>"
            . htmlspecialchars(
                $row["approval_deadline"] ?? ""
            )
            . "</td>";


        /* Advisor Status */

        echo "<td>"
            . htmlspecialchars(
                $advisor_status
            )
            . "</td>";


        /* HOD Status */

        echo "<td>"
            . htmlspecialchars(
                $hod_status
            )
            . "</td>";


        /* Overall Status */

        echo "<td>"
            . htmlspecialchars(
                $overall_status
            )
            . "</td>";


        echo "</tr>";

    }

}


echo "</table>";

echo "</body>";

echo "</html>";


mysqli_stmt_close($stmt);

exit();

?>
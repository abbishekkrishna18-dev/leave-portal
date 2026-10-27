<?php

session_start();
include "db.php";

require_once __DIR__ . "/vendor/autoload.php";

use Dompdf\Dompdf;



/* =========================================================
   STUDENT LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: index.php");
    exit();
}


$student_id = (int)$_SESSION["user_id"];


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
    die("Unable to generate OD report.");
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


/* =========================================================
   STORE APPLICATIONS
========================================================= */

$rows = [];

while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}


/* =========================================================
   STUDENT INFORMATION
========================================================= */

$student_name = "";

$department = "";

$class_name = "";


if (!empty($rows)) {

    $student_name =
        $rows[0]["student_name"] ?? "";

    $department =
        $rows[0]["department"] ?? "";

    $class_name =
        $rows[0]["class_name"] ?? "";

}


/* =========================================================
   HTML CONTENT
========================================================= */

$html = '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9px;
    margin: 20px;
}

h1 {
    text-align: center;
    font-size: 18px;
    margin-bottom: 15px;
}

.student-info {
    width: 100%;
    margin-bottom: 15px;
}

.student-info td {
    padding: 5px;
    border: 1px solid #999;
}

.label {
    font-weight: bold;
}

table.report {
    width: 100%;
    border-collapse: collapse;
}

table.report th {
    background: #eeeeee;
    font-weight: bold;
    text-align: center;
    padding: 6px;
    border: 1px solid #777;
}

table.report td {
    padding: 5px;
    border: 1px solid #999;
    vertical-align: top;
}

.center {
    text-align: center;
}

.footer {
    margin-top: 20px;
    font-size: 8px;
    text-align: right;
    color: #666;
}

</style>

</head>

<body>

<h1>OD APPLICATION REPORT</h1>


<table class="student-info">

<tr>

<td class="label">
Student ID
</td>

<td>
' . htmlspecialchars((string)$student_id) . '
</td>

<td class="label">
Student Name
</td>

<td>
' . htmlspecialchars($student_name) . '
</td>

</tr>


<tr>

<td class="label">
Department
</td>

<td>
' . htmlspecialchars($department) . '
</td>

<td class="label">
Class
</td>

<td>
' . htmlspecialchars($class_name) . '
</td>

</tr>

</table>


<table class="report">

<thead>

<tr>

<th>Student ID</th>

<th>Student Name</th>

<th>Department</th>

<th>Class</th>

<th>OD Type</th>

<th>OD From</th>

<th>OD To</th>

<th>Days</th>

<th>Reason</th>

<th>Applied Date</th>

<th>Approval Deadline</th>

<th>Advisor</th>

<th>HOD</th>

<th>Overall Status</th>

</tr>

</thead>

<tbody>
';


/* =========================================================
   APPLICATION DATA
========================================================= */

if (empty($rows)) {

    $html .= '

    <tr>

        <td colspan="14" class="center">

            No OD applications found.

        </td>

    </tr>

    ';

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
            $advisor_status === "Rejected" ||
            $hod_status === "Rejected"
        ) {

            $overall_status = "Rejected";

        } elseif (
            $advisor_status === "Approved" &&
            $hod_status === "Approved"
        ) {

            $overall_status = "Approved";

        } else {

            $overall_status = "Pending";

        }


        $html .= '

        <tr>

        <td class="center">
            ' . htmlspecialchars(
                (string)$row["student_id"]
            ) . '
        </td>

        <td>
            ' . htmlspecialchars(
                $row["student_name"] ?? ""
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $row["department"] ?? ""
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $row["class_name"] ?? ""
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $row["leave_type"] ?? ""
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $row["date_from"] ?? ""
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $row["date_to"] ?? ""
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                (string)($row["no_of_days"] ?? "")
            ) . '
        </td>

        <td>
            ' . htmlspecialchars(
                $row["reason"] ?? ""
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $row["applied_at"] ?? ""
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $row["approval_deadline"] ?? ""
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $advisor_status
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $hod_status
            ) . '
        </td>

        <td class="center">
            ' . htmlspecialchars(
                $overall_status
            ) . '
        </td>

        </tr>

        ';

    }

}


$html .= '

</tbody>

</table>


<div class="footer">

Generated on ' . date("d-m-Y H:i:s") . '

</div>

</body>

</html>
';


/* =========================================================
   GENERATE PDF
========================================================= */




$dompdf = new Dompdf();

$dompdf->loadHtml($html);


/* =========================================================
   PAPER SETTINGS
========================================================= */

$dompdf->setPaper(
    "A4",
    "landscape"
);


/* =========================================================
   RENDER PDF
========================================================= */

$dompdf->render();


/* =========================================================
   DOWNLOAD PDF
========================================================= */

$filename =
    "OD_Report_" .
    $student_id .
    "_" .
    date("Y-m-d") .
    ".pdf";


$dompdf->stream(
    $filename,
    [
        "Attachment" => true
    ]
);


mysqli_stmt_close($stmt);

exit();

?>
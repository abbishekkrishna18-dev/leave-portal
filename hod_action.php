<?php

session_start();

require_once "db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$smtp = require __DIR__ . '/smtp_config.php';


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
    header("Location: hod_dashboard.php");
    exit();
}


// --------------------------------------------------
// GET FORM DATA
// --------------------------------------------------

$leave_id = isset($_POST['leave_id']) ? (int)$_POST['leave_id'] : 0;
$action   = isset($_POST['action']) ? trim($_POST['action']) : '';
$remarks  = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';


// --------------------------------------------------
// VALIDATE INPUT
// --------------------------------------------------

if ($leave_id <= 0) {
    $_SESSION['error'] = "Invalid leave application.";
    header("Location: hod_dashboard.php");
    exit();
}

if (!in_array($action, ['approve', 'reject'])) {
    $_SESSION['error'] = "Invalid HOD action.";
    header("Location: hod_dashboard.php");
    exit();
}


// --------------------------------------------------
// REJECTION REASON REQUIRED
// --------------------------------------------------

if ($action === 'reject' && $remarks === '') {
    $_SESSION['error'] = "Please enter a rejection reason.";
    header("Location: hod_dashboard.php");
    exit();
}


// --------------------------------------------------
// GET LEAVE + STUDENT EMAIL
// --------------------------------------------------

$sql = "
    SELECT
        l.*,
        u.email AS student_email
    FROM leaves l
    LEFT JOIN users u
        ON u.id = l.student_id
    WHERE l.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['error'] = "Database error.";
    header("Location: hod_dashboard.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $leave_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$leave = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// --------------------------------------------------
// CHECK APPLICATION EXISTS
// --------------------------------------------------

if (!$leave) {
    $_SESSION['error'] = "Leave application not found.";
    header("Location: hod_dashboard.php");
    exit();
}


// --------------------------------------------------
// CHECK ADVISOR APPROVAL
// --------------------------------------------------

if ($leave['advisor_status'] !== 'Approved') {
    $_SESSION['error'] = "This application has not been approved by the Advisor.";
    header("Location: hod_dashboard.php");
    exit();
}


// --------------------------------------------------
// CHECK HOD STATUS
// --------------------------------------------------

if ($leave['hod_status'] !== 'Pending') {
    $_SESSION['error'] = "This application has already been processed.";
    header("Location: hod_dashboard.php");
    exit();
}


// --------------------------------------------------
// CHECK BLOCKED STATUS
// --------------------------------------------------

if (
    isset($leave['od_status']) &&
    strtolower($leave['od_status']) === 'blocked'
) {
    $_SESSION['error'] = "This OD application is blocked.";
    header("Location: hod_dashboard.php");
    exit();
}


// --------------------------------------------------
// UPDATE HOD STATUS
// --------------------------------------------------

if ($action === 'approve') {

    $new_status = 'Approved';
    $new_remarks = '';

} else {

    $new_status = 'Rejected';
    $new_remarks = $remarks;
}


$update_sql = "
    UPDATE leaves
    SET
        hod_status = ?,
        hod_remarks = ?
    WHERE id = ?
";

$update_stmt = mysqli_prepare($conn, $update_sql);

if (!$update_stmt) {
    $_SESSION['error'] = "Unable to process application.";
    header("Location: hod_dashboard.php");
    exit();
}

mysqli_stmt_bind_param(
    $update_stmt,
    "ssi",
    $new_status,
    $new_remarks,
    $leave_id
);

if (!mysqli_stmt_execute($update_stmt)) {

    mysqli_stmt_close($update_stmt);

    $_SESSION['error'] = "Failed to update application.";
    header("Location: hod_dashboard.php");
    exit();
}

mysqli_stmt_close($update_stmt);


// --------------------------------------------------
// SEND EMAIL TO STUDENT
// --------------------------------------------------

$student_email = trim($leave['student_email'] ?? '');

$email_sent = false;

if ($student_email !== '' && filter_var($student_email, FILTER_VALIDATE_EMAIL)) {

    try {

        $mail = new PHPMailer(true);

        // SMTP
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp['port'];

        // Sender
        $mail->setFrom(
            $smtp['from_email'],
            $smtp['from_name']
        );

        // Student
        $mail->addAddress(
            $student_email,
            $leave['student_name']
        );

        // HTML email
        $mail->isHTML(true);

        if ($action === 'approve') {

            $mail->Subject = 'Leave / OD Application Approved by HOD';

            $mail->Body = '
                <div style="font-family:Arial,sans-serif;max-width:650px;margin:auto;border:1px solid #ddd;border-radius:10px;overflow:hidden;">

                    <div style="background:#4e73df;color:white;padding:20px;">
                        <h2 style="margin:0;">MKCE Leave Portal</h2>
                    </div>

                    <div style="padding:25px;">

                        <h3 style="color:#1cc88a;">
                            Application Approved
                        </h3>

                        <p>Dear ' . htmlspecialchars($leave['student_name']) . ',</p>

                        <p>
                            Your Leave / OD application has been
                            <strong>approved by the HOD</strong>.
                        </p>

                        <table style="width:100%;border-collapse:collapse;margin-top:20px;">

                            <tr>
                                <td style="padding:10px;border:1px solid #ddd;"><strong>Application ID</strong></td>
                                <td style="padding:10px;border:1px solid #ddd;">' . $leave_id . '</td>
                            </tr>

                            <tr>
                                <td style="padding:10px;border:1px solid #ddd;"><strong>Type</strong></td>
                                <td style="padding:10px;border:1px solid #ddd;">' . htmlspecialchars($leave['leave_type']) . '</td>
                            </tr>

                            <tr>
                                <td style="padding:10px;border:1px solid #ddd;"><strong>From</strong></td>
                                <td style="padding:10px;border:1px solid #ddd;">' . htmlspecialchars($leave['date_from']) . '</td>
                            </tr>

                            <tr>
                                <td style="padding:10px;border:1px solid #ddd;"><strong>To</strong></td>
                                <td style="padding:10px;border:1px solid #ddd;">' . htmlspecialchars($leave['date_to']) . '</td>
                            </tr>

                            <tr>
                                <td style="padding:10px;border:1px solid #ddd;"><strong>No. of Days</strong></td>
                                <td style="padding:10px;border:1px solid #ddd;">' . htmlspecialchars($leave['no_of_days']) . '</td>
                            </tr>

                        </table>

                        <p style="margin-top:25px;">
                            <strong>Final Status:</strong>
                            <span style="color:#1cc88a;">Approved</span>
                        </p>

                        <p>
                            Regards,<br>
                            <strong>MKCE Leave Portal</strong>
                        </p>

                    </div>
                </div>
            ';

            $mail->AltBody =
                "Dear " . $leave['student_name'] . ",\n\n" .
                "Your Leave / OD application has been approved by the HOD.\n\n" .
                "Application ID: " . $leave_id . "\n" .
                "Type: " . $leave['leave_type'] . "\n" .
                "From: " . $leave['date_from'] . "\n" .
                "To: " . $leave['date_to'] . "\n" .
                "No. of Days: " . $leave['no_of_days'] . "\n\n" .
                "Final Status: Approved\n\n" .
                "MKCE Leave Portal";

        } else {

            $mail->Subject = 'Leave / OD Application Rejected by HOD';

            $safe_remarks = htmlspecialchars($remarks);

            $mail->Body = '
                <div style="font-family:Arial,sans-serif;max-width:650px;margin:auto;border:1px solid #ddd;border-radius:10px;overflow:hidden;">

                    <div style="background:#4e73df;color:white;padding:20px;">
                        <h2 style="margin:0;">MKCE Leave Portal</h2>
                    </div>

                    <div style="padding:25px;">

                        <h3 style="color:#e74a3b;">
                            Application Rejected
                        </h3>

                        <p>Dear ' . htmlspecialchars($leave['student_name']) . ',</p>

                        <p>
                            Your Leave / OD application has been
                            <strong>rejected by the HOD</strong>.
                        </p>

                        <table style="width:100%;border-collapse:collapse;margin-top:20px;">

                            <tr>
                                <td style="padding:10px;border:1px solid #ddd;"><strong>Application ID</strong></td>
                                <td style="padding:10px;border:1px solid #ddd;">' . $leave_id . '</td>
                            </tr>

                            <tr>
                                <td style="padding:10px;border:1px solid #ddd;"><strong>Type</strong></td>
                                <td style="padding:10px;border:1px solid #ddd;">' . htmlspecialchars($leave['leave_type']) . '</td>
                            </tr>

                            <tr>
                                <td style="padding:10px;border:1px solid #ddd;"><strong>From</strong></td>
                                <td style="padding:10px;border:1px solid #ddd;">' . htmlspecialchars($leave['date_from']) . '</td>
                            </tr>

                            <tr>
                                <td style="padding:10px;border:1px solid #ddd;"><strong>To</strong></td>
                                <td style="padding:10px;border:1px solid #ddd;">' . htmlspecialchars($leave['date_to']) . '</td>
                            </tr>

                        </table>

                        <div style="margin-top:25px;padding:15px;background:#f8d7da;border-left:5px solid #e74a3b;">

                            <strong>Reason for Rejection:</strong>

                            <p style="margin-bottom:0;margin-top:8px;">
                                ' . $safe_remarks . '
                            </p>

                        </div>

                        <p style="margin-top:25px;">
                            <strong>Final Status:</strong>
                            <span style="color:#e74a3b;">Rejected</span>
                        </p>

                        <p>
                            Regards,<br>
                            <strong>MKCE Leave Portal</strong>
                        </p>

                    </div>
                </div>
            ';

            $mail->AltBody =
                "Dear " . $leave['student_name'] . ",\n\n" .
                "Your Leave / OD application has been rejected by the HOD.\n\n" .
                "Application ID: " . $leave_id . "\n" .
                "Type: " . $leave['leave_type'] . "\n" .
                "From: " . $leave['date_from'] . "\n" .
                "To: " . $leave['date_to'] . "\n\n" .
                "Reason for Rejection:\n" .
                $remarks . "\n\n" .
                "Final Status: Rejected\n\n" .
                "MKCE Leave Portal";
        }

        $mail->send();

        $email_sent = true;

    } catch (Exception $e) {

        // Application status is already updated.
        // Email failure should not undo the HOD decision.

        $email_sent = false;
    }
}


// --------------------------------------------------
// SUCCESS MESSAGE
// --------------------------------------------------

if ($action === 'approve') {

    if ($email_sent) {
        $_SESSION['success'] = "Application approved and email sent to the student.";
    } else {
        $_SESSION['success'] = "Application approved, but the email could not be sent.";
    }

} else {

    if ($email_sent) {
        $_SESSION['success'] = "Application rejected and rejection email sent to the student.";
    } else {
        $_SESSION['success'] = "Application rejected, but the email could not be sent.";
    }
}


header("Location: hod_dashboard.php");
exit();

?>
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$smtp = require __DIR__ . '/smtp_config.php';

$mail = new PHPMailer(true);

try {

    // SMTP settings

    $mail->isSMTP();

    $mail->Host =
        $smtp['host'];

    $mail->SMTPAuth = true;

    $mail->Username =
        $smtp['username'];

    $mail->Password =
        $smtp['password'];

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port =
        $smtp['port'];


    // Sender

    $mail->setFrom(
        $smtp['from_email'],
        $smtp['from_name']
    );


    // IMPORTANT:
    // Replace this with your own Gmail address
    // only if you want to test SMTP again.

    $mail->addAddress(
        'YOUR_GMAIL@gmail.com'
    );


    // Email content

    $mail->isHTML(true);

    $mail->Subject =
        'MKCE Leave Portal - SMTP Test';

    $mail->Body = '

        <h2>MKCE Leave Portal</h2>

        <p>
            This is a test email.
        </p>

        <p>
            Gmail SMTP is working successfully.
        </p>

    ';

    $mail->AltBody =
        'MKCE Leave Portal - Gmail SMTP test successful.';


    $mail->send();

    echo '
        <h2 style="color:green;">
            Email sent successfully!
        </h2>
    ';

} catch (Exception $e) {

    echo '
        <h2 style="color:red;">
            Email could not be sent.
        </h2>
    ';

    echo '
        <p>
            Error:
            ' .
            htmlspecialchars(
                $mail->ErrorInfo
            ) .
            '
        </p>
    ';

}

?>
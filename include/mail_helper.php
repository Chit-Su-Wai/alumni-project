<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../src/Exception.php';
require_once __DIR__ . '/../src/PHPMailer.php';
require_once __DIR__ . '/../src/SMTP.php';
require_once __DIR__ . '/auth_helpers.php';

/*
|--------------------------------------------------------------------------
| Gmail SMTP Configuration
|--------------------------------------------------------------------------
| Replace with your Gmail account and App Password
*/

const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 465;

const SMTP_USERNAME = 'chitsuwai.ucsh@gmail.com';
const SMTP_PASSWORD = 'poubcagyrbsvzwtb';

const SMTP_FROM_EMAIL = SMTP_USERNAME;
const SMTP_FROM_NAME = 'Alumni Network';
function send_otp_email(string $email, string $otp, string $type = 'register'): bool
{
    $subject = $type === 'register'
        ? 'Email Verification - Alumni Network'
        : 'Password Reset OTP - Alumni Network';

    $heading = $type === 'register'
        ? 'Verify Your Email'
        : 'Password Reset';

    $bodyText = $type === 'register'
        ? 'Use the OTP below to verify your email address and complete your registration.'
        : 'Use the OTP below to reset your password.';

    $message = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
</head>

<body style="margin:0;padding:0;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px;background:#f1f5f9;">
<tr>
<td align="center">

<table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;">

<tr>
<td style="background:#0d9488;padding:25px;text-align:center;">
<h1 style="margin:0;color:#ffffff;">Alumni Network</h1>
</td>
</tr>

<tr>
<td style="padding:35px;">

<h2>{$heading}</h2>

<p>{$bodyText}</p>

<div style="text-align:center;margin:30px 0;">

<div style="
display:inline-block;
padding:18px 30px;
font-size:36px;
font-weight:bold;
letter-spacing:8px;
background:#ecfeff;
border:2px dashed #14b8a6;
color:#0f766e;
border-radius:10px;
font-family:monospace;">
{$otp}
</div>

</div>

<p>This OTP expires in <strong>' . ALUMNI_OTP_EXPIRY_MINUTES . ' minutes</strong>.</p>

<p style="color:#888;">
If you didn't request this email, please ignore it.
</p>

</td>
</tr>

<tr>
<td style="background:#f8fafc;padding:15px;text-align:center;font-size:12px;color:#94a3b8;">
© 2026 Alumni Network
</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>
HTML;

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
       
        $mail->Host = SMTP_HOST;

        $mail->SMTPAuth = true;

        $mail->Username = SMTP_USERNAME;

        $mail->Password = SMTP_PASSWORD;

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        $mail->addAddress($email);

        $mail->isHTML(true);

        $mail->Subject = $subject;

        $mail->Body = $message;

        return $mail->send();

    } catch (Exception $e) {

       error_log($mail->ErrorInfo);
return false;
    }
    
}
function generate_otp(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function store_otp(mysqli $conn, string $email, string $otp, string $type): bool
{
    delete_otp_records($conn, $email, $type);

    $expires_at = otp_expires_at();

    $stmt = $conn->prepare(
        "INSERT INTO otps (email, otp, type, expires_at)
         VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param("ssss", $email, $otp, $type, $expires_at);

    return $stmt->execute();

}

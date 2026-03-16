<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

function sendStaffAccountMail(string $toEmail, string $staffName, string $loginEmail, string $plainPassword, string &$mailError = ""): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'smartlankaproject@gmail.com';   // your Gmail
        $mail->Password   = 'esopdtrjngahhogn';           // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('fitnesscentrefitzone@gmail.com', 'FitZone');
        $mail->addAddress($toEmail, $staffName);

        $mail->isHTML(true);
        $mail->Subject = 'Your FitZone Staff Account Has Been Created';

        $body = "
            <div style='font-family:Arial,sans-serif;line-height:1.7;color:#111827'>
                <h2 style='color:#1d4ed8;'>Welcome to FitZone</h2>
                <p>Hello <strong>" . htmlspecialchars($staffName) . "</strong>,</p>

                <p>Your staff account has been created successfully.</p>

                <p><strong>Login Details:</strong></p>
                <ul>
                    <li><strong>Email:</strong> " . htmlspecialchars($loginEmail) . "</li>
                    <li><strong>Password:</strong> " . htmlspecialchars($plainPassword) . "</li>
                </ul>

                <p>Please use these details to log in to your FitZone staff dashboard.</p>
                <p>For security, please change your password after your first login.</p>

                <br>
                <p>Regards,<br><strong>FitZone Admin</strong></p>
            </div>
        ";

        $mail->Body = $body;
        $mail->AltBody = "Hello $staffName,\n\nYour FitZone staff account has been created.\nEmail: $loginEmail\nPassword: $plainPassword\n\nPlease login and change your password.\n\nRegards,\nFitZone Admin";

        return $mail->send();
    } catch (Exception $e) {
        $mailError = $mail->ErrorInfo;
        return false;
    }
}
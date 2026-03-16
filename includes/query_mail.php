<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

function sendQueryReplyMail($toEmail, $toName, $subjectText, $userMessage, $adminReply)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fitnesscentrefitzone@gmail.com';   // your Gmail
        $mail->Password   = 'YOUR_APP_PASSWORD_HERE';           // your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('fitnesscentrefitzone@gmail.com', 'FitZone');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'FitZone Query Response - ' . $subjectText;

        $safeName = htmlspecialchars($toName);
        $safeSubject = htmlspecialchars($subjectText);
        $safeUserMessage = nl2br(htmlspecialchars($userMessage));
        $safeReply = nl2br(htmlspecialchars($adminReply));

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;max-width:700px;margin:auto;padding:20px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;'>
                <h2 style='color:#111827;'>Hello {$safeName},</h2>
                <p style='font-size:15px;color:#374151;'>Your query has been reviewed by the FitZone admin team.</p>

                <div style='background:#ffffff;padding:15px;border-radius:10px;border:1px solid #e5e7eb;margin:15px 0;'>
                    <p><strong>Subject:</strong> {$safeSubject}</p>
                    <p><strong>Your Message:</strong><br>{$safeUserMessage}</p>
                </div>

                <div style='background:#eff6ff;padding:15px;border-left:4px solid #2563eb;border-radius:10px;margin:15px 0;'>
                    <p><strong>Admin Reply:</strong><br>{$safeReply}</p>
                </div>

                <p style='font-size:14px;color:#6b7280;'>Thank you for contacting FitZone.</p>
            </div>
        ";

        $mail->AltBody = "Hello {$toName},\n\nYour FitZone query has been reviewed.\n\nSubject: {$subjectText}\nYour Message: {$userMessage}\n\nAdmin Reply:\n{$adminReply}\n\nThank you.";

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
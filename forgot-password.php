<?php
session_start();
require_once __DIR__ . "/config/db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/vendor/phpmailer/src/Exception.php";
require_once __DIR__ . "/vendor/phpmailer/src/PHPMailer.php";
require_once __DIR__ . "/vendor/phpmailer/src/SMTP.php";

$message = "";
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $message = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

                $update = $conn->prepare("UPDATE users SET resettoken = ?, resettokenexpiry = ? WHERE id = ?");
                if ($update) {
                    $update->bind_param("ssi", $token, $expiry, $user['id']);
                    $update->execute();
                    $update->close();

                    $resetLink = "http://localhost/fitzone/reset-password.php?token=" . urlencode($token);

                    $fullName = "FitZone User";

                    $mail = new PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'smartlankaproject@gmail.com';
                        $mail->Password = 'esopdtrjngahhogn'; 
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        // test pannumbothu uncomment pannu
                        // $mail->SMTPDebug = 2;
                        // $mail->Debugoutput = 'html';

                        $mail->setFrom('fitnesscentrefitzone@gmail.com', 'FitZone');
                        $mail->addAddress($user['email'], $fullName);

                        $mail->isHTML(true);
                        $mail->Subject = 'FitZone Password Reset Request';
                        $mail->Body = "
                            <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;'>
                                <h2 style='color:#111827;'>Forgot your password?</h2>
                                <p>Hello {$fullName},</p>
                                <p>We received a request to reset your FitZone account password.</p>
                                <p style='margin:20px 0;'>
                                    <a href='{$resetLink}' style='background:#22c55e;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>
                                        Reset Password
                                    </a>
                                </p>
                                <p>This link will expire in 1 hour.</p>
                                <p>If you did not request this, you can ignore this email.</p>
                            </div>
                        ";
                        $mail->AltBody = "Hello {$fullName},\n\nUse this link to reset your password:\n{$resetLink}\n\nThis link expires in 1 hour.";

                        $mail->send();
                    } catch (Exception $e) {
                        // for project safety, generic message
                    }
                }
            }

            $message = "If this email exists in our system, a password reset link will be sent.";
        } else {
            $message = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | FitZone</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
    body{background:#f4f7fb;}
    .page{
      min-height:calc(100vh - 90px);
      display:flex;
      align-items:center;
      justify-content:center;
      padding:40px 20px;
    }
    .card{
      width:100%;
      max-width:440px;
      background:#fff;
      padding:30px;
      border-radius:22px;
      box-shadow:0 16px 40px rgba(0,0,0,0.08);
    }
    .card h2{
      margin-bottom:10px;
      color:#0f172a;
      text-align:center;
    }
    .card p{
      text-align:center;
      color:#64748b;
      margin-bottom:20px;
      font-size:14px;
      line-height:1.7;
    }
    .msg{
      background:#ecfdf5;
      color:#166534;
      border:1px solid #bbf7d0;
      padding:12px 14px;
      border-radius:12px;
      margin-bottom:16px;
      font-size:14px;
    }
    label{
      display:block;
      margin-bottom:8px;
      font-weight:600;
      color:#111827;
    }
    input{
      width:100%;
      height:48px;
      border:1px solid #d1d5db;
      border-radius:14px;
      padding:0 14px;
      margin-bottom:18px;
      outline:none;
    }
    input:focus{
      border-color:#22c55e;
      box-shadow:0 0 0 4px rgba(34,197,94,0.12);
    }
    button{
      width:100%;
      height:48px;
      border:none;
      border-radius:14px;
      background:#22c55e;
      color:#fff;
      font-weight:700;
      cursor:pointer;
    }
    .back-link{
      display:block;
      text-align:center;
      margin-top:16px;
      color:#2563eb;
      text-decoration:none;
      font-weight:600;
    }
  </style>
</head>
<body>

<?php include __DIR__ . "/includes/navbar.php"; ?>

<div class="page">
  <form method="POST" class="card">
    <h2>Forgot Password</h2>
    <p>Enter your registered email address. We’ll help you reset your password.</p>

    <?php if ($message): ?>
      <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <label for="email">Email Address</label>
    <input
      type="email"
      id="email"
      name="email"
      value="<?= htmlspecialchars($email) ?>"
      placeholder="Enter your email"
      required
    >

    <button type="submit">Send Reset Link</button>
    <a href="/fitzone/login.php" class="back-link">← Back to Login</a>
  </form>
</div>

</body>
</html>
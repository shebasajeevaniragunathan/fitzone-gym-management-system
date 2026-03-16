<?php
session_start();
include "../db.php";
require_once "../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$message_type = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    if ($email === "") {
        $message = "Email is required.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a valid email address.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ? AND role = 'staff' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $upd = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $upd->bind_param("ssi", $token, $expiry, $user['id']);

            if ($upd->execute()) {
                $reset_link = "http://localhost/FITZONE/staff/reset-password.php?token=" . urlencode($token);

                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'yourgmail@gmail.com';
                    $mail->Password   = 'your_app_password';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('yourgmail@gmail.com', 'FitZone');
                    $mail->addAddress($user['email'], $user['name']);

                    $mail->isHTML(true);
                    $mail->Subject = 'FitZone Staff Password Reset';
                    $mail->Body = "
                        <div style='font-family:Arial,sans-serif;line-height:1.6;'>
                            <h2>FitZone Password Reset</h2>
                            <p>Hello {$user['name']},</p>
                            <p>Click the button below to reset your password:</p>
                            <p>
                                <a href='{$reset_link}' style='display:inline-block;padding:12px 20px;background:#111827;color:#fff;text-decoration:none;border-radius:8px;'>
                                    Reset Password
                                </a>
                            </p>
                            <p>This link will expire in 1 hour.</p>
                            <p>If you did not request this, please ignore this email.</p>
                        </div>
                    ";

                    $mail->send();
                    $message = "Password reset link has been sent to your email.";
                    $message_type = "success";
                } catch (Exception $e) {
                    $message = "Email could not be sent. Check PHPMailer settings.";
                    $message_type = "error";
                }
            } else {
                $message = "Failed to generate reset token.";
                $message_type = "error";
            }
        } else {
            $message = "No staff account found with this email.";
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - FitZone</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif;}
    body{
      min-height:100vh;
      background:#f6f8fb;
      display:flex;
      justify-content:center;
      align-items:center;
      padding:20px;
    }
    .box{
      width:100%;
      max-width:430px;
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:18px;
      box-shadow:0 10px 30px rgba(0,0,0,0.06);
      padding:35px 30px;
    }
    h2{text-align:center;margin-bottom:8px;color:#111827;}
    p{text-align:center;color:#6b7280;margin-bottom:24px;font-size:14px;}
    .form-group{margin-bottom:18px;}
    label{display:block;margin-bottom:8px;font-size:14px;font-weight:600;color:#374151;}
    input{
      width:100%;
      padding:13px 14px;
      border:1px solid #d1d5db;
      border-radius:10px;
      outline:none;
      font-size:14px;
    }
    .btn{
      width:100%;
      border:none;
      background:#111827;
      color:#fff;
      padding:13px;
      border-radius:10px;
      font-size:15px;
      font-weight:600;
      cursor:pointer;
    }
    .back{
      display:block;
      text-align:center;
      margin-top:15px;
      color:#111827;
      text-decoration:none;
      font-size:14px;
      font-weight:600;
    }
    .msg{
      padding:12px 14px;
      border-radius:10px;
      margin-bottom:18px;
      font-size:14px;
      text-align:center;
      font-weight:600;
    }
    .success{background:#dcfce7;color:#166534;}
    .error{background:#fee2e2;color:#b91c1c;}
  </style>
</head>
<body>
  <div class="box">
    <h2>Forgot Password</h2>
    <p>Enter your staff email to receive a reset link</p>

    <?php if ($message !== ""): ?>
      <div class="msg <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" required>
      </div>

      <button type="submit" class="btn">Send Reset Link</button>
    </form>

    <a href="login.php" class="back">← Back to Login</a>
  </div>
</body>
</html>
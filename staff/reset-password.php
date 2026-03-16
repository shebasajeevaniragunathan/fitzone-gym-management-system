<?php
session_start();
include "../db.php";

$token = trim($_GET['token'] ?? '');
$message = "";
$message_type = "";
$valid_token = false;

if ($token !== "") {
    $stmt = $conn->prepare("SELECT id, email, reset_token_expiry FROM users WHERE reset_token = ? AND role = 'staff' LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (strtotime($user['reset_token_expiry']) > time()) {
            $valid_token = true;
        } else {
            $message = "Reset link has expired.";
            $message_type = "error";
        }
    } else {
        $message = "Invalid reset link.";
        $message_type = "error";
    }
} else {
    $message = "Token is missing.";
    $message_type = "error";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $valid_token) {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($new_password === "" || $confirm_password === "") {
        $message = "All fields are required.";
        $message_type = "error";
    } elseif (strlen($new_password) < 5) {
        $message = "Password must be at least 5 characters.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else {
        // For secure system use password_hash($new_password, PASSWORD_DEFAULT)
        $upd = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL, login_attempts = 0, last_attempt_time = NULL WHERE reset_token = ? AND role = 'staff'");
        $upd->bind_param("ss", $new_password, $token);

        if ($upd->execute()) {
            $message = "Password reset successful. You can now login.";
            $message_type = "success";
            $valid_token = false;
        } else {
            $message = "Failed to reset password.";
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
  <title>Reset Password - FitZone</title>
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
    .back{
      display:block;
      text-align:center;
      margin-top:15px;
      color:#111827;
      text-decoration:none;
      font-size:14px;
      font-weight:600;
    }
  </style>
</head>
<body>
  <div class="box">
    <h2>Reset Password</h2>
    <p>Enter your new password</p>

    <?php if ($message !== ""): ?>
      <div class="msg <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <?php if ($valid_token): ?>
      <form method="POST">
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="new_password" placeholder="Enter new password" required>
        </div>

        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" placeholder="Confirm new password" required>
        </div>

        <button type="submit" class="btn">Reset Password</button>
      </form>
    <?php endif; ?>

    <a href="login.php" class="back">← Back to Login</a>
  </div>
</body>
</html>
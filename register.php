<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/mail.php";

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $role = 'customer';
    $status = 'active';

    if ($full_name === '' || $email === '' || $phone === '' || $password === '' || $confirm_password === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } elseif (!preg_match('/^[0-9+\-\s]{8,15}$/', $phone)) {
        $error = "Enter a valid phone number.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Password and confirm password do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if ($exists) {
            $error = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $full_name, $email, $phone, $hashed, $role, $status);

            if ($stmt->execute()) {
                $subject = "Welcome to FitZone 🎉";
                $body = "
                    <h2>Thank you for registering with FitZone!</h2>
                    <p>Hello <b>" . htmlspecialchars($full_name) . "</b>,</p>
                    <p>Your account has been created successfully.</p>
                    <p>You can now log in and access FitZone services.</p>
                    <br>
                    <p>Regards,<br>FitZone Fitness Center</p>
                ";

                $mailError = "";
                sendFitZoneMail($email, $full_name, $subject, $body, $mailError);

                $message = "Registration successful. A thank you email has been sent.";
            } else {
                $error = "Registration failed. Please try again.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register | FitZone</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{margin:0;font-family:Arial,sans-serif;background:#07152d;display:flex;justify-content:center;align-items:center;min-height:100vh;color:#fff}
.card{width:420px;background:#0f1d3a;padding:28px;border-radius:22px;box-shadow:0 10px 30px rgba(0,0,0,.3)}
h1{margin:0 0 8px}
p{color:#b8c4e0}
input{width:100%;padding:13px 14px;margin:8px 0 14px;border-radius:12px;border:1px solid #2c406f;background:#091327;color:#fff;box-sizing:border-box}
button{width:100%;padding:13px;border:none;border-radius:12px;background:#5aa2ff;color:#000;font-weight:700;cursor:pointer}
.msg{padding:12px;border-radius:12px;margin-bottom:14px;font-weight:700}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
a{color:#5aa2ff;text-decoration:none}
</style>
</head>
<body>
<div class="card">
    <h1>Create Account ✨</h1>
    <p>Register your FitZone account</p>

    <?php if($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="password" name="password" placeholder="Password" required minlength="8">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="8">
        <button type="submit">Register</button>
    </form>

    <p style="text-align:center;margin-top:14px;">Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
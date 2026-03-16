<?php
session_start();
require_once __DIR__ . "/config/db.php";

$message = "";
$error = "";

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '') {
    die("Invalid or missing reset token.");
}

/* -----------------------------
   Find valid token
----------------------------- */
$stmt = $conn->prepare("
    SELECT id, email, resettokenexpiry
    FROM users
    WHERE resettoken = ?
    LIMIT 1
");

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("This password reset link is invalid or has expired.");
}

/* -----------------------------
   Check expiry manually
----------------------------- */
if (empty($user['resettokenexpiry']) || strtotime($user['resettokenexpiry']) < time()) {
    die("This password reset link is invalid or has expired.");
}

/* -----------------------------
   Reset password
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($password === '' || $confirmPassword === '') {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $update = $conn->prepare("
            UPDATE users
            SET passwordhash = ?, resettoken = NULL, resettokenexpiry = NULL
            WHERE id = ?
        ");

        if ($update) {
            $update->bind_param("si", $hashedPassword, $user['id']);

            if ($update->execute()) {
                $message = "Your password has been reset successfully. You can now log in.";
            } else {
                $error = "Failed to reset password.";
            }

            $update->close();
        } else {
            $error = "Database error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | FitZone</title>
    <style>
        body{
            margin:0;
            font-family:Arial,sans-serif;
            background:#f4f7fb;
            display:flex;
            justify-content:center;
            align-items:center;
            min-height:100vh;
        }
        .box{
            width:100%;
            max-width:420px;
            background:#fff;
            padding:30px;
            border-radius:16px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
        }
        h2{
            text-align:center;
            margin-bottom:18px;
            color:#111827;
        }
        .msg{
            background:#dcfce7;
            color:#166534;
            padding:12px;
            border-radius:10px;
            margin-bottom:14px;
        }
        .err{
            background:#fee2e2;
            color:#991b1b;
            padding:12px;
            border-radius:10px;
            margin-bottom:14px;
        }
        label{
            display:block;
            margin-bottom:6px;
            font-weight:600;
        }
        input{
            width:100%;
            padding:12px;
            border:1px solid #ccc;
            border-radius:10px;
            margin-bottom:16px;
            box-sizing:border-box;
        }
        button{
            width:100%;
            padding:12px;
            border:none;
            background:#22c55e;
            color:#fff;
            border-radius:10px;
            font-weight:700;
            cursor:pointer;
        }
        a{
            display:block;
            text-align:center;
            margin-top:14px;
            color:#2563eb;
            text-decoration:none;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Reset Password</h2>

        <?php if ($message): ?>
            <div class="msg"><?= htmlspecialchars($message) ?></div>
            <a href="login.php">Go to Login</a>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="err"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <label>New Password</label>
                <input type="password" name="password" required>

                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>

                <button type="submit">Reset Password</button>
            </form>

            <a href="login.php">Back to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
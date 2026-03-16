<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password | FitZone</title>

<style>
  body{
    margin:0;
    font-family:Arial,Helvetica,sans-serif;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#f2f4f7;
  }
  .box{
    width:100%;
    max-width:520px;
    background:#fff;
    border-radius:16px;
    padding:28px;
    box-shadow:0 15px 35px rgba(0,0,0,.12);
  }
  h1{margin:0 0 8px;}
  p{color:#555;line-height:1.5;}
  .note{
    margin-top:14px;
    background:#eef6ff;
    border:1px solid #cfe7ff;
    padding:12px;
    border-radius:12px;
  }
  a{color:#2563eb;font-weight:800;text-decoration:none;}
  a:hover{text-decoration:underline;}
</style>
</head>
<body>

<div class="box">
  <h1>Forgot Password 🔐</h1>
  <p>For this project version, password reset is handled by the administrator.</p>

  <div class="note">
    ✅ Please contact FitZone admin/staff to reset your password.<br>
    You can also create a new account using a different email.
  </div>

  <p style="margin-top:16px;">
    <a href="login.php">← Back to Login</a>
  </p>
</div>

</body>
</html>
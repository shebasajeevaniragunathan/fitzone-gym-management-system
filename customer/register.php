<?php
session_start();
require_once __DIR__ . "/config/db.php";

$name = "";
$email = "";
$phone = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if ($name === '') {
        $errors[] = "Full name is required.";
    }

    if ($email === '') {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if ($phone === '') {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = "Phone number must contain 10 to 15 digits only.";
    }

    if ($password === '') {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if ($confirm_password === '') {
        $errors[] = "Confirm password is required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check existing email
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "This email is already registered.";
        }
        $check->close();
    }

    // Insert user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = "customer";

        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);

        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - FitZone</title>
  <style>
    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
      font-family:Arial, Helvetica, sans-serif;
    }

    body{
      background:#f4f6f9;
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:30px 15px;
    }

    .auth-container{
      width:100%;
      max-width:460px;
      background:#fff;
      padding:35px 30px;
      border-radius:20px;
      box-shadow:0 10px 30px rgba(0,0,0,0.08);
    }

    .auth-container h2{
      text-align:center;
      color:#111827;
      margin-bottom:10px;
      font-size:30px;
    }

    .auth-container p{
      text-align:center;
      color:#6b7280;
      margin-bottom:25px;
      font-size:15px;
    }

    .form-group{
      margin-bottom:16px;
    }

    .form-group label{
      display:block;
      margin-bottom:7px;
      font-weight:600;
      color:#374151;
    }

    .form-group input{
      width:100%;
      padding:13px 14px;
      border:1px solid #d1d5db;
      border-radius:10px;
      outline:none;
      font-size:15px;
      transition:0.3s;
      background:#fff;
    }

    .form-group input:focus{
      border-color:#111827;
      box-shadow:0 0 0 3px rgba(17,24,39,0.08);
    }

    .btn{
      width:100%;
      border:none;
      background:#111827;
      color:#fff;
      padding:14px;
      border-radius:10px;
      font-size:16px;
      font-weight:700;
      cursor:pointer;
      transition:0.3s;
      margin-top:8px;
    }

    .btn:hover{
      background:#000;
    }

    .bottom-text{
      text-align:center;
      margin-top:18px;
      color:#6b7280;
      font-size:14px;
    }

    .bottom-text a{
      color:#111827;
      text-decoration:none;
      font-weight:700;
    }

    .error-box{
      background:#fee2e2;
      border:1px solid #fecaca;
      color:#991b1b;
      padding:14px 16px;
      border-radius:10px;
      margin-bottom:18px;
    }

    .error-box ul{
      padding-left:18px;
    }

    .error-box li{
      margin-bottom:5px;
    }
  </style>
</head>
<body>

  <div class="auth-container">
    <h2>Create Account</h2>
    <p>Register as a customer to access your FitZone dashboard</p>

    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Enter your full name">
      </div>

      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email">
      </div>

      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Enter your phone number">
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password">
      </div>

      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Re-enter password">
      </div>

      <button type="submit" class="btn">Register</button>
    </form>

    <div class="bottom-text">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </div>

</body>
</html>
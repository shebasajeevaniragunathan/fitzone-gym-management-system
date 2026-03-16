<?php
session_start();
require_once __DIR__ . "/config/db.php";

// If already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'customer') {
        header("Location: customer/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: dashboard/admin.php");
        exit;
    } elseif ($_SESSION['role'] === 'staff') {
        header("Location: dashboard/staff.php");
        exit;
    }
}

$email = "";
$error = "";
$success = "";

if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Registration successful! Please login.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("
            SELECT id, firstname, lastname, email, passwordhash, role, status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (!empty($user['status']) && strtolower($user['status']) !== 'active') {
                $error = "Your account is inactive. Please contact admin.";
            } elseif (password_verify($password, $user['passwordhash'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = strtolower(trim($user['role']));

                if ($_SESSION['role'] === 'customer') {
                    header("Location: customer/dashboard.php");
                    exit;
                } elseif ($_SESSION['role'] === 'admin') {
                    header("Location: dashboard/admin.php");
                    exit;
                } elseif ($_SESSION['role'] === 'staff') {
                    header("Location: dashboard/staff.php");
                    exit;
                } else {
                    header("Location: index.php");
                    exit;
                }

            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No account found with this email.";
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
  <title>Login - FitZone</title>
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
      max-width:430px;
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
      font-weight:600;
    }
    .success-box{
      background:#dcfce7;
      border:1px solid #bbf7d0;
      color:#166534;
      padding:14px 16px;
      border-radius:10px;
      margin-bottom:18px;
      font-weight:600;
    }
  </style>
</head>
<body>

  <div class="auth-container">
    <h2>Welcome Back</h2>
    <p>Login to continue to your FitZone account</p>

    <?php if ($success !== ''): ?>
      <div class="success-box"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email">
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password">
      </div>

      <button type="submit" class="btn">Login</button>
    </form>

    <div class="bottom-text">
      Don’t have an account? <a href="register.php">Register</a>
    </div>
  </div>

</body>
</html>
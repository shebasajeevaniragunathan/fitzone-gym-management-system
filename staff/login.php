<?php
session_start();
include "../db.php";

if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'staff') {
    header("Location: dashboard.php");
    exit;
}

/* =========================
   REMEMBER ME AUTO LOGIN
========================= */
if (!isset($_SESSION['staff_role']) && !empty($_COOKIE['staff_remember'])) {
    $remember_token = $_COOKIE['staff_remember'];

    $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE remember_token = ? AND role = 'staff' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $remember_token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            $_SESSION['staff_id']    = $user['id'];
            $_SESSION['staff_name']  = $user['name'];
            $_SESSION['staff_email'] = $user['email'];
            $_SESSION['staff_role']  = $user['role'];

            header("Location: dashboard.php");
            exit;
        }
    }
}

$error = "";
$success = "";
$email = "";
$max_attempts = 5;
$lock_minutes = 10;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']) ? 1 : 0;

    if ($email === "" && $password === "") {
        $error = "Email and password are required.";
    } elseif ($email === "") {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } elseif ($password === "") {
        $error = "Password is required.";
    } elseif (strlen($password) < 5) {
        $error = "Password must be at least 5 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, login_attempts, last_attempt_time FROM users WHERE email = ? AND role = 'staff' LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                $attempts = (int)$user['login_attempts'];
                $last_attempt_time = $user['last_attempt_time'];

                if ($attempts >= $max_attempts && $last_attempt_time) {
                    $last_time = strtotime($last_attempt_time);
                    $current_time = time();
                    $diff_minutes = ($current_time - $last_time) / 60;

                    if ($diff_minutes < $lock_minutes) {
                        $remaining = ceil($lock_minutes - $diff_minutes);
                        $error = "Too many failed login attempts. Try again after {$remaining} minute(s).";
                    } else {
                        $resetStmt = $conn->prepare("UPDATE users SET login_attempts = 0 WHERE id = ?");
                        $resetStmt->bind_param("i", $user['id']);
                        $resetStmt->execute();
                        $attempts = 0;
                    }
                }

                if ($error === "") {
                    // For hashed password use: password_verify($password, $user['password'])
                    if ($password === $user['password']) {
                        $_SESSION['staff_id']    = $user['id'];
                        $_SESSION['staff_name']  = $user['name'];
                        $_SESSION['staff_email'] = $user['email'];
                        $_SESSION['staff_role']  = $user['role'];

                        $resetStmt = $conn->prepare("UPDATE users SET login_attempts = 0, last_attempt_time = NULL WHERE id = ?");
                        $resetStmt->bind_param("i", $user['id']);
                        $resetStmt->execute();

                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $upd = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                            $upd->bind_param("si", $token, $user['id']);
                            $upd->execute();

                            setcookie("staff_remember", $token, time() + (86400 * 30), "/", "", false, true);
                        } else {
                            setcookie("staff_remember", "", time() - 3600, "/");
                        }

                        $_SESSION['login_success'] = "Login successful! Welcome back.";
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        $new_attempts = $attempts + 1;
                        $now = date("Y-m-d H:i:s");

                        $upd = $conn->prepare("UPDATE users SET login_attempts = ?, last_attempt_time = ? WHERE id = ?");
                        $upd->bind_param("isi", $new_attempts, $now, $user['id']);
                        $upd->execute();

                        $remaining_attempts = $max_attempts - $new_attempts;
                        if ($remaining_attempts > 0) {
                            $error = "Incorrect password. You have {$remaining_attempts} attempt(s) left.";
                        } else {
                            $error = "Too many failed login attempts. Try again after {$lock_minutes} minutes.";
                        }
                    }
                }
            } else {
                $error = "No staff account found with this email.";
            }

            $stmt->close();
        } else {
            $error = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Login - FitZone</title>
  <style>
    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
      font-family:Arial, sans-serif;
    }

    body{
      min-height:100vh;
      background:#f6f8fb;
      display:flex;
      justify-content:center;
      align-items:center;
      padding:20px;
    }

    .login-box{
      width:100%;
      max-width:440px;
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:18px;
      box-shadow:0 10px 30px rgba(0,0,0,0.06);
      padding:35px 30px;
    }

    .login-box h2{
      text-align:center;
      color:#111827;
      margin-bottom:8px;
      font-size:28px;
    }

    .login-box p{
      text-align:center;
      color:#6b7280;
      margin-bottom:25px;
      font-size:14px;
    }

    .form-group{
      margin-bottom:18px;
    }

    label{
      display:block;
      margin-bottom:8px;
      font-size:14px;
      font-weight:600;
      color:#374151;
    }

    input[type="email"],
    input[type="password"],
    input[type="text"]{
      width:100%;
      padding:13px 14px;
      border:1px solid #d1d5db;
      border-radius:10px;
      outline:none;
      font-size:14px;
      transition:0.3s ease;
      background:#fff;
    }

    input:focus{
      border-color:#111827;
      box-shadow:0 0 0 3px rgba(17,24,39,0.08);
    }

    .password-wrap{
      position:relative;
    }

    .toggle-password{
      position:absolute;
      right:14px;
      top:50%;
      transform:translateY(-50%);
      cursor:pointer;
      font-size:18px;
      user-select:none;
    }

    .options{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:18px;
      gap:10px;
      flex-wrap:wrap;
    }

    .remember{
      display:flex;
      align-items:center;
      gap:8px;
      font-size:14px;
      color:#374151;
    }

    .forgot-link{
      font-size:14px;
      color:#111827;
      text-decoration:none;
      font-weight:600;
    }

    .forgot-link:hover{
      text-decoration:underline;
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
      transition:0.3s ease;
      margin-top:5px;
    }

    .btn:hover{
      background:#1f2937;
    }

    .btn:disabled{
      opacity:0.7;
      cursor:not-allowed;
    }

    .note{
      margin-top:18px;
      text-align:center;
      font-size:13px;
      color:#9ca3af;
    }

    .popup{
      position:fixed;
      top:20px;
      right:20px;
      min-width:280px;
      max-width:350px;
      padding:14px 16px;
      border-radius:12px;
      color:#fff;
      box-shadow:0 10px 20px rgba(0,0,0,0.15);
      z-index:9999;
      display:none;
      animation:slideIn 0.3s ease;
      font-size:14px;
      font-weight:600;
    }

    .popup.success{
      background:#16a34a;
    }

    .popup.error{
      background:#dc2626;
    }

    @keyframes slideIn{
      from{
        opacity:0;
        transform:translateY(-10px);
      }
      to{
        opacity:1;
        transform:translateY(0);
      }
    }
  </style>
</head>
<body>

<div id="popup" class="popup"></div>

<div class="login-box">
  <h2>Staff Login</h2>
  <p>Login to access your dashboard</p>

  <form method="POST" action="" id="loginForm">
    <div class="form-group">
      <label for="email">Email Address</label>
      <input
        type="email"
        id="email"
        name="email"
        placeholder="Enter your email"
        value="<?php echo htmlspecialchars($email); ?>"
        required
      >
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <div class="password-wrap">
        <input
          type="password"
          id="password"
          name="password"
          placeholder="Enter your password"
          required
        >
        <span class="toggle-password" id="togglePassword">👁️</span>
      </div>
    </div>

    <div class="options">
      <label class="remember">
        <input type="checkbox" name="remember">
        Remember me
      </label>

      <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
    </div>

    <button type="submit" class="btn" id="loginBtn">Login</button>
  </form>

  <div class="note">FitZone Staff Panel</div>
</div>

<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const loginForm = document.getElementById('loginForm');
  const loginBtn = document.getElementById('loginBtn');
  const popup = document.getElementById('popup');

  togglePassword.addEventListener('click', function () {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.textContent = type === 'password' ? '👁️' : '🙈';
  });

  loginForm.addEventListener('submit', function () {
    loginBtn.disabled = true;
    loginBtn.textContent = 'Logging in...';
  });

  function showPopup(message, type) {
    popup.textContent = message;
    popup.className = 'popup ' + type;
    popup.style.display = 'block';

    setTimeout(() => {
      popup.style.display = 'none';
    }, 3500);
  }

  <?php if ($error !== ""): ?>
    showPopup(<?php echo json_encode($error); ?>, 'error');
  <?php endif; ?>

  <?php if ($success !== ""): ?>
    showPopup(<?php echo json_encode($success); ?>, 'success');
  <?php endif; ?>
</script>

</body>
</html>
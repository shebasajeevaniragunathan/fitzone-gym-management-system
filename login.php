<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . "/config/db.php";

/* ---------------------------
   Auto login with remember token
--------------------------- */
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['fitzone_remember'])) {
    $rememberToken = trim($_COOKIE['fitzone_remember']);

    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, full_name, email, role, status, remember_token
        FROM users
        WHERE remember_token = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("s", $rememberToken);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && $user['status'] === 'active') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['first_name'] ?? '';
            $_SESSION['lastname'] = $user['last_name'] ?? '';
            $_SESSION['name'] = !empty($user['full_name'])
                ? $user['full_name']
                : trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            $role = strtolower(trim($user['role'] ?? ''));

            if ($role === 'admin') {
                header("Location: /fitzone/dashboard/admin.php");
                exit;
            } elseif ($role === 'staff') {
                header("Location: /fitzone/staff/dashboard.php");
                exit;
            } elseif ($role === 'customer') {
                header("Location: /fitzone/customer/dashboard.php");
                exit;
            } else {
                header("Location: /fitzone/index.php");
                exit;
            }
        } else {
            setcookie("fitzone_remember", "", time() - 3600, "/");
        }
    }
}

/* ---------------------------
   If already logged in
--------------------------- */
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = strtolower(trim($_SESSION['role'] ?? ''));

    if ($role === 'admin') {
        header("Location: /fitzone/dashboard/admin.php");
        exit;
    } elseif ($role === 'staff') {
        header("Location: /fitzone/staff/dashboard.php");
        exit;
    } elseif ($role === 'customer') {
        header("Location: /fitzone/customer/dashboard.php");
        exit;
    } else {
        header("Location: /fitzone/index.php");
        exit;
    }
}

$error = "";
$success = "";
$email = "";

/* ---------------------------
   Handle Login
--------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rememberMe = isset($_POST['remember_me']);

    if ($email === '' || $password === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, full_name, email, password_hash, role, status, login_attempts, last_attempt_time
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user) {
                if ($user['status'] !== 'active') {
                    $error = "Your account is blocked. Please contact admin.";
                } else {
                    $attempts = (int)($user['login_attempts'] ?? 0);
                    $lastAttemptTime = $user['last_attempt_time'] ?? null;

                    if ($attempts >= 5 && !empty($lastAttemptTime)) {
                        $lastAttemptTs = strtotime($lastAttemptTime);
                        $lockSeconds = 15 * 60; // 15 mins

                        if ($lastAttemptTs && (time() - $lastAttemptTs) < $lockSeconds) {
                            $remaining = ceil(($lockSeconds - (time() - $lastAttemptTs)) / 60);
                            $error = "Too many failed attempts. Try again in {$remaining} minute(s).";
                        } else {
                            $resetStmt = $conn->prepare("
                                UPDATE users
                                SET login_attempts = 0, last_attempt_time = NULL
                                WHERE id = ?
                            ");
                            if ($resetStmt) {
                                $resetStmt->bind_param("i", $user['id']);
                                $resetStmt->execute();
                                $resetStmt->close();
                            }
                            $attempts = 0;
                        }
                    }

                    if ($error === "") {
                        $dbPassword = $user['password_hash'] ?? '';
                        $validPassword = password_verify($password, $dbPassword) || $password === $dbPassword;

                        if ($validPassword) {
                            $displayName = !empty($user['full_name'])
                                ? $user['full_name']
                                : trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

                            if ($displayName === '') {
                                $displayName = $user['email'];
                            }

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['firstname'] = $user['first_name'] ?? '';
                            $_SESSION['lastname'] = $user['last_name'] ?? '';
                            $_SESSION['name'] = $displayName;
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['role'] = $user['role'];

                            $upd = $conn->prepare("
                                UPDATE users
                                SET login_attempts = 0,
                                    last_attempt_time = NULL,
                                    last_login = NOW()
                                WHERE id = ?
                            ");
                            if ($upd) {
                                $upd->bind_param("i", $user['id']);
                                $upd->execute();
                                $upd->close();
                            }

                            if ($rememberMe) {
                                $rememberToken = bin2hex(random_bytes(32));

                                $rememberStmt = $conn->prepare("
                                    UPDATE users
                                    SET remember_token = ?
                                    WHERE id = ?
                                ");
                                if ($rememberStmt) {
                                    $rememberStmt->bind_param("si", $rememberToken, $user['id']);
                                    $rememberStmt->execute();
                                    $rememberStmt->close();
                                }

                                setcookie(
                                    "fitzone_remember",
                                    $rememberToken,
                                    time() + (60 * 60 * 24 * 30),
                                    "/",
                                    "",
                                    false,
                                    true
                                );
                            } else {
                                setcookie("fitzone_remember", "", time() - 3600, "/");
                            }

                            $role = strtolower(trim($user['role'] ?? ''));

                            if ($role === 'admin') {
                                header("Location: /fitzone/dashboard/admin.php");
                                exit;
                            } elseif ($role === 'staff') {
                                header("Location: /fitzone/staff/dashboard.php");
                                exit;
                            } elseif ($role === 'customer') {
                                header("Location: /fitzone/customer/dashboard.php");
                                exit;
                            } else {
                                header("Location: /fitzone/index.php");
                                exit;
                            }
                        } else {
                            $upd = $conn->prepare("
                                UPDATE users
                                SET login_attempts = login_attempts + 1,
                                    last_attempt_time = NOW()
                                WHERE id = ?
                            ");
                            if ($upd) {
                                $upd->bind_param("i", $user['id']);
                                $upd->execute();
                                $upd->close();
                            }

                            $error = "Invalid email or password.";
                        }
                    }
                }
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Database query error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | FitZone</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
    body{background:#f4f7fb;}
    .login-page{
      min-height:calc(100vh - 90px);
      display:flex;align-items:center;justify-content:center;
      padding:40px 20px;
      background:
        linear-gradient(rgba(15,23,42,0.55), rgba(15,23,42,0.55)),
        url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1600&q=80');
      background-size:cover;background-position:center;
    }
    .login-card{
      width:100%;max-width:440px;background:rgba(255,255,255,0.97);
      border-radius:24px;padding:32px;box-shadow:0 20px 50px rgba(0,0,0,0.15);
    }
    .login-card h2{font-size:32px;color:#0f172a;margin-bottom:8px;text-align:center;}
    .login-card .sub{text-align:center;color:#64748b;margin-bottom:22px;font-size:14px;}
    .field{margin-bottom:18px;}
    .field label{display:block;margin-bottom:8px;color:#0f172a;font-weight:600;font-size:14px;}
    .input-wrap{position:relative;}
    .input-wrap input{
      width:100%;height:50px;border:1px solid #d1d5db;border-radius:14px;
      padding:0 14px;font-size:15px;outline:none;transition:0.2s ease;background:#fff;
    }
    .input-wrap input:focus{border-color:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,0.12);}
    .password-input{padding-right:48px !important;}
    .toggle-password{
      position:absolute;top:50%;right:14px;transform:translateY(-50%);
      background:none;border:none;cursor:pointer;color:#64748b;font-size:16px;
    }
    .toggle-password:hover{color:#111827;}
    .form-row{
      display:flex;justify-content:space-between;align-items:center;
      gap:12px;margin-bottom:18px;flex-wrap:wrap;
    }
    .remember-me{display:flex;align-items:center;gap:8px;color:#374151;font-size:14px;}
    .remember-me input{width:16px;height:16px;accent-color:#22c55e;}
    .forgot-link{color:#2563eb;font-size:14px;font-weight:600;text-decoration:none;}
    .forgot-link:hover{text-decoration:underline;}
    .login-btn{
      width:100%;height:50px;border:none;border-radius:14px;background:#22c55e;
      color:#fff;font-size:16px;font-weight:700;cursor:pointer;transition:0.2s ease;
    }
    .login-btn:hover{background:#16a34a;}
    .login-btn.loading{opacity:.8;pointer-events:none;}
    .register-text{text-align:center;margin-top:18px;color:#64748b;font-size:14px;}
    .register-text a{color:#2563eb;text-decoration:none;font-weight:700;}
    .register-text a:hover{text-decoration:underline;}
    @media (max-width:500px){
      .login-card{padding:24px 18px;}
      .login-card h2{font-size:26px;}
    }
  </style>
</head>
<body>

<?php include __DIR__ . "/includes/navbar.php"; ?>

<div class="login-page">
  <form method="POST" class="login-card" autocomplete="off" novalidate id="loginForm">
    <h2>Welcome Back</h2>
    <p class="sub">Login to continue to FitZone</p>

    <input type="text" name="fakeusernameremembered" style="display:none;">
    <input type="password" name="fakepasswordremembered" style="display:none;">

    <div class="field">
      <label for="email">Email Address</label>
      <div class="input-wrap">
        <input
          type="email"
          id="email"
          name="email"
          value="<?= htmlspecialchars($email) ?>"
          placeholder="Enter your email"
          autocomplete="off"
          autocapitalize="off"
          spellcheck="false"
          required
        >
      </div>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <div class="input-wrap">
        <input
          type="password"
          id="password"
          name="password"
          class="password-input"
          placeholder="Enter your password"
          autocomplete="new-password"
          required
          minlength="6"
        >
        <button type="button" class="toggle-password" id="togglePassword">
          <i class="fa-solid fa-eye"></i>
        </button>
      </div>
    </div>

    <div class="form-row">
      <label class="remember-me">
        <input type="checkbox" name="remember_me">
        <span>Remember me</span>
      </label>

      <a href="/fitzone/forgot-password.php" class="forgot-link">Forgot password?</a>
    </div>

    <button type="submit" class="login-btn" id="loginBtn">Login</button>

    <p class="register-text">
      Don’t have an account?
      <a href="/fitzone/register.php">Register</a>
    </p>
  </form>
</div>

<?php if ($error): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Login Failed',
  text: <?= json_encode($error) ?>,
  confirmButtonColor: '#22c55e'
});
</script>
<?php endif; ?>

<?php if ($success): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Success',
  text: <?= json_encode($success) ?>,
  confirmButtonColor: '#22c55e'
});
</script>
<?php endif; ?>

<script>
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const loginForm = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const loginBtn = document.getElementById('loginBtn');

togglePassword.addEventListener('click', function () {
  const icon = this.querySelector('i');
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    passwordInput.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
});

loginForm.addEventListener('submit', function(e) {
  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();

  if (email === '' || password === '') {
    e.preventDefault();
    Swal.fire({icon:'warning', title:'Missing Fields', text:'All fields are required.', confirmButtonColor:'#22c55e'});
    return;
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(email)) {
    e.preventDefault();
    Swal.fire({icon:'warning', title:'Invalid Email', text:'Please enter a valid email address.', confirmButtonColor:'#22c55e'});
    emailInput.focus();
    return;
  }

  if (password.length < 6) {
    e.preventDefault();
    Swal.fire({icon:'warning', title:'Weak Password', text:'Password must be at least 6 characters.', confirmButtonColor:'#22c55e'});
    passwordInput.focus();
    return;
  }

  loginBtn.classList.add('loading');
  loginBtn.innerText = 'Logging in...';
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>


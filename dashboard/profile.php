<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* -----------------------------
   HELPERS
----------------------------- */
function columnExists(mysqli $conn, string $table, string $column): bool {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

$userId = (int)$_SESSION['user_id'];
$message = "";
$error = "";

/* -----------------------------
   DETECT COLUMNS
----------------------------- */
$nameCol = null;
if (columnExists($conn, 'users', 'full_name')) {
    $nameCol = 'full_name';
} elseif (columnExists($conn, 'users', 'name')) {
    $nameCol = 'name';
}

$passCol = columnExists($conn, 'users', 'password_hash') ? 'password_hash' : 'password';
$hasPhone = columnExists($conn, 'users', 'phone');
$hasProfileImage = columnExists($conn, 'users', 'profile_image');

/* -----------------------------
   FETCH USER
----------------------------- */
$sql = "SELECT id, email, role";

if ($nameCol) {
    $sql .= ", $nameCol AS display_name";
}

if ($hasPhone) {
    $sql .= ", phone";
}

if ($hasProfileImage) {
    $sql .= ", profile_image";
}

$sql .= ", $passCol AS pass FROM users WHERE id=? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Admin account not found.");
}

/* -----------------------------
   UPLOAD SETTINGS
----------------------------- */
$uploadDirFs = realpath(__DIR__ . "/..") . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "profile" . DIRECTORY_SEPARATOR;
$uploadDirDb = "../uploads/profile/";

/* create folder if not exists */
if (!is_dir($uploadDirFs)) {
    @mkdir($uploadDirFs, 0777, true);
}

/* -----------------------------
   UPDATE PROFILE
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['display_name'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');

    $profileImageName = $user['profile_image'] ?? null;

    /* image upload */
    if ($hasProfileImage && isset($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])) {
        $file = $_FILES['profile_image'];

        if ($file['error'] === 0) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                $error = "Only JPG, JPEG, PNG, and WEBP images are allowed.";
            } elseif (($file['size'] ?? 0) > 2 * 1024 * 1024) {
                $error = "Image size must be less than 2MB.";
            } else {
                $safeFileName = "admin_" . $userId . "_" . time() . "." . $ext;
                $targetFs = $uploadDirFs . $safeFileName;

                if (move_uploaded_file($file['tmp_name'], $targetFs)) {
                    $profileImageName = $safeFileName;
                } else {
                    $error = "Failed to upload profile image.";
                }
            }
        } else {
            $error = "Error while uploading image.";
        }
    }

    if ($error === "") {
        $fields = [];
        $types = "";
        $values = [];

        if ($nameCol) {
            $fields[] = "$nameCol=?";
            $types .= "s";
            $values[] = $newName;
        }

        if ($hasPhone) {
            $fields[] = "phone=?";
            $types .= "s";
            $values[] = $newPhone;
        }

        if ($hasProfileImage) {
            $fields[] = "profile_image=?";
            $types .= "s";
            $values[] = $profileImageName;
        }

        if (!empty($fields)) {
            $sqlUpdate = "UPDATE users SET " . implode(", ", $fields) . " WHERE id=?";
            $types .= "i";
            $values[] = $userId;

            $upd = $conn->prepare($sqlUpdate);
            $upd->bind_param($types, ...$values);

            if ($upd->execute()) {
                $message = "Profile updated successfully.";
                $_SESSION['name'] = $newName !== '' ? $newName : ($_SESSION['name'] ?? 'Admin');

                /* refresh user data */
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $error = "Failed to update profile.";
            }
            $upd->close();
        }
    }
}

/* -----------------------------
   CHANGE PASSWORD
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $dbPass = $user['pass'] ?? '';

    $ok = false;
    if ($dbPass !== '') {
        $ok = password_verify($current, $dbPass) || hash_equals((string)$dbPass, (string)$current);
    }

    if (!$ok) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirm password do not match.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET $passCol=? WHERE id=?");
        $upd->bind_param("si", $hashed, $userId);

        if ($upd->execute()) {
            $message = "Password changed successfully.";
        } else {
            $error = "Failed to change password.";
        }
        $upd->close();
    }
}

/* -----------------------------
   PROFILE IMAGE URL
----------------------------- */
$profileImageUrl = "";
if ($hasProfileImage && !empty($user['profile_image'])) {
    $profileImageUrl = $uploadDirDb . rawurlencode($user['profile_image']);
}

$displayNameValue = $user['display_name'] ?? ($_SESSION['name'] ?? 'Admin');
$phoneValue = $user['phone'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Settings | FitZone</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    *{
      box-sizing:border-box;
      margin:0;
      padding:0;
      font-family:'Poppins',sans-serif;
    }

    body{
      background:#f4f7fb;
      color:#0f172a;
    }

    .wrap{
      max-width:1200px;
      margin:30px auto;
      padding:20px;
    }

    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:14px;
      flex-wrap:wrap;
      margin-bottom:24px;
    }

    .page-title{
      display:flex;
      align-items:center;
      gap:12px;
    }

    .page-title h1{
      font-size:38px;
      font-weight:800;
      color:#0b1739;
    }

    .back-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:14px 22px;
      border-radius:14px;
      background:#0f172a;
      color:#fff;
      font-weight:700;
      text-decoration:none;
      transition:.2s ease;
    }

    .back-btn:hover{
      transform:translateY(-1px);
    }

    .alert{
      padding:14px 16px;
      border-radius:14px;
      margin-bottom:18px;
      font-weight:600;
    }

    .alert-success{
      background:#dcfce7;
      color:#166534;
      border:1px solid #bbf7d0;
    }

    .alert-error{
      background:#fee2e2;
      color:#991b1b;
      border:1px solid #fecaca;
    }

    .grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:22px;
    }

    .card{
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:22px;
      padding:24px;
      box-shadow:0 10px 28px rgba(15,23,42,.05);
    }

    .card h2{
      font-size:22px;
      margin-bottom:18px;
      color:#0b1739;
    }

    .profile-top{
      display:flex;
      align-items:center;
      gap:18px;
      flex-wrap:wrap;
      margin-bottom:20px;
    }

    .avatar{
      width:110px;
      height:110px;
      border-radius:50%;
      overflow:hidden;
      border:4px solid #e5e7eb;
      background:linear-gradient(135deg,#dbeafe,#ede9fe);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:40px;
      font-weight:800;
      color:#334155;
      flex-shrink:0;
    }

    .avatar img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }

    .profile-meta h3{
      font-size:22px;
      margin-bottom:6px;
      color:#0f172a;
    }

    .profile-meta p{
      color:#64748b;
      margin-bottom:4px;
      font-size:14px;
    }

    .info-box{
      background:#f8fafc;
      border:1px solid #e5e7eb;
      border-radius:16px;
      padding:16px;
      margin-bottom:18px;
      line-height:2;
    }

    .info-box strong{
      color:#0f172a;
    }

    .form-group{
      margin-bottom:16px;
    }

    .form-group label{
      display:block;
      margin-bottom:8px;
      font-weight:600;
      color:#334155;
    }

    .input,
    .file-input{
      width:100%;
      padding:14px 15px;
      border:1px solid #d1d5db;
      border-radius:14px;
      background:#fff;
      font-size:15px;
      outline:none;
      transition:border-color .2s ease, box-shadow .2s ease;
    }

    .input:focus,
    .file-input:focus{
      border-color:#3b82f6;
      box-shadow:0 0 0 4px rgba(59,130,246,.12);
    }

    .password-wrap{
      position:relative;
    }

    .password-wrap .input{
      padding-right:52px;
    }

    .toggle-btn{
      position:absolute;
      right:12px;
      top:50%;
      transform:translateY(-50%);
      border:none;
      background:transparent;
      cursor:pointer;
      font-size:18px;
    }

    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:13px 20px;
      border:none;
      border-radius:14px;
      font-weight:700;
      cursor:pointer;
      transition:.2s ease;
    }

    .btn-green{
      background:#22c55e;
      color:#062d16;
    }

    .btn-green:hover{
      transform:translateY(-1px);
    }

    .hint{
      font-size:13px;
      color:#64748b;
      margin-top:6px;
    }

    @media (max-width: 900px){
      .grid{
        grid-template-columns:1fr;
      }

      .page-title h1{
        font-size:30px;
      }
    }
  </style>
</head>
<body>

<div class="wrap">

  <div class="topbar">
    <div class="page-title">
      <span style="font-size:34px;">⚙️</span>
      <h1>Profile Settings</h1>
    </div>

    <a href="admin.php" class="back-btn">← Back to Dashboard</a>
  </div>

  <?php if ($message !== ""): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($error !== ""): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="grid">

    <!-- PROFILE INFO -->
    <div class="card">
      <h2>Profile Information</h2>

      <div class="profile-top">
        <div class="avatar">
          <?php if ($profileImageUrl !== ""): ?>
            <img src="<?= htmlspecialchars($profileImageUrl) ?>" alt="Profile Image">
          <?php else: ?>
            <?= htmlspecialchars(strtoupper(substr($displayNameValue, 0, 1))) ?>
          <?php endif; ?>
        </div>

        <div class="profile-meta">
          <h3><?= htmlspecialchars($displayNameValue) ?></h3>
          <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
          <p>Role: <?= htmlspecialchars(ucfirst($user['role'] ?? 'admin')) ?></p>
        </div>
      </div>

      <div class="info-box">
        <div><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '') ?></div>
        <div><strong>Role:</strong> <?= htmlspecialchars(ucfirst($user['role'] ?? 'admin')) ?></div>
        <div><strong>Phone:</strong> <?= htmlspecialchars($phoneValue !== '' ? $phoneValue : 'Not added') ?></div>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="display_name">Display Name</label>
          <input
            type="text"
            id="display_name"
            name="display_name"
            class="input"
            value="<?= htmlspecialchars($displayNameValue) ?>"
            placeholder="Enter display name"
          >
        </div>

        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input
            type="text"
            id="phone"
            name="phone"
            class="input"
            value="<?= htmlspecialchars($phoneValue) ?>"
            placeholder="Enter phone number"
          >
        </div>

        <div class="form-group">
          <label for="profile_image">Profile Image</label>
          <input
            type="file"
            id="profile_image"
            name="profile_image"
            class="file-input"
            accept=".jpg,.jpeg,.png,.webp"
          >
          <div class="hint">Allowed: JPG, JPEG, PNG, WEBP. Max size: 2MB</div>
        </div>

        <button type="submit" name="update_profile" class="btn btn-green">Update Profile</button>
      </form>
    </div>

    <!-- CHANGE PASSWORD -->
    <div class="card">
      <h2>Change Password</h2>

      <form method="POST" autocomplete="off">

        <div class="form-group">
          <label for="current_password">Current Password</label>
          <div class="password-wrap">
            <input type="password" id="current_password" name="current_password" class="input" required>
            <button type="button" class="toggle-btn" onclick="togglePassword('current_password', this)">👁️</button>
          </div>
        </div>

        <div class="form-group">
          <label for="new_password">New Password</label>
          <div class="password-wrap">
            <input type="password" id="new_password" name="new_password" class="input" minlength="8" required>
            <button type="button" class="toggle-btn" onclick="togglePassword('new_password', this)">👁️</button>
          </div>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <div class="password-wrap">
            <input type="password" id="confirm_password" name="confirm_password" class="input" minlength="8" required>
            <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password', this)">👁️</button>
          </div>
        </div>

        <button type="submit" name="change_password" class="btn btn-green">Change Password</button>
      </form>
    </div>

  </div>
</div>

<script>
function togglePassword(id, btn){
  const input = document.getElementById(id);
  if (!input) return;

  if (input.type === "password") {
    input.type = "text";
    btn.textContent = "🙈";
  } else {
    input.type = "password";
    btn.textContent = "👁️";
  }
}
</script>

</body>
</html>
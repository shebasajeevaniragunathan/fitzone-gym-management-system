<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$message = "";
$error = "";
$show_popup = false;

$stmt = $conn->prepare("SELECT id, first_name, last_name, full_name, email, phone, profile_image, password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Customer not found.");
}

/* =========================
   UPDATE PROFILE
========================= */
if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    if ($first_name === '') {
        $error = "First name is required.";
    } elseif ($last_name === '') {
        $error = "Last name is required.";
    } elseif ($phone === '') {
        $error = "Phone number is required.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $error = "Phone number must contain 10 to 15 digits only.";
    } else {
        $full_name = trim($first_name . ' ' . $last_name);
        $profile_image = $user['profile_image'] ?? null;

        if (isset($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            $file_name = $_FILES['profile_image']['name'];
            $file_tmp  = $_FILES['profile_image']['tmp_name'];
            $file_size = (int)$_FILES['profile_image']['size'];

            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_ext)) {
                $error = "Only JPG, JPEG, PNG, and WEBP images are allowed.";
            } elseif ($file_size > 2 * 1024 * 1024) {
                $error = "Profile image size must be less than 2MB.";
            } else {
                $new_file_name = "customer_" . $user_id . "_" . time() . "." . $ext;
                $upload_dir = __DIR__ . "/uploads/";
                $upload_path = $upload_dir . $new_file_name;

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    if (!empty($user['profile_image']) && file_exists(__DIR__ . "/" . $user['profile_image'])) {
                        @unlink(__DIR__ . "/" . $user['profile_image']);
                    }
                    $profile_image = "uploads/" . $new_file_name;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if ($error === "") {
            $update = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, full_name = ?, phone = ?, profile_image = ? WHERE id = ?");
            $update->bind_param("sssssi", $first_name, $last_name, $full_name, $phone, $profile_image, $user_id);

            if ($update->execute()) {
                $_SESSION['name'] = $full_name;
                $message = "Profile updated successfully.";
                $show_popup = true;
            } else {
                $error = "Failed to update profile. Please try again.";
            }
            $update->close();
        }
    }
}

/* =========================
   CHANGE PASSWORD
========================= */
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $error = "All password fields are required.";
    } elseif (!password_verify($current_password, $user['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $pass_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $pass_stmt->bind_param("si", $new_hash, $user_id);

        if ($pass_stmt->execute()) {
            $message = "Password changed successfully.";
            $show_popup = true;
        } else {
            $error = "Failed to change password. Please try again.";
        }
        $pass_stmt->close();
    }
}

/* =========================
   REFRESH USER DATA
========================= */
$stmt = $conn->prepare("SELECT id, first_name, last_name, full_name, email, phone, profile_image, password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$back_url = $_SERVER['HTTP_REFERER'] ?? 'profile.php';
$photo_path = !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile - FitZone</title>
  <link rel="stylesheet" href="customer-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="main-content">

  <div class="profile-page-header">
    <div>
      <h1>Edit Profile</h1>
      <p>Update your personal details, profile photo, and password.</p>
    </div>

   <a href="profile.php" class="back-btn">
    <i class="fa-solid fa-arrow-left"></i> Back
   </a>
  </div>

  <?php if ($message !== ''): ?>
    <div class="success-box"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <div class="edit-grid">

    <!-- Update Profile -->
    <div class="edit-card">
      <h3><i class="fa-solid fa-user-pen"></i> Update Profile</h3>

      <form method="POST" class="profile-form" enctype="multipart/form-data">
        
        <div class="image-upload-wrap">
          <div class="profile-preview">
            <?php if ($photo_path !== ''): ?>
              <img src="<?php echo $photo_path; ?>" alt="Profile Photo">
            <?php else: ?>
              <div class="default-avatar">
                <i class="fa-solid fa-user"></i>
              </div>
            <?php endif; ?>
          </div>

          <label>Profile Photo</label>
          <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">
          <small>Allowed: JPG, JPEG, PNG, WEBP (Max: 2MB)</small>
        </div>

        <label>First Name</label>
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">

        <label>Last Name</label>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">

        <label>Email Address</label>
        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>

        <label>Phone Number</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">

        <div class="form-actions">
          <button type="submit" name="update_profile">
            <i class="fa-solid fa-floppy-disk"></i> Update Profile
          </button>

          <a href="<?php echo htmlspecialchars($back_url); ?>" class="cancel-btn">
            <i class="fa-solid fa-xmark"></i> Cancel
          </a>
        </div>
      </form>
    </div>

    <!-- Change Password -->
    <div class="edit-card">
      <h3><i class="fa-solid fa-lock"></i> Change Password</h3>

      <form method="POST" class="profile-form">
        <label>Current Password</label>
        <div class="password-field">
          <input type="password" name="current_password" id="current_password" placeholder="Enter current password">
          <span class="toggle-password" onclick="togglePassword('current_password', this)">
            <i class="fa-solid fa-eye"></i>
          </span>
        </div>

        <label>New Password</label>
        <div class="password-field">
          <input type="password" name="new_password" id="new_password" placeholder="Enter new password">
          <span class="toggle-password" onclick="togglePassword('new_password', this)">
            <i class="fa-solid fa-eye"></i>
          </span>
        </div>

        <label>Confirm New Password</label>
        <div class="password-field">
          <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password">
          <span class="toggle-password" onclick="togglePassword('confirm_password', this)">
            <i class="fa-solid fa-eye"></i>
          </span>
        </div>

        <div class="form-actions">
          <button type="submit" name="change_password">
            <i class="fa-solid fa-key"></i> Change Password
          </button>

          <a href="<?php echo htmlspecialchars($back_url); ?>" class="cancel-btn">
            <i class="fa-solid fa-xmark"></i> Cancel
          </a>
        </div>
      </form>
    </div>

  </div>

</div>

<?php if ($show_popup): ?>
<div class="popup-overlay" id="successPopup">
  <div class="popup-box">
    <div class="popup-icon">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <h3>Success</h3>
    <p><?php echo htmlspecialchars($message); ?></p>
    <button onclick="closePopup()">OK</button>
  </div>
</div>
<?php endif; ?>

<script>
function togglePassword(inputId, el) {
  const input = document.getElementById(inputId);
  const icon = el.querySelector("i");

  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

function closePopup() {
  const popup = document.getElementById("successPopup");
  if (popup) {
    popup.style.display = "none";
  }
}
</script>

</body>
</html>
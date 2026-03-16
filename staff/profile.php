<?php
require_once __DIR__ . "/includes/auth.php";

$userId = (int)$_SESSION['user_id'];
$message = "";
$error = "";

/* =========================
   ADD profile_image COLUMN IF MISSING
========================= */
$checkProfileImage = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
if ($checkProfileImage && $checkProfileImage->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD profile_image VARCHAR(255) NULL AFTER phone");
}

/* =========================
   ADD last_login COLUMN IF MISSING
========================= */
$checkLastLogin = $conn->query("SHOW COLUMNS FROM users LIKE 'last_login'");
if ($checkLastLogin && $checkLastLogin->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD last_login DATETIME NULL AFTER status");
}

/* =========================
   FETCH CURRENT USER
========================= */
$stmt = $conn->prepare("
    SELECT id, first_name, last_name, email, phone, role, status, profile_image, created_at, last_login, password_hash
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: /fitzone/logout.php");
    exit;
}

/* =========================
   UPDATE PROFILE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $profileImageName = $user['profile_image'] ?? null;

    if ($firstName === '' || $lastName === '') {
        $error = "First name and last name are required.";
    } else {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $uploadDir = dirname(__DIR__) . "/uploads/staff/";

            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                    $error = "Failed to create upload folder.";
                }
            }

            if ($error === "") {
                $tmpName  = $_FILES['profile_image']['tmp_name'];
                $fileName = $_FILES['profile_image']['name'];
                $fileSize = $_FILES['profile_image']['size'];

                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $allowed, true)) {
                    $error = "Only JPG, JPEG, PNG and WEBP files are allowed.";
                } elseif ($fileSize > 2 * 1024 * 1024) {
                    $error = "Image size must be less than 2MB.";
                } else {
                    $newFileName = "staff_" . $userId . "_" . time() . "." . $ext;
                    $destination = $uploadDir . $newFileName;

                    if (move_uploaded_file($tmpName, $destination)) {
                        if (!empty($user['profile_image'])) {
                            $oldPath = $uploadDir . $user['profile_image'];
                            if (file_exists($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                        $profileImageName = $newFileName;
                    } else {
                        $error = "Failed to upload profile image.";
                    }
                }
            }
        }

        if ($error === "") {
            $stmt = $conn->prepare("
                UPDATE users
                SET first_name = ?, last_name = ?, phone = ?, profile_image = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssi", $firstName, $lastName, $phone, $profileImageName, $userId);

            if ($stmt->execute()) {
                $_SESSION['firstname'] = $firstName;
                $_SESSION['lastname'] = $lastName;
                $_SESSION['name'] = trim($firstName . ' ' . $lastName);
                $message = "Profile updated successfully.";
            } else {
                $error = "Failed to update profile.";
            }
            $stmt->close();
        }
    }
}

/* =========================
   CHANGE PASSWORD
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword     = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = "All password fields are required.";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New password and confirm password do not match.";
    } else {
        $dbPassword = $user['password_hash'] ?? '';
        $validCurrent = password_verify($currentPassword, $dbPassword) || $currentPassword === $dbPassword;

        if (!$validCurrent) {
            $error = "Current password is incorrect.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);

            if ($stmt->execute()) {
                $message = "Password changed successfully.";
            } else {
                $error = "Failed to change password.";
            }
            $stmt->close();
        }
    }
}

/* =========================
   REFRESH USER DATA
========================= */
$stmt = $conn->prepare("
    SELECT id, first_name, last_name, email, phone, role, status, profile_image, created_at, last_login, password_hash
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$profileImage = $user['profile_image'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile | Staff Panel</title>
<style>
body{
    margin:0;
    font-family:Arial,sans-serif;
}
.page-title{
    font-size:32px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:22px;
}
.card{
    max-width:950px;
    background:#fff;
    border-radius:18px;
    padding:22px;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 24px rgba(0,0,0,.05);
    margin-bottom:22px;
}
.profile-top{
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:24px;
    flex-wrap:wrap;
}
.avatar-box{
    width:110px;
    height:110px;
    border-radius:50%;
    overflow:hidden;
    border:4px solid #e5e7eb;
    background:#f3f4f6;
    display:flex;
    align-items:center;
    justify-content:center;
}
.avatar-box img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.avatar-placeholder{
    font-size:34px;
    font-weight:800;
    color:#64748b;
}
.profile-meta h2{
    margin:0 0 8px;
    color:#111827;
    font-size:34px;
}
.profile-meta p{
    margin:0 0 5px;
    color:#6b7280;
}
label{
    display:block;
    font-weight:600;
    margin-bottom:6px;
}
input{
    width:100%;
    padding:12px;
    border:1px solid #d1d5db;
    border-radius:12px;
    margin-bottom:12px;
    font-size:15px;
}
input[readonly]{
    background:#f8fafc;
    color:#475569;
}
button,.btn-cancel{
    padding:12px 16px;
    border:none;
    border-radius:12px;
    cursor:pointer;
    font-weight:700;
    text-decoration:none;
    display:inline-block;
}
button{
    background:#22c55e;
    color:#062d16;
}
.btn-cancel{
    background:#e5e7eb;
    color:#111827;
    margin-left:10px;
}
.msg{
    padding:12px;
    border-radius:12px;
    margin-bottom:12px;
}
.ok{
    background:#dcfce7;
    color:#166534;
}
.err{
    background:#fee2e2;
    color:#991b1b;
}
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}
.full{
    grid-column:1 / -1;
}
.file-input{
    padding:10px;
    background:#fff;
}
.section-title{
    font-size:24px;
    font-weight:800;
    color:#111827;
    margin-bottom:18px;
}
.password-card{
    max-width:950px;
    background:#fff;
    border-radius:18px;
    padding:22px;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 24px rgba(0,0,0,.05);
}
.actions-row{
    margin-top:10px;
}
@media(max-width:800px){
    .grid{
        grid-template-columns:1fr;
    }
    .profile-meta h2{
        font-size:28px;
    }
}
</style>
</head>
<body>
<div class="staff-layout">
    <?php include __DIR__ . "/includes/sidebar.php"; ?>

    <div class="staff-main">
        <div class="page-title">My Profile</div>

        <?php if ($message): ?>
            <div class="msg ok"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="msg err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- PROFILE DETAILS CARD -->
        <div class="card">
            <div class="profile-top">
                <div class="avatar-box">
                    <?php if (!empty($profileImage) && file_exists(dirname(__DIR__) . "/uploads/staff/" . $profileImage)): ?>
                        <img src="/fitzone/uploads/staff/<?= htmlspecialchars($profileImage) ?>" alt="Profile Photo">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?= htmlspecialchars(strtoupper(substr($fullName ?: 'S', 0, 1))) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-meta">
                    <h2><?= htmlspecialchars($fullName ?: 'Staff User') ?></h2>
                    <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <p><?= htmlspecialchars(ucfirst($user['role'] ?? 'staff')) ?> • <?= htmlspecialchars(ucfirst($user['status'] ?? 'active')) ?></p>
                </div>
            </div>

            <div class="section-title">Profile Information</div>

            <form method="POST" enctype="multipart/form-data" autocomplete="off">
                <div class="grid">
                    <div>
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                    </div>

                    <div>
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                    </div>

                    <div>
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                    </div>

                    <div>
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>

                    <div>
                        <label>Role</label>
                        <input type="text" value="<?= htmlspecialchars($user['role'] ?? '') ?>" readonly>
                    </div>

                    <div>
                        <label>Status</label>
                        <input type="text" value="<?= htmlspecialchars($user['status'] ?? '') ?>" readonly>
                    </div>

                    <div>
                        <label>Joined Date</label>
                        <input type="text" value="<?= htmlspecialchars($user['created_at'] ?? 'Not available') ?>" readonly>
                    </div>

                    <div>
                        <label>Last Login</label>
                        <input type="text" value="<?= htmlspecialchars($user['last_login'] ?? 'Not available') ?>" readonly>
                    </div>

                    <div class="full">
                        <label>Profile Photo</label>
                        <input type="file" name="profile_image" class="file-input" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>

                <div class="actions-row">
                    <button type="submit" name="save_profile">Save Changes</button>
                    <a href="/fitzone/staff/dashboard.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>

        <!-- CHANGE PASSWORD CARD -->
        <div class="password-card">
            <div class="section-title">Change Password</div>

            <form method="POST" autocomplete="off">
                <div class="grid">
                    <div class="full">
                        <label>Current Password</label>
                        <input type="password" name="current_password" placeholder="Enter current password" required>
                    </div>

                    <div>
                        <label>New Password</label>
                        <input type="password" name="new_password" placeholder="Enter new password" required>
                    </div>

                    <div>
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                </div>

                <button type="submit" name="change_password">Change Password</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
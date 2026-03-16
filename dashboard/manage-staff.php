<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . "/../config/db.php";

require_once __DIR__ . "/../includes/send_staff_mail.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function tableExists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

function uploadStaffImage(array $file, string &$error = ""): ?string {
    if (!isset($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = "Image upload failed.";
        return null;
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt)) {
        $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";
        return null;
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        $error = "Image size must be less than 2MB.";
        return null;
    }

    $uploadDir = __DIR__ . "/../uploads/profile/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newName = "staff_" . time() . "_" . mt_rand(1000, 9999) . "." . $ext;
    $destination = $uploadDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $error = "Failed to save uploaded image.";
        return null;
    }

    return "uploads/profile/" . $newName;
}

$message = "";
$error = "";
$editStaff = null;

if (!tableExists($conn, 'users')) {
    $error = "Users table not found.";
} else {

    /* CREATE STAFF */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $status = trim($_POST['status'] ?? 'active');
        $role = 'staff';

        $can_manage_queries = isset($_POST['can_manage_queries']) ? 1 : 0;
        $can_manage_attendance = isset($_POST['can_manage_attendance']) ? 1 : 0;
        $can_manage_appointments = isset($_POST['can_manage_appointments']) ? 1 : 0;
        $can_view_registrations = isset($_POST['can_view_registrations']) ? 1 : 0;
        $can_view_payments = isset($_POST['can_view_payments']) ? 1 : 0;
        $can_update_membership = isset($_POST['can_update_membership']) ? 1 : 0;

        if ($full_name === '' || $email === '' || $phone === '' || $password === '' || $confirm_password === '') {
            $error = "All required fields must be filled.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Enter a valid email address.";
        } elseif (!preg_match('/^[0-9+\-\s]{8,15}$/', $phone)) {
            $error = "Enter a valid phone number.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif ($password !== $confirm_password) {
            $error = "Password and confirm password do not match.";
        } elseif (!in_array($status, ['active', 'blocked'])) {
            $error = "Invalid status selected.";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if ($exists) {
                $error = "Email already exists.";
            } else {
                $uploadErr = "";
                $profile_image = uploadStaffImage($_FILES['profile_image'] ?? [], $uploadErr);

                if ($uploadErr !== "") {
                    $error = $uploadErr;
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);

                    $st = $conn->prepare("
                        INSERT INTO users
                        (
                            full_name, email, phone, profile_image, password_hash, role, status,
                            can_manage_queries, can_manage_attendance, can_manage_appointments,
                            can_view_registrations, can_view_payments, can_update_membership
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $st->bind_param(
                        "sssssssiiiiii",
                        $full_name,
                        $email,
                        $phone,
                        $profile_image,
                        $hashed,
                        $role,
                        $status,
                        $can_manage_queries,
                        $can_manage_attendance,
                        $can_manage_appointments,
                        $can_view_registrations,
                        $can_view_payments,
                        $can_update_membership
                    );

                    if ($st->execute()) {
                        $message = "Staff account created successfully.";
                    } else {
                        $error = "Failed to create staff account: " . $st->error;
                    }
                    $st->close();
                }
            }
        }
    }

    /* DELETE STAFF */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
        $staff_id = (int)($_POST['staff_id'] ?? 0);

        if ($staff_id <= 0) {
            $error = "Invalid staff ID.";
        } else {
            $getImg = $conn->prepare("SELECT profile_image FROM users WHERE id=? AND role='staff' LIMIT 1");
            $getImg->bind_param("i", $staff_id);
            $getImg->execute();
            $imgRow = $getImg->get_result()->fetch_assoc();
            $getImg->close();

            $del = $conn->prepare("DELETE FROM users WHERE id=? AND role='staff' LIMIT 1");
            $del->bind_param("i", $staff_id);

            if ($del->execute() && $del->affected_rows > 0) {
                if (!empty($imgRow['profile_image'])) {
                    $imgPath = __DIR__ . "/../" . $imgRow['profile_image'];
                    if (file_exists($imgPath)) {
                        @unlink($imgPath);
                    }
                }
                $message = "Staff account deleted successfully.";
            } else {
                $error = "Unable to delete staff account.";
            }
            $del->close();
        }
    }

    /* LOAD EDIT STAFF */
    if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
        $edit_id = (int)$_GET['edit'];

        if ($edit_id > 0) {
            $st = $conn->prepare("
                SELECT
                    id, full_name, email, phone, profile_image, status,
                    can_manage_queries, can_manage_attendance, can_manage_appointments,
                    can_view_registrations, can_view_payments, can_update_membership
                FROM users
                WHERE id=? AND role='staff'
                LIMIT 1
            ");
            $st->bind_param("i", $edit_id);
            $st->execute();
            $editStaff = $st->get_result()->fetch_assoc();
            $st->close();

            if (!$editStaff) {
                $error = "Staff record not found.";
            }
        }
    }

    /* UPDATE STAFF */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $status = trim($_POST['status'] ?? 'active');

        $can_manage_queries = isset($_POST['can_manage_queries']) ? 1 : 0;
        $can_manage_attendance = isset($_POST['can_manage_attendance']) ? 1 : 0;
        $can_manage_appointments = isset($_POST['can_manage_appointments']) ? 1 : 0;
        $can_view_registrations = isset($_POST['can_view_registrations']) ? 1 : 0;
        $can_view_payments = isset($_POST['can_view_payments']) ? 1 : 0;
        $can_update_membership = isset($_POST['can_update_membership']) ? 1 : 0;

        if ($staff_id <= 0) {
            $error = "Invalid staff ID.";
        } elseif ($full_name === '' || $email === '' || $phone === '') {
            $error = "Full name, email and phone are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Enter a valid email address.";
        } elseif (!preg_match('/^[0-9+\-\s]{8,15}$/', $phone)) {
            $error = "Enter a valid phone number.";
        } elseif (!in_array($status, ['active', 'blocked'])) {
            $error = "Invalid status selected.";
        } elseif ($password !== '' && strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif ($password !== '' && $password !== $confirm_password) {
            $error = "Password and confirm password do not match.";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE email=? AND id != ? LIMIT 1");
            $check->bind_param("si", $email, $staff_id);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if ($exists) {
                $error = "Another account already uses this email.";
            } else {
                $old = $conn->prepare("SELECT profile_image FROM users WHERE id=? AND role='staff' LIMIT 1");
                $old->bind_param("i", $staff_id);
                $old->execute();
                $oldRow = $old->get_result()->fetch_assoc();
                $old->close();

                $uploadErr = "";
                $newImage = uploadStaffImage($_FILES['profile_image'] ?? [], $uploadErr);

                if ($uploadErr !== "") {
                    $error = $uploadErr;
                } else {
                    $profile_image = $oldRow['profile_image'] ?? null;

                    if ($newImage) {
                        $profile_image = $newImage;

                        if (!empty($oldRow['profile_image'])) {
                            $oldPath = __DIR__ . "/../" . $oldRow['profile_image'];
                            if (file_exists($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    }

                    if ($password !== '') {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);

                        $up = $conn->prepare("
                            UPDATE users
                            SET
                                full_name=?,
                                email=?,
                                phone=?,
                                profile_image=?,
                                password_hash=?,
                                status=?,
                                can_manage_queries=?,
                                can_manage_attendance=?,
                                can_manage_appointments=?,
                                can_view_registrations=?,
                                can_view_payments=?,
                                can_update_membership=?
                            WHERE id=? AND role='staff'
                        ");

                        $up->bind_param(
                            "ssssssiiiiiii",
                            $full_name,
                            $email,
                            $phone,
                            $profile_image,
                            $hashed,
                            $status,
                            $can_manage_queries,
                            $can_manage_attendance,
                            $can_manage_appointments,
                            $can_view_registrations,
                            $can_view_payments,
                            $can_update_membership,
                            $staff_id
                        );
                    } else {
                        $up = $conn->prepare("
                            UPDATE users
                            SET
                                full_name=?,
                                email=?,
                                phone=?,
                                profile_image=?,
                                status=?,
                                can_manage_queries=?,
                                can_manage_attendance=?,
                                can_manage_appointments=?,
                                can_view_registrations=?,
                                can_view_payments=?,
                                can_update_membership=?
                            WHERE id=? AND role='staff'
                        ");

                        $up->bind_param(
                            "sssssiiiiiii",
                            $full_name,
                            $email,
                            $phone,
                            $profile_image,
                            $status,
                            $can_manage_queries,
                            $can_manage_attendance,
                            $can_manage_appointments,
                            $can_view_registrations,
                            $can_view_payments,
                            $can_update_membership,
                            $staff_id
                        );
                    }

                    if ($up->execute()) {
                        $message = "Staff account updated successfully.";
                        $editStaff = null;
                    } else {
                        $error = "Failed to update staff account: " . $up->error;
                    }
                    $up->close();
                }
            }
        }
    }
}

/* FETCH STAFF LIST */
$staffs = [];
if (tableExists($conn, 'users')) {
    $res = $conn->query("
        SELECT
            id, full_name, email, phone, profile_image, role, status,
            can_manage_queries, can_manage_attendance, can_manage_appointments,
            can_view_registrations, can_view_payments, can_update_membership
        FROM users
        WHERE role='staff'
        ORDER BY id DESC
    ");

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $staffs[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Staff | FitZone</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{box-sizing:border-box;}
body{
    margin:0;
    background:#f4f7fb;
    font-family:Arial, sans-serif;
    color:#111827;
}
.wrap{
    max-width:1400px;
    margin:30px auto;
    padding:20px;
}
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:22px;
}
.page-title{
    margin:0;
    font-size:46px;
    font-weight:800;
    color:#0f172a;
}
.grid{
    display:grid;
    grid-template-columns:430px 1fr;
    gap:24px;
}
.card{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:24px;
    padding:24px;
    box-shadow:0 12px 35px rgba(15,23,42,.06);
}
.card h2{
    margin-top:0;
    margin-bottom:20px;
    font-size:24px;
    color:#0f172a;
}
label{
    display:block;
    font-size:15px;
    font-weight:700;
    margin:10px 0 8px;
    color:#111827;
}
input[type="text"],
input[type="email"],
input[type="password"],
input[type="file"],
select{
    width:100%;
    padding:13px 14px;
    border:1px solid #cfd8e3;
    border-radius:14px;
    background:#f8fbff;
    font-size:15px;
    outline:none;
    margin-bottom:12px;
}
input:focus,
select:focus{
    border-color:#3b82f6;
    box-shadow:0 0 0 4px rgba(59,130,246,.10);
}
.password-wrap{
    position:relative;
}
.password-wrap input{
    padding-right:50px;
}
.toggle-pass{
    position:absolute;
    right:12px;
    top:12px;
    border:none;
    background:transparent;
    cursor:pointer;
    font-size:18px;
}
.permissions-box{
    display:grid;
    gap:10px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    padding:14px;
    border-radius:16px;
    margin:8px 0 18px;
}
.check-item{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:600;
    font-size:14px;
    margin:0;
    cursor:pointer;
}
.check-item input{
    width:18px;
    height:18px;
    margin:0;
}
.image-preview{
    margin:10px 0 16px;
}
.image-preview img{
    width:90px;
    height:90px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #dbeafe;
}
.table-avatar{
    width:52px;
    height:52px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #dbeafe;
}
.avatar-placeholder{
    width:52px;
    height:52px;
    border-radius:50%;
    background:#e5e7eb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}
.form-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.btn{
    padding:12px 18px;
    border:none;
    border-radius:14px;
    text-decoration:none;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    cursor:pointer;
    transition:.2s ease;
}
.btn:hover{
    transform:translateY(-1px);
}
.btn-dark{background:#0f172a;color:#fff;}
.btn-green{background:#22c55e;color:#062d16;}
.btn-blue{background:#3b82f6;color:#fff;}
.btn-red{background:#ef4444;color:#fff;}
.btn-gray{background:#e5e7eb;color:#111827;}
.btn-sm{
    padding:10px 14px;
    border-radius:12px;
    font-size:14px;
}
.msg{
    padding:14px 16px;
    border-radius:14px;
    margin-bottom:18px;
    font-weight:700;
}
.ok{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
}
.err{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
}
.table-wrap{
    overflow-x:auto;
}
table{
    width:100%;
    border-collapse:collapse;
    min-width:1000px;
}
th, td{
    padding:15px 12px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:top;
}
th{
    color:#374151;
    font-size:15px;
}
.badge{
    display:inline-block;
    padding:7px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
}
.active{background:#dcfce7;color:#166534;}
.blocked{background:#fee2e2;color:#991b1b;}
.perm-tags{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
.perm-tags span{
    background:#eff6ff;
    color:#1d4ed8;
    font-size:12px;
    padding:6px 10px;
    border-radius:999px;
    font-weight:700;
}
.perm-tags .no-perm{
    background:#f3f4f6;
    color:#6b7280;
}
.actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.actions form{
    margin:0;
}
.empty{
    text-align:center;
    color:#6b7280;
    padding:20px 0;
    font-weight:600;
}
@media(max-width:1100px){
    .grid{
        grid-template-columns:1fr;
    }
}
@media(max-width:650px){
    .page-title{
        font-size:32px;
    }
    .card{
        padding:18px;
    }
}
</style>
</head>
<body>

<div class="wrap">
    <div class="topbar">
        <h1 class="page-title">🛡️ Staff Management</h1>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="manage-users.php" class="btn btn-blue">👥 Manage Users</a>
            <a href="admin.php" class="btn btn-dark">← Back to Dashboard</a>
        </div>
    </div>

    <?php if($message): ?>
        <div class="msg ok"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="grid">

        <div class="card">
            <?php if ($editStaff): ?>
                <h2>✏️ Edit Staff Account</h2>

                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="staff_id" value="<?= (int)$editStaff['id'] ?>">

                    <label>Full Name</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($editStaff['full_name'] ?? '') ?>">

                    <label>Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($editStaff['email'] ?? '') ?>">

                    <label>Phone Number</label>
                    <input type="text" name="phone" required value="<?= htmlspecialchars($editStaff['phone'] ?? '') ?>">

                    <label>New Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="editPassword" placeholder="Leave blank to keep current password">
                        <button type="button" class="toggle-pass" onclick="togglePassword('editPassword', this)">👁️</button>
                    </div>

                    <label>Confirm New Password</label>
                    <div class="password-wrap">
                        <input type="password" name="confirm_password" id="editConfirmPassword" placeholder="Re-enter new password">
                        <button type="button" class="toggle-pass" onclick="togglePassword('editConfirmPassword', this)">👁️</button>
                    </div>

                    <label>Status</label>
                    <select name="status" required>
                        <option value="active" <?= (($editStaff['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="blocked" <?= (($editStaff['status'] ?? '') === 'blocked') ? 'selected' : '' ?>>Blocked</option>
                    </select>

                    <label>Profile Image</label>
                    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">

                    <?php if (!empty($editStaff['profile_image'])): ?>
                        <div class="image-preview">
                            <img src="../<?= htmlspecialchars($editStaff['profile_image']) ?>" alt="Profile Image">
                        </div>
                    <?php endif; ?>

                    <label>Permissions</label>
                    <div class="permissions-box">
                        <label class="check-item">
                            <input type="checkbox" name="can_manage_queries" <?= !empty($editStaff['can_manage_queries']) ? 'checked' : '' ?>>
                            <span>Can manage queries</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_manage_attendance" <?= !empty($editStaff['can_manage_attendance']) ? 'checked' : '' ?>>
                            <span>Can manage attendance</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_manage_appointments" <?= !empty($editStaff['can_manage_appointments']) ? 'checked' : '' ?>>
                            <span>Can manage appointments</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_view_registrations" <?= !empty($editStaff['can_view_registrations']) ? 'checked' : '' ?>>
                            <span>Can view customer registrations</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_view_payments" <?= !empty($editStaff['can_view_payments']) ? 'checked' : '' ?>>
                            <span>Can view payments status</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_update_membership" <?= !empty($editStaff['can_update_membership']) ? 'checked' : '' ?>>
                            <span>Can update membership status</span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-blue" type="submit" name="update_staff">Update Staff</button>
                        <a href="manage-staff.php" class="btn btn-gray">Cancel</a>
                    </div>
                </form>

            <?php else: ?>
                <h2>➕ Create Staff Account</h2>

                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required placeholder="Enter full name">

                    <label>Email</label>
                    <input type="email" name="email" required placeholder="Enter staff email">

                    <label>Phone Number</label>
                    <input type="text" name="phone" required placeholder="Enter phone number">

                    <label>Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="createPassword" minlength="8" required placeholder="Enter password">
                        <button type="button" class="toggle-pass" onclick="togglePassword('createPassword', this)">👁️</button>
                    </div>

                    <label>Confirm Password</label>
                    <div class="password-wrap">
                        <input type="password" name="confirm_password" id="createConfirmPassword" minlength="8" required placeholder="Confirm password">
                        <button type="button" class="toggle-pass" onclick="togglePassword('createConfirmPassword', this)">👁️</button>
                    </div>

                    <label>Status</label>
                    <select name="status" required>
                        <option value="active" selected>Active</option>
                        <option value="blocked">Blocked</option>
                    </select>

                    <label>Profile Image</label>
                    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">

                    <label>Permissions</label>
                    <div class="permissions-box">
                        <label class="check-item">
                            <input type="checkbox" name="can_manage_queries">
                            <span>Can manage queries</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_manage_attendance">
                            <span>Can manage attendance</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_manage_appointments">
                            <span>Can manage appointments</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_view_registrations">
                            <span>Can view customer registrations</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_view_payments">
                            <span>Can view payments status</span>
                        </label>

                        <label class="check-item">
                            <input type="checkbox" name="can_update_membership">
                            <span>Can update membership status</span>
                        </label>
                    </div>

                    <button class="btn btn-green" type="submit" name="create_staff">Create Staff</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>👨‍💼 Staff Accounts</h2>

            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>

                    <?php if (!empty($staffs)): ?>
                        <?php foreach($staffs as $s): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($s['profile_image'])): ?>
                                        <img class="table-avatar" src="../<?= htmlspecialchars($s['profile_image']) ?>" alt="Profile">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">👤</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($s['full_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
                                <td>
                                    <span class="badge <?= (($s['status'] ?? 'active') === 'active') ? 'active' : 'blocked' ?>">
                                        <?= htmlspecialchars(ucfirst($s['status'] ?? 'active')) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="perm-tags">
                                        <?php if (!empty($s['can_manage_queries'])): ?><span>Queries</span><?php endif; ?>
                                        <?php if (!empty($s['can_manage_attendance'])): ?><span>Attendance</span><?php endif; ?>
                                        <?php if (!empty($s['can_manage_appointments'])): ?><span>Appointments</span><?php endif; ?>
                                        <?php if (!empty($s['can_view_registrations'])): ?><span>Registrations</span><?php endif; ?>
                                        <?php if (!empty($s['can_view_payments'])): ?><span>Payments</span><?php endif; ?>
                                        <?php if (!empty($s['can_update_membership'])): ?><span>Membership</span><?php endif; ?>

                                        <?php if (
                                            empty($s['can_manage_queries']) &&
                                            empty($s['can_manage_attendance']) &&
                                            empty($s['can_manage_appointments']) &&
                                            empty($s['can_view_registrations']) &&
                                            empty($s['can_view_payments']) &&
                                            empty($s['can_update_membership'])
                                        ): ?>
                                            <span class="no-perm">No permissions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="manage-staff.php?edit=<?= (int)$s['id'] ?>" class="btn btn-blue btn-sm">Edit</a>

                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this staff account?');">
                                            <input type="hidden" name="staff_id" value="<?= (int)$s['id'] ?>">
                                            <button type="submit" name="delete_staff" class="btn btn-red btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty">No staff accounts found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
function togglePassword(inputId, btn){
    const input = document.getElementById(inputId);
    if(input.type === "password"){
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
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function tableExists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

$message = "";
$error = "";

if (!tableExists($conn, 'users')) {
    $error = "Users table not found.";
} else {
    /* Toggle active / blocked */
    if (isset($_GET['toggle']) && ctype_digit($_GET['toggle'])) {
        $id = (int)$_GET['toggle'];

        $q = $conn->prepare("SELECT id, status FROM users WHERE id=? LIMIT 1");
        $q->bind_param("i", $id);
        $q->execute();
        $row = $q->get_result()->fetch_assoc();
        $q->close();

        if ($row) {
            $newStatus = (($row['status'] ?? 'active') === 'active') ? 'blocked' : 'active';

            $u = $conn->prepare("UPDATE users SET status=? WHERE id=?");
            $u->bind_param("si", $newStatus, $id);

            if ($u->execute()) {
                $message = "User status updated successfully.";
            } else {
                $error = "Failed to update user status.";
            }
            $u->close();
        } else {
            $error = "User not found.";
        }
    }

    /* Delete user */
    if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
        $id = (int)$_GET['delete'];

        if ($id === (int)$_SESSION['user_id']) {
            $error = "You cannot delete your own admin account.";
        } else {
            $getImg = $conn->prepare("SELECT profile_image FROM users WHERE id=? LIMIT 1");
            $getImg->bind_param("i", $id);
            $getImg->execute();
            $imgRow = $getImg->get_result()->fetch_assoc();
            $getImg->close();

            $d = $conn->prepare("DELETE FROM users WHERE id=? LIMIT 1");
            $d->bind_param("i", $id);

            if ($d->execute() && $d->affected_rows > 0) {
                if (!empty($imgRow['profile_image'])) {
                    $imgPath = __DIR__ . "/../" . $imgRow['profile_image'];
                    if (file_exists($imgPath)) {
                        @unlink($imgPath);
                    }
                }
                $message = "User deleted successfully.";
            } else {
                $error = "Failed to delete user.";
            }
            $d->close();
        }
    }
}

$users = [];
$search = trim($_GET['search'] ?? '');

if (tableExists($conn, 'users')) {
    if ($search !== '') {
        $sql = "
            SELECT id, full_name, email, phone, role, status
            FROM users
            WHERE full_name LIKE ? OR email LIKE ? OR role LIKE ?
            ORDER BY id DESC
        ";
        $st = $conn->prepare($sql);
        $like = "%$search%";
        $st->bind_param("sss", $like, $like, $like);
    } else {
        $sql = "
            SELECT id, full_name, email, phone, role, status
            FROM users
            ORDER BY id DESC
        ";
        $st = $conn->prepare($sql);
    }

    if ($st) {
        $st->execute();
        $res = $st->get_result();
        while ($row = $res->fetch_assoc()) {
            $users[] = $row;
        }
        $st->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users | FitZone</title>
<style>
body{
    margin:0;
    font-family:Arial,sans-serif;
    background:#f4f7fb;
    color:#0f172a;
}
.wrap{
    max-width:1250px;
    margin:30px auto;
    padding:20px;
}
.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:18px;
}
.top h1{
    margin:0;
    font-size:46px;
    font-weight:800;
}
.card{
    background:#fff;
    border-radius:22px;
    padding:24px;
    box-shadow:0 10px 28px rgba(0,0,0,.06);
    border:1px solid #e5e7eb;
}
.btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:11px 18px;
    border-radius:14px;
    text-decoration:none;
    font-weight:700;
    border:none;
    cursor:pointer;
    transition:.2s ease;
}
.btn:hover{
    transform:translateY(-1px);
}
.btn-dark{background:#0f172a;color:#fff;}
.btn-green{background:#22c55e;color:#062d16;}
.btn-red{background:#ef4444;color:#fff;}
.btn-blue{background:#3b82f6;color:#fff;}
.btn-gray{background:#e5e7eb;color:#111827;}
.toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    flex-wrap:wrap;
    margin-bottom:18px;
}
.search-form{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
}
input[type="text"]{
    padding:12px 14px;
    border:1px solid #d1d5db;
    border-radius:14px;
    min-width:260px;
    outline:none;
}
input[type="text"]:focus{
    border-color:#3b82f6;
    box-shadow:0 0 0 4px rgba(59,130,246,.10);
}
table{
    width:100%;
    border-collapse:collapse;
}
th,td{
    padding:15px 12px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:middle;
}
th{
    color:#374151;
    font-size:15px;
}
.badge{
    padding:7px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
    display:inline-block;
}
.active{background:#dcfce7;color:#166534;}
.blocked{background:#fee2e2;color:#991b1b;}
.admin{background:#ede9fe;color:#5b21b6;}
.staff{background:#dbeafe;color:#1d4ed8;}
.customer{background:#f3f4f6;color:#374151;}
.msg{
    padding:13px 16px;
    border-radius:14px;
    margin:0 0 16px;
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
.actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.empty{
    text-align:center;
    color:#6b7280;
    padding:20px 0;
    font-weight:600;
}
@media(max-width:700px){
    .top h1{
        font-size:34px;
    }
    input[type="text"]{
        min-width:100%;
        width:100%;
    }
}
</style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>👥 Manage Users</h1>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn btn-green" href="manage-staff.php">+ Add Staff</a>
            <a class="btn btn-blue" href="manage-staff.php">Manage Staff</a>
            <a class="btn btn-dark" href="admin.php">← Back to Dashboard</a>
        </div>
    </div>

    <?php if($message): ?>
        <div class="msg ok"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search by name, email or role" value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-blue" type="submit">Search</button>
                <a class="btn btn-gray" href="manage-users.php">Reset</a>
            </form>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php if (!empty($users)): ?>
                <?php foreach($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= htmlspecialchars($u['full_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                        <td>
                            <span class="badge <?= htmlspecialchars($u['role'] ?? 'customer') ?>">
                                <?= htmlspecialchars(ucfirst($u['role'] ?? 'customer')) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= (($u['status'] ?? 'active') === 'active') ? 'active' : 'blocked' ?>">
                                <?= htmlspecialchars(ucfirst($u['status'] ?? 'active')) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-blue" href="?toggle=<?= (int)$u['id'] ?>" onclick="return confirm('Change user status?')">Block/Unblock</a>

                                <?php if (($u['role'] ?? '') === 'staff'): ?>
                                    <a class="btn btn-green" href="manage-staff.php?edit=<?= (int)$u['id'] ?>">Edit Staff</a>
                                <?php endif; ?>

                                <a class="btn btn-red" href="?delete=<?= (int)$u['id'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="empty">No users found.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
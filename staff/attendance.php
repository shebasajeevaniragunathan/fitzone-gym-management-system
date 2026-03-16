<?php
require_once __DIR__ . "/includes/auth.php";

$message = "";
$error = "";

$conn->query("
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent') NOT NULL DEFAULT 'present',
    marked_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['attendance_date'], $_POST['status'])) {
    $userId = (int)$_POST['user_id'];
    $date = trim($_POST['attendance_date']);
    $status = trim($_POST['status']);
    $markedBy = (int)($_SESSION['user_id'] ?? 0);

    if ($userId > 0 && $date !== '' && in_array($status, ['present', 'absent'], true)) {
        $check = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND attendance_date = ? LIMIT 1");
        $check->bind_param("is", $userId, $date);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, marked_by = ? WHERE id = ?");
            $stmt->bind_param("sii", $status, $markedBy, $existing['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO attendance (user_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $userId, $date, $status, $markedBy);
        }

        if ($stmt->execute()) {
            $message = "Attendance saved successfully.";
        } else {
            $error = "Failed to save attendance.";
        }
        $stmt->close();
    }
}

$customers = [];
$res = $conn->query("
    SELECT id, first_name, last_name, full_name, email
    FROM users
    WHERE role = 'customer'
    ORDER BY id DESC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $customers[] = $row;
    }
}

$attendanceRows = [];
$res2 = $conn->query("
    SELECT a.*, u.full_name, u.first_name, u.last_name, u.email
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.attendance_date DESC, a.id DESC
");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $attendanceRows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance | Staff Panel</title>
<style>
body{margin:0;font-family:Arial,sans-serif}
.page-title{font-size:32px;font-weight:800;color:#0f172a;margin-bottom:22px}
.grid{display:grid;grid-template-columns:360px 1fr;gap:20px}
.card{background:#fff;border-radius:18px;padding:22px;border:1px solid #e5e7eb;box-shadow:0 10px 24px rgba(0,0,0,.05)}
label{display:block;font-weight:600;margin-bottom:6px}
select,input,button{
    width:100%;padding:12px;border:1px solid #d1d5db;border-radius:12px;margin-bottom:12px;
}
button{background:#22c55e;color:#062d16;font-weight:700;border:none;cursor:pointer}
.msg{padding:12px;border-radius:12px;margin-bottom:12px}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
table{width:100%;border-collapse:collapse}
th,td{padding:14px;border-bottom:1px solid #e5e7eb;text-align:left}
th{background:#f8fafc}
.present{color:#166534;font-weight:700}
.absent{color:#991b1b;font-weight:700}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="staff-layout">
    <?php include __DIR__ . "/includes/sidebar.php"; ?>

    <div class="staff-main">
        <div class="page-title">Manage Attendance</div>

        <?php if ($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="grid">
            <div class="card">
                <h2>Mark Attendance</h2>
                <form method="POST">
                    <label>Customer</label>
                    <select name="user_id" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?>
                            <?php $name = $c['full_name'] ?: trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')); ?>
                            <option value="<?= (int)$c['id'] ?>">
                                <?= htmlspecialchars($name ?: $c['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Date</label>
                    <input type="date" name="attendance_date" value="<?= date('Y-m-d') ?>" required>

                    <label>Status</label>
                    <select name="status" required>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                    </select>

                    <button type="submit">Save Attendance</button>
                </form>
            </div>

            <div class="card">
                <h2>Attendance Records</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                    <?php if (!empty($attendanceRows)): ?>
                        <?php foreach ($attendanceRows as $r): ?>
                        <?php $name = $r['full_name'] ?: trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')); ?>
                        <tr>
                            <td><?= (int)$r['id'] ?></td>
                            <td><?= htmlspecialchars($name ?: 'Customer') ?></td>
                            <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['attendance_date'] ?? '') ?></td>
                            <td class="<?= htmlspecialchars($r['status']) ?>">
                                <?= htmlspecialchars(ucfirst($r['status'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No attendance records found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
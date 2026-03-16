<?php
require_once __DIR__ . "/includes/auth.php";

$message = "";
$error = "";

/* -----------------------------
   Ensure status column exists in bookings table
----------------------------- */
$check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'status'");
if ($check && $check->num_rows === 0) {
    $conn->query("ALTER TABLE bookings ADD status VARCHAR(20) NOT NULL DEFAULT 'pending'");
}

/* -----------------------------
   Update booking status
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $id = (int)$_POST['booking_id'];
    $status = trim($_POST['status']);

    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];

    if ($id > 0 && in_array($status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            $message = "Booking updated successfully.";
        } else {
            $error = "Failed to update booking.";
        }
        $stmt->close();
    }
}

/* -----------------------------
   Fetch bookings with customer, program, trainer details
----------------------------- */
$appointments = [];

$sql = "
    SELECT
        b.id,
        b.booking_date,
        b.booking_time,
        b.status,
        b.created_at,
        u.full_name,
        u.email,
        COALESCE(p.title, 'Program Not Found') AS program_name,
        COALESCE(t.name, p.trainer_name, 'Trainer Not Assigned') AS trainer_name
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN programs p ON b.program_id = p.id
    LEFT JOIN trainers t ON b.trainer_id = t.id
    ORDER BY b.booking_date DESC, b.booking_time DESC, b.id DESC
";

$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $appointments[] = $row;
    }
} else {
    $error = "Unable to load bookings.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointments | Staff Panel</title>
<style>
body{
    margin:0;
    font-family:Arial,sans-serif;
    background:#f4f7fb;
}
.page-title{
    font-size:32px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:22px;
}
.card{
    background:#fff;
    border-radius:18px;
    padding:22px;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 24px rgba(0,0,0,.05);
}
.table-wrap{
    overflow-x:auto;
}
table{
    width:100%;
    border-collapse:collapse;
    min-width:1100px;
}
th,td{
    padding:14px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:top;
}
th{
    background:#f8fafc;
    color:#0f172a;
}
.msg{
    padding:12px;
    border-radius:12px;
    margin-bottom:12px;
    font-weight:700;
}
.ok{
    background:#dcfce7;
    color:#166534;
}
.err{
    background:#fee2e2;
    color:#991b1b;
}
select,button{
    padding:10px 12px;
    border-radius:10px;
    border:1px solid #d1d5db;
    font-size:14px;
}
button{
    background:#22c55e;
    color:#062d16;
    font-weight:700;
    border:none;
    cursor:pointer;
    margin-top:8px;
}
button:hover{
    opacity:.95;
}
.status-pill{
    padding:6px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    display:inline-block;
    text-transform:capitalize;
}
.pending{background:#fef3c7;color:#92400e;}
.confirmed{background:#dcfce7;color:#166534;}
.completed{background:#dbeafe;color:#1d4ed8;}
.cancelled{background:#fee2e2;color:#991b1b;}
.default-status{background:#e5e7eb;color:#374151;}
.small-text{
    color:#64748b;
    font-size:13px;
    line-height:1.6;
}
</style>
</head>
<body>
<div class="staff-layout">
    <?php include __DIR__ . "/includes/sidebar.php"; ?>

    <div class="staff-main">
        <div class="page-title">Manage Appointments</div>

        <?php if ($message): ?>
            <div class="msg ok"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="msg err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Program</th>
                        <th>Trainer</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>

                    <?php if (!empty($appointments)): ?>
                        <?php foreach ($appointments as $a): ?>
                            <?php
                                $currentStatus = strtolower(trim($a['status'] ?? 'pending'));
                                $allowedClasses = ['pending', 'confirmed', 'completed', 'cancelled'];
                                $statusClass = in_array($currentStatus, $allowedClasses, true) ? $currentStatus : 'default-status';
                            ?>
                            <tr>
                                <td><?= (int)$a['id'] ?></td>
                                <td><?= htmlspecialchars($a['full_name'] ?? 'Customer') ?></td>
                                <td><?= htmlspecialchars($a['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($a['program_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($a['trainer_name'] ?? '-') ?></td>
                                <td>
                                    <?php
                                        echo !empty($a['booking_date'])
                                            ? htmlspecialchars(date("Y-m-d", strtotime($a['booking_date'])))
                                            : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                        echo !empty($a['booking_time'])
                                            ? htmlspecialchars(date("h:i A", strtotime($a['booking_time'])))
                                            : '-';
                                    ?>
                                </td>
                                <td>
                                    <span class="status-pill <?= $statusClass ?>">
                                        <?= htmlspecialchars(ucfirst($currentStatus)) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="booking_id" value="<?= (int)$a['id'] ?>">
                                        <select name="status">
                                            <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="confirmed" <?= $currentStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="completed" <?= $currentStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $currentStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <br>
                                        <button type="submit">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">No bookings found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
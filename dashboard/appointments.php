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

if (!tableExists($conn, 'appointments')) {
    $error = "appointments table not found.";
} else {
    if (isset($_GET['approve']) && ctype_digit($_GET['approve'])) {
        $id = (int)$_GET['approve'];
        $u = $conn->prepare("UPDATE appointments SET status='approved' WHERE id=?");
        $u->bind_param("i", $id);
        if ($u->execute()) $message = "Appointment approved.";
        $u->close();
    }

    if (isset($_GET['reject']) && ctype_digit($_GET['reject'])) {
        $id = (int)$_GET['reject'];
        $u = $conn->prepare("UPDATE appointments SET status='rejected' WHERE id=?");
        $u->bind_param("i", $id);
        if ($u->execute()) $message = "Appointment rejected.";
        $u->close();
    }
}

$appointments = [];
if (tableExists($conn, 'appointments')) {
    $res = $conn->query("SELECT * FROM appointments ORDER BY id DESC");
    while ($row = $res->fetch_assoc()) $appointments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointments | FitZone</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{margin:0;background:#f4f7fb;font-family:Arial,sans-serif}
.wrap{max-width:1200px;margin:30px auto;padding:20px}
.card{background:#fff;padding:22px;border-radius:18px;border:1px solid #e5e7eb;box-shadow:0 10px 25px rgba(0,0,0,.05)}
.btn{padding:10px 14px;border:none;border-radius:12px;font-weight:700;text-decoration:none;display:inline-block}
.btn-dark{background:#0f172a;color:#fff}
.btn-green{background:#22c55e;color:#062d16}
.btn-red{background:#ef4444;color:#fff}
.msg{padding:12px;border-radius:12px;margin-bottom:12px}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{padding:14px;border-bottom:1px solid #e5e7eb;text-align:left}
.badge{padding:6px 12px;border-radius:999px;font-size:13px;font-weight:700}
.pending{background:#fef3c7;color:#92400e}
.approved{background:#dcfce7;color:#166534}
.rejected{background:#fee2e2;color:#991b1b}
</style>
</head>
<body>
<div class="wrap">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:18px">
    <h1>📅 Appointments / Bookings</h1>
    <a href="admin.php" class="btn btn-dark">← Back to Dashboard</a>
  </div>

  <?php if($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <table>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Date</th>
        <th>Program</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
      <?php if (!empty($appointments)): ?>
        <?php foreach($appointments as $a): ?>
        <tr>
          <td><?= (int)$a['id'] ?></td>
          <td><?= htmlspecialchars($a['name'] ?? '') ?></td>
          <td><?= htmlspecialchars($a['email'] ?? '') ?></td>
          <td><?= htmlspecialchars($a['appointment_date'] ?? ($a['date'] ?? '')) ?></td>
          <td><?= htmlspecialchars($a['program'] ?? '') ?></td>
          <td>
            <?php $s = $a['status'] ?? 'pending'; ?>
            <span class="badge <?= htmlspecialchars($s) ?>"><?= htmlspecialchars(ucfirst($s)) ?></span>
          </td>
          <td>
            <a class="btn btn-green" href="?approve=<?= (int)$a['id'] ?>">Approve</a>
            <a class="btn btn-red" href="?reject=<?= (int)$a['id'] ?>">Reject</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7">No appointments found.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
</body>
</html>
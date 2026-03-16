<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'staff') {
  header("Location: ../auth/login.php"); exit;
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Staff Dashboard</title>
<link rel="stylesheet" href="../assets/style.css"></head>
<body>
  <div class="card" style="max-width:720px;">
    <h2 style="margin:0;">Staff Dashboard 🧾</h2>
    <p class="small">Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Staff') ?></p>
    <a class="btn" style="display:inline-block; text-align:center; text-decoration:none;" href="../auth/logout.php">Logout</a>
  </div>
</body></html>
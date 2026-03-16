<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

/* =========================
   CREATE TABLE IF NOT EXISTS
========================= */
$createTableSQL = "
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    day_name VARCHAR(50) NOT NULL,
    time_slot VARCHAR(50) NOT NULL,
    trainer_name VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTableSQL);

/* =========================
   ADD COLUMNS IF MISSING
========================= */
$columnsToCheck = [
    "day_name" => "ALTER TABLE programs ADD day_name VARCHAR(50) NOT NULL AFTER description",
    "time_slot" => "ALTER TABLE programs ADD time_slot VARCHAR(50) NOT NULL AFTER day_name",
    "trainer_name" => "ALTER TABLE programs ADD trainer_name VARCHAR(100) NULL AFTER time_slot"
];

foreach ($columnsToCheck as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM programs LIKE '$col'");
    if ($check && $check->num_rows === 0) {
        $conn->query($sql);
    }
}

/* =========================
   ADD PROGRAM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_program'])) {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $day_name    = trim($_POST['day_name'] ?? '');
    $time_slot   = trim($_POST['time_slot'] ?? '');
    $trainer_name = trim($_POST['trainer_name'] ?? '');

    if ($title === '' || $day_name === '' || $time_slot === '') {
        $error = "Title, day and time are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO programs (title, description, day_name, time_slot, trainer_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $description, $day_name, $time_slot, $trainer_name);

        if ($stmt->execute()) {
            header("Location: programs.php?success=added");
            exit;
        } else {
            $error = "Failed to add program.";
        }
        $stmt->close();
    }
}

/* =========================
   DELETE PROGRAM
========================= */
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM programs WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: programs.php?success=deleted");
        exit;
    } else {
        $error = "Failed to delete program.";
    }
    $stmt->close();
}

/* =========================
   SUCCESS MESSAGE
========================= */
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $message = "Program added successfully.";
    } elseif ($_GET['success'] === 'deleted') {
        $message = "Program deleted successfully.";
    }
}

/* =========================
   FETCH PROGRAMS
========================= */
$programs = [];
$orderSql = "
SELECT * FROM programs
ORDER BY FIELD(day_name, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
id ASC
";
$res = $conn->query($orderSql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $programs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Programs | FitZone</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{box-sizing:border-box}
body{
    margin:0;
    background:#f4f7fb;
    font-family:Arial,sans-serif;
    color:#111827;
}
.wrap{
    max-width:1250px;
    margin:30px auto;
    padding:20px;
}
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:18px;
}
.grid{
    display:grid;
    grid-template-columns:360px 1fr;
    gap:20px;
}
.card{
    background:#fff;
    padding:22px;
    border-radius:18px;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 25px rgba(0,0,0,.05);
}
label{
    display:block;
    margin-bottom:6px;
    font-weight:600;
}
input, textarea, select{
    width:100%;
    padding:12px;
    border:1px solid #d1d5db;
    border-radius:12px;
    margin:6px 0 12px;
    font-size:14px;
}
textarea{
    min-height:100px;
    resize:vertical;
}
.btn{
    padding:11px 16px;
    border:none;
    border-radius:12px;
    font-weight:700;
    text-decoration:none;
    display:inline-block;
    cursor:pointer;
}
.btn-dark{background:#0f172a;color:#fff}
.btn-green{background:#22c55e;color:#062d16}
.btn-red{background:#ef4444;color:#fff}
.msg{
    padding:12px;
    border-radius:12px;
    margin-bottom:12px;
}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
table{
    width:100%;
    border-collapse:collapse;
}
th,td{
    padding:14px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:top;
}
th{
    background:#f9fafb;
}
.desc{
    color:#6b7280;
    line-height:1.5;
    max-width:280px;
}
@media(max-width:900px){
    .grid{grid-template-columns:1fr}
}
</style>
</head>
<body>
<div class="wrap">
  <div class="topbar">
    <h1>🪪 Programs / Classes</h1>
    <a href="admin.php" class="btn btn-dark">← Back to Dashboard</a>
  </div>

  <?php if($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <h2>Add Program</h2>
      <form method="POST" autocomplete="off">
        <label>Class Title</label>
        <input type="text" name="title" required placeholder="Cardio Blast">

        <label>Description</label>
        <textarea name="description" placeholder="High-energy cardio workout for fat burning and stamina."></textarea>

        <label>Day</label>
        <select name="day_name" required>
          <option value="">Select Day</option>
          <option>Monday</option>
          <option>Tuesday</option>
          <option>Wednesday</option>
          <option>Thursday</option>
          <option>Friday</option>
          <option>Saturday</option>
          <option>Sunday</option>
        </select>

        <label>Time</label>
        <input type="text" name="time_slot" required placeholder="6:00 AM">

        <label>Trainer Name</label>
        <input type="text" name="trainer_name" placeholder="Nimal Silva">

        <button class="btn btn-green" type="submit" name="add_program">Add Program</button>
      </form>
    </div>

    <div class="card">
      <h2>Program List</h2>
      <table>
        <tr>
          <th>ID</th>
          <th>Day</th>
          <th>Time</th>
          <th>Title</th>
          <th>Trainer</th>
          <th>Description</th>
          <th>Action</th>
        </tr>
        <?php if (!empty($programs)): ?>
          <?php foreach($programs as $p): ?>
          <tr>
            <td><?= (int)$p['id'] ?></td>
            <td><?= htmlspecialchars($p['day_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['time_slot'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['title'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['trainer_name'] ?? '') ?></td>
            <td class="desc"><?= nl2br(htmlspecialchars($p['description'] ?? '')) ?></td>
            <td>
              <a class="btn btn-red" href="?delete=<?= (int)$p['id'] ?>" onclick="return confirm('Delete this program?')">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7">No programs found.</td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>
</body>
</html>
<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$programs = [];

$sql = "
SELECT 
    p.id,
    p.title,
    p.description,
    p.day_name,
    p.time_slot,
    p.trainer_name,
    p.schedule,
    pe.enrolled_at,
    pe.status
FROM program_enrollments pe
INNER JOIN programs p ON pe.program_id = p.id
WHERE pe.user_id = ?
ORDER BY pe.enrolled_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $programs[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Programs</title>

<style>
body{
font-family:Arial;
background:#f5f7fb;
}

.container{
max-width:1000px;
margin:auto;
padding:40px;
}

.card{
background:white;
padding:25px;
border-radius:12px;
margin-bottom:20px;
box-shadow:0 10px 20px rgba(0,0,0,0.08);
}

.status{
background:#dcfce7;
color:#166534;
padding:5px 10px;
border-radius:6px;
font-size:13px;
font-weight:bold;
}
</style>

</head>

<body>

<div class="container">

<h1>My Programs</h1>

<?php if(!empty($programs)): ?>

<?php foreach($programs as $program): ?>

<div class="card">

<h2><?= htmlspecialchars($program['title']) ?></h2>

<p><?= htmlspecialchars($program['description']) ?></p>

<p><b>Trainer:</b> <?= htmlspecialchars($program['trainer_name']) ?></p>

<p><b>Day:</b> <?= htmlspecialchars($program['day_name']) ?></p>

<p><b>Time:</b> <?= htmlspecialchars($program['time_slot']) ?></p>

<p><b>Schedule:</b> <?= htmlspecialchars($program['schedule']) ?></p>

<p><b>Joined:</b> <?= date("d M Y", strtotime($program['enrolled_at'])) ?></p>

<span class="status"><?= htmlspecialchars($program['status']) ?></span>

</div>

<?php endforeach; ?>

<?php else: ?>

<p>No programs joined yet.</p>

<?php endif; ?>

</div>

</body>
</html>
<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* Protect page */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$program_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($program_id <= 0) {
    die("Invalid program ID.");
}

/* Get program */
$stmt = $conn->prepare("
SELECT id, title, description, day_name, time_slot, trainer_name, schedule 
FROM programs 
WHERE id = ? 
LIMIT 1
");

$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();
$program = $result->fetch_assoc();
$stmt->close();

if (!$program) {
    die("Program not found.");
}

/* Check enrollment */
$isEnrolled = false;

$check = $conn->prepare("SELECT id FROM program_enrollments WHERE user_id = ? AND program_id = ? LIMIT 1");
$check->bind_param("ii", $user_id, $program_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows > 0) {
    $isEnrolled = true;
}

$check->close();
?>

<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($program['title']) ?> | FitZone</title>

<style>

body{
font-family:Arial;
background:#f5f7fb;
margin:0;
padding:0;
}

.container{
max-width:900px;
margin:auto;
padding:40px;
}

.card{
background:#fff;
padding:40px;
border-radius:15px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

h1{
margin-bottom:15px;
}

.info{
margin-top:20px;
}

.info p{
margin:8px 0;
font-size:16px;
}

.btn{
display:inline-block;
padding:12px 20px;
border-radius:8px;
text-decoration:none;
font-weight:bold;
margin-top:20px;
}

.btn-primary{
background:#2563eb;
color:white;
}

.btn-success{
background:#16a34a;
color:white;
}

.btn-outline{
border:1px solid #ddd;
color:#333;
}

.enrolled{
background:#dcfce7;
padding:12px;
border-radius:8px;
margin-top:15px;
color:#166534;
font-weight:bold;
}

</style>
</head>

<body>

<div class="container">

<a href="../programs.php" class="btn btn-outline">← Back to Programs</a>

<div class="card">

<h1><?= htmlspecialchars($program['title']) ?></h1>

<p><?= nl2br(htmlspecialchars($program['description'])) ?></p>

<div class="info">

<p><strong>Trainer:</strong> <?= htmlspecialchars($program['trainer_name']) ?></p>

<p><strong>Day:</strong> <?= htmlspecialchars($program['day_name']) ?></p>

<p><strong>Time:</strong> <?= htmlspecialchars($program['time_slot']) ?></p>

<p><strong>Schedule:</strong> <?= htmlspecialchars($program['schedule']) ?></p>

</div>

<?php if ($isEnrolled): ?>

<div class="enrolled">✅ You are already enrolled in this program</div>

<a href="my-programs.php" class="btn btn-success">View My Programs</a>

<?php else: ?>

<a href="join-program.php?id=<?= $program['id'] ?>" class="btn btn-primary">Join Program</a>

<?php endif; ?>

</div>

</div>

</body>
</html>
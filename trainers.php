<?php
session_start();
require_once __DIR__ . "/config/db.php";

$trainers = [];
if ($result = $conn->query("SELECT id, name, specialty, experience, profile_image FROM trainers ORDER BY id DESC")) {
    while ($row = $result->fetch_assoc()) {
        $trainers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trainers | FitZone</title>
  <style>
    body{
      margin:0;
      font-family:Arial, sans-serif;
      background:#f4f7fb;
    }
    .trainers-section{
      padding:60px 20px;
      min-height:100vh;
    }
    .container{
      max-width:1200px;
      margin:0 auto;
    }
    .title{
      text-align:center;
      font-size:42px;
      margin-bottom:14px;
      color:#111827;
    }
    .sub{
      text-align:center;
      color:#64748b;
      margin-bottom:40px;
      font-size:17px;
    }
    .grid{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
      gap:22px;
    }
    .card{
      background:#fff;
      border-radius:20px;
      padding:22px;
      box-shadow:0 10px 30px rgba(0,0,0,.06);
      text-align:center;
    }
    .trainer-img{
      width:120px;
      height:120px;
      border-radius:50%;
      object-fit:cover;
      margin-bottom:16px;
      background:#e5e7eb;
      display:block;
      margin-left:auto;
      margin-right:auto;
      border:4px solid #f1f5f9;
    }
    .card h3{
      font-size:22px;
      margin-bottom:8px;
      color:#111827;
    }
    .specialty{
      color:#22c55e;
      font-weight:700;
      margin-bottom:10px;
    }
    .bio{
      color:#64748b;
      line-height:1.6;
      margin:0;
    }
    .empty{
      grid-column:1/-1;
      text-align:center;
      background:#fff;
      padding:30px;
      border-radius:18px;
    }
  </style>
</head>
<body>

<?php include __DIR__ . "/includes/navbar.php"; ?>

<section class="trainers-section">
  <div class="container">
    <h1 class="title">Our Trainers</h1>
    <p class="sub">Meet our professional fitness experts.</p>

    <div class="grid">
      <?php if (!empty($trainers)): ?>
        <?php foreach ($trainers as $trainer): ?>
          <?php
            $imagePath = !empty($trainer['profile_image'])
              ? "uploads/trainers/" . htmlspecialchars($trainer['profile_image'])
              : "https://via.placeholder.com/120x120?text=Trainer";
          ?>
          <div class="card">
            <img 
              src="<?= $imagePath ?>"
              alt="<?= htmlspecialchars($trainer['name']) ?>"
              class="trainer-img"
            >
            <h3><?= htmlspecialchars($trainer['name']) ?></h3>
            <p class="specialty"><?= htmlspecialchars($trainer['specialty']) ?></p>
            <p class="bio">
              <?= !empty($trainer['experience']) 
                    ? htmlspecialchars($trainer['experience']) 
                    : 'Certified professional trainer at FitZone.' ?>
            </p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty">
          No trainers available right now.
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

</body>
</html>
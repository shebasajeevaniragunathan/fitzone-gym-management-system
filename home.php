
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitZone Fitness Center</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
</head>
<body>

<?php include __DIR__ . "/includes/navbar.php"; ?>

<!-- HERO SECTION -->
<section class="hero reveal" style="position:relative; height:100vh; width:100%; overflow:hidden;">

  <!-- Background Image -->
  <img 
    src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=1920&q=80"
    alt="Gym Workout"
    style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover;"
  >

  <!-- Dark Overlay -->
  <div style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.45);"></div>

  <!-- Center Text -->
  <div style="
      position:absolute;
      top:50%;
      left:50%;
      transform:translate(-50%,-50%);
      text-align:center;
      color:white;
      max-width:900px;
      width:90%;
  ">

    <h1 style="font-size:64px; font-weight:800;">Push Your Limits</h1>

    <p style="font-size:22px; margin-top:12px;">
      Transform Your Body. Transform Your Life.
    </p>

    <a href="auth/register.php" class="btn" style="margin-top:25px;">Join Now</a>

  </div>

</section>



<!-- ABOUT SECTION -->
<section class="about reveal">
  <div class="container">
    <h2>About FitZone</h2>
    <p>FitZone Fitness Center is a modern gym located in Kurunegala offering cardio, strength training, yoga, and personalized coaching programs.</p>
  </div>
</section>



<!-- OUR PROGRAMS SECTION -->
<section class="services reveal">
  <div class="container">
    <h2>Our Programs</h2>

    <div class="card-container">

      <div class="card">
        <img src="images/weight-loss.jpg" alt="Weight Loss Program">
        <div class="card-content">
          <h3>Weight Loss Program</h3>
          <p>
            A complete fat-burning program including cardio workouts, guided exercises,
            and nutrition-focused support.
          </p>

          <div class="program-meta">
            <span>⏰ 45 mins</span>
            <span>🔥 Beginner</span>
          </div>

          <a href="programs.php" class="join-btn">Join Program</a>
        </div>
      </div>

      <div class="card">
        <img src="images/strength-training.jpg" alt="Strength Training">
        <div class="card-content">
          <h3>Strength Training</h3>
          <p>
            Build muscle mass, power, and endurance with focused resistance and weight
            training sessions.
          </p>

          <div class="program-meta">
            <span>⏰ 60 mins</span>
            <span>💪 Intermediate</span>
          </div>

          <a href="programs.php" class="join-btn">Join Program</a>
        </div>
      </div>

      <div class="card">
        <img src="images/cardio-fitness.jpg" alt="Cardio Fitness">
        <div class="card-content">
          <h3>Cardio Fitness</h3>
          <p>
            Improve heart health, endurance, and overall stamina through high-energy
            cardio workouts.
          </p>

          <div class="program-meta">
            <span>⏰ 40 mins</span>
            <span>🏃 All Levels</span>
          </div>

          <a href="programs.php" class="join-btn">Join Program</a>
        </div>
      </div>

    </div>

    <div class="see-all-wrap">
      <a href="programs.php" class="see-all-btn">See All Programs</a>
    </div>
  </div>
</section>

<!-- MEMBERSHIP SECTION -->
<section class="membership reveal">
  <div class="container">
    <h2>Membership Plans</h2>

    <div class="card-container">
      <?php
      require_once __DIR__ . "/config/db.php";

      $plans = [];
      $sql = "SELECT id, name, price, duration, description
              FROM memberships
              ORDER BY price ASC";

      if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
          $plans[] = $row;
        }
        $res->free();
      }

      if (count($plans) === 0):
      ?>
        <div class="card">
          <h3>No plans available</h3>
          <p>Please check back later.</p>
        </div>
      <?php else: ?>
        <?php foreach ($plans as $p): ?>
          <div class="card">
            <h3><?= htmlspecialchars($p['name']) ?></h3>

            <p>
              <strong>LKR <?= number_format((float)$p['price'], 2) ?></strong>
              / <?= (int)$p['duration'] ?> Days
            </p>

            <?php if (!empty($p['description'])): ?>
              <p style="opacity:0.9; line-height:1.6;">
                <?= nl2br(htmlspecialchars($p['description'])) ?>
              </p>
            <?php else: ?>
              <p style="opacity:0.8; line-height:1.6;">
                More membership details will be updated soon.
              </p>
            <?php endif; ?>

            <a href="register.php" class="join-btn">Join Now</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- TRAINERS SECTION -->
<section class="trainers reveal">
  <div class="container">
    <h2>Meet Our Trainers</h2>
    <p>Certified trainers to guide your fitness journey.</p>

    <div class="trainer-grid">
      <?php
      require_once __DIR__ . "/config/db.php";

      $trainers = [];
      $res = $conn->query("SELECT * FROM trainers ORDER BY id DESC");

      if ($res) {
          while ($row = $res->fetch_assoc()) {
              $trainers[] = $row;
          }
      }

      if (!empty($trainers)):
          foreach ($trainers as $trainer):
      ?>
        <div class="trainer-card">
          <?php if (!empty($trainer['profile_image']) && file_exists(__DIR__ . "/uploads/trainers/" . $trainer['profile_image'])): ?>
            <img src="uploads/trainers/<?= htmlspecialchars($trainer['profile_image']) ?>" alt="<?= htmlspecialchars($trainer['name']) ?>">
          <?php else: ?>
            <img src="https://via.placeholder.com/400x260?text=Trainer" alt="Trainer">
          <?php endif; ?>

          <div class="trainer-info">
            <h3><?= htmlspecialchars($trainer['name']) ?></h3>
            <span><?= htmlspecialchars($trainer['specialty'] ?? 'Fitness Trainer') ?></span>
            <p><?= htmlspecialchars($trainer['experience'] ?? 'Professional trainer at FitZone.') ?></p>
          </div>
        </div>
      <?php
          endforeach;
      else:
      ?>
        <div class="trainer-card">
          <img src="https://via.placeholder.com/400x260?text=Trainer" alt="Trainer">
          <div class="trainer-info">
            <h3>No Trainers Yet</h3>
            <span>FitZone</span>
            <p>Trainer details will be updated soon.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- WEEKLY CLASS SCHEDULE -->
<section class="schedule reveal">
  <div class="container">
    <h2>Weekly Class Schedule</h2>
    <p>Choose a class that fits your routine.</p>

    <div class="schedule-table-wrap">
      <table class="schedule-table">
        <thead>
          <tr>
            <th>Day</th>
            <th>Time</th>
            <th>Class</th>
            <th>Trainer</th>
          </tr>
        </thead>
        <tbody>
          <?php
          require_once __DIR__ . "/config/db.php";

          $programs = [];
          $sql = "
            SELECT day_name, time_slot, title, trainer_name
            FROM programs
            ORDER BY FIELD(day_name, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), id ASC
          ";

          if ($res = $conn->query($sql)) {
              while ($row = $res->fetch_assoc()) {
                  $programs[] = $row;
              }
              $res->free();
          }

          if (!empty($programs)):
              foreach ($programs as $row):
          ?>
            <tr>
              <td><?= htmlspecialchars($row['day_name'] ?? '') ?></td>
              <td>🕒 <?= htmlspecialchars($row['time_slot'] ?? '') ?></td>
              <td>🏋️ <?= htmlspecialchars($row['title'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['trainer_name'] ?? 'FitZone Trainer') ?></td>
            </tr>
          <?php
              endforeach;
          else:
          ?>
            <tr>
              <td colspan="4" style="text-align:center;">No class schedule available right now.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- TESTIMONIALS SLIDER SECTION -->
<section class="testimonials reveal">
  <div class="container">
    <h2>What Members Say</h2>
    <p class="sub">Real feedback from FitZone members.</p>

    <div class="slider" id="testimonialSlider">
      <div class="slide active">
        <p>“FitZone helped me lose 8kg in 2 months. Trainers are super supportive!”</p>
        <h4>— Kavindi, Kurunegala</h4>
      </div>

      <div class="slide">
        <p>“Clean gym, great equipment, and the yoga sessions are amazing.”</p>
        <h4>— Ruwan, Kurunegala</h4>
      </div>

      <div class="slide">
        <p>“The trainers are extremely motivating and knowledgeable. I gained muscle and confidence in just 3 months!”</p>
        <h4>— Dinesh, Kurunegala</h4>
      </div>

      <div class="slide">
        <p>“FitZone feels like family. The flexible class schedule helps me balance work and fitness perfectly.”</p>
        <h4>— Tharushi, Kurunegala</h4>
      </div>

      <div class="slide">
        <p>“Membership plans are affordable and the schedule fits my work hours.”</p>
        <h4>— Sahan, Kurunegala</h4>
      </div>

      <div class="slider-controls">
        <button class="sbtn" id="prevBtn" type="button">‹</button>
        <button class="sbtn" id="nextBtn" type="button">›</button>
      </div>
    </div>
  </div>
</section>

<!-- Blogs section-->
<?php
// make sure $conn exists (you already included db.php above in memberships section)
// if not, uncomment this:
// require_once __DIR__ . "/config/db.php";

$sql = "
SELECT b.id, b.title, b.content, b.created_at, b.views, c.name AS category
FROM blogs b
LEFT JOIN categories c ON c.id = b.category_id
ORDER BY b.created_at DESC
LIMIT 3
";
$result = $conn->query($sql);
?>

<section class="blog-preview reveal" id="blog">
  <div class="container">
    <h2>Latest From Our Blog</h2>
    <p class="sub">Workout routines, healthy recipes & success stories.</p>

    <div class="blog-grid">
      <?php if($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <div class="blog-card">
            <p style="opacity:.85; font-size:13px; margin-bottom:8px;">
              🏷 <?= htmlspecialchars($row['category'] ?? 'General') ?> • 👁 <?= (int)($row['views'] ?? 0) ?> views
            </p>
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <p><?= substr(strip_tags($row['content']), 0, 120) ?>...</p>
            <a href="blog.php?id=<?= (int)$row['id'] ?>" class="link">Read More →</a>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="blog-card">
          <h3>No posts yet</h3>
          <p>We’ll add new fitness tips soon 💪</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- CALL TO ACTION -->
<section class="cta reveal">
  <div class="container">
    <h2>Ready to Transform Your Life?</h2>
    <a href="register.php" class="btn">Join FitZone Today</a>
  </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>

<script src="js/script.js"></script>
</body>
</html>
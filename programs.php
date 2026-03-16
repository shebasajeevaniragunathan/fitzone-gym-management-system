<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isCustomerLoggedIn = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'customer');

function programLink(int $programId, bool $isCustomerLoggedIn): string {
    if ($isCustomerLoggedIn) {
        return "customer/program-details.php?id=" . $programId;
    }
    return "register.php";
}

function programButtonText(bool $isCustomerLoggedIn): string {
    return $isCustomerLoggedIn ? "View Details" : "Join Program";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Programs | FitZone</title>
  <style>
    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
      font-family:Arial, Helvetica, sans-serif;
    }

    body{
      background:#f5f5f5;
      color:#222;
      line-height:1.6;
    }

    a{
      text-decoration:none;
    }

    .nav{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:15px 40px;
      background:#111;
      position:sticky;
      top:0;
      z-index:1000;
    }

    .nav-left .brand{
      color:#fff;
      font-size:28px;
      font-weight:bold;
    }

    .nav-right{
      display:flex;
      gap:20px;
      align-items:center;
      flex-wrap:wrap;
    }

    .nav-right a{
      color:#fff;
      font-size:15px;
      transition:0.3s;
    }

    .nav-right a:hover,
    .nav-right a.active{
      color:#ff3c00;
    }

    .register-btn{
      background:#ff3c00;
      padding:10px 18px;
      border-radius:6px;
      color:#fff !important;
      font-weight:bold;
    }

    .register-btn:hover{
      background:#e63600;
    }

    .hero{
      min-height:55vh;
      background:linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
      url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1400&q=80');
      background-size:cover;
      background-position:center;
      display:flex;
      align-items:center;
      justify-content:center;
      text-align:center;
      color:#fff;
      padding:40px 20px;
    }

    .hero-content{
      max-width:800px;
    }

    .hero h1{
      font-size:50px;
      margin-bottom:15px;
    }

    .hero p{
      font-size:18px;
      color:#eee;
    }

    .container{
      width:90%;
      max-width:1200px;
      margin:auto;
    }

    .section{
      padding:70px 0;
    }

    .section-title{
      text-align:center;
      margin-bottom:45px;
    }

    .section-title h2{
      font-size:36px;
      color:#111;
      margin-bottom:10px;
    }

    .section-title p{
      color:#666;
      max-width:700px;
      margin:auto;
    }

    .program-grid{
      display:grid;
      grid-template-columns:repeat(3, 1fr);
      gap:25px;
    }

    .program-card{
      background:#fff;
      border-radius:16px;
      overflow:hidden;
      box-shadow:0 8px 20px rgba(0,0,0,0.08);
      transition:0.3s;
    }

    .program-card:hover{
      transform:translateY(-8px);
    }

    .program-card img{
      width:100%;
      height:240px;
      object-fit:cover;
    }

    .program-info{
      padding:22px;
    }

    .program-info h3{
      font-size:24px;
      color:#111;
      margin-bottom:10px;
    }

    .program-info p{
      color:#666;
      margin-bottom:15px;
      font-size:15px;
    }

    .program-meta{
      display:flex;
      justify-content:space-between;
      font-size:14px;
      color:#444;
      margin-bottom:18px;
      font-weight:600;
    }

    .btn{
      display:inline-block;
      background:#ff3c00;
      color:#fff;
      padding:10px 18px;
      border-radius:8px;
      font-weight:bold;
      transition:0.3s;
    }

    .btn:hover{
      background:#e63600;
    }

    .benefits{
      background:#111;
      color:#fff;
    }

    .benefit-grid{
      display:grid;
      grid-template-columns:repeat(4, 1fr);
      gap:20px;
      margin-top:30px;
    }

    .benefit-box{
      background:#1c1c1c;
      padding:25px 20px;
      border-radius:14px;
      text-align:center;
      border-top:4px solid #ff3c00;
    }

    .benefit-box h3{
      font-size:20px;
      margin-bottom:10px;
    }

    .benefit-box p{
      color:#ddd;
      font-size:14px;
    }

    .cta{
      background:linear-gradient(135deg,#ff3c00,#ff6a00);
      color:#fff;
      text-align:center;
      padding:60px 20px;
      border-radius:20px;
    }

    .cta h2{
      font-size:36px;
      margin-bottom:15px;
    }

    .cta p{
      max-width:700px;
      margin:0 auto 20px;
      font-size:17px;
    }

    .cta .btn{
      background:#fff;
      color:#111;
    }

    .cta .btn:hover{
      background:#f2f2f2;
    }

    .footer{
      background:#0d0d0d;
      color:#ccc;
      text-align:center;
      padding:22px 15px;
      margin-top:60px;
    }

    @media (max-width: 992px){
      .program-grid{
        grid-template-columns:repeat(2, 1fr);
      }

      .benefit-grid{
        grid-template-columns:repeat(2, 1fr);
      }
    }

    @media (max-width: 768px){
      .nav{
        flex-direction:column;
        gap:15px;
        padding:15px 20px;
      }

      .nav-right{
        justify-content:center;
      }

      .hero h1{
        font-size:34px;
      }

      .section-title h2,
      .cta h2{
        font-size:28px;
      }

      .program-grid,
      .benefit-grid{
        grid-template-columns:1fr;
      }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . "/includes/navbar.php"; ?>

  <section class="hero">
    <div class="hero-content">
      <h1>Our Fitness Programs</h1>
      <p>
        Explore our professional training programs designed to help you build strength,
        lose weight, improve stamina, and stay healthy.
      </p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-title">
        <h2>Choose Your Program</h2>
        <p>
          FitZone offers a variety of programs suitable for beginners, intermediate members,
          and advanced fitness enthusiasts.
        </p>
      </div>

      <div class="program-grid">

        <div class="program-card">
          <img src="https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=900&q=80" alt="Weight Loss Program">
          <div class="program-info">
            <h3>Weight Loss Program</h3>
            <p>
              A complete fat-burning program including cardio workouts, guided exercises,
              and nutrition-focused support.
            </p>
            <div class="program-meta">
              <span>⏰ 45 mins</span>
              <span>🔥 Beginner</span>
            </div>
            <a href="<?= htmlspecialchars(programLink(1, $isCustomerLoggedIn)); ?>" class="btn">
              <?= htmlspecialchars(programButtonText($isCustomerLoggedIn)); ?>
            </a>
          </div>
        </div>

        <div class="program-card">
          <img src="https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=900&q=80" alt="Strength Training">
          <div class="program-info">
            <h3>Strength Training</h3>
            <p>
              Build muscle mass, power, and endurance with focused resistance and weight
              training sessions.
            </p>
            <div class="program-meta">
              <span>⏰ 60 mins</span>
              <span>💪 Intermediate</span>
            </div>
            <a href="<?= htmlspecialchars(programLink(2, $isCustomerLoggedIn)); ?>" class="btn">
              <?= htmlspecialchars(programButtonText($isCustomerLoggedIn)); ?>
            </a>
          </div>
        </div>

        <div class="program-card">
          <img src="https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=900&q=80" alt="Cardio Fitness">
          <div class="program-info">
            <h3>Cardio Fitness</h3>
            <p>
              Improve heart health, endurance, and overall stamina through high-energy cardio
              workouts.
            </p>
            <div class="program-meta">
              <span>⏰ 40 mins</span>
              <span>🏃 All Levels</span>
            </div>
            <a href="<?= htmlspecialchars(programLink(3, $isCustomerLoggedIn)); ?>" class="btn">
              <?= htmlspecialchars(programButtonText($isCustomerLoggedIn)); ?>
            </a>
          </div>
        </div>

        <div class="program-card">
          <img src="https://images.unsplash.com/photo-1549476464-37392f717541?auto=format&fit=crop&w=900&q=80" alt="Bodybuilding">
          <div class="program-info">
            <h3>Bodybuilding</h3>
            <p>
              Specialized training plan for muscle growth, symmetry, and advanced body
              transformation goals.
            </p>
            <div class="program-meta">
              <span>⏰ 75 mins</span>
              <span>🏆 Advanced</span>
            </div>
            <a href="<?= htmlspecialchars(programLink(4, $isCustomerLoggedIn)); ?>" class="btn">
              <?= htmlspecialchars(programButtonText($isCustomerLoggedIn)); ?>
            </a>
          </div>
        </div>

        <div class="program-card">
          <img src="https://images.unsplash.com/photo-1518310383802-640c2de311b2?auto=format&fit=crop&w=900&q=80" alt="Yoga and Flexibility">
          <div class="program-info">
            <h3>Yoga & Flexibility</h3>
            <p>
              Enhance flexibility, balance, breathing, and relaxation with guided yoga
              sessions and stretch routines.
            </p>
            <div class="program-meta">
              <span>⏰ 50 mins</span>
              <span>🧘 Beginner</span>
            </div>
            <a href="<?= htmlspecialchars(programLink(5, $isCustomerLoggedIn)); ?>" class="btn">
              <?= htmlspecialchars(programButtonText($isCustomerLoggedIn)); ?>
            </a>
          </div>
        </div>

        <div class="program-card">
          <img src="https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=900&q=80" alt="Personal Training">
          <div class="program-info">
            <h3>Personal Training</h3>
            <p>
              Get one-to-one guidance from expert trainers with customized workout plans
              based on your fitness goals.
            </p>
            <div class="program-meta">
              <span>⏰ Flexible</span>
              <span>👨‍🏫 Custom</span>
            </div>
            <a href="<?= htmlspecialchars(programLink(6, $isCustomerLoggedIn)); ?>" class="btn">
              <?= htmlspecialchars(programButtonText($isCustomerLoggedIn)); ?>
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>

  <section class="section benefits">
    <div class="container">
      <div class="section-title">
        <h2 style="color:#fff;">Why Our Programs Work</h2>
        <p style="color:#ddd;">
          Every FitZone program is carefully designed to deliver real, measurable fitness results.
        </p>
      </div>

      <div class="benefit-grid">
        <div class="benefit-box">
          <h3>Expert Guidance</h3>
          <p>Certified trainers support you with proper workout methods and motivation.</p>
        </div>
        <div class="benefit-box">
          <h3>Flexible Schedules</h3>
          <p>Choose programs that fit your time and daily routine comfortably.</p>
        </div>
        <div class="benefit-box">
          <h3>Goal Based Plans</h3>
          <p>Programs are structured for weight loss, muscle gain, endurance, and wellness.</p>
        </div>
        <div class="benefit-box">
          <h3>Modern Facilities</h3>
          <p>Train in a clean, energetic, and fully equipped gym environment.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="cta">
        <h2>Start Your Fitness Journey Today</h2>
        <p>
          Join FitZone and become part of a motivating fitness community with the right
          program for your body and goals.
        </p>
        <a href="memberships.php" class="btn">View Membership Plans</a>
      </div>
    </div>
  </section>

  <footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> FitZone Gym. All Rights Reserved.</p>
  </footer>

</body>
</html>
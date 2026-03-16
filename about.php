<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Gym | FitZone</title>
  <style>
    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
      font-family: Arial, Helvetica, sans-serif;
    }

    body{
      background:#f5f5f5;
      color:#222;
      line-height:1.6;
    }

    a{
      text-decoration:none;
    }

    /* ===== Navbar fallback styling ===== */
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
      letter-spacing:1px;
    }

    .nav-right{
      display:flex;
      align-items:center;
      gap:20px;
      flex-wrap:wrap;
    }

    .nav-right a{
      color:#fff;
      font-size:15px;
      transition:0.3s;
    }

    .nav-right a:hover{
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

    /* ===== Hero ===== */
    .about-hero{
      min-height:70vh;
      background:
        linear-gradient(rgba(0,0,0,0.60), rgba(0,0,0,0.60)),
        url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1400&q=80');
      background-size:cover;
      background-position:center;
      display:flex;
      align-items:center;
      justify-content:center;
      text-align:center;
      color:#fff;
      padding:40px 20px;
    }

    .about-hero-content{
      max-width:850px;
    }

    .about-hero h1{
      font-size:52px;
      margin-bottom:18px;
      font-weight:800;
    }

    .about-hero p{
      font-size:19px;
      color:#f1f1f1;
      max-width:700px;
      margin:0 auto 25px;
    }

    .hero-btns{
      display:flex;
      justify-content:center;
      gap:15px;
      flex-wrap:wrap;
    }

    .btn{
      display:inline-block;
      padding:12px 24px;
      border-radius:8px;
      font-weight:bold;
      transition:0.3s;
    }

    .btn-primary{
      background:#ff3c00;
      color:#fff;
    }

    .btn-primary:hover{
      background:#e63600;
      transform:translateY(-2px);
    }

    .btn-outline{
      border:2px solid #fff;
      color:#fff;
    }

    .btn-outline:hover{
      background:#fff;
      color:#111;
    }

    /* ===== Common ===== */
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
      margin-bottom:12px;
    }

    .section-title p{
      color:#666;
      max-width:700px;
      margin:auto;
      font-size:16px;
    }

    /* ===== About intro ===== */
    .about-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:40px;
      align-items:center;
    }

    .about-image img{
      width:100%;
      border-radius:16px;
      box-shadow:0 8px 20px rgba(0,0,0,0.15);
    }

    .about-text h2{
      font-size:36px;
      margin-bottom:20px;
      color:#111;
    }

    .about-text p{
      color:#555;
      margin-bottom:15px;
      font-size:16px;
      text-align:justify;
    }

    .about-list{
      margin-top:20px;
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:15px;
    }

    .about-list div{
      background:#fff;
      padding:14px 16px;
      border-radius:10px;
      box-shadow:0 4px 12px rgba(0,0,0,0.06);
      font-weight:600;
      color:#333;
    }

    /* ===== Mission vision ===== */
    .mv-section{
      background:#111;
      color:#fff;
    }

    .mv-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:30px;
    }

    .mv-card{
      background:#1c1c1c;
      padding:35px 28px;
      border-radius:16px;
      box-shadow:0 8px 18px rgba(0,0,0,0.25);
      border-left:5px solid #ff3c00;
    }

    .mv-card h3{
      font-size:28px;
      margin-bottom:15px;
      color:#fff;
    }

    .mv-card p{
      color:#ddd;
      font-size:16px;
    }

    /* ===== Why choose us ===== */
    .features-grid{
      display:grid;
      grid-template-columns:repeat(3, 1fr);
      gap:25px;
    }

    .feature-card{
      background:#fff;
      padding:30px 22px;
      border-radius:16px;
      text-align:center;
      box-shadow:0 8px 18px rgba(0,0,0,0.08);
      transition:0.3s;
    }

    .feature-card:hover{
      transform:translateY(-8px);
    }

    .feature-icon{
      font-size:38px;
      margin-bottom:15px;
    }

    .feature-card h3{
      margin-bottom:12px;
      color:#111;
      font-size:22px;
    }

    .feature-card p{
      color:#666;
      font-size:15px;
    }

    /* ===== Stats ===== */
    .stats{
      background:linear-gradient(135deg, #ff3c00, #ff6a00);
      color:#fff;
    }

    .stats-grid{
      display:grid;
      grid-template-columns:repeat(4, 1fr);
      gap:25px;
      text-align:center;
    }

    .stat-box{
      padding:25px 15px;
      background:rgba(255,255,255,0.10);
      border-radius:14px;
      backdrop-filter:blur(4px);
    }

    .stat-box h3{
      font-size:38px;
      margin-bottom:8px;
    }

    .stat-box p{
      font-size:16px;
      font-weight:600;
    }

    /* ===== Trainers preview ===== */
    .trainer-grid{
      display:grid;
      grid-template-columns:repeat(3, 1fr);
      gap:25px;
    }

    .trainer-card{
      background:#fff;
      border-radius:16px;
      overflow:hidden;
      box-shadow:0 8px 18px rgba(0,0,0,0.08);
      transition:0.3s;
    }

    .trainer-card:hover{
      transform:translateY(-6px);
    }

    .trainer-card img{
      width:100%;
      height:280px;
      object-fit:cover;
    }

    .trainer-info{
      padding:20px;
      text-align:center;
    }

    .trainer-info h3{
      margin-bottom:8px;
      color:#111;
      font-size:22px;
    }

    .trainer-info p{
      color:#ff3c00;
      font-weight:bold;
      margin-bottom:10px;
    }

    .trainer-info span{
      color:#666;
      font-size:15px;
    }

    /* ===== CTA ===== */
    .cta{
      background:#111;
      color:#fff;
      text-align:center;
      border-radius:20px;
      padding:55px 25px;
    }

    .cta h2{
      font-size:36px;
      margin-bottom:15px;
    }

    .cta p{
      max-width:700px;
      margin:0 auto 25px;
      color:#ddd;
      font-size:17px;
    }

    /* ===== Footer ===== */
    .footer{
      background:#0d0d0d;
      color:#ccc;
      padding:22px 15px;
      text-align:center;
      margin-top:60px;
    }

    .footer p{
      font-size:15px;
    }

    /* ===== Responsive ===== */
    @media (max-width: 992px){
      .about-grid,
      .mv-grid,
      .features-grid,
      .trainer-grid,
      .stats-grid{
        grid-template-columns:1fr 1fr;
      }

      .about-hero h1{
        font-size:42px;
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
        gap:12px;
      }

      .about-grid,
      .mv-grid,
      .features-grid,
      .trainer-grid,
      .stats-grid,
      .about-list{
        grid-template-columns:1fr;
      }

      .about-hero h1{
        font-size:34px;
      }

      .about-hero p{
        font-size:16px;
      }

      .section-title h2,
      .about-text h2,
      .cta h2{
        font-size:28px;
      }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <?php include "includes/navbar.php"; ?>

  <!-- Hero Section -->
  <section class="about-hero">
    <div class="about-hero-content">
      <h1>About FitZone Gym</h1>
      <p>
        Welcome to FitZone, where strength meets dedication. We are committed to helping
        you build a healthier lifestyle through expert guidance, modern equipment,
        personalized training, and a motivating fitness environment.
      </p>
      <div class="hero-btns">
        <a href="memberships.php" class="btn btn-primary">Join Now</a>
        <a href="contact.php" class="btn btn-outline">Contact Us</a>
      </div>
    </div>
  </section>

  <!-- About Intro -->
  <section class="section">
    <div class="container">
      <div class="about-grid">
        <div class="about-image">
          <img src="https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=1000&q=80" alt="About FitZone Gym">
        </div>
        <div class="about-text">
          <h2>Your Fitness Journey Starts Here</h2>
          <p>
            FitZone is more than just a gym. It is a place where individuals of all fitness
            levels come together to transform their bodies, improve their health, and boost
            their confidence. Whether you are a beginner or an experienced athlete, our
            fitness programs are designed to support your personal goals.
          </p>
          <p>
            Our gym provides a welcoming atmosphere, certified trainers, advanced workout
            equipment, and customized support to ensure every member achieves lasting
            results. At FitZone, we believe fitness is not only about appearance but also
            about discipline, energy, and overall well-being.
          </p>

          <div class="about-list">
            <div> Modern Equipment</div>
            <div> Certified Trainers</div>
            <div> Flexible Memberships</div>
            <div> Friendly Environment</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Mission & Vision -->
  <section class="section mv-section">
    <div class="container">
      <div class="section-title">
        <h2 style="color:white;">Our Mission & Vision</h2>
        <p style="color:#d5d5d5;">
          We are focused on building a strong fitness community and inspiring every member
          to live a healthier and more active life.
        </p>
      </div>

      <div class="mv-grid">
        <div class="mv-card">
          <h3>Our Mission</h3>
          <p>
            Our mission is to provide high-quality fitness services, professional guidance,
            and a supportive environment that empowers people to improve their physical and
            mental well-being through consistent exercise and healthy lifestyle habits.
          </p>
        </div>

        <div class="mv-card">
          <h3>Our Vision</h3>
          <p>
            Our vision is to become one of the most trusted and inspiring fitness centers,
            known for transforming lives by promoting strength, confidence, discipline,
            and long-term wellness for every member.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Choose Us -->
  <section class="section">
    <div class="container">
      <div class="section-title">
        <h2>Why Choose FitZone?</h2>
        <p>
          We combine quality facilities, expert support, and a motivating atmosphere to
          create the best fitness experience for our members.
        </p>
      </div>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">🏋️</div>
          <h3>Advanced Equipment</h3>
          <p>
            Train with modern gym machines and equipment designed for strength, cardio,
            endurance, and flexibility improvement.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">👨‍🏫</div>
          <h3>Expert Trainers</h3>
          <p>
            Our certified trainers help members stay focused, follow proper techniques,
            and achieve goals safely and effectively.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🔥</div>
          <h3>Motivating Environment</h3>
          <p>
            FitZone creates an energetic and friendly atmosphere where every member feels
            encouraged to stay committed.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🥗</div>
          <h3>Healthy Lifestyle Focus</h3>
          <p>
            We promote total wellness through fitness awareness, body transformation,
            healthy routines, and consistency.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">📅</div>
          <h3>Flexible Plans</h3>
          <p>
            Choose membership packages that suit your schedule, budget, and personal
            workout needs.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">💪</div>
          <h3>Results Driven</h3>
          <p>
            Our programs are designed to help members lose weight, build muscle, and
            improve overall fitness step by step.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats -->
  <section class="section stats">
    <div class="container">
      <div class="stats-grid">
        <div class="stat-box">
          <h3>500+</h3>
          <p>Active Members</p>
        </div>
        <div class="stat-box">
          <h3>15+</h3>
          <p>Expert Trainers</p>
        </div>
        <div class="stat-box">
          <h3>10+</h3>
          <p>Fitness Programs</p>
        </div>
        <div class="stat-box">
          <h3>5+</h3>
          <p>Years Experience</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Trainers Preview -->
  <section class="section">
    <div class="container">
      <div class="section-title">
        <h2>Meet Our Trainers</h2>
        <p>
          Our passionate trainers are here to guide, motivate, and support your fitness
          transformation journey.
        </p>
      </div>

      <div class="trainer-grid">
        <div class="trainer-card">
          <img src="https://images.unsplash.com/photo-1567013127542-490d757e6349?auto=format&fit=crop&w=900&q=80" alt="Trainer 1">
          <div class="trainer-info">
            <h3>John </h3>
            <p>Strength Coach</p>
            <span>Specialized in muscle building and strength training programs.</span>
          </div>
        </div>

        <div class="trainer-card">
          <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=900&q=80" alt="Trainer 2">
          <div class="trainer-info">
            <h3>Bhuvi Kumar</h3>
            <p>Fitness Trainer</p>
            <span>Focused on cardio, weight loss, and full-body conditioning.</span>
          </div>
        </div>

        <div class="trainer-card">
          <img src="https://images.unsplash.com/photo-1594737625785-a6cbdabd333c?auto=format&fit=crop&w=900&q=80" alt="Trainer 3">
          <div class="trainer-info">
            <h3>David </h3>
            <p>Personal Trainer</p>
            <span>Guides members with personalized workout and wellness plans.</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="section">
    <div class="container">
      <div class="cta">
        <h2>Ready to Transform Your Body?</h2>
        <p>
          Join FitZone today and take the first step toward a stronger, healthier, and
          more confident version of yourself. Your fitness journey begins now.
        </p>
        <a href="register.php" class="btn btn-primary">Get Started</a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include "includes/footer.php"; ?>

</body>
</html>
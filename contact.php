<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config/db.php";

$successMessage = "";
$errorMessage = "";

$name = "";
$email = "";
$phone = "";
$subject = "";
$messageText = "";

/* Show success message only once after redirect */
if (isset($_SESSION['contact_success'])) {
    $successMessage = $_SESSION['contact_success'];
    unset($_SESSION['contact_success']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $messageText = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $messageText === '') {
        $errorMessage = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS queries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(30) DEFAULT NULL,
                subject VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                response TEXT DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";

        if ($conn->query($createTableSQL)) {
            $stmt = $conn->prepare("
                INSERT INTO queries (name, email, phone, subject, message, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");

            if ($stmt) {
                $stmt->bind_param("sssss", $name, $email, $phone, $subject, $messageText);

                if ($stmt->execute()) {
                    $_SESSION['contact_success'] = "Thank you! Your message has been submitted successfully. We will get back to you soon.";
                    header("Location: contact.php");
                    exit;
                } else {
                    $errorMessage = "Sorry! Your message could not be saved. Please try again.";
                }

                $stmt->close();
            } else {
                $errorMessage = "Database error: Unable to prepare query.";
            }
        } else {
            $errorMessage = "Database error: Unable to create queries table.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us | FitZone</title>
  <style>
    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
      font-family: Arial, Helvetica, sans-serif;
    }

    body{
      background:#f5f7fa;
      color:#222;
      line-height:1.6;
    }

    a{
      text-decoration:none;
    }

    .container{
      width:90%;
      max-width:1200px;
      margin:auto;
    }

    /* Navbar */
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

    .nav-right a:hover,
    .nav-right a.active{
      color:#ff5a1f;
    }

    .register-btn{
      background:#ff5a1f;
      color:#fff !important;
      padding:10px 18px;
      border-radius:8px;
      font-weight:bold;
    }

    .register-btn:hover{
      background:#e24d15;
    }

    /* Hero */
    .contact-hero{
      background:linear-gradient(rgba(0,0,0,0.60), rgba(0,0,0,0.60)),
      url('assets/images/contact-banner.jpg');
      background-size:cover;
      background-position:center;
      min-height:42vh;
      display:flex;
      align-items:center;
      justify-content:center;
      text-align:center;
      padding:40px 20px;
      color:#fff;
    }

    .contact-hero h1{
      font-size:48px;
      margin-bottom:12px;
    }

    .contact-hero p{
      font-size:18px;
      max-width:700px;
      color:#eee;
      margin:auto;
    }

    /* Sections */
    .section{
      padding:70px 0;
    }

    .section-title{
      text-align:center;
      margin-bottom:40px;
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

    /* Contact cards */
    .contact-cards{
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:25px;
      margin-top:20px;
    }

    .contact-card{
      background:#fff;
      border-radius:18px;
      padding:30px 25px;
      text-align:center;
      box-shadow:0 8px 22px rgba(0,0,0,0.08);
      transition:0.3s;
    }

    .contact-card:hover{
      transform:translateY(-8px);
    }

    .contact-card .icon{
      font-size:34px;
      margin-bottom:15px;
    }

    .contact-card h3{
      font-size:22px;
      color:#111;
      margin-bottom:10px;
    }

    .contact-card p{
      color:#666;
      font-size:15px;
    }

    /* Contact form area */
    .contact-wrapper{
      display:grid;
      grid-template-columns:1.1fr 0.9fr;
      gap:30px;
      align-items:start;
    }

    .contact-form-box,
    .contact-info-box{
      background:#fff;
      border-radius:20px;
      padding:32px;
      box-shadow:0 8px 24px rgba(0,0,0,0.08);
    }

    .contact-form-box h3,
    .contact-info-box h3{
      font-size:28px;
      margin-bottom:20px;
      color:#111;
    }

    .form-group{
      margin-bottom:18px;
    }

    .form-group label{
      display:block;
      margin-bottom:8px;
      font-weight:600;
      color:#333;
    }

    .form-group input,
    .form-group textarea{
      width:100%;
      padding:14px 15px;
      border:1px solid #ddd;
      border-radius:10px;
      outline:none;
      font-size:15px;
      transition:0.3s;
      background:#fafafa;
    }

    .form-group input:focus,
    .form-group textarea:focus{
      border-color:#ff5a1f;
      background:#fff;
    }

    .form-group textarea{
      resize:none;
      min-height:140px;
    }

    .btn{
      display:inline-block;
      background:#ff5a1f;
      color:#fff;
      border:none;
      padding:14px 28px;
      border-radius:10px;
      font-size:16px;
      font-weight:700;
      cursor:pointer;
      transition:0.3s;
    }

    .btn:hover{
      background:#e24d15;
    }

    .alert{
      padding:14px 18px;
      border-radius:10px;
      margin-bottom:20px;
      font-size:15px;
      font-weight:600;
    }

    .alert-success{
      background:#dcfce7;
      color:#166534;
      border:1px solid #bbf7d0;
    }

    .alert-error{
      background:#fee2e2;
      color:#991b1b;
      border:1px solid #fecaca;
    }

    /* Side info */
    .info-item{
      margin-bottom:22px;
      padding-bottom:18px;
      border-bottom:1px solid #eee;
    }

    .info-item:last-child{
      border-bottom:none;
      margin-bottom:0;
      padding-bottom:0;
    }

    .info-item h4{
      font-size:18px;
      margin-bottom:8px;
      color:#111;
    }

    .info-item p{
      color:#666;
      font-size:15px;
    }

    .hours-list{
      display:grid;
      gap:10px;
      margin-top:10px;
    }

    .hours-row{
      display:flex;
      justify-content:space-between;
      gap:15px;
      padding:10px 0;
      border-bottom:1px dashed #e5e5e5;
      font-size:15px;
      color:#555;
    }

    /* Map */
    .map-box{
      background:#fff;
      border-radius:20px;
      overflow:hidden;
      box-shadow:0 8px 24px rgba(0,0,0,0.08);
    }

    .map-box iframe{
      width:100%;
      height:420px;
      border:0;
      display:block;
    }

    /* Footer */
    .footer{
      background:#0d0d0d;
      color:#ccc;
      text-align:center;
      padding:22px 15px;
      margin-top:50px;
    }

    /* Responsive */
    @media (max-width: 992px){
      .contact-cards{
        grid-template-columns:1fr 1fr;
      }

      .contact-wrapper{
        grid-template-columns:1fr;
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

      .contact-hero h1{
        font-size:34px;
      }

      .contact-hero p{
        font-size:16px;
      }

      .section-title h2{
        font-size:28px;
      }

      .contact-cards{
        grid-template-columns:1fr;
      }

      .contact-form-box,
      .contact-info-box{
        padding:24px;
      }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="nav">
    <div class="nav-left">
      <a class="brand" href="index.php">FitZone</a>
    </div>

    <div class="nav-right">
      <a href="index.php">Home</a>
      <a href="about.php">About Gym</a>
      <a href="programs.php">Programs</a>
      <a href="trainers.php">Trainers</a>
      <a href="memberships.php">Membership Plans</a>
      <a href="blog.php">Blogs</a>
      <a href="contact.php" class="active">Contact Us</a>
      <a href="register.php" class="register-btn">Register</a>
    </div>
  </nav>

  <!-- Hero -->
  <section class="contact-hero">
    <div>
      <h1>Contact Us</h1>
      <p>
        We would love to hear from you. Reach out to FitZone for membership inquiries,
        training details, support, or any questions about our gym services.
      </p>
    </div>
  </section>

  <!-- Contact cards -->
  <section class="section">
    <div class="container">
      <div class="section-title">
        <h2>Get In Touch</h2>
        <p>
          Contact our team through phone, email, or visit our gym location for more information.
        </p>
      </div>

      <div class="contact-cards">
        <div class="contact-card">
          <div class="icon">📍</div>
          <h3>Our Location</h3>
          <p>No. 25, Main Street, Kurunagala, Sri Lanka</p>
        </div>

        <div class="contact-card">
          <div class="icon">📞</div>
          <h3>Phone Number</h3>
          <p>+94 77 123 4567</p>
        </div>

        <div class="contact-card">
          <div class="icon">✉️</div>
          <h3>Email Address</h3>
          <p>info@fitzone.com</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact form + info -->
  <section class="section" style="padding-top:0;">
    <div class="container">
      <div class="contact-wrapper">

        <div class="contact-form-box">
          <h3>Send Us a Message</h3>

          <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
          <?php endif; ?>

          <?php if ($errorMessage): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
          <?php endif; ?>

          <form action="" method="POST">
            <div class="form-group">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($name); ?>">
            </div>

            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" placeholder="Enter your email address" required value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="text" id="phone" name="phone" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($phone); ?>">
            </div>

            <div class="form-group">
              <label for="subject">Subject</label>
              <input type="text" id="subject" name="subject" placeholder="Enter subject" required value="<?php echo htmlspecialchars($subject); ?>">
            </div>

            <div class="form-group">
              <label for="message">Message</label>
              <textarea id="message" name="message" placeholder="Write your message here..." required><?php echo htmlspecialchars($messageText); ?></textarea>
            </div>

            <button type="submit" class="btn">Send Message</button>
          </form>
        </div>

        <div class="contact-info-box">
          <h3>Contact Information</h3>

          <div class="info-item">
            <h4>Address</h4>
            <p>No. 25, Main Street, Kurunagala, Sri Lanka</p>
          </div>

          <div class="info-item">
            <h4>Call Us</h4>
            <p>+94 77 123 4567</p>
          </div>

          <div class="info-item">
            <h4>Email Us</h4>
            <p>info@fitzone.com</p>
          </div>

          <div class="info-item">
            <h4>Gym Opening Hours</h4>
            <div class="hours-list">
              <div class="hours-row">
                <span>Monday - Friday</span>
                <span>5:30 AM - 10:00 PM</span>
              </div>
              <div class="hours-row">
                <span>Saturday</span>
                <span>6:00 AM - 9:00 PM</span>
              </div>
              <div class="hours-row">
                <span>Sunday</span>
                <span>6:00 AM - 6:00 PM</span>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </section>

  <!-- Map -->
  <section class="section" style="padding-top:0;">
    <div class="container">
      <div class="section-title">
        <h2>Find Us on Map</h2>
        <p>Visit our gym and experience a motivating fitness environment.</p>
      </div>

      <div class="map-box">
        <iframe
          src="https://www.google.com/maps?q=Colombo,Sri%20Lanka&output=embed"
          allowfullscreen=""
          loading="lazy">
        </iframe>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> FitZone Gym. All Rights Reserved.</p>
  </footer>

</body>
</html>
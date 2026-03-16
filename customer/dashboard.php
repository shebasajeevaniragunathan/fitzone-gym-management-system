<?php
session_start();

// Protect customer page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_name = trim($_SESSION['name'] ?? '');
$email = trim($_SESSION['email'] ?? '');

if ($customer_name === '') {
    $customer_name = 'Customer';
}

if ($email === '') {
    $email = 'customer@fitzone.com';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Dashboard - FitZone</title>
  <link rel="stylesheet" href="customer-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo">
      <h2>FitZone</h2>
      <p>Customer Panel</p>
    </div>

    <ul class="nav-links">
      <li class="active">
        <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
      </li>
      <li>
        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
      </li>
      <li>
        <a href="edit-profile.php"><i class="fa-solid fa-user-pen"></i> Edit Profile</a>
      </li>
      <li>
        <a href="membership.php"><i class="fa-solid fa-id-card"></i> My Membership</a>
      </li>
      <li>
        <a href="bookings.php"><i class="fa-solid fa-calendar-check"></i> My Bookings</a>
      </li>
      
      <li>
        <a href="../programs.php"><i class="fa-solid fa-dumbbell"></i> Programs</a>
      </li>
      <li>
        <a href="../trainers.php"><i class="fa-solid fa-user-group"></i> Trainers</a>
      </li>
      <li>
        <a href="../queries.php"><i class="fa-solid fa-envelope"></i> Queries</a>
      </li>
      <li>
        <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <div>
        <h1>Welcome, <?php echo htmlspecialchars($customer_name); ?> 👋</h1>
        <p>Manage your fitness journey from your dashboard.</p>
      </div>

      <div class="user-box">
        <i class="fa-solid fa-circle-user"></i>
        <span><?php echo htmlspecialchars($email); ?></span>
      </div>
    </header>

    <!-- Stats Cards -->
    <section class="cards">
      <div class="card">
        <div class="icon blue">
          <i class="fa-solid fa-id-card"></i>
        </div>
        <div>
          <h3>Membership</h3>
          <p>View your membership details</p>
        </div>
      </div>

      <div class="card">
        <div class="icon green">
          <i class="fa-solid fa-dumbbell"></i>
        </div>
        <div>
          <h3>Programs</h3>
          <p>Explore training programs</p>
        </div>
      </div>

      <div class="card">
        <div class="icon orange">
          <i class="fa-solid fa-user-group"></i>
        </div>
        <div>
          <h3>Trainers</h3>
          <p>Meet our expert trainers</p>
        </div>
      </div>

      <div class="card">
        <div class="icon red">
          <i class="fa-solid fa-envelope"></i>
        </div>
        <div>
          <h3>Support</h3>
          <p>Contact FitZone anytime</p>
        </div>
      </div>
    </section>

    <!-- Welcome Section -->
    <section class="welcome-box">
      <div class="welcome-text">
        <h2>Your Fitness Journey Starts Here 🚀</h2>
        <p>
          Welcome to your customer dashboard. From here, you can explore membership plans,
          view gym programs, connect with trainers, and stay updated with FitZone services.
        </p>

        <div class="btn-group">
          <a href="../memberships.php" class="btn primary-btn">View Memberships</a>
          <a href="../programs.php" class="btn secondary-btn">Explore Programs</a>
        </div>
      </div>

      <div class="welcome-image">
        <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=900&q=80" alt="Fitness">
      </div>
    </section>

    <!-- Quick Actions -->
    <section class="quick-actions">
      <h2>Quick Actions</h2>

      <div class="action-grid">
        <a href="../memberships.php" class="action-box">
          <i class="fa-solid fa-credit-card"></i>
          <span>Membership Plans</span>
        </a>

        <a href="../trainers.php" class="action-box">
          <i class="fa-solid fa-user-group"></i>
          <span>Our Trainers</span>
        </a>

        <a href="../programs.php" class="action-box">
          <i class="fa-solid fa-dumbbell"></i>
          <span>Gym Programs</span>
        </a>

        <a href="../contact.php" class="action-box">
          <i class="fa-solid fa-headset"></i>
          <span>Contact Support</span>
        </a>
      </div>
    </section>

  </main>

</body>
</html>
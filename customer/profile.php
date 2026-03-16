<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, first_name, last_name, full_name, email, phone, role, status, created_at, profile_image FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Customer not found.");
}

$display_name = trim($user['full_name'] ?? '');
if ($display_name === '') {
    $display_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
}
if ($display_name === '') {
    $display_name = 'Customer';
}

$first_letter = strtoupper(substr($display_name, 0, 1));
$joined_date = !empty($user['created_at']) ? date("d M Y", strtotime($user['created_at'])) : 'N/A';
$profile_image = trim($user['profile_image'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - FitZone</title>
  <link rel="stylesheet" href="customer-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="main-content">

  <div class="profile-page-header">
  <div>
    <h1>My Profile</h1>
    <p>View and manage your FitZone account information.</p>
  </div>

  <div style="display:flex;gap:10px;">

    <a href="dashboard.php" class="back-btn">
      <i class="fa-solid fa-arrow-left"></i> Dashboard
    </a>

    <a href="edit-profile.php" class="top-edit-btn">
      <i class="fa-solid fa-pen-to-square"></i> Edit Profile
    </a>

  </div>
</div>

  <div class="modern-profile-card">
    
    <div class="profile-left">
      <div class="profile-avatar">
        <?php if ($profile_image !== ''): ?>
          <img src="<?php echo htmlspecialchars($profile_image); ?>?v=<?php echo time(); ?>" alt="Profile Image" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
        <?php else: ?>
          <?php echo htmlspecialchars($first_letter); ?>
        <?php endif; ?>
      </div>

      <h2><?php echo htmlspecialchars($display_name); ?></h2>
      <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>

      <div class="profile-badges">
        <span class="badge role-badge">
          <i class="fa-solid fa-user"></i>
          <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
        </span>

        <span class="badge status-badge">
          <i class="fa-solid fa-circle-check"></i>
          <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
        </span>
      </div>
    </div>

    <div class="profile-right">
      <h3>Personal Information</h3>

      <div class="info-grid">
        <div class="info-item">
          <span>First Name</span>
          <strong><?php echo htmlspecialchars($user['first_name'] ?? ''); ?></strong>
        </div>

        <div class="info-item">
          <span>Last Name</span>
          <strong><?php echo htmlspecialchars($user['last_name'] ?? ''); ?></strong>
        </div>

        <div class="info-item">
          <span>Full Name</span>
          <strong><?php echo htmlspecialchars($display_name); ?></strong>
        </div>

        <div class="info-item">
          <span>Email Address</span>
          <strong><?php echo htmlspecialchars($user['email']); ?></strong>
        </div>

        <div class="info-item">
          <span>Phone Number</span>
          <strong><?php echo htmlspecialchars($user['phone']); ?></strong>
        </div>

        <div class="info-item">
          <span>Joined On</span>
          <strong><?php echo htmlspecialchars($joined_date); ?></strong>
        </div>
      </div>
    </div>
  </div>

  <div class="profile-summary-grid">
    <div class="summary-card">
      <div class="summary-icon blue-bg">
        <i class="fa-solid fa-id-card"></i>
      </div>
      <div>
        <h4>Membership</h4>
        <p>Standard Plan</p>
      </div>
    </div>

    <div class="summary-card">
      <div class="summary-icon green-bg">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <div>
        <h4>Account Status</h4>
        <p><?php echo htmlspecialchars(ucfirst($user['status'])); ?></p>
      </div>
    </div>

    <div class="summary-card">
      <div class="summary-icon orange-bg">
        <i class="fa-solid fa-calendar-days"></i>
      </div>
      <div>
        <h4>Joined Date</h4>
        <p><?php echo htmlspecialchars($joined_date); ?></p>
      </div>
    </div>
  </div>

</div>

</body>
</html>
<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* ---------------------------
   Get current active membership
--------------------------- */
$current_plan = null;

$stmt = $conn->prepare("
    SELECT 
        cm.id,
        cm.user_id,
        cm.plan_id,
        cm.start_date,
        cm.expiry_date,
        cm.membership_status,
        m.name,
        m.price,
        m.billing_period,
        m.benefits,
        m.duration,
        m.description
    FROM customer_memberships cm
    INNER JOIN memberships m ON cm.plan_id = m.id
    WHERE cm.user_id = ? AND cm.membership_status = 'active'
    ORDER BY cm.id DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_plan = $result->fetch_assoc();
$stmt->close();

/* ---------------------------
   Get all active plans
--------------------------- */
$plans = [];
$res = $conn->query("
    SELECT id, name, price, billing_period, benefits, duration, description
    FROM memberships
    WHERE is_active = 1
    ORDER BY price ASC
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $plans[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Membership - FitZone</title>
  <link rel="stylesheet" href="customer-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .membership-wrapper{padding:30px;}
    .page-header{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin-bottom:25px;}
    .page-header h2{font-size:32px;color:#0f172a;margin-bottom:6px;}
    .page-header p{color:#64748b;margin:0;}
    .header-actions{display:flex;gap:10px;flex-wrap:wrap;}
    .back-btn,.btn-primary,.btn-secondary{
      display:inline-flex;align-items:center;gap:8px;padding:12px 18px;border-radius:10px;
      text-decoration:none;font-weight:600;transition:0.2s ease;border:none;cursor:pointer;
    }
    .back-btn{background:#64748b;color:#fff;}
    .back-btn:hover{background:#475569;}
    .btn-primary{background:#22c55e;color:#fff;}
    .btn-primary:hover{background:#16a34a;}
    .btn-secondary{background:#0f172a;color:#fff;}
    .btn-secondary:hover{background:#1e293b;}

    .membership-card{
      background:#fff;border-radius:20px;padding:28px;box-shadow:0 10px 30px rgba(0,0,0,0.06);
      margin-bottom:30px;border-left:5px solid #22c55e;
    }
    .membership-card h3{font-size:24px;color:#0f172a;margin-bottom:18px;}

    .membership-details{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
      gap:16px;
      margin-bottom:22px;
    }

    .detail-box{
      background:#f8fafc;
      border:1px solid #e5e7eb;
      border-radius:14px;
      padding:16px;
    }

    .detail-box span{
      display:block;
      color:#64748b;
      font-size:14px;
      margin-bottom:6px;
    }

    .detail-box strong{
      color:#0f172a;
      font-size:16px;
    }

    .status-badge{
      display:inline-block;
      padding:6px 12px;
      border-radius:999px;
      font-size:13px;
      font-weight:700;
      background:#dcfce7;
      color:#166534;
    }

    .section-title{
      font-size:26px;
      color:#0f172a;
      margin-bottom:18px;
    }

    .plans-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
      gap:22px;
    }

    .plan-card{
      background:#fff;
      border-radius:20px;
      padding:24px;
      box-shadow:0 10px 25px rgba(0,0,0,0.06);
      border:2px solid transparent;
      position:relative;
      transition:0.25s ease;
    }

    .plan-card:hover{
      transform:translateY(-4px);
      box-shadow:0 16px 35px rgba(0,0,0,0.08);
    }

    .plan-card.current{
      border-color:#22c55e;
    }

    .current-badge{
      position:absolute;
      top:16px;
      right:16px;
      background:#22c55e;
      color:#fff;
      font-size:12px;
      font-weight:700;
      padding:6px 10px;
      border-radius:999px;
    }

    .plan-card h4{
      font-size:22px;
      color:#0f172a;
      margin-bottom:8px;
    }

    .plan-price{
      font-size:28px;
      font-weight:800;
      color:#22c55e;
      margin-bottom:6px;
    }

    .plan-period{
      color:#64748b;
      margin-bottom:6px;
      font-weight:600;
    }

    .plan-duration{
      color:#64748b;
      margin-bottom:14px;
    }

    .plan-description{
      color:#475569;
      font-size:14px;
      margin-bottom:14px;
      line-height:1.6;
    }

    .plan-features{
      list-style:none;
      padding:0;
      margin:0 0 22px 0;
    }

    .plan-features li{
      display:flex;
      align-items:flex-start;
      gap:10px;
      margin-bottom:10px;
      color:#334155;
    }

    .plan-features i{
      color:#22c55e;
      margin-top:3px;
    }

    .current-plan-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:100%;
      padding:12px 16px;
      border-radius:10px;
      text-decoration:none;
      font-weight:700;
      background:#e2e8f0;
      color:#475569;
    }

    .empty-box{
      background:#fff3cd;
      color:#856404;
      padding:16px 18px;
      border-radius:12px;
      margin-bottom:25px;
      border:1px solid #ffe69c;
    }

    @media (max-width:768px){
      .membership-wrapper{padding:20px;}
      .page-header h2{font-size:26px;}
      .section-title{font-size:22px;}
    }
  </style>
</head>
<body>
<div class="main-content">
  <div class="membership-wrapper">

    <div class="page-header">
      <div>
        <h2>My Membership</h2>
        <p>View your current membership, explore plans, and upgrade anytime.</p>
      </div>

      <div class="header-actions">
        <a href="dashboard.php" class="back-btn">
          <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
      </div>
    </div>

    <?php if ($current_plan): ?>
      <div class="membership-card">
        <h3><i class="fa-solid fa-id-card"></i> Current Plan</h3>

        <div class="membership-details">
          <div class="detail-box">
            <span>Plan Name</span>
            <strong><?php echo htmlspecialchars($current_plan['name']); ?></strong>
          </div>

          <div class="detail-box">
            <span>Status</span>
            <strong><span class="status-badge"><?php echo htmlspecialchars(ucfirst($current_plan['membership_status'])); ?></span></strong>
          </div>

          <div class="detail-box">
            <span>Start Date</span>
            <strong><?php echo date("d M Y", strtotime($current_plan['start_date'])); ?></strong>
          </div>

          <div class="detail-box">
            <span>Expiry Date</span>
            <strong><?php echo date("d M Y", strtotime($current_plan['expiry_date'])); ?></strong>
          </div>

          <div class="detail-box">
            <span>Billing Period</span>
            <strong><?php echo htmlspecialchars(ucfirst($current_plan['billing_period'])); ?></strong>
          </div>

          <div class="detail-box">
            <span>Duration</span>
            <strong><?php echo (int)$current_plan['duration']; ?> Days</strong>
          </div>

          <div class="detail-box">
            <span>Price</span>
            <strong>LKR <?php echo number_format((float)$current_plan['price'], 2); ?></strong>
          </div>
        </div>

        <a class="btn-primary" href="payments.php">
          <i class="fa-solid fa-credit-card"></i> View Payments
        </a>
      </div>
    <?php else: ?>
      <div class="empty-box">
        <i class="fa-solid fa-circle-info"></i> You do not have an active membership yet. Please choose a plan below.
      </div>
    <?php endif; ?>

    <h3 class="section-title">Available Plans</h3>

    <div class="plans-grid">
      <?php foreach ($plans as $plan): ?>
        <?php
          $isCurrent = $current_plan && ((int)$plan['id'] === (int)$current_plan['plan_id']);
          $benefits = array_filter(array_map('trim', explode(',', $plan['benefits'] ?? '')));
        ?>
        <div class="plan-card <?php echo $isCurrent ? 'current' : ''; ?>">

          <?php if ($isCurrent): ?>
            <div class="current-badge">Current Plan</div>
          <?php endif; ?>

          <h4><?php echo htmlspecialchars($plan['name']); ?></h4>
          <div class="plan-price">LKR <?php echo number_format((float)$plan['price'], 2); ?></div>
          <div class="plan-period"><?php echo htmlspecialchars(ucfirst($plan['billing_period'])); ?></div>
          <div class="plan-duration"><?php echo (int)$plan['duration']; ?> Days</div>

          <?php if (!empty($plan['description'])): ?>
            <div class="plan-description">
              <?php echo htmlspecialchars($plan['description']); ?>
            </div>
          <?php endif; ?>

          <ul class="plan-features">
            <?php foreach ($benefits as $benefit): ?>
              <li>
                <i class="fa-solid fa-check"></i>
                <span><?php echo htmlspecialchars($benefit); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php if ($isCurrent): ?>
            <span class="current-plan-btn">
              <i class="fa-solid fa-circle-check"></i> Active Plan
            </span>
          <?php else: ?>
            <a href="payments.php?plan_id=<?php echo (int)$plan['id']; ?>" class="btn-secondary">
              <i class="fa-solid fa-arrow-up"></i> Choose / Upgrade
            </a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>
</body>
</html>
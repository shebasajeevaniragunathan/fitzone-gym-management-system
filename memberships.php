<?php
require_once __DIR__ . "/config/db.php";

$plans = [];
$res = $conn->query("SELECT * FROM memberships ORDER BY price ASC");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $plans[] = $row;
    }
}
?>

<section class="membership-section" id="memberships">
  <div class="container">
    <div class="section-title">
      <h2>Our Membership Plans</h2>
      <p>Choose the perfect plan for your fitness journey.</p>
    </div>

    <div class="plans-grid">
      <?php if (!empty($plans)): ?>
        <?php foreach ($plans as $plan): ?>
          <div class="plan-card">
            <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
            <div class="price">Rs. <?php echo number_format((float)$plan['price'], 2); ?></div>
            <p class="duration"><?php echo (int)$plan['duration']; ?> Days</p>
            <a href="register.php" class="plan-btn">Join Now</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align:center;">No membership plans available right now.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
.membership-section{
    padding:70px 20px;
    background:#f8fafc;
}
.membership-section .container{
    max-width:1200px;
    margin:0 auto;
}
.section-title{
    text-align:center;
    margin-bottom:40px;
}
.section-title h2{
    font-size:36px;
    margin-bottom:10px;
    color:#111827;
}
.section-title p{
    color:#6b7280;
    font-size:16px;
}
.plans-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:25px;
}
.plan-card{
    background:#fff;
    border-radius:18px;
    padding:30px 20px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    border:1px solid #e5e7eb;
    transition:0.3s ease;
}
.plan-card:hover{
    transform:translateY(-6px);
}
.plan-card h3{
    font-size:24px;
    margin-bottom:15px;
    color:#111827;
}
.plan-card .price{
    font-size:30px;
    font-weight:800;
    color:#ef4444;
    margin-bottom:10px;
}
.plan-card .duration{
    font-size:16px;
    color:#6b7280;
    margin-bottom:20px;
}
.plan-btn{
    display:inline-block;
    padding:12px 22px;
    background:#111827;
    color:#fff;
    text-decoration:none;
    border-radius:10px;
    font-weight:700;
}
.plan-btn:hover{
    background:#ef4444;
}
</style>
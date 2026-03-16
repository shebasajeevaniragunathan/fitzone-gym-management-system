<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /fitzone/login.php");
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin';

function tableExists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

function getCount(mysqli $conn, string $table, string $where = "1"): int {
    if (!tableExists($conn, $table)) return 0;
    $res = $conn->query("SELECT COUNT(*) AS total FROM `$table` WHERE $where");
    if ($res && $row = $res->fetch_assoc()) return (int)$row['total'];
    return 0;
}

$totalUsers = getCount($conn, "users");
$totalPlans = getCount($conn, "memberships");
$totalTrainers = getCount($conn, "trainers");
$totalBlogs = getCount($conn, "blogs");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | FitZone</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
    body{background:#f4f7fb;color:#0f172a;}
    a{text-decoration:none;}
    .layout{display:flex;min-height:100vh;}
    .sidebar{
      width:240px;background:#0f172a;color:#fff;padding:24px 16px;
    }
    .logo{
      font-size:28px;font-weight:800;margin-bottom:24px;
    }
    .admin-box{
      background:rgba(255,255,255,.08);
      padding:14px;border-radius:16px;margin-bottom:20px;
    }
    .admin-box small{color:#cbd5e1;display:block;margin-bottom:4px;}
    .menu{list-style:none;display:flex;flex-direction:column;gap:10px;}
    .menu a{
      display:block;padding:12px 14px;border-radius:12px;color:#e5e7eb;background:rgba(255,255,255,.04);
    }
    .menu a:hover,.menu a.active{background:#1d4ed8;color:#fff;}
    .main{flex:1;padding:28px;}
    .topbar{
      display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;
    }
    .topbar h1{font-size:42px;margin-bottom:8px;}
    .topbar p{color:#64748b;}
    .top-actions{display:flex;gap:12px;flex-wrap:wrap;}
    .btn{
      display:inline-block;padding:12px 18px;border-radius:12px;font-weight:700;color:#fff;
    }
    .btn-dark{background:#111827;}
    .btn-green{background:#22c55e;}
    .cards{
      display:grid;grid-template-columns:repeat(4,1fr);gap:18px;
    }
    .card{
      background:#fff;border-radius:20px;padding:22px;box-shadow:0 10px 24px rgba(0,0,0,.05);
    }
    .card h3{font-size:18px;margin-bottom:12px;}
    .card .num{font-size:34px;font-weight:800;margin-bottom:6px;}
    .sections{
      margin-top:26px;
      display:grid;grid-template-columns:repeat(3,1fr);gap:18px;
    }
    .section-box{
      background:#fff;border-radius:20px;padding:22px;box-shadow:0 10px 24px rgba(0,0,0,.05);
    }
    .section-box h3{margin-bottom:10px;}
    .section-box p{color:#64748b;line-height:1.7;margin-bottom:14px;}
    .small-links{display:flex;gap:10px;flex-wrap:wrap;}
    .small-links a{
      background:#f1f5f9;border:1px solid #dbe1ea;padding:10px 14px;border-radius:12px;color:#111827;font-weight:700;
    }
    @media(max-width:1100px){
      .cards,.sections{grid-template-columns:1fr 1fr;}
    }
    @media(max-width:800px){
      .layout{flex-direction:column;}
      .sidebar{width:100%;}
      .cards,.sections{grid-template-columns:1fr;}
      .topbar h1{font-size:32px;}
    }
  </style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="logo">FitZone</div>

    <div class="admin-box">
      <small>Logged in as</small>
      <strong><?= htmlspecialchars($adminName) ?></strong>
    </div>

    <ul class="menu">
      <li><a href="admin.php" class="active">Dashboard</a></li>
      <li><a href="membership-plans.php">Membership Plans</a></li>
      <li><a href="trainers.php">Trainers</a></li>
      <li><a href="programs.php">Programs</a></li>
      <li><a href="manage-users.php">Users</a></li>
      <li><a href="manage-blogs.php">Blogs</a></li>
      <li><a href="profile.php">Profile</a></li>
      <li><a href="/fitzone/logout.php">Logout</a></li>
    </ul>
  </aside>

  <main class="main">
    <div class="topbar">
      <div>
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($adminName) ?> ✨</p>
      </div>
      <div class="top-actions">
        <a href="/fitzone/index.php" class="btn btn-green">View Website</a>
        <a href="javascript:history.back()" class="btn btn-dark">← Back</a>
      </div>
    </div>

    <section class="cards">
      <div class="card">
        <h3>Total Users</h3>
        <div class="num"><?= $totalUsers ?></div>
        <p>Customers and admins</p>
      </div>
      <div class="card">
        <h3>Membership Plans</h3>
        <div class="num"><?= $totalPlans ?></div>
        <p>Active gym plans</p>
      </div>
      <div class="card">
        <h3>Trainers</h3>
        <div class="num"><?= $totalTrainers ?></div>
        <p>Available trainers</p>
      </div>
      <div class="card">
        <h3>Blogs</h3>
        <div class="num"><?= $totalBlogs ?></div>
        <p>Published posts</p>
      </div>
    </section>

    <section class="sections">
      <div class="section-box">
        <h3>Membership Management</h3>
        <p>Admin add panna all membership plans website la memberships page la display aagum.</p>
        <div class="small-links">
          <a href="membership-plans.php">Manage Plans</a>
          <a href="/fitzone/memberships.php">View Page</a>
        </div>
      </div>

      <div class="section-box">
        <h3>Trainer Management</h3>
        <p>Admin add panna trainers public trainers page la users ku show aagum.</p>
        <div class="small-links">
          <a href="trainers.php">Manage Trainers</a>
          <a href="/fitzone/trainers.php">View Page</a>
        </div>
      </div>

      <div class="section-box">
        <h3>Programs Management</h3>
        <p>Programs and classes public programs page la separate ah display aagum.</p>
        <div class="small-links">
          <a href="programs.php">Manage Programs</a>
          <a href="/fitzone/programs.php">View Page</a>
        </div>
      </div>
    </section>
  </main>
</div>
</body>
</html>
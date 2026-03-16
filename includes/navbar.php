<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn  = isset($_SESSION['user_id']);
$userRole    = $_SESSION['role'] ?? '';
?>
<nav class="navbar">
  <div class="nav-container">
    
    <a href="/fitzone/index.php" class="nav-logo">
      <span class="logo-box">F</span>
      <span class="logo-text">FitZone</span>
    </a>

    <div class="nav-links">
      <a href="/fitzone/index.php" class="<?= ($currentPage === 'index.php' || $currentPage === 'home.php') ? 'active' : '' ?>">Home</a>

      <a href="/fitzone/about.php" class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">About</a>

      <a href="/fitzone/programs.php" class="<?= $currentPage === 'programs.php' ? 'active' : '' ?>">Programs</a>

      <a href="/fitzone/trainers.php" class="<?= $currentPage === 'trainers.php' ? 'active' : '' ?>">Trainers</a>

      <a href="/fitzone/memberships.php" class="<?= $currentPage === 'memberships.php' ? 'active' : '' ?>">Plans</a>

      <a href="/fitzone/index.php#blog" class="<?= $currentPage === 'blog.php' ? 'active' : '' ?>">Blogs</a>

      <a href="/fitzone/contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">Contact</a>
    </div>

    <div class="nav-actions">
      <?php if (!$isLoggedIn): ?>
        <a href="/fitzone/login.php" class="btn btn-login <?= $currentPage === 'login.php' ? 'active-btn' : '' ?>">Login</a>
        <a href="/fitzone/register.php" class="btn btn-register <?= $currentPage === 'register.php' ? 'active-btn' : '' ?>">Register</a>
      <?php else: ?>
        <?php if ($userRole === 'admin'): ?>
          <a href="/fitzone/dashboard/admin.php" class="btn btn-dashboard">Dashboard</a>
        <?php endif; ?>
        <a href="/fitzone/logout.php" class="btn btn-logout">Logout</a>
      <?php endif; ?>
    </div>

  </div>
</nav>

<style>
  *{
    box-sizing:border-box;
  }

  .navbar{
    width:100%;
    background:rgba(15, 23, 42, 0.96);
    backdrop-filter:blur(10px);
    position:sticky;
    top:0;
    z-index:1000;
    box-shadow:0 8px 24px rgba(0,0,0,0.08);
    border-bottom:1px solid rgba(255,255,255,0.06);
  }

  .nav-container{
    max-width:1280px;
    margin:0 auto;
    padding:14px 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    flex-wrap:wrap;
  }

  .nav-logo{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
  }

  .logo-box{
    width:42px;
    height:42px;
    border-radius:12px;
    background:linear-gradient(135deg,#22c55e,#3b82f6);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-weight:800;
    font-size:20px;
    box-shadow:0 8px 18px rgba(34,197,94,0.25);
  }

  .logo-text{
    color:#fff;
    font-size:28px;
    font-weight:800;
    letter-spacing:0.5px;
  }

  .nav-links{
    display:flex;
    align-items:center;
    gap:24px;
    flex-wrap:wrap;
  }

  .nav-links a{
    color:#e5e7eb;
    text-decoration:none;
    font-size:15px;
    font-weight:500;
    position:relative;
    transition:0.25s ease;
    padding:6px 0;
  }

  .nav-links a:hover{
    color:#22c55e;
  }

  .nav-links a.active{
    color:#22c55e;
    font-weight:700;
  }

  .nav-links a.active::after{
    content:"";
    position:absolute;
    left:0;
    bottom:-6px;
    width:100%;
    height:3px;
    border-radius:10px;
    background:#22c55e;
  }

  .nav-actions{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
  }

  .btn{
    text-decoration:none;
    padding:10px 18px;
    border-radius:12px;
    font-size:14px;
    font-weight:700;
    transition:0.25s ease;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:96px;
  }

  .btn-login{
    background:#2563eb;
    color:#fff;
  }

  .btn-login:hover{
    background:#1d4ed8;
    transform:translateY(-2px);
  }

  .btn-register{
    background:#22c55e;
    color:#fff;
  }

  .btn-register:hover{
    background:#16a34a;
    transform:translateY(-2px);
  }

  .btn-dashboard{
    background:#7c3aed;
    color:#fff;
  }

  .btn-dashboard:hover{
    background:#6d28d9;
    transform:translateY(-2px);
  }

  .btn-logout{
    background:#ef4444;
    color:#fff;
  }

  .btn-logout:hover{
    background:#dc2626;
    transform:translateY(-2px);
  }

  .active-btn{
    box-shadow:0 0 0 3px rgba(255,255,255,0.15) inset;
  }

  @media (max-width: 1024px){
    .nav-container{
      justify-content:center;
    }

    .nav-logo{
      width:100%;
      justify-content:center;
    }

    .nav-links{
      justify-content:center;
    }

    .nav-actions{
      justify-content:center;
    }
  }

  @media (max-width: 640px){
    .logo-text{
      font-size:24px;
    }

    .nav-links{
      gap:14px;
    }

    .nav-links a{
      font-size:14px;
    }

    .btn{
      min-width:auto;
      padding:10px 14px;
    }
  }
</style>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$name = $_SESSION['name'] ?? 'Staff';
?>
<style>
.staff-layout{display:flex;min-height:100vh;background:#f4f7fb;}
.staff-sidebar{
    width:260px;
    background:#0f172a;
    color:#fff;
    padding:24px 18px;
    position:sticky;
    top:0;
    height:100vh;
}
.staff-brand{
    font-size:28px;
    font-weight:800;
    margin-bottom:10px;
}
.staff-role{
    font-size:14px;
    color:#94a3b8;
    margin-bottom:28px;
}
.staff-userbox{
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.08);
    border-radius:14px;
    padding:14px;
    margin-bottom:24px;
}
.staff-userbox strong{display:block;font-size:16px;}
.staff-userbox span{font-size:13px;color:#cbd5e1;}
.staff-menu{
    display:flex;
    flex-direction:column;
    gap:10px;
}
.staff-menu a{
    color:#e5e7eb;
    text-decoration:none;
    padding:12px 14px;
    border-radius:12px;
    font-weight:600;
    transition:.2s ease;
}
.staff-menu a:hover,
.staff-menu a.active{
    background:#22c55e;
    color:#062d16;
}
.staff-main{
    flex:1;
    padding:28px;
}
@media(max-width:900px){
    .staff-layout{flex-direction:column;}
    .staff-sidebar{
        width:100%;
        height:auto;
        position:relative;
    }
}
</style>

<div class="staff-sidebar">
    <div class="staff-brand">FitZone</div>
    <div class="staff-role">Staff Panel</div>

    <div class="staff-userbox">
        <strong><?= htmlspecialchars($name) ?></strong>
        <span>Gym Staff</span>
    </div>

    <nav class="staff-menu">
        <a href="/fitzone/staff/dashboard.php">Dashboard</a>
        <a href="/fitzone/staff/customers.php">Customers</a>
        <a href="/fitzone/staff/appointments.php">Appointments</a>
        <a href="/fitzone/staff/queries.php">Queries</a>
        <a href="/fitzone/staff/attendance.php">Attendance</a>
        <a href="/fitzone/staff/profile.php">Profile</a>
        <a href="/fitzone/logout.php">Logout</a>
    </nav>
</div>
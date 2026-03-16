<?php
require_once __DIR__ . "/includes/auth.php";

function tableExists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

$totalCustomers = 0;
$totalAppointments = 0;
$totalQueries = 0;
$todayAttendance = 0;

$staffName = $_SESSION['name'] ?? 'Staff';

/* =========================
   TOTAL CUSTOMERS
========================= */
if (tableExists($conn, 'users')) {
    $roleChecks = ['customer', 'Customer', 'member', 'Member', 'user', 'User'];

    foreach ($roleChecks as $roleValue) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role = ?");
        if ($stmt) {
            $stmt->bind_param("s", $roleValue);
            $stmt->execute();
            $count = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
            $stmt->close();

            if ($count > 0) {
                $totalCustomers = $count;
                break;
            }
        }
    }
}

/* =========================
   TOTAL APPOINTMENTS
========================= */
if (tableExists($conn, 'appointments')) {
    $res = $conn->query("SELECT COUNT(*) AS total FROM appointments");
    if ($res) {
        $totalAppointments = (int)($res->fetch_assoc()['total'] ?? 0);
    }
}

/* =========================
   TOTAL QUERIES
========================= */
$queryTable = '';
if (tableExists($conn, 'queries')) {
    $queryTable = 'queries';
} elseif (tableExists($conn, 'contact_queries')) {
    $queryTable = 'contact_queries';
}

if ($queryTable !== '') {
    $res = $conn->query("SELECT COUNT(*) AS total FROM {$queryTable}");
    if ($res) {
        $totalQueries = (int)($res->fetch_assoc()['total'] ?? 0);
    }
}

/* =========================
   TODAY ATTENDANCE
========================= */
if (tableExists($conn, 'attendance')) {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE attendance_date = ?");
    if ($stmt) {
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $todayAttendance = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard | FitZone</title>
<style>
body{
    margin:0;
    font-family:Arial,sans-serif;
}
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:12px;
    margin-bottom:22px;
}
.page-title{
    font-size:34px;
    font-weight:800;
    color:#0f172a;
    margin:0;
}
.top-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.btn-top{
    display:inline-block;
    padding:11px 16px;
    border-radius:12px;
    text-decoration:none;
    font-weight:700;
    transition:.2s ease;
}
.btn-back{
    background:#2563eb;
    color:#fff;
}
.btn-home{
    background:#f97316;
    color:#fff;
}
.btn-top:hover{
    opacity:.92;
}
.welcome-card{
    background:linear-gradient(135deg,#0f172a,#1e293b);
    color:#fff;
    border-radius:20px;
    padding:24px;
    margin-bottom:22px;
    box-shadow:0 10px 24px rgba(0,0,0,.10);
}
.welcome-card h2{
    margin:0 0 8px;
    font-size:28px;
}
.welcome-card p{
    margin:0;
    color:#cbd5e1;
    font-size:15px;
}
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:18px;
    margin-bottom:28px;
}
.card{
    background:#fff;
    border-radius:18px;
    padding:22px;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 24px rgba(0,0,0,.05);
}
.card h3{
    margin:0 0 10px;
    font-size:16px;
    color:#6b7280;
}
.card .num{
    font-size:34px;
    font-weight:800;
    color:#111827;
}
.quick{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:18px;
}
.quick a{
    display:block;
    background:#fff;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 24px rgba(0,0,0,.05);
    border-radius:18px;
    padding:20px;
    text-decoration:none;
    color:#111827;
    font-weight:700;
    transition:.2s ease;
}
.quick a:hover{
    transform:translateY(-4px);
}
.quick a span{
    display:block;
    font-size:14px;
    color:#6b7280;
    font-weight:400;
    margin-top:8px;
}
</style>
</head>
<body>
<div class="staff-layout">
    <?php include __DIR__ . "/includes/sidebar.php"; ?>

    <div class="staff-main">

        <div class="topbar">
            <h1 class="page-title">Staff Dashboard</h1>
            <div class="top-actions">
                <a href="javascript:history.back()" class="btn-top btn-back">← Back</a>
                <a href="/fitzone/index.php" class="btn-top btn-home">🏠 Home Page</a>
            </div>
        </div>

        <div class="welcome-card">
            <h2>Welcome, <?= htmlspecialchars($staffName) ?> 👋</h2>
            <p>Manage customers, appointments, customer queries, and daily attendance from one place.</p>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Total Customers</h3>
                <div class="num"><?= $totalCustomers ?></div>
            </div>
            <div class="card">
                <h3>Total Appointments</h3>
                <div class="num"><?= $totalAppointments ?></div>
            </div>
            <div class="card">
                <h3>Total Queries</h3>
                <div class="num"><?= $totalQueries ?></div>
            </div>
            <div class="card">
                <h3>Today's Attendance</h3>
                <div class="num"><?= $todayAttendance ?></div>
            </div>
        </div>

        <div class="quick">
            <a href="/fitzone/staff/customers.php">
                View Customers
                <span>Registered customer list</span>
            </a>

            <a href="/fitzone/staff/appointments.php">
                Manage Appointments
                <span>Approve, reject, complete</span>
            </a>

            <a href="/fitzone/staff/queries.php">
                Respond Queries
                <span>Reply to customer messages</span>
            </a>

            <a href="/fitzone/staff/attendance.php">
                Manage Attendance
                <span>Mark present or absent</span>
            </a>
        </div>

    </div>
</div>
</body>
</html>
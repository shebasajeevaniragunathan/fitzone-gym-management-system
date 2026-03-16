<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* -----------------------------
   Protect customer page
----------------------------- */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_id   = (int)$_SESSION['user_id'];
$customer_name = trim($_SESSION['name'] ?? '');
$email         = trim($_SESSION['email'] ?? '');

if ($customer_name === '') {
    $customer_name = 'Customer';
}

if ($email === '') {
    $email = 'customer@fitzone.com';
}

$bookings = [];
$programs = [];
$trainers = [];
$error_message = "";
$success_message = "";

/* -----------------------------
   Load programs
----------------------------- */
$programSql = "SELECT id, title, trainer_name FROM programs ORDER BY title ASC";
$programRes = $conn->query($programSql);
if ($programRes) {
    while ($row = $programRes->fetch_assoc()) {
        $programs[] = $row;
    }
}

/* -----------------------------
   Load trainers
----------------------------- */
$trainerSql = "SELECT id, name FROM trainers ORDER BY name ASC";
$trainerRes = $conn->query($trainerSql);
if ($trainerRes) {
    while ($row = $trainerRes->fetch_assoc()) {
        $trainers[] = $row;
    }
}

/* -----------------------------
   Handle booking submit
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $program_id   = (int)($_POST['program_id'] ?? 0);
    $trainer_id   = !empty($_POST['trainer_id']) ? (int)$_POST['trainer_id'] : null;
    $booking_date = trim($_POST['booking_date'] ?? '');
    $booking_time = trim($_POST['booking_time'] ?? '');
    $status       = 'pending';

    if ($program_id <= 0 || $booking_date === '' || $booking_time === '') {
        $error_message = "Please fill all required fields.";
    } else {
        /* prevent duplicate same booking */
        if ($trainer_id !== null) {
            $check = $conn->prepare("
                SELECT id 
                FROM bookings 
                WHERE user_id = ? 
                  AND program_id = ? 
                  AND trainer_id = ? 
                  AND booking_date = ? 
                  AND booking_time = ?
                LIMIT 1
            ");
            $check->bind_param("iiiss", $customer_id, $program_id, $trainer_id, $booking_date, $booking_time);
        } else {
            $check = $conn->prepare("
                SELECT id 
                FROM bookings 
                WHERE user_id = ? 
                  AND program_id = ? 
                  AND trainer_id IS NULL
                  AND booking_date = ? 
                  AND booking_time = ?
                LIMIT 1
            ");
            $check->bind_param("iiss", $customer_id, $program_id, $booking_date, $booking_time);
        }

        if ($check) {
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            $check->close();
        } else {
            $existing = false;
        }

        if ($existing) {
            $error_message = "You already booked this session.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO bookings (user_id, program_id, trainer_id, booking_date, booking_time, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if ($stmt) {
                $stmt->bind_param("iiisss", $customer_id, $program_id, $trainer_id, $booking_date, $booking_time, $status);

                if ($stmt->execute()) {
                    $success_message = "Booking created successfully.";
                } else {
                    $error_message = "Failed to create booking.";
                }

                $stmt->close();
            } else {
                $error_message = "Database error while creating booking.";
            }
        }
    }
}

/* -----------------------------
   Fetch this customer's bookings
----------------------------- */
$sql = "
    SELECT 
        b.id,
        b.booking_date,
        b.booking_time,
        b.status,
        b.created_at,
        COALESCE(p.title, 'Program Not Found') AS program_name,
        COALESCE(t.name, p.trainer_name, 'Trainer Not Assigned') AS trainer_name
    FROM bookings b
    LEFT JOIN programs p ON b.program_id = p.id
    LEFT JOIN trainers t ON b.trainer_id = t.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.booking_time DESC, b.id DESC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }

    $stmt->close();
} else {
    $error_message = "Unable to load bookings right now.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Bookings - FitZone</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
      font-family:Arial, Helvetica, sans-serif;
    }
    body{
      background:#f4f7fb;
      display:flex;
      min-height:100vh;
    }
    .sidebar{
      width:260px;
      background:#0f172a;
      color:#fff;
      padding:25px 20px;
      position:fixed;
      height:100vh;
      overflow-y:auto;
    }
    .logo h2{
      font-size:28px;
      margin-bottom:6px;
      color:#22c55e;
    }
    .logo p{
      font-size:14px;
      color:#cbd5e1;
      margin-bottom:28px;
    }
    .nav-links{
      list-style:none;
    }
    .nav-links li{
      margin-bottom:10px;
    }
    .nav-links a{
      display:flex;
      align-items:center;
      gap:12px;
      text-decoration:none;
      color:#e2e8f0;
      padding:12px 14px;
      border-radius:12px;
      transition:0.3s;
    }
    .nav-links a:hover,
    .nav-links .active a{
      background:#22c55e;
      color:#fff;
    }
    .main-content{
      margin-left:260px;
      flex:1;
      padding:30px;
    }
    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:20px;
      margin-bottom:25px;
      flex-wrap:wrap;
    }
    .topbar h1{
      font-size:30px;
      color:#0f172a;
      margin-bottom:8px;
    }
    .topbar p{
      color:#64748b;
    }
    .user-box{
      background:#fff;
      padding:12px 18px;
      border-radius:12px;
      box-shadow:0 8px 20px rgba(0,0,0,0.06);
      display:flex;
      align-items:center;
      gap:10px;
      color:#0f172a;
      font-weight:600;
    }
    .page-card{
      background:#fff;
      border-radius:18px;
      padding:25px;
      box-shadow:0 10px 30px rgba(0,0,0,0.06);
      margin-bottom:24px;
    }
    .page-card h2{
      color:#0f172a;
      margin-bottom:8px;
    }
    .page-card p{
      color:#64748b;
      margin-bottom:20px;
    }
    .alert-error,
    .alert-success{
      padding:14px 16px;
      border-radius:12px;
      margin-bottom:18px;
      font-weight:600;
    }
    .alert-error{
      background:#fee2e2;
      color:#991b1b;
    }
    .alert-success{
      background:#dcfce7;
      color:#166534;
    }
    .form-grid{
      display:grid;
      grid-template-columns:repeat(2,1fr);
      gap:18px;
    }
    .form-group{
      display:flex;
      flex-direction:column;
      gap:8px;
    }
    .form-group.full{
      grid-column:1 / -1;
    }
    .form-group label{
      font-weight:700;
      color:#0f172a;
    }
    .form-group select,
    .form-group input{
      width:100%;
      padding:12px 14px;
      border:1px solid #d1d5db;
      border-radius:12px;
      background:#fff;
      outline:none;
      font-size:15px;
    }
    .form-group select:focus,
    .form-group input:focus{
      border-color:#22c55e;
      box-shadow:0 0 0 3px rgba(34,197,94,0.12);
    }
    .submit-btn{
      margin-top:18px;
      border:none;
      background:#22c55e;
      color:#fff;
      padding:13px 22px;
      border-radius:12px;
      font-weight:700;
      cursor:pointer;
      transition:.3s;
    }
    .submit-btn:hover{
      background:#16a34a;
    }
    .empty-state{
      text-align:center;
      padding:55px 20px;
      border:2px dashed #dbe3ef;
      border-radius:16px;
      background:#f8fbff;
    }
    .empty-state i{
      font-size:48px;
      color:#94a3b8;
      margin-bottom:14px;
    }
    .empty-state h3{
      color:#0f172a;
      margin-bottom:10px;
      font-size:24px;
    }
    .empty-state p{
      color:#64748b;
      margin-bottom:20px;
      line-height:1.7;
      max-width:650px;
      margin-left:auto;
      margin-right:auto;
    }
    .btn-row{
      display:flex;
      justify-content:center;
      gap:12px;
      flex-wrap:wrap;
    }
    .explore-btn,
    .secondary-btn{
      display:inline-block;
      text-decoration:none;
      padding:12px 20px;
      border-radius:12px;
      font-weight:700;
      transition:0.3s;
    }
    .explore-btn{
      background:#22c55e;
      color:#fff;
    }
    .explore-btn:hover{
      background:#16a34a;
    }
    .secondary-btn{
      background:#e2e8f0;
      color:#0f172a;
    }
    .secondary-btn:hover{
      background:#cbd5e1;
    }
    .table-wrap{
      overflow-x:auto;
    }
    table{
      width:100%;
      border-collapse:collapse;
      min-width:760px;
    }
    table thead{
      background:#0f172a;
      color:#fff;
    }
    table th, table td{
      padding:14px 16px;
      text-align:left;
      border-bottom:1px solid #e5e7eb;
    }
    table tbody tr:hover{
      background:#f8fafc;
    }
    .status{
      padding:7px 12px;
      border-radius:999px;
      font-size:13px;
      font-weight:700;
      display:inline-block;
      text-transform:capitalize;
    }
    .confirmed{background:#dcfce7;color:#166534;}
    .pending{background:#fef3c7;color:#92400e;}
    .completed{background:#dbeafe;color:#1d4ed8;}
    .cancelled{background:#fee2e2;color:#991b1b;}
    .default-status{background:#e5e7eb;color:#374151;}

    @media (max-width:900px){
      .sidebar{
        width:220px;
      }
      .main-content{
        margin-left:220px;
      }
      .form-grid{
        grid-template-columns:1fr;
      }
    }
    @media (max-width:768px){
      body{
        flex-direction:column;
      }
      .sidebar{
        position:relative;
        width:100%;
        height:auto;
      }
      .main-content{
        margin-left:0;
      }
    }
  </style>
</head>
<body>

  <aside class="sidebar">
    <div class="logo">
      <h2>FitZone</h2>
      <p>Customer Panel</p>
    </div>

    <ul class="nav-links">
      <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
      <li><a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
      <li><a href="edit-profile.php"><i class="fa-solid fa-user-pen"></i> Edit Profile</a></li>
      <li><a href="membership.php"><i class="fa-solid fa-id-card"></i> My Membership</a></li>
      <li class="active"><a href="bookings.php"><i class="fa-solid fa-calendar-check"></i> My Bookings</a></li>
      <li><a href="payments.php"><i class="fa-solid fa-receipt"></i> Payment History</a></li>
      <li><a href="../programs.php"><i class="fa-solid fa-dumbbell"></i> Programs</a></li>
      <li><a href="../trainers.php"><i class="fa-solid fa-user-group"></i> Trainers</a></li>
      <li><a href="../contact.php"><i class="fa-solid fa-envelope"></i> Contact Us</a></li>
      <li><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </aside>

  <main class="main-content">
    <header class="topbar">
      <div>
        <h1>My Bookings 📅</h1>
        <p>Book a training session and view your saved bookings.</p>
      </div>

      <div class="user-box">
        <i class="fa-solid fa-circle-user"></i>
        <span><?php echo htmlspecialchars($email); ?></span>
      </div>
    </header>

    <section class="page-card">
      <h2>Book a New Session</h2>
      <p>Select a program, trainer, date and time to create a booking request.</p>

      <?php if ($error_message !== ''): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>

      <?php if ($success_message !== ''): ?>
        <div class="alert-success"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label for="program_id">Program</label>
            <select name="program_id" id="program_id" required>
              <option value="">Select Program</option>
              <?php foreach ($programs as $program): ?>
                <option value="<?php echo (int)$program['id']; ?>">
                  <?php echo htmlspecialchars($program['title']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="trainer_id">Trainer</label>
            <select name="trainer_id" id="trainer_id">
              <option value="">Select Trainer (Optional)</option>
              <?php foreach ($trainers as $trainer): ?>
                <option value="<?php echo (int)$trainer['id']; ?>">
                  <?php echo htmlspecialchars($trainer['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="booking_date">Booking Date</label>
            <input type="date" name="booking_date" id="booking_date" required min="<?php echo date('Y-m-d'); ?>">
          </div>

          <div class="form-group">
            <label for="booking_time">Booking Time</label>
            <input type="time" name="booking_time" id="booking_time" required>
          </div>
        </div>

        <button type="submit" name="create_booking" class="submit-btn">Book Now</button>
      </form>
    </section>

    <section class="page-card">
      <h2>Hello, <?php echo htmlspecialchars($customer_name); ?> 👋</h2>
      <p>Here are your actual bookings from the system.</p>

      <?php if (!empty($bookings)): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Program</th>
                <th>Trainer</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $booking): ?>
                <?php
                  $status = strtolower(trim($booking['status'] ?? ''));
                  $allowedClasses = ['confirmed', 'pending', 'completed', 'cancelled'];
                  $statusClass = in_array($status, $allowedClasses, true) ? $status : 'default-status';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($booking['program_name']); ?></td>
                  <td><?php echo htmlspecialchars($booking['trainer_name']); ?></td>
                  <td>
                    <?php
                      echo !empty($booking['booking_date'])
                        ? htmlspecialchars(date("Y-m-d", strtotime($booking['booking_date'])))
                        : '-';
                    ?>
                  </td>
                  <td>
                    <?php
                      echo !empty($booking['booking_time'])
                        ? htmlspecialchars(date("h:i A", strtotime($booking['booking_time'])))
                        : '-';
                    ?>
                  </td>
                  <td>
                    <span class="status <?php echo $statusClass; ?>">
                      <?php echo htmlspecialchars(ucfirst($booking['status'] ?? 'Unknown')); ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fa-regular fa-calendar-xmark"></i>
          <h3>No Session Bookings Yet</h3>
          <p>
            You have not booked any personal training sessions or appointments yet.
            Create your first booking using the form above.
          </p>
          <div class="btn-row">
            <a href="../programs.php" class="explore-btn">Explore Programs</a>
            <a href="../trainers.php" class="secondary-btn">View Trainers</a>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </main>

</body>
</html>
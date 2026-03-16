<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* -----------------------------
   Protect customer page
----------------------------- */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$customer_name = trim($_SESSION['name'] ?? ($_SESSION['full_name'] ?? 'Customer'));
$email = trim($_SESSION['email'] ?? 'customer@fitzone.com');

$payments = [];
$total_paid = 0;
$paid_count = 0;
$pending_count = 0;
$last_payment_date = '-';
$current_plan = 'No Active Plan';
$membership_status = 'Not Active';
$membership_start = '-';
$membership_end = '-';
$error_message = '';
$success_message = '';

$selected_plan = null;
$selected_plan_id = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;

/* -----------------------------
   Helper: check table exists
----------------------------- */
function tableExists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

/* -----------------------------
   Load selected plan for payment
----------------------------- */
if ($selected_plan_id > 0 && tableExists($conn, 'memberships')) {
    $plan_stmt = $conn->prepare("
        SELECT id, name, price, billing_period, benefits, duration, description
        FROM memberships
        WHERE id = ? AND is_active = 1
        LIMIT 1
    ");
    if ($plan_stmt) {
        $plan_stmt->bind_param("i", $selected_plan_id);
        $plan_stmt->execute();
        $plan_result = $plan_stmt->get_result();
        $selected_plan = $plan_result->fetch_assoc();
        $plan_stmt->close();

        if (!$selected_plan) {
            $error_message = "Selected membership plan was not found.";
        }
    }
}

/* -----------------------------
   Handle card payment submit
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    $plan_id = (int)($_POST['plan_id'] ?? 0);
    $card_name = trim($_POST['card_name'] ?? '');
    $card_number = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
    $expiry_month = trim($_POST['expiry_month'] ?? '');
    $expiry_year = trim($_POST['expiry_year'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');

    if ($plan_id <= 0) {
        $error_message = "Invalid membership plan selected.";
    } elseif ($card_name === '' || $card_number === '' || $expiry_month === '' || $expiry_year === '' || $cvv === '') {
        $error_message = "Please fill in all card payment details.";
    } elseif (strlen($card_number) < 13 || strlen($card_number) > 19) {
        $error_message = "Please enter a valid card number.";
    } elseif (!preg_match('/^\d{2}$/', $expiry_month) || (int)$expiry_month < 1 || (int)$expiry_month > 12) {
        $error_message = "Please enter a valid expiry month.";
    } elseif (!preg_match('/^\d{2,4}$/', $expiry_year)) {
        $error_message = "Please enter a valid expiry year.";
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $error_message = "Please enter a valid CVV.";
    } else {
        $plan_stmt = $conn->prepare("
            SELECT id, name, price, duration
            FROM memberships
            WHERE id = ? AND is_active = 1
            LIMIT 1
        ");

        if ($plan_stmt) {
            $plan_stmt->bind_param("i", $plan_id);
            $plan_stmt->execute();
            $plan_result = $plan_stmt->get_result();
            $plan_data = $plan_result->fetch_assoc();
            $plan_stmt->close();

            if (!$plan_data) {
                $error_message = "Selected plan is not available.";
            } else {
                $amount = (float)$plan_data['price'];
                $duration_days = (int)($plan_data['duration'] ?? 30);
                if ($duration_days <= 0) {
                    $duration_days = 30;
                }

                $start_date = date('Y-m-d');
                $expiry_date = date('Y-m-d', strtotime("+{$duration_days} days"));
                $masked_card = 'Card ending in ' . substr($card_number, -4);

                try {
                    $conn->begin_transaction();

                    /* Insert payment record */
                    $payment_stmt = $conn->prepare("
                        INSERT INTO payments (user_id, plan_id, amount, payment_method, payment_status, transaction_note)
                        VALUES (?, ?, ?, ?, 'paid', ?)
                    ");

                    if (!$payment_stmt) {
                        throw new Exception("Unable to prepare payment record.");
                    }

                    $payment_method = 'Online Card Payment';
                    $payment_stmt->bind_param("iidss", $user_id, $plan_id, $amount, $payment_method, $masked_card);
                    $payment_stmt->execute();
                    $payment_stmt->close();

                    /* Insert active membership */
                    $membership_stmt = $conn->prepare("
                        INSERT INTO customer_memberships (user_id, plan_id, start_date, expiry_date, membership_status)
                        VALUES (?, ?, ?, ?, 'active')
                    ");

                    if (!$membership_stmt) {
                        throw new Exception("Unable to activate membership.");
                    }

                    $membership_stmt->bind_param("iiss", $user_id, $plan_id, $start_date, $expiry_date);
                    $membership_stmt->execute();
                    $membership_stmt->close();

                    $conn->commit();

                    $success_message = "Payment successful! Your membership has been activated.";
                    $selected_plan_id = 0;
                    $selected_plan = null;
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = "Payment failed. " . $e->getMessage();
                }
            }
        }
    }
}

/* -----------------------------
   Load current membership summary
----------------------------- */
if (tableExists($conn, 'customer_memberships')) {
    $membership_sql = "
        SELECT 
            cm.id,
            cm.user_id,
            cm.plan_id,
            cm.start_date,
            cm.expiry_date,
            cm.membership_status,
            m.name AS plan_name,
            m.price,
            m.duration
        FROM customer_memberships cm
        LEFT JOIN memberships m ON cm.plan_id = m.id
        WHERE cm.user_id = ? AND cm.membership_status = 'active'
        ORDER BY cm.id DESC
        LIMIT 1
    ";

    $membership_stmt = $conn->prepare($membership_sql);
    if ($membership_stmt) {
        $membership_stmt->bind_param("i", $user_id);
        $membership_stmt->execute();
        $membership_result = $membership_stmt->get_result();
        $membership = $membership_result->fetch_assoc();
        $membership_stmt->close();

        if ($membership) {
            $current_plan = $membership['plan_name'] ?: 'Membership Plan';
            $membership_status = ucfirst($membership['membership_status'] ?? 'Active');

            if (!empty($membership['start_date'])) {
                $membership_start = date("d M Y", strtotime($membership['start_date']));
            }

            if (!empty($membership['expiry_date'])) {
                $membership_end = date("d M Y", strtotime($membership['expiry_date']));
            }
        }
    }
}

/* -----------------------------
   Load payment history
----------------------------- */
if (tableExists($conn, 'payments')) {
    $payments_sql = "
        SELECT 
            p.*,
            m.name AS plan_name
        FROM payments p
        LEFT JOIN memberships m ON p.plan_id = m.id
        WHERE p.user_id = ?
        ORDER BY p.id DESC
    ";

    $payments_stmt = $conn->prepare($payments_sql);
    if ($payments_stmt) {
        $payments_stmt->bind_param("i", $user_id);
        $payments_stmt->execute();
        $payments_result = $payments_stmt->get_result();

        while ($row = $payments_result->fetch_assoc()) {
            $payments[] = $row;

            $amount = (float)($row['amount'] ?? 0);
            $status = strtolower(trim($row['payment_status'] ?? ''));

            if ($status === 'paid' || $status === 'completed' || $status === 'success') {
                $total_paid += $amount;
                $paid_count++;

                if ($last_payment_date === '-') {
                    if (!empty($row['payment_date'])) {
                        $last_payment_date = date("d M Y h:i A", strtotime($row['payment_date']));
                    }
                }
            }

            if ($status === 'pending') {
                $pending_count++;
            }
        }

        $payments_stmt->close();
    } else {
        $error_message = "Unable to load payment history.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payments - FitZone</title>
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
      min-height:100vh;
      color:#0f172a;
    }
    .page{
      max-width:1250px;
      margin:0 auto;
      padding:35px 20px 50px;
    }
    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:20px;
      flex-wrap:wrap;
      margin-bottom:25px;
    }
    .topbar h1{
      font-size:42px;
      margin-bottom:10px;
    }
    .topbar p{
      color:#64748b;
      font-size:18px;
    }
    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      text-decoration:none;
      padding:12px 18px;
      border-radius:12px;
      font-weight:700;
      transition:0.3s;
      border:none;
      cursor:pointer;
    }
    .btn-back{
      background:#64748b;
      color:#fff;
    }
    .btn-back:hover{
      background:#475569;
    }
    .btn-primary{
      background:#22c55e;
      color:#fff;
    }
    .btn-primary:hover{
      background:#16a34a;
    }
    .btn-dark{
      background:#0f172a;
      color:#fff;
    }
    .btn-dark:hover{
      background:#1e293b;
    }

    .alert{
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

    .payment-layout{
      display:grid;
      grid-template-columns:1.05fr 0.95fr;
      gap:22px;
      margin-bottom:25px;
    }

    .card-box{
      background:#fff;
      border-radius:20px;
      padding:24px;
      box-shadow:0 10px 25px rgba(15,23,42,0.06);
    }

    .card-box h2{
      font-size:28px;
      margin-bottom:8px;
      color:#0f172a;
    }

    .card-box p{
      color:#64748b;
      margin-bottom:18px;
      line-height:1.6;
    }

    .plan-preview{
      background:linear-gradient(135deg, #0f172a, #1e293b);
      color:#fff;
      border-radius:20px;
      padding:24px;
      margin-top:10px;
    }

    .plan-preview .mini{
      color:#cbd5e1;
      font-size:14px;
      margin-bottom:10px;
    }

    .plan-preview .plan-name{
      font-size:30px;
      font-weight:800;
      margin-bottom:10px;
    }

    .plan-preview .plan-price{
      font-size:36px;
      font-weight:800;
      color:#4ade80;
      margin-bottom:8px;
    }

    .plan-preview .plan-meta{
      color:#cbd5e1;
      margin-bottom:16px;
      font-size:15px;
    }

    .plan-preview ul{
      list-style:none;
      padding:0;
      margin:18px 0 0;
    }

    .plan-preview li{
      display:flex;
      align-items:flex-start;
      gap:10px;
      margin-bottom:10px;
      line-height:1.5;
      color:#e2e8f0;
    }

    .plan-preview li i{
      color:#4ade80;
      margin-top:4px;
    }

    .payment-form{
      display:grid;
      gap:16px;
    }

    .form-row{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:16px;
    }

    .form-group label{
      display:block;
      font-weight:700;
      margin-bottom:8px;
      color:#334155;
    }

    .form-group input{
      width:100%;
      padding:13px 14px;
      border:1px solid #dbe3ef;
      border-radius:12px;
      font-size:15px;
      outline:none;
      transition:0.2s ease;
      background:#fff;
    }

    .form-group input:focus{
      border-color:#22c55e;
      box-shadow:0 0 0 3px rgba(34,197,94,0.12);
    }

    .secure-note{
      margin-top:6px;
      color:#64748b;
      font-size:13px;
    }

    .membership-card{
      background:linear-gradient(135deg, #0f172a, #1e293b);
      color:#fff;
      border-radius:22px;
      padding:28px;
      margin-bottom:25px;
      box-shadow:0 12px 30px rgba(15,23,42,0.15);
    }
    .membership-card-top{
      display:flex;
      justify-content:space-between;
      gap:20px;
      flex-wrap:wrap;
      align-items:flex-start;
    }
    .membership-title{
      font-size:16px;
      color:#cbd5e1;
      margin-bottom:10px;
    }
    .membership-plan{
      font-size:34px;
      font-weight:800;
      margin-bottom:8px;
    }
    .membership-status{
      display:inline-block;
      margin-top:10px;
      padding:8px 14px;
      border-radius:999px;
      font-size:13px;
      font-weight:700;
      background:#dcfce7;
      color:#166534;
    }
    .membership-meta{
      display:grid;
      grid-template-columns:repeat(2, minmax(180px, 1fr));
      gap:18px;
      margin-top:25px;
    }
    .membership-meta .box{
      background:rgba(255,255,255,0.08);
      padding:16px;
      border-radius:14px;
    }
    .membership-meta .box span{
      display:block;
      font-size:13px;
      color:#cbd5e1;
      margin-bottom:6px;
    }
    .membership-meta .box strong{
      font-size:17px;
      color:#fff;
    }

    .summary-grid{
      display:grid;
      grid-template-columns:repeat(4, 1fr);
      gap:20px;
      margin-bottom:25px;
    }
    .summary-card{
      background:#fff;
      padding:22px;
      border-radius:18px;
      box-shadow:0 10px 25px rgba(15,23,42,0.06);
    }
    .summary-card .label{
      font-size:14px;
      color:#64748b;
      margin-bottom:10px;
    }
    .summary-card .value{
      font-size:28px;
      font-weight:800;
      color:#0f172a;
    }
    .summary-card .sub{
      margin-top:8px;
      font-size:14px;
      color:#64748b;
    }

    .section-card{
      background:#fff;
      border-radius:20px;
      padding:25px;
      box-shadow:0 10px 25px rgba(15,23,42,0.06);
    }
    .section-head{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:15px;
      flex-wrap:wrap;
      margin-bottom:20px;
    }
    .section-head h2{
      font-size:28px;
      color:#0f172a;
    }
    .section-head p{
      color:#64748b;
      margin-top:6px;
    }

    .table-wrap{
      overflow-x:auto;
    }
    table{
      width:100%;
      border-collapse:collapse;
      min-width:900px;
    }
    thead{
      background:#0f172a;
      color:#fff;
    }
    th, td{
      text-align:left;
      padding:16px 14px;
      border-bottom:1px solid #e5e7eb;
      vertical-align:middle;
    }
    tbody tr:hover{
      background:#f8fafc;
    }

    .badge{
      display:inline-block;
      padding:7px 12px;
      border-radius:999px;
      font-size:13px;
      font-weight:700;
      text-transform:capitalize;
    }
    .paid{
      background:#dcfce7;
      color:#166534;
    }
    .pending{
      background:#fef3c7;
      color:#92400e;
    }
    .failed{
      background:#fee2e2;
      color:#991b1b;
    }
    .unknown{
      background:#e5e7eb;
      color:#374151;
    }

    .empty-state{
      text-align:center;
      padding:55px 20px;
      border:2px dashed #dbe3ef;
      border-radius:18px;
      background:#f8fbff;
    }
    .empty-state i{
      font-size:50px;
      color:#94a3b8;
      margin-bottom:15px;
    }
    .empty-state h3{
      font-size:28px;
      color:#0f172a;
      margin-bottom:10px;
    }
    .empty-state p{
      color:#64748b;
      max-width:600px;
      margin:0 auto 20px;
      line-height:1.7;
    }

    @media (max-width: 1100px){
      .summary-grid{
        grid-template-columns:repeat(2, 1fr);
      }
      .payment-layout{
        grid-template-columns:1fr;
      }
    }
    @media (max-width: 768px){
      .topbar h1{
        font-size:34px;
      }
      .summary-grid{
        grid-template-columns:1fr;
      }
      .membership-meta{
        grid-template-columns:1fr;
      }
      .section-head h2{
        font-size:24px;
      }
      .form-row{
        grid-template-columns:1fr;
      }
    }
  </style>
</head>
<body>

<div class="page">

  <div class="topbar">
    <div>
      <h1>Payments</h1>
      <p>Track your billing, current membership, and payment history.</p>
    </div>

    <a href="membership.php" class="btn btn-back">
      <i class="fa-solid fa-arrow-left"></i> Membership
    </a>
  </div>

  <?php if ($error_message !== ''): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
  <?php endif; ?>

  <?php if ($success_message !== ''): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
  <?php endif; ?>

  <?php if ($selected_plan): ?>
    <div class="payment-layout">
      <div class="card-box">
        <h2><i class="fa-solid fa-credit-card"></i> Online Card Payment</h2>
        <p>Complete your payment securely to activate the selected membership plan instantly.</p>

        <form method="POST" class="payment-form" autocomplete="off">
          <input type="hidden" name="plan_id" value="<?php echo (int)$selected_plan['id']; ?>">

          <div class="form-group">
            <label>Cardholder Name</label>
            <input
              type="text"
              name="card_name"
              placeholder="Enter cardholder name"
              value="<?php echo htmlspecialchars($customer_name); ?>"
              required
            >
          </div>

          <div class="form-group">
            <label>Card Number</label>
            <input
              type="text"
              name="card_number"
              placeholder="1234 5678 9012 3456"
              maxlength="19"
              required
            >
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Expiry Month</label>
              <input
                type="text"
                name="expiry_month"
                placeholder="MM"
                maxlength="2"
                required
              >
            </div>

            <div class="form-group">
              <label>Expiry Year</label>
              <input
                type="text"
                name="expiry_year"
                placeholder="YY"
                maxlength="4"
                required
              >
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>CVV</label>
              <input
                type="password"
                name="cvv"
                placeholder="123"
                maxlength="4"
                required
              >
            </div>

            <div class="form-group">
              <label>Email</label>
              <input
                type="text"
                value="<?php echo htmlspecialchars($email); ?>"
                readonly
              >
            </div>
          </div>

          <div class="secure-note">
            <i class="fa-solid fa-shield-halved"></i> Demo secure card payment form for project presentation.
          </div>

          <button type="submit" name="pay_now" class="btn btn-primary" style="width:100%; margin-top:8px;">
            <i class="fa-solid fa-lock"></i> Pay Now & Activate Membership
          </button>
        </form>
      </div>

      <div class="card-box">
        <h2><i class="fa-solid fa-crown"></i> Selected Plan</h2>
        <p>Review your chosen membership plan before making payment.</p>

        <div class="plan-preview">
          <div class="mini">Chosen Membership Plan</div>
          <div class="plan-name"><?php echo htmlspecialchars($selected_plan['name']); ?></div>
          <div class="plan-price">LKR <?php echo number_format((float)$selected_plan['price'], 2); ?></div>
          <div class="plan-meta">
            <?php echo htmlspecialchars(ucfirst($selected_plan['billing_period'])); ?> •
            <?php echo (int)$selected_plan['duration']; ?> Days
          </div>

          <?php if (!empty($selected_plan['description'])): ?>
            <div style="color:#e2e8f0; line-height:1.7; margin-bottom:12px;">
              <?php echo htmlspecialchars($selected_plan['description']); ?>
            </div>
          <?php endif; ?>

          <?php
            $benefits = array_filter(array_map('trim', explode(',', $selected_plan['benefits'] ?? '')));
          ?>
          <?php if (!empty($benefits)): ?>
            <ul>
              <?php foreach ($benefits as $benefit): ?>
                <li>
                  <i class="fa-solid fa-check"></i>
                  <span><?php echo htmlspecialchars($benefit); ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Current Membership Summary -->
  <div class="membership-card">
    <div class="membership-card-top">
      <div>
        <div class="membership-title">Current Membership</div>
        <div class="membership-plan"><?php echo htmlspecialchars($current_plan); ?></div>
        <span class="membership-status"><?php echo htmlspecialchars($membership_status); ?></span>
      </div>

      <div style="color:#cbd5e1; font-size:15px; font-weight:600;">
        <?php echo htmlspecialchars($email); ?>
      </div>
    </div>

    <div class="membership-meta">
      <div class="box">
        <span>Start Date</span>
        <strong><?php echo htmlspecialchars($membership_start); ?></strong>
      </div>
      <div class="box">
        <span>End Date</span>
        <strong><?php echo htmlspecialchars($membership_end); ?></strong>
      </div>
    </div>
  </div>

  <!-- Billing Summary -->
  <div class="summary-grid">
    <div class="summary-card">
      <div class="label">Total Paid</div>
      <div class="value">LKR <?php echo number_format($total_paid, 2); ?></div>
      <div class="sub">All successful membership payments</div>
    </div>

    <div class="summary-card">
      <div class="label">Successful Payments</div>
      <div class="value"><?php echo $paid_count; ?></div>
      <div class="sub">Completed transactions</div>
    </div>

    <div class="summary-card">
      <div class="label">Pending Payments</div>
      <div class="value"><?php echo $pending_count; ?></div>
      <div class="sub">Waiting for confirmation</div>
    </div>

    <div class="summary-card">
      <div class="label">Last Payment</div>
      <div class="value" style="font-size:20px;"><?php echo htmlspecialchars($last_payment_date); ?></div>
      <div class="sub">Most recent successful payment</div>
    </div>
  </div>

  <!-- Payment History -->
  <div class="section-card">
    <div class="section-head">
      <div>
        <h2><i class="fa-solid fa-receipt"></i> Payment History</h2>
        <p>See all your real payment records here.</p>
      </div>

      <a href="membership.php" class="btn btn-primary">
        <i class="fa-solid fa-crown"></i> View Membership Plans
      </a>
    </div>

    <?php if (!empty($payments)): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Plan</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $index => $payment): ?>
              <?php
                $statusRaw = strtolower(trim($payment['payment_status'] ?? 'unknown'));
                $statusClass = in_array($statusRaw, ['paid','pending','failed']) ? $statusRaw : 'unknown';

                $planName = $payment['plan_name'] ?? 'Membership Payment';
                $amount = (float)($payment['amount'] ?? 0);
                $method = $payment['payment_method'] ?? 'N/A';

                $dateValue = '-';
                if (!empty($payment['payment_date'])) {
                    $dateValue = date("d M Y h:i A", strtotime($payment['payment_date']));
                }
              ?>
              <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($planName); ?></td>
                <td>LKR <?php echo number_format($amount, 2); ?></td>
                <td><?php echo htmlspecialchars($method); ?></td>
                <td>
                  <span class="badge <?php echo htmlspecialchars($statusClass); ?>">
                    <?php echo htmlspecialchars(ucfirst($statusRaw)); ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars($dateValue); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-regular fa-credit-card"></i>
        <h3>No Payments Yet</h3>
        <p>
          You have not made any membership payments yet. Once you purchase or renew a membership,
          your billing history will appear here.
        </p>
        <a href="membership.php" class="btn btn-primary">
          <i class="fa-solid fa-dumbbell"></i> View Membership Plans
        </a>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
  const cardInput = document.querySelector('input[name="card_number"]');
  if (cardInput) {
    cardInput.addEventListener('input', function () {
      let value = this.value.replace(/\D/g, '').substring(0, 16);
      value = value.replace(/(.{4})/g, '$1 ').trim();
      this.value = value;
    });
  }

  const monthInput = document.querySelector('input[name="expiry_month"]');
  if (monthInput) {
    monthInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').substring(0, 2);
    });
  }

  const yearInput = document.querySelector('input[name="expiry_year"]');
  if (yearInput) {
    yearInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').substring(0, 4);
    });
  }

  const cvvInput = document.querySelector('input[name="cvv"]');
  if (cvvInput) {
    cvvInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').substring(0, 4);
    });
  }
</script>

</body>
</html>
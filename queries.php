<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$success = "";
$error = "";

/* -----------------------------
   Create table if not exists
----------------------------- */
$createTableSQL = "
CREATE TABLE IF NOT EXISTS queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    response TEXT DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    admin_reply TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($createTableSQL);

/* -----------------------------
   Add missing columns safely
----------------------------- */
$check = $conn->query("SHOW COLUMNS FROM queries LIKE 'response'");
if ($check && $check->num_rows === 0) {
    $conn->query("ALTER TABLE queries ADD response TEXT DEFAULT NULL AFTER message");
}

$check = $conn->query("SHOW COLUMNS FROM queries LIKE 'admin_reply'");
if ($check && $check->num_rows === 0) {
    $conn->query("ALTER TABLE queries ADD admin_reply TEXT DEFAULT NULL AFTER status");
}

$check = $conn->query("SHOW COLUMNS FROM queries LIKE 'updated_at'");
if ($check && $check->num_rows === 0) {
    $conn->query("ALTER TABLE queries ADD updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
}

/* -----------------------------
   Submit new query
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_query'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || $message === '') {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO queries (user_id, subject, message, status)
            VALUES (?, ?, ?, 'pending')
        ");

        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $subject, $message);

            if ($stmt->execute()) {
                $success = "Your query has been sent successfully.";
            } else {
                $error = "Failed to send your query.";
            }

            $stmt->close();
        } else {
            $error = "Database error.";
        }
    }
}

/* -----------------------------
   Fetch customer queries
----------------------------- */
$queries = [];
$stmt = $conn->prepare("
    SELECT 
        id,
        subject,
        message,
        status,
        response,
        admin_reply,
        created_at,
        updated_at
    FROM queries
    WHERE user_id = ?
    ORDER BY created_at DESC
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $queries[] = $row;
    }

    $stmt->close();
}

/* -----------------------------
   Counts
----------------------------- */
$totalQueries = 0;
$pendingQueries = 0;
$resolvedQueries = 0;

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM queries WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $totalQueries = (int)($res['total'] ?? 0);
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM queries WHERE user_id = ? AND status = 'pending'");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $pendingQueries = (int)($res['total'] ?? 0);
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM queries WHERE user_id = ? AND status = 'resolved'");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $resolvedQueries = (int)($res['total'] ?? 0);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Queries - FitZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }
        body{
            background:#f4f7fb;
            color:#1f2937;
        }
        .container{
            width:90%;
            max-width:1100px;
            margin:30px auto;
        }
        .page-title{
            font-size:32px;
            margin-bottom:20px;
            color:#111827;
        }
        .stats{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
            gap:18px;
            margin-bottom:22px;
        }
        .stat-card{
            background:#fff;
            border-radius:14px;
            padding:20px;
            box-shadow:0 4px 14px rgba(0,0,0,0.08);
        }
        .stat-card h3{
            font-size:15px;
            color:#6b7280;
            margin-bottom:8px;
        }
        .stat-card p{
            font-size:28px;
            font-weight:700;
            color:#111827;
        }
        .card{
            background:#fff;
            border-radius:14px;
            padding:22px;
            box-shadow:0 4px 14px rgba(0,0,0,0.08);
            margin-bottom:25px;
        }
        .card h3{
            margin-bottom:18px;
            color:#111827;
        }
        .form-group{
            margin-bottom:15px;
        }
        .form-group label{
            display:block;
            margin-bottom:7px;
            font-weight:600;
        }
        .form-group input,
        .form-group textarea{
            width:100%;
            padding:12px 14px;
            border:1px solid #d1d5db;
            border-radius:10px;
            outline:none;
            font-size:15px;
        }
        .form-group textarea{
            min-height:120px;
            resize:vertical;
        }
        .btn{
            background:#2563eb;
            color:#fff;
            border:none;
            padding:12px 22px;
            border-radius:10px;
            cursor:pointer;
            font-size:15px;
            font-weight:600;
        }
        .btn:hover{
            background:#1d4ed8;
        }
        .msg-success{
            background:#dcfce7;
            color:#166534;
            padding:12px 14px;
            border-radius:10px;
            margin-bottom:16px;
        }
        .msg-error{
            background:#fee2e2;
            color:#991b1b;
            padding:12px 14px;
            border-radius:10px;
            margin-bottom:16px;
        }
        .query-box{
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:18px;
            margin-bottom:16px;
            background:#fafafa;
        }
        .query-top{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
            margin-bottom:10px;
        }
        .query-subject{
            font-size:18px;
            font-weight:700;
            color:#111827;
        }
        .badge{
            padding:6px 12px;
            border-radius:999px;
            font-size:13px;
            font-weight:700;
            text-transform:capitalize;
        }
        .pending{
            background:#fef3c7;
            color:#92400e;
        }
        .resolved{
            background:#dcfce7;
            color:#166534;
        }
        .date{
            font-size:13px;
            color:#6b7280;
            margin-bottom:10px;
        }
        .message{
            margin-bottom:12px;
            line-height:1.6;
        }
        .reply{
            background:#eff6ff;
            border-left:4px solid #2563eb;
            padding:12px;
            border-radius:8px;
            margin-top:10px;
            line-height:1.7;
        }
        .reply strong{
            display:block;
            margin-bottom:5px;
            color:#111827;
        }
        .empty{
            text-align:center;
            color:#6b7280;
            padding:20px 0;
        }
        .back-link{
            display:inline-block;
            margin-bottom:18px;
            text-decoration:none;
            color:#2563eb;
            font-weight:600;
        }
    </style>
</head>
<body>
    <div class="container">
        <a class="back-link" href="index.php"><i class="fa-solid fa-arrow-left"></i> Back</a>

        <h1 class="page-title">My Queries</h1>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Queries</h3>
                <p><?php echo $totalQueries; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Queries</h3>
                <p><?php echo $pendingQueries; ?></p>
            </div>
            <div class="stat-card">
                <h3>Resolved Queries</h3>
                <p><?php echo $resolvedQueries; ?></p>
            </div>
        </div>

        <div class="card">
            <h3>Send a New Query</h3>

            <?php if ($success): ?>
                <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" placeholder="Enter query subject">
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" placeholder="Type your query here..."></textarea>
                </div>

                <button type="submit" name="send_query" class="btn">
                    <i class="fa-solid fa-paper-plane"></i> Send Query
                </button>
            </form>
        </div>

        <div class="card">
            <h3>My Previous Queries</h3>

            <?php if (count($queries) > 0): ?>
                <?php foreach ($queries as $query): ?>
                    <?php
                        $replyText = '';

                        if (!empty($query['admin_reply'])) {
                            $replyText = $query['admin_reply'];
                        } elseif (!empty($query['response'])) {
                            $replyText = $query['response'];
                        }
                    ?>

                    <div class="query-box">
                        <div class="query-top">
                            <div class="query-subject"><?php echo htmlspecialchars($query['subject']); ?></div>
                            <span class="badge <?php echo $query['status'] === 'resolved' ? 'resolved' : 'pending'; ?>">
                                <?php echo htmlspecialchars(ucfirst($query['status'])); ?>
                            </span>
                        </div>

                        <div class="date">
                            Sent on: <?php echo htmlspecialchars($query['created_at']); ?>
                        </div>

                        <div class="message">
                            <?php echo nl2br(htmlspecialchars($query['message'])); ?>
                        </div>

                        <?php if ($replyText !== ''): ?>
                            <div class="reply">
                                <strong>Admin Reply:</strong>
                                <?php echo nl2br(htmlspecialchars($replyText)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">No queries found yet.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
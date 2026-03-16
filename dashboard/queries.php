<?php
session_start();
require_once "../config/db.php";

/* -----------------------------
   Protect admin page
----------------------------- */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

/* -----------------------------
   Ensure table exists
----------------------------- */
$createTableSQL = "
CREATE TABLE IF NOT EXISTS queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    response TEXT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    admin_reply TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($createTableSQL);

/* -----------------------------
   Add missing columns safely
----------------------------- */
$neededColumns = [
    "user_id"     => "ALTER TABLE queries ADD user_id INT NULL AFTER id",
    "name"        => "ALTER TABLE queries ADD name VARCHAR(120) NOT NULL AFTER user_id",
    "email"       => "ALTER TABLE queries ADD email VARCHAR(150) NOT NULL AFTER name",
    "phone"       => "ALTER TABLE queries ADD phone VARCHAR(30) DEFAULT NULL AFTER email",
    "subject"     => "ALTER TABLE queries ADD subject VARCHAR(200) NOT NULL AFTER phone",
    "message"     => "ALTER TABLE queries ADD message TEXT NOT NULL AFTER subject",
    "response"    => "ALTER TABLE queries ADD response TEXT DEFAULT NULL AFTER message",
    "status"      => "ALTER TABLE queries ADD status VARCHAR(20) DEFAULT 'pending' AFTER response",
    "admin_reply" => "ALTER TABLE queries ADD admin_reply TEXT DEFAULT NULL AFTER status",
    "created_at"  => "ALTER TABLE queries ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER admin_reply",
    "updated_at"  => "ALTER TABLE queries ADD updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
];

foreach ($neededColumns as $col => $sqlFix) {
    $check = $conn->query("SHOW COLUMNS FROM queries LIKE '$col'");
    if ($check && $check->num_rows === 0) {
        $conn->query($sqlFix);
    }
}

/* -----------------------------
   Update query status + reply
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_query'])) {
    $query_id    = (int)($_POST['query_id'] ?? 0);
    $status      = trim($_POST['status'] ?? '');
    $admin_reply = trim($_POST['admin_reply'] ?? '');

    $allowed = ['pending', 'resolved'];

    if ($query_id <= 0 || !in_array($status, $allowed, true)) {
        $error = "Invalid request.";
    } elseif ($status === 'resolved' && $admin_reply === '') {
        $error = "Please enter an admin reply before marking the query as resolved.";
    } else {
        $stmt = $conn->prepare("
            UPDATE queries
            SET status = ?, admin_reply = ?, response = ?
            WHERE id = ?
        ");

        if ($stmt) {
            $stmt->bind_param("sssi", $status, $admin_reply, $admin_reply, $query_id);

            if ($stmt->execute()) {
                $message = "Query updated successfully.";
            } else {
                $error = "Failed to update query.";
            }

            $stmt->close();
        } else {
            $error = "Database error.";
        }
    }
}

/* -----------------------------
   Fetch all queries
----------------------------- */
$queries = [];

$sql = "
    SELECT 
        id,
        user_id,
        name,
        email,
        phone,
        subject,
        message,
        status,
        response,
        admin_reply,
        created_at,
        updated_at
    FROM queries
    ORDER BY created_at DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $queries[] = $row;
    }
}

/* -----------------------------
   Dashboard counts
----------------------------- */
$totalQueries = 0;
$pendingQueries = 0;
$resolvedQueries = 0;

$res = $conn->query("SELECT COUNT(*) AS total FROM queries");
if ($res) {
    $totalQueries = (int)($res->fetch_assoc()['total'] ?? 0);
}

$res = $conn->query("SELECT COUNT(*) AS total FROM queries WHERE status = 'pending'");
if ($res) {
    $pendingQueries = (int)($res->fetch_assoc()['total'] ?? 0);
}

$res = $conn->query("SELECT COUNT(*) AS total FROM queries WHERE status = 'resolved'");
if ($res) {
    $resolvedQueries = (int)($res->fetch_assoc()['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Queries - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }
        body{
            background:#f3f6fb;
            color:#1f2937;
        }
        .container{
            width:95%;
            max-width:1400px;
            margin:30px auto;
        }
        .title{
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
        .alert-success{
            background:#dcfce7;
            color:#166534;
            padding:12px 15px;
            border-radius:10px;
            margin-bottom:15px;
        }
        .alert-error{
            background:#fee2e2;
            color:#991b1b;
            padding:12px 15px;
            border-radius:10px;
            margin-bottom:15px;
        }
        .table-wrap{
            background:#fff;
            border-radius:14px;
            box-shadow:0 4px 14px rgba(0,0,0,0.08);
            overflow:auto;
            padding:15px;
        }
        table{
            width:100%;
            border-collapse:collapse;
            min-width:1250px;
        }
        th, td{
            padding:14px;
            text-align:left;
            border-bottom:1px solid #e5e7eb;
            vertical-align:top;
        }
        th{
            background:#111827;
            color:#fff;
            font-size:14px;
        }
        td{
            font-size:14px;
        }
        .badge{
            display:inline-block;
            padding:6px 12px;
            border-radius:999px;
            font-size:12px;
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
        textarea, select{
            width:100%;
            padding:10px;
            border:1px solid #d1d5db;
            border-radius:8px;
            font-size:14px;
        }
        textarea{
            min-height:90px;
            resize:vertical;
        }
        .btn{
            background:#2563eb;
            color:#fff;
            border:none;
            padding:10px 14px;
            border-radius:8px;
            cursor:pointer;
            font-weight:600;
        }
        .btn:hover{
            background:#1d4ed8;
        }
        .name{
            font-weight:700;
            margin-bottom:4px;
        }
        .mail, .phone{
            color:#6b7280;
            font-size:13px;
            margin-bottom:3px;
        }
        .small{
            color:#6b7280;
            font-size:12px;
            margin-top:4px;
        }
        .reply-box{
            background:#eff6ff;
            border-left:4px solid #2563eb;
            padding:10px 12px;
            border-radius:8px;
            line-height:1.6;
        }
        .no-reply{
            color:#9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">Manage Customer Queries</h1>

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

        <?php if ($message): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Current Reply</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($queries) > 0): ?>
                        <?php foreach ($queries as $q): ?>
                            <?php
                                $replyText = '';
                                if (!empty($q['admin_reply'])) {
                                    $replyText = $q['admin_reply'];
                                } elseif (!empty($q['response'])) {
                                    $replyText = $q['response'];
                                }
                            ?>
                            <tr>
                                <td><?php echo (int)$q['id']; ?></td>

                                <td>
                                    <div class="name"><?php echo htmlspecialchars($q['name'] ?: 'Customer'); ?></div>
                                    <div class="mail"><?php echo htmlspecialchars($q['email'] ?? ''); ?></div>
                                    <div class="phone"><?php echo htmlspecialchars($q['phone'] ?? ''); ?></div>
                                    <div class="small">User ID: <?php echo htmlspecialchars($q['user_id'] ?? 'N/A'); ?></div>
                                </td>

                                <td><?php echo htmlspecialchars($q['subject']); ?></td>

                                <td><?php echo nl2br(htmlspecialchars($q['message'])); ?></td>

                                <td>
                                    <span class="badge <?php echo $q['status'] === 'resolved' ? 'resolved' : 'pending'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($q['status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($replyText !== ''): ?>
                                        <div class="reply-box">
                                            <?php echo nl2br(htmlspecialchars($replyText)); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-reply">No reply yet</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($q['created_at']); ?>
                                    <div class="small">Updated: <?php echo htmlspecialchars($q['updated_at']); ?></div>
                                </td>

                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="query_id" value="<?php echo (int)$q['id']; ?>">

                                        <div style="margin-bottom:10px;">
                                            <select name="status" required>
                                                <option value="pending" <?php echo $q['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="resolved" <?php echo $q['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                            </select>
                                        </div>

                                        <div style="margin-bottom:10px;">
                                            <textarea name="admin_reply" placeholder="Type admin reply..."><?php echo htmlspecialchars($replyText); ?></textarea>
                                        </div>

                                        <button type="submit" name="update_query" class="btn">
                                            <i class="fa-solid fa-pen-to-square"></i> Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; color:#6b7280;">No queries found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

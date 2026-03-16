<?php
require_once __DIR__ . "/includes/auth.php";

$message = "";
$error = "";

$tableName = "queries";

$exists = $conn->query("SHOW TABLES LIKE '{$tableName}'");
if (!$exists || $exists->num_rows === 0) {
    die("Queries table not found. If your table name is contact_queries, change it in staff/queries.php");
}

$checkResponse = $conn->query("SHOW COLUMNS FROM {$tableName} LIKE 'response'");
if ($checkResponse && $checkResponse->num_rows === 0) {
    $conn->query("ALTER TABLE {$tableName} ADD response TEXT NULL");
}

$checkStatus = $conn->query("SHOW COLUMNS FROM {$tableName} LIKE 'status'");
if ($checkStatus && $checkStatus->num_rows === 0) {
    $conn->query("ALTER TABLE {$tableName} ADD status VARCHAR(20) NOT NULL DEFAULT 'pending'");
}

/* Handle save response */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query_id'])) {
    $id = (int)($_POST['query_id'] ?? 0);
    $response = trim($_POST['response'] ?? '');
    $status = trim($_POST['status'] ?? 'pending');

    /* auto resolve if response is given */
    if ($response !== '') {
        $status = 'resolved';
    }

    if (!in_array($status, ['pending', 'resolved'])) {
        $status = 'pending';
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE {$tableName} SET response = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $response, $status, $id);

        if ($stmt->execute()) {
            $message = ($status === 'resolved')
                ? "Reply saved and query marked as resolved."
                : "Query updated successfully.";
        } else {
            $error = "Failed to update query.";
        }
        $stmt->close();
    }
}

/* Fetch queries */
$pendingQueries = [];
$resolvedQueries = [];

$res = $conn->query("SELECT * FROM {$tableName} ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $status = strtolower(trim($row['status'] ?? 'pending'));
        if ($status === 'resolved') {
            $resolvedQueries[] = $row;
        } else {
            $pendingQueries[] = $row;
        }
    }
}

$pendingCount = count($pendingQueries);
$resolvedCount = count($resolvedQueries);
$totalCount = $pendingCount + $resolvedCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Queries | Staff Panel</title>
<style>
body{
    margin:0;
    font-family:Arial,sans-serif;
    background:#f4f7fb;
}
.page-title{
    font-size:32px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:22px;
}
.top-stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
    margin-bottom:24px;
}
.stat-card{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:20px;
    box-shadow:0 10px 24px rgba(0,0,0,.05);
}
.stat-card h3{
    margin:0 0 10px;
    font-size:16px;
    color:#64748b;
}
.stat-card .value{
    font-size:34px;
    font-weight:800;
    color:#0f172a;
}
.section-title{
    font-size:24px;
    font-weight:800;
    color:#0f172a;
    margin:0 0 16px;
}
.card{
    background:#fff;
    border-radius:18px;
    padding:22px;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 24px rgba(0,0,0,.05);
    margin-bottom:24px;
}
.msg{
    padding:14px 16px;
    border-radius:12px;
    margin-bottom:16px;
    font-weight:700;
}
.ok{
    background:#dcfce7;
    color:#166534;
}
.err{
    background:#fee2e2;
    color:#991b1b;
}
.query-box{
    border:1px solid #e5e7eb;
    border-radius:16px;
    padding:20px;
    margin-bottom:18px;
    background:#fff;
}
.query-box:last-child{
    margin-bottom:0;
}
.pending-box{
    border-left:5px solid #f59e0b;
}
.resolved-box{
    border-left:5px solid #22c55e;
    background:#f9fffb;
}
.meta{
    color:#6b7280;
    font-size:14px;
    margin-bottom:12px;
}
.query-text{
    margin:10px 0;
    font-size:15px;
    line-height:1.7;
    color:#111827;
}
textarea, select{
    width:100%;
    padding:12px;
    border:1px solid #d1d5db;
    border-radius:12px;
    margin-top:10px;
    font-size:15px;
    box-sizing:border-box;
    background:#fff;
}
textarea{
    min-height:120px;
    resize:vertical;
}
button{
    margin-top:14px;
    padding:12px 18px;
    border:none;
    border-radius:12px;
    background:#22c55e;
    color:#062d16;
    font-weight:700;
    cursor:pointer;
}
button:hover{
    opacity:.95;
}
.badge{
    display:inline-block;
    padding:6px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}
.pending{
    background:#fef3c7;
    color:#92400e;
}
.resolved{
    background:#dcfce7;
    color:#166534;
}
.empty-box{
    padding:18px;
    border:1px dashed #cbd5e1;
    border-radius:16px;
    color:#64748b;
    background:#f8fafc;
    font-weight:600;
}
.label{
    display:block;
    margin-top:10px;
    font-weight:700;
    color:#111827;
}
.answer-box{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:14px;
    margin-top:10px;
    color:#0f172a;
    line-height:1.7;
    white-space:pre-wrap;
}
.small-note{
    margin-top:10px;
    font-size:13px;
    color:#64748b;
}
@media (max-width: 900px){
    .top-stats{
        grid-template-columns:1fr;
    }
}
</style>
</head>
<body>
<div class="staff-layout">
    <?php include __DIR__ . "/includes/sidebar.php"; ?>

    <div class="staff-main">
        <div class="page-title">Customer Queries</div>

        <?php if ($message): ?>
            <div class="msg ok"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="msg err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Top Stats -->
        <div class="top-stats">
            <div class="stat-card">
                <h3>Total Queries</h3>
                <div class="value"><?= $totalCount ?></div>
            </div>

            <div class="stat-card">
                <h3>Pending Queries</h3>
                <div class="value"><?= $pendingCount ?></div>
            </div>

            <div class="stat-card">
                <h3>Resolved Queries</h3>
                <div class="value"><?= $resolvedCount ?></div>
            </div>
        </div>

        <!-- Pending Queries -->
        <div class="card">
            <div class="section-title">🟡 Pending Queries</div>

            <?php if (!empty($pendingQueries)): ?>
                <?php foreach ($pendingQueries as $q): ?>
                    <div class="query-box pending-box">
                        <div class="meta">
                            <strong><?= htmlspecialchars($q['name'] ?? 'Customer') ?></strong> |
                            <?= htmlspecialchars($q['email'] ?? '') ?> |
                            <span class="badge pending">Pending</span>
                        </div>

                        <div class="query-text"><strong>Subject:</strong> <?= htmlspecialchars($q['subject'] ?? '') ?></div>
                        <div class="query-text"><strong>Message:</strong> <?= htmlspecialchars($q['message'] ?? '') ?></div>

                        <form method="POST">
                            <input type="hidden" name="query_id" value="<?= (int)$q['id'] ?>">

                            <label class="label">Response</label>
                            <textarea name="response" placeholder="Type your reply here..."><?= htmlspecialchars($q['response'] ?? '') ?></textarea>

                            <label class="label">Status</label>
                            <select name="status">
                                <option value="pending" selected>Pending</option>
                                <option value="resolved">Resolved</option>
                            </select>

                            <button type="submit">Save Response</button>
                            <div class="small-note">Once a response is entered, this query will automatically move to Resolved Queries.</div>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-box">No pending queries right now.</div>
            <?php endif; ?>
        </div>

        <!-- Resolved Queries -->
        <div class="card">
            <div class="section-title">🟢 Resolved Queries</div>

            <?php if (!empty($resolvedQueries)): ?>
                <?php foreach ($resolvedQueries as $q): ?>
                    <div class="query-box resolved-box">
                        <div class="meta">
                            <strong><?= htmlspecialchars($q['name'] ?? 'Customer') ?></strong> |
                            <?= htmlspecialchars($q['email'] ?? '') ?> |
                            <span class="badge resolved">Resolved</span>
                        </div>

                        <div class="query-text"><strong>Subject:</strong> <?= htmlspecialchars($q['subject'] ?? '') ?></div>
                        <div class="query-text"><strong>Message:</strong> <?= htmlspecialchars($q['message'] ?? '') ?></div>

                        <label class="label">Response</label>
                        <div class="answer-box"><?= nl2br(htmlspecialchars($q['response'] ?? 'No response added.')) ?></div>

                        <form method="POST">
                            <input type="hidden" name="query_id" value="<?= (int)$q['id'] ?>">
                            <input type="hidden" name="response" value="<?= htmlspecialchars($q['response'] ?? '', ENT_QUOTES) ?>">
                            <input type="hidden" name="status" value="pending">

                            <div class="small-note">Need to work on this again?</div>
                            <button type="submit">Reopen Query</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-box">No resolved queries yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
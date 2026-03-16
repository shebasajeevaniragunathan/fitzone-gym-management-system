<?php
require_once __DIR__ . "/includes/auth.php";

$customers = [];
$res = $conn->query("
    SELECT id, first_name, last_name, full_name, email, phone, status, created_at
    FROM users
    WHERE role = 'customer'
    ORDER BY id DESC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $customers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers | Staff Panel</title>
<style>
body{margin:0;font-family:Arial,sans-serif}
.page-title{font-size:32px;font-weight:800;color:#0f172a;margin-bottom:22px}
.card{background:#fff;border-radius:18px;padding:22px;border:1px solid #e5e7eb;box-shadow:0 10px 24px rgba(0,0,0,.05)}
table{width:100%;border-collapse:collapse}
th,td{padding:14px;border-bottom:1px solid #e5e7eb;text-align:left}
th{background:#f8fafc}
.status{
    padding:6px 12px;border-radius:999px;font-size:12px;font-weight:700;
}
.active{background:#dcfce7;color:#166534}
.blocked{background:#fee2e2;color:#991b1b}
</style>
</head>
<body>
<div class="staff-layout">
    <?php include __DIR__ . "/includes/sidebar.php"; ?>

    <div class="staff-main">
        <div class="page-title">Customer Registrations</div>

        <div class="card">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Registered On</th>
                </tr>
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $c): ?>
                    <?php
                    $name = $c['full_name'] ?: trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                    ?>
                    <tr>
                        <td><?= (int)$c['id'] ?></td>
                        <td><?= htmlspecialchars($name ?: 'Customer') ?></td>
                        <td><?= htmlspecialchars($c['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($c['phone'] ?? '') ?></td>
                        <td>
                            <span class="status <?= (($c['status'] ?? '') === 'active') ? 'active' : 'blocked' ?>">
                                <?= htmlspecialchars(ucfirst($c['status'] ?? 'unknown')) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($c['created_at'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No customers found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
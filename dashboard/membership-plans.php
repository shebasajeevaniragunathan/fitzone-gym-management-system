<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";
$editPlan = null;

/* =========================
   CREATE TABLE IF NOT EXISTS
========================= */
$createTableSQL = "
CREATE TABLE IF NOT EXISTS memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL,
    description TEXT NOT NULL
)";
$conn->query($createTableSQL);

/* =========================
   ADD DESCRIPTION COLUMN IF MISSING
========================= */
$checkColumn = $conn->query("SHOW COLUMNS FROM memberships LIKE 'description'");
if ($checkColumn && $checkColumn->num_rows === 0) {
    $conn->query("ALTER TABLE memberships ADD description TEXT NOT NULL AFTER duration");
}

/* =========================
   ADD PLAN
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plan'])) {
    $name        = trim($_POST['name'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $duration    = (int)($_POST['duration'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $price <= 0 || $duration <= 0 || $description === '') {
        $error = "Plan name, price, duration and details are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO memberships (name, price, duration, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdis", $name, $price, $duration, $description);

        if ($stmt->execute()) {
            header("Location: membership-plans.php?success=added");
            exit;
        } else {
            $error = "Failed to add plan.";
        }
        $stmt->close();
    }
}

/* =========================
   FETCH PLAN FOR EDIT
========================= */
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    if ($id > 0) {
        $stmt = $conn->prepare("SELECT * FROM memberships WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $editPlan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$editPlan) {
            $error = "Plan not found.";
        }
    }
}

/* =========================
   UPDATE PLAN
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_plan'])) {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $duration    = (int)($_POST['duration'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0 || $name === '' || $price <= 0 || $duration <= 0 || $description === '') {
        $error = "Valid plan details are required.";
    } else {
        $stmt = $conn->prepare("UPDATE memberships SET name = ?, price = ?, duration = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sdisi", $name, $price, $duration, $description, $id);

        if ($stmt->execute()) {
            header("Location: membership-plans.php?success=updated");
            exit;
        } else {
            $error = "Failed to update plan.";
        }
        $stmt->close();
    }
}

/* =========================
   DELETE PLAN
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM memberships WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: membership-plans.php?success=deleted");
            exit;
        } else {
            $error = "Failed to delete plan.";
        }
        $stmt->close();
    }
}

/* =========================
   SUCCESS MESSAGE
========================= */
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $message = "Membership plan added successfully.";
    } elseif ($_GET['success'] === 'updated') {
        $message = "Membership plan updated successfully.";
    } elseif ($_GET['success'] === 'deleted') {
        $message = "Membership plan deleted successfully.";
    }
}

/* =========================
   FETCH ALL PLANS
========================= */
$plans = [];
$res = $conn->query("SELECT * FROM memberships ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $plans[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Membership Plans | FitZone</title>
  <style>
    *{
      box-sizing:border-box;
      margin:0;
      padding:0;
      font-family:Arial, sans-serif;
    }

    body{
      background:#f4f7fb;
      color:#1f2937;
    }

    .container{
      max-width:1300px;
      margin:30px auto;
      padding:20px;
    }

    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      flex-wrap:wrap;
      gap:15px;
      margin-bottom:25px;
    }

    .topbar h1{
      font-size:30px;
      font-weight:800;
    }

    .back-btn{
      text-decoration:none;
      background:#111827;
      color:#fff;
      padding:12px 18px;
      border-radius:10px;
      font-weight:700;
    }

    .grid{
      display:grid;
      grid-template-columns:380px 1fr;
      gap:20px;
    }

    .card{
      background:#fff;
      border-radius:18px;
      padding:22px;
      box-shadow:0 8px 25px rgba(0,0,0,0.06);
      border:1px solid #e5e7eb;
    }

    .card h2{
      margin-bottom:18px;
      font-size:22px;
    }

    label{
      display:block;
      margin-bottom:6px;
      font-weight:600;
    }

    input, textarea{
      width:100%;
      padding:12px;
      border:1px solid #d1d5db;
      border-radius:10px;
      margin-bottom:15px;
      font-size:14px;
    }

    textarea{
      min-height:110px;
      resize:vertical;
    }

    .btn{
      border:none;
      border-radius:10px;
      padding:12px 16px;
      cursor:pointer;
      font-weight:700;
      text-decoration:none;
      display:inline-block;
    }

    .btn-green{
      background:#22c55e;
      color:white;
      width:100%;
    }

    .btn-blue{
      background:#3b82f6;
      color:white;
      width:100%;
    }

    .btn-gray{
      background:#e5e7eb;
      color:#111827;
      width:100%;
      text-align:center;
      margin-top:10px;
    }

    .btn-edit{
      background:#f59e0b;
      color:white;
      padding:8px 12px;
      border-radius:8px;
      text-decoration:none;
      font-size:14px;
    }

    .btn-delete{
      background:#ef4444;
      color:white;
      padding:8px 12px;
      border-radius:8px;
      text-decoration:none;
      font-size:14px;
    }

    .msg{
      padding:12px 15px;
      border-radius:10px;
      margin-bottom:20px;
      font-weight:600;
    }

    .success{
      background:#dcfce7;
      color:#166534;
    }

    .error{
      background:#fee2e2;
      color:#991b1b;
    }

    table{
      width:100%;
      border-collapse:collapse;
    }

    th, td{
      padding:14px;
      border-bottom:1px solid #e5e7eb;
      text-align:left;
      vertical-align:top;
    }

    th{
      background:#f9fafb;
    }

    .actions{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
    }

    .desc-cell{
      max-width:280px;
      line-height:1.5;
      color:#4b5563;
    }

    @media (max-width: 950px){
      .grid{
        grid-template-columns:1fr;
      }
    }
  </style>
</head>
<body>

<div class="container">

  <div class="topbar">
    <h1>💳 Membership Plans</h1>
    <a href="admin.php" class="back-btn">← Back to Dashboard</a>
  </div>

  <?php if ($message): ?>
    <div class="msg success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <div class="grid">

    <div class="card">
      <h2><?php echo $editPlan ? "✏️ Edit Plan" : "➕ Add New Plan"; ?></h2>

      <form method="POST" autocomplete="off">
        <label>Plan Name</label>
        <input type="text" name="name" required
               value="<?php echo htmlspecialchars($editPlan['name'] ?? ''); ?>">

        <label>Price (LKR)</label>
        <input type="number" step="0.01" min="0" name="price" required
               value="<?php echo htmlspecialchars($editPlan['price'] ?? ''); ?>">

        <label>Duration (Days)</label>
        <input type="number" min="1" name="duration" required
               value="<?php echo htmlspecialchars($editPlan['duration'] ?? ''); ?>">

        <label>Plan Details / Description</label>
        <textarea name="description" required><?php echo htmlspecialchars($editPlan['description'] ?? ''); ?></textarea>

        <?php if ($editPlan): ?>
          <input type="hidden" name="id" value="<?php echo (int)$editPlan['id']; ?>">
          <button type="submit" name="update_plan" class="btn btn-blue">Update Plan</button>
          <a href="membership-plans.php" class="btn btn-gray">Cancel</a>
        <?php else: ?>
          <button type="submit" name="add_plan" class="btn btn-green">Add Plan</button>
        <?php endif; ?>
      </form>
    </div>

    <div class="card">
      <h2>Existing Plans</h2>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Plan Name</th>
            <th>Price</th>
            <th>Duration</th>
            <th>Details</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($plans)): ?>
            <?php foreach ($plans as $plan): ?>
              <tr>
                <td><?php echo (int)$plan['id']; ?></td>
                <td><?php echo htmlspecialchars($plan['name']); ?></td>
                <td>Rs. <?php echo number_format((float)$plan['price'], 2); ?></td>
                <td><?php echo (int)$plan['duration']; ?> Days</td>
                <td class="desc-cell">
<?php
$desc = $plan['description'] ?? '';
if(trim($desc) === ''){
    $desc = "No details added yet.";
}
echo nl2br(htmlspecialchars($desc));
?>
</td>
                <td>
                  <div class="actions">
                    <a class="btn-edit" href="membership-plans.php?edit=<?php echo (int)$plan['id']; ?>">Edit</a>
                    <a class="btn-delete"
                       href="membership-plans.php?delete=<?php echo (int)$plan['id']; ?>"
                       onclick="return confirm('Are you sure you want to delete this plan?');">
                       Delete
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align:center; color:#6b7280;">No membership plans found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

    </div>
  </div>
</div>

</body>
</html>
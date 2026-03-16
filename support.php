<?php
session_start();
require_once "config/db.php"; // $conn

// If logged in, auto fill (optional)
$user_name  = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';

$success = "";
$error = "";

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO queries (user_name, user_email, subject, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        $stmt->execute();
        $stmt->close();

        $success = "✅ Your query has been submitted. Staff will respond soon!";
        // clear fields
        $user_name = $user_email = "";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Support</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f4f6f9;}
    .card{border-radius:14px;}
  </style>
</head>

<body>
<div class="container py-5" style="max-width:720px;">

  <h3 class="fw-bold mb-3">📩 Contact Support</h3>

  <div class="card shadow-sm border-0 p-4">

    <?php if($success){ ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php } ?>

    <?php if($error){ ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>

    <form method="post" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">Your Name</label>
        <input type="text" name="name" class="form-control" required
               value="<?php echo htmlspecialchars($user_name); ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required
               value="<?php echo htmlspecialchars($user_email); ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Subject</label>
        <input type="text" name="subject" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Message</label>
        <textarea name="message" class="form-control" rows="5" required></textarea>
      </div>

      <button class="btn btn-primary w-100">Submit Query</button>
    </form>

  </div>

  <div class="text-muted small mt-3">
    After submitting, staff can view it at: <b>/fitzone/staff/queries.php</b>
  </div>

</div>
</body>
</html>
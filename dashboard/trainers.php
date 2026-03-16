<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function tableExists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

$message = "";
$error = "";

/* =========================
   CREATE TABLE IF NOT EXISTS
========================= */
$createTableSQL = "
CREATE TABLE IF NOT EXISTS trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(150) DEFAULT NULL,
    experience TEXT DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTableSQL);

/* =========================
   ADD profile_image COLUMN IF MISSING
========================= */
$checkColumn = $conn->query("SHOW COLUMNS FROM trainers LIKE 'profile_image'");
if ($checkColumn && $checkColumn->num_rows === 0) {
    $conn->query("ALTER TABLE trainers ADD profile_image VARCHAR(255) NULL AFTER experience");
}

/* =========================
   DEFAULT EDIT VALUES
========================= */
$editMode = false;
$editTrainer = [
    'id' => '',
    'name' => '',
    'specialty' => '',
    'experience' => '',
    'profile_image' => ''
];

/* =========================
   EDIT FETCH
========================= */
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int)$_GET['edit'];

    $editStmt = $conn->prepare("SELECT * FROM trainers WHERE id = ? LIMIT 1");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editRes = $editStmt->get_result();

    if ($editRes && $editRes->num_rows === 1) {
        $editTrainer = $editRes->fetch_assoc();
        $editMode = true;
    } else {
        $error = "Trainer not found.";
    }
    $editStmt->close();
}

/* =========================
   ADD TRAINER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    $name        = trim($_POST['name'] ?? '');
    $specialty   = trim($_POST['specialty'] ?? '');
    $experience  = trim($_POST['experience'] ?? '');
    $profileImage = null;

    if ($name === '') {
        $error = "Trainer name is required.";
    } else {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $uploadDir = __DIR__ . "/../uploads/trainers/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $tmpName  = $_FILES['profile_image']['tmp_name'];
            $fileName = $_FILES['profile_image']['name'];
            $fileSize = $_FILES['profile_image']['size'];

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, JPEG, PNG, and WEBP images are allowed.";
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $error = "Image size must be less than 2MB.";
            } else {
                $newFileName = "trainer_" . time() . "_" . rand(1000, 9999) . "." . $ext;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $profileImage = $newFileName;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if ($error === "") {
            $st = $conn->prepare("INSERT INTO trainers (name, specialty, experience, profile_image) VALUES (?, ?, ?, ?)");
            $st->bind_param("ssss", $name, $specialty, $experience, $profileImage);

            if ($st->execute()) {
                header("Location: trainers.php?success=added");
                exit;
            } else {
                $error = "Failed to add trainer.";
            }
            $st->close();
        }
    }
}

/* =========================
   UPDATE TRAINER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_trainer'])) {
    $id          = (int)($_POST['trainer_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $specialty   = trim($_POST['specialty'] ?? '');
    $experience  = trim($_POST['experience'] ?? '');
    $oldImage    = trim($_POST['old_image'] ?? '');
    $profileImage = $oldImage;

    if ($id <= 0) {
        $error = "Invalid trainer ID.";
    } elseif ($name === '') {
        $error = "Trainer name is required.";
    } else {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $uploadDir = __DIR__ . "/../uploads/trainers/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $tmpName  = $_FILES['profile_image']['tmp_name'];
            $fileName = $_FILES['profile_image']['name'];
            $fileSize = $_FILES['profile_image']['size'];

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, JPEG, PNG, and WEBP images are allowed.";
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $error = "Image size must be less than 2MB.";
            } else {
                $newFileName = "trainer_" . time() . "_" . rand(1000, 9999) . "." . $ext;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $profileImage = $newFileName;

                    if (!empty($oldImage)) {
                        $oldPath = __DIR__ . "/../uploads/trainers/" . $oldImage;
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if ($error === "") {
            $up = $conn->prepare("UPDATE trainers SET name = ?, specialty = ?, experience = ?, profile_image = ? WHERE id = ? LIMIT 1");
            $up->bind_param("ssssi", $name, $specialty, $experience, $profileImage, $id);

            if ($up->execute()) {
                header("Location: trainers.php?success=updated");
                exit;
            } else {
                $error = "Failed to update trainer.";
            }
            $up->close();
        }
    }
}

/* =========================
   DELETE TRAINER
========================= */
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $imgStmt = $conn->prepare("SELECT profile_image FROM trainers WHERE id = ? LIMIT 1");
    $imgStmt->bind_param("i", $id);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result()->fetch_assoc();
    $imgStmt->close();

    $d = $conn->prepare("DELETE FROM trainers WHERE id = ? LIMIT 1");
    $d->bind_param("i", $id);

    if ($d->execute()) {
        if (!empty($imgRes['profile_image'])) {
            $oldPath = __DIR__ . "/../uploads/trainers/" . $imgRes['profile_image'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        header("Location: trainers.php?success=deleted");
        exit;
    } else {
        $error = "Failed to delete trainer.";
    }
    $d->close();
}

/* =========================
   SUCCESS MESSAGE
========================= */
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $message = "Trainer added successfully.";
    } elseif ($_GET['success'] === 'updated') {
        $message = "Trainer updated successfully.";
    } elseif ($_GET['success'] === 'deleted') {
        $message = "Trainer deleted successfully.";
    }
}

/* =========================
   FETCH TRAINERS
========================= */
$trainers = [];
if (tableExists($conn, 'trainers')) {
    $res = $conn->query("SELECT * FROM trainers ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $trainers[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Trainers | FitZone</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{box-sizing:border-box}
body{
    margin:0;
    background:#f4f7fb;
    font-family:Arial,sans-serif;
    color:#111827;
}
.wrap{
    max-width:1250px;
    margin:30px auto;
    padding:20px;
}
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:18px;
}
.grid{
    display:grid;
    grid-template-columns:360px 1fr;
    gap:20px;
}
.card{
    background:#fff;
    padding:22px;
    border-radius:18px;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 25px rgba(0,0,0,.05);
}
label{
    display:block;
    font-weight:600;
    margin-bottom:6px;
}
input,textarea{
    width:100%;
    padding:12px;
    border:1px solid #d1d5db;
    border-radius:12px;
    margin:6px 0 12px;
    font-size:14px;
}
textarea{
    min-height:110px;
    resize:vertical;
}
.btn{
    padding:11px 16px;
    border:none;
    border-radius:12px;
    font-weight:700;
    text-decoration:none;
    display:inline-block;
    cursor:pointer;
    transition:.2s ease;
}
.btn:hover{
    opacity:.9;
}
.btn-dark{background:#0f172a;color:#fff}
.btn-green{background:#22c55e;color:#062d16}
.btn-red{background:#ef4444;color:#fff}
.btn-blue{background:#3b82f6;color:#fff}
.btn-gray{background:#6b7280;color:#fff}
table{
    width:100%;
    border-collapse:collapse;
}
th,td{
    padding:14px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:middle;
}
.msg{
    padding:12px;
    border-radius:12px;
    margin-bottom:12px;
}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}

.trainer-thumb{
    width:60px;
    height:60px;
    border-radius:50%;
    object-fit:cover;
    background:#e5e7eb;
    border:2px solid #f3f4f6;
}

.no-img{
    width:60px;
    height:60px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#e5e7eb;
    color:#6b7280;
    font-size:12px;
    font-weight:700;
}

.current-img-box{
    margin:8px 0 14px;
}
.current-img-box img{
    width:70px;
    height:70px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #e5e7eb;
}
.action-wrap{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.form-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:10px;
}

@media(max-width:900px){
    .grid{grid-template-columns:1fr}
    table{font-size:14px; display:block; overflow-x:auto}
}
</style>
</head>
<body>
<div class="wrap">
  <div class="topbar">
    <h1>🏋️ Trainers</h1>
    <a href="admin.php" class="btn btn-dark">← Back to Dashboard</a>
  </div>

  <?php if($message): ?>
    <div class="msg ok"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if($error): ?>
    <div class="msg err"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="grid">
    <div class="card">
      <h2><?= $editMode ? 'Edit Trainer' : 'Add Trainer' ?></h2>

      <form method="POST" enctype="multipart/form-data" autocomplete="off">
        <?php if($editMode): ?>
          <input type="hidden" name="trainer_id" value="<?= (int)$editTrainer['id'] ?>">
          <input type="hidden" name="old_image" value="<?= htmlspecialchars($editTrainer['profile_image'] ?? '') ?>">
        <?php endif; ?>

        <label>Name</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($editTrainer['name'] ?? '') ?>">

        <label>Specialty</label>
        <input type="text" name="specialty" placeholder="Yoga / Cardio / Strength" value="<?= htmlspecialchars($editTrainer['specialty'] ?? '') ?>">

        <label>Experience / Short Bio</label>
        <textarea name="experience" placeholder="Certified trainer with 3 years of experience in strength & conditioning"><?= htmlspecialchars($editTrainer['experience'] ?? '') ?></textarea>

        <?php if($editMode && !empty($editTrainer['profile_image'])): ?>
          <label>Current Image</label>
          <div class="current-img-box">
            <img src="../uploads/trainers/<?= htmlspecialchars($editTrainer['profile_image']) ?>" alt="Current Trainer Image">
          </div>
        <?php endif; ?>

        <label>Profile Image <?= $editMode ? '(Upload only if you want to change)' : '' ?></label>
        <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">

        <div class="form-actions">
          <?php if($editMode): ?>
            <button class="btn btn-blue" type="submit" name="update_trainer">Update Trainer</button>
            <a href="trainers.php" class="btn btn-gray">Cancel</a>
          <?php else: ?>
            <button class="btn btn-green" type="submit" name="add_trainer">Add Trainer</button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card">
      <h2>Trainer List</h2>
      <table>
        <tr>
          <th>Photo</th>
          <th>ID</th>
          <th>Name</th>
          <th>Specialty</th>
          <th>Experience</th>
          <th>Action</th>
        </tr>

        <?php if (!empty($trainers)): ?>
          <?php foreach($trainers as $t): ?>
          <tr>
            <td>
              <?php if (!empty($t['profile_image'])): ?>
                <img class="trainer-thumb" src="../uploads/trainers/<?= htmlspecialchars($t['profile_image']) ?>" alt="Trainer">
              <?php else: ?>
                <div class="no-img">No Img</div>
              <?php endif; ?>
            </td>
            <td><?= (int)$t['id'] ?></td>
            <td><?= htmlspecialchars($t['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($t['specialty'] ?? '') ?></td>
            <td><?= htmlspecialchars($t['experience'] ?? '') ?></td>
            <td>
              <div class="action-wrap">
                <a class="btn btn-blue" href="?edit=<?= (int)$t['id'] ?>">Edit</a>
                <a class="btn btn-red" href="?delete=<?= (int)$t['id'] ?>" onclick="return confirm('Delete trainer?')">Delete</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6">No trainers found.</td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>
</body>
</html>
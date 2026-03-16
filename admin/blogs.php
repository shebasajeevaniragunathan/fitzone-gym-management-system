<?php
session_start();
require_once "../config/db.php"; // $conn

// ✅ Optional: admin check
// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
//     header("Location: ../login.php");
//     exit;
// }

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Upload settings
$uploadDir = __DIR__ . "/../uploads/blogs/";
$uploadUrl = "../uploads/blogs/"; // for img src

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

/* =========================
   ADD BLOG
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_blog'])) {

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    if ($category_id <= 0) $category_id = null;

    $imageName = null;

    // ✅ handle image upload
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (in_array($ext, $allowed, true)) {
            $imageName = "blog_" . time() . "_" . rand(1000,9999) . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    if ($title !== '' && $content !== '') {
        $stmt = $conn->prepare("INSERT INTO blogs (category_id, title, content, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $category_id, $title, $content, $imageName);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: blogs.php");
    exit;
}

/* =========================
   UPDATE BLOG
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_blog'])) {

    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    if ($category_id <= 0) $category_id = null;

    // fetch old image
    $oldImage = null;
    if ($id > 0) {
        $st = $conn->prepare("SELECT image FROM blogs WHERE id=? LIMIT 1");
        $st->bind_param("i", $id);
        $st->execute();
        $oldImage = $st->get_result()->fetch_assoc()['image'] ?? null;
        $st->close();
    }

    $imageName = $oldImage;

    // ✅ new upload overwrite
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (in_array($ext, $allowed, true)) {
            $imageName = "blog_" . time() . "_" . rand(1000,9999) . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);

            // delete old file
            if ($oldImage && file_exists($uploadDir . $oldImage)) {
                @unlink($uploadDir . $oldImage);
            }
        }
    }

    if ($id > 0 && $title !== '' && $content !== '') {
        $stmt = $conn->prepare("
            UPDATE blogs
            SET category_id=?, title=?, content=?, image=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->bind_param("isssi", $category_id, $title, $content, $imageName, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: blogs.php");
    exit;
}

/* =========================
   DELETE BLOG
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id > 0) {
        // delete image too
        $st = $conn->prepare("SELECT image FROM blogs WHERE id=? LIMIT 1");
        $st->bind_param("i", $id);
        $st->execute();
        $img = $st->get_result()->fetch_assoc()['image'] ?? null;
        $st->close();

        $stmt = $conn->prepare("DELETE FROM blogs WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        if ($img && file_exists($uploadDir . $img)) {
            @unlink($uploadDir . $img);
        }
    }

    header("Location: blogs.php");
    exit;
}

/* =========================
   EDIT MODE FETCH
========================= */
$editBlog = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) {
        $st = $conn->prepare("SELECT * FROM blogs WHERE id=? LIMIT 1");
        $st->bind_param("i", $id);
        $st->execute();
        $editBlog = $st->get_result()->fetch_assoc();
        $st->close();
    }
}

/* =========================
   FETCH CATEGORIES
========================= */
$cats = [];
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
while($c = $catRes->fetch_assoc()){
    $cats[] = $c;
}

/* =========================
   FETCH BLOG LIST
========================= */
$list = $conn->query("
    SELECT b.*, c.name AS category_name
    FROM blogs b
    LEFT JOIN categories c ON c.id = b.category_id
    ORDER BY b.id DESC
");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Blogs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background:#f4f6f9; }
    .card { border-radius:14px; }
    .thumb { width:70px; height:55px; object-fit:cover; border-radius:10px; border:1px solid #e7e7e7; }
  </style>
</head>

<body>
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="fw-bold mb-0">📝 Manage Blogs</h3>
    <a href="blogs.php" class="btn btn-outline-secondary btn-sm">Refresh</a>
  </div>

  <div class="row g-4">

    <!-- LEFT: ADD/EDIT FORM -->
    <div class="col-md-4">
      <div class="card shadow-sm border-0 p-3">
        <h5 class="fw-bold mb-3">
          <?php echo $editBlog ? "✏️ Edit Blog" : "➕ Add Blog"; ?>
        </h5>

        <form method="post" enctype="multipart/form-data" autocomplete="off">
          <div class="mb-2">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required
              value="<?php echo h($editBlog['title'] ?? ''); ?>">
          </div>

          <div class="mb-2">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
              <option value="0">-- Select --</option>
              <?php foreach($cats as $cat){ ?>
                <option value="<?php echo (int)$cat['id']; ?>"
                  <?php echo (($editBlog['category_id'] ?? 0) == $cat['id']) ? 'selected' : ''; ?>>
                  <?php echo h($cat['name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="6" required><?php echo h($editBlog['content'] ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Image (optional)</label>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <?php if(!empty($editBlog['image'])){ ?>
              <div class="mt-2">
                <img class="thumb" src="<?php echo $uploadUrl . h($editBlog['image']); ?>" alt="Blog">
              </div>
            <?php } ?>
          </div>

          <?php if($editBlog){ ?>
            <input type="hidden" name="id" value="<?php echo (int)$editBlog['id']; ?>">
            <button class="btn btn-success w-100" name="update_blog">Update Blog</button>
            <a href="blogs.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
          <?php } else { ?>
            <button class="btn btn-primary w-100" name="add_blog">Add Blog</button>
          <?php } ?>
        </form>

      </div>
    </div>

    <!-- RIGHT: LIST -->
    <div class="col-md-8">
      <div class="card shadow-sm border-0 p-3">
        <h5 class="fw-bold mb-3">Existing Blogs</h5>

        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:70px;">ID</th>
                <th style="width:90px;">Image</th>
                <th>Title</th>
                <th style="width:140px;">Category</th>
                <th style="width:90px;">Views</th>
                <th style="width:160px;">Created</th>
                <th style="width:170px;">Action</th>
              </tr>
            </thead>
            <tbody>
            <?php if($list && $list->num_rows > 0){ ?>
              <?php while($b = $list->fetch_assoc()){ ?>
                <tr>
                  <td><?php echo (int)$b['id']; ?></td>
                  <td>
                    <?php if(!empty($b['image'])){ ?>
                      <img class="thumb" src="<?php echo $uploadUrl . h($b['image']); ?>" alt="img">
                    <?php } else { ?>
                      <div class="text-muted small">No image</div>
                    <?php } ?>
                  </td>
                  <td>
                    <div class="fw-semibold"><?php echo h($b['title']); ?></div>
                    <div class="text-muted small">
                      <?php echo h(mb_strimwidth(strip_tags($b['content']), 0, 80, "...")); ?>
                    </div>
                  </td>
                  <td><?php echo h($b['category_name'] ?? '—'); ?></td>
                  <td><?php echo (int)($b['views'] ?? 0); ?></td>
                  <td><?php echo h($b['created_at']); ?></td>
                  <td class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-warning btn-sm" href="blogs.php?edit=<?php echo (int)$b['id']; ?>">Edit</a>
                    <a class="btn btn-danger btn-sm"
                       href="blogs.php?delete=<?php echo (int)$b['id']; ?>"
                       onclick="return confirm('Delete this blog?');">
                      Delete
                    </a>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">No blogs found.</td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </div>
</div>
</body>
</html>